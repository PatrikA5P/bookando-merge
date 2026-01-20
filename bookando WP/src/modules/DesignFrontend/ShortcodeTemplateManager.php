<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Shortcode Template Manager
 *
 * Manages shortcode templates and presets
 */
class ShortcodeTemplateManager
{
    /**
     * Get all templates
     */
    public static function getTemplates(string $shortcodeType = null): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_shortcode_templates';

        if ($shortcodeType) {
            $templates = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE shortcode_type = %s ORDER BY is_preset DESC, usage_count DESC, name ASC",
                $shortcodeType
            ), ARRAY_A);
        } else {
            $templates = $wpdb->get_results(
                "SELECT * FROM {$table} ORDER BY is_preset DESC, usage_count DESC, name ASC",
                ARRAY_A
            );
        }

        foreach ($templates as &$template) {
            $template['config'] = !empty($template['config']) ? json_decode($template['config'], true) : [];
        }

        return $templates ?: [];
    }

    /**
     * Get single template
     */
    public static function getTemplate(int $id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_shortcode_templates';

        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ), ARRAY_A);

        if ($template) {
            $template['config'] = !empty($template['config']) ? json_decode($template['config'], true) : [];
        }

        return $template ?: null;
    }

    /**
     * Create template
     */
    public static function createTemplate(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_shortcode_templates';

        $wpdb->insert($table, [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'shortcode_type' => sanitize_text_field($data['shortcode_type']),
            'config' => wp_json_encode($data['config'] ?? []),
            'is_preset' => (int)($data['is_preset'] ?? 0),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ]);

        return (int)$wpdb->insert_id;
    }

    /**
     * Update template
     */
    public static function updateTemplate(int $id, array $data): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_shortcode_templates';

        return (bool)$wpdb->update($table, [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'config' => wp_json_encode($data['config'] ?? []),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);
    }

    /**
     * Delete template
     */
    public static function deleteTemplate(int $id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_shortcode_templates';

        return (bool)$wpdb->delete($table, ['id' => $id]);
    }

    /**
     * Increment usage count
     */
    public static function incrementUsage(int $id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_shortcode_templates';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET usage_count = usage_count + 1 WHERE id = %d",
            $id
        ));
    }

    /**
     * Create default presets
     */
    public static function createDefaultPresets(): void
    {
        $presets = [
            [
                'name' => 'Anfänger-Kurse',
                'description' => 'Alle Angebote für Fahranfänger',
                'shortcode_type' => 'catalog',
                'config' => [
                    'tag' => '{1}', // Tag ID for "beginner"
                    'layout' => 'grid',
                    'columns' => 3,
                    'show' => 'courses',
                ],
                'is_preset' => 1,
            ],
            [
                'name' => 'Premium-Pakete',
                'description' => 'Featured Premium-Angebote',
                'shortcode_type' => 'catalog',
                'config' => [
                    'featured' => 1,
                    'show' => 'packages',
                    'layout' => 'grid',
                    'columns' => 2,
                ],
                'is_preset' => 1,
            ],
            [
                'name' => 'Wochenend-Kurse',
                'description' => 'Kurse am Wochenende',
                'shortcode_type' => 'list',
                'config' => [
                    'tag' => '{2}', // Tag ID for "weekend"
                    'show' => 'courses',
                    'limit' => 5,
                ],
                'is_preset' => 1,
            ],
            [
                'name' => 'Schnell-Buchung',
                'description' => 'Schneller Buchungs-Wizard',
                'shortcode_type' => 'booking',
                'config' => [
                    'layout' => 'compact',
                    'show' => 'all',
                    'preselect' => 1,
                ],
                'is_preset' => 1,
            ],
            [
                'name' => 'Theorieprüfungs-Termine',
                'description' => 'Kalender für Theorieprüfung',
                'shortcode_type' => 'calendar',
                'config' => [
                    'category' => '1', // Theory category
                    'view' => 'month',
                ],
                'is_preset' => 1,
            ],
        ];

        foreach ($presets as $preset) {
            // Check if preset already exists
            global $wpdb;
            $table = $wpdb->prefix . 'bookando_frontend_shortcode_templates';
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE name = %s AND is_preset = 1",
                $preset['name']
            ));

            if (!$exists) {
                self::createTemplate($preset);
            }
        }
    }
}
