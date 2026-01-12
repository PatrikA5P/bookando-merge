<?php

declare(strict_types=1);

namespace Bookando\Core\Database;

use Bookando\Core\Service\ActivityLogger;

/**
 * Migration 002: Create Queue Jobs Table
 *
 * Creates the bookando_queue_jobs table for async job processing.
 *
 * @package Bookando\Core\Database
 */
class Migration002_CreateQueueTable
{
    /**
     * Run the migration
     *
     * @return bool Success
     */
    public static function up(): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bookando_queue_jobs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_class VARCHAR(255) NOT NULL,
            payload LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            priority TINYINT UNSIGNED NOT NULL DEFAULT 5,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            unique_key VARCHAR(255) NULL,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL,
            available_at DATETIME NOT NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            failed_at DATETIME NULL,
            PRIMARY KEY (id),
            INDEX idx_status_priority (status, priority, available_at),
            INDEX idx_unique_key (unique_key),
            INDEX idx_created_at (created_at),
            INDEX idx_status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Verify table creation
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));

        if ($table_exists) {
            ActivityLogger::info('migration.002', 'Queue table created successfully', [
                'table' => $table_name,
            ]);
            return true;
        }

        ActivityLogger::error('migration.002', 'Failed to create queue table', [
            'table' => $table_name,
        ]);
        return false;
    }

    /**
     * Rollback the migration
     *
     * @return bool Success
     */
    public static function down(): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bookando_queue_jobs';

        $result = $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

        if ($result !== false) {
            ActivityLogger::info('migration.002_rollback', 'Queue table dropped', [
                'table' => $table_name,
            ]);
            return true;
        }

        return false;
    }
}
