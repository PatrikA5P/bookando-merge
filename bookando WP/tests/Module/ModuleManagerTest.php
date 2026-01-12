<?php

declare(strict_types=1);

namespace Bookando\Tests\Module;

use Bookando\Module\ModuleFactory;
use Bookando\Module\ModuleManager;
use PHPUnit\Framework\TestCase;

final class ModuleManagerTest extends TestCase
{
    public static int $configLoads = 0;

    private string $configFile;
    /**
     * @var array<string, array{value:mixed, expires_at: int|null}>
     */
    private array $transients = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::$configLoads = 0;
        $this->transients = [];

        $tmp = tempnam(sys_get_temp_dir(), 'bookando-modules-');
        if ($tmp === false) {
            $this->fail('Unable to create temporary module configuration file.');
        }

        $configPath = $tmp . '.php';
        rename($tmp, $configPath);
        $this->configFile = $configPath;

        $this->writeConfig([
            'alpha' => [
                'class' => DummyModule::class,
            ],
            'missing' => [
                'class' => 'Bookando\\Modules\\Missing\\Module',
            ],
        ]);

        DummyModule::$instances = 0;
    }

    protected function tearDown(): void
    {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }

        parent::tearDown();
    }

    public function testRegistersModulesOnlyOncePerRequest(): void
    {
        $manager = new ModuleManager($this->createFactory());
        $definitions = $manager->getDefinitions();

        $this->assertArrayHasKey('alpha', $definitions);
        $this->assertSame(1, self::$configLoads, 'Config file should be loaded exactly once.');

        $manager->getDefinitions();
        $this->assertSame(1, self::$configLoads, 'Subsequent calls must reuse the cached definitions.');
    }

    public function testReadsDefinitionsFromTransientCache(): void
    {
        $manager = new ModuleManager($this->createFactory());
        $manager->getDefinitions();
        $this->assertSame(1, self::$configLoads);

        $manager = new ModuleManager($this->createFactory());
        $manager->getDefinitions();

        $this->assertSame(1, self::$configLoads, 'Definitions should be served from the transient cache.');
    }

    public function testRecordsErrorsWhenModuleClassIsMissing(): void
    {
        $manager = new ModuleManager($this->createFactory());

        $instance = $manager->get('alpha');
        $this->assertInstanceOf(DummyModule::class, $instance);
        $this->assertSame(1, DummyModule::$instances);

        $missing = $manager->get('missing');
        $this->assertNull($missing);

        $errors = $manager->getErrors();
        $this->assertArrayHasKey('missing', $errors);
        $this->assertStringContainsString('could not be found', $errors['missing']);
    }

    private function createFactory(): ModuleFactory
    {
        return new ModuleFactory(
            $this->configFile,
            fn(string $key) => $this->getTransient($key),
            fn(string $key, $value, int $ttl): bool => $this->setTransient($key, $value, $ttl),
            fn(string $key): bool => $this->deleteTransient($key),
            'bookando_test_module_registry',
            1
        );
    }

    /**
     * @param array<string, mixed> $definitions
     */
    private function writeConfig(array $definitions): void
    {
        $export = var_export($definitions, true);
        $contents = sprintf(
            "<?php\\nBookando\\\\Tests\\\\Module\\\\ModuleManagerTest::\\$configLoads++;\\nreturn %s;\\n",
            $export
        );

        file_put_contents($this->configFile, $contents);
    }

    private function getTransient(string $key)
    {
        if (!isset($this->transients[$key])) {
            return false;
        }

        $entry = $this->transients[$key];
        if ($entry['expires_at'] !== null && $entry['expires_at'] < time()) {
            unset($this->transients[$key]);
            return false;
        }

        return $entry['value'];
    }

    private function setTransient(string $key, $value, int $ttl): bool
    {
        $this->transients[$key] = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : null,
        ];

        return true;
    }

    private function deleteTransient(string $key): bool
    {
        unset($this->transients[$key]);
        return true;
    }
}

final class DummyModule
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }
}
