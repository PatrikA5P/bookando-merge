<?php
namespace Bookando\Core\Tenant;

/**
 * TenantInstaller - Erstellt Datenbank-Tabellen für Multi-Tenant-System.
 *
 * Tabelle: wp_bookando_tenants
 * - Speichert Tenant-Konfiguration, Lizenzen, Subdomain-Mappings
 * - Wird bei Plugin-Aktivierung automatisch erstellt
 */
class TenantInstaller
{
    /**
     * Erstellt die wp_bookando_tenants Tabelle.
     */
    public static function install(): void
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'bookando_tenants';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            license_key VARCHAR(255) NOT NULL,
            platform ENUM('saas', 'cloud', 'app') NOT NULL DEFAULT 'saas',
            plan ENUM('basic', 'pro', 'enterprise', 'lifetime') NOT NULL DEFAULT 'basic',
            external_id VARCHAR(255) DEFAULT NULL COMMENT 'Externe ID (z.B. Stripe Customer ID)',
            subdomain VARCHAR(100) NOT NULL COMMENT 'Subdomain für SaaS-Zugriff',
            api_key_hash VARCHAR(255) NOT NULL COMMENT 'BCrypt-Hash des API-Keys',
            status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL COMMENT 'Lizenz-Ablaufdatum (NULL = lifetime)',
            metadata TEXT DEFAULT NULL COMMENT 'JSON-Metadaten (z.B. plattformübergreifende Sync-Info)',
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            UNIQUE KEY subdomain (subdomain),
            KEY status (status),
            KEY platform (platform),
            KEY expires_at (expires_at)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Versionierung für zukünftige Migrationen
        update_option('bookando_tenant_db_version', '1.0.0');
    }

    /**
     * Seed: Erstellt Standard-Tenant (ID 1) für lokale Installation.
     */
    public static function seedDefaultTenant(): void
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'bookando_tenants';

        // Prüfe, ob bereits Tenants existieren
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tableName}");

        if ($count > 0) {
            return; // Bereits Tenants vorhanden
        }

        // Default-Tenant anlegen
        $wpdb->insert($tableName, [
            'id'           => 1,
            'company_name' => get_bloginfo('name') ?: 'Default Tenant',
            'email'        => get_option('admin_email') ?: 'admin@localhost.local',
            'license_key'  => 'LOCAL-DEV-LICENSE-' . wp_generate_password(12, false),
            'platform'     => 'saas',
            'plan'         => 'basic',
            'subdomain'    => 'default',
            'api_key_hash' => password_hash('local-dev-key', PASSWORD_BCRYPT),
            'status'       => 'active',
            'created_at'   => current_time('mysql'),
            'expires_at'   => null, // Kein Ablauf für lokale Installation
            'metadata'     => wp_json_encode(['local_install' => true]),
        ]);
    }
}
