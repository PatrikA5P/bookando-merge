<?php
/**
 * Bookando Module Activation Script
 *
 * This script activates all modules and runs their installers to create dummy data.
 * Run this file once through WordPress admin or command line.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to find WordPress
    $wp_load_paths = [
        __DIR__ . '/../../../../wp-load.php',
        __DIR__ . '/../../../wp-load.php',
        __DIR__ . '/../../wp-load.php',
        __DIR__ . '/../wp-load.php',
    ];

    $loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            define('WP_USE_THEMES', false);
            require_once $path;
            $loaded = true;
            break;
        }
    }

    if (!$loaded) {
        die('Error: Could not find WordPress. Please run this file from WordPress admin or adjust paths.');
    }
}

// Load Bookando
require_once __DIR__ . '/vendor/autoload.php';

echo "========================================\n";
echo "Bookando Module Activation Script\n";
echo "========================================\n\n";

// Step 1: Run core installer
echo "Step 1: Running core installer...\n";
try {
    \Bookando\Core\Installer::run();
    echo "✓ Core installer completed successfully\n\n";
} catch (\Throwable $e) {
    echo "✗ Core installer failed: " . $e->getMessage() . "\n\n";
}

// Step 2: Check active modules
echo "Step 2: Checking active modules...\n";
$active = get_option('bookando_active_modules', []);
echo "Currently active modules: " . (empty($active) ? 'NONE' : implode(', ', $active)) . "\n\n";

// Step 3: Scan and display all available modules
echo "Step 3: Scanning for available modules...\n";
$modulesPath = __DIR__ . '/src/modules/';
$availableModules = [];

foreach (glob($modulesPath . '*/module.json') as $jsonPath) {
    $folder = basename(dirname($jsonPath));
    $meta = json_decode(file_get_contents($jsonPath), true) ?? [];
    $slug = $meta['slug'] ?? $folder;

    // Skip legacy modules
    if (str_ends_with(strtolower($slug), '_old')) {
        continue;
    }

    $availableModules[] = [
        'slug' => $slug,
        'name' => $meta['name']['en'] ?? $meta['name']['default'] ?? $slug,
        'folder' => $folder
    ];

    echo "  - Found: {$slug} ({$folder})\n";
}

echo "\nTotal available modules: " . count($availableModules) . "\n\n";

// Step 4: Activate all modules
echo "Step 4: Activating all modules...\n";
$repository = \Bookando\Core\Manager\ModuleStateRepository::instance();
$activatedCount = 0;

foreach ($availableModules as $module) {
    $slug = $module['slug'];

    // Activate module
    $repository->activate($slug);
    echo "  ✓ Activated: {$slug}\n";
    $activatedCount++;
}

// Update option
update_option('bookando_active_modules', array_column($availableModules, 'slug'), false);

echo "\nActivated {$activatedCount} modules\n\n";

// Step 5: Verify activation
echo "Step 5: Verifying activation...\n";
$activeAfter = get_option('bookando_active_modules', []);
echo "Active modules now: " . implode(', ', $activeAfter) . "\n\n";

// Step 6: Final instructions
echo "========================================\n";
echo "✓ Activation Complete!\n";
echo "========================================\n\n";
echo "Next steps:\n";
echo "1. Refresh your WordPress admin page\n";
echo "2. Navigate to Bookando modules in the menu\n";
echo "3. All modules should now be visible with dummy data\n\n";
echo "If you still don't see data:\n";
echo "- Deactivate and reactivate the Bookando plugin in WordPress\n";
echo "- Check the browser console for JavaScript errors\n";
echo "- Check PHP error logs for backend errors\n\n";
