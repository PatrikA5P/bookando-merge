<?php

declare(strict_types=1);

namespace Bookando\Tests\Unit\Modules\Resources;

use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\Resources\ResourcesRepository;
use Bookando\Modules\Resources\ResourcesService;
use Bookando\Tests\Support\Resources\ResourceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WP_Error;

require_once dirname(__DIR__, 3) . '/Support/Resources/ResourceFactory.php';

final class ResourcesServiceTest extends TestCase
{
    /** @var ResourcesRepository&MockObject */
    private ResourcesRepository $repository;

    private ResourcesService $service;

    protected function setUp(): void
    {
        parent::setUp();

        bookando_test_reset_stubs();
        TenantManager::reset();
        TenantManager::setCurrentTenantId(1);

        $this->repository = $this->createMock(ResourcesRepository::class);
        $this->service = new ResourcesService($this->repository, fn(): bool => true);
    }

    protected function tearDown(): void
    {
        TenantManager::reset();
        parent::tearDown();
    }

    public function test_save_requires_manage_capability(): void
    {
        $service = new ResourcesService($this->repository, fn(): bool => false);

        $result = $service->save('rooms', ResourceFactory::make());

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_forbidden', $result->get_error_code());
    }

    public function test_save_returns_error_for_invalid_type(): void
    {
        $this->repository->expects($this->never())->method('save');

        $result = $this->service->save('unknown', ResourceFactory::make());

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_type', $result->get_error_code());
    }

    public function test_save_validates_required_name(): void
    {
        $this->repository->expects($this->never())->method('save');

        $result = $this->service->save('rooms', ['name' => '   ']);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('validation_failed', $result->get_error_code());
        $this->assertSame('name', $result->get_error_data()['field']);
    }

    public function test_save_validates_availability_structure(): void
    {
        $this->repository->expects($this->never())->method('save');

        $payload = ResourceFactory::make(['availability' => 'invalid']);
        $result = $this->service->save('rooms', $payload);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('validation_failed', $result->get_error_code());
        $this->assertSame('availability', $result->get_error_data()['field']);
    }

    public function test_save_persists_resource_when_valid(): void
    {
        $repository = new ResourcesRepository();
        $repository->resetCache();
        $service = new ResourcesService($repository, fn(): bool => true);

        $payload = ResourceFactory::make([
            'name' => ' Studio 1 ',
            'availability' => [
                ResourceFactory::slot(['start' => '09:00:00', 'end' => '10:30:00']),
            ],
        ]);

        $result = $service->save('rooms', $payload);

        $this->assertIsArray($result);
        $this->assertSame('Studio 1', $result['name']);
        $this->assertSame('09:00', $result['availability'][0]['start']);

        $state = $repository->getState();
        $this->assertSame('Studio 1', $state['rooms'][0]['name']);
    }

    public function test_delete_requires_manage_capability(): void
    {
        $service = new ResourcesService($this->repository, fn(): bool => false);

        $result = $service->delete('rooms', 'id-1');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_forbidden', $result->get_error_code());
    }

    public function test_delete_returns_error_for_invalid_type(): void
    {
        $this->repository->expects($this->never())->method('delete');

        $result = $this->service->delete('invalid', 'id-1');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_type', $result->get_error_code());
    }

    public function test_delete_validates_identifier(): void
    {
        $this->repository->expects($this->never())->method('delete');

        $result = $this->service->delete('rooms', '   ');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('validation_failed', $result->get_error_code());
        $this->assertSame('id', $result->get_error_data()['field']);
    }

    public function test_delete_returns_error_when_repository_reports_missing(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with('rooms', 'unknown')
            ->willReturn(false);

        $result = $this->service->delete('rooms', 'unknown');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('not_found', $result->get_error_code());
    }

    public function test_delete_returns_success_payload(): void
    {
        $repository = new ResourcesRepository();
        $repository->resetCache();
        $service = new ResourcesService($repository, fn(): bool => true);

        $resource = $service->save('materials', ResourceFactory::make(['name' => 'Beamer']));

        $result = $service->delete('materials', $resource['id']);

        $this->assertIsArray($result);
        $this->assertTrue($result['deleted']);

        $state = $repository->getState();
        $ids = array_map(static fn($entry) => $entry['id'], $state['materials']);
        $this->assertNotContains($resource['id'], $ids);
    }

    public function test_listByType_returns_error_for_invalid_type(): void
    {
        $this->repository->expects($this->never())->method('listByType');

        $result = $this->service->listByType('invalid');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_type', $result->get_error_code());
    }

    public function test_listByType_returns_resources(): void
    {
        $repository = new ResourcesRepository();
        $repository->resetCache();
        $service = new ResourcesService($repository, fn(): bool => true);

        $service->save('locations', ResourceFactory::make(['name' => 'Filiale Mitte']));

        $result = $service->listByType('locations');

        $this->assertIsArray($result);
        $this->assertSame('Filiale Mitte', $result[0]['name']);
    }

    public function test_getState_delegates_to_repository(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('getState')
            ->willReturn(['locations' => []]);

        $result = $this->service->getState();

        $this->assertSame(['locations' => []], $result);
namespace Bookando\Tests\Unit\Modules\Resources;

use Bookando\Modules\Resources\ResourcesService;
use PHPUnit\Framework\TestCase;
use WP_Error;

final class ResourcesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        bookando_test_reset_stubs();
    }

    public function test_validate_resource_returns_sanitized_payload_for_valid_data(): void
    {
        $payload = [
            'id' => 'resource-1',
            'name' => '  Seminarraum  ',
            'description' => 'Raum im Erdgeschoss',
            'capacity' => '10',
            'tags' => ['  Theorie ', 'Praxis'],
            'availability' => [
                [
                    'id' => 'slot-1',
                    'date' => '2025-02-15',
                    'start' => '08:00',
                    'end' => '12:30:00',
                    'capacity' => '5',
                    'notes' => 'Vormittag',
                ],
            ],
            'created_at' => '2025-02-10 08:00:00',
            'updated_at' => '2025-02-11 08:00:00',
        ];

        $result = ResourcesService::validateResource('rooms', $payload);

        $this->assertIsArray($result);
        $this->assertSame('rooms', $result['type']);
        $this->assertSame('resource-1', $result['id']);
        $this->assertSame('Seminarraum', $result['name']);
        $this->assertSame('Raum im Erdgeschoss', $result['description']);
        $this->assertSame(10, $result['capacity']);
        $this->assertSame(['Theorie', 'Praxis'], $result['tags']);
        $this->assertSame('2025-02-10 08:00:00', $result['created_at']);
        $this->assertSame('2025-02-11 08:00:00', $result['updated_at']);
        $this->assertCount(1, $result['availability']);
        $slot = $result['availability'][0];
        $this->assertSame('slot-1', $slot['id']);
        $this->assertSame('2025-02-15', $slot['date']);
        $this->assertSame('08:00', $slot['start']);
        $this->assertSame('12:30', $slot['end']);
        $this->assertSame(5, $slot['capacity']);
        $this->assertSame('Vormittag', $slot['notes']);
    }

    public function test_validate_resource_rejects_missing_name(): void
    {
        $payload = [
            'description' => 'Fehlender Name',
        ];

        $result = ResourcesService::validateResource('locations', $payload);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('validation_failed', $result->get_error_code());
        $this->assertSame('Validierung der Ressourcendaten fehlgeschlagen.', $result->get_error_message());

        $data = $result->get_error_data();
        $this->assertIsArray($data);
        $this->assertSame(422, $data['status']);
        $this->assertArrayHasKey('fields', $data);
        $this->assertArrayHasKey('name', $data['fields']);
        $this->assertSame('required', $data['fields']['name'][0]['code']);
    }

    public function test_validate_resource_rejects_invalid_availability_slot(): void
    {
        $payload = [
            'name' => 'Material A',
            'availability' => [
                [
                    'date' => '2025/02/15',
                    'start' => '8am',
                    'end' => '07:00',
                    'capacity' => -1,
                    'notes' => 123,
                ],
            ],
        ];

        $result = ResourcesService::validateResource('materials', $payload);

        $this->assertInstanceOf(WP_Error::class, $result);
        $data = $result->get_error_data();
        $this->assertArrayHasKey('fields', $data);
        $this->assertArrayHasKey('availability.0.date', $data['fields']);
        $this->assertArrayHasKey('availability.0.start', $data['fields']);
        $this->assertArrayHasKey('availability.0.capacity', $data['fields']);
        $this->assertArrayHasKey('availability.0.notes', $data['fields']);
    }
}
