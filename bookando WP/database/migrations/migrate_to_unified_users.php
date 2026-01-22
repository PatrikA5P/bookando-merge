<?php
/**
 * Migrate to Unified Users Table
 *
 * This script migrates data from separate tables (wp_bookando_customers, wp_bookando_employees)
 * into the unified wp_bookando_users table.
 *
 * Usage:
 *   php migrate_to_unified_users.php
 *
 * Or via WP-CLI:
 *   wp eval-file database/migrations/migrate_to_unified_users.php
 *
 * @package Bookando
 * @version 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || die('Direct access not allowed');

global $wpdb;

// Table names
$table_users = $wpdb->prefix . 'bookando_users';
$table_customers_old = $wpdb->prefix . 'bookando_customers';
$table_employees_old = $wpdb->prefix . 'bookando_employees';

// ============================================================================
// STEP 1: Create new unified table
// ============================================================================
echo "Step 1: Creating unified users table...\n";

$sql = file_get_contents(__DIR__ . '/../schemas/wp_bookando_users.sql');
$wpdb->query($sql);

echo "✓ Table created\n\n";

// ============================================================================
// STEP 2: Migrate Customers
// ============================================================================
echo "Step 2: Migrating customers...\n";

// Check if old customers table exists
$customers_exist = $wpdb->get_var("SHOW TABLES LIKE '$table_customers_old'");

if ($customers_exist) {
    $customers = $wpdb->get_results("SELECT * FROM $table_customers_old");
    $customer_count = 0;

    foreach ($customers as $customer) {
        // Check if user already exists (by email)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_users WHERE email = %s AND tenant_id = %d",
            $customer->email,
            $customer->tenant_id ?? 1
        ));

        if ($existing) {
            // User exists - add 'customer' role if not already present
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_users
                SET roles = JSON_MERGE_PRESERVE(roles, JSON_ARRAY('customer'))
                WHERE id = %d",
                $existing
            ));
            echo "  → Updated existing user ID $existing with customer role\n";
        } else {
            // Insert new customer
            $wpdb->insert($table_users, [
                'tenant_id' => $customer->tenant_id ?? 1,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'gender' => $customer->gender,
                'birthday' => $customer->birthday,
                'street' => $customer->street ?? $customer->address ?? null,
                'address_line_2' => $customer->address_line_2 ?? null,
                'zip' => $customer->zip,
                'city' => $customer->city,
                'country' => $customer->country,
                'roles' => '["customer"]',
                'status' => $customer->status ?? 'active',
                'customer_notes' => $customer->notes ?? null,
                'custom_fields' => $customer->custom_fields ?? null,
                'created_at' => $customer->created_at ?? current_time('mysql'),
                'updated_at' => $customer->updated_at ?? current_time('mysql'),
            ]);
            $customer_count++;
        }
    }

    echo "✓ Migrated $customer_count customers\n\n";
} else {
    echo "  ⊘ Old customers table not found - skipping\n\n";
}

// ============================================================================
// STEP 3: Migrate Employees
// ============================================================================
echo "Step 3: Migrating employees...\n";

// Check if old employees table exists
$employees_exist = $wpdb->get_var("SHOW TABLES LIKE '$table_employees_old'");

if ($employees_exist) {
    $employees = $wpdb->get_results("SELECT * FROM $table_employees_old");
    $employee_count = 0;

    foreach ($employees as $employee) {
        // Check if user already exists (by email)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_users WHERE email = %s AND tenant_id = %d",
            $employee->email,
            $employee->tenant_id ?? 1
        ));

        if ($existing) {
            // User exists (probably migrated as customer) - merge employee data
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_users
                SET
                    roles = JSON_MERGE_PRESERVE(roles, JSON_ARRAY('employee')),
                    position = %s,
                    department = %s,
                    hire_date = %s,
                    exit_date = %s,
                    badge_id = %s,
                    hub_password = %s,
                    employee_description = %s,
                    assigned_services = %s,
                    avatar_url = %s
                WHERE id = %d",
                $employee->position,
                $employee->department,
                $employee->hire_date,
                $employee->exit_date,
                $employee->badge_id ?? $employee->badge ?? null,
                $employee->hub_password,
                $employee->description,
                $employee->assigned_services,
                $employee->avatar ?? $employee->avatar_url ?? null,
                $existing
            ));
            echo "  → Updated existing user ID $existing with employee role and data\n";
        } else {
            // Insert new employee
            $wpdb->insert($table_users, [
                'tenant_id' => $employee->tenant_id ?? 1,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'gender' => $employee->gender,
                'birthday' => $employee->birthday,
                'street' => $employee->street ?? $employee->address ?? null,
                'address_line_2' => $employee->address_line_2 ?? null,
                'zip' => $employee->zip,
                'city' => $employee->city,
                'country' => $employee->country,
                'roles' => '["employee"]',
                'status' => $employee->status ?? 'active',
                'position' => $employee->position,
                'department' => $employee->department,
                'hire_date' => $employee->hire_date,
                'exit_date' => $employee->exit_date,
                'badge_id' => $employee->badge_id ?? $employee->badge ?? null,
                'hub_password' => $employee->hub_password,
                'employee_description' => $employee->description,
                'assigned_services' => $employee->assigned_services,
                'avatar_url' => $employee->avatar ?? $employee->avatar_url ?? null,
                'customer_notes' => $employee->notes ?? null,
                'created_at' => $employee->created_at ?? current_time('mysql'),
                'updated_at' => $employee->updated_at ?? current_time('mysql'),
            ]);
            $employee_count++;
        }
    }

    echo "✓ Migrated $employee_count employees\n\n";
} else {
    echo "  ⊘ Old employees table not found - skipping\n\n";
}

// ============================================================================
// STEP 4: Verify Migration
// ============================================================================
echo "Step 4: Verifying migration...\n";

$total_users = $wpdb->get_var("SELECT COUNT(*) FROM $table_users");
$total_customers = $wpdb->get_var("SELECT COUNT(*) FROM $table_users WHERE JSON_CONTAINS(roles, '\"customer\"')");
$total_employees = $wpdb->get_var("SELECT COUNT(*) FROM $table_users WHERE JSON_CONTAINS(roles, '\"employee\"')");
$dual_roles = $wpdb->get_var("SELECT COUNT(*) FROM $table_users WHERE JSON_CONTAINS(roles, '\"customer\"') AND JSON_CONTAINS(roles, '\"employee\"')");

echo "  Total users:       $total_users\n";
echo "  Customers:         $total_customers\n";
echo "  Employees:         $total_employees\n";
echo "  Dual roles:        $dual_roles\n\n";

// ============================================================================
// STEP 5: Backup & Drop Old Tables (COMMENTED OUT FOR SAFETY)
// ============================================================================
echo "Step 5: Old tables cleanup...\n";
echo "  ⚠ Old tables NOT dropped automatically for safety\n";
echo "  ⚠ Please verify data integrity first!\n";
echo "  ⚠ To drop old tables manually, run:\n";
echo "     DROP TABLE IF EXISTS $table_customers_old;\n";
echo "     DROP TABLE IF EXISTS $table_employees_old;\n\n";

// Uncomment these lines ONLY after verifying data integrity:
// $wpdb->query("DROP TABLE IF EXISTS $table_customers_old");
// $wpdb->query("DROP TABLE IF EXISTS $table_employees_old");

echo "✓ Migration completed successfully!\n";
echo "✓ Please update REST API endpoints to use new table\n";
