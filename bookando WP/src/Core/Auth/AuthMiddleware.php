<?php
declare(strict_types=1);

namespace Bookando\Core\Auth;

use WP_Error;
use WP_REST_Request;
use WP_User;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Service\ActivityLogger;

/**
 * Multi-Layer Authentication Middleware.
 *
 * Unterstützt drei Authentifizierungsmethoden (in dieser Reihenfolge):
 * 1. JWT Token (Bearer Authorization Header) - Für Mobile Apps & SaaS
 * 2. API Key (X-API-Key Header) - Für Server-to-Server Integration
 * 3. WordPress Session (Cookie) - Für WordPress-Plugin Frontend
 *
 * Workflow:
 * - Extrahiert Auth-Credentials aus Request
 * - Validiert Credentials
 * - Setzt WordPress User Context (wp_set_current_user)
 * - Setzt Tenant Context (TenantManager::setCurrentTenantId)
 * - Loggt Authentifizierung (ActivityLogger)
 *
 * Usage:
 * ```php
 * add_filter('rest_pre_dispatch', [AuthMiddleware::class, 'authenticate'], 10, 3);
 * ```
 */
final class AuthMiddleware
{
    /** Cache für authentifizierten User (pro Request) */
    private static ?array $authenticatedContext = null;

    /**
     * REST API Pre-Dispatch Hook.
     * Wird vor jedem REST-Request ausgeführt.
     *
     * @param mixed $result Response to replace the requested version with. Can be anything a normal endpoint can return, or null to not hijack the request.
     * @param WP_REST_Server $server Server instance.
     * @param WP_REST_Request $request Request used to generate the response.
     * @return mixed
     */
    public static function authenticate($result, $server, WP_REST_Request $request)
    {
        // Nur Bookando-Routen authentifizieren
        $route = $request->get_route();
        if (!self::isBookandoRoute($route)) {
            return $result;
        }

        // Bereits authentifiziert? (Cache)
        if (self::$authenticatedContext !== null) {
            return $result;
        }

        // Versuche Authentifizierung
        $authResult = self::authenticateRequest($request);

        if ($authResult instanceof WP_Error) {
            // Authentifizierung fehlgeschlagen - loggen
            ActivityLogger::warning('auth.failed', $authResult->get_error_message(), [
                'route' => $route,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);

            // Für öffentliche Routen: kein Error (anonymer Zugriff erlaubt)
            if (self::isPublicRoute($route)) {
                return $result;
            }

            // Ansonsten: Error zurückgeben
            return $authResult;
        }

        // Authentifizierung erfolgreich
        if ($authResult !== null) {
            self::$authenticatedContext = $authResult;

            // User-Context setzen
            wp_set_current_user($authResult['user_id']);

            // Tenant-Context setzen
            TenantManager::setCurrentTenantId($authResult['tenant_id']);

            // Erfolgs-Log
            ActivityLogger::info('auth.success', 'User authenticated', [
                'user_id' => $authResult['user_id'],
                'tenant_id' => $authResult['tenant_id'],
                'method' => $authResult['method'],
                'route' => $route,
            ]);
        }

        return $result;
    }

    /**
     * Versucht Request zu authentifizieren.
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error|null
     *   - array: ['user_id' => int, 'tenant_id' => int, 'method' => string]
     *   - WP_Error: Authentifizierung fehlgeschlagen
     *   - null: Keine Auth-Credentials vorhanden (anonymous)
     */
    public static function authenticateRequest(WP_REST_Request $request)
    {
        // 1. JWT Token (Bearer Authorization)
        $jwtResult = self::authenticateJWT($request);
        if ($jwtResult !== null) {
            return $jwtResult;
        }

        // 2. API Key (X-API-Key Header)
        $apiKeyResult = self::authenticateAPIKey($request);
        if ($apiKeyResult !== null) {
            return $apiKeyResult;
        }

        // 3. WordPress Session (Cookie)
        $sessionResult = self::authenticateSession($request);
        if ($sessionResult !== null) {
            return $sessionResult;
        }

        // Keine Authentifizierung vorhanden
        return null;
    }

    /**
     * JWT Token Authentication.
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error|null
     */
    private static function authenticateJWT(WP_REST_Request $request)
    {
        // Bearer Token aus Authorization Header extrahieren
        $authHeader = $request->get_header('Authorization');
        if (!$authHeader || !is_string($authHeader)) {
            return null;
        }

        // Format: "Bearer {token}"
        if (stripos($authHeader, 'Bearer ') !== 0) {
            return null;
        }

        $token = trim(substr($authHeader, 7));
        if ($token === '') {
            return new WP_Error(
                'jwt_empty_token',
                __('JWT token is empty.', 'bookando'),
                ['status' => 401]
            );
        }

        // Token validieren
        $payload = JWTService::validateToken($token);
        if ($payload instanceof WP_Error) {
            return $payload;
        }

        // User-ID und Tenant-ID extrahieren
        $userId = JWTService::getUserId($payload);
        $tenantId = JWTService::getTenantId($payload);

        // User existiert?
        $user = get_user_by('ID', $userId);
        if (!$user) {
            return new WP_Error(
                'jwt_user_not_found',
                __('User not found.', 'bookando'),
                ['status' => 401]
            );
        }

        return [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'method' => 'jwt',
            'token_payload' => $payload,
        ];
    }

    /**
     * API Key Authentication.
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error|null
     */
    private static function authenticateAPIKey(WP_REST_Request $request)
    {
        // API Key aus X-API-Key Header extrahieren
        $apiKey = $request->get_header('X-API-Key');
        if (!$apiKey || !is_string($apiKey)) {
            return null;
        }

        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            return new WP_Error(
                'api_key_empty',
                __('API key is empty.', 'bookando'),
                ['status' => 401]
            );
        }

        // API Key validieren und User auflösen
        $result = self::validateAPIKey($apiKey);
        if ($result instanceof WP_Error) {
            return $result;
        }

        return [
            'user_id' => $result['user_id'],
            'tenant_id' => $result['tenant_id'],
            'method' => 'api_key',
            'api_key_id' => $result['api_key_id'] ?? null,
        ];
    }

    /**
     * WordPress Session Authentication (Cookie-basiert).
     *
     * @param WP_REST_Request $request
     * @return array|null
     */
    private static function authenticateSession(WP_REST_Request $request): ?array
    {
        // Prüfe, ob User bereits eingeloggt ist (via Cookie)
        $userId = get_current_user_id();
        if ($userId === 0) {
            return null;
        }

        // Tenant-ID aus User-Meta oder Request-Parameter auflösen
        $tenantId = TenantManager::resolveFromRequest($request);

        return [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'method' => 'session',
        ];
    }

    /**
     * Validiert einen API Key und gibt User/Tenant zurück.
     *
     * API Keys werden in einer dedizierten Tabelle gespeichert:
     * - bookando_api_keys
     *
     * @param string $apiKey
     * @return array|WP_Error
     */
    private static function validateAPIKey(string $apiKey)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_api_keys';

        // API Key hashen (gespeichert wird nur Hash)
        $keyHash = hash('sha256', $apiKey);

        // Lookup in Datenbank
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id, tenant_id, name, permissions, rate_limit, last_used_at, expires_at, status
             FROM {$table}
             WHERE key_hash = %s",
            $keyHash
        ), ARRAY_A);

        if (!$row) {
            return new WP_Error(
                'api_key_invalid',
                __('Invalid API key.', 'bookando'),
                ['status' => 401]
            );
        }

        // Status prüfen
        if ($row['status'] !== 'active') {
            return new WP_Error(
                'api_key_inactive',
                __('API key is inactive.', 'bookando'),
                ['status' => 401]
            );
        }

        // Expiration prüfen
        if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) {
            return new WP_Error(
                'api_key_expired',
                __('API key has expired.', 'bookando'),
                ['status' => 401]
            );
        }

        // Rate Limit prüfen (falls konfiguriert)
        if (!empty($row['rate_limit'])) {
            $rateLimit = json_decode($row['rate_limit'], true);
            $identifier = 'api_key_' . $row['id'];
            $maxAttempts = $rateLimit['max_requests'] ?? 100;
            $windowSeconds = $rateLimit['window_seconds'] ?? 60;

            if (!Gate::checkRateLimit($identifier, $maxAttempts, $windowSeconds)) {
                return new WP_Error(
                    'api_key_rate_limit_exceeded',
                    __('API key rate limit exceeded.', 'bookando'),
                    ['status' => 429]
                );
            }
        }

        // Last-Used aktualisieren (asynchron für Performance)
        wp_schedule_single_event(time(), 'bookando_update_api_key_usage', [$row['id']]);

        return [
            'user_id' => (int) $row['user_id'],
            'tenant_id' => (int) $row['tenant_id'],
            'api_key_id' => (int) $row['id'],
            'permissions' => json_decode($row['permissions'] ?? '[]', true),
        ];
    }

    /**
     * Prüft, ob Route zu Bookando gehört.
     *
     * @param string $route
     * @return bool
     */
    private static function isBookandoRoute(string $route): bool
    {
        return stripos($route, '/bookando/') === 0;
    }

    /**
     * Prüft, ob Route öffentlich ist (kein Auth erforderlich).
     *
     * @param string $route
     * @return bool
     */
    private static function isPublicRoute(string $route): bool
    {
        $publicRoutes = [
            '/bookando/v1/auth/login',
            '/bookando/v1/auth/register',
            '/bookando/v1/auth/forgot-password',
            '/bookando/v1/public/events',          // Öffentliche Event-Liste
            '/bookando/v1/public/booking',         // Öffentliches Buchungsformular
            '/bookando/v1/integrations/webhook',   // Webhook-Callbacks
        ];

        foreach ($publicRoutes as $publicRoute) {
            if (stripos($route, $publicRoute) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Liefert den aktuellen authentifizierten Context.
     *
     * @return array|null ['user_id' => int, 'tenant_id' => int, 'method' => string]
     */
    public static function getCurrentContext(): ?array
    {
        return self::$authenticatedContext;
    }

    /**
     * Prüft, ob Request mit JWT authentifiziert wurde.
     *
     * @return bool
     */
    public static function isJWTAuth(): bool
    {
        return self::$authenticatedContext !== null
            && (self::$authenticatedContext['method'] ?? '') === 'jwt';
    }

    /**
     * Prüft, ob Request mit API Key authentifiziert wurde.
     *
     * @return bool
     */
    public static function isAPIKeyAuth(): bool
    {
        return self::$authenticatedContext !== null
            && (self::$authenticatedContext['method'] ?? '') === 'api_key';
    }

    /**
     * Prüft, ob Request mit Session authentifiziert wurde.
     *
     * @return bool
     */
    public static function isSessionAuth(): bool
    {
        return self::$authenticatedContext !== null
            && (self::$authenticatedContext['method'] ?? '') === 'session';
    }

    /**
     * Holt JWT-Payload aus authentifiziertem Context.
     *
     * @return array|null
     */
    public static function getJWTPayload(): ?array
    {
        if (!self::isJWTAuth()) {
            return null;
        }

        return self::$authenticatedContext['token_payload'] ?? null;
    }
}
