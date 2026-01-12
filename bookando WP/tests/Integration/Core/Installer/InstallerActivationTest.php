<?php

namespace Bookando\Tests\Integration\Core\Installer;

use Bookando\Core\Installer;
use Bookando\Core\Manager\ModuleStateRepository;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Tests\Support\RecordingWpdb;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/Support/RecordingWpdb.php';

final class InstallerActivationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (function_exists('bookando_test_reset_stubs')) {
            bookando_test_reset_stubs();
        }

        TenantManager::reset();
        $this->resetSingleton(ModuleStateRepository::class);
    }

    protected function tearDown(): void
    {
        $this->resetSingleton(ModuleStateRepository::class);
        TenantManager::reset();

        parent::tearDown();
    }

    public function test_install_modules_activates_all_modules_when_no_legacy_state(): void
    {
        $db = new RecordingWpdb();
        global $wpdb;
        $wpdb = $db;

        update_option('bookando_active_modules', []);

        $repository = $this->createRepository($db);
        $this->setRepositoryInstance($repository);

        $this->invokeInstallModules();

        $this->assertNotEmpty($db->rows);

        $activeSlugs = get_option('bookando_active_modules', []);
        $this->assertNotSame([], $activeSlugs);

        foreach ($db->rows as $slug => $row) {
            $this->assertSame('active', $row->status ?? null, "Module '{$slug}' should be active after installation.");
            $this->assertContains($slug, $activeSlugs);
        }
    }

    public function test_install_modules_respects_legacy_options_and_existing_rows(): void
    {
        $db = new RecordingWpdb([
            [
                'slug' => 'finance',
                'status' => 'inactive',
                'updated_at' => '2024-12-01 12:00:00',
                'deactivated_at' => '2024-12-01 12:00:00',
                'deactivated_by' => 7,
            ],
            [
                'slug' => 'settings',
                'status' => 'active',
                'installed_at' => '2024-12-01 10:00:00',
                'activated_at' => '2024-12-01 10:00:00',
                'updated_at' => '2024-12-01 10:00:00',
            ],
        ]);
        global $wpdb;
        $wpdb = $db;

        update_option('bookando_active_modules', ['settings']);

        $repository = $this->createRepository($db);
        $this->setRepositoryInstance($repository);

        $this->invokeInstallModules();

        $activeSlugs = get_option('bookando_active_modules', []);

        $this->assertSame('inactive', $db->rows['finance']->status ?? null);
        $this->assertSame('active', $db->rows['settings']->status ?? null);
        $this->assertContains('settings', $activeSlugs);
        $this->assertNotContains('finance', $activeSlugs);
    }

    private function invokeInstallModules(): void
    {
        $reflection = new \ReflectionClass(Installer::class);
        $method = $reflection->getMethod('installModules');
        $method->setAccessible(true);
        $method->invoke(null);
    }

    private function createRepository(RecordingWpdb $db): ModuleStateRepository
    {
        $reflection = new \ReflectionClass(ModuleStateRepository::class);
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance, $db);

        return $instance;
    }

    private function setRepositoryInstance(ModuleStateRepository $repository): void
    {
        $reflection = new \ReflectionClass(ModuleStateRepository::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, $repository);
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
