<?php
declare(strict_types=1);

namespace Bookando\Modules\Tools;

class Installer
{
    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Custom Fields Table
        $custom_fields_table = $wpdb->prefix . 'bookando_custom_fields';
        $sql_custom_fields = "CREATE TABLE {$custom_fields_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) unsigned NOT NULL DEFAULT 0,
            name varchar(255) NOT NULL,
            label varchar(255) NOT NULL,
            field_type varchar(50) NOT NULL DEFAULT 'text',
            entity_type varchar(50) NOT NULL,
            options longtext,
            is_required tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            position int(11) NOT NULL DEFAULT 0,
            validation_rules longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tenant_id (tenant_id),
            KEY entity_type (entity_type),
            KEY is_active (is_active)
        ) {$charset_collate};";
        dbDelta($sql_custom_fields);

        // Booking Forms Table (replaces Form Templates)
        $booking_forms_table = $wpdb->prefix . 'bookando_booking_forms';
        $sql_booking_forms = "CREATE TABLE {$booking_forms_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) unsigned NOT NULL DEFAULT 0,
            name varchar(255) NOT NULL,
            description text,
            fields longtext NOT NULL,
            is_default tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tenant_id (tenant_id),
            KEY is_default (is_default),
            KEY is_active (is_active)
        ) {$charset_collate};";
        dbDelta($sql_booking_forms);

        // Notification Matrices Table (new cascading notification system)
        $notification_matrices_table = $wpdb->prefix . 'bookando_notification_matrices';
        $sql_notification_matrices = "CREATE TABLE {$notification_matrices_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) unsigned NOT NULL DEFAULT 0,
            name varchar(255) NOT NULL,
            variants longtext NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tenant_id (tenant_id),
            KEY is_active (is_active)
        ) {$charset_collate};";
        dbDelta($sql_notification_matrices);

        // Notification Logs Table
        $notification_logs_table = $wpdb->prefix . 'bookando_notification_logs';
        $sql_notification_logs = "CREATE TABLE {$notification_logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) unsigned NOT NULL DEFAULT 0,
            notification_id bigint(20) unsigned,
            notification_name varchar(255) NOT NULL DEFAULT '',
            recipient varchar(255) NOT NULL,
            channel varchar(50) NOT NULL,
            status varchar(50) NOT NULL,
            error_message text,
            sent_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            metadata longtext,
            PRIMARY KEY (id),
            KEY tenant_id (tenant_id),
            KEY notification_id (notification_id),
            KEY status (status),
            KEY sent_at (sent_at)
        ) {$charset_collate};";
        dbDelta($sql_notification_logs);

        // Design Settings Table (stores theme customizations)
        $design_settings_table = $wpdb->prefix . 'bookando_design_settings';
        $sql_design_settings = "CREATE TABLE {$design_settings_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) unsigned NOT NULL DEFAULT 0,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            context varchar(100) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tenant_setting (tenant_id, setting_key, context),
            KEY tenant_id (tenant_id),
            KEY context (context)
        ) {$charset_collate};";
        dbDelta($sql_design_settings);
    }
}
