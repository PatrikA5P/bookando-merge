<?php
/**
 * Plugin Name: Bookando
 * Description: Modulares Kurs- & Buchungs-Plugin für WordPress
 * Version:     1.0.0
 * Author:      Patrik Augello
 * License:     GPL-2.0-or-later
 * Text Domain: bookando
 */

use Bookando\CLI\SeedDevLicenseCommand;
use Bookando\Core\Admin\ModuleDiagnostics;
use Bookando\Core\Assets;
use Bookando\Core\Installer;
use Bookando\Core\Plugin;
use Bookando\Core\Role\CapabilityService;
use Bookando\Core\Service\ActivityLogger;

defined('ABSPATH') || exit;

// ===============================
// Globale Konstanten
// ===============================
define('BOOKANDO_PLUGIN_FILE', __FILE__);
define('BOOKANDO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BOOKANDO_PLUGIN_URL', plugin_dir_url(__FILE__));

// ===============================
// Environment Configuration (.env)
// ===============================
// Load .env file if exists (must be loaded before autoload for early config)
$envLoaderPath = BOOKANDO_PLUGIN_DIR . 'src/Core/Config/EnvLoader.php';
if (file_exists($envLoaderPath)) {
    require_once $envLoaderPath;
    \Bookando\Core\Config\EnvLoader::load(BOOKANDO_PLUGIN_DIR);
}

// ===============================
// Feature-Flags: sichere Defaults (falls nicht in wp-config.php oder .env gesetzt)
// ===============================
if (!defined('BOOKANDO_DEV')) {
    define('BOOKANDO_DEV', \Bookando\Core\Config\EnvLoader::getBool('BOOKANDO_DEV', false));
}
if (!defined('BOOKANDO_SYNC_USERS')) {
    define('BOOKANDO_SYNC_USERS', \Bookando\Core\Config\EnvLoader::getBool('BOOKANDO_SYNC_USERS', false));
}

// ===============================
// Composer Autoload
// ===============================
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    ActivityLogger::log(
        'core.bootstrap',
        'Composer autoload loaded',
        ['path' => $autoloadPath],
        ActivityLogger::LEVEL_INFO,
        null,
        'core'
    );
} else {
    trigger_error('[Bookando] ❌ Autoload fehlt: vendor/autoload.php nicht gefunden', E_USER_WARNING);
    return;
}

// ===============================
// Optional: Core-Helpers laden
// ===============================
$helpersPath = BOOKANDO_PLUGIN_DIR . 'src/Core/Helpers.php';
if (file_exists($helpersPath)) {
    require_once $helpersPath;
}

// ===============================
// DI Container: Helpers & Service Provider
// ===============================
$containerHelpersPath = BOOKANDO_PLUGIN_DIR . 'src/Core/Container/helpers.php';
if (file_exists($containerHelpersPath)) {
    require_once $containerHelpersPath;
    ActivityLogger::log(
        'core.bootstrap',
        'DI Container helpers loaded',
        [],
        ActivityLogger::LEVEL_INFO,
        null,
        'core'
    );
}

// Register all services
if (class_exists(\Bookando\Core\Providers\ServiceProvider::class)) {
    \Bookando\Core\Providers\ServiceProvider::register();
    ActivityLogger::log(
        'core.bootstrap',
        'ServiceProvider registered all services',
        [],
        ActivityLogger::LEVEL_INFO,
        null,
        'core'
    );
}

// ===============================
// Debug-Logger laden (wenn BOOKANDO_DEBUG aktiv)
// ===============================
if (defined('BOOKANDO_DEBUG') && BOOKANDO_DEBUG === true) {
    $debugLoggerPath = BOOKANDO_PLUGIN_DIR . 'src/Core/Service/DebugLogger.php';
    if (file_exists($debugLoggerPath)) {
        require_once $debugLoggerPath;
        ActivityLogger::log(
            'core.bootstrap',
            'DebugLogger loaded',
            ['debug_mode' => true],
            ActivityLogger::LEVEL_INFO,
            null,
            'core'
        );
    }
}

// ===============================
// Zentrale Assets für SPA-Module (Vue, Pinia, Admin-UI)
// ===============================

// 2. Vue 3 und Pinia via CDN im HEAD – VOR allen Modulen
add_action('admin_enqueue_scripts', [Assets::class, 'enqueue_vue_pinia'], 1);

// ===============================
// Plugin-Aktivierung: Installer starten + Cron planen
// ===============================

register_activation_hook(BOOKANDO_PLUGIN_FILE, function () {
    ActivityLogger::log(
        'core.bootstrap',
        'Plugin activated – running installer',
        [],
        ActivityLogger::LEVEL_INFO,
        null,
        'core'
    );
    Installer::run();

    // Run database migrations (Foreign Keys + Sync Columns)
    if (class_exists(\Bookando\Core\Database\Migrator::class)) {
        $migrationSuccess = \Bookando\Core\Database\Migrator::runMigration001();
        ActivityLogger::log(
            'core.database',
            'Migration 001 executed',
            ['success' => $migrationSuccess],
            $migrationSuccess ? ActivityLogger::LEVEL_INFO : ActivityLogger::LEVEL_ERROR,
            null,
            'core'
        );
    }

    // Run Migration 002: Queue Table
    if (class_exists(\Bookando\Core\Database\Migration002_CreateQueueTable::class)) {
        $queueMigrationSuccess = \Bookando\Core\Database\Migration002_CreateQueueTable::up();
        ActivityLogger::log(
            'core.database',
            'Migration 002 (Queue Table) executed',
            ['success' => $queueMigrationSuccess],
            $queueMigrationSuccess ? ActivityLogger::LEVEL_INFO : ActivityLogger::LEVEL_ERROR,
            null,
            'core'
        );
    }

    // Run Migration 003: Time Tracking & Shift Management
    if (class_exists(\Bookando\Core\Database\Migration003_TimeTrackingAndShiftManagement::class)) {
        $migration003Success = \Bookando\Core\Database\Migration003_TimeTrackingAndShiftManagement::up();
        ActivityLogger::log(
            'core.database',
            'Migration 003 (Time Tracking & Shift Management) executed',
            ['success' => $migration003Success],
            $migration003Success ? ActivityLogger::LEVEL_INFO : ActivityLogger::LEVEL_ERROR,
            null,
            'core'
        );
    }

    // Rollen & Capabilities anlegen/zuweisen (Administrator, Manager, Employee etc.)
    CapabilityService::seedOnActivation();

    // Cron für Lizenzprüfung einplanen (täglich)
    if (!wp_next_scheduled('bookando_license_verify')) {
        wp_schedule_event(time(), 'daily', 'bookando_license_verify');
    }

    // Cron für Log-Cleanup einplanen (täglich)
    if (!wp_next_scheduled('bookando_log_cleanup')) {
        wp_schedule_event(time(), 'daily', 'bookando_log_cleanup');
    }

    // Cron für Queue Processing einplanen
    if (class_exists(\Bookando\Core\Queue\QueueManager::class)) {
        \Bookando\Core\Queue\QueueManager::registerCron();
    }
});

// Lizenz-Cron Handler
add_action('bookando_license_verify', function () {
    $key = \Bookando\Core\Licensing\LicenseManager::getLicenseKey();
    if ($key) {
        \Bookando\Core\Licensing\LicenseManager::verifyRemote($key);
    }
});

// Log-Cleanup Cron Handler (löscht Logs älter als 90 Tage)
add_action('bookando_log_cleanup', function () {
    \Bookando\Core\Service\ActivityLogger::cleanupOldLogs(90);
});

// Queue Processing Cron Handler
add_action('bookando_queue_process', function () {
    if (class_exists(\Bookando\Core\Queue\QueueManager::class)) {
        \Bookando\Core\Queue\QueueManager::process(20); // Process up to 20 jobs per minute
    }
});

// Queue Cleanup Cron Handler
add_action('bookando_queue_cleanup', function () {
    if (class_exists(\Bookando\Core\Queue\QueueManager::class)) {
        \Bookando\Core\Queue\QueueManager::cleanup(7); // Keep completed jobs for 7 days
    }
});

// Add custom cron interval for queue
add_filter('cron_schedules', function ($schedules) {
    if (class_exists(\Bookando\Core\Queue\QueueManager::class)) {
        return \Bookando\Core\Queue\QueueManager::addCronInterval($schedules);
    }
    return $schedules;
});

// (optional) Beim Deaktivieren aufräumen: Cron entfernen + Capabilities zurücksetzen
register_deactivation_hook(BOOKANDO_PLUGIN_FILE, function () {
    // Cronjobs entfernen
    wp_clear_scheduled_hook('bookando_license_verify');
    wp_clear_scheduled_hook('bookando_log_cleanup');
    wp_clear_scheduled_hook('bookando_queue_process');
    wp_clear_scheduled_hook('bookando_queue_cleanup');

    // Bookando-Capabilities von Rollen entfernen (Rollen behalten wir i. d. R.)
    if (class_exists(\Bookando\Core\Role\CapabilityService::class)) {
        \Bookando\Core\Role\CapabilityService::removeAllFrom('administrator');
        \Bookando\Core\Role\CapabilityService::removeAllFrom('bookando_manager');
        // Falls dem Employee Bookando-Caps gegeben wurden:
        \Bookando\Core\Role\CapabilityService::removeAllFrom('bookando_employee');
    }

    // (Optional) Rollen komplett löschen – nur verwenden, wenn gewünscht.
    // remove_role('bookando_manager');
    // remove_role('bookando_employee');
});


// In DEV: Caps auch im laufenden Betrieb sicherstellen (falls Plugin schon aktiv war)
if (defined('BOOKANDO_DEV') && BOOKANDO_DEV) {
    add_action('admin_init', function () {
        CapabilityService::assignAllTo('administrator');
    });
}

// ===============================
// Hauptklasse starten
// ===============================
new Plugin();
ActivityLogger::log(
    'core.bootstrap',
    'Plugin constructed',
    [],
    ActivityLogger::LEVEL_INFO,
    null,
    'core'
);

if (class_exists(ModuleDiagnostics::class)) {
    ModuleDiagnostics::register();
}

// ===============================
// Security Headers Middleware
// ===============================
if (class_exists(\Bookando\Core\Middleware\SecurityHeadersMiddleware::class)) {
    \Bookando\Core\Middleware\SecurityHeadersMiddleware::register();
    ActivityLogger::log(
        'core.security',
        'Security headers middleware registered',
        [],
        ActivityLogger::LEVEL_INFO,
        null,
        'core'
    );
}

if (
    defined('WP_CLI')
    && WP_CLI
    && function_exists('wp_get_environment_type')
    && wp_get_environment_type() === 'development'
) {
    WP_CLI::add_command('bookando seed-dev-license', SeedDevLicenseCommand::class);
}

// ===============================
// Deinstallation: Datenbankbereinigung & Optionen
// ===============================
register_uninstall_hook(BOOKANDO_PLUGIN_FILE, 'bookando_uninstall_all');

function bookando_uninstall_all() {
    global $wpdb;
    ActivityLogger::log(
        'core.uninstall',
        'Plugin uninstall triggered',
        [],
        ActivityLogger::LEVEL_INFO,
        null,
        'core'
    );

    // Alle Bookando-Tabellen dynamisch droppen
    $prefix = $wpdb->prefix . 'bookando_';
    $tables = $wpdb->get_col("SHOW TABLES LIKE '{$prefix}%'");
    foreach ($tables as $table) {
        // SICHERHEIT: Validiere dass Tabellenname wirklich mit bookando_ beginnt
        // und nur erlaubte Zeichen enthält (verhindert SQL Injection)
        if (is_string($table) && strpos($table, $wpdb->prefix . 'bookando_') === 0) {
            // Escapen für zusätzliche Sicherheit
            $safe_table = '`' . esc_sql($table) . '`';
            $wpdb->query("DROP TABLE IF EXISTS {$safe_table}");
        }
    }

    // Optionen entfernen
    $option_keys = [
        'bookando_license_data',
        'bookando_active_modules',
        // Addiere hier weitere bookando_ Optionen nach Bedarf
    ];
    foreach ($option_keys as $key) {
        delete_option($key);
    }

    // Cronjobs entfernen (Lizenzcheck, Log-Cleanup, Queue etc.)
    wp_clear_scheduled_hook('bookando_license_verify');
    wp_clear_scheduled_hook('bookando_log_cleanup');
    wp_clear_scheduled_hook('bookando_queue_process');
    wp_clear_scheduled_hook('bookando_queue_cleanup');
}
