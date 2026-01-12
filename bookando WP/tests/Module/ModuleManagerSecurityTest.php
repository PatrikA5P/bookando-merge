<?php

namespace Bookando\Tests\Module;

use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Manager\ModuleManager;
use Bookando\Core\Manager\ModuleManifest;
use Bookando\Core\Manager\ModuleStateRepository;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bookando\Core\Manager\ModuleManager
 */
final class ModuleManagerSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (function_exists('bookando_test_reset_stubs')) {
            bookando_test_reset_stubs();
        }

        $this->resetSingleton(ModuleManager::class);
        $this->resetSingleton(ModuleStateRepository::class);
        $this->setManifestCache([]);
        update_option('bookando_active_modules', []);
        delete_option('bookando_module_installed_at_secure');
        \Bookando\Modules\Secure\Module::reset();
    }

    protected function tearDown(): void
    {
        $this->resetSingleton(ModuleManager::class);
        $this->resetSingleton(ModuleStateRepository::class);
        $this->setManifestCache([]);
        update_option('bookando_active_modules', []);
        delete_option('bookando_module_installed_at_secure');

        parent::tearDown();
    }

    public function test_loadModules_blocks_unlicensed_module(): void
    {
        $this->setActiveModules(['secure']);
        $this->setManifestCache([
            'secure' => [
                'license_required' => true,
            ],
        ]);

        LicenseManager::setLicenseData([
            'key'      => 'demo',
            'plan'     => null,
            'modules'  => [],
            'features' => [],
        ]);

        $manager = ModuleManager::instance();
        $manager->loadModules();

        $this->assertSame([], $manager->getAllModules());
        $this->assertSame(0, \Bookando\Modules\Secure\Module::$bootCount);
    }

    public function test_loadModules_runs_only_once_per_request(): void
    {
        $this->setActiveModules(['secure']);
        $this->setManifestCache([
            'secure' => [
                'license_required' => false,
            ],
        ]);

        $manager = ModuleManager::instance();
        $manager->loadModules();
        $manager->loadModules();

        $this->assertSame(1, \Bookando\Modules\Secure\Module::$bootCount);
    }

    public function test_getRemainingTrialDays_returns_zero_after_expiration(): void
    {
        $expired = time() - (LicenseManager::GRACE_PERIOD_DAYS + 1) * DAY_IN_SECONDS;
        update_option('bookando_module_installed_at_secure', $expired);

        $manager = ModuleManager::instance();
        $this->assertSame(0, $manager->getRemainingTrialDays('secure'));
    }

    private function setActiveModules(array $slugs): void
    {
        update_option('bookando_active_modules', $slugs);
    }

    private function setManifestCache(array $overrides): void
    {
        $reflection = new \ReflectionClass(ModuleManifest::class);
        $property = $reflection->getProperty('manifestCache');
        $property->setAccessible(true);

        $cache = [];
        foreach ($overrides as $slug => $data) {
            $cache[$slug] = array_merge([
                'slug' => $slug,
                'name' => ucfirst($slug),
                'version' => '1.0.0',
                'description' => '',
                'group' => null,
                'plan' => null,
                'license_required' => false,
                'features_required' => [],
                'always_active' => false,
                'visible' => true,
                'tenant_required' => false,
                'dependencies' => [],
                'tabs' => [],
            ], $data);
        }

        $property->setValue(null, $cache);
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

namespace Bookando\Modules\Secure {

use Bookando\Core\Base\BaseModule;

final class Module extends BaseModule
{
    public static int $bootCount = 0;

    public static function reset(): void
    {
        self::$bootCount = 0;
    }

    public function register(): void
    {
        self::$bootCount++;
    }
}
}
