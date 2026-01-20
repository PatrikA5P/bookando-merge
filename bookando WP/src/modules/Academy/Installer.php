<?php

declare(strict_types=1);

namespace Bookando\Modules\Academy;

class Installer
{
    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $prefix = $wpdb->prefix . 'bookando_academy_';
        $charset = $wpdb->get_charset_collate();

        $tables = [
            // =========================================================
            // Kurse
            // =========================================================
            "CREATE TABLE {$prefix}courses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                course_type VARCHAR(50) DEFAULT 'online',
                category VARCHAR(100),
                level VARCHAR(50) DEFAULT 'beginner',
                visibility VARCHAR(50) DEFAULT 'public',
                featured_image TEXT,
                author VARCHAR(255),
                status VARCHAR(50) DEFAULT 'active',
                price DECIMAL(10,2) DEFAULT 0,
                currency VARCHAR(3) DEFAULT 'CHF',
                discount_eligible BOOLEAN DEFAULT 1,
                max_participants INT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) $charset;",

            // =========================================================
            // Packages (Ausbildungspakete)
            // =========================================================
            "CREATE TABLE {$prefix}packages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                items JSON,
                price DECIMAL(10,2) NOT NULL,
                original_price DECIMAL(10,2) DEFAULT NULL,
                discount_percent DECIMAL(5,2) DEFAULT 0,
                currency VARCHAR(3) DEFAULT 'CHF',
                validity_days INT DEFAULT NULL,
                category VARCHAR(100),
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_status (status)
            ) $charset;",

            // =========================================================
            // Topics (Themen innerhalb von Kursen)
            // =========================================================
            "CREATE TABLE {$prefix}topics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES {$prefix}courses(id) ON DELETE CASCADE,
                INDEX idx_course (course_id),
                INDEX idx_order (order_index)
            ) $charset;",

            // =========================================================
            // Lektionen (innerhalb von Topics)
            // =========================================================
            "CREATE TABLE {$prefix}lessons (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT,
                duration INT DEFAULT 0,
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (topic_id) REFERENCES {$prefix}topics(id) ON DELETE CASCADE,
                INDEX idx_topic (topic_id),
                INDEX idx_order (order_index)
            ) $charset;",

            // =========================================================
            // Quizzes (innerhalb von Topics)
            // =========================================================
            "CREATE TABLE {$prefix}quizzes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                questions JSON,
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (topic_id) REFERENCES {$prefix}topics(id) ON DELETE CASCADE,
                INDEX idx_topic (topic_id),
                INDEX idx_order (order_index)
            ) $charset;",

            // =========================================================
            // Ausbildungskarten
            // =========================================================
            "CREATE TABLE {$prefix}training_cards (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student VARCHAR(255) NOT NULL,
                customer_id BIGINT UNSIGNED DEFAULT NULL,
                instructor VARCHAR(255),
                program VARCHAR(255),
                category VARCHAR(10),
                package_id BIGINT UNSIGNED DEFAULT NULL,
                progress DECIMAL(5,4) DEFAULT 0,
                notes TEXT,
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student (student),
                INDEX idx_customer (customer_id),
                INDEX idx_category (category),
                INDEX idx_status (status),
                INDEX idx_package (package_id)
            ) $charset;",

            // =========================================================
            // Milestones (Legacy-Support für Ausbildungskarten)
            // =========================================================
            "CREATE TABLE {$prefix}training_milestones (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                card_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                completed BOOLEAN DEFAULT 0,
                completed_at DATETIME NULL,
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (card_id) REFERENCES {$prefix}training_cards(id) ON DELETE CASCADE,
                INDEX idx_card (card_id),
                INDEX idx_completed (completed)
            ) $charset;",

            // =========================================================
            // Training Topics (Hauptthemen in Ausbildungskarten)
            // =========================================================
            "CREATE TABLE {$prefix}training_topics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                card_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (card_id) REFERENCES {$prefix}training_cards(id) ON DELETE CASCADE,
                INDEX idx_card (card_id),
                INDEX idx_order (order_index)
            ) $charset;",

            // =========================================================
            // Training Lessons (Lektionen in Training Topics)
            // =========================================================
            "CREATE TABLE {$prefix}training_lessons (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                completed BOOLEAN DEFAULT 0,
                completed_at DATETIME NULL,
                notes TEXT,
                resources JSON,
                price DECIMAL(10,2) DEFAULT 0,
                invoice_id VARCHAR(50) DEFAULT NULL,
                payment_status VARCHAR(50) DEFAULT 'unpaid',
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (topic_id) REFERENCES {$prefix}training_topics(id) ON DELETE CASCADE,
                INDEX idx_topic (topic_id),
                INDEX idx_completed (completed),
                INDEX idx_payment_status (payment_status),
                INDEX idx_invoice (invoice_id),
                INDEX idx_order (order_index)
            ) $charset;",
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        // Migrations für bestehende Installationen
        self::migrate();

        // Setze Versions-Flag
        update_option('bookando_academy_db_version', '2.0.0');
    }

    /**
     * Führt Migrationen für bestehende Installationen durch.
     */
    private static function migrate(): void
    {
        global $wpdb;

        $current_version = get_option('bookando_academy_db_version', '0.0.0');

        // Migration von 1.0.0 zu 2.0.0: Finanz-Integration
        if (version_compare($current_version, '2.0.0', '<')) {
            $prefix = $wpdb->prefix . 'bookando_academy_';

            // Prüfe und füge fehlende Spalten hinzu
            $columns_to_add = [
                // Courses Tabelle
                "ALTER TABLE {$prefix}courses ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 0",
                "ALTER TABLE {$prefix}courses ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'CHF'",
                "ALTER TABLE {$prefix}courses ADD COLUMN IF NOT EXISTS discount_eligible BOOLEAN DEFAULT 1",
                "ALTER TABLE {$prefix}courses ADD COLUMN IF NOT EXISTS max_participants INT DEFAULT NULL",

                // Training Cards Tabelle
                "ALTER TABLE {$prefix}training_cards ADD COLUMN IF NOT EXISTS customer_id BIGINT UNSIGNED DEFAULT NULL",
                "ALTER TABLE {$prefix}training_cards ADD COLUMN IF NOT EXISTS package_id BIGINT UNSIGNED DEFAULT NULL",
                "ALTER TABLE {$prefix}training_cards ADD INDEX IF NOT EXISTS idx_customer (customer_id)",
                "ALTER TABLE {$prefix}training_cards ADD INDEX IF NOT EXISTS idx_package (package_id)",

                // Training Lessons Tabelle
                "ALTER TABLE {$prefix}training_lessons ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 0",
                "ALTER TABLE {$prefix}training_lessons ADD COLUMN IF NOT EXISTS invoice_id VARCHAR(50) DEFAULT NULL",
                "ALTER TABLE {$prefix}training_lessons ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'unpaid'",
                "ALTER TABLE {$prefix}training_lessons ADD INDEX IF NOT EXISTS idx_payment_status (payment_status)",
                "ALTER TABLE {$prefix}training_lessons ADD INDEX IF NOT EXISTS idx_invoice (invoice_id)",
            ];

            foreach ($columns_to_add as $sql) {
                $wpdb->query($sql);
            }
        }
    }

    /**
     * Deinstalliert alle Academy-Tabellen (nur bei vollständiger Plugin-Deinstallation).
     */
    public static function uninstall(): void
    {
        global $wpdb;

        $prefix = $wpdb->prefix . 'bookando_academy_';
        $tables = [
            "{$prefix}training_lessons",
            "{$prefix}training_topics",
            "{$prefix}training_milestones",
            "{$prefix}training_cards",
            "{$prefix}quizzes",
            "{$prefix}lessons",
            "{$prefix}topics",
            "{$prefix}courses",
            "{$prefix}packages",
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('bookando_academy_db_version');
        delete_option('bookando_academy_state'); // Alte wp_options Daten entfernen
    }
}
