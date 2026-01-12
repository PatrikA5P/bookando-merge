<?php

namespace Bookando\Core\Admin;

use Bookando\Core\Manager\ModuleManifest;
use Bookando\Core\Manager\ModuleStateRepository;
use Bookando\Core\Service\ActivityLogger;

class ModuleDiagnostics
{
    public static function register(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        add_action('admin_notices', [self::class, 'renderMissingModulesNotice']);
    }

    public static function renderMissingModulesNotice(): void
    {
        if (!function_exists('is_admin') || !is_admin()) {
            return;
        }

        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            return;
        }

        $missing = self::missingSlugs();
        if (empty($missing)) {
            return;
        }

        $list = implode(', ', array_map(static fn(string $slug): string => '<code>' . esc_html($slug) . '</code>', $missing));

        $message = sprintf(
            /* translators: %s is a comma separated list of module slugs */
            __('Folgende Bookando-Module sind aktiviert, aber im Plugin nicht vorhanden: %s. Bitte installieren Sie die fehlenden Module oder entfernen Sie ihre Slugs aus den Bookando-Einstellungen.', 'bookando'),
            $list
        );

        if (function_exists('wp_kses_post')) {
            $message = wp_kses_post($message);
        }

        printf('<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message);
    }

    public static function missingSlugs(): array
    {
        try {
            $active = ModuleStateRepository::instance()->getActiveSlugs();
        } catch (\Throwable $exception) {
            ActivityLogger::error('modules.diagnostics', 'Failed to read active modules', [
                'error' => $exception->getMessage(),
            ]);
            return [];
        }

        try {
            $available = ModuleManifest::slugs();
        } catch (\Throwable $exception) {
            ActivityLogger::error('modules.diagnostics', 'Failed to resolve available modules', [
                'error' => $exception->getMessage(),
            ]);
            return [];
        }

        return self::diffMissingSlugs($active, $available);
    }

    public static function diffMissingSlugs(array $active, array $available): array
    {
        $activeNormalized = self::normalizeSlugs($active);
        $availableNormalized = self::normalizeSlugs($available);

        return array_values(array_diff($activeNormalized, $availableNormalized));
    }

    private static function normalizeSlugs(array $slugs): array
    {
        $normalized = [];

        foreach ($slugs as $slug) {
            if (!is_scalar($slug)) {
                continue;
            }

            $clean = strtolower((string) $slug);
            $clean = preg_replace('/[^a-z0-9_\-]/', '', $clean ?? '');

            if (!is_string($clean) || $clean === '') {
                continue;
            }

            $normalized[] = $clean;
        }

        return array_values(array_unique($normalized));
    }
}
