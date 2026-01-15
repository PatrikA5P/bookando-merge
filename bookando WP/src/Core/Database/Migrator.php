<?php

declare(strict_types=1);

namespace Bookando\Core\Database;

use Bookando\Core\Service\ActivityLogger;

/**
 * Database migration runner.
 *
 * Handles execution of SQL migration files with proper error handling
 * and rollback support.
 */
class Migrator
{
    /**
     * Runs a specific migration file.
     *
     * @param string $migrationFile Migration filename (e.g., '001_add_foreign_keys_and_sync_columns.sql')
     * @return bool True on success, false on failure
     */
    public static function runMigration(string $migrationFile): bool
    {
        global $wpdb;

        $migrationPath = BOOKANDO_PLUGIN_DIR . 'database/migrations/' . $migrationFile;

        if (!file_exists($migrationPath)) {
            ActivityLogger::error(
                'core.database',
                "Migration file not found: {$migrationFile}",
                ['path' => $migrationPath]
            );
            return false;
        }

        // Read SQL file
        $sql = file_get_contents($migrationPath);
        if ($sql === false) {
            ActivityLogger::error(
                'core.database',
                "Failed to read migration file: {$migrationFile}"
            );
            return false;
        }

        // Replace wp_ prefix with actual prefix
        $sql = str_replace('wp_', $wpdb->prefix, $sql);

        // Remove comments and split into statements
        $statements = self::parseSQL($sql);

        // Execute each statement
        $wpdb->query('START TRANSACTION');

        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) {
                    continue;
                }

                $result = $wpdb->query($statement);

                // Check for errors (but allow warnings for IF NOT EXISTS)
                if ($result === false && !self::isAcceptableError($wpdb->last_error)) {
                    throw new \Exception("SQL Error: {$wpdb->last_error}\nStatement: {$statement}");
                }
            }

            $wpdb->query('COMMIT');

            ActivityLogger::info(
                'core.database',
                "Migration successful: {$migrationFile}",
                ['statements_executed' => count($statements)]
            );

            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');

            ActivityLogger::error(
                'core.database',
                "Migration failed: {$migrationFile}",
                [
                    'error' => $e->getMessage(),
                    'file' => $migrationPath
                ]
            );

            return false;
        }
    }

    /**
     * Parses SQL file into individual statements.
     *
     * @param string $sql Raw SQL content
     * @return array Array of SQL statements
     */
    private static function parseSQL(string $sql): array
    {
        // Remove SQL comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Split by semicolon (statement delimiter)
        $statements = explode(';', $sql);

        return array_filter(array_map('trim', $statements));
    }

    /**
     * Checks if a database error is acceptable (e.g., duplicate constraint).
     *
     * @param string $error Error message
     * @return bool
     */
    private static function isAcceptableError(string $error): bool
    {
        // MySQL errors that are OK during migrations
        $acceptableErrors = [
            'Duplicate key name',
            'Duplicate column name',
            'already exists',
            'Can\'t DROP', // When constraint doesn't exist
        ];

        foreach ($acceptableErrors as $acceptable) {
            if (stripos($error, $acceptable) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runs migration 001 (Foreign Keys + Sync Columns).
     *
     * @return bool
     */
    public static function runMigration001(): bool
    {
        return self::runMigration('001_add_foreign_keys_and_sync_columns.sql');
    }

    /**
     * Runs migration 002 (Queue Table).
     *
     * @return bool
     */
    public static function runMigration002(): bool
    {
        return Migration002_CreateQueueTable::up();
    }

    /**
     * Runs migration 003 (Time Tracking & Shift Management).
     *
     * @return bool
     */
    public static function runMigration003(): bool
    {
        return Migration003_TimeTrackingAndShiftManagement::up();
    }

    /**
     * Runs migration 004 (Module Slug StudlyCase Conversion).
     *
     * @return bool
     */
    public static function runMigration004(): bool
    {
        return Migration004_ModuleSlugStudlyCase::up();
    }

    /**
     * Run all pending migrations.
     *
     * @return array Results of each migration
     */
    public static function runAllMigrations(): array
    {
        $results = [];

        // Check which migrations need to run
        $migrations = [
            '002' => 'runMigration002',
            '003' => 'runMigration003',
            '004' => 'runMigration004',
        ];

        foreach ($migrations as $number => $method) {
            if (self::shouldRunMigration($number)) {
                $results[$number] = [
                    'executed' => true,
                    'success' => self::$method(),
                ];

                if ($results[$number]['success']) {
                    self::markMigrationAsRun($number);
                }
            } else {
                $results[$number] = [
                    'executed' => false,
                    'reason' => 'Already run',
                ];
            }
        }

        return $results;
    }

    /**
     * Check if a migration should run.
     *
     * @param string $migrationNumber
     * @return bool
     */
    private static function shouldRunMigration(string $migrationNumber): bool
    {
        $option_name = 'bookando_migration_' . $migrationNumber . '_executed';
        return !get_option($option_name, false);
    }

    /**
     * Mark a migration as executed.
     *
     * @param string $migrationNumber
     * @return void
     */
    private static function markMigrationAsRun(string $migrationNumber): void
    {
        $option_name = 'bookando_migration_' . $migrationNumber . '_executed';
        update_option($option_name, time());

        ActivityLogger::info(
            'core.database',
            "Migration {$migrationNumber} marked as executed"
        );
    }
}
