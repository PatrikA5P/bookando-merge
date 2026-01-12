<?php
namespace Bookando\Core\Licensing;

use Bookando\Core\Service\ActivityLogger;
use Bookando\Core\Tenant\TenantManager;

/**
 * LicenseGuard - Zentrale Lizenz-Verwaltung & -Prüfung
 *
 * VERANTWORTLICH FÜR:
 * - Zentrale Lizenz-Status-Prüfung für alle Zugriffe
 * - Feature-Flags basierend auf Lizenz-Plan
 * - Grace Period bei Lizenz-Ablauf
 * - Performance-Caching (Request-basiert)
 * - DEV-Bypass für Entwicklung (sicher!)
 *
 * BEST PRACTICE:
 * - Lizenz-Prüfung VOR jedem REST-API Zugriff
 * - Caching für Performance (nur während Request-Laufzeit)
 * - Grace Period: 7 Tage nach Ablauf noch Zugriff
 * - DEV-Bypass: NUR mit expliziter Capability
 */
class LicenseGuard
{
    /** @var array<int, array> Request-basierter Cache */
    private static array $licenseCache = [];

    /** @var int Grace Period in Sekunden (7 Tage) */
    private const GRACE_PERIOD_SECONDS = 7 * 24 * 60 * 60;

    /**
     * Prüft, ob der aktuelle Tenant eine gültige Lizenz hat.
     *
     * @param int|null $tenantId Optional: Spezifische Tenant-ID (sonst aktuelle)
     * @return bool True wenn Lizenz gültig, False sonst
     */
    public static function hasValidLicense(?int $tenantId = null): bool
    {
        // DEV-Bypass: Entwickler mit expliziter Capability
        if (self::isDevBypassAllowed()) {
            return true;
        }

        $tenantId = $tenantId ?? TenantManager::currentTenantId();

        // Cache-Check (nur während Request-Laufzeit)
        if (isset(self::$licenseCache[$tenantId])) {
            return self::$licenseCache[$tenantId]['is_valid'];
        }

        $license = self::getLicense($tenantId);

        if (!$license) {
            self::logLicenseCheck($tenantId, 'no_license', false);
            return self::$licenseCache[$tenantId] = ['is_valid' => false];
        }

        $isValid = self::validateLicense($license);

        self::logLicenseCheck($tenantId, $license['status'], $isValid);

        self::$licenseCache[$tenantId] = [
            'is_valid' => $isValid,
            'license'  => $license,
        ];

        return $isValid;
    }

    /**
     * Holt Lizenz-Daten für einen Tenant.
     *
     * @param int $tenantId
     * @return array<string, mixed>|null
     */
    public static function getLicense(int $tenantId): ?array
    {
        // Cache-Check
        if (isset(self::$licenseCache[$tenantId]['license'])) {
            return self::$licenseCache[$tenantId]['license'];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bookando_tenants';

        $sql = "SELECT * FROM {$table} WHERE id = %d LIMIT 1";
        $license = $wpdb->get_row($wpdb->prepare($sql, $tenantId), ARRAY_A);

        return $license ?: null;
    }

    /**
     * Validiert Lizenz-Status und Ablaufdatum.
     *
     * @param array<string, mixed> $license
     * @return bool
     */
    private static function validateLicense(array $license): bool
    {
        // Status muss 'active' sein
        if ($license['status'] !== 'active') {
            return false;
        }

        // Lifetime-Lizenzen haben kein Ablaufdatum
        if (empty($license['expires_at']) || $license['expires_at'] === null) {
            return true;
        }

        // Prüfe Ablaufdatum + Grace Period
        $expiresAt = strtotime($license['expires_at']);
        $now = time();
        $gracePeriodEnd = $expiresAt + self::GRACE_PERIOD_SECONDS;

        // Innerhalb Grace Period? → Warnung, aber noch gültig
        if ($now > $expiresAt && $now <= $gracePeriodEnd) {
            ActivityLogger::warning('license.grace_period', 'Lizenz in Grace Period', [
                'tenant_id'  => $license['id'],
                'expires_at' => $license['expires_at'],
                'grace_end'  => date('Y-m-d H:i:s', $gracePeriodEnd),
            ]);
            return true;
        }

        // Abgelaufen (auch nach Grace Period)?
        if ($now > $gracePeriodEnd) {
            return false;
        }

        return true;
    }

    /**
     * Prüft, ob ein bestimmtes Feature für den Tenant verfügbar ist.
     *
     * @param string $feature Feature-Name (z.B. 'api_access', 'advanced_reports', 'multi_user')
     * @param int|null $tenantId Optional: Spezifische Tenant-ID
     * @return bool
     */
    public static function hasFeature(string $feature, ?int $tenantId = null): bool
    {
        // DEV-Bypass
        if (self::isDevBypassAllowed()) {
            return true;
        }

        $tenantId = $tenantId ?? TenantManager::currentTenantId();

        if (!self::hasValidLicense($tenantId)) {
            return false;
        }

        $license = self::getLicense($tenantId);
        if (!$license) {
            return false;
        }

        $plan = $license['plan'] ?? 'basic';

        return self::isFeatureInPlan($feature, $plan);
    }

    /**
     * Prüft, ob Feature im Plan enthalten ist.
     *
     * @param string $feature
     * @param string $plan
     * @return bool
     */
    private static function isFeatureInPlan(string $feature, string $plan): bool
    {
        $features = self::getPlanFeatures($plan);

        return in_array($feature, $features, true);
    }

    /**
     * Gibt alle Features eines Plans zurück.
     *
     * @param string $plan
     * @return array<string>
     */
    public static function getPlanFeatures(string $plan): array
    {
        $planFeatures = [
            'basic' => [
                'api_access',
                'basic_reports',
                'single_user',
                'email_support',
            ],
            'pro' => [
                'api_access',
                'basic_reports',
                'advanced_reports',
                'multi_user',
                'priority_support',
                'webhooks',
                'custom_branding',
            ],
            'enterprise' => [
                'api_access',
                'basic_reports',
                'advanced_reports',
                'multi_user',
                'priority_support',
                'webhooks',
                'custom_branding',
                'sso',
                'advanced_security',
                'dedicated_support',
                'sla_guarantee',
                'white_label',
            ],
            'lifetime' => [
                'api_access',
                'basic_reports',
                'advanced_reports',
                'multi_user',
                'priority_support',
                'webhooks',
                'custom_branding',
            ],
        ];

        // Filter-Hook für Custom-Features
        if (function_exists('apply_filters')) {
            $planFeatures = apply_filters('bookando_plan_features', $planFeatures, $plan);
        }

        return $planFeatures[$plan] ?? $planFeatures['basic'];
    }

    /**
     * Gibt den aktuellen Plan des Tenants zurück.
     *
     * @param int|null $tenantId
     * @return string
     */
    public static function getCurrentPlan(?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? TenantManager::currentTenantId();
        $license = self::getLicense($tenantId);

        return $license['plan'] ?? 'basic';
    }

    /**
     * Gibt verbleibende Tage bis Lizenz-Ablauf zurück.
     *
     * @param int|null $tenantId
     * @return int|null NULL = Lifetime, -1 = Abgelaufen, >0 = Verbleibende Tage
     */
    public static function getDaysUntilExpiry(?int $tenantId = null): ?int
    {
        $tenantId = $tenantId ?? TenantManager::currentTenantId();
        $license = self::getLicense($tenantId);

        if (!$license) {
            return -1;
        }

        // Lifetime-Lizenz
        if (empty($license['expires_at']) || $license['expires_at'] === null) {
            return null;
        }

        $expiresAt = strtotime($license['expires_at']);
        $now = time();
        $diff = $expiresAt - $now;

        if ($diff < 0) {
            return -1; // Abgelaufen
        }

        return (int) ceil($diff / (60 * 60 * 24));
    }

    /**
     * Prüft, ob Tenant in Grace Period ist.
     *
     * @param int|null $tenantId
     * @return bool
     */
    public static function isInGracePeriod(?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? TenantManager::currentTenantId();
        $license = self::getLicense($tenantId);

        if (!$license || empty($license['expires_at'])) {
            return false;
        }

        $expiresAt = strtotime($license['expires_at']);
        $now = time();
        $gracePeriodEnd = $expiresAt + self::GRACE_PERIOD_SECONDS;

        return $now > $expiresAt && $now <= $gracePeriodEnd;
    }

    /**
     * Gibt Lizenz-Informationen für Dashboard zurück.
     *
     * @param int|null $tenantId
     * @return array{is_valid: bool, plan: string, expires_at: string|null, days_remaining: int|null, is_grace_period: bool, features: array}
     */
    public static function getLicenseInfo(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? TenantManager::currentTenantId();
        $license = self::getLicense($tenantId);

        if (!$license) {
            return [
                'is_valid'        => false,
                'plan'            => 'none',
                'expires_at'      => null,
                'days_remaining'  => -1,
                'is_grace_period' => false,
                'features'        => [],
            ];
        }

        return [
            'is_valid'        => self::hasValidLicense($tenantId),
            'plan'            => $license['plan'],
            'expires_at'      => $license['expires_at'],
            'days_remaining'  => self::getDaysUntilExpiry($tenantId),
            'is_grace_period' => self::isInGracePeriod($tenantId),
            'features'        => self::getPlanFeatures($license['plan']),
        ];
    }

    /**
     * DEV-Bypass: Nur für Entwickler mit expliziter Capability.
     *
     * SICHERHEIT:
     * - Erfordert eingeloggt als Admin UND Capability 'bookando_dev_bypass'
     * - ODER BOOKANDO_DEV_BYPASS=true in wp-config.php (nur für lokale Dev!)
     * - Wird auditiert
     *
     * @return bool
     */
    private static function isDevBypassAllowed(): bool
    {
        // Option 1: Lokale Dev-Umgebung (wp-config.php)
        if (defined('BOOKANDO_DEV_BYPASS') && BOOKANDO_DEV_BYPASS === true) {
            // Nur in non-production Umgebungen erlaubt
            $environment = defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production';

            if ($environment === 'production') {
                ActivityLogger::critical('license.dev_bypass_blocked', 'DEV-Bypass in Production blockiert!', [
                    'user_id' => get_current_user_id(),
                    'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
                return false;
            }

            return true;
        }

        // Option 2: Explizite Capability (für Support/Admin)
        if (function_exists('current_user_can') && current_user_can('bookando_dev_bypass')) {
            ActivityLogger::warning('license.dev_bypass_used', 'Lizenz-Bypass via Capability', [
                'user_id' => get_current_user_id(),
                'tenant_id' => TenantManager::currentTenantId(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Loggt Lizenz-Prüfungen für Audit.
     *
     * @param int $tenantId
     * @param string $status
     * @param bool $isValid
     * @return void
     */
    private static function logLicenseCheck(int $tenantId, string $status, bool $isValid): void
    {
        // Nur bei fehlgeschlagenen Checks loggen (Performance)
        if (!$isValid) {
            ActivityLogger::warning('license.check_failed', 'Lizenz-Prüfung fehlgeschlagen', [
                'tenant_id' => $tenantId,
                'status'    => $status,
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'endpoint'  => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Cache zurücksetzen (für Tests/CLI).
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$licenseCache = [];
    }
}
