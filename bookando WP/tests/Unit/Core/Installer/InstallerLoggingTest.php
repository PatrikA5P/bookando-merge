<?php

namespace Bookando\Tests\Unit\Core\Installer;

use Bookando\Core\Installer;
use Bookando\Core\Service\ActivityLogger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class InstallerLoggingTest extends TestCase
{
    private \Bookando_Test_SpyWpdb $wpdb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = new \Bookando_Test_SpyWpdb();
        $this->wpdb->registerLookup('wp_bookando_activity_log', 'wp_bookando_activity_log');

        global $wpdb;
        $wpdb = $this->wpdb;

        $this->resetLoggerCache();
    }

    private function resetLoggerCache(): void
    {
        $reflection = new ReflectionClass(ActivityLogger::class);
        $property = $reflection->getProperty('tableExists');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function test_migrate_legacy_tables_logs_drop_when_backup_exists(): void
    {
        $legacyKey = 'wp\\_bookando\\_customers';
        $backupKey = 'wp\\_bookando\\_customers\\_legacy';
        $this->wpdb->registerLookup($legacyKey, 'wp_bookando_customers');
        $this->wpdb->registerLookup($backupKey, 'wp_bookando_customers_legacy');

        $this->invokeMigrateLegacyTables();

        $this->assertNotEmpty($this->wpdb->inserted);
        $record = $this->wpdb->inserted[array_key_last($this->wpdb->inserted)];

        $this->assertSame('wp_bookando_activity_log', $record['table']);
        $this->assertSame('installer.legacy', $record['data']['context']);
        $this->assertSame('Legacy table removed because backup exists', $record['data']['message']);
        $this->assertSame('core', $record['data']['module_slug']);
    }

    public function test_migrate_legacy_tables_logs_warning_when_rename_fails(): void
    {
        $legacyKey = 'wp\\_bookando\\_customers';
        $backupKey = 'wp\\_bookando\\_customers\\_legacy';
        $this->wpdb->registerLookup($legacyKey, 'wp_bookando_customers');
        $this->wpdb->registerLookup($backupKey, null);
        $this->wpdb->renameResult = false;

        $this->invokeMigrateLegacyTables();

        $this->assertNotEmpty($this->wpdb->inserted);
        $record = $this->wpdb->inserted[array_key_last($this->wpdb->inserted)];

        $this->assertSame(ActivityLogger::LEVEL_WARNING, $record['data']['severity']);
        $this->assertSame('Failed to rename legacy table', $record['data']['message']);
        $this->assertSame('core', $record['data']['module_slug']);
    }

    private function invokeMigrateLegacyTables(): void
    {
        $method = new ReflectionMethod(Installer::class, 'migrateLegacyModuleTables');
        $method->setAccessible(true);
        $method->invoke(null);
    }
}
