<?php

declare(strict_types=1);

namespace Bookando\Core\Middleware;

use Bookando\Core\Config\EnvLoader;

/**
 * Security Headers Middleware
 *
 * Adds security-related HTTP headers to protect against common web vulnerabilities:
 * - XSS (Cross-Site Scripting)
 * - Clickjacking
 * - MIME sniffing
 * - Content injection
 * - Information leakage
 *
 * @package Bookando\Core\Middleware
 */
class SecurityHeadersMiddleware
{
    /**
     * Apply security headers to the current response
     *
     * WordPress' `send_headers` action passes the global `WP` instance as the
     * first argument. The middleware can also be executed manually with a plain
     * options array. To keep the public API flexible we accept both and ignore
     * the `WP` instance when it is provided via the hook.
     *
     * @param array<string, mixed>|\WP|null $options Override default header values
     * @return void
     */
    public static function apply($options = []): void
    {
        if ($options instanceof \WP) {
            $options = [];
        }

        if (!is_array($options)) {
            $options = [];
        }

        // Don't apply in AJAX or REST API requests (they have their own security)
        if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            self::applyApiHeaders();
            return;
        }

        // Get environment-specific configuration
        $isDev = EnvLoader::isDevelopment();
        $isProd = EnvLoader::isProduction();

        // X-Frame-Options: Prevent clickjacking
        $frameOptions = $options['x_frame_options'] ?? ($isDev ? 'SAMEORIGIN' : 'SAMEORIGIN');
        header("X-Frame-Options: {$frameOptions}");

        // X-Content-Type-Options: Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');

        // X-XSS-Protection: Legacy XSS filter (for older browsers)
        header('X-XSS-Protection: 1; mode=block');

        // Referrer-Policy: Control referrer information
        $referrerPolicy = $options['referrer_policy'] ?? 'strict-origin-when-cross-origin';
        header("Referrer-Policy: {$referrerPolicy}");

        // Content-Security-Policy: Restrict resource loading
        // Note: WordPress admin needs permissive CSP due to inline scripts
        $csp = $options['csp'] ?? self::buildContentSecurityPolicy($isDev);
        if ($csp) {
            header("Content-Security-Policy: {$csp}");
        }

        // Permissions-Policy (formerly Feature-Policy): Control browser features
        $permissionsPolicy = $options['permissions_policy'] ?? self::buildPermissionsPolicy();
        if ($permissionsPolicy) {
            header("Permissions-Policy: {$permissionsPolicy}");
        }

        // Strict-Transport-Security: Force HTTPS (only on HTTPS connections)
        if (is_ssl() && $isProd) {
            $hstsMaxAge = $options['hsts_max_age'] ?? 31536000; // 1 year
            header("Strict-Transport-Security: max-age={$hstsMaxAge}; includeSubDomains");
        }

        // Remove information disclosure headers
        header_remove('X-Powered-By');
        header_remove('Server');
    }

    /**
     * Apply security headers for REST API responses
     *
     * @return void
     */
    private static function applyApiHeaders(): void
    {
        // CORS headers are handled by WordPress and our CORS middleware
        // Just add basic security headers

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY'); // API responses shouldn't be framed
        header_remove('X-Powered-By');
        header_remove('Server');

        // Cache control for API responses (prevent sensitive data caching)
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }
    }

    /**
     * Build Content Security Policy header value
     *
     * @param bool $isDev Development mode (more permissive)
     * @return string CSP directives
     */
    private static function buildContentSecurityPolicy(bool $isDev): string
    {
        // WordPress admin requires inline scripts and styles
        // So we use a permissive CSP with nonces for critical operations

        $directives = [
            // Default fallback
            "default-src 'self'",

            // Scripts: Allow self, inline (unsafe due to WP), and CDNs
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com",

            // Styles: Allow self, inline (unsafe due to WP), and Google Fonts
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",

            // Images: Allow self, data URIs, and external sources
            "img-src 'self' data: https: http:",

            // Fonts: Allow self and Google Fonts
            "font-src 'self' data: https://fonts.gstatic.com",

            // AJAX/Fetch: Allow self and API endpoints
            "connect-src 'self'",

            // Forms: Only submit to self
            "form-action 'self'",

            // Frames: Disallow embedding (except from same origin)
            "frame-ancestors 'self'",

            // Base URI: Restrict base tag
            "base-uri 'self'",

            // Object/Embed: Disallow plugins
            "object-src 'none'",
        ];

        // In development, allow more sources
        if ($isDev) {
            $directives[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' *";
            $directives[] = "connect-src 'self' ws: wss: *"; // Allow WebSockets for HMR
        }

        return implode('; ', $directives);
    }

    /**
     * Build Permissions-Policy header value
     *
     * Disable unnecessary browser features to reduce attack surface
     *
     * @return string Permissions policy directives
     */
    private static function buildPermissionsPolicy(): string
    {
        $policies = [
            'geolocation=()',          // Disable geolocation
            'microphone=()',           // Disable microphone
            'camera=()',               // Disable camera
            'payment=()',              // Disable payment API
            'usb=()',                  // Disable USB
            'magnetometer=()',         // Disable magnetometer
            'gyroscope=()',            // Disable gyroscope
            'accelerometer=()',        // Disable accelerometer
            'ambient-light-sensor=()', // Disable ambient light sensor
        ];

        return implode(', ', $policies);
    }

    /**
     * Add security headers filter to REST API responses
     *
     * @param \WP_REST_Response $response Response object
     * @return \WP_REST_Response Modified response
     */
    public static function filterRestResponse(\WP_REST_Response $response): \WP_REST_Response
    {
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }

    /**
     * Register security headers middleware
     *
     * @return void
     */
    public static function register(): void
    {
        // Apply headers on every request (early)
        add_action('send_headers', [self::class, 'apply'], 1);

        // Also filter REST API responses
        add_filter('rest_post_dispatch', [self::class, 'filterRestResponse'], 10, 1);
    }
}
