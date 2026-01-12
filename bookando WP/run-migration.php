<?php
/**
 * Temporary script to run Migration 003
 * Run with: wp eval-file run-migration.php
 */

require_once __DIR__ . '/bookando.php';

echo "Running Migration 003: Time Tracking & Shift Management...\n\n";

// Run the migration
$result = \Bookando\Core\Database\Migration003_TimeTrackingAndShiftManagement::up();

if ($result) {
    echo "✅ Migration 003 completed successfully!\n\n";

    echo "Created tables:\n";
    echo "- time_entry_breaks\n";
    echo "- employee_vacation_balances\n";
    echo "- shifts\n";
    echo "- shift_templates\n";
    echo "- employee_shift_preferences\n";
    echo "- shift_requirements\n";
    echo "- overtime_balances\n\n";

    echo "Extended table:\n";
    echo "- employees_days_off (added 5 columns)\n\n";

    // Update the migration tracker
    update_option('bookando_migration_003_executed', time());
    echo "✅ Migration tracker updated\n";
} else {
    echo "❌ Migration 003 failed!\n";
    echo "Check your database error logs.\n";
}

echo "\nDone.\n";
