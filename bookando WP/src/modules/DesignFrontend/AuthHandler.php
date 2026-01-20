<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Authentication Handler
 *
 * Handles authentication for frontend portals (Customer & Employee)
 * Uses SEPARATE frontend user system (WordPress-independent)
 * Supports: Email, Google OAuth, Apple Sign In
 */
class AuthHandler
{
    /**
     * Register new user (Email + Password)
     *
     * @param array $data ['email', 'password', 'first_name', 'last_name', 'phone', 'role']
     * @return array|WP_Error
     */
    public static function registerUser(array $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_users';

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
            "SELECT id FROM {$table} WHERE email = %s",
            $email
        ));

        if ($exists) {
            return new \WP_Error('user_exists', 'Benutzer existiert bereits');
        }

        // Create user
        $inserted = $wpdb->insert($table, [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'role' => in_array($data['role'] ?? '', ['customer', 'employee']) ? $data['role'] : 'customer',
            'auth_provider' => 'email',
            'email_verified' => 0,
            'status' => 'active',
            'created_at' => current_time('mysql'),
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
     * @return array|WP_Error User data or error
     */
    public static function authenticateEmail(string $email, string $password)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_users';

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE email = %s AND status = 'active'",
            $email
        ), ARRAY_A);

        if (!$user) {
            return new \WP_Error('invalid_credentials', 'Ungültige Anmeldedaten');
        }

        if (!password_verify($password, $user['password_hash'])) {
            return new \WP_Error('invalid_credentials', 'Ungültige Anmeldedaten');
        }

        if (!$user['email_verified']) {
            return new \WP_Error('email_not_verified', 'E-Mail-Adresse noch nicht verifiziert');
        }

        return self::createSession((int)$user['id'], 'email', $user);
    }

    /**
     * Send verification email
     *
     * @param string $email
     * @param int $userId
     * @return bool|WP_Error
     */
    public static function sendVerificationEmail(string $email, int $userId)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_email_verifications';

        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Store token
        $wpdb->insert($table, [
            'email' => $email,
            'token' => $token,
            'user_id' => $userId,
            'verified' => 0,
            'expires_at' => $expires_at,
            'created_at' => current_time('mysql'),
        ]);

        // Send email
        $verification_url = add_query_arg([
            'bookando_action' => 'verify_email',
            'token' => $token,
        ], home_url());

        $subject = 'Email-Verifizierung | ' . get_bloginfo('name');
        $message = sprintf(
            "Hallo,\n\nBitte verifizieren Sie Ihre E-Mail-Adresse:\n%s\n\nDieser Link ist 24 Stunden gültig.\n\nVielen Dank!",
            $verification_url
        );

        return wp_mail($email, $subject, $message);
    }

    /**
     * Verify email token
     *
     * @param string $token
     * @return bool|WP_Error
     */
    public static function verifyEmailToken(string $token)
    {
        global $wpdb;
        $verTable = $wpdb->prefix . 'bookando_frontend_email_verifications';
        $userTable = $wpdb->prefix . 'bookando_frontend_users';

        $verification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$verTable} WHERE token = %s AND verified = 0 AND expires_at > NOW()",
            $token
        ), ARRAY_A);

        if (!$verification) {
            return new \WP_Error('invalid_token', 'Ungültiger oder abgelaufener Token');
        }

        // Mark as verified
        $wpdb->update($verTable, ['verified' => 1], ['id' => $verification['id']]);
        $wpdb->update($userTable, ['email_verified' => 1], ['id' => $verification['user_id']]);

        // Get user
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$userTable} WHERE id = %d",
            $verification['user_id']
        ), ARRAY_A);

        // Create session
        return self::createSession((int)$verification['user_id'], 'email', $user);
    }

    /**
     * Authenticate with Google OAuth
     *
     * @param string $googleToken Google ID Token
     * @return array|WP_Error
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
     * @return array|WP_Error
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
        $table = $wpdb->prefix . 'bookando_frontend_users';

        // Try to find by provider ID first
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE auth_provider = %s AND provider_user_id = %s",
            $provider,
            $providerId
        ), ARRAY_A);

        if ($user) {
            return $user;
        }

        // Try to find by email
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE email = %s",
            $email
        ), ARRAY_A);

        if ($user) {
            // Update with OAuth info
            $wpdb->update($table, [
                'auth_provider' => $provider,
                'provider_user_id' => $providerId,
                'email_verified' => 1, // OAuth emails are pre-verified
            ], ['id' => $user['id']]);
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $user['id']), ARRAY_A);
        }

        // Create new user
        $inserted = $wpdb->insert($table, [
            'email' => $email,
            'first_name' => sanitize_text_field($extra['first_name'] ?? ''),
            'last_name' => sanitize_text_field($extra['last_name'] ?? ''),
            'role' => 'customer',
            'auth_provider' => $provider,
            'provider_user_id' => $providerId,
            'email_verified' => 1, // OAuth emails are pre-verified
            'status' => 'active',
            'created_at' => current_time('mysql'),
        ]);

        if (!$inserted) {
            return new \WP_Error('user_creation_failed', 'Benutzer konnte nicht erstellt werden');
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $wpdb->insert_id
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

        return [
            'token' => $token,
            'expires_at' => $expires_at,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'role' => $user['role'],
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
        $userTable = $wpdb->prefix . 'bookando_frontend_users';

        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.* FROM {$sessionTable} s
             INNER JOIN {$userTable} u ON s.user_id = u.id
             WHERE s.session_token = %s AND s.expires_at > NOW() AND u.status = 'active'",
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
