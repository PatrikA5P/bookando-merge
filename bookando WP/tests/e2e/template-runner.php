<?php

declare(strict_types=1);

$scenario = $argv[1] ?? 'plugin';
$baseDir = __DIR__;
$themeDir = $baseDir . '/fixtures/theme/' . $scenario;

if (!is_dir($themeDir)) {
    fwrite(STDERR, "Unknown scenario: {$scenario}\n");
    exit(1);
}

$GLOBALS['bookando_e2e_theme_dir'] = $themeDir;

if (!defined('BOOKANDO_PLUGIN_DIR')) {
    define('BOOKANDO_PLUGIN_DIR', $baseDir . '/fixtures/plugin/');
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $string): string
    {
        return rtrim($string, "\\/") . '/';
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory(): string
    {
        return $GLOBALS['bookando_e2e_theme_dir'];
    }
}

require_once dirname(__DIR__, 2) . '/src/Core/Helpers.php';

ob_start();
bookando_get_template('example', 'dashboard');
$output = ob_get_clean() ?: '';

echo $output;
