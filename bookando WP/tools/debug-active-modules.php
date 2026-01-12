<?php
declare(strict_types=1);

if (!defined('WP_CLI')) {
    fwrite(STDERR, "This helper can only be executed via WP-CLI.\n");
    exit(1);
}

$pluginRoot = dirname(__DIR__);
$modulesDir = $pluginRoot . '/src/modules';

$mods = get_option('bookando_active_modules', []);

WP_CLI::line('Aktive Module (bookando_active_modules):');
WP_CLI::print_value($mods);

WP_CLI::line('');
WP_CLI::line('Module-Ordner:');
if (is_dir($modulesDir)) {
    $entries = array_values(array_diff(scandir($modulesDir) ?: [], ['.', '..']));
    WP_CLI::print_value($entries);
} else {
    WP_CLI::warning("Modul-Verzeichnis nicht gefunden: {$modulesDir}");
}

$checks = [
    'customers/Module.php' => file_exists($modulesDir . '/customers/Module.php'),
    'customers/admin/Admin.php' => file_exists($modulesDir . '/customers/admin/Admin.php'),
    'Composer Autoload' => class_exists('Bookando\\Modules\\customers\\Module'),
    'Admin Class' => class_exists('Bookando\\Modules\\customers\\admin\\Admin'),
];

WP_CLI::line('');
foreach ($checks as $label => $ok) {
    WP_CLI::line(sprintf('%s: %s', $label, $ok ? 'JA' : 'NEIN'));
}
