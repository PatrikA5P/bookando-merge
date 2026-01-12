<?php
/**
 * MU-Plugin Load Guard Template
 *
 * Use this template to prevent multiple loading of must-use plugins.
 * Copy this code to the top of your MU-plugin file to ensure it only loads once per request.
 *
 * Example usage for wp-content/mu-plugins/bookando-employees-debug.php:
 *
 * 1. Add this code at the very top of your MU-plugin file (after the opening PHP tag)
 * 2. Replace 'BOOKANDO_EMPLOYEES_DEBUG_LOADED' with a unique constant name for your plugin
 * 3. The constant should follow this pattern: PLUGINNAME_LOADED (all uppercase, underscores)
 *
 * @package Bookando
 * @since 1.0.0
 */

// =====================================================
// LOAD GUARD - Prevents multiple loading
// =====================================================

if (defined('YOUR_PLUGIN_CONSTANT_LOADED')) {
    // Already loaded - exit early to prevent duplicate execution
    return;
}

// Mark as loaded
define('YOUR_PLUGIN_CONSTANT_LOADED', true);

// =====================================================
// YOUR PLUGIN CODE STARTS HERE
// =====================================================

// ... rest of your MU-plugin code ...


// =====================================================
// EXAMPLE: bookando-employees-debug.php with Load Guard
// =====================================================

/*
<?php
if (defined('BOOKANDO_EMPLOYEES_DEBUG_LOADED')) {
    return;
}
define('BOOKANDO_EMPLOYEES_DEBUG_LOADED', true);

// Log that this MU-plugin was loaded
error_log('[Bookando MU] bookando-employees-debug.php geladen (' . current_filter() . ')');

// Your debug logic here
add_action('init', function() {
    if (defined('BOOKANDO_DEV') && BOOKANDO_DEV) {
        // Debug code for employees module
    }
});
*/
