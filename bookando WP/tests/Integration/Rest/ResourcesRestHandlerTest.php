<?php

namespace Bookando\Tests\Integration\Rest;

use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\resources\RestHandler;
use Bookando\Modules\resources\StateRepository;
use WP_REST_Request;
use WP_REST_Response;

class ResourcesRestHandlerTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantManager::reset();
        StateRepository::resetCache();
        TenantManager::setCurrentTenantId(1);
        delete_option('bookando_resources_state_1');
    }

    protected function tearDown(): void
    {
        delete_option('bookando_resources_state_1');
        StateRepository::resetCache();
        TenantManager::reset();
        parent::tearDown();
    }

    public function test_list_resources_returns_payload(): void
    {
        StateRepository::upsertResource('materials', [
            'id'   => 'mat-1',
            'name' => 'Material 1',
        ]);

        $request = new WP_REST_Request('GET', '/bookando/v1/resources/materials');
        $response = RestHandler::listResources('materials', $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $payload = $response->get_data();
        $this->assertIsArray($payload['data']);
        $this->assertSame('mat-1', $payload['data'][0]['id']);
    }

    public function test_get_resource_returns_entry(): void
    {
        StateRepository::upsertResource('rooms', [
            'id'   => 'room-abc',
            'name' => 'Room ABC',
        ]);

        $request = new WP_REST_Request('GET', '/bookando/v1/resources/rooms/room-abc');
        $request->set_param('id', 'room-abc');

        $response = RestHandler::getResource('rooms', $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $payload = $response->get_data();
        $this->assertSame('room-abc', $payload['data']['id']);
    }

    public function test_get_resource_requires_id(): void
    {
        $request = new WP_REST_Request('GET', '/bookando/v1/resources/rooms');
        $response = RestHandler::getResource('rooms', $request);

        $this->assertSame(400, $response->get_status());
        $payload = $response->get_data();
        $this->assertSame('missing_id', $payload['error']['code']);
    }

    public function test_get_resource_returns_404_for_unknown(): void
    {
        $request = new WP_REST_Request('GET', '/bookando/v1/resources/rooms/unknown');
        $request->set_param('id', 'unknown');

        $response = RestHandler::getResource('rooms', $request);

        $this->assertSame(404, $response->get_status());
        $payload = $response->get_data();
        $this->assertSame('not_found', $payload['error']['code']);
    }
}
