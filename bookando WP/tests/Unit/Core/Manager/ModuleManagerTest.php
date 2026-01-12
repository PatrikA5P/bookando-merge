<?php

namespace {
    if (!defined('BOOKANDO_PLUGIN_DIR')) {
        define('BOOKANDO_PLUGIN_DIR', dirname(__DIR__, 4) . '/');
    }

    if (!defined('DAY_IN_SECONDS')) {
        define('DAY_IN_SECONDS', 86400);
    }
}

namespace Bookando\Tests\Unit\Core\Manager {

    use Bookando\Core\Licensing\LicenseManager;
    use Bookando\Core\Manager\ModuleManager;
    use Bookando\Core\Manager\ModuleManifest;
    use Bookando\Core\Manager\ModuleStateRepository;
    use PHPUnit\Framework\TestCase;

    /**
     * @covers \Bookando\Core\Manager\ModuleManager
     */
    final class ModuleManagerTest extends TestCase
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
            $this->setManifestCache([]);

            \Bookando\Modules\Alpha\Module::reset();
            \Bookando\Modules\Beta\Module::reset();
            \Bookando\Modules\Trial\Module::reset();
            \Bookando\Modules\Gamma\Module::reset();
        }

        public function test_loadModules_initializes_only_modules_allowed_by_license(): void
        {
            $this->setActiveModules(['alpha', 'beta']);

            $this->setManifestCache([
                'alpha' => [
                    'license_required' => true,
                ],
                'beta' => [
                    'license_required' => true,
                    'features_required' => ['advanced_reporting'],
                ],
            ]);

            LicenseManager::setLicenseData([
                'key' => 'demo',
                'plan' => 'pro',
                'modules' => ['alpha', 'beta'],
                'features' => [],
            ]);

            $manager = ModuleManager::instance();
            $manager->loadModules();

            $modules = $manager->getAllModules();

            $this->assertArrayHasKey('alpha', $modules);
            $this->assertInstanceOf(\Bookando\Modules\Alpha\Module::class, $modules['alpha']);
            $this->assertSame(1, \Bookando\Modules\Alpha\Module::$bootCount);

            $this->assertArrayNotHasKey('beta', $modules);
            $this->assertSame(0, \Bookando\Modules\Beta\Module::$bootCount);
        }

        public function test_loadModules_boots_modules_when_required_feature_is_available(): void
        {
            $this->setActiveModules(['beta']);
            $this->setManifestCache([
                'beta' => [
                    'license_required' => true,
                    'features_required' => ['advanced_reporting'],
                ],
            ]);

            LicenseManager::setLicenseData([
                'key' => 'demo',
                'plan' => 'enterprise',
                'modules' => ['beta'],
                'features' => ['advanced_reporting'],
            ]);

            $manager = ModuleManager::instance();
            $manager->loadModules();

            $modules = $manager->getAllModules();

            $this->assertArrayHasKey('beta', $modules);
            $this->assertInstanceOf(\Bookando\Modules\Beta\Module::class, $modules['beta']);
            $this->assertSame(1, \Bookando\Modules\Beta\Module::$bootCount);
        }

        public function test_loadModules_disables_module_when_dependency_missing(): void
        {
            $this->setActiveModules(['gamma']);
            $this->setManifestCache([
                'gamma' => [
                    'dependencies' => ['alpha'],
                ],
            ]);

            $manager = ModuleManager::instance();
            $manager->loadModules();

            $this->assertSame([], $manager->getAllModules());
            $this->assertFalse($manager->isActive('gamma'));

            $this->assertNotFalse(get_option('bookando_module_installed_at_gamma', false));
        }

        public function test_loadModules_skips_legacy_modules_from_activation(): void
        {
            $this->setActiveModules(['alpha', 'legacy_old']);
            $this->setManifestCache([
                'alpha' => [],
            ]);

            $manager = ModuleManager::instance();
            $manager->loadModules();

            $modules = $manager->getAllModules();

            $this->assertArrayHasKey('alpha', $modules);
            $this->assertArrayNotHasKey('legacy_old', $modules);
            $this->assertFalse(get_option('bookando_module_installed_at_legacy_old', false));

            $this->assertSame(['alpha', 'legacy_old'], get_option('bookando_active_modules'));
        }

        public function test_loadModules_respects_grace_period_for_recent_installations(): void
        {
            $this->setActiveModules(['trial']);
            $this->setManifestCache([
                'trial' => [
                    'license_required' => true,
                ],
            ]);

            $recentInstall = time() - (LicenseManager::GRACE_PERIOD_DAYS - 1) * DAY_IN_SECONDS;
            update_option('bookando_module_installed_at_trial', $recentInstall);

            $manager = ModuleManager::instance();
            $manager->loadModules();

            $modules = $manager->getAllModules();

            $this->assertArrayHasKey('trial', $modules);
            $this->assertSame(1, \Bookando\Modules\Trial\Module::$bootCount);
        }

        public function test_loadModules_blocks_modules_after_grace_period_expired(): void
        {
            $this->setActiveModules(['trial']);
            $this->setManifestCache([
                'trial' => [
                    'license_required' => true,
                ],
            ]);

            $expiredInstall = time() - (LicenseManager::GRACE_PERIOD_DAYS + 1) * DAY_IN_SECONDS;
            update_option('bookando_module_installed_at_trial', $expiredInstall);

            $manager = ModuleManager::instance();
            $manager->loadModules();

            $modules = $manager->getAllModules();

            $this->assertArrayNotHasKey('trial', $modules);
            $this->assertSame(0, \Bookando\Modules\Trial\Module::$bootCount);
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
}

namespace Bookando\Modules\Alpha {
    use Bookando\Core\Base\BaseModule;

    class Module extends BaseModule
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

namespace Bookando\Modules\Beta {
    use Bookando\Core\Base\BaseModule;

    class Module extends BaseModule
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

namespace Bookando\Modules\Trial {
    use Bookando\Core\Base\BaseModule;

    class Module extends BaseModule
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

namespace Bookando\Modules\Gamma {
    use Bookando\Core\Base\BaseModule;

    class Module extends BaseModule
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
