#!/usr/bin/env php
<?php
/**
 * Direct migration runner
 * Run with: php migrate.php
 */

echo "\n🔧 Bookando Migration 003: Time Tracking & Shift Management\n";
echo "=============================================================\n\n";

// Load WordPress
$wp_load_paths = [
    __DIR__ . '/../../../wp-load.php',  // Standard plugin location
    __DIR__ . '/../../wp-load.php',
    __DIR__ . '/../wp-load.php',
    __DIR__ . '/wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        echo "✅ WordPress loaded from: $path\n\n";
        break;
    }
}

if (!$wp_loaded) {
    echo "❌ ERROR: Could not find wp-load.php\n";
    echo "Please run this script from the plugin directory or specify the WordPress root.\n\n";
    exit(1);
}

// Load the migration class
require_once __DIR__ . '/src/Core/Database/Migration003_TimeTrackingAndShiftManagement.php';

echo "Running migration...\n";

try {
    $result = \Bookando\Core\Database\Migration003_TimeTrackingAndShiftManagement::up();

    if ($result) {
        echo "\n✅ Migration completed successfully!\n\n";

        echo "Created tables:\n";
        echo "  • time_entry_breaks\n";
        echo "  • employee_vacation_balances\n";
        echo "  • shifts\n";
        echo "  • shift_templates\n";
        echo "  • employee_shift_preferences\n";
        echo "  • shift_requirements\n";
        echo "  • overtime_balances\n\n";

        echo "Extended table:\n";
        echo "  • employees_days_off (added 5 columns)\n\n";

        // Update migration tracker
        update_option('bookando_migration_003_executed', time());
        update_option('bookando_migration_003_version', '1.0.0');

        echo "✅ Migration tracker updated\n\n";
        echo "Next steps:\n";
        echo "1. Clear WordPress cache (if using cache plugin)\n";
        echo "2. Clear browser cache (Ctrl+Shift+R)\n";
        echo "3. Reload the Workday module in your browser\n\n";
    } else {
        echo "\n❌ Migration failed!\n";
        echo "Check your database error logs for details.\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "\n❌ Migration failed with error:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "Done! ✨\n\n";
