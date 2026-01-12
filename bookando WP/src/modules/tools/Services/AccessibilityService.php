<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Services;

/**
 * Accessibility Service
 *
 * Provides WCAG contrast checking and accessibility validation
 */
class AccessibilityService
{
    // WCAG 2.1 Contrast Ratios
    private const WCAG_AA_NORMAL = 4.5;
    private const WCAG_AA_LARGE = 3.0;
    private const WCAG_AAA_NORMAL = 7.0;
    private const WCAG_AAA_LARGE = 4.5;

    /**
     * Check contrast ratio between two colors
     *
     * @param string $foreground Foreground color (hex, rgb, rgba)
     * @param string $background Background color (hex, rgb, rgba)
     * @return array Contrast analysis result
     */
    public static function checkContrast(string $foreground, string $background): array
    {
        $fgRgb = self::parseColor($foreground);
        $bgRgb = self::parseColor($background);

        if (!$fgRgb || !$bgRgb) {
            return [
                'error' => 'Invalid color format',
                'ratio' => null,
                'wcag' => null,
            ];
        }

        $fgLuminance = self::getLuminance($fgRgb);
        $bgLuminance = self::getLuminance($bgRgb);

        $ratio = self::calculateContrastRatio($fgLuminance, $bgLuminance);

        return [
            'ratio' => round($ratio, 2),
            'wcag' => [
                'aa' => [
                    'normal' => $ratio >= self::WCAG_AA_NORMAL,
                    'large' => $ratio >= self::WCAG_AA_LARGE,
                ],
                'aaa' => [
                    'normal' => $ratio >= self::WCAG_AAA_NORMAL,
                    'large' => $ratio >= self::WCAG_AAA_LARGE,
                ],
            ],
            'level' => self::getWCAGLevel($ratio),
            'recommendation' => self::getRecommendation($ratio),
        ];
    }

    /**
     * Validate template accessibility
     *
     * @param array $template Template data
     * @return array Validation result with warnings
     */
    public static function validateTemplate(array $template): array
    {
        $warnings = [];
        $errors = [];

        $colors = $template['globalSettings']['colors'] ?? [];

        // Check content text on background
        if (!empty($colors['content']['text']) && !empty($colors['content']['background'])) {
            $check = self::checkContrast($colors['content']['text'], $colors['content']['background']);

            if ($check['ratio'] < self::WCAG_AA_NORMAL) {
                $warnings[] = [
                    'type' => 'contrast',
                    'severity' => 'high',
                    'message' => 'Content text contrast is too low',
                    'colors' => [
                        'foreground' => $colors['content']['text'],
                        'background' => $colors['content']['background'],
                    ],
                    'ratio' => $check['ratio'],
                    'minimum' => self::WCAG_AA_NORMAL,
                ];
            }
        }

        // Check heading on background
        if (!empty($colors['content']['heading']) && !empty($colors['content']['background'])) {
            $check = self::checkContrast($colors['content']['heading'], $colors['content']['background']);

            if ($check['ratio'] < self::WCAG_AA_LARGE) {
                $warnings[] = [
                    'type' => 'contrast',
                    'severity' => 'medium',
                    'message' => 'Heading contrast is too low',
                    'colors' => [
                        'foreground' => $colors['content']['heading'],
                        'background' => $colors['content']['background'],
                    ],
                    'ratio' => $check['ratio'],
                    'minimum' => self::WCAG_AA_LARGE,
                ];
            }
        }

        // Check primary button text on button background
        if (!empty($colors['buttons']['primary']['text']) && !empty($colors['buttons']['primary']['background'])) {
            $check = self::checkContrast($colors['buttons']['primary']['text'], $colors['buttons']['primary']['background']);

            if ($check['ratio'] < self::WCAG_AA_NORMAL) {
                $warnings[] = [
                    'type' => 'contrast',
                    'severity' => 'high',
                    'message' => 'Primary button text contrast is too low',
                    'colors' => [
                        'foreground' => $colors['buttons']['primary']['text'],
                        'background' => $colors['buttons']['primary']['background'],
                    ],
                    'ratio' => $check['ratio'],
                    'minimum' => self::WCAG_AA_NORMAL,
                ];
            }
        }

        // Check input text on input background
        if (!empty($colors['input']['text']) && !empty($colors['input']['background'])) {
            $check = self::checkContrast($colors['input']['text'], $colors['input']['background']);

            if ($check['ratio'] < self::WCAG_AA_NORMAL) {
                $warnings[] = [
                    'type' => 'contrast',
                    'severity' => 'high',
                    'message' => 'Input text contrast is too low',
                    'colors' => [
                        'foreground' => $colors['input']['text'],
                        'background' => $colors['input']['background'],
                    ],
                    'ratio' => $check['ratio'],
                    'minimum' => self::WCAG_AA_NORMAL,
                ];
            }
        }

        // Check sidebar text on sidebar background
        if (!empty($colors['sidebar']['text']) && !empty($colors['sidebar']['background'])) {
            $check = self::checkContrast($colors['sidebar']['text'], $colors['sidebar']['background']);

            if ($check['ratio'] < self::WCAG_AA_NORMAL) {
                $warnings[] = [
                    'type' => 'contrast',
                    'severity' => 'high',
                    'message' => 'Sidebar text contrast is too low',
                    'colors' => [
                        'foreground' => $colors['sidebar']['text'],
                        'background' => $colors['sidebar']['background'],
                    ],
                    'ratio' => $check['ratio'],
                    'minimum' => self::WCAG_AA_NORMAL,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'warnings' => $warnings,
            'errors' => $errors,
            'summary' => [
                'total_checks' => count($warnings) + count($errors),
                'passed' => 0,
                'warnings' => count($warnings),
                'errors' => count($errors),
            ],
        ];
    }

    /**
     * Parse color string to RGB array
     *
     * @param string $color Color string (hex, rgb, rgba)
     * @return array|null RGB array [r, g, b] or null if invalid
     */
    private static function parseColor(string $color): ?array
    {
        $color = trim($color);

        // HEX format (#RGB or #RRGGBB)
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color, $matches)) {
            $hex = $matches[1];

            if (strlen($hex) === 3) {
                $r = hexdec(str_repeat($hex[0], 2));
                $g = hexdec(str_repeat($hex[1], 2));
                $b = hexdec(str_repeat($hex[2], 2));
            } else {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
            }

            return [$r, $g, $b];
        }

        // RGB/RGBA format
        if (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*[\d.]+)?\s*\)$/i', $color, $matches)) {
            return [(int)$matches[1], (int)$matches[2], (int)$matches[3]];
        }

        return null;
    }

    /**
     * Calculate relative luminance
     *
     * @param array $rgb RGB array [r, g, b]
     * @return float Luminance value
     */
    private static function getLuminance(array $rgb): float
    {
        [$r, $g, $b] = $rgb;

        // Convert to relative values (0-1)
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        // Apply gamma correction
        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Calculate luminance using ITU-R BT.709 coefficients
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Calculate contrast ratio between two luminance values
     *
     * @param float $l1 Luminance 1
     * @param float $l2 Luminance 2
     * @return float Contrast ratio
     */
    private static function calculateContrastRatio(float $l1, float $l2): float
    {
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Get WCAG level for a given contrast ratio
     *
     * @param float $ratio Contrast ratio
     * @return string WCAG level (AAA, AA, Fail)
     */
    private static function getWCAGLevel(float $ratio): string
    {
        if ($ratio >= self::WCAG_AAA_NORMAL) {
            return 'AAA';
        }

        if ($ratio >= self::WCAG_AA_NORMAL) {
            return 'AA';
        }

        if ($ratio >= self::WCAG_AA_LARGE) {
            return 'AA (Large Text Only)';
        }

        return 'Fail';
    }

    /**
     * Get recommendation based on contrast ratio
     *
     * @param float $ratio Contrast ratio
     * @return string Recommendation text
     */
    private static function getRecommendation(float $ratio): string
    {
        if ($ratio >= self::WCAG_AAA_NORMAL) {
            return 'Excellent contrast! Exceeds all WCAG standards.';
        }

        if ($ratio >= self::WCAG_AA_NORMAL) {
            return 'Good contrast. Meets WCAG AA standards for normal text.';
        }

        if ($ratio >= self::WCAG_AA_LARGE) {
            return 'Fair contrast. Only suitable for large text (18pt or 14pt bold).';
        }

        $needed = self::WCAG_AA_NORMAL - $ratio;
        return sprintf('Poor contrast. Needs to increase by %.1f to meet WCAG AA standards.', $needed);
    }
}
