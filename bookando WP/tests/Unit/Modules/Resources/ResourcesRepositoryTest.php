<?php

declare(strict_types=1);

namespace Bookando\Tests\Unit\Modules\Resources;

use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\Resources\ResourcesRepository;
use Bookando\Tests\Support\Resources\ResourceFactory;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/Support/Resources/ResourceFactory.php';

final class ResourcesRepositoryTest extends TestCase
{
    private ResourcesRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        bookando_test_reset_stubs();
        TenantManager::reset();
        TenantManager::setCurrentTenantId(1);

        $this->repository = new ResourcesRepository();
        $this->repository->resetCache();
    }

    protected function tearDown(): void
    {
        $this->repository->resetCache();
        TenantManager::reset();
        parent::tearDown();
    }

    public function test_getState_seeds_defaults_when_empty(): void
    {
        $state = $this->repository->getState();

        $this->assertArrayHasKey('locations', $state);
        $this->assertArrayHasKey('rooms', $state);
        $this->assertArrayHasKey('materials', $state);
        $this->assertNotEmpty($state['locations']);
    }

    public function test_upsert_creates_resource_with_generated_identifiers(): void
    {
        $payload = ResourceFactory::make([
            'name' => '  Neuer Standort  ',
            'tags' => [' Empfang ', '<script>alert(1)</script>'],
            'availability' => [
                ResourceFactory::slot([
                    'start' => '08:30:59',
                    'end' => '10:15:00',
                    'notes' => '  Frühschicht  ',
                ]),
            ],
        ]);

        $resource = $this->repository->save('locations', $payload);

        $this->assertNotSame('', $resource['id']);
        $this->assertSame('Neuer Standort', $resource['name']);
        $this->assertSame(['Empfang'], $resource['tags']);
        $this->assertSame('2025-01-01 12:00:00', $resource['created_at']);
        $this->assertSame('2025-01-01 12:00:00', $resource['updated_at']);
        $this->assertSame('08:30', $resource['availability'][0]['start']);
        $this->assertSame('10:15', $resource['availability'][0]['end']);
        $this->assertSame('Frühschicht', $resource['availability'][0]['notes']);

        $option = get_option('bookando_resources_state_1');
        $this->assertSame($resource['id'], $option['locations'][0]['id']);
    }

    public function test_upsert_updates_existing_resource(): void
    {
        $initial = $this->repository->save('rooms', ResourceFactory::make([
            'name' => 'Raum Alpha',
        ]));

        $updated = $this->repository->save('rooms', [
            'id' => $initial['id'],
            'name' => '  Raum Beta  ',
            'capacity' => 10,
            'created_at' => '2024-12-31 10:00:00',
            'tags' => [' Beta '],
        ]);

        $this->assertSame($initial['id'], $updated['id']);
        $this->assertSame('Raum Beta', $updated['name']);
        $this->assertSame(10, $updated['capacity']);
        $this->assertSame('2024-12-31 10:00:00', $updated['created_at']);
        $this->assertSame('2025-01-01 12:00:00', $updated['updated_at']);

        $state = $this->repository->getState();
        $this->assertSame('Raum Beta', $state['rooms'][0]['name']);
    }

    public function test_delete_removes_resource_and_returns_boolean(): void
    {
        $first = $this->repository->save('materials', ResourceFactory::make(['name' => 'Fahrzeug A']));
        $second = $this->repository->save('materials', ResourceFactory::make(['name' => 'Fahrzeug B']));

        $deleted = $this->repository->delete('materials', $first['id']);

        $this->assertTrue($deleted);

        $state = $this->repository->getState();
        $ids = array_map(static fn($resource) => $resource['id'], $state['materials']);

        $this->assertNotContains($first['id'], $ids);
        $this->assertContains($second['id'], $ids);
    }

    public function test_delete_returns_false_when_id_missing(): void
    {
        $this->repository->save('rooms', ResourceFactory::make(['name' => 'Raum']));

        $result = $this->repository->delete('rooms', 'non-existent');

        $this->assertFalse($result);
    }

    public function test_state_is_isolated_per_tenant(): void
    {
        $this->repository->save('locations', ResourceFactory::make(['name' => 'Tenant 1']));

        TenantManager::setCurrentTenantId(2);
        $this->repository->resetCache();
        $this->repository->save('locations', ResourceFactory::make(['name' => 'Tenant 2']));

        TenantManager::setCurrentTenantId(1);
        $this->repository->resetCache();
        $tenantOne = $this->repository->getState();

        TenantManager::setCurrentTenantId(2);
        $this->repository->resetCache();
        $tenantTwo = $this->repository->getState();

        $this->assertSame('Tenant 1', $tenantOne['locations'][0]['name']);
        $this->assertSame('Tenant 2', $tenantTwo['locations'][0]['name']);
    }
}
