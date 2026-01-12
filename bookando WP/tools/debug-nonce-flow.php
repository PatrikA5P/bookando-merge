<?php
/**
 * Debug-Tool für Nonce-Redirect-Problem
 *
 * Verwendung: Diesen Code in wp-config.php einfügen (temporär):
 * define('BOOKANDO_DEBUG_NONCE', true);
 */

if (!defined('BOOKANDO_DEBUG_NONCE') || !BOOKANDO_DEBUG_NONCE) {
    return;
}

add_action('admin_init', function() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'bookando') === false) {
        return;
    }

    error_log('=== BOOKANDO NONCE DEBUG ===');
    error_log('Screen ID: ' . $screen->id);
    error_log('Current URL: ' . $_SERVER['REQUEST_URI']);
    error_log('_wpnonce in REQUEST: ' . (isset($_REQUEST['_wpnonce']) ? 'YES' : 'NO'));

    if (isset($_REQUEST['_wpnonce'])) {
        $raw_nonce = $_REQUEST['_wpnonce'];
        error_log('Raw nonce value: ' . $raw_nonce);
        error_log('Raw nonce type: ' . gettype($raw_nonce));

        // Test mit sanitize_text_field (alt, falsch)
        $sanitized = sanitize_text_field($raw_nonce);
        error_log('Sanitized nonce: ' . $sanitized);
        error_log('Nonces match: ' . ($raw_nonce === $sanitized ? 'YES' : 'NO'));

        // Test mit nur wp_unslash (neu, korrekt)
        $unslashed = wp_unslash($raw_nonce);
        error_log('Unslashed nonce: ' . $unslashed);
        error_log('Raw === Unslashed: ' . ($raw_nonce === $unslashed ? 'YES' : 'NO'));

        // Teste verschiedene Nonce-Actions
        $test_actions = [
            'bookando_module_assets_settings',
            'bookando_module_assets_customers',
            'bookando_module_assets_employees',
            'bookando_module_assets_offers',
            'bookando_module_assets_appointments',
        ];

        foreach ($test_actions as $action) {
            $verify_raw = wp_verify_nonce($raw_nonce, $action);
            $verify_sanitized = wp_verify_nonce($sanitized, $action);
            $verify_unslashed = wp_verify_nonce($unslashed, $action);

            error_log("Action: $action");
            error_log("  - Raw verify: " . ($verify_raw ? 'VALID' : 'INVALID'));
            error_log("  - Sanitized verify: " . ($verify_sanitized ? 'VALID' : 'INVALID'));
            error_log("  - Unslashed verify: " . ($verify_unslashed ? 'VALID' : 'INVALID'));
        }
    }

    error_log('=== END NONCE DEBUG ===');
}, 1);
