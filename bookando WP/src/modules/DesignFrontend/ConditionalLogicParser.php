<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend;

/**
 * Conditional Logic Parser
 *
 * Parses and evaluates conditional logic in shortcodes
 */
class ConditionalLogicParser
{
    /**
     * Apply conditional logic to shortcode attributes
     *
     * @param array $atts Original shortcode attributes
     * @return array Modified attributes based on conditions
     */
    public static function apply(array $atts): array
    {
        $user = self::getCurrentUser();
        $finalAtts = $atts;

        // Process conditional attributes
        foreach ($atts as $key => $value) {
            // if_logged_in
            if ($key === 'if_logged_in' && $user) {
                $finalAtts = array_merge($finalAtts, self::parseConditionalValue($value));
                unset($finalAtts['if_logged_in']);
            }

            // if_not_logged_in
            if ($key === 'if_not_logged_in' && !$user) {
                $finalAtts = array_merge($finalAtts, self::parseConditionalValue($value));
                unset($finalAtts['if_not_logged_in']);
            }

            // if_role_XXX
            if (str_starts_with($key, 'if_role_') && $user) {
                $role = substr($key, 8); // Remove "if_role_" prefix
                if (self::userHasRole($user, $role)) {
                    $finalAtts = array_merge($finalAtts, self::parseConditionalValue($value));
                    unset($finalAtts[$key]);
                }
            }

            // if_user_XXX (specific user ID)
            if (str_starts_with($key, 'if_user_') && $user) {
                $userId = (int)substr($key, 8);
                if ($user['id'] == $userId) {
                    $finalAtts = array_merge($finalAtts, self::parseConditionalValue($value));
                    unset($finalAtts[$key]);
                }
            }

            // if_has_bookings (customer has active bookings)
            if ($key === 'if_has_bookings' && $user) {
                if (self::userHasBookings($user['id'])) {
                    $finalAtts = array_merge($finalAtts, self::parseConditionalValue($value));
                    unset($finalAtts['if_has_bookings']);
                }
            }

            // if_no_bookings (customer has no bookings)
            if ($key === 'if_no_bookings' && $user) {
                if (!self::userHasBookings($user['id'])) {
                    $finalAtts = array_merge($finalAtts, self::parseConditionalValue($value));
                    unset($finalAtts['if_no_bookings']);
                }
            }

            // if_date_range
            if ($key === 'if_date_range') {
                $ranges = explode('|', $value);
                foreach ($ranges as $range) {
                    if (self::isInDateRange($range)) {
                        // Apply date-specific config
                        // Format: "2024-12-01:2024-12-25:featured=1,category=1"
                        $parts = explode(':', $range);
                        if (count($parts) >= 3) {
                            $config = self::parseConditionalValue($parts[2]);
                            $finalAtts = array_merge($finalAtts, $config);
                        }
                        break;
                    }
                }
                unset($finalAtts['if_date_range']);
            }

            // if_url_param
            if ($key === 'if_url_param') {
                // Format: "source=facebook:category=1,featured=1"
                $conditions = explode('|', $value);
                foreach ($conditions as $condition) {
                    $parts = explode(':', $condition);
                    if (count($parts) >= 2) {
                        [$param, $value, $config] = array_pad($parts, 3, null);
                        [$paramName, $paramValue] = explode('=', $param);
                        if (isset($_GET[$paramName]) && $_GET[$paramName] == $paramValue && $config) {
                            $finalAtts = array_merge($finalAtts, self::parseConditionalValue($config));
                        }
                    }
                }
                unset($finalAtts['if_url_param']);
            }
        }

        return $finalAtts;
    }

    /**
     * Parse conditional value string into array
     * Example: "category=1,featured=1" => ['category' => '1', 'featured' => '1']
     */
    protected static function parseConditionalValue(string $value): array
    {
        $result = [];
        $pairs = explode(',', $value);

        foreach ($pairs as $pair) {
            $parts = explode('=', trim($pair), 2);
            if (count($parts) === 2) {
                $result[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $result;
    }

    /**
     * Get current frontend user
     */
    protected static function getCurrentUser(): ?array
    {
        // Check for session token in cookie or header
        $token = null;

        if (isset($_COOKIE['bookando_session'])) {
            $token = $_COOKIE['bookando_session'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        }

        if (!$token) {
            return null;
        }

        return AuthHandler::validateSession($token);
    }

    /**
     * Check if user has specific role
     */
    protected static function userHasRole(?array $user, string $role): bool
    {
        if (!$user) {
            return false;
        }

        $roles = !empty($user['roles']) ? json_decode($user['roles'], true) : [];
        if (!is_array($roles)) {
            return false;
        }

        // Check both "customer" and "bookando_customer" formats
        return in_array($role, $roles) || in_array("bookando_{$role}", $roles);
    }

    /**
     * Check if user has bookings
     */
    protected static function userHasBookings(int $userId): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_appointments';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE customer_id = %d AND status IN ('approved', 'confirmed', 'pending')",
            $userId
        ));

        return (int)$count > 0;
    }

    /**
     * Check if current date is in range
     * Format: "2024-12-01:2024-12-25"
     */
    protected static function isInDateRange(string $range): bool
    {
        $parts = explode(':', $range);
        if (count($parts) < 2) {
            return false;
        }

        $start = strtotime($parts[0]);
        $end = strtotime($parts[1]);
        $now = time();

        return $now >= $start && $now <= $end;
    }

    /**
     * Check if conditional logic exists in attributes
     */
    public static function hasConditionals(array $atts): bool
    {
        foreach (array_keys($atts) as $key) {
            if (str_starts_with($key, 'if_')) {
                return true;
            }
        }
        return false;
    }
}
