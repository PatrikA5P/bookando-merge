<?php
declare(strict_types=1);

namespace Bookando\Core\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;
use Bookando\Core\Auth\JWTService;
use Bookando\Core\Auth\Gate;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Service\ActivityLogger;

/**
 * Authentication REST API für Mobile Apps und SaaS-Integration.
 *
 * Endpoints:
 * - POST /bookando/v1/auth/login       - Login mit Credentials, gibt JWT Token zurück
 * - POST /bookando/v1/auth/refresh     - Refresh Access Token mit Refresh Token
 * - POST /bookando/v1/auth/logout      - Revoke Token (Logout)
 * - POST /bookando/v1/auth/register    - User-Registrierung (optional)
 * - GET  /bookando/v1/auth/me          - Aktueller User-Info
 */
final class AuthApi
{
    /**
     * Registriert alle Auth-Endpoints.
     */
    public static function register(): void
    {
        register_rest_route('bookando/v1', '/auth/login', [
            'methods' => 'POST',
            'callback' => [self::class, 'login'],
            'permission_callback' => '__return_true', // Öffentlich
        ]);

        register_rest_route('bookando/v1', '/auth/refresh', [
            'methods' => 'POST',
            'callback' => [self::class, 'refresh'],
            'permission_callback' => '__return_true', // Öffentlich
        ]);

        register_rest_route('bookando/v1', '/auth/logout', [
            'methods' => 'POST',
            'callback' => [self::class, 'logout'],
            'permission_callback' => 'is_user_logged_in', // Authentifiziert
        ]);

        register_rest_route('bookando/v1', '/auth/me', [
            'methods' => 'GET',
            'callback' => [self::class, 'me'],
            'permission_callback' => 'is_user_logged_in', // Authentifiziert
        ]);

        register_rest_route('bookando/v1', '/auth/register', [
            'methods' => 'POST',
            'callback' => [self::class, 'registerUser'],
            'permission_callback' => '__return_true', // Öffentlich
        ]);
    }

    /**
     * Login mit Username/Email + Password.
     * Gibt Access Token + Refresh Token zurück.
     *
     * POST /bookando/v1/auth/login
     * Body: {
     *   "username": "user@example.com",
     *   "password": "secret",
     *   "tenant_id": 1 (optional)
     * }
     *
     * Response: {
     *   "access_token": "...",
     *   "refresh_token": "...",
     *   "expires_in": 86400,
     *   "token_type": "Bearer",
     *   "user": {...}
     * }
     */
    public static function login(WP_REST_Request $request): WP_REST_Response
    {
        // Rate Limiting: Strict limit for login attempts
        $rateLimitCheck = \Bookando\Core\Middleware\RateLimitMiddleware::check($request, 'auth');
        if ($rateLimitCheck instanceof WP_Error) {
            return new WP_REST_Response($rateLimitCheck, 429);
        }

        $username = $request->get_param('username');
        $password = $request->get_param('password');
        $tenantId = $request->get_param('tenant_id');

        // Validierung
        if (empty($username) || empty($password)) {
            return new WP_REST_Response([
                'code' => 'missing_credentials',
                'message' => __('Username and password are required.', 'bookando'),
            ], 400);
        }

        // WordPress Authentication
        $user = wp_authenticate($username, $password);

        if ($user instanceof WP_Error) {
            ActivityLogger::warning('auth.login.failed', 'Login failed', [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            return new WP_REST_Response([
                'code' => 'invalid_credentials',
                'message' => __('Invalid username or password.', 'bookando'),
            ], 401);
        }

        // Tenant-ID auflösen
        if ($tenantId === null) {
            $tenantId = (int) get_user_meta($user->ID, 'user_tenant_id', true);
        } else {
            $tenantId = (int) $tenantId;
        }

        if ($tenantId <= 0) {
            $tenantId = 1; // Default Tenant
        }

        // JWT Tokens generieren
        $accessToken = JWTService::generateToken($user->ID, $tenantId);
        $refreshToken = JWTService::generateRefreshToken($user->ID, $tenantId);

        // Erfolgs-Log
        ActivityLogger::info('auth.login.success', 'User logged in via API', [
            'user_id' => $user->ID,
            'tenant_id' => $tenantId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        // Rate Limit zurücksetzen bei erfolgreichem Login
        Gate::resetRateLimit('rest_auth_login_ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        return new WP_REST_Response([
            'access_token' => $accessToken['token'],
            'refresh_token' => $refreshToken['token'],
            'expires_in' => 86400, // 24 Stunden
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->ID,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'tenant_id' => $tenantId,
            ],
        ], 200);
    }

    /**
     * Refresh Access Token mit Refresh Token.
     *
     * POST /bookando/v1/auth/refresh
     * Body: {
     *   "refresh_token": "..."
     * }
     *
     * Response: {
     *   "access_token": "...",
     *   "refresh_token": "...",
     *   "expires_in": 86400,
     *   "token_type": "Bearer"
     * }
     */
    public static function refresh(WP_REST_Request $request): WP_REST_Response
    {
        $refreshToken = $request->get_param('refresh_token');

        if (empty($refreshToken)) {
            return new WP_REST_Response([
                'code' => 'missing_refresh_token',
                'message' => __('Refresh token is required.', 'bookando'),
            ], 400);
        }

        // Token erneuern
        $result = JWTService::refreshAccessToken($refreshToken);

        if ($result instanceof WP_Error) {
            return new WP_REST_Response([
                'code' => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ], 401);
        }

        return new WP_REST_Response($result, 200);
    }

    /**
     * Logout (Token revoken).
     *
     * POST /bookando/v1/auth/logout
     * Header: Authorization: Bearer {token}
     *
     * Response: {
     *   "message": "Logged out successfully."
     * }
     */
    public static function logout(WP_REST_Request $request): WP_REST_Response
    {
        // JWT-Token aus Authorization Header extrahieren
        $authHeader = $request->get_header('Authorization');
        if ($authHeader && stripos($authHeader, 'Bearer ') === 0) {
            $token = trim(substr($authHeader, 7));
            $payload = JWTService::validateToken($token);

            if (!($payload instanceof WP_Error) && isset($payload['jti'])) {
                // Token revoken
                $ttl = isset($payload['exp']) ? ($payload['exp'] - time()) : 86400;
                JWTService::revokeToken($payload['jti'], $ttl);

                ActivityLogger::info('auth.logout', 'User logged out', [
                    'user_id' => get_current_user_id(),
                    'jti' => $payload['jti'],
                ]);
            }
        }

        return new WP_REST_Response([
            'message' => __('Logged out successfully.', 'bookando'),
        ], 200);
    }

    /**
     * Liefert Infos über aktuellen User.
     *
     * GET /bookando/v1/auth/me
     * Header: Authorization: Bearer {token}
     *
     * Response: {
     *   "id": 1,
     *   "email": "user@example.com",
     *   "display_name": "John Doe",
     *   "tenant_id": 1,
     *   "roles": ["administrator"],
     *   "capabilities": ["manage_bookando_customers", ...]
     * }
     */
    public static function me(WP_REST_Request $request): WP_REST_Response
    {
        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);

        if (!$user) {
            return new WP_REST_Response([
                'code' => 'user_not_found',
                'message' => __('User not found.', 'bookando'),
            ], 404);
        }

        $tenantId = TenantManager::currentTenantId();

        return new WP_REST_Response([
            'id' => $user->ID,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'tenant_id' => $tenantId,
            'roles' => $user->roles,
            'capabilities' => array_keys(array_filter($user->allcaps, fn($v) => $v === true)),
        ], 200);
    }

    /**
     * User-Registrierung (optional, deaktiviert wenn nicht erlaubt).
     *
     * POST /bookando/v1/auth/register
     * Body: {
     *   "email": "user@example.com",
     *   "password": "secret",
     *   "first_name": "John",
     *   "last_name": "Doe",
     *   "tenant_id": 1 (optional)
     * }
     *
     * Response: {
     *   "user_id": 123,
     *   "message": "User registered successfully."
     * }
     */
    public static function registerUser(WP_REST_Request $request): WP_REST_Response
    {
        // Prüfe, ob Registrierung erlaubt ist
        if (!get_option('users_can_register')) {
            return new WP_REST_Response([
                'code' => 'registration_disabled',
                'message' => __('User registration is disabled.', 'bookando'),
            ], 403);
        }

        // Rate Limiting: Strict limit for registration
        $rateLimitCheck = \Bookando\Core\Middleware\RateLimitMiddleware::check($request, 'auth');
        if ($rateLimitCheck instanceof WP_Error) {
            return new WP_REST_Response($rateLimitCheck, 429);
        }

        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $firstName = sanitize_text_field($request->get_param('first_name'));
        $lastName = sanitize_text_field($request->get_param('last_name'));
        $tenantId = (int) $request->get_param('tenant_id');

        // Validierung
        if (empty($email) || !is_email($email)) {
            return new WP_REST_Response([
                'code' => 'invalid_email',
                'message' => __('Invalid email address.', 'bookando'),
            ], 400);
        }

        if (empty($password) || strlen($password) < 8) {
            return new WP_REST_Response([
                'code' => 'weak_password',
                'message' => __('Password must be at least 8 characters long.', 'bookando'),
            ], 400);
        }

        // User existiert bereits?
        if (email_exists($email)) {
            return new WP_REST_Response([
                'code' => 'email_exists',
                'message' => __('This email is already registered.', 'bookando'),
            ], 400);
        }

        // Username aus E-Mail generieren
        $username = sanitize_user(current(explode('@', $email)), true);
        if (username_exists($username)) {
            $username = $username . '_' . wp_rand(1000, 9999);
        }

        // WordPress User erstellen
        $userId = wp_create_user($username, $password, $email);

        if ($userId instanceof WP_Error) {
            return new WP_REST_Response([
                'code' => 'registration_failed',
                'message' => $userId->get_error_message(),
            ], 500);
        }

        // User-Meta setzen
        wp_update_user([
            'ID' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => trim("$firstName $lastName"),
        ]);

        // Tenant-ID setzen
        if ($tenantId <= 0) {
            $tenantId = 1; // Default
        }
        update_user_meta($userId, 'user_tenant_id', $tenantId);

        // Standard-Rolle zuweisen
        $user = new WP_User($userId);
        $user->set_role('subscriber');

        ActivityLogger::info('auth.register', 'New user registered via API', [
            'user_id' => $userId,
            'email' => $email,
            'tenant_id' => $tenantId,
        ]);

        return new WP_REST_Response([
            'user_id' => $userId,
            'message' => __('User registered successfully.', 'bookando'),
        ], 201);
    }
}
