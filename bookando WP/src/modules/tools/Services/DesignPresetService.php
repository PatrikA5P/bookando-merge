<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Services;

/**
 * Design Preset Service
 *
 * Provides predefined design templates (Modern, Classic, Minimal, Vibrant)
 */
class DesignPresetService
{
    /**
     * Get all available presets
     *
     * @return array Array of presets
     */
    public static function getAll(): array
    {
        return [
            self::getModernPreset(),
            self::getClassicPreset(),
            self::getMinimalPreset(),
            self::getVibrantPreset(),
        ];
    }

    /**
     * Get preset by name
     *
     * @param string $name Preset name
     * @return array|null Preset or null if not found
     */
    public static function get(string $name): ?array
    {
        $presets = self::getAll();

        foreach ($presets as $preset) {
            if ($preset['name'] === $name) {
                return $preset;
            }
        }

        return null;
    }

    /**
     * Modern Preset - Gradient + Clean Design
     *
     * @return array Preset configuration
     */
    private static function getModernPreset(): array
    {
        return [
            'name' => 'Modern',
            'label' => 'Modern',
            'description' => 'Modernes Design mit Gradienten und klaren Linien',
            'thumbnail' => 'modern-preset.png',
            'globalSettings' => [
                'fontFamily' => 'Inter',
                'customFontUrl' => '',
                'border' => [
                    'width' => 1,
                    'radius' => 12,
                ],
                'colors' => [
                    'primary' => '#1A84EE',
                    'success' => '#10B981',
                    'warning' => '#F59E0B',
                    'error' => '#EF4444',
                    'sidebar' => [
                        'background' => '#1F2937',
                        'text' => '#F9FAFB',
                    ],
                    'content' => [
                        'background' => '#FFFFFF',
                        'heading' => '#111827',
                        'text' => '#6B7280',
                    ],
                    'input' => [
                        'background' => '#F9FAFB',
                        'border' => '#E5E7EB',
                        'text' => '#111827',
                        'placeholder' => 'rgba(107, 114, 128, 0.5)',
                    ],
                    'buttons' => [
                        'primary' => [
                            'background' => '#1A84EE',
                            'text' => '#FFFFFF',
                        ],
                        'secondary' => [
                            'background' => '#F3F4F6',
                            'text' => '#374151',
                        ],
                    ],
                ],
                'gradient' => [
                    'enabled' => true,
                    'color1' => '#1A84EE',
                    'color2' => '#A78BFA',
                    'angle' => 135,
                ],
            ],
        ];
    }

    /**
     * Classic Preset - Traditional + Elegant
     *
     * @return array Preset configuration
     */
    private static function getClassicPreset(): array
    {
        return [
            'name' => 'Classic',
            'label' => 'Classic',
            'description' => 'Klassisches Design mit eleganten Farben',
            'thumbnail' => 'classic-preset.png',
            'globalSettings' => [
                'fontFamily' => 'Roboto',
                'customFontUrl' => '',
                'border' => [
                    'width' => 1,
                    'radius' => 4,
                ],
                'colors' => [
                    'primary' => '#2563EB',
                    'success' => '#059669',
                    'warning' => '#D97706',
                    'error' => '#DC2626',
                    'sidebar' => [
                        'background' => '#1E3A8A',
                        'text' => '#DBEAFE',
                    ],
                    'content' => [
                        'background' => '#FEFEFE',
                        'heading' => '#1F2937',
                        'text' => '#4B5563',
                    ],
                    'input' => [
                        'background' => '#FFFFFF',
                        'border' => '#D1D5DB',
                        'text' => '#1F2937',
                        'placeholder' => 'rgba(75, 85, 99, 0.5)',
                    ],
                    'buttons' => [
                        'primary' => [
                            'background' => '#2563EB',
                            'text' => '#FFFFFF',
                        ],
                        'secondary' => [
                            'background' => '#FFFFFF',
                            'text' => '#2563EB',
                        ],
                    ],
                ],
                'gradient' => [
                    'enabled' => false,
                    'color1' => '#2563EB',
                    'color2' => '#1E40AF',
                    'angle' => 90,
                ],
            ],
        ];
    }

    /**
     * Minimal Preset - Monochrome + Simple
     *
     * @return array Preset configuration
     */
    private static function getMinimalPreset(): array
    {
        return [
            'name' => 'Minimal',
            'label' => 'Minimal',
            'description' => 'Minimalistisches Design mit monochromen Farben',
            'thumbnail' => 'minimal-preset.png',
            'globalSettings' => [
                'fontFamily' => 'Open Sans',
                'customFontUrl' => '',
                'border' => [
                    'width' => 1,
                    'radius' => 8,
                ],
                'colors' => [
                    'primary' => '#18181B',
                    'success' => '#10B981',
                    'warning' => '#F59E0B',
                    'error' => '#EF4444',
                    'sidebar' => [
                        'background' => '#F4F4F5',
                        'text' => '#18181B',
                    ],
                    'content' => [
                        'background' => '#FFFFFF',
                        'heading' => '#09090B',
                        'text' => '#71717A',
                    ],
                    'input' => [
                        'background' => '#FFFFFF',
                        'border' => '#E4E4E7',
                        'text' => '#18181B',
                        'placeholder' => 'rgba(113, 113, 122, 0.5)',
                    ],
                    'buttons' => [
                        'primary' => [
                            'background' => '#18181B',
                            'text' => '#FAFAFA',
                        ],
                        'secondary' => [
                            'background' => '#F4F4F5',
                            'text' => '#18181B',
                        ],
                    ],
                ],
                'gradient' => [
                    'enabled' => false,
                    'color1' => '#18181B',
                    'color2' => '#3F3F46',
                    'angle' => 180,
                ],
            ],
        ];
    }

    /**
     * Vibrant Preset - Bold Colors + Energetic
     *
     * @return array Preset configuration
     */
    private static function getVibrantPreset(): array
    {
        return [
            'name' => 'Vibrant',
            'label' => 'Vibrant',
            'description' => 'Lebendiges Design mit kräftigen Farben',
            'thumbnail' => 'vibrant-preset.png',
            'globalSettings' => [
                'fontFamily' => 'Poppins',
                'customFontUrl' => '',
                'border' => [
                    'width' => 2,
                    'radius' => 16,
                ],
                'colors' => [
                    'primary' => '#EC4899',
                    'success' => '#14B8A6',
                    'warning' => '#FBBF24',
                    'error' => '#F43F5E',
                    'sidebar' => [
                        'background' => '#BE185D',
                        'text' => '#FCE7F3',
                    ],
                    'content' => [
                        'background' => '#FEF3F2',
                        'heading' => '#831843',
                        'text' => '#9F1239',
                    ],
                    'input' => [
                        'background' => '#FFFFFF',
                        'border' => '#F9A8D4',
                        'text' => '#831843',
                        'placeholder' => 'rgba(159, 18, 57, 0.4)',
                    ],
                    'buttons' => [
                        'primary' => [
                            'background' => '#EC4899',
                            'text' => '#FFFFFF',
                        ],
                        'secondary' => [
                            'background' => '#FECDD3',
                            'text' => '#831843',
                        ],
                    ],
                ],
                'gradient' => [
                    'enabled' => true,
                    'color1' => '#EC4899',
                    'color2' => '#8B5CF6',
                    'angle' => 45,
                ],
            ],
        ];
    }

    /**
     * Apply preset to a template
     *
     * @param string $presetName Preset name
     * @param array $template Existing template
     * @return array Template with preset applied
     */
    public static function applyPreset(string $presetName, array $template): array
    {
        $preset = self::get($presetName);

        if (!$preset) {
            return $template;
        }

        // Merge preset settings with template
        $template['globalSettings'] = array_merge(
            $template['globalSettings'] ?? [],
            $preset['globalSettings']
        );

        return $template;
    }
}
