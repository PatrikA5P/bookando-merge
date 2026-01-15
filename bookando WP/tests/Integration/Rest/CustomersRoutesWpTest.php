<?php
namespace Bookando\Tests\Integration\Rest;

use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\Customers\Api\Api as CustomersApi;
use WP_REST_Request;

class CustomersRoutesWpTest extends \WP_UnitTestCase
{
    private string $table;

    protected function setUp(): void
    {
        parent::setUp();

        global $wp_rest_server, $wpdb;

        $wp_rest_server = null;
        rest_get_server();

        TenantManager::reset();
        LicenseManager::clear();
        LicenseManager::setLicenseData([
            'key'      => 'test-key',
            'modules'  => ['customers'],
            'features' => ['rest_api_read', 'rest_api_write'],
            'plan'     => 'pro',
        ]);

        update_option('bookando_active_modules', ['customers']);
        update_option('bookando_module_installed_at_customers', time() - DAY_IN_SECONDS);

        $this->table = $wpdb->prefix . 'bookando_users';
        $wpdb->query("DROP TABLE IF EXISTS {$this->table}");
        $this->createTable();

        CustomersApi::registerRoutes();
    }

    protected function tearDown(): void
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table}");
        wp_set_current_user(0);
        TenantManager::reset();
        LicenseManager::clear();
        parent::tearDown();
    }

    public function test_get_collection_returns_customers(): void
    {
        $this->authenticateAdmin();
        $firstId = $this->seedCustomer([
            'first_name' => 'Erika',
            'last_name'  => 'Mustermann',
            'email'      => 'erika@example.com',
        ]);
        $this->seedCustomer([
            'first_name' => 'Hidden',
            'tenant_id'  => 2,
            'roles'      => '[]',
        ]);

        $request = new WP_REST_Request('GET', '/bookando/v1/customers');
        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
        $payload = $response->get_data();
        $this->assertIsArray($payload['data']);
        $this->assertSame(1, $payload['data']['total']);
        $this->assertCount(1, $payload['data']['data']);
        $this->assertSame($firstId, $payload['data']['data'][0]['id']);
    }

    public function test_post_creates_customer_record(): void
    {
        $nonce = $this->authenticateAdmin(true);

        $request = new WP_REST_Request('POST', '/bookando/v1/customers');
        $request->set_header('X-WP-Nonce', $nonce);
        $request->set_body(json_encode([
            'first_name' => 'Lisa',
            'last_name'  => 'Meier',
            'email'      => 'lisa@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(201, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('id', $data['data']);

        $row = $this->fetchCustomer((int) $data['data']['id']);
        $this->assertSame('Lisa', $row['first_name']);
        $this->assertSame('lisa@example.com', $row['email']);
        $this->assertSame('["customer"]', $row['roles']);
    }

    public function test_put_updates_customer(): void
    {
        $nonce = $this->authenticateAdmin(true);
        $customerId = $this->seedCustomer([
            'first_name' => 'Paul',
            'status'     => 'blocked',
        ]);

        $request = new WP_REST_Request('PUT', '/bookando/v1/customers/' . $customerId);
        $request->set_header('X-WP-Nonce', $nonce);
        $request->set_body(json_encode([
            'first_name' => 'Pauline',
            'status'     => 'active',
        ], JSON_THROW_ON_ERROR));

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['data']['updated']);

        $row = $this->fetchCustomer($customerId);
        $this->assertSame('Pauline', $row['first_name']);
        $this->assertSame('active', $row['status']);
    }

    public function test_delete_soft_marks_customer_deleted(): void
    {
        $nonce = $this->authenticateAdmin(true);
        $customerId = $this->seedCustomer([
            'first_name' => 'Karl',
            'status'     => 'active',
        ]);

        $request = new WP_REST_Request('DELETE', '/bookando/v1/customers/' . $customerId);
        $request->set_header('X-WP-Nonce', $nonce);

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['data']['hard']);

        $row = $this->fetchCustomer($customerId);
        $this->assertSame('deleted', $row['status']);
        $this->assertNull($row['deleted_at']);
    }

    public function test_bulk_block_updates_multiple_customers(): void
    {
        $nonce = $this->authenticateAdmin(true);
        $first = $this->seedCustomer(['first_name' => 'Eva']);
        $second = $this->seedCustomer(['first_name' => 'Tom']);

        $request = new WP_REST_Request('POST', '/bookando/v1/customers/bulk');
        $request->set_header('X-WP-Nonce', $nonce);
        $request->set_body(json_encode([
            'action' => 'block',
            'ids'    => [$first, $second],
        ], JSON_THROW_ON_ERROR));

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame(['ok' => true, 'affected' => 2], $data['data']);

        $this->assertSame('blocked', $this->fetchCustomer($first)['status']);
        $this->assertSame('blocked', $this->fetchCustomer($second)['status']);
    }

    private function authenticateAdmin(bool $withNonce = false): ?string
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        TenantManager::setCurrentTenantId(1);
        if (!$withNonce) {
            return null;
        }

        return wp_create_nonce('wp_rest');
    }

    private function createTable(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$this->table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
            first_name varchar(191) DEFAULT '',
            last_name varchar(191) DEFAULT '',
            email varchar(191) DEFAULT '',
            phone varchar(191) DEFAULT '',
            address text NULL,
            address_2 text NULL,
            zip varchar(50) DEFAULT '',
            city varchar(191) DEFAULT '',
            country varchar(2) DEFAULT NULL,
            birthdate date DEFAULT NULL,
            gender varchar(10) DEFAULT NULL,
            language varchar(10) DEFAULT 'de',
            note text NULL,
            avatar_url text NULL,
            timezone varchar(100) DEFAULT '',
            status varchar(20) DEFAULT 'active',
            roles longtext NOT NULL,
            description text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            total_appointments int DEFAULT 0,
            last_appointment datetime DEFAULT NULL,
            next_appointment datetime DEFAULT NULL,
            external_id varchar(191) DEFAULT NULL,
            PRIMARY KEY  (id)
        ) {$charset};";
        $wpdb->query($sql);
    }

    private function seedCustomer(array $data): int
    {
        global $wpdb;
        $defaults = [
            'tenant_id' => 1,
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'address' => null,
            'address_2' => null,
            'zip' => '',
            'city' => '',
            'country' => null,
            'birthdate' => null,
            'gender' => null,
            'language' => 'de',
            'note' => null,
            'avatar_url' => null,
            'timezone' => '',
            'status' => 'active',
            'roles' => '["customer"]',
            'description' => null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'deleted_at' => null,
            'total_appointments' => 0,
            'last_appointment' => null,
            'next_appointment' => null,
            'external_id' => null,
        ];

        $wpdb->insert($this->table, array_merge($defaults, $data));
        return (int) $wpdb->insert_id;
    }

    private function fetchCustomer(int $id): array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
        $this->assertIsArray($row);

        return $row;
    }
}
