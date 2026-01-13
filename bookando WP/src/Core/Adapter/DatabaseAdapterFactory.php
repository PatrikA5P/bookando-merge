<?php

declare(strict_types=1);

namespace Bookando\Core\Adapter;

/**
 * Database Adapter Factory
 *
 * Creates the appropriate DatabaseAdapter based on the environment.
 * Supports:
 * - WordPress Plugin mode (default)
 * - Standalone SaaS mode (future)
 * - Docker/Cloud deployment (future)
 *
 * Usage:
 * ```php
 * $db = DatabaseAdapterFactory::create();
 * $users = $db->query('SELECT * FROM users WHERE tenant_id = %d', [$tenantId]);
 * ```
 *
 * @package Bookando\Core\Adapter
 */
class DatabaseAdapterFactory
{
    /** @var DatabaseAdapter|null Singleton instance */
    private static ?DatabaseAdapter $instance = null;

    /**
     * Create or return singleton DatabaseAdapter
     *
     * @return DatabaseAdapter
     */
    public static function create(): DatabaseAdapter
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Detect environment
        $mode = self::detectMode();

        switch ($mode) {
            case 'wordpress':
                self::$instance = new WordPressDatabaseAdapter();
                break;

            case 'standalone':
                // Future: Implement PDODatabaseAdapter for standalone SaaS
                throw new \RuntimeException(
                    'Standalone mode not yet implemented. ' .
                    'Please run as WordPress plugin or wait for SaaS release.'
                );

            default:
                throw new \RuntimeException("Unknown database mode: {$mode}");
        }

        return self::$instance;
    }

    /**
     * Detect running mode
     *
     * @return string 'wordpress' or 'standalone'
     */
    private static function detectMode(): string
    {
        // Check environment variable first
        $envMode = getenv('BOOKANDO_MODE');
        if ($envMode !== false) {
            return $envMode;
        }

        // Auto-detect: If WordPress functions exist, use WordPress mode
        if (function_exists('wp_get_current_user')) {
            return 'wordpress';
        }

        // Default to standalone (future)
        return 'standalone';
    }

    /**
     * Reset singleton (for testing)
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Set custom adapter (for testing/mocking)
     *
     * @param DatabaseAdapter $adapter
     * @return void
     */
    public static function setAdapter(DatabaseAdapter $adapter): void
    {
        self::$instance = $adapter;
    }
}
