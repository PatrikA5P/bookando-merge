<?php

declare(strict_types=1);

namespace Bookando\Core\Api;

use Bookando\Core\Api\Response;
use WP_Error;

/**
 * Health Check API
 *
 * Provides health and readiness endpoints for cloud deployments,
 * load balancers, and Kubernetes/ECS health probes.
 *
 * @package Bookando\Core\Api
 */
class HealthApi
{
    /**
     * Register health check routes
     *
     * @return void
     */
    public static function registerRoutes(): void
    {
        // Health check endpoint (liveness probe)
        register_rest_route('bookando/v1', '/health', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'health'],
            'permission_callback' => '__return_true', // Public endpoint
            'show_in_index'       => false,
        ]);

        // Readiness check endpoint (readiness probe)
        register_rest_route('bookando/v1', '/ready', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'ready'],
            'permission_callback' => '__return_true', // Public endpoint
            'show_in_index'       => false,
        ]);
    }

    /**
     * Health check endpoint
     *
     * Returns application health status with dependency checks.
     * Used by load balancers and orchestration platforms for liveness probes.
     *
     * @return \WP_REST_Response
     */
    public static function health(): \WP_REST_Response
    {
        $checks = [
            'database' => self::checkDatabase(),
            'cache'    => self::checkCache(),
            'storage'  => self::checkStorage(),
        ];

        $allHealthy = !in_array(false, $checks, true);

        $response = [
            'status'    => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => current_time('c'),
            'version'   => defined('BOOKANDO_VERSION') ? BOOKANDO_VERSION : 'unknown',
            'checks'    => [
                'database' => $checks['database'] ? 'ok' : 'fail',
                'cache'    => $checks['cache'] ? 'ok' : 'fail',
                'storage'  => $checks['storage'] ? 'ok' : 'fail',
            ],
        ];

        $status = $allHealthy ? 200 : 503;
        return Response::ok($response, [], $status);
    }

    /**
     * Readiness check endpoint
     *
     * Returns readiness status for accepting traffic.
     * Used by Kubernetes/ECS readiness probes.
     *
     * @return \WP_REST_Response
     */
    public static function ready(): \WP_REST_Response
    {
        // Check if application is fully initialized
        $ready = defined('BOOKANDO_PLUGIN_FILE') && did_action('plugins_loaded');

        $response = [
            'ready'             => $ready,
            'startup_complete'  => did_action('init') > 0,
            'timestamp'         => current_time('c'),
        ];

        $status = $ready ? 200 : 503;
        return Response::ok($response, [], $status);
    }

    /**
     * Check database connectivity
     *
     * @return bool
     */
    private static function checkDatabase(): bool
    {
        global $wpdb;

        try {
            // Simple query to test connection
            $result = $wpdb->get_var("SELECT 1");
            return $result === '1';
        } catch (\Throwable $e) {
            error_log('Health check - Database error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check cache availability
     *
     * @return bool
     */
    private static function checkCache(): bool
    {
        try {
            $testKey = 'bookando_health_check_' . time();
            $testValue = 'test';

            // Test write
            wp_cache_set($testKey, $testValue, 'bookando_health', 10);

            // Test read
            $retrieved = wp_cache_get($testKey, 'bookando_health');

            // Cleanup
            wp_cache_delete($testKey, 'bookando_health');

            return $retrieved === $testValue;
        } catch (\Throwable $e) {
            error_log('Health check - Cache error: ' . $e->getMessage());
            return true; // Cache is optional, don't fail health check
        }
    }

    /**
     * Check storage/filesystem availability
     *
     * @return bool
     */
    private static function checkStorage(): bool
    {
        try {
            $uploadDir = wp_upload_dir();

            if (!empty($uploadDir['error'])) {
                error_log('Health check - Upload directory error: ' . $uploadDir['error']);
                return false;
            }

            $basedir = $uploadDir['basedir'];

            // Check if directory is writable
            if (!is_writable($basedir)) {
                error_log('Health check - Upload directory not writable: ' . $basedir);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            error_log('Health check - Storage error: ' . $e->getMessage());
            return false;
        }
    }
}
