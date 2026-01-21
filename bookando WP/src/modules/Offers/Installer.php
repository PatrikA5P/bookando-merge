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

        // Create dummy offers for demonstration
        self::createDummyOffers();
    }

    /**
     * Create dummy offers for testing and demonstration
     */
    private static function createDummyOffers(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_offers';

        // Get tenant ID
        $tenantId = \Bookando\Core\Tenant\TenantManager::currentTenantId();

        // Check if offers already exist for this tenant
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE tenant_id = %d",
            $tenantId
        ));

        // Only create dummy offers if none exist
        if ((int)$count > 0) {
            return;
        }

        $dummyOffers = [
            // Dienstleistungen (Individual Services)
            [
                'title' => 'Fahrlektion Auto (45 Min)',
                'description' => 'Einzelne Fahrstunde mit erfahrenem Fahrlehrer',
                'offer_type' => 'dienstleistungen',
                'price' => 85.00,
                'currency' => 'CHF',
                'duration_minutes' => 45,
                'booking_enabled' => 1,
                'status' => 'active',
                'featured' => 1,
                'display_order' => 1
            ],
            [
                'title' => 'Fahrlektion Motorrad (45 Min)',
                'description' => 'Motorrad-Fahrstunde für Anfänger und Fortgeschrittene',
                'offer_type' => 'dienstleistungen',
                'price' => 95.00,
                'currency' => 'CHF',
                'duration_minutes' => 45,
                'booking_enabled' => 1,
                'status' => 'active',
                'display_order' => 2
            ],
            [
                'title' => 'Autobahnfahrt (90 Min)',
                'description' => 'Training für Autobahnfahrten und schnelles Fahren',
                'offer_type' => 'dienstleistungen',
                'price' => 160.00,
                'currency' => 'CHF',
                'duration_minutes' => 90,
                'booking_enabled' => 1,
                'status' => 'active',
                'display_order' => 3
            ],

            // Kurse (Scheduled Courses)
            [
                'title' => 'VKU Kurs (8 Lektionen)',
                'description' => 'Verkehrskunde-Kurs obligatorisch für alle Lernfahrer',
                'offer_type' => 'kurse',
                'price' => 250.00,
                'currency' => 'CHF',
                'duration_minutes' => 480, // 8 hours total
                'max_participants' => 12,
                'current_participants' => 5,
                'booking_enabled' => 1,
                'status' => 'active',
                'featured' => 1,
                'display_order' => 10
            ],
            [
                'title' => 'Nothelferkurs',
                'description' => 'Obligatorischer Nothelferkurs für Führerschein-Erwerb',
                'offer_type' => 'kurse',
                'price' => 150.00,
                'currency' => 'CHF',
                'duration_minutes' => 600, // 10 hours
                'max_participants' => 15,
                'current_participants' => 8,
                'booking_enabled' => 1,
                'status' => 'active',
                'featured' => 1,
                'display_order' => 11
            ],
            [
                'title' => 'Intensivkurs Auto (5 Tage)',
                'description' => 'Fünftägiger Intensivkurs mit täglich 3 Fahrstunden',
                'offer_type' => 'kurse',
                'price' => 1200.00,
                'currency' => 'CHF',
                'duration_minutes' => 1350, // 5 days x 4.5 hours
                'max_participants' => 4,
                'current_participants' => 2,
                'booking_enabled' => 1,
                'status' => 'active',
                'display_order' => 12
            ],

            // Online (Self-paced courses)
            [
                'title' => 'Theorieprüfung Online-Vorbereitung',
                'description' => 'Vollständiger Online-Kurs zur Vorbereitung auf die Theorieprüfung',
                'offer_type' => 'online',
                'price' => 49.00,
                'currency' => 'CHF',
                'access_duration_days' => 90,
                'booking_enabled' => 1,
                'status' => 'active',
                'featured' => 1,
                'display_order' => 20
            ],
            [
                'title' => 'Verkehrsregeln Online-Training',
                'description' => 'Interaktives Online-Training zu Schweizer Verkehrsregeln',
                'offer_type' => 'online',
                'price' => 29.00,
                'currency' => 'CHF',
                'access_duration_days' => 60,
                'booking_enabled' => 1,
                'status' => 'active',
                'display_order' => 21
            ]
        ];

        foreach ($dummyOffers as $offer) {
            $wpdb->insert(
                $table,
                [
                    'tenant_id' => $tenantId,
                    'title' => $offer['title'],
                    'description' => $offer['description'],
                    'offer_type' => $offer['offer_type'],
                    'price' => $offer['price'],
                    'currency' => $offer['currency'],
                    'duration_minutes' => $offer['duration_minutes'] ?? null,
                    'max_participants' => $offer['max_participants'] ?? null,
                    'current_participants' => $offer['current_participants'] ?? 0,
                    'access_duration_days' => $offer['access_duration_days'] ?? null,
                    'booking_enabled' => $offer['booking_enabled'],
                    'status' => $offer['status'],
                    'featured' => $offer['featured'] ?? 0,
                    'display_order' => $offer['display_order'],
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                [
                    '%d', // tenant_id
                    '%s', // title
                    '%s', // description
                    '%s', // offer_type
                    '%f', // price
                    '%s', // currency
                    '%d', // duration_minutes
                    '%d', // max_participants
                    '%d', // current_participants
                    '%d', // access_duration_days
                    '%d', // booking_enabled
                    '%s', // status
                    '%d', // featured
                    '%d', // display_order
                    '%s', // created_at
                    '%s'  // updated_at
                ]
            );
        }
    }
