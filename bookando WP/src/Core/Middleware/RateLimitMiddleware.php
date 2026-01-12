<?php

declare(strict_types=1);

namespace Bookando\Core\Middleware;

use WP_Error;
use WP_REST_Request;

/**
 * Rate Limiting Middleware
 *
 * Protects API endpoints from abuse, DDoS attacks, and excessive usage.
 * Uses WordPress transients for storage (can be swapped to Redis later).
 *
 * @package Bookando\Core\Middleware
 */
class RateLimitMiddleware
{
    /**
     * Default rate limits (requests per time window)
     */
    private const DEFAULT_LIMITS = [
        'read'  => 100,  // GET requests per minute
        'write' => 30,   // POST/PUT/PATCH/DELETE per minute
        'auth'  => 10,   // Authentication attempts per minute
    ];

    /**
     * Time window in seconds
     */
    private const TIME_WINDOW = 60; // 1 minute

    /**
     * Check rate limit for a request
     *
     * @param WP_REST_Request $request The REST request
     * @param string $type Limit type (read/write/auth)
     * @return true|WP_Error True if allowed, WP_Error if rate limited
     */
    public static function check(WP_REST_Request $request, string $type = 'read')
    {
        // Dev bypass for development
        if (defined('BOOKANDO_DEV') && BOOKANDO_DEV) {
            return true;
        }

        $identifier = self::getIdentifier($request);
        $limit = self::getLimit($type);
        $key = self::getCacheKey($identifier, $type);

        // Get current count
        $count = (int) get_transient($key);

        if ($count >= $limit) {
            // Rate limit exceeded
            $retryAfter = self::getRetryAfter($key);

            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    /* translators: %1$d: retry after seconds */
                    __('Rate limit exceeded. Please try again in %1$d seconds.', 'bookando'),
                    $retryAfter
                ),
                [
                    'status' => 429,
                    'headers' => [
                        'X-RateLimit-Limit' => $limit,
                        'X-RateLimit-Remaining' => 0,
                        'X-RateLimit-Reset' => time() + $retryAfter,
                        'Retry-After' => $retryAfter,
                    ],
                ]
            );
        }

        // Increment counter
        if ($count === 0) {
            // First request in window, set with expiry
            set_transient($key, 1, self::TIME_WINDOW);
        } else {
            // Increment existing counter
            set_transient($key, $count + 1, self::TIME_WINDOW);
        }

        // Add rate limit headers to response
        add_filter('rest_post_dispatch', function ($response) use ($limit, $count) {
            if (method_exists($response, 'header')) {
                $response->header('X-RateLimit-Limit', (string) $limit);
                $response->header('X-RateLimit-Remaining', (string) max(0, $limit - $count - 1));
                $response->header('X-RateLimit-Reset', (string) (time() + self::TIME_WINDOW));
            }
            return $response;
        }, 10, 1);

        return true;
    }

    /**
     * Get unique identifier for rate limiting
     *
     * Priority: User ID > IP Address
     *
     * @param WP_REST_Request $request
     * @return string
     */
    private static function getIdentifier(WP_REST_Request $request): string
    {
        // Authenticated users: use user ID
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }

        // Anonymous: use IP address
        $ip = self::getClientIp();
        return 'ip_' . md5($ip);
    }

    /**
     * Get client IP address (handles proxies)
     *
     * @return string
     */
    private static function getClientIp(): string
    {
        // Check for proxy headers (in order of trustworthiness)
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Standard proxy header
            'REMOTE_ADDR',               // Direct connection
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // X-Forwarded-For can contain multiple IPs (client, proxy1, proxy2)
                // Take the first (leftmost) IP which is the original client
                if ($header === 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0'; // Fallback
    }

    /**
     * Get rate limit for a given type
     *
     * Allows filtering via WordPress hooks
     *
     * @param string $type
     * @return int
     */
    private static function getLimit(string $type): int
    {
        $limit = self::DEFAULT_LIMITS[$type] ?? self::DEFAULT_LIMITS['read'];

        /**
         * Filter rate limit for a specific type
         *
         * @param int $limit Current limit
         * @param string $type Limit type (read/write/auth)
         */
        return (int) apply_filters('bookando_rate_limit', $limit, $type);
    }

    /**
     * Get cache key for rate limiting
     *
     * @param string $identifier User/IP identifier
     * @param string $type Limit type
     * @return string
     */
    private static function getCacheKey(string $identifier, string $type): string
    {
        return sprintf('bookando_rate_limit_%s_%s', $type, $identifier);
    }

    /**
     * Get seconds until rate limit resets
     *
     * @param string $key Cache key
     * @return int Seconds
     */
    private static function getRetryAfter(string $key): int
    {
        // Get transient timeout
        $timeout = get_option('_transient_timeout_' . $key);

        if ($timeout === false) {
            return self::TIME_WINDOW;
        }

        return max(1, (int) $timeout - time());
    }

    /**
     * Check if request is a write operation
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public static function isWriteRequest(WP_REST_Request $request): bool
    {
        $method = strtoupper($request->get_method());
        return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    /**
     * Check if request is authentication-related
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public static function isAuthRequest(WP_REST_Request $request): bool
    {
        $route = $request->get_route();

        $authPatterns = [
            '/bookando/v1/auth',
            '/bookando/v1/login',
            '/bookando/v1/token',
            '/bookando/v1/integrations/oauth',
        ];

        foreach ($authPatterns as $pattern) {
            if (strpos($route, $pattern) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply rate limiting to a REST request
     *
     * Automatically determines limit type based on request
     *
     * @param WP_REST_Request $request
     * @return true|WP_Error
     */
    public static function apply(WP_REST_Request $request)
    {
        // Determine limit type
        if (self::isAuthRequest($request)) {
            $type = 'auth';
        } elseif (self::isWriteRequest($request)) {
            $type = 'write';
        } else {
            $type = 'read';
        }

        return self::check($request, $type);
    }

    /**
     * Reset rate limit for an identifier
     *
     * Useful for testing or manual resets
     *
     * @param string $identifier User/IP identifier
     * @param string $type Limit type
     * @return bool
     */
    public static function reset(string $identifier, string $type = 'read'): bool
    {
        $key = self::getCacheKey($identifier, $type);
        return delete_transient($key);
    }

    /**
     * Get current rate limit status for an identifier
     *
     * @param string $identifier User/IP identifier
     * @param string $type Limit type
     * @return array{count: int, limit: int, remaining: int, reset: int}
     */
    public static function getStatus(string $identifier, string $type = 'read'): array
    {
        $key = self::getCacheKey($identifier, $type);
        $count = (int) get_transient($key);
        $limit = self::getLimit($type);

        return [
            'count' => $count,
            'limit' => $limit,
            'remaining' => max(0, $limit - $count),
            'reset' => time() + self::getRetryAfter($key),
        ];
    }
}
