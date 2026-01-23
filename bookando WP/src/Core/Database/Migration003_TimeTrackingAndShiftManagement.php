<?php

declare(strict_types=1);

namespace Bookando\Core\Database;

use Bookando\Core\Service\ActivityLogger;

/**
 * Migration 003: Time Tracking & Shift Management Enhancements
 *
 * This migration adds:
 * 1. Break tracking table (time_entry_breaks)
 * 2. Absence types to days_off table
 * 3. Vacation balance tracking (employee_vacation_balances)
 * 4. Shift management tables (shifts, shift_templates, etc.)
 *
 * @package Bookando\Core\Database
 */
class Migration003_TimeTrackingAndShiftManagement
{
    /**
     * Run the migration
     *
     * @return bool Success
     */
    public static function up(): bool
    {
        global $wpdb;

        $prefix = $wpdb->prefix . 'bookando_';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $success = true;
        $errors = [];

        // =========================================================
        // 1. CREATE: Time Entry Breaks Table
        // =========================================================

        $table_name = $prefix . 'time_entry_breaks';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_entry_id BIGINT UNSIGNED NOT NULL,
            break_start_at DATETIME NOT NULL,
            break_end_at DATETIME NULL,
            break_minutes INT UNSIGNED NULL,
            break_type ENUM('paid','unpaid','meal','rest','automatic') DEFAULT 'unpaid',
            is_automatic TINYINT(1) DEFAULT 0,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_break_entry (time_entry_id),
            KEY idx_break_start (break_start_at)
        ) {$charset_collate};";

        dbDelta($sql);

        if (!self::tableExists($table_name)) {
            $errors[] = "Failed to create table: {$table_name}";
            $success = false;
        } else {
            ActivityLogger::info('migration.003', "Created table: {$table_name}");
        }

        // =========================================================
        // 2. ALTER: Add absence type fields to employees_days_off
        // =========================================================

        $table_name = $prefix . 'employees_days_off';

        // Check if columns already exist before adding
        $columns_to_add = [
            'absence_type' => "ENUM('vacation','sick','sick_child','training','unpaid','compensatory','parental','special','public_holiday') NOT NULL DEFAULT 'vacation' AFTER end_date",
            'hours_per_day' => "DECIMAL(4,2) NULL AFTER absence_type",
            'affects_vacation_balance' => "TINYINT(1) DEFAULT 1 AFTER hours_per_day",
            'requires_certificate' => "TINYINT(1) DEFAULT 0 AFTER affects_vacation_balance",
            'certificate_uploaded' => "TINYINT(1) DEFAULT 0 AFTER requires_certificate"
        ];

        foreach ($columns_to_add as $column => $definition) {
            if (!self::columnExists($table_name, $column)) {
                $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN {$column} {$definition}");
                if ($result === false) {
                    $errors[] = "Failed to add column {$column} to {$table_name}";
                    $success = false;
                } else {
                    ActivityLogger::info('migration.003', "Added column {$column} to {$table_name}");
                }
            }
        }

        // =========================================================
        // 3. CREATE: Employee Vacation Balances Table
        // =========================================================

        $table_name = $prefix . 'employee_vacation_balances';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            year YEAR NOT NULL,
            entitled_days DECIMAL(5,2) NOT NULL DEFAULT 25.00,
            carried_over_days DECIMAL(5,2) DEFAULT 0.00,
            taken_days DECIMAL(5,2) DEFAULT 0.00,
            planned_days DECIMAL(5,2) DEFAULT 0.00,
            remaining_days DECIMAL(5,2) GENERATED ALWAYS AS
                (entitled_days + carried_over_days - taken_days - planned_days) STORED,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_year (user_id, year),
            KEY idx_year (year),
            KEY idx_user (user_id)
        ) {$charset_collate};";

        dbDelta($sql);

        if (!self::tableExists($table_name)) {
            $errors[] = "Failed to create table: {$table_name}";
            $success = false;
        } else {
            ActivityLogger::info('migration.003', "Created table: {$table_name}");
        }

        // =========================================================
        // 4. CREATE: Shifts Table
        // =========================================================

        $table_name = $prefix . 'shifts';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            shift_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            break_minutes INT UNSIGNED DEFAULT 0,
            location_id BIGINT UNSIGNED NULL,
            service_id BIGINT UNSIGNED NULL,
            event_period_id BIGINT UNSIGNED NULL,
            shift_type ENUM('regular','on_call','training','event','standby') DEFAULT 'regular',
            status ENUM('draft','published','confirmed','cancelled','completed') DEFAULT 'draft',
            notes TEXT NULL,
            color VARCHAR(7) NULL,
            template_id BIGINT UNSIGNED NULL,
            generated_by VARCHAR(50) NULL,
            recurring_rule JSON NULL,
            published_at DATETIME NULL,
            published_by BIGINT UNSIGNED NULL,
            created_by BIGINT UNSIGNED NULL,
            updated_by BIGINT UNSIGNED NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_shift_user (user_id),
            KEY idx_shift_date (shift_date),
            KEY idx_shift_user_date (user_id, shift_date),
            KEY idx_shift_datetime (shift_date, start_time, end_time),
            KEY idx_shift_status (status),
            KEY idx_shift_location (location_id),
            KEY idx_shift_service (service_id),
            KEY idx_shift_event (event_period_id),
            KEY idx_shift_tenant (tenant_id),
            KEY idx_shift_template (template_id)
        ) {$charset_collate};";

        dbDelta($sql);

        if (!self::tableExists($table_name)) {
            $errors[] = "Failed to create table: {$table_name}";
            $success = false;
        } else {
            ActivityLogger::info('migration.003', "Created table: {$table_name}");
        }

        // =========================================================
        // 5. CREATE: Shift Templates Table
        // =========================================================

        $table_name = $prefix . 'shift_templates';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            break_minutes INT UNSIGNED DEFAULT 0,
            default_location_id BIGINT UNSIGNED NULL,
            default_service_id BIGINT UNSIGNED NULL,
            shift_type ENUM('regular','on_call','training','event','standby') DEFAULT 'regular',
            color VARCHAR(7) NULL,
            required_role VARCHAR(64) NULL,
            meta JSON NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_template_tenant (tenant_id)
        ) {$charset_collate};";

        dbDelta($sql);

        if (!self::tableExists($table_name)) {
            $errors[] = "Failed to create table: {$table_name}";
            $success = false;
        } else {
            ActivityLogger::info('migration.003', "Created table: {$table_name}");
        }

        // =========================================================
        // 6. CREATE: Employee Shift Preferences Table
        // =========================================================

        $table_name = $prefix . 'employee_shift_preferences';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            max_hours_per_day DECIMAL(4,2) DEFAULT 8.00,
            max_hours_per_week DECIMAL(5,2) DEFAULT 40.00,
            preferred_work_days_per_week INT DEFAULT 5,
            preferred_shift_types JSON NULL,
            preferred_days JSON NULL,
            blocked_days JSON NULL,
            preferred_time_ranges JSON NULL,
            max_consecutive_work_days INT DEFAULT 6,
            min_hours_between_shifts DECIMAL(4,2) DEFAULT 11.00,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_prefs (user_id)
        ) {$charset_collate};";

        dbDelta($sql);

        if (!self::tableExists($table_name)) {
            $errors[] = "Failed to create table: {$table_name}";
            $success = false;
        } else {
            ActivityLogger::info('migration.003', "Created table: {$table_name}");
        }

        // =========================================================
        // 7. CREATE: Shift Requirements Table
        // =========================================================

        $table_name = $prefix . 'shift_requirements';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NULL,
            day_of_week TINYINT UNSIGNED NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            required_employees INT DEFAULT 1,
            required_role VARCHAR(64) NULL,
            location_id BIGINT UNSIGNED NULL,
            service_id BIGINT UNSIGNED NULL,
            priority INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_req_tenant (tenant_id),
            KEY idx_req_dow (day_of_week),
            KEY idx_req_dates (start_date, end_date),
            KEY idx_req_time (start_time, end_time),
            KEY idx_req_active (is_active)
        ) {$charset_collate};";

        dbDelta($sql);

        if (!self::tableExists($table_name)) {
            $errors[] = "Failed to create table: {$table_name}";
            $success = false;
        } else {
            ActivityLogger::info('migration.003', "Created table: {$table_name}");
        }

        // =========================================================
        // 8. CREATE: Overtime Balances Table
        // =========================================================

        $table_name = $prefix . 'overtime_balances';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            overtime_minutes INT NOT NULL DEFAULT 0,
            compensated_minutes INT NOT NULL DEFAULT 0,
            balance_minutes INT GENERATED ALWAYS AS
                (overtime_minutes - compensated_minutes) STORED,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_user_period (user_id, period_start, period_end),
            KEY idx_user (user_id)
        ) {$charset_collate};";

        dbDelta($sql);

        if (!self::tableExists($table_name)) {
            $errors[] = "Failed to create table: {$table_name}";
            $success = false;
        } else {
            ActivityLogger::info('migration.003', "Created table: {$table_name}");
        }

        // =========================================================
        // Log results
        // =========================================================

        if ($success) {
            ActivityLogger::info('migration.003', 'Migration 003 completed successfully', [
                'tables_created' => 7,
                'columns_added' => 5,
            ]);
        } else {
            ActivityLogger::error('migration.003', 'Migration 003 completed with errors', [
                'errors' => $errors,
            ]);
        }

        return $success;
    }

    /**
     * Rollback the migration
     *
     * @return bool Success
     */
    public static function down(): bool
    {
        global $wpdb;

        $prefix = $wpdb->prefix . 'bookando_';
        $success = true;

        // Drop created tables
        $tables_to_drop = [
            'time_entry_breaks',
            'employee_vacation_balances',
            'shifts',
            'shift_templates',
            'employee_shift_preferences',
            'shift_requirements',
            'overtime_balances',
        ];

        foreach ($tables_to_drop as $table) {
            $table_name = $prefix . $table;
            $result = $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

            if ($result === false) {
                $success = false;
                ActivityLogger::error('migration.003_rollback', "Failed to drop table: {$table_name}");
            } else {
                ActivityLogger::info('migration.003_rollback', "Dropped table: {$table_name}");
            }
        }

        // Remove added columns from employees_days_off
        $table_name = $prefix . 'employees_days_off';
        $columns_to_remove = [
            'absence_type',
            'hours_per_day',
            'affects_vacation_balance',
            'requires_certificate',
            'certificate_uploaded',
        ];

        foreach ($columns_to_remove as $column) {
            if (self::columnExists($table_name, $column)) {
                $result = $wpdb->query("ALTER TABLE {$table_name} DROP COLUMN {$column}");

                if ($result === false) {
                    $success = false;
                    ActivityLogger::error('migration.003_rollback', "Failed to drop column {$column} from {$table_name}");
                } else {
                    ActivityLogger::info('migration.003_rollback', "Dropped column {$column} from {$table_name}");
                }
            }
        }

        if ($success) {
            ActivityLogger::info('migration.003_rollback', 'Migration 003 rolled back successfully');
        }

        return $success;
    }

    /**
     * Check if a table exists
     *
     * @param string $table_name Full table name with prefix
     * @return bool
     */
    private static function tableExists(string $table_name): bool
    {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));

        return $result === $table_name;
    }

    /**
     * Check if a column exists in a table
     *
     * @param string $table_name Full table name with prefix
     * @param string $column_name Column name
     * @return bool
     */
    private static function columnExists(string $table_name, string $column_name): bool
    {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            "SHOW COLUMNS FROM {$table_name} LIKE %s",
            $column_name
        ));

        return $result === $column_name;
    }
}
