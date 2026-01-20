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

            -- Offer Type: termine (individual appointments), kurse (planned courses), online (e-learning)
            offer_type VARCHAR(32) NOT NULL DEFAULT 'termine',

            -- Pricing
            price DECIMAL(10,2) DEFAULT NULL,
            currency VARCHAR(3) DEFAULT 'EUR',

            -- Duration (for individual appointments)
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

            -- Online Course Settings
            access_duration_days INT DEFAULT NULL, -- How long users have access
            course_url VARCHAR(500) DEFAULT NULL,
            platform VARCHAR(50) DEFAULT NULL, -- zoom, moodle, custom, etc.

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
