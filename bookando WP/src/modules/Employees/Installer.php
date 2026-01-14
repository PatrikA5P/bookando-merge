<?php

declare(strict_types=1);

namespace Bookando\Modules\Employees;

class Installer
{
    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tableName = $wpdb->prefix . 'bookando_employees';
        $charset   = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(191) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) {$charset};";

        dbDelta($sql);
    }
}
