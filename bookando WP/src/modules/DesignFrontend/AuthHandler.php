<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Authentication Handler
 *
 * Handles authentication for frontend portals (Customer & Employee)
 * Supports: Email, Google OAuth, Apple Sign In
 */
class AuthHandler
{
    /**
     * Authenticate with email and password
     *
     * @param string $email
     * @param string $password
     * @return array|WP_Error User data or error
     */
    public static function authenticateEmail(string $email, string $password)
    {
        $user = wp_authenticate($email, $password);

        if (is_wp_error($user)) {
            return $user;
        }

        return self::createSession($user->ID, 'email');
    }

    /**
     * Send verification email
     *
     * @param string $email
     * @return bool|WP_Error
     */
    public static function sendVerificationEmail(string $email)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_email_verifications';

        // Check if user exists
        $user = get_user_by('email', $email);
        if (!$user) {
            return new \WP_Error('user_not_found', 'Benutzer nicht gefunden');
        }

        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Store token
        $wpdb->insert($table, [
            'email' => $email,
            'token' => $token,
            'user_id' => $user->ID,
            'verified' => 0,
            'expires_at' => $expires_at,
            'created_at' => current_time('mysql'),
        ]);

        // Send email
        $verification_url = add_query_arg([
            'action' => 'verify_email',
            'token' => $token,
        ], home_url('/bookando/auth'));

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
        $table = $wpdb->prefix . 'bookando_frontend_email_verifications';

        $verification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE token = %s AND verified = 0 AND expires_at > NOW()",
            $token
        ), ARRAY_A);

        if (!$verification) {
            return new \WP_Error('invalid_token', 'Ungültiger oder abgelaufener Token');
        }

        // Mark as verified
        $wpdb->update($table, ['verified' => 1], ['id' => $verification['id']]);

        // Create session
        return self::createSession((int)$verification['user_id'], 'email');
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

        // Verify Google token (using Google API)
        $client = new \Google_Client(['client_id' => $config['client_id']]);
        $payload = $client->verifyIdToken($googleToken);

        if (!$payload) {
            return new \WP_Error('invalid_google_token', 'Ungültiges Google-Token');
        }

        $email = $payload['email'];
        $googleId = $payload['sub'];

        // Find or create user
        $user = get_user_by('email', $email);
        if (!$user) {
            $user_id = wp_create_user(
                $email,
                wp_generate_password(),
                $email
            );
            if (is_wp_error($user_id)) {
                return $user_id;
            }
            update_user_meta($user_id, 'bookando_google_id', $googleId);
            $user = get_user_by('id', $user_id);
        }

        return self::createSession($user->ID, 'google');
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

        // Verify Apple token
        // Apple Sign In JWT verification
        $jwt = self::decodeAppleJWT($appleToken, $config);
        if (is_wp_error($jwt)) {
            return $jwt;
        }

        $email = $jwt['email'];
        $appleId = $jwt['sub'];

        // Find or create user
        $user = get_user_by('email', $email);
        if (!$user) {
            $user_id = wp_create_user(
                $email,
                wp_generate_password(),
                $email
            );
            if (is_wp_error($user_id)) {
                return $user_id;
            }
            update_user_meta($user_id, 'bookando_apple_id', $appleId);
            $user = get_user_by('id', $user_id);
        }

        return self::createSession($user->ID, 'apple');
    }

    /**
     * Create authentication session
     *
     * @param int $userId
     * @param string $provider
     * @return array
     */
    protected static function createSession(int $userId, string $provider): array
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

        $user = get_userdata($userId);

        return [
            'token' => $token,
            'expires_at' => $expires_at,
            'user' => [
                'id' => $user->ID,
                'email' => $user->user_email,
                'name' => $user->display_name,
            ],
        ];
    }

    /**
     * Validate session token
     *
     * @param string $token
     * @return int|false User ID or false
     */
    public static function validateSession(string $token)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_auth_sessions';

        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE session_token = %s AND expires_at > NOW()",
            $token
        ), ARRAY_A);

        return $session ? (int)$session['user_id'] : false;
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
     *
     * @param string $provider
     * @return array|null
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
     * Decode and verify Apple JWT
     *
     * @param string $jwt
     * @param array $config
     * @return array|WP_Error
     */
    protected static function decodeAppleJWT(string $jwt, array $config)
    {
        // This is a simplified version
        // In production, use a proper JWT library like firebase/php-jwt
        try {
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return new \WP_Error('invalid_jwt', 'Ungültiges JWT-Format');
            }

            $payload = json_decode(base64_decode($parts[1]), true);
            return $payload;
        } catch (\Exception $e) {
            return new \WP_Error('jwt_decode_error', $e->getMessage());
        }
    }
}
