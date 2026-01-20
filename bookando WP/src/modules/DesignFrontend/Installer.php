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
            // HINWEIS: Wir verwenden die existierende bookando_users Tabelle!
            // Keine separate frontend_users Tabelle nötig.
            // =========================================================

            // =========================================================
            // OAuth Provider Verknüpfungen (Google, Apple Sign In)
            // =========================================================
            "CREATE TABLE {$prefix}oauth_links (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                provider VARCHAR(50) NOT NULL, -- google, apple
                provider_user_id VARCHAR(255) NOT NULL,
                provider_email VARCHAR(255),
                access_token TEXT,
                refresh_token TEXT,
                expires_at DATETIME,
                metadata JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_provider_user (provider, provider_user_id),
                INDEX idx_user_id (user_id),
                INDEX idx_provider (provider)
            ) $charset;",

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
            // Shortcode Templates & Presets
            // =========================================================
            "CREATE TABLE {$prefix}shortcode_templates (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                shortcode_type VARCHAR(50) NOT NULL, -- booking, catalog, list, calendar, etc.
                config JSON, -- Alle Parameter
                is_preset BOOLEAN DEFAULT 0, -- System-Presets vs. User-Templates
                created_by BIGINT UNSIGNED,
                usage_count INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_shortcode_type (shortcode_type),
                INDEX idx_is_preset (is_preset),
                INDEX idx_created_by (created_by)
            ) $charset;",

            // =========================================================
            // Auth Sessions (Session-Token Management)
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

            // =========================================================
            // A/B Testing für Shortcodes
            // =========================================================
            "CREATE TABLE {$prefix}ab_tests (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                shortcode_type VARCHAR(50) NOT NULL,
                variant_a_config JSON,
                variant_b_config JSON,
                split_percentage INT DEFAULT 50, -- 0-100
                status VARCHAR(50) DEFAULT 'active', -- active, paused, completed
                winner VARCHAR(10), -- a, b, null
                impressions_a INT DEFAULT 0,
                impressions_b INT DEFAULT 0,
                conversions_a INT DEFAULT 0,
                conversions_b INT DEFAULT 0,
                started_at DATETIME,
                ended_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_shortcode_type (shortcode_type)
            ) $charset;",

            // =========================================================
            // Shortcode Analytics (Usage & Performance)
            // =========================================================
            "CREATE TABLE {$prefix}shortcode_analytics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                shortcode_id VARCHAR(100), -- Optional: wenn mit Template verknüpft
                shortcode_type VARCHAR(50) NOT NULL,
                page_url VARCHAR(500),
                impressions INT DEFAULT 0,
                interactions INT DEFAULT 0, -- Clicks, hovers, etc.
                conversions INT DEFAULT 0, -- Completed bookings
                avg_load_time_ms INT DEFAULT 0,
                date DATE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_shortcode_date (shortcode_id, shortcode_type, page_url(191), date),
                INDEX idx_shortcode_type (shortcode_type),
                INDEX idx_date (date)
            ) $charset;",

            // =========================================================
            // SaaS Link Generator (UTM-Parameters & Tracking)
            // =========================================================
            "CREATE TABLE {$prefix}generated_links (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                link_hash VARCHAR(64) NOT NULL UNIQUE,
                target_type VARCHAR(50) NOT NULL, -- catalog, booking, calendar, etc.
                target_config JSON, -- Filter/Parameter
                utm_source VARCHAR(255),
                utm_medium VARCHAR(255),
                utm_campaign VARCHAR(255),
                utm_term VARCHAR(255),
                utm_content VARCHAR(255),
                expires_at DATETIME,
                created_by BIGINT UNSIGNED,
                click_count INT DEFAULT 0,
                conversion_count INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_link_hash (link_hash),
                INDEX idx_target_type (target_type),
                INDEX idx_created_by (created_by),
                INDEX idx_expires_at (expires_at)
            ) $charset;",
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        // Setze Versions-Flag
        update_option('bookando_frontend_db_version', '2.0.0');

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
            "{$prefix}generated_links",
            "{$prefix}shortcode_analytics",
            "{$prefix}ab_tests",
            "{$prefix}offer_displays",
            "{$prefix}auth_providers",
            "{$prefix}auth_sessions",
            "{$prefix}shortcode_templates",
            "{$prefix}pages",
            "{$prefix}oauth_links",
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('bookando_frontend_db_version');
    }
}
