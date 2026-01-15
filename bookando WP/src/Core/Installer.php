<?php

declare(strict_types=1);

namespace Bookando\Core;

use Bookando\Core\Database\Migrator;
use Bookando\Core\Manager\ModuleManager;
use Bookando\Core\Manager\ModuleStateRepository;
use Bookando\Core\Service\ActivityLogger;

final class Installer
{
    /**
     * Wird bei Aktivierung und bei Core-Updates aufgerufen.
     */
    public static function run(): void
    {
        ActivityLogger::log(
            'installer.core',
            'Installer started',
            [],
            ActivityLogger::LEVEL_INFO,
            null,
            'core'
        );

        self::installCoreTables();
        self::migrateLegacyModuleTables();
        self::runDatabaseMigrations();
        self::installModules();

        ActivityLogger::log(
            'installer.core',
            'Installer finished',
            [],
            ActivityLogger::LEVEL_INFO,
            null,
            'core'
        );
    }

    /**
     * Installiert alle Core-Tabellen (neue Datenbankstruktur inkl. Settings).
     *
     * Design-Prinzipien:
     * - Verfügbarkeitsregeln (Workday/Specialday) speichern wir mit DATE+TIME (lokale TZ),
     *   konkrete Termine/Events mit *_at_utc (UTC) + optional client_tz.
     * - Specialdays „überschreiben“ Workdays am betreffenden Tag.
     * - Sets liefern Defaults (Services/Locations), Intervals erlauben optionale Overrides.
     * - Webhook-Outbox, UI-Monatscache sind optional und rein technisch (ohne Logik).
     */
    protected static function installCoreTables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $p   = $wpdb->prefix . 'bookando_';
        $col = $wpdb->get_charset_collate();

        $sql = [

            // =========================================================
            //  Mandanten & Benutzer
            // =========================================================

            "CREATE TABLE {$p}tenants (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                status VARCHAR(50) DEFAULT 'active',
                time_zone VARCHAR(100) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $col;",

            "CREATE TABLE {$p}roles (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(100),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_roles_slug (slug)
            ) $col;",

            "CREATE TABLE {$p}users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                roles JSON DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                first_name VARCHAR(100),
                last_name  VARCHAR(100),
                email VARCHAR(190),
                phone VARCHAR(50),
                address VARCHAR(100),
                address_2 VARCHAR(100),
                zip VARCHAR(100),
                city VARCHAR(100),
                country VARCHAR(100),
                birthdate DATE,
                gender ENUM('m','f','d','n'),
                language VARCHAR(30) DEFAULT 'de',
                note TEXT,
                description TEXT,
                avatar_url TEXT,
                timezone VARCHAR(100),
                external_id VARCHAR(255),
                badge_id VARCHAR(100),
                password_hash VARCHAR(255),
                password_reset_token VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME DEFAULT NULL,
                UNIQUE KEY uq_tenant_email (tenant_id,email),
                KEY idx_users_tenant (tenant_id)
            ) $col;",

            "CREATE TABLE {$p}user_roles (
                user_id BIGINT UNSIGNED,
                role_id BIGINT UNSIGNED,
                PRIMARY KEY (user_id, role_id)
            ) $col;",

            // =========================================================
            //  Events & Perioden (UTC, pro Periode mappbar)
            // =========================================================

            "CREATE TABLE {$p}events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                type ENUM('course','lesson','exam','meeting','generic'),
                name VARCHAR(255),
                description TEXT,
                organizer_id BIGINT UNSIGNED,
                status ENUM('draft','open','closed','cancelled') DEFAULT 'open',
                booking_start_utc DATETIME NULL,
                booking_end_utc   DATETIME NULL,
                price DECIMAL(10,2) NULL,
                max_capacity INT NULL,
                settings JSON NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_events_tenant (tenant_id),
                KEY idx_events_status (status)
            ) $col;",

            // Konkrete Event-Termine (UTC) – pro Periode können Services/Locations/Employees/Resources zugeordnet werden
            "CREATE TABLE {$p}event_periods (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                event_id BIGINT UNSIGNED NOT NULL,
                period_start_utc DATETIME NOT NULL,
                period_end_utc   DATETIME NOT NULL,
                time_zone VARCHAR(100) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_evtp_event (event_id),
                KEY idx_evtp_range (period_start_utc, period_end_utc)
            ) $col;",

            // Zuordnungen pro Event-Periode (fein granular, gut für Webhooks)
            "CREATE TABLE {$p}event_period_employees (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                period_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                role VARCHAR(64) NULL,
                UNIQUE KEY uq_evtp_emp (period_id, user_id),
                KEY idx_evtp_emp_user (user_id)
            ) $col;",

            "CREATE TABLE {$p}event_period_services (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                period_id BIGINT UNSIGNED NOT NULL,
                service_id BIGINT UNSIGNED NOT NULL,
                UNIQUE KEY uq_evtp_srv (period_id, service_id),
                KEY idx_evtp_srv_service (service_id)
            ) $col;",

            "CREATE TABLE {$p}event_period_locations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                period_id BIGINT UNSIGNED NOT NULL,
                location_id BIGINT UNSIGNED NOT NULL,
                UNIQUE KEY uq_evtp_loc (period_id, location_id),
                KEY idx_evtp_loc_location (location_id)
            ) $col;",

            // Optional: deklarative Ressourcenplanung pro Event-Periode (keine harte Reservierung notwendig)
            "CREATE TABLE {$p}resources (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(64) NULL,
                quantity INT DEFAULT 1,
                status VARCHAR(32) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_res_tenant (tenant_id),
                KEY idx_res_type (type)
            ) $col;",

            "CREATE TABLE {$p}event_period_resources (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                period_id BIGINT UNSIGNED NOT NULL,
                resource_id BIGINT UNSIGNED NOT NULL,
                required_quantity INT NOT NULL DEFAULT 1,
                UNIQUE KEY uq_evtp_res (period_id, resource_id),
                KEY idx_evtp_res_resource (resource_id)
            ) $col;",

            // =========================================================
            //  Appointments (konkrete Buchungen, UTC)
            // =========================================================

            "CREATE TABLE {$p}appointments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                customer_id BIGINT UNSIGNED NULL,
                employee_id BIGINT UNSIGNED NULL,
                service_id  BIGINT UNSIGNED NULL,
                location_id BIGINT UNSIGNED NULL,
                event_id    BIGINT UNSIGNED NULL,
                status ENUM('pending','approved','confirmed','cancelled','noshow') DEFAULT 'pending',
                starts_at_utc DATETIME NOT NULL,
                ends_at_utc   DATETIME NOT NULL,
                client_tz VARCHAR(100) NULL,
                price DECIMAL(10,2) NULL,
                persons INT DEFAULT 1,
                meta JSON NULL,
                referred_by_tenant BIGINT UNSIGNED DEFAULT NULL,
                referral_source VARCHAR(100) DEFAULT NULL,
                commission_status VARCHAR(50) DEFAULT NULL,
                commission_amount DECIMAL(10,2) DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_apt_tenant (tenant_id),
                KEY idx_apt_customer (customer_id),
                KEY idx_apt_employee (employee_id),
                KEY idx_apt_time (starts_at_utc, ends_at_utc),
                KEY idx_apt_status (status),
                KEY idx_apt_referral (referred_by_tenant, commission_status)
            ) $col;",

            // =========================================================
            //  Orte & Zahlungen & Custom Fields & Benachrichtigungen
            // =========================================================

            "CREATE TABLE {$p}locations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                name VARCHAR(255),
                description TEXT,
                address VARCHAR(255),
                phone VARCHAR(50),
                latitude VARCHAR(50),
                longitude VARCHAR(50),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_loc_tenant (tenant_id)
            ) $col;",

            "CREATE TABLE {$p}payments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED,
                appointment_id BIGINT UNSIGNED,
                amount DECIMAL(10,2),
                status VARCHAR(50),
                method VARCHAR(50),
                transaction_id VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_pay_tenant (tenant_id),
                KEY idx_pay_appt (appointment_id)
            ) $col;",

            "CREATE TABLE {$p}custom_fields (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                label VARCHAR(255),
                type VARCHAR(50),
                required TINYINT(1) DEFAULT 0,
                position INT,
                settings JSON
            ) $col;",

            "CREATE TABLE {$p}custom_field_options (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                field_id BIGINT UNSIGNED,
                label VARCHAR(255),
                position INT,
                KEY idx_cfo_field (field_id)
            ) $col;",

            "CREATE TABLE {$p}custom_field_map (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                field_id BIGINT UNSIGNED,
                entity_type VARCHAR(50),
                entity_id BIGINT UNSIGNED,
                KEY idx_cfm_entity (entity_type, entity_id)
            ) $col;",

            "CREATE TABLE {$p}notifications (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                name VARCHAR(255),
                type VARCHAR(50),
                status VARCHAR(50),
                subject TEXT,
                content TEXT,
                trigger_entity VARCHAR(50),
                trigger_action VARCHAR(50),
                settings JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_notif_tenant (tenant_id),
                KEY idx_notif_trigger (trigger_entity, trigger_action)
            ) $col;",

            "CREATE TABLE {$p}notification_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                notification_id BIGINT UNSIGNED,
                user_id BIGINT UNSIGNED,
                appointment_id BIGINT UNSIGNED,
                status VARCHAR(50),
                sent_at DATETIME,
                response TEXT,
                KEY idx_notiflog_notif (notification_id),
                KEY idx_notiflog_user (user_id)
            ) $col;",

            // =========================================================
            //  Settings
            // =========================================================

            "CREATE TABLE {$p}settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                settings_key VARCHAR(64) NOT NULL,
                value JSON,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_tenant_settings (tenant_id, settings_key)
            ) $col;",

            "CREATE TABLE {$p}booking_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                slot_length_minutes INT DEFAULT 15,
                default_status VARCHAR(32) DEFAULT 'approved',
                use_service_duration TINYINT(1) DEFAULT 0,
                buffer_time_in_slot TINYINT(1) DEFAULT 0,
                min_time_before_booking INT DEFAULT 0,
                min_time_before_cancel INT DEFAULT 172800,
                min_time_before_reschedule INT DEFAULT 172800,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_booking_settings (tenant_id)
            ) $col;",

            "CREATE TABLE {$p}working_hours_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                week_schedule JSON,
                days_off JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_working_hours_settings (tenant_id)
            ) $col;",

            "CREATE TABLE {$p}company_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                name VARCHAR(255),
                address VARCHAR(255),
                phone VARCHAR(50),
                email VARCHAR(190),
                website VARCHAR(255),
                logo_url VARCHAR(255),
                translations JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_company_settings (tenant_id)
            ) $col;",

            "CREATE TABLE {$p}notifications_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                mail_service VARCHAR(32) DEFAULT 'smtp',
                smtp_host VARCHAR(255),
                smtp_port INT,
                smtp_secure VARCHAR(16),
                smtp_username VARCHAR(255),
                smtp_password VARCHAR(255),
                sender_name VARCHAR(255),
                sender_email VARCHAR(190),
                notify_customers TINYINT(1) DEFAULT 1,
                bcc_email VARCHAR(255),
                bcc_sms VARCHAR(255),
                sms_balance_email JSON,
                send_invoice TINYINT(1) DEFAULT 0,
                send_ics_attachment TINYINT(1) DEFAULT 0,
                send_ics_attachment_pending TINYINT(1) DEFAULT 0,
                provider_settings JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_notifications_settings (tenant_id)
            ) $col;",

            "CREATE TABLE {$p}payments_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                currency VARCHAR(16) DEFAULT 'CHF',
                currency_symbol VARCHAR(8) DEFAULT 'CHF',
                currency_position VARCHAR(32) DEFAULT 'afterWithSpace',
                price_separator VARCHAR(16) DEFAULT 'space-dot',
                price_decimals INT DEFAULT 0,
                hide_currency_frontend TINYINT(1) DEFAULT 0,
                default_payment_method VARCHAR(32) DEFAULT 'onSite',
                cart_enabled TINYINT(1) DEFAULT 0,
                coupons_enabled TINYINT(1) DEFAULT 0,
                coupons_case_insensitive TINYINT(1) DEFAULT 1,
                payment_links_enabled TINYINT(1) DEFAULT 0,
                taxes_enabled TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_payments_settings (tenant_id)
            ) $col;",

            "CREATE TABLE {$p}integrations_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                google_calendar JSON,
                outlook_calendar JSON,
                apple_calendar JSON,
                zoom JSON,
                webhooks JSON,
                analytics JSON,
                lessonspace JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_integrations_settings (tenant_id)
            ) $col;",

            "CREATE TABLE {$p}event_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                allow_overbooking TINYINT(1) DEFAULT 0,
                allow_underbooking TINYINT(1) DEFAULT 0,
                employee_selection_logic VARCHAR(32) DEFAULT 'roundRobin',
                persons_count_logic VARCHAR(32) DEFAULT 'default',
                waiting_list_enabled TINYINT(1) DEFAULT 0,
                min_capacity_default INT DEFAULT 1,
                max_capacity_default INT DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_event_settings (tenant_id)
            ) $col;",

            // ACL-Tabelle für Cross-Tenant-Sharing
            "CREATE TABLE {$p}share_acl (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                resource_type VARCHAR(64) NOT NULL,
                resource_id BIGINT UNSIGNED NOT NULL,
                owner_tenant BIGINT UNSIGNED NOT NULL,
                grantee_tenant BIGINT UNSIGNED NOT NULL,
                scope VARCHAR(64) DEFAULT 'view',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NULL,
                UNIQUE KEY uq_share (resource_type, resource_id, grantee_tenant),
                KEY idx_share_owner (owner_tenant),
                KEY idx_share_expires (expires_at)
            ) $col;",

            // =========================================================
            //  Zentrale Kategorien für Angebote (service|event|coupon)
            // =========================================================
            "CREATE TABLE {$p}offer_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                offer_type ENUM('event','service','package','coupon') NOT NULL,
                slug VARCHAR(120) NOT NULL,
                name VARCHAR(255) NOT NULL,
                color VARCHAR(20) DEFAULT NULL,
                i18n JSON DEFAULT NULL,
                sort INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_tenant_type_slug (tenant_id, offer_type, slug),
                KEY idx_tenant_type (tenant_id, offer_type)
            ) $col;",

            "CREATE TABLE {$p}offer_category_rel (
                category_id BIGINT UNSIGNED NOT NULL,
                offer_type ENUM('event','service','package','coupon') NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (category_id, offer_type, entity_id),
                KEY idx_offer_type_entity (offer_type, entity_id)
            ) $col;",

            // =========================================================
            //  Kalender-Integrationen & Sync
            // =========================================================

            "CREATE TABLE {$p}calendar_connections (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                provider ENUM('google','microsoft','exchange','icloud','ics') NOT NULL,
                account_email VARCHAR(190) NULL,
                scope ENUM('ro','rw') NOT NULL DEFAULT 'ro',
                auth_type ENUM('oauth','caldav','ics') NOT NULL DEFAULT 'oauth',
                access_token TEXT NULL,
                refresh_token TEXT NULL,
                expires_at DATETIME NULL,
                caldav_url TEXT NULL,
                caldav_username VARCHAR(190) NULL,
                caldav_password_enc TEXT NULL,
                ics_url TEXT NULL,
                meta JSON NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_user_provider (user_id, provider)
            ) $col;",

            "CREATE TABLE {$p}calendars (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                connection_id BIGINT UNSIGNED NOT NULL,
                calendar_id VARCHAR(255) NOT NULL,
                name VARCHAR(255) NULL,
                access ENUM('ro','rw') NOT NULL DEFAULT 'ro',
                is_busy_source TINYINT(1) NOT NULL DEFAULT 1,
                is_default_write TINYINT(1) NOT NULL DEFAULT 0,
                time_zone VARCHAR(100) NULL,
                color VARCHAR(32) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_conn_cal (connection_id, calendar_id),
                KEY idx_conn (connection_id)
            ) $col;",

            "CREATE TABLE {$p}calendar_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                appointment_id BIGINT UNSIGNED NOT NULL,
                calendar_id BIGINT UNSIGNED NOT NULL,
                external_event_id VARCHAR(255) NOT NULL,
                status ENUM('created','updated','cancelled') DEFAULT 'created',
                last_sync_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_app_cal (appointment_id, calendar_id)
            ) $col;",

            // =========================================================
            //  Availability-Definitionen (Workday/Specialday) – lokale TZ
            //  Specialdays ersetzen Workdays am Tag/Range (höhere Priorität).
            // =========================================================

            // Mitarbeiter ↔ Service: Preis/Kapazitäten (stabil gelassen)
            "CREATE TABLE {$p}employees_services (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                service_id BIGINT UNSIGNED NOT NULL,
                price DECIMAL(10,2) NULL,
                min_capacity INT NULL,
                max_capacity INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_emp_service (user_id, service_id),
                KEY idx_empsvc_service (service_id)
            ) $col;",

            // -------- Workdays (Defaults für typische Wochenstruktur) --------
            "CREATE TABLE {$p}employees_workday_sets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                week_day_id TINYINT UNSIGNED NOT NULL,
                label VARCHAR(190) NULL,
                sort SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_wds_user_day (user_id, week_day_id),
                KEY idx_wds_user_day_sort (user_id, week_day_id, sort)
            ) $col;",

            "CREATE TABLE {$p}employees_workday_intervals (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                set_id BIGINT UNSIGNED NOT NULL,
                start_time TIME NOT NULL,
                end_time   TIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_wdi_set (set_id),
                KEY idx_wdi_range (set_id, start_time, end_time)
            ) $col;",

            // Defaults: Services/Locations auf SET-Ebene
            "CREATE TABLE {$p}employees_workday_set_services (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                set_id BIGINT UNSIGNED NOT NULL,
                service_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_wdss (set_id, service_id),
                KEY idx_wdss_service (service_id)
            ) $col;",

            "CREATE TABLE {$p}employees_workday_set_locations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                set_id BIGINT UNSIGNED NOT NULL,
                location_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_wdsl (set_id, location_id),
                KEY idx_wdsl_location (location_id)
            ) $col;",

            // Optionale Interval-Overrides (nur wenn einzelne Zeitfenster abweichen)
            "CREATE TABLE {$p}employees_workday_interval_services (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                interval_id BIGINT UNSIGNED NOT NULL,
                service_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_wdis (interval_id, service_id),
                KEY idx_wdis_service (service_id)
            ) $col;",

            "CREATE TABLE {$p}employees_workday_interval_locations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                interval_id BIGINT UNSIGNED NOT NULL,
                location_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_wdil (interval_id, location_id),
                KEY idx_wdil_location (location_id)
            ) $col;",

            // -------- Specialdays (ersetzen Workdays, höhere Priorität) --------
            "CREATE TABLE {$p}employees_days_off (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(255) NULL,
                note TEXT NULL,
                start_date DATE NOT NULL,
                end_date   DATE NOT NULL,
                repeat_yearly TINYINT(1) NOT NULL DEFAULT 0,
                request_status ENUM('approved','pending','rejected','cancelled') NOT NULL DEFAULT 'approved',
                requested_by BIGINT UNSIGNED NULL,
                requested_at DATETIME NULL,
                reviewed_by BIGINT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                rejection_reason TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_emphol_user (user_id),
                KEY idx_emphol_start (start_date),
                KEY idx_emphol_range (user_id, start_date, end_date),
                KEY idx_emphol_repeat (repeat_yearly, start_date),
                KEY idx_emphol_status (request_status),
                KEY idx_emphol_requested (requested_by, requested_at)
            ) $col;",

            "CREATE TABLE {$p}employees_specialday_sets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                start_date DATE NOT NULL,
                end_date   DATE NULL,
                label VARCHAR(190) NULL,
                sort SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_esds_user_date (user_id, start_date, end_date),
                KEY idx_esds_user_sort (user_id, sort)
            ) $col;",

            "CREATE TABLE {$p}employees_specialday_intervals (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                set_id BIGINT UNSIGNED NOT NULL,
                start_time TIME NOT NULL,
                end_time   TIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_esdi_set (set_id),
                KEY idx_esdi_range (set_id, start_time, end_time)
            ) $col;",

            // Defaults: Services/Locations auf SET-Ebene
            "CREATE TABLE {$p}employees_specialday_set_services (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                set_id BIGINT UNSIGNED NOT NULL,
                service_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_sdss (set_id, service_id),
                KEY idx_sdss_service (service_id)
            ) $col;",

            "CREATE TABLE {$p}employees_specialday_set_locations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                set_id BIGINT UNSIGNED NOT NULL,
                location_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_sdsl (set_id, location_id),
                KEY idx_sdsl_location (location_id)
            ) $col;",

            // Optionale Interval-Overrides (nur wenn einzelne Zeitfenster abweichen)
            "CREATE TABLE {$p}employees_specialday_interval_services (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                interval_id BIGINT UNSIGNED NOT NULL,
                service_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_sdis (interval_id, service_id),
                KEY idx_sdis_service (service_id)
            ) $col;",

            "CREATE TABLE {$p}employees_specialday_interval_locations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                interval_id BIGINT UNSIGNED NOT NULL,
                location_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_sdl (interval_id, location_id),
                KEY idx_sdl_location (location_id)
            ) $col;",

            // =========================================================
            //  Time Tracking & Workforce Management
            // =========================================================

            "CREATE TABLE {$p}time_entries (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                clock_in_at DATETIME NOT NULL,
                clock_out_at DATETIME NULL,
                break_minutes INT UNSIGNED DEFAULT 0,
                total_minutes INT UNSIGNED NULL,
                total_hours DECIMAL(8,2) NULL,
                source ENUM('timer','manual','import') NOT NULL DEFAULT 'timer',
                status ENUM('active','completed','corrected','deleted') NOT NULL DEFAULT 'completed',
                notes TEXT NULL,
                location_id BIGINT UNSIGNED NULL,
                service_id BIGINT UNSIGNED NULL,
                created_by BIGINT UNSIGNED NULL,
                approved_by BIGINT UNSIGNED NULL,
                approved_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_time_user (user_id),
                KEY idx_time_tenant (tenant_id),
                KEY idx_time_clock_in (clock_in_at),
                KEY idx_time_range (user_id, clock_in_at, clock_out_at),
                KEY idx_time_status (status),
                KEY idx_time_source (source),
                KEY idx_time_location (location_id),
                KEY idx_time_service (service_id)
            ) $col;",

            "CREATE TABLE {$p}active_timers (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                started_at DATETIME NOT NULL,
                location_id BIGINT UNSIGNED NULL,
                service_id BIGINT UNSIGNED NULL,
                notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_active_timer_user (user_id),
                KEY idx_timer_tenant (tenant_id),
                KEY idx_timer_started (started_at)
            ) $col;",

            // =========================================================
            //  Optionale Technik-Tabellen
            // =========================================================

            // Flüchtiger UI-Cache für Monatsansichten (keine Business-Logik!)
            "CREATE TABLE {$p}availability_month_cache (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                service_id BIGINT UNSIGNED NULL,
                location_id BIGINT UNSIGNED NULL,
                year SMALLINT UNSIGNED NOT NULL,
                month TINYINT UNSIGNED NOT NULL,
                payload MEDIUMTEXT NOT NULL,
                valid_until DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_amc (tenant_id, user_id, service_id, location_id, year, month),
                KEY idx_amc_valid (valid_until)
            ) $col;",

            // Zuverlässige, retry-fähige Webhook-Auslieferung (Outbox-Pattern)
            "CREATE TABLE {$p}webhook_outbox (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                event_type VARCHAR(64) NOT NULL,
                payload JSON NOT NULL,
                attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                next_attempt_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                delivered_at DATETIME NULL,
                status ENUM('pending','delivered','failed') DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_who_status (status, next_attempt_at),
                KEY idx_who_type (event_type)
            ) $col;",

            // =========================================================
            //  API-Keys & Rollen-Settings
            // =========================================================

            "CREATE TABLE {$p}api_keys (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                api_key VARCHAR(128) NOT NULL,
                description VARCHAR(255),
                expires_at DATETIME,
                permissions JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_apikey_tenant (tenant_id),
                UNIQUE KEY uq_api_key (api_key)
            ) $col;",

            "CREATE TABLE {$p}role_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NULL,
                role_slug VARCHAR(64) NOT NULL,
                settings JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_role_settings (tenant_id, role_slug)
            ) $col;",
            "CREATE TABLE {$p}module_states (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(190) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'inactive',
                installed_at DATETIME NULL,
                activated_at DATETIME NULL,
                deactivated_at DATETIME NULL,
                activated_by BIGINT UNSIGNED NULL,
                deactivated_by BIGINT UNSIGNED NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_error TEXT NULL,
                UNIQUE KEY uq_module_states_slug (slug),
                KEY idx_module_states_status (status),
                KEY idx_module_states_updated (updated_at)
            ) $col;",

            "CREATE TABLE {$p}activity_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                severity VARCHAR(20) NOT NULL,
                context VARCHAR(190) NOT NULL,
                message TEXT,
                payload LONGTEXT NULL,
                tenant_id BIGINT UNSIGNED NULL,
                module_slug VARCHAR(190) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_activity_log_severity (severity),
                KEY idx_activity_log_context (context),
                KEY idx_activity_log_logged (logged_at)
            ) $col;",

            // =========================================================
            //  API Keys (für plattformübergreifende Authentifizierung)
            // =========================================================
            "CREATE TABLE {$p}api_keys (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                tenant_id BIGINT UNSIGNED NOT NULL,
                key_hash VARCHAR(64) NOT NULL,
                name VARCHAR(255) NOT NULL,
                permissions JSON DEFAULT NULL,
                rate_limit JSON DEFAULT NULL,
                last_used_at DATETIME NULL,
                expires_at DATETIME NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_api_key_hash (key_hash),
                KEY idx_api_keys_user (user_id),
                KEY idx_api_keys_tenant (tenant_id),
                KEY idx_api_keys_status (status),
                KEY idx_api_keys_expires (expires_at)
            ) $col;",

            // =========================================================
            //  Partner-Relationships (B2B-Kooperationen)
            // =========================================================
            "CREATE TABLE {$p}partner_relationships (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                primary_tenant BIGINT UNSIGNED NOT NULL,
                partner_tenant BIGINT UNSIGNED NOT NULL,
                relationship_type VARCHAR(50) DEFAULT 'trusted_partner',
                status VARCHAR(50) DEFAULT 'active',
                sharing_permissions JSON DEFAULT NULL,
                commission_type VARCHAR(50) DEFAULT 'percentage',
                commission_value DECIMAL(10,2) DEFAULT 0.00,
                metadata JSON DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                expires_at DATETIME NULL,
                UNIQUE KEY uq_partnership (primary_tenant, partner_tenant),
                KEY idx_partner_status (partner_tenant, status),
                KEY idx_primary_status (primary_tenant, status)
            ) $col;",

            // =========================================================
            //  Shared Offerings (Cross-Tenant-Kursfreigabe)
            // =========================================================
            "CREATE TABLE {$p}shared_offerings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                offering_type VARCHAR(50) NOT NULL,
                offering_id BIGINT UNSIGNED NOT NULL,
                owner_tenant BIGINT UNSIGNED NOT NULL,
                visibility VARCHAR(50) DEFAULT 'private',
                allowed_partners JSON DEFAULT NULL,
                commission_type VARCHAR(50) DEFAULT 'percentage',
                commission_value DECIMAL(10,2) DEFAULT 0.00,
                max_referral_slots INT DEFAULT NULL,
                referral_slots_used INT DEFAULT 0,
                custom_title VARCHAR(255) DEFAULT NULL,
                custom_description TEXT DEFAULT NULL,
                custom_price DECIMAL(10,2) DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                metadata JSON DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_offering (offering_type, offering_id),
                KEY idx_owner (owner_tenant),
                KEY idx_visibility_status (visibility, status)
            ) $col;",

            // =========================================================
            //  Commission Ledger (Provisionsabrechnung)
            // =========================================================
            "CREATE TABLE {$p}commission_ledger (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_tenant BIGINT UNSIGNED NOT NULL,
                partner_tenant BIGINT UNSIGNED NOT NULL,
                appointment_id BIGINT UNSIGNED NOT NULL,
                offering_type VARCHAR(50) NOT NULL,
                offering_id BIGINT UNSIGNED NOT NULL,
                booking_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                currency VARCHAR(3) DEFAULT 'EUR',
                status VARCHAR(50) DEFAULT 'pending',
                payment_method VARCHAR(50) DEFAULT NULL,
                payment_reference VARCHAR(255) DEFAULT NULL,
                transaction_date DATETIME NOT NULL,
                approved_at DATETIME DEFAULT NULL,
                paid_at DATETIME DEFAULT NULL,
                metadata JSON DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_commission (appointment_id),
                KEY idx_partner_status (partner_tenant, status),
                KEY idx_owner_paid (owner_tenant, paid_at),
                KEY idx_transaction_date (transaction_date)
            ) $col;",

        ];
        foreach ($sql as $statement) {
            dbDelta($statement);
        }

        // ➜ Nach dem dbDelta-Loop einmal ausführen:
        self::normalizeTimestamps();

        // ➕ Standardrollen initial einfügen (nur wenn leer)
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}roles");
        if ($count === 0) {
            $wpdb->insert("{$p}roles", [ 'slug' => 'bookando_customer' ]);
            $wpdb->insert("{$p}roles", [ 'slug' => 'bookando_employee' ]);
            $wpdb->insert("{$p}roles", [ 'slug' => 'bookando_admin' ]);
            $wpdb->insert("{$p}roles", [ 'slug' => 'bookando_teacher' ]);
        }
    }

    // Erzwingt korrektes ON UPDATE für updated_at + kein ON UPDATE an created_at
    protected static function normalizeTimestamps(): void
    {
        global $wpdb;
        $p = $wpdb->prefix . 'bookando_';
        $tables = [
            "{$p}employees_workday_intervals",
            "{$p}employees_specialday_intervals",
            // hier kannst du bei Bedarf weitere Tabellen mit updated_at ergänzen
        ];

        foreach ($tables as $table) {
            $tableName = esc_sql($table);

            $updatedColumn = $wpdb->get_row(
                $wpdb->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE %s", 'updated_at'),
                ARRAY_A
            );

            if (is_array($updatedColumn)) {
                $extra = strtolower((string) ($updatedColumn['Extra'] ?? ''));
                if (!str_contains($extra, 'on update')) {
                    $wpdb->query(
                        "ALTER TABLE `{$tableName}` " .
                        "MODIFY `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
                    );
                }
            }

            $createdColumn = $wpdb->get_row(
                $wpdb->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE %s", 'created_at'),
                ARRAY_A
            );

            if (is_array($createdColumn)) {
                $extra = strtolower((string) ($createdColumn['Extra'] ?? ''));
                if (str_contains($extra, 'on update')) {
                    $wpdb->query(
                        "ALTER TABLE `{$tableName}` " .
                        "MODIFY `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP"
                    );
                }
            }
        }
    }


    /**
     * Entfernt nicht mehr benötigte Tabellen aus alten Modul-Versionen.
     */
    protected static function migrateLegacyModuleTables(): void
    {
        global $wpdb;

        $legacyTable = $wpdb->prefix . 'bookando_customers';
        $backupTable = $legacyTable . '_legacy';

        $likeLegacy = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $legacyTable);
        $legacyExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $likeLegacy));
        if ($legacyExists !== $legacyTable) {
            return;
        }

        $likeBackup = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $backupTable);
        $backupExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $likeBackup));
        if ($backupExists === $backupTable) {
            $wpdb->query("DROP TABLE `{$legacyTable}`");
            ActivityLogger::log(
                'installer.legacy',
                'Legacy table removed because backup exists',
                [
                    'table'  => $legacyTable,
                    'backup' => $backupTable,
                ],
                ActivityLogger::LEVEL_INFO,
                null,
                'core'
            );

            return;
        }

        $renamed = $wpdb->query("RENAME TABLE `{$legacyTable}` TO `{$backupTable}`");
        if ($renamed === false) {
            ActivityLogger::log(
                'installer.legacy',
                'Failed to rename legacy table',
                [
                    'table'  => $legacyTable,
                    'backup' => $backupTable,
                ],
                ActivityLogger::LEVEL_WARNING,
                null,
                'core'
            );

            return;
        }

        ActivityLogger::log(
            'installer.legacy',
            'Legacy table renamed for archival',
            [
                'table'  => $legacyTable,
                'backup' => $backupTable,
            ],
            ActivityLogger::LEVEL_INFO,
            null,
            'core'
        );
    }

    /**
     * Führt alle registrierten Datenbankmigrationen aus.
     */
    protected static function runDatabaseMigrations(): void
    {
        ActivityLogger::log(
            'installer.migrations',
            'Starting database migrations',
            [],
            ActivityLogger::LEVEL_INFO,
            null,
            'core'
        );

        $results = Migrator::runAllMigrations();

        foreach ($results as $migration => $success) {
            ActivityLogger::log(
                'installer.migrations',
                $success ? 'Migration completed successfully' : 'Migration failed',
                ['migration' => $migration],
                $success ? ActivityLogger::LEVEL_INFO : ActivityLogger::LEVEL_ERROR,
                null,
                'core'
            );
        }

        ActivityLogger::log(
            'installer.migrations',
            'Database migrations finished',
            [
                'total' => count($results),
                'successful' => count(array_filter($results)),
                'failed' => count(array_filter($results, fn($r) => !$r)),
            ],
            ActivityLogger::LEVEL_INFO,
            null,
            'core'
        );
    }

    /**
     * Lädt Module, ruft deren install() und Capabilities::register() auf.
     */
    protected static function installModules(): void
    {
        $modulesPath = plugin_dir_path(BOOKANDO_PLUGIN_FILE) . 'src/modules/';
        $repository = ModuleStateRepository::instance();

        $legacyActive = get_option('bookando_active_modules', []);
        if (!is_array($legacyActive)) {
            $legacyActive = [];
        }
        $legacyActive = array_values(array_unique(array_map('strval', $legacyActive)));
        $defaultActive = empty($legacyActive);

        $activeSlugs = [];

        foreach (glob($modulesPath . '*/module.json') as $jsonPath) {
            $folder = basename(dirname($jsonPath));
            $meta = json_decode(file_get_contents($jsonPath), true) ?? [];
            $slug = $meta['slug'] ?? $folder;

            if (ModuleManager::isLegacySlug($slug) || ModuleManager::isLegacySlug($folder)) {
                ActivityLogger::log(
                    'installer.modules',
                    'Legacy module skipped during install',
                    [
                        'slug' => $slug,
                        'folder' => $folder,
                    ],
                    ActivityLogger::LEVEL_INFO,
                    null,
                    $slug
                );
                continue;
            }

            // Modul-Install ausführen
            $class = "Bookando\\Modules\\{$folder}\\Module";
            if (class_exists($class) && method_exists($class, 'install')) {
                ActivityLogger::log(
                    'installer.modules',
                    'Invoking module installer',
                    [
                        'class' => $class,
                        'slug'  => $slug,
                    ],
                    ActivityLogger::LEVEL_INFO,
                    null,
                    $slug
                );
                $class::install();
            }

            // Modul-Capabilities registrieren
            $capClass = "Bookando\\Modules\\{$folder}\\Capabilities";
            if (class_exists($capClass) && method_exists($capClass, 'register')) {
                ActivityLogger::log(
                    'installer.modules',
                    'Registering module capabilities',
                    [
                        'class' => $capClass,
                        'slug'  => $slug,
                    ],
                    ActivityLogger::LEVEL_INFO,
                    null,
                    $slug
                );
                $capClass::register();
            }

            $installedAt = get_option('bookando_module_installed_at_' . strtolower($slug));
            $hasRecordedState = $repository->hasRecordedState($slug);
            $isActive = $defaultActive
                || in_array($slug, $legacyActive, true)
                || !$hasRecordedState;

            ActivityLogger::log(
                'installer.modules',
                'Evaluated module activation state',
                [
                    'slug' => $slug,
                    'defaultActive' => $defaultActive,
                    'legacyActive' => in_array($slug, $legacyActive, true),
                    'hasRecordedState' => $hasRecordedState,
                    'isActive' => $isActive,
                ],
                ActivityLogger::LEVEL_INFO,
                null,
                $slug
            );
            $repository->persistInitialState($slug, $isActive, $installedAt ? (int) $installedAt : null);

            if ($isActive) {
                $activeSlugs[] = $slug;
            }
        }

        update_option('bookando_active_modules', $activeSlugs, false);
        ActivityLogger::info('installer.modules', 'Module installation state synced', [
            'active' => $activeSlugs,
            'total'  => count($activeSlugs),
        ]);
    }
}
