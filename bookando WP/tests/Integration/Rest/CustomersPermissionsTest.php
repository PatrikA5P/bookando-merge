<?php
namespace Bookando\Tests\Integration\Rest;

use WP_Error;
use WP_REST_Request;
use Bookando\Core\Dispatcher\RestPermissions;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Tenant\TenantManager;

class CustomersPermissionsTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantManager::reset();
        LicenseManager::clear();
    }

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        TenantManager::reset();
        LicenseManager::clear();
        parent::tearDown();
    }

    private function setLicenseFeatures(array $features): void
    {
        LicenseManager::setLicenseData([
            'key'      => 'test-key',
            'modules'  => ['customers'],
            'features' => $features,
            'plan'     => 'pro',
        ]);
    }

    public function test_collection_requires_manage_capability(): void
    {
        $this->setLicenseFeatures(['rest_api_read']);
        $userId = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('GET', '/bookando/v1/customers');

        $result = RestPermissions::customers($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_forbidden', $result->get_error_code());
    }

    public function test_read_requires_license_feature(): void
    {
        $this->setLicenseFeatures([]);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('GET', '/bookando/v1/customers');

        $result = RestPermissions::customers($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_license_read_disabled', $result->get_error_code());
    }

    public function test_write_requires_nonce(): void
    {
        $this->setLicenseFeatures(['rest_api_read', 'rest_api_write']);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('POST', '/bookando/v1/customers');

        $result = RestPermissions::customers($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_nonce_invalid', $result->get_error_code());
    }

    public function test_write_requires_license_feature(): void
    {
        $this->setLicenseFeatures(['rest_api_read']);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('POST', '/bookando/v1/customers');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $result = RestPermissions::customers($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_license_write_disabled', $result->get_error_code());
    }
}
