<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

class Installer
{
    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $prefix = $wpdb->prefix . 'bookando_frontend_';
        $charset = $wpdb->get_charset_collate();

        $tables = [
            // =========================================================
            // Frontend Pages (Custom Landing Pages)
            // =========================================================
            "CREATE TABLE {$prefix}pages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(255) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                type VARCHAR(50) DEFAULT 'offer', -- offer, portal_customer, portal_employee
                template VARCHAR(50) DEFAULT 'default',
                header_config JSON,
                content_config JSON,
                footer_config JSON,
                seo_config JSON,
                status VARCHAR(50) DEFAULT 'published',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_type (type),
                INDEX idx_status (status)
            ) $charset;",

            // =========================================================
            // Shortcode Configurations
            // =========================================================
            "CREATE TABLE {$prefix}shortcodes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                shortcode_id VARCHAR(100) NOT NULL UNIQUE,
                type VARCHAR(50) NOT NULL, -- offers, customer_portal, employee_portal, booking
                config JSON,
                filters JSON, -- category, tags, etc.
                display_options JSON, -- grid/list, columns, etc.
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_shortcode_id (shortcode_id),
                INDEX idx_type (type)
            ) $charset;",

            // =========================================================
            // Auth Sessions (für Email/Google/Apple Login)
            // =========================================================
            "CREATE TABLE {$prefix}auth_sessions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                session_token VARCHAR(255) NOT NULL UNIQUE,
                user_id BIGINT UNSIGNED NOT NULL,
                auth_provider VARCHAR(50) DEFAULT 'email', -- email, google, apple
                ip_address VARCHAR(45),
                user_agent TEXT,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_session_token (session_token),
                INDEX idx_user_id (user_id),
                INDEX idx_expires_at (expires_at)
            ) $charset;",

            // =========================================================
            // Auth Providers (OAuth Konfigurationen)
            // =========================================================
            "CREATE TABLE {$prefix}auth_providers (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(50) NOT NULL UNIQUE, -- google, apple
                enabled BOOLEAN DEFAULT 0,
                client_id VARCHAR(255),
                client_secret VARCHAR(255),
                redirect_uri VARCHAR(255),
                config JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_provider (provider),
                INDEX idx_enabled (enabled)
            ) $charset;",

            // =========================================================
            // Email Verification Tokens
            // =========================================================
            "CREATE TABLE {$prefix}email_verifications (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                user_id BIGINT UNSIGNED DEFAULT NULL,
                verified BOOLEAN DEFAULT 0,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_token (token),
                INDEX idx_expires_at (expires_at)
            ) $charset;",

            // =========================================================
            // Offer Display Settings (für öffentliche Angebote)
            // =========================================================
            "CREATE TABLE {$prefix}offer_displays (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                offer_type VARCHAR(50) NOT NULL, -- course, appointment, package
                offer_id BIGINT UNSIGNED NOT NULL,
                visible BOOLEAN DEFAULT 1,
                featured BOOLEAN DEFAULT 0,
                display_order INT DEFAULT 0,
                custom_title VARCHAR(255),
                custom_description TEXT,
                custom_image VARCHAR(255),
                tags JSON,
                categories JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_offer_type (offer_type),
                INDEX idx_offer_id (offer_id),
                INDEX idx_visible (visible),
                INDEX idx_featured (featured),
                INDEX idx_display_order (display_order)
            ) $charset;",
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        // Setze Versions-Flag
        update_option('bookando_frontend_db_version', '1.0.0');

        // Create default auth providers
        self::createDefaultAuthProviders();
    }

    /**
     * Create default auth provider entries
     */
    protected static function createDefaultAuthProviders(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_auth_providers';

        $providers = [
            ['provider' => 'google', 'enabled' => 0],
            ['provider' => 'apple', 'enabled' => 0],
        ];

        foreach ($providers as $provider) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE provider = %s",
                $provider['provider']
            ));

            if (!$exists) {
                $wpdb->insert($table, $provider);
            }
        }
    }

    /**
     * Deinstalliert alle DesignFrontend-Tabellen
     */
    public static function uninstall(): void
    {
        global $wpdb;

        $prefix = $wpdb->prefix . 'bookando_frontend_';
        $tables = [
            "{$prefix}offer_displays",
            "{$prefix}email_verifications",
            "{$prefix}auth_providers",
            "{$prefix}auth_sessions",
            "{$prefix}shortcodes",
            "{$prefix}pages",
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('bookando_frontend_db_version');
    }
}
