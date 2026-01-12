<?php
namespace Bookando\Core\Auth;

use WP_Error;
use WP_REST_Request;
use Bookando\Core\Role\CapabilityService;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Service\ActivityLogger;
use function _x;

class Gate
{
    /**
     * Dev-Bypass mit Production-Schutz und Audit-Logging.
     *
     * SICHERHEIT: Wenn WP_ENVIRONMENT_TYPE nicht gesetzt ist, wird 'production' angenommen.
     * Dies verhindert versehentliche Bypasses in Production-Umgebungen.
     *
     * @return bool
     */
    public static function devBypass(): bool
    {
        // ⚠️ KRITISCH: NIEMALS in Production!
        // Default ist 'production' wenn nicht explizit gesetzt
        $environment = defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production';

        if ($environment === 'production') {
            return false;
        }

        // Admin im DEV darf alles
        $bypass = defined('BOOKANDO_DEV') && BOOKANDO_DEV && current_user_can('manage_options');

        if ($bypass) {
            // 🔒 Audit-Log für DevBypass-Nutzung
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[1]['function'] ?? 'unknown';
            $class = $backtrace[1]['class'] ?? '';

            ActivityLogger::warning('security.devbypass', 'DevBypass verwendet', [
                'user_id' => get_current_user_id(),
                'caller' => $class ? "{$class}::{$caller}" : $caller,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'environment' => $environment
            ]);
        }

        return $bypass;
    }

    /**
     * Rate Limiting für einen bestimmten Identifier (z.B. User-ID, IP, Endpoint).
     *
     * @param string $identifier Eindeutiger Schlüssel (z.B. "rest_login_user_123" oder "rest_create_ip_192.168.1.1")
     * @param int $maxAttempts Maximale Anzahl von Versuchen im Zeitfenster
     * @param int $windowSeconds Zeitfenster in Sekunden
     * @return bool True wenn erlaubt, False wenn Rate Limit überschritten
     */
    public static function checkRateLimit(string $identifier, int $maxAttempts = 10, int $windowSeconds = 60): bool
    {
        // DevBypass überspringt Rate Limiting (nur in DEV!)
        if (self::devBypass()) {
            return true;
        }

        $key = 'bookando_ratelimit_' . md5($identifier);
        $attempts = (int) get_transient($key);

        if ($attempts >= $maxAttempts) {
            // Rate Limit überschritten - loggen
            ActivityLogger::warning('security', 'Rate limit exceeded', [
                'identifier' => $identifier,
                'attempts' => $attempts,
                'max' => $maxAttempts,
                'window' => $windowSeconds
            ]);

            return false;
        }

        // Erhöhe Counter
        set_transient($key, $attempts + 1, $windowSeconds);

        return true;
    }

    /**
     * Hilfsmethode: Rate Limit für REST-Request basierend auf User oder IP.
     *
     * @param WP_REST_Request $request
     * @param string $action Action-Name (z.B. "create_customer")
     * @param int $maxAttempts
     * @param int $windowSeconds
     * @return WP_Error|bool True wenn erlaubt, WP_Error wenn blockiert
     */
    public static function checkRestRateLimit(
        WP_REST_Request $request,
        string $action,
        int $maxAttempts = 20,
        int $windowSeconds = 60
    ) {
        $userId = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Identifier: Bevorzuge User-ID, sonst IP
        $identifier = $userId > 0
            ? "rest_{$action}_user_{$userId}"
            : "rest_{$action}_ip_{$ip}";

        if (!self::checkRateLimit($identifier, $maxAttempts, $windowSeconds)) {
            return new WP_Error(
                'rest_rate_limit_exceeded',
                _x('Rate limit exceeded. Please try again later.', 'REST API error message', 'bookando'),
                ['status' => 429, 'retry_after' => $windowSeconds]
            );
        }

        return true;
    }

    /**
     * Hilfsmethode: Setze Rate Limit zurück (z.B. nach erfolgreichem Login).
     *
     * @param string $identifier
     * @return void
     */
    public static function resetRateLimit(string $identifier): void
    {
        $key = 'bookando_ratelimit_' . md5($identifier);
        delete_transient($key);
    }

    public static function moduleCap(string $module): string
    {
        return CapabilityService::moduleCap($module);
    }

    public static function hasCapability(string $capability): bool
    {
        if (self::devBypass()) {
            return true;
        }

        return current_user_can($capability);
    }

    public static function canManage(string $module): bool
    {
        if (self::devBypass()) return true;
        return current_user_can(self::moduleCap($module)) || current_user_can('manage_options');
    }

    /** True für POST/PUT/PATCH/DELETE – dort ist Nonce Pflicht */
    public static function isWrite(WP_REST_Request $request): bool
    {
        $m = strtoupper((string) $request->get_method());
        return in_array($m, ['POST','PUT','PATCH','DELETE'], true);
    }


    public static function verifyNonce(WP_REST_Request $request): bool
    {
        // Header bevorzugen, Fallback auf $_SERVER
        $nonce = '';
        if (method_exists($request, 'get_header')) {
            $nonce = (string) $request->get_header('X-WP-Nonce');
        }
        if ($nonce === '') {
            $nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
        }
        return is_string($nonce) && wp_verify_nonce($nonce, 'wp_rest');
    }

    /**
     * Zentraler Erlaubnis-Check pro Request/Modul.
     * - DEV-Bypass: Admin darf alles
     * - Writes: Login + Nonce + Modul-Capability
     * - Reads:  Login + (Modul-Capability ODER 'read')
     */
    public static function allow(WP_REST_Request $request, string $module): bool
    {
        return self::evaluate($request, $module) === true;
    }

    /**
     * Wie {@see allow()}, liefert bei Verweigerung jedoch einen WP_Error.
     */
    public static function evaluate(WP_REST_Request $request, string $module)
    {
        if (self::devBypass()) {
            return true;
        }

        // 🔒 Rate Limiting mit verbesserter Middleware
        $rateLimitCheck = \Bookando\Core\Middleware\RateLimitMiddleware::apply($request);
        if ($rateLimitCheck instanceof WP_Error) {
            return $rateLimitCheck;
        }

        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_unauthorized',
                _x('Not logged in', 'REST API error message', 'bookando'),
                ['status' => 401]
            );
        }

        // ❗ Ohne gültigen Tenant kein Zugriff (Safety-Net)
        $tenantId = TenantManager::currentTenantId();
        if ($tenantId <= 0) {
            return new WP_Error(
                'rest_tenant_forbidden',
                _x('Tenant scope invalid', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }

        // Lizenz-Feature: read vs. write
        if (self::isWrite($request)) {
            if (!self::verifyNonce($request)) {
                return new WP_Error(
                    'rest_nonce_invalid',
                    _x('Invalid nonce', 'REST API error message', 'bookando'),
                    ['status' => 401]
                );
            }

            // Schreibende REST-Calls nur, wenn Feature freigeschaltet
            if (!LicenseManager::isFeatureEnabled('rest_api_write')) {
                return new WP_Error(
                    'rest_license_write_disabled',
                    _x('REST write feature disabled by license', 'REST API error message', 'bookando'),
                    ['status' => 403]
                );
            }

            if (!self::canManage($module)) {
                return new WP_Error(
                    'rest_forbidden',
                    _x('Insufficient capability', 'REST API error message', 'bookando'),
                    ['status' => 403]
                );
            }

            return true;
        }

        if (!LicenseManager::isFeatureEnabled('rest_api_read')) {
            return new WP_Error(
                'rest_license_read_disabled',
                _x('REST read feature disabled by license', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }

        if (self::canManage($module) || current_user_can('read')) {
            return true;
        }

        return new WP_Error(
            'rest_forbidden',
            _x('Insufficient capability', 'REST API error message', 'bookando'),
            ['status' => 403]
        );
    }

    public static function isSelf(int $bookandoId): bool
    {
        global $wpdb;
        // external_id ist VARCHAR → sauber in INT casten
        $ext = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT CAST(external_id AS UNSIGNED) FROM {$wpdb->prefix}bookando_users WHERE id = %d",
            $bookandoId
        ));
        return $ext > 0 && get_current_user_id() === $ext;
    }
}
