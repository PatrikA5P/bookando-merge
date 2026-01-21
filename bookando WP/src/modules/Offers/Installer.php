<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers;

final class Installer
{
    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table   = $wpdb->prefix . 'bookando_offers';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(191) NOT NULL,
            description TEXT,

            -- Offer Type: dienstleistungen (individual services), kurse (planned courses), online (self-paced)
            offer_type VARCHAR(32) NOT NULL DEFAULT 'dienstleistungen',

            -- Pricing
            price DECIMAL(10,2) DEFAULT NULL,
            currency VARCHAR(3) DEFAULT 'EUR',

            -- Duration (for individual services/appointments)
            duration_minutes INT DEFAULT NULL,

            -- Scheduling (for planned courses)
            schedule_type VARCHAR(32) DEFAULT NULL, -- single_date, recurring, multi_day
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            start_time TIME DEFAULT NULL,
            end_time TIME DEFAULT NULL,
            recurrence_pattern VARCHAR(100) DEFAULT NULL, -- JSON: {days: [1,3,5], frequency: 'weekly'}
            max_participants INT DEFAULT NULL,
            current_participants INT DEFAULT 0,

            -- Academy Integration (requires Academy module license)
            academy_course_ids JSON, -- Array of course IDs from Academy module
            academy_access_type VARCHAR(32) DEFAULT 'on_completion', -- immediate, on_completion, manual
            academy_access_duration_days INT DEFAULT NULL, -- How long after purchase/completion users have access (NULL = lifetime)
            auto_enroll_academy BOOLEAN DEFAULT 0, -- Auto-enroll in Academy courses on purchase/completion
            academy_certificate_on_completion BOOLEAN DEFAULT 1, -- Award certificate from Academy course

            -- Online Course Settings (deprecated in favor of Academy integration)
            access_duration_days INT DEFAULT NULL, -- Legacy: How long users have access
            course_url VARCHAR(500) DEFAULT NULL, -- Legacy: External course URL
            platform VARCHAR(50) DEFAULT NULL, -- Legacy: zoom, moodle, custom, etc.

            -- Booking Settings
            booking_enabled BOOLEAN DEFAULT 1,
            requires_approval BOOLEAN DEFAULT 0,
            advance_booking_min INT DEFAULT NULL, -- Minutes in advance required
            advance_booking_max INT DEFAULT NULL, -- Max days in advance allowed

            -- Categories & Tags (JSON arrays)
            category_ids JSON,
            tag_ids JSON,
            employee_ids JSON,
            location_ids JSON,

            -- Display Settings
            featured BOOLEAN DEFAULT 0,
            display_order INT DEFAULT 0,
            image_url VARCHAR(500) DEFAULT NULL,

            -- Metadata
            status VARCHAR(32) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,

            KEY tenant_id (tenant_id),
            KEY offer_type (offer_type),
            KEY status (status),
            KEY start_date (start_date),
            KEY featured (featured),
            KEY display_order (display_order)
        ) {$charset};";

        dbDelta($sql);

        update_option('bookando_offers_db_version', '2.0.0');
    }
}
