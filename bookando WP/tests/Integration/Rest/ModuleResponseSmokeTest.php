<?php
namespace Bookando\Tests\Integration\Rest;

use WP_REST_Request;
use WP_REST_Response;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\Academy\RestHandler as AcademyRestHandler;
use Bookando\Modules\Appointments\RestHandler as AppointmentsRestHandler;
use Bookando\Modules\Customers\RestHandler as CustomersRestHandler;
use Bookando\Modules\Finance\RestHandler as FinanceRestHandler;
use Bookando\Modules\Offers\RestHandler as OffersRestHandler;
use Bookando\Modules\Resources\RestHandler as ResourcesRestHandler;
use Bookando\Modules\Settings\RestHandler as SettingsRestHandler;

class ModuleResponseSmokeTest extends \WP_UnitTestCase
{
    /** @var array<int, string> */
    private array $createdTables = [];

    protected function setUp(): void
    {
        parent::setUp();

        TenantManager::reset();
        TenantManager::setCurrentTenantId(1);

        LicenseManager::clear();
        LicenseManager::setLicenseData([
            'key'      => 'test',
            'modules'  => ['customers'],
            'features' => ['rest_api_read'],
            'plan'     => 'pro',
        ]);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
    }

    protected function tearDown(): void
    {
        global $wpdb;

        foreach ($this->createdTables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('bookando_academy_state');
        delete_option('bookando_finance_state');
        delete_option('bookando_resources_state');
        LicenseManager::clear();
        wp_set_current_user(0);

        parent::tearDown();
    }

    private function createTable(string $suffix, string $definition): string
    {
        global $wpdb;

        $table = $wpdb->prefix . $suffix;
        $collate = $wpdb->get_charset_collate();

        $wpdb->query("DROP TABLE IF EXISTS {$table}");
        $wpdb->query("CREATE TABLE {$table} ({$definition}) {$collate}");

        $this->createdTables[] = $table;

        return $table;
    }

    public function test_academy_state_returns_successful_response(): void
    {
        $response = AcademyRestHandler::getState();

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertTrue($data['meta']['success']);
    }

    public function test_academy_progress_missing_id_returns_error_response(): void
    {
        $request = new WP_REST_Request('POST', '/bookando/v1/academy/training_cards_progress');
        $response = AcademyRestHandler::updateProgress($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());
        $body = $response->get_data();
        $this->assertSame('missing_id', $body['error']['code']);
    }

    public function test_finance_state_returns_successful_response(): void
    {
        $response = FinanceRestHandler::getState();

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
    }

    public function test_finance_save_invoice_rejects_invalid_payload(): void
    {
        $request = new WP_REST_Request('POST', '/bookando/v1/finance/invoices');
        $request->set_body('invalid-json');

        $response = FinanceRestHandler::saveInvoice($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('invalid_payload', $response->get_data()['error']['code']);
    }

    public function test_resources_state_returns_successful_response(): void
    {
        $response = ResourcesRestHandler::getState();

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
    }

    public function test_resources_delete_without_id_returns_error(): void
    {
        $request = new WP_REST_Request('DELETE', '/bookando/v1/resources/locations');

        $response = ResourcesRestHandler::deleteResource('locations', $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());
        $this->assertSame('missing_id', $response->get_data()['error']['code']);
    }

    public function test_settings_company_get_returns_successful_response(): void
    {
        global $wpdb;

        $table = $this->createTable('bookando_company_settings', '
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) DEFAULT NULL,
            name varchar(191) DEFAULT NULL,
            address text NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            website varchar(191) DEFAULT NULL,
            logo_url varchar(191) DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY  (id)
        ');

        $wpdb->insert($table, [
            'tenant_id' => null,
            'name'      => 'Bookando GmbH',
            'address'   => 'Example Street 1',
            'phone'     => '+410000000',
            'email'     => 'info@example.com',
            'website'   => 'https://example.com',
            'logo_url'  => 'https://example.com/logo.png',
            'created_at'=> current_time('mysql'),
            'updated_at'=> current_time('mysql'),
        ]);

        $request = new WP_REST_Request('GET', '/bookando/v1/settings/company');
        $response = SettingsRestHandler::company($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertSame('Bookando GmbH', $response->get_data()['data']['name']);
    }

    public function test_settings_company_method_not_allowed_returns_error(): void
    {
        $request = new WP_REST_Request('DELETE', '/bookando/v1/settings/company');
        $response = SettingsRestHandler::company($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(405, $response->get_status());
    }

    public function test_offers_list_returns_successful_response(): void
    {
        global $wpdb;

        $table = $this->createTable('bookando_offers', '
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) DEFAULT NULL,
            title varchar(191) DEFAULT NULL,
            status varchar(32) DEFAULT NULL,
            deleted_at datetime DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ');

        $wpdb->insert($table, [
            'title'      => 'Yoga Kurs',
            'status'     => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        $request = new WP_REST_Request('GET', '/bookando/v1/offers/offers');
        $response = OffersRestHandler::offers([], $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $payload = $response->get_data();
        $this->assertIsArray($payload['data']);
        $this->assertNotEmpty($payload['data']);
        $this->assertSame(1, $payload['meta']['total']);
    }

    public function test_offers_not_found_returns_error_response(): void
    {
        $request = new WP_REST_Request('GET', '/bookando/v1/offers/offers/999');
        $response = OffersRestHandler::offers(['subkey' => 999], $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(404, $response->get_status());
        $this->assertSame('not_found', $response->get_data()['error']['code']);
    }

    public function test_appointments_timeline_returns_successful_response(): void
    {
        global $wpdb;

        $appointments = $this->createTable('bookando_appointments', '
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) DEFAULT 1,
            customer_id bigint(20) DEFAULT NULL,
            employee_id bigint(20) DEFAULT NULL,
            service_id bigint(20) DEFAULT NULL,
            event_id bigint(20) DEFAULT NULL,
            status varchar(32) DEFAULT NULL,
            starts_at_utc datetime DEFAULT NULL,
            ends_at_utc datetime DEFAULT NULL,
            client_tz varchar(64) DEFAULT NULL,
            price decimal(10,2) DEFAULT NULL,
            persons int DEFAULT 1,
            meta longtext DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ');

        $users = $this->createTable('bookando_users', '
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) DEFAULT 1,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            email varchar(191) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            roles longtext DEFAULT NULL,
            status varchar(20) DEFAULT NULL,
            deleted_at datetime DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ');

        $offers = $this->createTable('bookando_offers', '
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) DEFAULT 1,
            title varchar(191) DEFAULT NULL,
            status varchar(32) DEFAULT NULL,
            deleted_at datetime DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ');

        $events = $this->createTable('bookando_events', '
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) DEFAULT 1,
            name varchar(191) DEFAULT NULL,
            type varchar(50) DEFAULT NULL,
            status varchar(50) DEFAULT NULL,
            max_capacity int DEFAULT NULL,
            price decimal(10,2) DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ');

        $periods = $this->createTable('bookando_event_periods', '
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) DEFAULT NULL,
            period_start_utc datetime DEFAULT NULL,
            period_end_utc datetime DEFAULT NULL,
            time_zone varchar(64) DEFAULT NULL,
            PRIMARY KEY (id)
        ');

        $now = gmdate('Y-m-d H:i:s');
        $later = gmdate('Y-m-d H:i:s', strtotime('+1 hour'));

        $wpdb->insert($users, [
            'first_name' => 'Ada',
            'last_name'  => 'Lovelace',
            'email'      => 'ada@example.com',
            'roles'      => wp_json_encode(['customer']),
            'status'     => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        $wpdb->insert($offers, [
            'title'      => 'Coaching',
            'status'     => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        $wpdb->insert($events, [
            'name'        => 'Workshop',
            'type'        => 'training',
            'status'      => 'scheduled',
            'max_capacity'=> 10,
            'price'       => 120,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ]);
        $eventId = (int) $wpdb->insert_id;

        $wpdb->insert($periods, [
            'event_id'         => $eventId,
            'period_start_utc' => $now,
            'period_end_utc'   => $later,
            'time_zone'        => 'UTC',
        ]);
        $periodId = (int) $wpdb->insert_id;

        $wpdb->insert($appointments, [
            'customer_id'   => 1,
            'service_id'    => 1,
            'event_id'      => $eventId,
            'status'        => 'confirmed',
            'starts_at_utc' => $now,
            'ends_at_utc'   => $later,
            'client_tz'     => 'UTC',
            'persons'       => 1,
            'meta'          => wp_json_encode(['note' => 'Test']),
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
        ]);

        $request = new WP_REST_Request('GET', '/bookando/v1/appointments/timeline');
        $response = AppointmentsRestHandler::timeline([], $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $payload = $response->get_data();
        $this->assertArrayHasKey('data', $payload);
    }

    public function test_appointments_non_post_request_returns_error(): void
    {
        $request = new WP_REST_Request('GET', '/bookando/v1/appointments/appointments');
        $response = AppointmentsRestHandler::appointments([], $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(405, $response->get_status());
    }

    public function test_customers_list_returns_successful_response(): void
    {
        global $wpdb;

        $table = $this->createTable('bookando_users', '
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tenant_id bigint(20) DEFAULT 1,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            email varchar(191) DEFAULT NULL,
            status varchar(20) DEFAULT NULL,
            deleted_at datetime DEFAULT NULL,
            roles longtext DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ');

        $wpdb->insert($table, [
            'first_name' => 'Clara',
            'last_name'  => 'Test',
            'email'      => 'clara@example.com',
            'status'     => 'active',
            'roles'      => wp_json_encode(['customer']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        $request = new WP_REST_Request('GET', '/bookando/v1/customers/customers');
        $request->set_param('include_deleted', 'no');

        $response = CustomersRestHandler::customers([], $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data()['data'];
        $this->assertSame(1, $data['total']);
    }

    public function test_customers_method_not_allowed_returns_error_response(): void
    {
        $request = new WP_REST_Request('PATCH', '/bookando/v1/customers/customers');
        $response = CustomersRestHandler::customers([], $request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(405, $response->get_status());
    }
}

