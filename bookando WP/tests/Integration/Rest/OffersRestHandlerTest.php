<?php

namespace Bookando\Tests\Integration\Rest;

use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\Offers\RestHandler;
use WP_REST_Request;
use WP_REST_Response;

class OffersRestHandlerTest extends \WP_UnitTestCase
{
    private string $table;

    protected function setUp(): void
    {
        parent::setUp();
        TenantManager::reset();
        TenantManager::setCurrentTenantId(1);
        $this->table = $this->createOffersTable();
    }

    protected function tearDown(): void
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$this->table}");
        TenantManager::reset();

        parent::tearDown();
    }

    private function createOffersTable(): string
    {
        global $wpdb;

        $table = $wpdb->prefix . 'bookando_offers';
        $collate = $wpdb->get_charset_collate();

        $wpdb->query("DROP TABLE IF EXISTS {$table}");
        $wpdb->query(<<<SQL
            CREATE TABLE {$table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                tenant_id bigint(20) unsigned NOT NULL,
                title varchar(191) NOT NULL,
                status varchar(32) NOT NULL,
                created_at datetime DEFAULT NULL,
                updated_at datetime DEFAULT NULL,
                deleted_at datetime DEFAULT NULL,
                PRIMARY KEY (id)
            ) {$collate}
        SQL);

        return $table;
    }

    private function insertOffer(array $overrides = []): int
    {
        global $wpdb;

        $defaults = [
            'tenant_id'  => 1,
            'title'      => 'Sample',
            'status'     => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'deleted_at' => null,
        ];

        $wpdb->insert($this->table, array_merge($defaults, $overrides));

        return (int) $wpdb->insert_id;
    }

    public function test_get_returns_single_offer(): void
    {
        $id = $this->insertOffer(['title' => 'Alpha']);

        $request = new WP_REST_Request('GET', "/bookando/v1/offers/offers/{$id}");
        $request->set_param('id', $id);

        $response = RestHandler::get($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertSame('Alpha', $response->get_data()['data']['title']);
    }

    public function test_list_returns_paginated_payload(): void
    {
        $this->insertOffer(['title' => 'Alpha']);
        $this->insertOffer(['title' => 'Beta']);

        $request = new WP_REST_Request('GET', '/bookando/v1/offers/offers');
        $request->set_param('page', 1);
        $request->set_param('per_page', 1);
        $request->set_param('order_by', 'created_at');
        $request->set_param('order', 'ASC');

        $response = RestHandler::list($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $payload = $response->get_data();
        $this->assertCount(1, $payload['data']);
        $this->assertSame(2, $payload['meta']['total']);
        $this->assertSame(1, $payload['meta']['per_page']);
    }

    public function test_create_persists_offer(): void
    {
        $request = new WP_REST_Request('POST', '/bookando/v1/offers/offers');
        $request->set_body(json_encode([
            'title'  => 'Neue Leistung',
            'status' => 'active',
        ], JSON_THROW_ON_ERROR));

        $response = RestHandler::create($request);

        $this->assertSame(201, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('id', $data['data']);

        global $wpdb;
        $saved = $wpdb->get_row($wpdb->prepare("SELECT title, status FROM {$this->table} WHERE id = %d", $data['data']['id']), ARRAY_A);
        $this->assertSame('Neue Leistung', $saved['title']);
        $this->assertSame('active', $saved['status']);
    }

    public function test_create_rejects_invalid_payload(): void
    {
        $request = new WP_REST_Request('POST', '/bookando/v1/offers/offers');
        $request->set_body(json_encode(['status' => 'active'], JSON_THROW_ON_ERROR));

        $response = RestHandler::create($request);

        $this->assertSame(422, $response->get_status());
        $this->assertSame('invalid_payload', $response->get_data()['error']['code']);
    }

    public function test_update_changes_title(): void
    {
        $id = $this->insertOffer(['title' => 'Alt']);

        $request = new WP_REST_Request('PUT', "/bookando/v1/offers/offers/{$id}");
        $request->set_body(json_encode(['title' => 'Neu'], JSON_THROW_ON_ERROR));
        $request->set_param('id', $id);

        $response = RestHandler::update($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['data']['updated']);

        global $wpdb;
        $fresh = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$this->table} WHERE id = %d", $id));
        $this->assertSame('Neu', $fresh);
    }

    public function test_delete_soft_marks_record(): void
    {
        $id = $this->insertOffer();

        $request = new WP_REST_Request('DELETE', "/bookando/v1/offers/offers/{$id}");
        $request->set_param('id', $id);
        $response = RestHandler::delete($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['data']['hard']);

        global $wpdb;
        $deletedAt = $wpdb->get_var($wpdb->prepare("SELECT deleted_at FROM {$this->table} WHERE id = %d", $id));
        $this->assertNotNull($deletedAt);
    }

    public function test_delete_hard_removes_record(): void
    {
        $id = $this->insertOffer();

        $request = new WP_REST_Request('DELETE', "/bookando/v1/offers/offers/{$id}");
        $request->set_param('hard', 1);
        $request->set_param('id', $id);
        $response = RestHandler::delete($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['data']['hard']);

        global $wpdb;
        $remaining = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE id = %d", $id));
        $this->assertSame(0, $remaining);
    }

    public function test_bulk_soft_delete_marks_multiple_records(): void
    {
        $first  = $this->insertOffer();
        $second = $this->insertOffer();

        $request = new WP_REST_Request('POST', '/bookando/v1/offers/bulk');
        $request->set_body(json_encode([
            'action' => 'delete_soft',
            'ids'    => [$first, $second, 0],
        ], JSON_THROW_ON_ERROR));

        $response = RestHandler::bulk($request);

        $this->assertSame(200, $response->get_status());
        $payload = $response->get_data();
        $this->assertSame(2, $payload['data']['deleted']);
        $this->assertSame(2, $payload['data']['requested']);

        global $wpdb;
        $softDeleted = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE id IN (%d, %d) AND deleted_at IS NOT NULL",
            $first,
            $second
        ));
        $this->assertSame(2, $softDeleted);
    }

    public function test_bulk_invalid_action_returns_error(): void
    {
        $id = $this->insertOffer();

        $request = new WP_REST_Request('POST', '/bookando/v1/offers/bulk');
        $request->set_body(json_encode([
            'action' => 'archive',
            'ids'    => [$id],
        ], JSON_THROW_ON_ERROR));

        $response = RestHandler::bulk($request);

        $this->assertSame(422, $response->get_status());
        $this->assertSame('invalid_payload', $response->get_data()['error']['code']);
    }
}
