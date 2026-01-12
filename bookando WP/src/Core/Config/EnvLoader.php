<?php

declare(strict_types=1);

namespace Bookando\Core\Config;

/**
 * Environment Configuration Loader
 *
 * Lightweight .env file parser for managing environment-specific configuration.
 * Supports production, staging, and development environments.
 *
 * @package Bookando\Core\Config
 */
class EnvLoader
{
    /**
     * Loaded environment variables
     *
     * @var array<string, string>
     */
    private static array $variables = [];

    /**
     * Whether environment has been loaded
     *
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * Load .env file from specified directory
     *
     * @param string $directory Directory containing .env file
     * @param string $filename Filename (default: .env)
     * @return bool Success
     */
    public static function load(string $directory, string $filename = '.env'): bool
    {
        if (self::$loaded) {
            return true; // Already loaded
        }

        $filePath = rtrim($directory, '/') . '/' . $filename;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            // .env file is optional, not an error
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            // Remove quotes from value
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Expand variables ${VAR} or $VAR
            $value = self::expandVariables($value);

            // Store in internal array
            self::$variables[$key] = $value;

            // Also set as environment variable (if not already set)
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$loaded = true;
        return true;
    }

    /**
     * Get environment variable value
     *
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Priority: loaded vars > $_ENV > getenv > default
        if (isset(self::$variables[$key])) {
            return self::$variables[$key];
        }

        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    /**
     * Get environment variable as boolean
     *
     * @param string $key Variable name
     * @param bool $default Default value
     * @return bool
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get environment variable as integer
     *
     * @param string $key Variable name
     * @param int $default Default value
     * @return int
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * Check if variable exists
     *
     * @param string $key Variable name
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::$variables[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Get all loaded variables
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::$variables;
    }

    /**
     * Clear loaded environment (useful for testing)
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$variables = [];
        self::$loaded = false;
    }

    /**
     * Expand variables in value
     *
     * Supports ${VAR} and $VAR syntax
     *
     * @param string $value Value with potential variables
     * @return string Expanded value
     */
    private static function expandVariables(string $value): string
    {
        // ${VAR} syntax
        $value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/', function ($matches) {
            return self::get($matches[1], '');
        }, $value);

        // $VAR syntax (only if followed by non-alphanumeric)
        $value = preg_replace_callback('/\$([A-Z0-9_]+)(?![A-Z0-9_])/', function ($matches) {
            return self::get($matches[1], '');
        }, $value);

        return $value;
    }

    /**
     * Determine current environment
     *
     * @return string Environment name (production, staging, development)
     */
    public static function environment(): string
    {
        // Check WP_ENVIRONMENT_TYPE first (WordPress 5.5+)
        if (defined('WP_ENVIRONMENT_TYPE')) {
            return WP_ENVIRONMENT_TYPE;
        }

        // Check .env
        $env = self::get('APP_ENV', 'production');

        // Check WP_DEBUG as fallback
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return 'development';
        }

        return $env;
    }

    /**
     * Check if running in production
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return self::environment() === 'production';
    }

    /**
     * Check if running in development
     *
     * @return bool
     */
    public static function isDevelopment(): bool
    {
        return in_array(self::environment(), ['development', 'local', 'dev'], true);
    }

    /**
     * Require environment variables
     *
     * Throws exception if any required variable is missing
     *
     * @param string ...$keys Required variable names
     * @return void
     * @throws \RuntimeException
     */
    public static function require(string ...$keys): void
    {
        $missing = [];

        foreach ($keys as $key) {
            if (!self::has($key)) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                sprintf(
                    'Missing required environment variables: %s',
                    implode(', ', $missing)
                )
            );
        }
    }
}
