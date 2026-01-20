<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Dynamic Filter Helper
 *
 * Resolves dynamic filters based on current page context
 */
class DynamicFilterHelper
{
    /**
     * Resolve dynamic filters in shortcode attributes
     *
     * @param array $atts Shortcode attributes
     * @return array Resolved attributes
     */
    public static function resolve(array $atts): array
    {
        global $post;

        foreach ($atts as $key => $value) {
            if (is_string($value)) {
                // Auto category based on current page
                if ($value === 'auto' || $value === 'current' || $value === 'page') {
                    switch ($key) {
                        case 'category':
                            $atts[$key] = self::getCurrentCategory();
                            break;
                        case 'tag':
                            $atts[$key] = self::getCurrentTags();
                            break;
                        case 'employee':
                            $atts[$key] = self::getCurrentEmployee();
                            break;
                        case 'location':
                            $atts[$key] = self::getCurrentLocation();
                            break;
                    }
                }

                // Current post author (for employee filter)
                if ($value === 'current_author' && $key === 'employee') {
                    if ($post && $post->post_author) {
                        // Map WordPress user to bookando user
                        $atts[$key] = self::getBookandoUserFromWP((int)$post->post_author);
                    }
                }

                // URL parameter pre-fill
                if (str_starts_with($value, 'url:')) {
                    $paramName = substr($value, 4);
                    if (isset($_GET[$paramName])) {
                        $atts[$key] = sanitize_text_field($_GET[$paramName]);
                    }
                }
            }
        }

        return $atts;
    }

    /**
     * Get current page category
     */
    protected static function getCurrentCategory(): string
    {
        // Check if we're on a category archive
        if (is_category()) {
            $category = get_queried_object();
            return (string)$category->term_id;
        }

        // Check post categories
        if (is_single()) {
            $categories = get_the_category();
            if (!empty($categories)) {
                return (string)$categories[0]->term_id;
            }
        }

        // Check custom taxonomy (bookando_offer_category)
        $terms = get_the_terms(get_the_ID(), 'bookando_offer_category');
        if ($terms && !is_wp_error($terms)) {
            return (string)$terms[0]->term_id;
        }

        return '';
    }

    /**
     * Get current page tags
     */
    protected static function getCurrentTags(): string
    {
        $tags = [];

        // Check if we're on a tag archive
        if (is_tag()) {
            $tag = get_queried_object();
            $tags[] = $tag->term_id;
        }

        // Check post tags
        if (is_single()) {
            $postTags = get_the_tags();
            if ($postTags) {
                foreach ($postTags as $tag) {
                    $tags[] = $tag->term_id;
                }
            }
        }

        return !empty($tags) ? '{' . implode(',', $tags) . '}' : '';
    }

    /**
     * Get current employee from page context
     */
    protected static function getCurrentEmployee(): string
    {
        global $post;

        // Check URL parameter
        if (isset($_GET['employee'])) {
            return sanitize_text_field($_GET['employee']);
        }

        // Check custom field
        if ($post) {
            $employee = get_post_meta($post->ID, 'bookando_employee_id', true);
            if ($employee) {
                return (string)$employee;
            }
        }

        // Check if we're on an employee archive/single page
        if (get_post_type() === 'bookando_employee') {
            return (string)get_the_ID();
        }

        return '';
    }

    /**
     * Get current location from page context
     */
    protected static function getCurrentLocation(): string
    {
        global $post;

        // Check URL parameter
        if (isset($_GET['location'])) {
            return sanitize_text_field($_GET['location']);
        }

        // Check custom field
        if ($post) {
            $location = get_post_meta($post->ID, 'bookando_location_id', true);
            if ($location) {
                return (string)$location;
            }
        }

        // Check if we're on a location archive/single page
        if (get_post_type() === 'bookando_location') {
            return (string)get_the_ID();
        }

        return '';
    }

    /**
     * Map WordPress user ID to Bookando user ID
     */
    protected static function getBookandoUserFromWP(int $wpUserId): string
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_users';

        // Try to find by external_id or email
        $wpUser = get_userdata($wpUserId);
        if (!$wpUser) {
            return '';
        }

        // Search by email
        $bookandoUser = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE email = %s AND deleted_at IS NULL LIMIT 1",
            $wpUser->user_email
        ));

        return $bookandoUser ? (string)$bookandoUser : '';
    }

    /**
     * Check if filter should use dynamic resolution
     */
    public static function isDynamic(string $value): bool
    {
        return in_array(strtolower($value), ['auto', 'current', 'page', 'current_author'])
            || str_starts_with($value, 'url:');
    }
}
