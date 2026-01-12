<?php

namespace Bookando\Tests\Unit\Core\Licensing;

use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Service\ActivityLogger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LicenseManagerLoggingTest extends TestCase
{
    private \Bookando_Test_SpyWpdb $wpdb;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOKANDO_DEV')) {
            define('BOOKANDO_DEV', true);
        }

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

    public function test_dev_mode_logs_module_allowance(): void
    {
        LicenseManager::isModuleAllowed('calendar');

        $this->assertNotEmpty($this->wpdb->inserted);
        $record = $this->wpdb->inserted[array_key_last($this->wpdb->inserted)];

        $this->assertSame('wp_bookando_activity_log', $record['table']);
        $this->assertSame('license.dev', $record['data']['context']);
        $this->assertSame('Module allowed in dev mode', $record['data']['message']);
        $this->assertSame('calendar', $record['data']['module_slug']);
    }

    public function test_missing_module_manifest_logs_warning(): void
    {
        LicenseManager::getModuleMeta('missing-module');

        $this->assertNotEmpty($this->wpdb->inserted);
        $record = $this->wpdb->inserted[array_key_last($this->wpdb->inserted)];

        $this->assertSame(ActivityLogger::LEVEL_WARNING, $record['data']['severity']);
        $this->assertSame('license.meta', $record['data']['context']);
        $this->assertSame('missing-module', $record['data']['module_slug']);
        $this->assertSame('Module manifest missing', $record['data']['message']);
    }
}
