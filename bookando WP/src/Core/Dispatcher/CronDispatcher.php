<?php

namespace Bookando\Core\Dispatcher;

use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Manager\ModuleManager;
use Bookando\Core\Manager\ModuleStateRepository;
use Bookando\Core\Service\ActivityLogger;

/**
 * Dispatcher für alle zeitgesteuerten Aufgaben via WP-Cron.
 * Beispiel: E-Mail-Queue, Cleanup, externe Syncs
 */
class CronDispatcher
{
    public static function register(): void
    {
        // Hourly-Job (custom)
        add_action('bookando_cron_hourly', [self::class, 'hourly']);
        // Daily-Job (custom)
        add_action('bookando_cron_daily', [self::class, 'daily']);

        // Optional: Cron-Intervale registrieren (nur einmal pro Request!)
        add_filter('cron_schedules', [self::class, 'addCronIntervals']);
    }

    public static function hourly()
    {
        $repository = ModuleStateRepository::instance();
        $activeSlugs = $repository->getActiveSlugs();

        $missing = [];
        foreach ($activeSlugs as $slug) {
            $manifest = BOOKANDO_PLUGIN_DIR . "/src/modules/{$slug}/module.json";
            if (!file_exists($manifest)) {
                $missing[] = $slug;
            }
        }

        if ($missing) {
            ActivityLogger::warning('cron.hourly', 'Active modules missing manifest files', ['slugs' => $missing]);
        }

        $stale = $repository->findInactiveOlderThanDays(30);
        if ($stale) {
            ActivityLogger::info('cron.hourly', 'Inactive modules older than 30 days', ['slugs' => $stale]);
        }

        ActivityLogger::info('cron.hourly', 'Hourly health check complete', [
            'active_count' => count($activeSlugs),
        ]);
    }

    public static function daily()
    {
        $repository = ModuleStateRepository::instance();
        $states = $repository->getAllStates();
        $available = ModuleManager::instance()->scanModules();
        $availableMap = array_map('strtolower', $available);
        $orphaned = [];
        foreach ($states as $state) {
            $slug = strtolower($state->slug ?? '');
            if ($slug && !in_array($slug, $availableMap, true)) {
                $orphaned[] = $slug;
            }
        }

        if ($orphaned) {
            ActivityLogger::warning('cron.daily', 'Module states without filesystem manifest', ['slugs' => $orphaned]);
        }

        $licenseValid = LicenseManager::hasValidLicense();
        ActivityLogger::info('cron.daily', 'License health snapshot', [
            'valid' => $licenseValid,
            'plan'  => LicenseManager::getLicensePlan(),
        ]);
    }

    /**
     * Füge eigene Cron-Intervalle hinzu (z.B. alle 15 Minuten)
     */
    public static function addCronIntervals($schedules)
    {
        $schedules['bookando_fifteen'] = [
            'interval' => 15 * 60,
            'display'  => __('Every 15 Minutes', 'bookando')
        ];
        return $schedules;
    }
}
