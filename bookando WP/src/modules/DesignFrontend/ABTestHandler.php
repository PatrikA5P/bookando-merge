<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * A/B Test Handler
 *
 * Manages A/B testing for shortcodes
 */
class ABTestHandler
{
    /**
     * Get active A/B test for shortcode type
     */
    public static function getActiveTest(string $shortcodeType): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_ab_tests';

        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE shortcode_type = %s AND status = 'active' LIMIT 1",
            $shortcodeType
        ), ARRAY_A);

        if ($test) {
            $test['variant_a_config'] = !empty($test['variant_a_config']) ? json_decode($test['variant_a_config'], true) : [];
            $test['variant_b_config'] = !empty($test['variant_b_config']) ? json_decode($test['variant_b_config'], true) : [];
        }

        return $test ?: null;
    }

    /**
     * Determine which variant to show
     */
    public static function getVariant(array $test): string
    {
        $sessionKey = 'bookando_ab_test_' . $test['id'];

        // Check if user already has assigned variant
        if (isset($_SESSION[$sessionKey])) {
            return $_SESSION[$sessionKey];
        }

        // Assign variant based on split percentage
        $rand = rand(1, 100);
        $variant = $rand <= $test['split_percentage'] ? 'a' : 'b';

        // Store in session
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION[$sessionKey] = $variant;

        return $variant;
    }

    /**
     * Apply A/B test variant to shortcode attributes
     */
    public static function applyVariant(string $shortcodeType, array $atts): array
    {
        $test = self::getActiveTest($shortcodeType);
        if (!$test) {
            return $atts;
        }

        $variant = self::getVariant($test);
        $variantConfig = $variant === 'a' ? $test['variant_a_config'] : $test['variant_b_config'];

        // Track impression
        self::trackImpression($test['id'], $variant);

        // Merge variant config with atts (variant config has priority)
        return array_merge($atts, $variantConfig);
    }

    /**
     * Track impression
     */
    public static function trackImpression(int $testId, string $variant): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_ab_tests';

        $field = $variant === 'a' ? 'impressions_a' : 'impressions_b';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET {$field} = {$field} + 1 WHERE id = %d",
            $testId
        ));
    }

    /**
     * Track conversion
     */
    public static function trackConversion(string $shortcodeType): void
    {
        $test = self::getActiveTest($shortcodeType);
        if (!$test) {
            return;
        }

        $sessionKey = 'bookando_ab_test_' . $test['id'];
        if (!isset($_SESSION[$sessionKey])) {
            return;
        }

        $variant = $_SESSION[$sessionKey];
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_ab_tests';

        $field = $variant === 'a' ? 'conversions_a' : 'conversions_b';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET {$field} = {$field} + 1 WHERE id = %d",
            $test['id']
        ));

        // Check if we should determine winner
        self::checkForWinner($test['id']);
    }

    /**
     * Create A/B test
     */
    public static function createTest(array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_ab_tests';

        $wpdb->insert($table, [
            'name' => sanitize_text_field($data['name']),
            'shortcode_type' => sanitize_text_field($data['shortcode_type']),
            'variant_a_config' => wp_json_encode($data['variant_a_config'] ?? []),
            'variant_b_config' => wp_json_encode($data['variant_b_config'] ?? []),
            'split_percentage' => (int)($data['split_percentage'] ?? 50),
            'status' => 'active',
            'started_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
        ]);

        return (int)$wpdb->insert_id;
    }

    /**
     * Stop test and determine winner
     */
    public static function stopTest(int $testId): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_ab_tests';

        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $testId
        ), ARRAY_A);

        if (!$test) {
            return;
        }

        // Calculate conversion rates
        $crA = $test['impressions_a'] > 0 ? ($test['conversions_a'] / $test['impressions_a']) : 0;
        $crB = $test['impressions_b'] > 0 ? ($test['conversions_b'] / $test['impressions_b']) : 0;

        $winner = $crA > $crB ? 'a' : ($crB > $crA ? 'b' : null);

        $wpdb->update($table, [
            'status' => 'completed',
            'winner' => $winner,
            'ended_at' => current_time('mysql'),
        ], ['id' => $testId]);
    }

    /**
     * Check if test has enough data to determine winner
     */
    protected static function checkForWinner(int $testId): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_ab_tests';

        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $testId
        ), ARRAY_A);

        if (!$test) {
            return;
        }

        $totalImpressions = $test['impressions_a'] + $test['impressions_b'];
        $totalConversions = $test['conversions_a'] + $test['conversions_b'];

        // Auto-stop after 1000 impressions or 100 conversions
        if ($totalImpressions >= 1000 || $totalConversions >= 100) {
            self::stopTest($testId);
        }
    }

    /**
     * Get all tests
     */
    public static function getAllTests(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_ab_tests';

        $tests = $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY created_at DESC",
            ARRAY_A
        );

        foreach ($tests as &$test) {
            $test['variant_a_config'] = !empty($test['variant_a_config']) ? json_decode($test['variant_a_config'], true) : [];
            $test['variant_b_config'] = !empty($test['variant_b_config']) ? json_decode($test['variant_b_config'], true) : [];

            // Calculate conversion rates
            $test['cr_a'] = $test['impressions_a'] > 0 ? round(($test['conversions_a'] / $test['impressions_a']) * 100, 2) : 0;
            $test['cr_b'] = $test['impressions_b'] > 0 ? round(($test['conversions_b'] / $test['impressions_b']) * 100, 2) : 0;
        }

        return $tests ?: [];
    }
}
