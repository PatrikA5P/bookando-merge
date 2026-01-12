<?php

namespace Bookando\Tests\Unit\Core\Manager;

use Bookando\Core\Manager\ModuleStateRepository;
use Bookando\Tests\Support\RecordingWpdb;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/Support/RecordingWpdb.php';

final class ModuleStateRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (function_exists('bookando_test_reset_stubs')) {
            bookando_test_reset_stubs();
        }

        $this->resetSingleton();
        global $wpdb;
        $wpdb = new \wpdb();
    }

    protected function tearDown(): void
    {
        $this->resetSingleton();
        parent::tearDown();
    }

    public function test_getActiveSlugs_returns_legacy_state_when_table_missing(): void
    {
        update_option('bookando_active_modules', ['alpha', 'beta']);

        $repository = $this->createRepository(new \wpdb());

        $this->assertSame(['alpha', 'beta'], $repository->getActiveSlugs());
    }

    public function test_getActiveSlugs_reads_from_table_and_syncs_legacy_option(): void
    {
        $db = new RecordingWpdb([
            [
                'slug' => 'alpha',
                'status' => 'active',
                'installed_at' => '2024-12-31 10:00:00',
                'updated_at' => '2024-12-31 10:00:00',
            ],
            [
                'slug' => 'beta',
                'status' => 'inactive',
                'updated_at' => '2024-12-31 10:00:00',
            ],
        ]);

        $repository = $this->createRepository($db);

        $this->assertSame(['alpha'], $repository->getActiveSlugs());
        $this->assertSame(['alpha'], get_option('bookando_active_modules'));
    }

    public function test_activate_and_deactivate_update_persistent_state(): void
    {
        $db = new RecordingWpdb();
        $repository = $this->createRepository($db);

        $repository->activate('gamma', 21);

        $this->assertArrayHasKey('gamma', $db->rows);
        $this->assertSame('active', $db->rows['gamma']->status ?? null);
        $this->assertSame(21, $db->rows['gamma']->activated_by ?? null);
        $this->assertNotEmpty($db->rows['gamma']->installed_at ?? null);
        $this->assertContains('gamma', get_option('bookando_active_modules', []));

        $repository->deactivate('gamma', 42);

        $this->assertSame('inactive', $db->rows['gamma']->status ?? null);
        $this->assertSame(42, $db->rows['gamma']->deactivated_by ?? null);
        $this->assertSame([], get_option('bookando_active_modules'));
    }

    private function createRepository(\wpdb $db): ModuleStateRepository
    {
        $reflection = new \ReflectionClass(ModuleStateRepository::class);
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($instance, $db);

        return $instance;
    }

    private function resetSingleton(): void
    {
        $reflection = new \ReflectionClass(ModuleStateRepository::class);
        if ($reflection->hasProperty('instance')) {
            $property = $reflection->getProperty('instance');
            $property->setAccessible(true);
            $property->setValue(null, null);
        }
    }
}
