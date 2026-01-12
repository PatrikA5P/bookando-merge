<?php
/**
 * Test-Skript um die Redirect-Logik zu simulieren
 *
 * Führe aus: php tools/test-redirect-logic.php
 */

// Simuliere WordPress-Funktionen
function sanitize_key($key) {
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', $key));
}

function sanitize_text_field($str) {
    // Simuliere WordPress sanitize_text_field
    $filtered = wp_check_invalid_utf8( $str );
    $filtered = trim( $filtered );
    // Entferne HTML-Tags, Zeilenumbrüche etc.
    $filtered = strip_tags( $filtered );
    return $filtered;
}

function wp_check_invalid_utf8($string) {
    return $string;
}

function strip_tags($str) {
    return preg_replace('/<[^>]*>/', '', $str);
}

// Test verschiedene Nonce-Werte
$test_nonces = [
    'abc123def456',           // Normaler Nonce
    'abc-123_def.456',        // Mit Sonderzeichen
    'abc 123 def 456',        // Mit Leerzeichen
    'äöü123',                 // Mit Umlauten
    'abc<script>def',         // Mit HTML
];

echo "=== NONCE SANITIZATION TEST ===\n\n";

foreach ($test_nonces as $nonce) {
    echo "Original: '$nonce'\n";
    echo "sanitize_text_field: '" . sanitize_text_field($nonce) . "'\n";
    echo "Match: " . ($nonce === sanitize_text_field($nonce) ? 'YES' : 'NO') . "\n\n";
}

echo "\n=== MODULE SLUG SANITIZATION TEST ===\n\n";

$module_slugs = ['settings', 'customers', 'employees', 'offers', 'appointments', 'academy', 'finance', 'resources'];

foreach ($module_slugs as $slug) {
    $sanitized = sanitize_key($slug);
    echo "Original: '$slug' -> Sanitized: '$sanitized' -> Match: " . ($slug === $sanitized ? 'YES' : 'NO') . "\n";
}

echo "\n=== REDIRECT LOGIC SIMULATION ===\n\n";

// Simuliere die Logik aus Menu.php::ensureModuleNonce
function simulateRedirectLogic($moduleSlug, $hasNonce, $nonceValue) {
    // Zeile 95 in Menu.php
    $moduleSlug = sanitize_key($moduleSlug);

    // Zeile 107: Action erstellen
    $action = "bookando_module_assets_{$moduleSlug}";

    echo "Module Slug (sanitized): $moduleSlug\n";
    echo "Action: $action\n";
    echo "Has Nonce: " . ($hasNonce ? 'YES' : 'NO') . "\n";

    if ($hasNonce) {
        echo "Nonce Value (raw): $nonceValue\n";
        echo "Would verify against action: $action\n";
    } else {
        echo "REDIRECT: Would redirect and add nonce for action $action\n";
    }

    echo "\n";
}

simulateRedirectLogic('settings', false, '');
simulateRedirectLogic('settings', true, 'abc123def456');
