<?php

declare(strict_types=1);

namespace Bookando\Modules\Tools\Services;

use Bookando\Core\Database\DBManager;

/**
 * Design Template Service
 *
 * Handles storage, retrieval, and management of design templates
 */
class DesignTemplateService
{
    private const OPTION_KEY = 'bookando_design_templates';
    private const CACHE_GROUP = 'bookando_design';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all design templates
     *
     * @param array $filters Optional filters (area, tenant_id)
     * @return array Array of templates
     */
    public static function getAll(array $filters = []): array
    {
        $cacheKey = self::getCacheKey('all', $filters);
        $cached = wp_cache_get($cacheKey, self::CACHE_GROUP);

        if ($cached !== false) {
            return $cached;
        }

        $templates = get_option(self::OPTION_KEY, []);

        // Apply filters
        if (!empty($filters['area'])) {
            $templates = array_filter($templates, function ($template) use ($filters) {
                return $template['area'] === $filters['area'];
            });
        }

        if (!empty($filters['tenant_id'])) {
            $templates = array_filter($templates, function ($template) use ($filters) {
                return ($template['tenant_id'] ?? null) === $filters['tenant_id'];
            });
        }

        // Sort by updated_at DESC
        usort($templates, function ($a, $b) {
            return strtotime($b['updated_at'] ?? '0') - strtotime($a['updated_at'] ?? '0');
        });

        wp_cache_set($cacheKey, $templates, self::CACHE_GROUP, self::CACHE_TTL);

        return $templates;
    }

    /**
     * Get a single template by ID
     *
     * @param int $id Template ID
     * @return array|null Template data or null if not found
     */
    public static function get(int $id): ?array
    {
        $templates = self::getAll();

        foreach ($templates as $template) {
            if ($template['id'] === $id) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Create a new design template
     *
     * @param array $data Template data
     * @return array Created template with ID
     */
    public static function create(array $data): array
    {
        $templates = self::getAll();

        $template = [
            'id' => self::generateId($templates),
            'name' => $data['name'] ?? 'Neue Vorlage',
            'area' => $data['area'] ?? 'services',
            'globalSettings' => $data['globalSettings'] ?? self::getDefaultGlobalSettings(),
            'components' => $data['components'] ?? [],
            'tenant_id' => $data['tenant_id'] ?? null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $templates[] = $template;
        update_option(self::OPTION_KEY, $templates);

        // Clear cache
        self::clearCache();

        // Log activity
        do_action('bookando_design_template_created', $template);

        return $template;
    }

    /**
     * Update an existing template
     *
     * @param int $id Template ID
     * @param array $data Updated data
     * @return array|null Updated template or null if not found
     */
    public static function update(int $id, array $data): ?array
    {
        $templates = self::getAll();
        $found = false;

        foreach ($templates as $index => $template) {
            if ($template['id'] === $id) {
                $templates[$index] = array_merge($template, [
                    'name' => $data['name'] ?? $template['name'],
                    'area' => $data['area'] ?? $template['area'],
                    'globalSettings' => $data['globalSettings'] ?? $template['globalSettings'],
                    'components' => $data['components'] ?? $template['components'],
                    'updated_at' => current_time('mysql'),
                ]);

                $found = true;
                break;
            }
        }

        if (!$found) {
            return null;
        }

        update_option(self::OPTION_KEY, $templates);

        // Clear cache
        self::clearCache();

        // Log activity
        do_action('bookando_design_template_updated', $templates[$index]);

        return $templates[$index];
    }

    /**
     * Delete a template
     *
     * @param int $id Template ID
     * @return bool True if deleted, false if not found
     */
    public static function delete(int $id): bool
    {
        $templates = self::getAll();
        $deleted = false;

        foreach ($templates as $index => $template) {
            if ($template['id'] === $id) {
                // Delete compiled CSS
                self::deleteCompiledCSS($id);

                unset($templates[$index]);
                $deleted = true;
                break;
            }
        }

        if (!$deleted) {
            return false;
        }

        // Re-index array
        $templates = array_values($templates);
        update_option(self::OPTION_KEY, $templates);

        // Clear cache
        self::clearCache();

        // Log activity
        do_action('bookando_design_template_deleted', $id);

        return true;
    }

    /**
     * Compile template to CSS
     *
     * @param int $id Template ID
     * @return string|null Compiled CSS or null if template not found
     */
    public static function compile(int $id): ?string
    {
        $template = self::get($id);

        if (!$template) {
            return null;
        }

        $css = self::generateCSS($template);

        // Save compiled CSS
        self::saveCompiledCSS($id, $css);

        return $css;
    }

    /**
     * Generate CSS from template data
     *
     * @param array $template Template data
     * @return string Generated CSS
     */
    private static function generateCSS(array $template): string
    {
        $globalSettings = $template['globalSettings'] ?? [];
        $colors = $globalSettings['colors'] ?? [];
        $gradient = $globalSettings['gradient'] ?? [];
        $border = $globalSettings['border'] ?? [];

        $css = "/* Bookando Design Template: {$template['name']} */\n";
        $css .= "/* Generated: " . date('Y-m-d H:i:s') . " */\n\n";

        $css .= ":root {\n";

        // Font Family
        if (!empty($globalSettings['fontFamily'])) {
            $css .= "  --bookando-font-family: '{$globalSettings['fontFamily']}', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;\n";
        }

        // Primary & State Colors
        if (!empty($colors['primary'])) {
            $css .= "  --bookando-primary: {$colors['primary']};\n";
        }
        if (!empty($colors['success'])) {
            $css .= "  --bookando-success: {$colors['success']};\n";
        }
        if (!empty($colors['warning'])) {
            $css .= "  --bookando-warning: {$colors['warning']};\n";
        }
        if (!empty($colors['error'])) {
            $css .= "  --bookando-error: {$colors['error']};\n";
        }

        // Sidebar Colors
        if (!empty($colors['sidebar']['background'])) {
            $css .= "  --bookando-sidebar-bg: {$colors['sidebar']['background']};\n";
        }
        if (!empty($colors['sidebar']['text'])) {
            $css .= "  --bookando-sidebar-text: {$colors['sidebar']['text']};\n";
        }

        // Content Colors
        if (!empty($colors['content']['background'])) {
            $css .= "  --bookando-content-bg: {$colors['content']['background']};\n";
        }
        if (!empty($colors['content']['heading'])) {
            $css .= "  --bookando-content-heading: {$colors['content']['heading']};\n";
        }
        if (!empty($colors['content']['text'])) {
            $css .= "  --bookando-content-text: {$colors['content']['text']};\n";
        }

        // Input Colors
        if (!empty($colors['input']['background'])) {
            $css .= "  --bookando-input-bg: {$colors['input']['background']};\n";
        }
        if (!empty($colors['input']['border'])) {
            $css .= "  --bookando-input-border: {$colors['input']['border']};\n";
        }
        if (!empty($colors['input']['text'])) {
            $css .= "  --bookando-input-text: {$colors['input']['text']};\n";
        }
        if (!empty($colors['input']['placeholder'])) {
            $css .= "  --bookando-input-placeholder: {$colors['input']['placeholder']};\n";
        }

        // Button Colors
        if (!empty($colors['buttons']['primary']['background'])) {
            $css .= "  --bookando-btn-primary-bg: {$colors['buttons']['primary']['background']};\n";
        }
        if (!empty($colors['buttons']['primary']['text'])) {
            $css .= "  --bookando-btn-primary-text: {$colors['buttons']['primary']['text']};\n";
        }
        if (!empty($colors['buttons']['secondary']['background'])) {
            $css .= "  --bookando-btn-secondary-bg: {$colors['buttons']['secondary']['background']};\n";
        }
        if (!empty($colors['buttons']['secondary']['text'])) {
            $css .= "  --bookando-btn-secondary-text: {$colors['buttons']['secondary']['text']};\n";
        }

        // Border
        if (isset($border['width'])) {
            $css .= "  --bookando-border-width: {$border['width']}px;\n";
        }
        if (isset($border['radius'])) {
            $css .= "  --bookando-border-radius: {$border['radius']}px;\n";
        }

        // Gradient
        if (!empty($gradient['enabled']) && !empty($gradient['color1']) && !empty($gradient['color2'])) {
            $angle = $gradient['angle'] ?? 135;
            $css .= "  --bookando-gradient: linear-gradient({$angle}deg, {$gradient['color1']}, {$gradient['color2']});\n";
        }

        $css .= "}\n\n";

        // Apply to body
        $css .= "body.bookando-template-{$template['id']} {\n";
        $css .= "  font-family: var(--bookando-font-family);\n";
        $css .= "}\n\n";

        // Component-specific CSS can be added here based on $template['components']

        return $css;
    }

    /**
     * Save compiled CSS to file
     *
     * @param int $id Template ID
     * @param string $css Compiled CSS
     * @return bool True if saved successfully
     */
    private static function saveCompiledCSS(int $id, string $css): bool
    {
        $uploadDir = wp_upload_dir();
        $cssDir = $uploadDir['basedir'] . '/bookando/design-templates';

        // Create directory if it doesn't exist
        if (!file_exists($cssDir)) {
            wp_mkdir_p($cssDir);
        }

        $cssFile = $cssDir . "/template-{$id}.css";

        return file_put_contents($cssFile, $css) !== false;
    }

    /**
     * Delete compiled CSS file
     *
     * @param int $id Template ID
     * @return bool True if deleted successfully
     */
    private static function deleteCompiledCSS(int $id): bool
    {
        $uploadDir = wp_upload_dir();
        $cssFile = $uploadDir['basedir'] . "/bookando/design-templates/template-{$id}.css";

        if (file_exists($cssFile)) {
            return unlink($cssFile);
        }

        return true;
    }

    /**
     * Get compiled CSS URL
     *
     * @param int $id Template ID
     * @return string|null CSS URL or null if file doesn't exist
     */
    public static function getCompiledCSSUrl(int $id): ?string
    {
        $uploadDir = wp_upload_dir();
        $cssFile = $uploadDir['basedir'] . "/bookando/design-templates/template-{$id}.css";

        if (file_exists($cssFile)) {
            return $uploadDir['baseurl'] . "/bookando/design-templates/template-{$id}.css";
        }

        return null;
    }

    /**
     * Get default global settings
     *
     * @return array Default settings
     */
    private static function getDefaultGlobalSettings(): array
    {
        return [
            'fontFamily' => 'Inter',
            'customFontUrl' => '',
            'border' => [
                'width' => 1,
                'radius' => 6,
            ],
            'colors' => [
                'primary' => '#1A84EE',
                'success' => '#5FCE19',
                'warning' => '#FFA700',
                'error' => '#FF0040',
                'sidebar' => [
                    'background' => '#1F2937',
                    'text' => '#F9FAFB',
                ],
                'content' => [
                    'background' => '#FFFFFF',
                    'heading' => '#354052',
                    'text' => '#7F8FA4',
                ],
                'input' => [
                    'background' => '#FFFFFF',
                    'border' => '#E2E6EC',
                    'text' => '#354052',
                    'placeholder' => 'rgba(127, 143, 164, 0.5)',
                ],
                'buttons' => [
                    'primary' => [
                        'background' => '#1A84EE',
                        'text' => '#FFFFFF',
                    ],
                    'secondary' => [
                        'background' => '#F9F9F9',
                        'text' => '#354052',
                    ],
                ],
            ],
            'gradient' => [
                'enabled' => false,
                'color1' => '#1A84EE',
                'color2' => '#A28FF3',
                'angle' => 135,
            ],
        ];
    }

    /**
     * Generate unique ID for new template
     *
     * @param array $templates Existing templates
     * @return int New ID
     */
    private static function generateId(array $templates): int
    {
        if (empty($templates)) {
            return 1;
        }

        $maxId = 0;
        foreach ($templates as $template) {
            if ($template['id'] > $maxId) {
                $maxId = $template['id'];
            }
        }

        return $maxId + 1;
    }

    /**
     * Clear cache
     */
    private static function clearCache(): void
    {
        wp_cache_delete_group(self::CACHE_GROUP);
    }

    /**
     * Get cache key
     *
     * @param string $key Key identifier
     * @param array $params Additional parameters
     * @return string Cache key
     */
    private static function getCacheKey(string $key, array $params = []): string
    {
        return $key . '_' . md5(serialize($params));
    }

    /**
     * Duplicate a template
     *
     * @param int $id Template ID to duplicate
     * @return array|null Duplicated template or null if not found
     */
    public static function duplicate(int $id): ?array
    {
        $template = self::get($id);

        if (!$template) {
            return null;
        }

        // Remove ID and update name
        unset($template['id']);
        $template['name'] = $template['name'] . ' (Kopie)';

        return self::create($template);
    }
}
