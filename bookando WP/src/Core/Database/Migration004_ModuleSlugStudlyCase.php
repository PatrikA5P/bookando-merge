<?php

declare(strict_types=1);

namespace Bookando\Core\Database;

/**
 * Migration 004: Convert module slugs from lowercase to StudlyCase
 *
 * After PSR-4 refactoring, all module directories are now StudlyCase:
 * - academy → Academy
 * - customers → Customers
 * - offers → Offers
 * etc.
 *
 * This migration updates all database entries to match the new format.
 */
class Migration004_ModuleSlugStudlyCase
{
    /**
     * Map of lowercase module slugs to StudlyCase
     */
    private const MODULE_MAP = [
        'academy'      => 'Academy',
        'appointments' => 'Appointments',
        'customers'    => 'Customers',
        'employees'    => 'Employees',
        'finance'      => 'Finance',
        'offers'       => 'Offers',
        'partnerhub'   => 'Partnerhub',
        'resources'    => 'Resources',
        'settings'     => 'Settings',
        'tools'        => 'Tools',
        'workday'      => 'Workday',
    ];

    public static function up(): bool
    {
        global $wpdb;

        error_log('[Bookando Migration 004] Starting module slug StudlyCase conversion...');

        try {
            // 1. Update bookando_active_modules option
            self::updateActiveModules();

            // 2. Update individual module state options (bookando_module_state_*)
            self::updateModuleStateOptions();

            // 3. Update module installation timestamps
            self::updateInstallationTimestamps();

            // 4. Update any meta data or other tables that might store module slugs
            self::updateMetaData();

            error_log('[Bookando Migration 004] ✅ Module slug conversion completed');
            return true;
        } catch (\Throwable $e) {
            error_log('[Bookando Migration 004] ❌ Error: ' . $e->getMessage());
            return false;
        }
    }

    private static function updateActiveModules(): void
    {
        $activeModules = get_option('bookando_active_modules', []);

        if (!is_array($activeModules) || empty($activeModules)) {
            error_log('[Bookando Migration 004] No active modules found, skipping');
            return;
        }

        $updated = [];
        $changed = false;

        foreach ($activeModules as $slug) {
            if (isset(self::MODULE_MAP[$slug])) {
                $updated[] = self::MODULE_MAP[$slug];
                $changed = true;
                error_log("[Bookando Migration 004] Active module: {$slug} → " . self::MODULE_MAP[$slug]);
            } else {
                // Already StudlyCase or unknown module
                $updated[] = $slug;
            }
        }

        if ($changed) {
            update_option('bookando_active_modules', $updated);
            error_log('[Bookando Migration 004] ✅ Updated bookando_active_modules: ' . implode(', ', $updated));
        } else {
            error_log('[Bookando Migration 004] Active modules already in correct format');
        }
    }

    private static function updateModuleStateOptions(): void
    {
        global $wpdb;

        // Find all options like bookando_module_state_customers
        $results = $wpdb->get_results(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE 'bookando_module_state_%'",
            ARRAY_A
        );

        if (empty($results)) {
            return;
        }

        foreach ($results as $row) {
            $optionName = $row['option_name'];

            // Extract slug from option name: bookando_module_state_customers → customers
            if (preg_match('/^bookando_module_state_(.+)$/', $optionName, $matches)) {
                $oldSlug = $matches[1];

                if (isset(self::MODULE_MAP[$oldSlug])) {
                    $newSlug = self::MODULE_MAP[$oldSlug];
                    $newOptionName = 'bookando_module_state_' . $newSlug;

                    // Copy to new option name
                    update_option($newOptionName, $row['option_value']);

                    // Delete old option
                    delete_option($optionName);

                    error_log("[Bookando Migration 004] Renamed option: {$optionName} → {$newOptionName}");
                }
            }
        }
    }

    private static function updateInstallationTimestamps(): void
    {
        global $wpdb;

        // Find all options like bookando_module_installed_at_customers
        $results = $wpdb->get_results(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE 'bookando_module_installed_at_%'",
            ARRAY_A
        );

        if (empty($results)) {
            return;
        }

        foreach ($results as $row) {
            $optionName = $row['option_name'];

            // Extract slug: bookando_module_installed_at_customers → customers
            if (preg_match('/^bookando_module_installed_at_(.+)$/', $optionName, $matches)) {
                $oldSlug = $matches[1];

                if (isset(self::MODULE_MAP[$oldSlug])) {
                    $newSlug = self::MODULE_MAP[$oldSlug];
                    $newOptionName = 'bookando_module_installed_at_' . strtolower($newSlug);

                    // Note: We keep these lowercase for backward compatibility
                    // The code in ModuleManager uses strtolower() for these keys
                    if ($optionName !== $newOptionName) {
                        update_option($newOptionName, $row['option_value']);
                        delete_option($optionName);
                        error_log("[Bookando Migration 004] Renamed timestamp: {$optionName} → {$newOptionName}");
                    }
                }
            }
        }
    }

    private static function updateMetaData(): void
    {
        global $wpdb;

        // Update user meta that might contain module slugs
        // For example: bookando_favorite_modules, bookando_module_permissions, etc.
        $metaKeys = [
            'bookando_favorite_modules',
            'bookando_module_permissions',
            'bookando_module_access',
        ];

        foreach ($metaKeys as $metaKey) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT umeta_id, user_id, meta_value
                 FROM {$wpdb->usermeta}
                 WHERE meta_key = %s",
                $metaKey
            ), ARRAY_A);

            if (empty($results)) {
                continue;
            }

            foreach ($results as $row) {
                $value = maybe_unserialize($row['meta_value']);

                if (is_array($value)) {
                    $updated = self::convertSlugsInArray($value);

                    if ($updated !== $value) {
                        update_user_meta(
                            (int) $row['user_id'],
                            $metaKey,
                            $updated
                        );
                        error_log("[Bookando Migration 004] Updated user meta {$metaKey} for user " . $row['user_id']);
                    }
                }
            }
        }
    }

    /**
     * Recursively convert module slugs in arrays
     */
    private static function convertSlugsInArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            // Convert key if it's a module slug
            $newKey = self::MODULE_MAP[$key] ?? $key;

            // Convert value if it's a module slug or array
            if (is_array($value)) {
                $newValue = self::convertSlugsInArray($value);
            } elseif (is_string($value) && isset(self::MODULE_MAP[$value])) {
                $newValue = self::MODULE_MAP[$value];
            } else {
                $newValue = $value;
            }

            $result[$newKey] = $newValue;
        }

        return $result;
    }

    public static function down(): void
    {
        // Rollback: Convert StudlyCase back to lowercase
        // Not recommended, but provided for completeness

        $activeModules = get_option('bookando_active_modules', []);

        if (is_array($activeModules)) {
            $reverted = array_map('strtolower', $activeModules);
            update_option('bookando_active_modules', $reverted);
        }

        error_log('[Bookando Migration 004] ⚠️  Rolled back to lowercase module slugs');
    }
}
