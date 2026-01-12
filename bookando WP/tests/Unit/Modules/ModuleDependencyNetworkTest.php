<?php

namespace {
    if (!defined('BOOKANDO_PLUGIN_DIR')) {
        define('BOOKANDO_PLUGIN_DIR', dirname(__DIR__, 4) . '/');
    }

    if (!defined('DAY_IN_SECONDS')) {
        define('DAY_IN_SECONDS', 86400);
    }
}

namespace Bookando\Tests\Unit\Modules {

    use Bookando\Core\Licensing\LicenseManager;
    use Bookando\Core\Manager\ModuleManager;
    use Bookando\Core\Manager\ModuleManifest;
    use Bookando\Core\Manager\ModuleStateRepository;
    use PHPUnit\Framework\TestCase;

    /**
     * @covers \Bookando\Core\Manager\ModuleManager
     */
    final class ModuleDependencyNetworkTest extends TestCase
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

            \Bookando\Modules\Core\Module::reset();
            \Bookando\Modules\Feature\Module::reset();
            \Bookando\Modules\Broken\Module::reset();
            \Bookando\Modules\Ripple\Module::reset();
        }

        public function test_missing_dependency_only_disables_affected_modules(): void
        {
            $this->setActiveModules(['core', 'feature', 'broken', 'ripple']);

            $this->setManifestCache([
                'core' => [],
                'feature' => [
                    'dependencies' => ['core'],
                ],
                'broken' => [
                    'dependencies' => ['ghost'],
                ],
                'ripple' => [
                    'dependencies' => ['broken'],
                ],
            ]);

            $manager = ModuleManager::instance();
            $manager->loadModules();

            $modules = $manager->getAllModules();

            $this->assertArrayHasKey('core', $modules);
            $this->assertArrayHasKey('feature', $modules);
            $this->assertArrayNotHasKey('broken', $modules);
            $this->assertArrayNotHasKey('ripple', $modules);

            $this->assertSame(1, \Bookando\Modules\Core\Module::$bootCount);
            $this->assertSame(1, \Bookando\Modules\Feature\Module::$bootCount);
            $this->assertSame(0, \Bookando\Modules\Broken\Module::$bootCount);
            $this->assertSame(0, \Bookando\Modules\Ripple\Module::$bootCount);

            $this->assertFalse($manager->isActive('broken'));
            $this->assertFalse($manager->isActive('ripple'));
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

namespace Bookando\Modules\Core {
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

namespace Bookando\Modules\Feature {
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

namespace Bookando\Modules\Broken {
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

namespace Bookando\Modules\Ripple {
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
