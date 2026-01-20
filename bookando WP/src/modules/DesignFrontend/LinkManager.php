<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Link Manager
 *
 * Manages SaaS link generation with UTM tracking
 */
class LinkManager
{
    /**
     * Generate trackable link
     */
    public static function generateLink(array $data): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_generated_links';

        // Build target configuration
        $targetConfig = [
            'category' => !empty($data['category']) ? $data['category'] : null,
            'tag' => !empty($data['tag']) ? $data['tag'] : null,
            'employee' => !empty($data['employee']) ? $data['employee'] : null,
            'location' => !empty($data['location']) ? $data['location'] : null,
            'offer' => !empty($data['offer']) ? $data['offer'] : null,
        ];

        // Generate unique hash
        $hash = substr(md5(uniqid((string)rand(), true)), 0, 8);

        // Insert link
        $wpdb->insert($table, [
            'link_hash' => $hash,
            'target_type' => sanitize_text_field($data['target_type'] ?? 'catalog'),
            'target_config' => wp_json_encode($targetConfig),
            'utm_source' => sanitize_text_field($data['utm_source'] ?? ''),
            'utm_medium' => sanitize_text_field($data['utm_medium'] ?? ''),
            'utm_campaign' => sanitize_text_field($data['utm_campaign'] ?? ''),
            'utm_term' => sanitize_text_field($data['utm_term'] ?? ''),
            'utm_content' => sanitize_text_field($data['utm_content'] ?? ''),
            'expires_at' => !empty($data['expires']) ? $data['expires'] . ' 23:59:59' : null,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ]);

        // Build URLs
        $baseUrl = home_url('/bookando/go/' . $hash);
        $fullUrl = self::buildFullUrl($data['target_type'], $targetConfig, [
            'utm_source' => $data['utm_source'],
            'utm_medium' => $data['utm_medium'] ?? '',
            'utm_campaign' => $data['utm_campaign'] ?? '',
            'utm_term' => $data['utm_term'] ?? '',
            'utm_content' => $data['utm_content'] ?? '',
        ]);

        return [
            'short_link' => $baseUrl,
            'full_link' => $fullUrl,
            'link_hash' => $hash,
            'qr_code' => $data['generate_qr'] ?? false,
        ];
    }

    /**
     * Build full URL with all parameters
     */
    protected static function buildFullUrl(string $targetType, array $targetConfig, array $utm): string
    {
        $baseUrl = home_url('/bookando/' . $targetType);
        $params = [];

        // Add target config
        foreach ($targetConfig as $key => $value) {
            if ($value !== null && $value !== '') {
                $params[$key] = $value;
            }
        }

        // Add UTM params
        foreach ($utm as $key => $value) {
            if ($value !== null && $value !== '') {
                $params[$key] = $value;
            }
        }

        return add_query_arg($params, $baseUrl);
    }

    /**
     * Get all links
     */
    public static function getLinks(int $limit = 50): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_generated_links';

        $links = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        foreach ($links as &$link) {
            $link['short_link'] = home_url('/bookando/go/' . $link['link_hash']);
        }

        return $links ?: [];
    }

    /**
     * Get analytics
     */
    public static function getAnalytics(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_generated_links';

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total_links,
                SUM(click_count) as total_clicks,
                SUM(conversion_count) as total_conversions
            FROM {$table}",
            ARRAY_A
        );

        $avgCr = 0;
        if ($stats['total_clicks'] > 0) {
            $avgCr = round(($stats['total_conversions'] / $stats['total_clicks']) * 100, 1);
        }

        $topLinks = $wpdb->get_results(
            "SELECT * FROM {$table}
             WHERE click_count > 0
             ORDER BY conversion_count DESC, click_count DESC
             LIMIT 10",
            ARRAY_A
        );

        return [
            'total_links' => (int)($stats['total_links'] ?? 0),
            'total_clicks' => (int)($stats['total_clicks'] ?? 0),
            'total_conversions' => (int)($stats['total_conversions'] ?? 0),
            'avg_cr' => $avgCr,
            'top_links' => $topLinks ?: [],
        ];
    }

    /**
     * Track link click
     */
    public static function trackClick(string $hash): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_generated_links';

        // Get link
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE link_hash = %s",
            $hash
        ), ARRAY_A);

        if (!$link) {
            return null;
        }

        // Check expiration
        if ($link['expires_at'] && strtotime($link['expires_at']) < time()) {
            return null;
        }

        // Increment click count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET click_count = click_count + 1 WHERE link_hash = %s",
            $hash
        ));

        return $link;
    }

    /**
     * Track conversion
     */
    public static function trackConversion(string $hash): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_generated_links';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET conversion_count = conversion_count + 1 WHERE link_hash = %s",
            $hash
        ));
    }
}
