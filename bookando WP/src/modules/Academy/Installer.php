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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
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
                instructor VARCHAR(255),
                program VARCHAR(255),
                category VARCHAR(10),
                progress DECIMAL(5,4) DEFAULT 0,
                notes TEXT,
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student (student),
                INDEX idx_category (category),
                INDEX idx_status (status)
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
                order_index INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (topic_id) REFERENCES {$prefix}training_topics(id) ON DELETE CASCADE,
                INDEX idx_topic (topic_id),
                INDEX idx_completed (completed),
                INDEX idx_order (order_index)
            ) $charset;",
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        // Setze Versions-Flag
        update_option('bookando_academy_db_version', '1.0.0');
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
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('bookando_academy_db_version');
        delete_option('bookando_academy_state'); // Alte wp_options Daten entfernen
    }
}
