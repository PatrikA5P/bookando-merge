<?php
/**
 * Script to activate Workday and Resources modules
 * Run this from command line: php activate-workday-resources.php
 */

// Load WordPress
$wp_load_paths = [
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
    __DIR__ . '/../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("Error: Could not find wp-load.php\nPlease run this script from the plugin directory.\n");
}

if (!defined('BOOKANDO_PLUGIN_DIR')) {
    die("Error: Bookando plugin is not loaded!\n");
}

echo "=== Activating Workday and Resources Modules ===\n\n";

// Get ModuleManager and StateRepository
$manager = \Bookando\Core\Manager\ModuleManager::instance();
$stateRepo = \Bookando\Core\Manager\ModuleStateRepository::instance();

// Check current states
echo "1. Current module states:\n";
$workdayActive = $stateRepo->isActive('workday');
$resourcesActive = $stateRepo->isActive('resources');
echo "   - workday: " . ($workdayActive ? 'ACTIVE' : 'INACTIVE') . "\n";
echo "   - resources: " . ($resourcesActive ? 'ACTIVE' : 'INACTIVE') . "\n\n";

// Activate workday
if (!$workdayActive) {
    echo "2. Activating workday module...\n";
    $result = $stateRepo->activate('workday');
    if ($result) {
        echo "   ✓ workday activated successfully\n";
    } else {
        echo "   ✗ Failed to activate workday\n";
    }
} else {
    echo "2. workday is already active\n";
}

// Activate resources
if (!$resourcesActive) {
    echo "3. Activating resources module...\n";
    $result = $stateRepo->activate('resources');
    if ($result) {
        echo "   ✓ resources activated successfully\n";
    } else {
        echo "   ✗ Failed to activate resources\n";
    }
} else {
    echo "3. resources is already active\n";
}

// Update the bookando_active_modules option
echo "\n4. Updating bookando_active_modules option...\n";
$currentActive = get_option('bookando_active_modules', []);
if (!is_array($currentActive)) {
    $currentActive = [];
}

$updated = false;
if (!in_array('workday', $currentActive, true)) {
    $currentActive[] = 'workday';
    $updated = true;
}
if (!in_array('resources', $currentActive, true)) {
    $currentActive[] = 'resources';
    $updated = true;
}

if ($updated) {
    update_option('bookando_active_modules', $currentActive, false);
    echo "   ✓ Option updated: " . implode(', ', $currentActive) . "\n";
} else {
    echo "   - No update needed\n";
}

// Verify
echo "\n5. Verification:\n";
$workdayActive = $stateRepo->isActive('workday');
$resourcesActive = $stateRepo->isActive('resources');
echo "   - workday: " . ($workdayActive ? 'ACTIVE ✓' : 'INACTIVE ✗') . "\n";
echo "   - resources: " . ($resourcesActive ? 'ACTIVE ✓' : 'INACTIVE ✗') . "\n";

// Check module.json files
echo "\n6. Checking module.json files:\n";
$workdayJson = BOOKANDO_PLUGIN_DIR . '/src/modules/workday/module.json';
$resourcesJson = BOOKANDO_PLUGIN_DIR . '/src/modules/resources/module.json';
echo "   - workday: " . (file_exists($workdayJson) ? 'EXISTS ✓' : 'MISSING ✗') . "\n";
echo "   - resources: " . (file_exists($resourcesJson) ? 'EXISTS ✓' : 'MISSING ✗') . "\n";

// Check assets
echo "\n7. Checking built assets:\n";
$workdayAsset = BOOKANDO_PLUGIN_DIR . '/dist/workday/main.js';
$resourcesAsset = BOOKANDO_PLUGIN_DIR . '/dist/resources/main.js';
echo "   - workday/main.js: " . (file_exists($workdayAsset) ? 'EXISTS (' . filesize($workdayAsset) . ' bytes) ✓' : 'MISSING ✗') . "\n";
echo "   - resources/main.js: " . (file_exists($resourcesAsset) ? 'EXISTS (' . filesize($resourcesAsset) . ' bytes) ✓' : 'MISSING ✗') . "\n";

echo "\n=== Done ===\n";
echo "\nNext steps:\n";
echo "1. Clear WordPress cache if you're using a caching plugin\n";
echo "2. Reload the WordPress admin page\n";
echo "3. The modules should now be visible and functional\n\n";
