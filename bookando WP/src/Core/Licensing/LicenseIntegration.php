<?php
namespace Bookando\Core\Licensing;

use Bookando\Core\Tenant\TenantManager;

/**
 * LicenseIntegration - Brücke zwischen altem & neuem Lizenz-System
 *
 * ZWEI SYSTEME:
 * 1. ALT: LicenseManager (Module-basiert, wp_options)
 * 2. NEU: LicenseGuard (Tenant-basiert, wp_bookando_tenants)
 *
 * VERWENDUNG:
 * - Für NEUE Multi-Tenant SaaS: Nutzen Sie LicenseGuard
 * - Für ALTE Single-Tenant Instanzen: LicenseManager bleibt kompatibel
 * - Diese Klasse verbindet beide für Migrations-Phase
 *
 * MIGRATION:
 * - Phase 1: Beide Systeme parallel (aktuelle Phase)
 * - Phase 2: Migriere alte Lizenzen nach wp_bookando_tenants
 * - Phase 3: LicenseManager nur noch als Wrapper für LicenseGuard
 */
class LicenseIntegration
{
    /**
     * Prüft Lizenz (neues System ODER altes System).
     *
     * @return bool
     */
    public static function hasValidLicense(): bool
    {
        // Prüfe zuerst neues Tenant-basiertes System
        if (self::isTenantBasedLicensingActive()) {
            return LicenseGuard::hasValidLicense();
        }

        // Fallback: Altes Module-basiertes System
        return LicenseManager::hasValidLicense();
    }

    /**
     * Prüft Feature (neues ODER altes System).
     *
     * @param string $feature
     * @return bool
     */
    public static function hasFeature(string $feature): bool
    {
        // Prüfe zuerst neues Tenant-basiertes System
        if (self::isTenantBasedLicensingActive()) {
            return LicenseGuard::hasFeature($feature);
        }

        // Fallback: Altes Module-basiertes System
        return LicenseManager::isFeatureEnabled($feature);
    }

    /**
     * Gibt aktuellen Plan zurück (neues ODER altes System).
     *
     * @return string
     */
    public static function getCurrentPlan(): string
    {
        // Prüfe zuerst neues Tenant-basiertes System
        if (self::isTenantBasedLicensingActive()) {
            return LicenseGuard::getCurrentPlan();
        }

        // Fallback: Altes Module-basiertes System
        return LicenseManager::getLicensePlan() ?? 'basic';
    }

    /**
     * Prüft, ob Tenant-basiertes Lizenz-System aktiv ist.
     *
     * @return bool
     */
    private static function isTenantBasedLicensingActive(): bool
    {
        // Wenn wp_bookando_tenants Tabelle existiert UND Tenant hat Lizenz
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_tenants';

        // Prüfe, ob Tabelle existiert
        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;

        if (!$tableExists) {
            return false;
        }

        // Prüfe, ob aktueller Tenant Lizenz hat
        $tenantId = TenantManager::currentTenantId();
        $license = LicenseGuard::getLicense($tenantId);

        return $license !== null;
    }

    /**
     * Migriert alte Lizenz (wp_options) zu neuem Tenant-System.
     *
     * VERWENDUNG:
     * - Einmalig nach Plugin-Update aufrufen
     * - Migriert 'bookando_license_data' zu wp_bookando_tenants
     *
     * @return array{success: bool, message: string}
     */
    public static function migrateOldLicenseToTenant(): array
    {
        // Prüfe, ob alte Lizenz existiert
        $oldLicense = get_option('bookando_license_data');

        if (!$oldLicense || !is_array($oldLicense)) {
            return [
                'success' => false,
                'message' => 'Keine alte Lizenz zum Migrieren gefunden',
            ];
        }

        // Prüfe, ob bereits migriert
        $tenantId = TenantManager::currentTenantId();
        $existingLicense = LicenseGuard::getLicense($tenantId);

        if ($existingLicense) {
            return [
                'success' => false,
                'message' => 'Tenant hat bereits eine Lizenz im neuen System',
            ];
        }

        // Migriere zu wp_bookando_tenants
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_tenants';

        $licenseKey = $oldLicense['key'] ?? 'MIGRATED-' . wp_generate_password(12, false);
        $plan = $oldLicense['plan'] ?? 'basic';

        $inserted = $wpdb->insert($table, [
            'id'           => $tenantId,
            'company_name' => get_bloginfo('name') ?: 'Migrated Tenant',
            'email'        => get_option('admin_email') ?: 'admin@localhost.local',
            'license_key'  => $licenseKey,
            'platform'     => 'saas',
            'plan'         => $plan,
            'subdomain'    => 'migrated-' . $tenantId,
            'api_key_hash' => password_hash('migrated-key', PASSWORD_BCRYPT),
            'status'       => 'active',
            'created_at'   => current_time('mysql'),
            'expires_at'   => null, // Lifetime für migrierte Lizenzen
            'metadata'     => wp_json_encode([
                'migrated_from' => 'wp_options',
                'old_modules'   => $oldLicense['modules'] ?? [],
                'old_features'  => $oldLicense['features'] ?? [],
            ]),
        ]);

        if (!$inserted) {
            return [
                'success' => false,
                'message' => 'Fehler beim Migrieren: ' . $wpdb->last_error,
            ];
        }

        // Markiere alte Lizenz als migriert (nicht löschen für Backup)
        update_option('bookando_license_data_migrated', $oldLicense);
        update_option('bookando_license_migration_date', current_time('mysql'));

        return [
            'success' => true,
            'message' => 'Lizenz erfolgreich migriert zu Tenant-ID ' . $tenantId,
        ];
    }

    /**
     * DEV-Bypass: Kombiniert beide Systeme.
     *
     * @return bool
     */
    public static function isDevBypassActive(): bool
    {
        // Neues System: BOOKANDO_DEV_BYPASS in wp-config.php
        if (defined('BOOKANDO_DEV_BYPASS') && BOOKANDO_DEV_BYPASS === true) {
            $environment = defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production';
            return $environment !== 'production';
        }

        // Altes System: BOOKANDO_DEV
        if (defined('BOOKANDO_DEV') && BOOKANDO_DEV === true) {
            return true;
        }

        // Capability-basiert
        return function_exists('current_user_can') && current_user_can('bookando_dev_bypass');
    }
}
