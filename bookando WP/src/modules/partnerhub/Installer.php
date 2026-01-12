<?php
/**
 * Installer für Modul "partnerhub"
 */
declare(strict_types=1);

namespace Bookando\Modules\partnerhub;

class Installer
{
    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        // Partners table
        $table = $wpdb->prefix . 'bookando_partners';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            partner_type ENUM('incoming','outgoing','bidirectional') NOT NULL DEFAULT 'bidirectional',
            name VARCHAR(255) NOT NULL,
            company_name VARCHAR(255) DEFAULT NULL,
            website_url VARCHAR(500) DEFAULT NULL,
            api_endpoint VARCHAR(500) DEFAULT NULL,
            contact_email VARCHAR(255) DEFAULT NULL,
            contact_phone VARCHAR(100) DEFAULT NULL,
            api_key VARCHAR(100) DEFAULT NULL,
            status ENUM('active','inactive','pending','suspended') NOT NULL DEFAULT 'pending',
            settings LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            KEY tenant_id (tenant_id),
            KEY status (status),
            KEY api_key (api_key),
            KEY deleted_at (deleted_at)
        ) {$charset};";
        dbDelta($sql);

        // Partner Mappings table
        $table = $wpdb->prefix . 'bookando_partner_mappings';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            partner_id BIGINT UNSIGNED NOT NULL,
            local_type ENUM('service','event','package','voucher') NOT NULL,
            local_id BIGINT UNSIGNED NOT NULL,
            remote_type VARCHAR(50) DEFAULT NULL,
            remote_id VARCHAR(255) DEFAULT NULL,
            sync_status ENUM('active','paused','error','pending') NOT NULL DEFAULT 'pending',
            last_synced_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            KEY tenant_id (tenant_id),
            KEY partner_id (partner_id),
            KEY local_lookup (local_type, local_id),
            KEY sync_status (sync_status),
            KEY deleted_at (deleted_at)
        ) {$charset};";
        dbDelta($sql);

        // Partner Rules table
        $table = $wpdb->prefix . 'bookando_partner_rules';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            partner_id BIGINT UNSIGNED NOT NULL,
            mapping_id BIGINT UNSIGNED DEFAULT NULL,
            rule_type ENUM('pricing','availability','capacity','blackout') NOT NULL,
            priority INT NOT NULL DEFAULT 0,
            pricing_strategy ENUM('fixed','percentage_markup','percentage_discount','dynamic') DEFAULT NULL,
            pricing_value DECIMAL(10,2) DEFAULT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            KEY tenant_id (tenant_id),
            KEY partner_id (partner_id),
            KEY mapping_id (mapping_id),
            KEY rule_type (rule_type),
            KEY status (status),
            KEY deleted_at (deleted_at)
        ) {$charset};";
        dbDelta($sql);

        // Partner Consents table (DSGVO-konform)
        $table = $wpdb->prefix . 'bookando_partner_consents';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            partner_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            purpose ENUM('booking','student_card','course_enrollment','event_participation','other') NOT NULL,
            data_categories LONGTEXT NOT NULL,
            consent_given TINYINT(1) NOT NULL DEFAULT 0,
            consent_method ENUM('explicit','implied','contract') NOT NULL DEFAULT 'explicit',
            consent_timestamp DATETIME DEFAULT NULL,
            consent_ip_address VARCHAR(45) DEFAULT NULL,
            valid_from DATETIME DEFAULT NULL,
            valid_until DATETIME DEFAULT NULL,
            revoked TINYINT(1) NOT NULL DEFAULT 0,
            revoked_at DATETIME DEFAULT NULL,
            status ENUM('active','expired','revoked','pending') NOT NULL DEFAULT 'pending',
            legal_basis ENUM('consent','contract','legal_obligation','vital_interest','public_task','legitimate_interest') NOT NULL DEFAULT 'consent',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            KEY tenant_id (tenant_id),
            KEY partner_id (partner_id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY valid_until (valid_until),
            KEY deleted_at (deleted_at)
        ) {$charset};";
        dbDelta($sql);
    }
}
