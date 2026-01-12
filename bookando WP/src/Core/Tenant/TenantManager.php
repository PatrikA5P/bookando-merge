<?php
namespace Bookando\Core\Tenant;

use WP_REST_Request;

class TenantManager
{
    /** Per-Request Memoization */
    private static ?int $cachedTenantId = null;

    /** Cached tenant configuration */
    private static ?array $cachedTenantConfig = null;

    /** Aktuellen Tenant (mit Cache) liefern. */
    public static function currentTenantId(): int
    {
        if (self::$cachedTenantId !== null) {
            return self::$cachedTenantId;
        }

        $id = self::resolveFromRequest(null);
        $id = (int) ($id > 0 ? $id : 1);

        // Letzte Chance für Integrationen, den Tenant zu überschreiben
        if (function_exists('apply_filters')) {
            $id = (int) apply_filters('bookando_tenant_id_resolved', $id);
        }

        return self::$cachedTenantId = ($id > 0 ? $id : 1);
    }

    /** Tenant für die Restdauer des Requests manuell setzen/clearen. */
    public static function setCurrentTenantId(?int $tenantId): void
    {
        self::$cachedTenantId = $tenantId !== null ? max(1, (int) $tenantId) : null;
    }

    /** Cache zurücksetzen (nützlich für Tests/CLIs). */
    public static function reset(): void
    {
        self::$cachedTenantId = null;
        self::$cachedTenantConfig = null;
    }

    /**
     * Prüft, ob der aktuelle Kontext auf den Ziel-Tenant zugreifen darf.
     *
     * Filter-Hooks für Erweiterungen:
     *  - {@see 'bookando_tenant_allowed_targets'} erlaubt zusätzliche Tenant-IDs in die Allow-Liste aufzunehmen.
     *  - {@see 'bookando_tenant_is_allowed'} kann das finale Ergebnis überschreiben (z. B. via ACL oder canAccessShared()).
     */
    public static function isAllowedFor(int $tenantId): bool
    {
        if ($tenantId <= 0) {
            return false;
        }

        $currentTenant = self::currentTenantId();

        $allowed = ($tenantId === $currentTenant);

        if (function_exists('current_user_can')) {
            if (current_user_can('manage_options') || current_user_can('bookando_switch_tenant')) {
                $allowed = true;
            }
        }

        $allowedTargets = [$currentTenant];

        if (function_exists('get_option')) {
            $shared = get_option('bookando_shared_tenants');
            if (is_array($shared)) {
                $map = $shared[$currentTenant] ?? $shared[(string) $currentTenant] ?? [];
                if (!is_array($map)) {
                    $map = preg_split('/[,\s]+/', (string) $map, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                }
                foreach ($map as $id) {
                    $id = (int) $id;
                    if ($id > 0) {
                        $allowedTargets[] = $id;
                    }
                }
            }
        }

        if (function_exists('apply_filters')) {
            $allowedTargets = (array) apply_filters(
                'bookando_tenant_allowed_targets',
                array_values(array_unique(array_map('intval', $allowedTargets))),
                $currentTenant,
                $tenantId
            );
        }

        if (!$allowed) {
            $allowed = in_array($tenantId, array_map('intval', $allowedTargets), true);
        }

        if (function_exists('apply_filters')) {
            $allowed = (bool) apply_filters('bookando_tenant_is_allowed', $allowed, $tenantId, $currentTenant);
        }

        return $allowed;
    }

    /**
     * Tenant aus Request-Kontext bestimmen (Priorität):
     * 1) Header X-BOOKANDO-TENANT (nur wenn erlaubt)
     * 2) Param "tenant_id" (Route/Body/Query)
     * 3) User-Meta "user_tenant_id"
     * 4) Subdomain-Mapping (wenn aktiviert)
     * 5) Fallback: 1
     */
    public static function resolveFromRequest($request = null): int
    {
        $headerTenant = 0;
        $paramTenant = 0;
        $subdomainTenant = 0;
        $subdomain = '';
        $host = null;

        // 1) Sicherer Header-Override
        $headerTenant = self::sanitizeTenantId($_SERVER['HTTP_X_BOOKANDO_TENANT'] ?? null);
        if ($headerTenant > 0) {
            $allowed = function_exists('current_user_can')
                ? (current_user_can('manage_options') || current_user_can('bookando_switch_tenant'))
                : false;
            if (function_exists('apply_filters')) {
                // Integrationen können alternative Checks erlauben/verbieten
                $allowed = (bool) apply_filters('bookando_tenant_allow_header_switch', $allowed, $headerTenant);
            }
            if ($allowed) {
                return $headerTenant;
            }
        }

        // 2) Request-Param tenant_id (oder _tenant_id)
        if ($request instanceof WP_REST_Request) {
            $paramTenant = $request->get_param('tenant_id') ?? $request->get_param('_tenant_id');
        } elseif (is_array($request) && isset($request['tenant_id'])) {
            $paramTenant = $request['tenant_id'];
        } elseif (is_array($request) && isset($request['_tenant_id'])) {
            $paramTenant = $request['_tenant_id'];
        }
        $paramTenant = self::sanitizeTenantId($paramTenant ?? null);
        if ($paramTenant > 0) {
            return $paramTenant;
        }

        // 3) User-Meta (eingeloggt)
        $user = wp_get_current_user();
        if ($user instanceof \WP_User && $user->ID) {
            $tenantId = (int) get_user_meta($user->ID, 'user_tenant_id', true);
            if ($tenantId > 0) {
                return $tenantId;
            }
        }

        // 4) Subdomain-Mapping (SaaS)
        if (defined('BOOKANDO_SUBDOMAIN_MULTI_TENANT') && BOOKANDO_SUBDOMAIN_MULTI_TENANT) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (is_string($host) && $host !== '' && strpos($host, '.') !== false) {
                $subdomain = self::extractSubdomain($host);
                if ($subdomain !== '') {
                    $subdomainTenant = self::mapSubdomainToTenantId($subdomain, $host);
                    if ($subdomainTenant > 0) {
                        return $subdomainTenant;
                    }
                }
            }
        }

        // 5) Fallback
        $fallback = self::getFallbackTenantId();

        self::logFallbackUsage($fallback, [
            'header'    => $headerTenant > 0 ? $headerTenant : null,
            'param'     => $paramTenant > 0 ? $paramTenant : null,
            'subdomain' => $subdomain !== '' ? $subdomain : null,
            'host'      => $host,
        ]);

        return $fallback;
    }

    /** Linkeste Host-Label als Subdomain extrahieren (hookbar). */
    protected static function extractSubdomain(string $host): string
    {
        $sub = strtolower(explode('.', $host)[0] ?? '');
        if (function_exists('apply_filters')) {
            // z. B. Multi-Level-/wildcard-Logik anpassbar machen
            $sub = (string) apply_filters('bookando_tenant_subdomain_value', $sub, $host);
        }
        // nur sichere Zeichen zulassen
        return preg_replace('/[^a-z0-9-]/', '', $sub) ?: '';
    }

    /**
     * Subdomain → Tenant-ID (hookbar + option-basiert).
     * Reihenfolge:
     *  - Filter 'bookando_tenant_map_subdomain' (bekommt $sub, $host, $default)
     *  - Option 'bookando_subdomain_map' (assoziatives Array: sub => id)
     *  - Demo-Map (kann in Prod entfernt werden)
     */
    public static function mapSubdomainToTenantId(string $sub, ?string $host = null): int
    {
        $default = 0;

        // Option-basiertes Mapping (Admin-configurable)
        if (function_exists('get_option')) {
            $opt = get_option('bookando_subdomain_map');
            if (is_array($opt)) {
                $value = $opt[$sub] ?? $opt[(string) $sub] ?? null;
                $candidate = self::sanitizeTenantId($value);
                if ($candidate > 0) {
                    $default = $candidate;
                }
            }
        }

        if ($default <= 0) {
            $config = self::tenantConfig();
            if (isset($config['subdomain_map']) && is_array($config['subdomain_map'])) {
                $value = $config['subdomain_map'][$sub] ?? $config['subdomain_map'][(string) $sub] ?? null;
                $candidate = self::sanitizeTenantId($value);
                if ($candidate > 0) {
                    $default = $candidate;
                }
            }
        }

        if (function_exists('apply_filters')) {
            $mapped = (int) apply_filters('bookando_tenant_map_subdomain', $default, $sub, (string) $host);
            return $mapped > 0 ? $mapped : 0;
        }

        return $default > 0 ? $default : 0;
    }

    /** Eingaben in positive Tenant-IDs überführen. */
    protected static function sanitizeTenantId($value): int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (!is_scalar($value)) {
            return 0;
        }

        $string = (string) $value;
        if (function_exists('sanitize_text_field')) {
            $string = sanitize_text_field($string);
        } else {
            $string = trim($string);
        }

        if (function_exists('absint')) {
            $int = absint($string);
        } else {
            $int = (int) filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            $int = $int < 0 ? -$int : $int;
        }

        return $int > 0 ? $int : 0;
    }

    /** Fallback-Tenant-ID aus Option, Config oder Filter ermitteln. */
    protected static function getFallbackTenantId(): int
    {
        $fallback = 1;

        $config = self::tenantConfig();
        if (isset($config['default_tenant'])) {
            $candidate = self::sanitizeTenantId($config['default_tenant']);
            if ($candidate > 0) {
                $fallback = $candidate;
            }
        }

        if (function_exists('get_option')) {
            $optionValue = get_option('bookando_default_tenant_id', null);
            $candidate = self::sanitizeTenantId($optionValue);
            if ($candidate > 0) {
                $fallback = $candidate;
            }
        }

        if (function_exists('apply_filters')) {
            $fallback = (int) apply_filters('bookando_tenant_default_id', $fallback);
        }

        return $fallback > 0 ? $fallback : 1;
    }

    /** Tenant-Konfiguration aus Datei oder Filter laden. */
    protected static function tenantConfig(): array
    {
        if (self::$cachedTenantConfig !== null) {
            return self::$cachedTenantConfig;
        }

        $config = [];
        $path = self::tenantConfigPath();
        if ($path !== '' && file_exists($path)) {
            $data = include $path;
            if (is_array($data)) {
                $config = $data;
            }
        }

        if (function_exists('apply_filters')) {
            $config = (array) apply_filters('bookando_tenant_config', $config);
        }

        return self::$cachedTenantConfig = $config;
    }

    /** Pfad zur Tenant-Konfigurationsdatei bestimmen. */
    protected static function tenantConfigPath(): string
    {
        if (defined('BOOKANDO_TENANT_CONFIG')) {
            return (string) BOOKANDO_TENANT_CONFIG;
        }

        $base = defined('BOOKANDO_PLUGIN_DIR')
            ? BOOKANDO_PLUGIN_DIR
            : dirname(__DIR__, 3) . '/';

        return rtrim($base, '/\\') . '/config/tenants.php';
    }

    /** Fälle ohne Treffer protokollieren. */
    protected static function logFallbackUsage(int $tenantId, array $context): void
    {
        if ($tenantId <= 0) {
            $tenantId = 1;
        }

        $context = array_filter($context, static fn($value) => $value !== null && $value !== '');
        $context['tenant_id'] = $tenantId;

        if (class_exists('Bookando\\Logger') && method_exists('Bookando\\Logger', 'error')) {
            \Bookando\Logger::error('tenant.fallback', $context);
            return;
        }

        $message = '[Bookando] Tenant fallback used: ';
        if (function_exists('wp_json_encode')) {
            $message .= wp_json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $message .= json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        error_log($message);
    }

    /**
     * Tenant-ID für Datenbank-Abfragen (mit strikter Isolation).
     *
     * SICHERHEIT: Diese Methode gibt IMMER eine gültige tenant_id zurück,
     * auch im DEV-Modus. Dies verhindert versehentlichen Zugriff auf
     * Daten anderer Tenants durch Umgebungsvariablen.
     *
     * Für Entwickler/Admins:
     * - Nutzen Sie den X-BOOKANDO-TENANT Header für Tenant-Switching
     * - Erfordert 'manage_options' oder 'bookando_switch_tenant' Capability
     * - Wird automatisch geloggt (siehe resolveFromRequest)
     *
     * @return int Die aktuelle Tenant-ID (NIEMALS null)
     * @deprecated Verwenden Sie direkt currentTenantId() - beide liefern identische Werte
     */
    public static function currentTenantIdForQuery(): int
    {
        // SICHERHEIT: IMMER strikte Tenant-Isolation erzwingen
        return self::currentTenantId();
    }

    /**
     * Cross-Tenant-Zugriff via share ACL:
     * Zugriff erlaubt, wenn
     *  - der grantee-Tenant == owner-Tenant ist, ODER
     *  - ein ACL-Eintrag existiert (nicht abgelaufen).
     *
     * Erwartet Tabelle {prefix}bookando_share_acl (siehe Installer).
     */
    public static function canAccessShared(string $resourceType, int $resourceId, ?int $ownerTenantId = null): bool
    {
        global $wpdb;

        $grantee = self::currentTenantId();
        if ($ownerTenantId !== null && $grantee === (int) $ownerTenantId) {
            return true;
        }

        $tbl = $wpdb->prefix . 'bookando_share_acl';
        $sql = "SELECT 1 FROM {$tbl}
                WHERE resource_type = %s
                  AND resource_id   = %d
                  AND grantee_tenant = %d
                  AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1";

        $ok = (int) $wpdb->get_var($wpdb->prepare($sql, $resourceType, $resourceId, $grantee));
        return $ok === 1;
    }
}
