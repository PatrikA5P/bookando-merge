<?php
/**
 * UMFASSENDE BOOKANDO MODUL-DIAGNOSE
 *
 * Dieses Script analysiert ALLE Aspekte des Modul-Ladens und zeigt genau,
 * wo das Problem liegt.
 *
 * VERWENDUNG:
 * 1. Kopieren Sie dieses Script nach: wp-content/mu-plugins/bookando-full-diagnosis.php
 * 2. Rufen Sie die WordPress-Admin-Seite auf
 * 3. Prüfen Sie wp-content/debug.log
 */

add_action('init', function() {
    if (!defined('BOOKANDO_PLUGIN_DIR')) {
        error_log('❌ BOOKANDO_PLUGIN_DIR ist nicht definiert - Plugin nicht geladen!');
        return;
    }

    error_log('═══════════════════════════════════════════════════════════');
    error_log('🔍 BOOKANDO MODUL-DIAGNOSE START: ' . date('Y-m-d H:i:s'));
    error_log('═══════════════════════════════════════════════════════════');

    // 1. PRÜFE DATENBANK-STATUS
    error_log('');
    error_log('📊 SCHRITT 1: DATENBANK-STATUS');
    error_log('───────────────────────────────────────────────────────────');

    global $wpdb;
    $table = $wpdb->prefix . 'bookando_module_states';

    $modules = $wpdb->get_results(
        "SELECT slug, status, installed_at, activated_at FROM {$table} ORDER BY slug",
        ARRAY_A
    );

    if (empty($modules)) {
        error_log('❌ Keine Module in Datenbank gefunden!');
    } else {
        error_log('✅ Module in Datenbank:');
        foreach ($modules as $mod) {
            $icon = $mod['status'] === 'active' ? '✓' : '✗';
            error_log("   {$icon} {$mod['slug']}: {$mod['status']} (aktiviert: {$mod['activated_at']})");
        }
    }

    // 2. PRÜFE WORDPRESS-OPTION
    error_log('');
    error_log('📝 SCHRITT 2: WORDPRESS-OPTION');
    error_log('───────────────────────────────────────────────────────────');

    $activeModules = get_option('bookando_active_modules', []);
    if (!is_array($activeModules)) {
        error_log('❌ bookando_active_modules ist kein Array!');
        $activeModules = [];
    } else {
        error_log('✅ bookando_active_modules: ' . implode(', ', $activeModules));
        error_log('   - workday enthalten: ' . (in_array('workday', $activeModules) ? 'JA' : 'NEIN'));
        error_log('   - resources enthalten: ' . (in_array('resources', $activeModules) ? 'JA' : 'NEIN'));
    }

    // 3. PRÜFE MODUL-DATEIEN
    error_log('');
    error_log('📁 SCHRITT 3: MODUL-DATEIEN');
    error_log('───────────────────────────────────────────────────────────');

    $workdayJson = BOOKANDO_PLUGIN_DIR . '/src/modules/workday/module.json';
    $workdayModule = BOOKANDO_PLUGIN_DIR . '/src/modules/workday/Module.php';
    $workdayAdmin = BOOKANDO_PLUGIN_DIR . '/src/modules/workday/Admin/Admin.php';
    $workdayAsset = BOOKANDO_PLUGIN_DIR . '/dist/workday/main.js';

    error_log('Workday-Dateien:');
    error_log('   module.json: ' . (file_exists($workdayJson) ? '✓ EXISTS' : '✗ MISSING'));
    error_log('   Module.php: ' . (file_exists($workdayModule) ? '✓ EXISTS' : '✗ MISSING'));
    error_log('   Admin.php: ' . (file_exists($workdayAdmin) ? '✓ EXISTS' : '✗ MISSING'));
    error_log('   main.js: ' . (file_exists($workdayAsset) ? '✓ EXISTS (' . filesize($workdayAsset) . ' bytes)' : '✗ MISSING'));

    if (file_exists($workdayJson)) {
        $json = json_decode(file_get_contents($workdayJson), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            error_log('   ✓ module.json ist gültiges JSON');
            error_log('   - visible: ' . ($json['visible'] ?? 'undefined'));
            error_log('   - menu_icon: ' . ($json['menu_icon'] ?? 'undefined'));
            error_log('   - menu_position: ' . ($json['menu_position'] ?? 'undefined'));
        } else {
            error_log('   ✗ module.json ist KEIN gültiges JSON: ' . json_last_error_msg());
        }
    }

    $resourcesJson = BOOKANDO_PLUGIN_DIR . '/src/modules/resources/module.json';
    $resourcesModule = BOOKANDO_PLUGIN_DIR . '/src/modules/resources/Module.php';
    $resourcesAdmin = BOOKANDO_PLUGIN_DIR . '/src/modules/resources/Admin/Admin.php';
    $resourcesAsset = BOOKANDO_PLUGIN_DIR . '/dist/resources/main.js';

    error_log('');
    error_log('Resources-Dateien:');
    error_log('   module.json: ' . (file_exists($resourcesJson) ? '✓ EXISTS' : '✗ MISSING'));
    error_log('   Module.php: ' . (file_exists($resourcesModule) ? '✓ EXISTS' : '✗ MISSING'));
    error_log('   Admin.php: ' . (file_exists($resourcesAdmin) ? '✓ EXISTS' : '✗ MISSING'));
    error_log('   main.js: ' . (file_exists($resourcesAsset) ? '✓ EXISTS (' . filesize($resourcesAsset) . ' bytes)' : '✗ MISSING'));

    // 4. PRÜFE MODULEMANAGER
    error_log('');
    error_log('🔧 SCHRITT 4: MODULEMANAGER');
    error_log('───────────────────────────────────────────────────────────');

    if (!class_exists('Bookando\Core\Manager\ModuleManager')) {
        error_log('❌ ModuleManager Klasse existiert nicht!');
        return;
    }

    try {
        $manager = \Bookando\Core\Manager\ModuleManager::instance();
        error_log('✅ ModuleManager Instanz erhalten');

        // Gescannte Module
        $scanned = $manager->scanModules();
        error_log('');
        error_log('Gescannte Module (' . count($scanned) . '):');
        error_log('   ' . implode(', ', $scanned));
        error_log('   - workday gefunden: ' . (in_array('workday', $scanned) ? 'JA' : 'NEIN'));
        error_log('   - resources gefunden: ' . (in_array('resources', $scanned) ? 'JA' : 'NEIN'));

        // Geladene Module
        $loaded = $manager->getAllModules();
        error_log('');
        error_log('Geladene Module (' . count($loaded) . '):');
        if (empty($loaded)) {
            error_log('   ❌ KEINE Module geladen!');
        } else {
            foreach ($loaded as $slug => $instance) {
                error_log('   ✓ ' . $slug . ' (' . get_class($instance) . ')');
            }
        }
        error_log('   - workday geladen: ' . (isset($loaded['workday']) ? 'JA' : 'NEIN'));
        error_log('   - resources geladen: ' . (isset($loaded['resources']) ? 'JA' : 'NEIN'));

        // Sichtbare Module
        $visible = $manager->getVisibleModules();
        error_log('');
        error_log('Sichtbare Module (' . count($visible) . '):');
        foreach ($visible as $mod) {
            error_log('   - ' . $mod['slug'] . ' (visible: ' . ($mod['visible'] ? 'true' : 'false') . ')');
        }

    } catch (\Throwable $e) {
        error_log('❌ FEHLER beim ModuleManager: ' . $e->getMessage());
        error_log('   Trace: ' . $e->getTraceAsString());
    }

    // 5. PRÜFE LIZENZ-MANAGER
    error_log('');
    error_log('🔐 SCHRITT 5: LIZENZ-MANAGER');
    error_log('───────────────────────────────────────────────────────────');

    if (class_exists('Bookando\Core\Licensing\LicenseManager')) {
        error_log('✅ LicenseManager verfügbar');
        error_log('   workday allowed: ' . (\Bookando\Core\Licensing\LicenseManager::isModuleAllowed('workday') ? 'JA' : 'NEIN'));
        error_log('   resources allowed: ' . (\Bookando\Core\Licensing\LicenseManager::isModuleAllowed('resources') ? 'JA' : 'NEIN'));

        // Features prüfen
        $workdayFeatures = ['workday_management', 'rest_api_read', 'rest_api_write'];
        foreach ($workdayFeatures as $feature) {
            $enabled = \Bookando\Core\Licensing\LicenseManager::isFeatureEnabled($feature);
            error_log('   Feature "' . $feature . '": ' . ($enabled ? 'ENABLED' : 'DISABLED'));
        }
    } else {
        error_log('❌ LicenseManager nicht verfügbar');
    }

    // 6. PRÜFE MENÜ-REGISTRIERUNG
    error_log('');
    error_log('📋 SCHRITT 6: ADMIN-MENÜ');
    error_log('───────────────────────────────────────────────────────────');

    global $menu, $submenu;

    $bookandoMenuFound = false;
    $workdayMenuFound = false;
    $resourcesMenuFound = false;

    if (isset($menu)) {
        foreach ($menu as $item) {
            if (isset($item[2]) && strpos($item[2], 'bookando') !== false) {
                $bookandoMenuFound = true;
                error_log('✓ Bookando Hauptmenü gefunden: ' . $item[0] . ' (' . $item[2] . ')');
            }
        }
    }

    if (isset($submenu['bookando'])) {
        error_log('');
        error_log('Bookando Submenüs:');
        foreach ($submenu['bookando'] as $item) {
            error_log('   - ' . $item[0] . ' (slug: ' . $item[2] . ')');
            if (strpos($item[2], 'workday') !== false) $workdayMenuFound = true;
            if (strpos($item[2], 'resources') !== false) $resourcesMenuFound = true;
        }
    } else {
        error_log('❌ Keine Bookando Submenüs gefunden!');
    }

    error_log('');
    error_log('Menü-Status:');
    error_log('   Bookando Hauptmenü: ' . ($bookandoMenuFound ? '✓ GEFUNDEN' : '✗ FEHLT'));
    error_log('   Workday Submenü: ' . ($workdayMenuFound ? '✓ GEFUNDEN' : '✗ FEHLT'));
    error_log('   Resources Submenü: ' . ($resourcesMenuFound ? '✓ GEFUNDEN' : '✗ FEHLT'));

    // 7. PRÜFE HOOK-REGISTRIERUNG
    error_log('');
    error_log('🔗 SCHRITT 7: WORDPRESS-HOOKS');
    error_log('───────────────────────────────────────────────────────────');

    global $wp_filter;

    $hooks_to_check = [
        'bookando_register_module_menus',
        'admin_menu',
        'admin_enqueue_scripts'
    ];

    foreach ($hooks_to_check as $hook) {
        if (isset($wp_filter[$hook])) {
            $count = count($wp_filter[$hook]->callbacks);
            error_log("✓ Hook '{$hook}' hat {$count} Callback(s)");
        } else {
            error_log("✗ Hook '{$hook}' hat keine Callbacks");
        }
    }

    error_log('');
    error_log('═══════════════════════════════════════════════════════════');
    error_log('🏁 DIAGNOSE ENDE');
    error_log('═══════════════════════════════════════════════════════════');

}, 999); // Sehr späte Priority damit alle Module geladen sind

// Zusätzlich: Hook in admin_menu um zu sehen wann Menüs registriert werden
add_action('admin_menu', function() {
    error_log('');
    error_log('📍 CHECKPOINT: admin_menu Hook wurde gefeuert');
}, 999);

add_action('admin_enqueue_scripts', function($hook_suffix) {
    if (strpos($hook_suffix, 'bookando') !== false) {
        error_log('');
        error_log('📍 CHECKPOINT: admin_enqueue_scripts für Bookando-Seite');
        error_log('   Hook Suffix: ' . $hook_suffix);

        // Prüfe welche Scripts enqueued wurden
        global $wp_scripts;
        if ($wp_scripts) {
            error_log('   Enqueued Bookando Scripts:');
            foreach ($wp_scripts->queue as $handle) {
                if (strpos($handle, 'bookando') !== false) {
                    $script = $wp_scripts->registered[$handle];
                    error_log('   - ' . $handle . ' → ' . $script->src);
                }
            }
        }
    }
}, 999);
