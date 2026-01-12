<?php

namespace Bookando\Tests\Unit\Core\Licensing;

use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Service\ActivityLogger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LicenseManagerFeatureFlagTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_feature_enabled_is_deterministic_without_logging_in_production(): void
    {
        if (!defined('BOOKANDO_DEV')) {
            define('BOOKANDO_DEV', false);
        }

        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', false);
        }

        $spyWpdb = new \Bookando_Test_SpyWpdb();
        $spyWpdb->registerLookup('wp_bookando_activity_log', 'wp_bookando_activity_log');

        global $wpdb;
        $wpdb = $spyWpdb;

        $this->resetLoggerCache();

        LicenseManager::clear();
        LicenseManager::setLicenseData([
            'key'      => 'prod-key',
            'plan'     => null,
            'modules'  => [],
            'features' => ['feature-alpha'],
        ]);

        $this->assertTrue(LicenseManager::isFeatureEnabled('feature-alpha'));
        $this->assertTrue(LicenseManager::isFeatureEnabled('feature-alpha'));
        $this->assertFalse(LicenseManager::isFeatureEnabled('feature-beta'));

        $this->assertSame([], $spyWpdb->inserted);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_feature_enabled_logs_when_wp_debug_is_enabled(): void
    {
        if (!defined('BOOKANDO_DEV')) {
            define('BOOKANDO_DEV', false);
        }

        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }

        $spyWpdb = new \Bookando_Test_SpyWpdb();
        $spyWpdb->registerLookup('wp_bookando_activity_log', 'wp_bookando_activity_log');

        global $wpdb;
        $wpdb = $spyWpdb;

        $this->resetLoggerCache();

        LicenseManager::clear();
        LicenseManager::setLicenseData([
            'key'      => 'prod-key',
            'plan'     => null,
            'modules'  => [],
            'features' => ['feature-alpha'],
        ]);

        LicenseManager::isFeatureEnabled('feature-alpha');

        $this->assertNotEmpty($spyWpdb->inserted);
        $record = $spyWpdb->inserted[array_key_last($spyWpdb->inserted)];

        $this->assertSame('wp_bookando_activity_log', $record['table']);
        $this->assertSame('license.debug', $record['data']['context']);
        $payload = json_decode((string) $record['data']['payload'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('feature-alpha', $payload['feature']);
    }

    private function resetLoggerCache(): void
    {
        $reflection = new ReflectionClass(ActivityLogger::class);
        $property   = $reflection->getProperty('tableExists');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
