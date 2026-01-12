<?php

namespace Bookando\Tests\License;

use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Manager\ModuleManager;
use Bookando\Core\Manager\ModuleStateRepository;
use PHPUnit\Framework\TestCase;
use WP_Error;

/**
 * @covers \Bookando\Core\Licensing\LicenseManager
 */
final class LicenseManagerAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (function_exists('bookando_test_reset_stubs')) {
            bookando_test_reset_stubs();
        }

        LicenseManager::clear();
        $this->resetSingleton(ModuleManager::class);
        $this->resetSingleton(ModuleStateRepository::class);
        delete_option('bookando_module_installed_at_appointments');
    }

    protected function tearDown(): void
    {
        LicenseManager::clear();
        $this->resetSingleton(ModuleManager::class);
        $this->resetSingleton(ModuleStateRepository::class);
        delete_option('bookando_module_installed_at_appointments');

        parent::tearDown();
    }

    public function test_module_is_not_allowed_when_grace_period_expired(): void
    {
        LicenseManager::setLicenseData([
            'key'      => 'demo',
            'plan'     => null,
            'modules'  => [],
            'features' => [],
        ]);

        $expired = time() - (LicenseManager::GRACE_PERIOD_DAYS + 2) * DAY_IN_SECONDS;
        update_option('bookando_module_installed_at_appointments', $expired);

        $this->assertFalse(LicenseManager::isModuleAllowed('appointments'));
    }

    public function test_module_is_allowed_when_plan_includes_it(): void
    {
        LicenseManager::setLicenseData([
            'key'      => 'demo',
            'plan'     => 'starter',
            'modules'  => [],
            'features' => [],
        ]);

        $this->assertTrue(LicenseManager::isModuleAllowed('appointments'));
    }

    public function test_feature_checks_respect_license_payload(): void
    {
        LicenseManager::setLicenseData([
            'key'      => 'demo',
            'plan'     => 'starter',
            'modules'  => [],
            'features' => ['rest_api_read'],
        ]);

        $this->assertTrue(LicenseManager::isFeatureEnabled('rest_api_read'));
        $this->assertFalse(LicenseManager::isFeatureEnabled('rest_api_write'));
    }

    public function test_ensure_feature_returns_payment_required_error(): void
    {
        LicenseManager::setLicenseData([
            'key'      => 'demo',
            'plan'     => 'starter',
            'modules'  => [],
            'features' => [],
        ]);

        $result = LicenseManager::ensureFeature('appointments', 'rest_api_write');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('feature_not_available', $result->get_error_code());
        $this->assertSame(402, $result->get_error_data()['status']);
        $this->assertSame('appointments', $result->get_error_data()['module']);
    }

    /**
     * @param class-string $class
     */
    private function resetSingleton(string $class): void
    {
        $reflection = new \ReflectionClass($class);
        if ($reflection->hasProperty('instance')) {
            $property = $reflection->getProperty('instance');
            $property->setAccessible(true);
            $property->setValue(null, null);
        }
    }
}
