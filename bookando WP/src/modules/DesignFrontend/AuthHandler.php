<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Authentication Handler
 *
 * Handles authentication for frontend portals (Customer & Employee)
 * Uses bookando_users table (WordPress-independent, shared with core)
 * Supports: Email, Google OAuth, Apple Sign In
 */
class AuthHandler
{
    /**
     * Register new user (Email + Password)
     *
     * @param array $data ['email', 'password', 'first_name', 'last_name', 'phone', 'role']
     * @return array|\WP_Error
     */
    public static function registerUser(array $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_users';

        $email = sanitize_email($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!is_email($email)) {
            return new \WP_Error('invalid_email', 'Ungültige E-Mail-Adresse');
        }

        if (strlen($password) < 8) {
            return new \WP_Error('weak_password', 'Passwort muss mindestens 8 Zeichen haben');
        }

        // Check if user exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE email = %s AND deleted_at IS NULL",
            $email
        ));

        if ($exists) {
            return new \WP_Error('user_exists', 'Benutzer existiert bereits');
        }

        // Determine role
        $role = in_array($data['role'] ?? '', ['customer', 'employee', 'admin', 'teacher'])
            ? $data['role']
            : 'customer';
        $roles = wp_json_encode(["bookando_{$role}"]);

        // Create user
        $inserted = $wpdb->insert($table, [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'roles' => $roles,
            'status' => 'pending_verification', // Will be 'active' after email verification
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        if (!$inserted) {
            return new \WP_Error('registration_failed', 'Registrierung fehlgeschlagen');
        }

        $userId = (int)$wpdb->insert_id;

        // Send verification email
        self::sendVerificationEmail($email, $userId);

        return [
            'userId' => $userId,
            'email' => $email,
            'message' => 'Registrierung erfolgreich. Bitte überprüfen Sie Ihre E-Mail.',
        ];
    }

    /**
     * Authenticate with email and password
     *
     * @param string $email
     * @param string $password
     * @return array|\WP_Error User data or error
     */
    public static function authenticateEmail(string $email, string $password)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_users';

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE email = %s AND deleted_at IS NULL",
            $email
        ), ARRAY_A);

        if (!$user) {
            return new \WP_Error('invalid_credentials', 'Ungültige Anmeldedaten');
        }

        if (!$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
            return new \WP_Error('invalid_credentials', 'Ungültige Anmeldedaten');
        }

        // Allow login even if status is pending_verification (optional: enforce verification)
        if ($user['status'] !== 'active' && $user['status'] !== 'pending_verification') {
            return new \WP_Error('account_inactive', 'Konto ist inaktiv');
        }

        return self::createSession((int)$user['id'], 'email', $user);
    }

    /**
     * Send verification email
     *
     * @param string $email
     * @param int $userId
     * @return bool|\WP_Error
     */
    public static function sendVerificationEmail(string $email, int $userId)
    {
        global $wpdb;
        $userTable = $wpdb->prefix . 'bookando_users';

        // Generate token and store in password_reset_token field (reuse for verification)
        $token = bin2hex(random_bytes(32));

        $wpdb->update($userTable, [
            'password_reset_token' => $token,
            'updated_at' => current_time('mysql'),
        ], ['id' => $userId]);

        // Send email
        $verification_url = add_query_arg([
            'bookando_action' => 'verify_email',
            'token' => $token,
        ], home_url());

        $subject = 'Email-Verifizierung | ' . get_bloginfo('name');
        $message = sprintf(
            "Hallo,\n\nBitte verifizieren Sie Ihre E-Mail-Adresse:\n%s\n\nVielen Dank!",
            $verification_url
        );

        return wp_mail($email, $subject, $message);
    }

    /**
     * Verify email token
     *
     * @param string $token
     * @return bool|\WP_Error
     */
    public static function verifyEmailToken(string $token)
    {
        global $wpdb;
        $userTable = $wpdb->prefix . 'bookando_users';

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$userTable} WHERE password_reset_token = %s AND deleted_at IS NULL",
            $token
        ), ARRAY_A);

        if (!$user) {
            return new \WP_Error('invalid_token', 'Ungültiger Token');
        }

        // Mark as verified
        $wpdb->update($userTable, [
            'status' => 'active',
            'password_reset_token' => null,
            'updated_at' => current_time('mysql'),
        ], ['id' => $user['id']]);

        // Create session
        return self::createSession((int)$user['id'], 'email', $user);
    }

    /**
     * Authenticate with Google OAuth
     *
     * @param string $googleToken Google ID Token
     * @return array|\WP_Error
     */
    public static function authenticateGoogle(string $googleToken)
    {
        // Get Google client credentials
        $config = self::getProviderConfig('google');
        if (!$config || !$config['enabled']) {
            return new \WP_Error('google_disabled', 'Google-Anmeldung ist nicht aktiviert');
        }

        // Verify Google token (simplified - in production use Google API client library)
        try {
            $payload = self::verifyGoogleToken($googleToken, $config);
            if (!$payload) {
                return new \WP_Error('invalid_google_token', 'Ungültiges Google-Token');
            }

            $email = $payload['email'];
            $googleId = $payload['sub'];
            $firstName = $payload['given_name'] ?? '';
            $lastName = $payload['family_name'] ?? '';

            // Find or create user
            $user = self::findOrCreateOAuthUser($email, $googleId, 'google', [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            if (is_wp_error($user)) {
                return $user;
            }

            return self::createSession((int)$user['id'], 'google', $user);
        } catch (\Exception $e) {
            return new \WP_Error('google_auth_error', $e->getMessage());
        }
    }

    /**
     * Authenticate with Apple Sign In
     *
     * @param string $appleToken Apple ID Token
     * @return array|\WP_Error
     */
    public static function authenticateApple(string $appleToken)
    {
        // Get Apple client credentials
        $config = self::getProviderConfig('apple');
        if (!$config || !$config['enabled']) {
            return new \WP_Error('apple_disabled', 'Apple-Anmeldung ist nicht aktiviert');
        }

        try {
            // Verify Apple token
            $jwt = self::decodeAppleJWT($appleToken, $config);
            if (is_wp_error($jwt)) {
                return $jwt;
            }

            $email = $jwt['email'];
            $appleId = $jwt['sub'];

            // Find or create user
            $user = self::findOrCreateOAuthUser($email, $appleId, 'apple', []);

            if (is_wp_error($user)) {
                return $user;
            }

            return self::createSession((int)$user['id'], 'apple', $user);
        } catch (\Exception $e) {
            return new \WP_Error('apple_auth_error', $e->getMessage());
        }
    }

    /**
     * Find or create OAuth user
     */
    protected static function findOrCreateOAuthUser(string $email, string $providerId, string $provider, array $extra = [])
    {
        global $wpdb;
        $userTable = $wpdb->prefix . 'bookando_users';
        $oauthTable = $wpdb->prefix . 'bookando_frontend_oauth_links';

        // Try to find by OAuth link
        $oauthLink = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$oauthTable} WHERE provider = %s AND provider_user_id = %s",
            $provider,
            $providerId
        ), ARRAY_A);

        if ($oauthLink) {
            // Get user
            $user = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$userTable} WHERE id = %d AND deleted_at IS NULL",
                $oauthLink['user_id']
            ), ARRAY_A);

            if ($user) {
                return $user;
            }
        }

        // Try to find by email
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$userTable} WHERE email = %s AND deleted_at IS NULL",
            $email
        ), ARRAY_A);

        if ($user) {
            // Create OAuth link
            $wpdb->replace($oauthTable, [
                'user_id' => $user['id'],
                'provider' => $provider,
                'provider_user_id' => $providerId,
                'provider_email' => $email,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);

            // Activate account if pending
            if ($user['status'] === 'pending_verification') {
                $wpdb->update($userTable, ['status' => 'active'], ['id' => $user['id']]);
                $user['status'] = 'active';
            }

            return $user;
        }

        // Create new user
        $roles = wp_json_encode(['bookando_customer']);
        $inserted = $wpdb->insert($userTable, [
            'email' => $email,
            'first_name' => sanitize_text_field($extra['first_name'] ?? ''),
            'last_name' => sanitize_text_field($extra['last_name'] ?? ''),
            'roles' => $roles,
            'status' => 'active', // OAuth emails are pre-verified
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        if (!$inserted) {
            return new \WP_Error('user_creation_failed', 'Benutzer konnte nicht erstellt werden');
        }

        $userId = (int)$wpdb->insert_id;

        // Create OAuth link
        $wpdb->insert($oauthTable, [
            'user_id' => $userId,
            'provider' => $provider,
            'provider_user_id' => $providerId,
            'provider_email' => $email,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$userTable} WHERE id = %d",
            $userId
        ), ARRAY_A);
    }

    /**
     * Create authentication session
     *
     * @param int $userId
     * @param string $provider
     * @param array $user
     * @return array
     */
    protected static function createSession(int $userId, string $provider, array $user): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_auth_sessions';

        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));

        $wpdb->insert($table, [
            'session_token' => $token,
            'user_id' => $userId,
            'auth_provider' => $provider,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expires_at' => $expires_at,
            'created_at' => current_time('mysql'),
        ]);

        // Parse roles from JSON
        $roles = !empty($user['roles']) ? json_decode($user['roles'], true) : [];
        $primaryRole = is_array($roles) && !empty($roles) ? str_replace('bookando_', '', $roles[0]) : 'customer';

        return [
            'token' => $token,
            'expires_at' => $expires_at,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'role' => $primaryRole,
                'roles' => $roles,
            ],
        ];
    }

    /**
     * Validate session token and return user
     *
     * @param string $token
     * @return array|false User data or false
     */
    public static function validateSession(string $token)
    {
        global $wpdb;
        $sessionTable = $wpdb->prefix . 'bookando_frontend_auth_sessions';
        $userTable = $wpdb->prefix . 'bookando_users';

        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.* FROM {$sessionTable} s
             INNER JOIN {$userTable} u ON s.user_id = u.id
             WHERE s.session_token = %s AND s.expires_at > NOW() AND u.status = 'active' AND u.deleted_at IS NULL",
            $token
        ), ARRAY_A);

        return $session ?: false;
    }

    /**
     * Logout (invalidate session)
     *
     * @param string $token
     * @return bool
     */
    public static function logout(string $token): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_auth_sessions';

        return (bool)$wpdb->delete($table, ['session_token' => $token]);
    }

    /**
     * Get provider configuration
     */
    protected static function getProviderConfig(string $provider): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_auth_providers';

        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE provider = %s",
            $provider
        ), ARRAY_A);

        if (!$config) {
            return null;
        }

        $config['config'] = !empty($config['config']) ? json_decode($config['config'], true) : [];
        return $config;
    }

    /**
     * Verify Google JWT token
     */
    protected static function verifyGoogleToken(string $token, array $config): ?array
    {
        // Simplified - in production use Google API Client Library
        // For now, decode without full verification (demo purposes)
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        return $payload;
    }

    /**
     * Decode Apple JWT
     */
    protected static function decodeAppleJWT(string $jwt, array $config)
    {
        // Simplified - in production use proper JWT library
        try {
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return new \WP_Error('invalid_jwt', 'Ungültiges JWT-Format');
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            return $payload;
        } catch (\Exception $e) {
            return new \WP_Error('jwt_decode_error', $e->getMessage());
        }
    }
}
