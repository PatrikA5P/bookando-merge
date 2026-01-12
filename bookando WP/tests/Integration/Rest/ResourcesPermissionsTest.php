<?php
namespace Bookando\Tests\Integration\Rest;

use WP_Error;
use WP_REST_Request;
use Bookando\Core\Dispatcher\RestModuleGuard;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\resources\Capabilities;
use Bookando\Modules\resources\RestHandler;

class ResourcesPermissionsTest extends \WP_UnitTestCase
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
        remove_all_filters('bookando_resources_capability_map');
        TenantManager::reset();
        LicenseManager::clear();
        parent::tearDown();
    }

    private function setLicenseFeatures(array $features): void
    {
        LicenseManager::setLicenseData([
            'key'      => 'test-key',
            'modules'  => ['resources'],
            'features' => $features,
            'plan'     => 'pro',
        ]);
    }

    private function guard(): callable
    {
        return RestModuleGuard::for('resources', static fn(\WP_REST_Request $request) => RestHandler::guardCapabilities($request));
    }

    public function test_read_requires_license_feature(): void
    {
        $this->setLicenseFeatures([]);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('GET', '/bookando/v1/resources/state');

        $result = ($this->guard())($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_license_read_disabled', $result->get_error_code());
    }

    public function test_write_requires_nonce(): void
    {
        $this->setLicenseFeatures(['rest_api_read', 'rest_api_write']);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('POST', '/bookando/v1/resources/locations');

        $result = ($this->guard())($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_nonce_invalid', $result->get_error_code());
    }

    public function test_write_requires_license_feature(): void
    {
        $this->setLicenseFeatures(['rest_api_read']);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('POST', '/bookando/v1/resources/rooms');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $result = ($this->guard())($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_license_write_disabled', $result->get_error_code());
    }

    public function test_write_requires_additional_capability_when_mapped(): void
    {
        $this->setLicenseFeatures(['rest_api_read', 'rest_api_write']);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        add_filter('bookando_resources_capability_map', function (array $map) {
            $map['POST']['materials'] = 'export_bookando_resources';
            return $map;
        });

        $request = new WP_REST_Request('POST', '/bookando/v1/resources/materials');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $result = ($this->guard())($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_forbidden', $result->get_error_code());
        $this->assertStringContainsString('export_bookando_resources', $result->get_error_message());
    }

    public function test_write_succeeds_with_valid_nonce_license_and_capabilities(): void
    {
        $this->setLicenseFeatures(['rest_api_read', 'rest_api_write']);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('POST', '/bookando/v1/resources/locations');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $result = ($this->guard())($request);

        $this->assertTrue($result);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function deleteRouteProvider(): array
    {
        return [
            'locations' => ['locations'],
            'rooms'     => ['rooms'],
            'materials' => ['materials'],
        ];
    }

    /**
     * @dataProvider deleteRouteProvider
     */
    public function test_delete_requires_additional_capability_when_mapped(string $type): void
    {
        $this->setLicenseFeatures(['rest_api_read', 'rest_api_write']);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $capability = "delete_bookando_{$type}";

        add_filter('bookando_resources_capability_map', function (array $map) use ($type, $capability) {
            $map['DELETE'][$type] = $capability;
            return $map;
        });

        $request = new WP_REST_Request('DELETE', "/bookando/v1/resources/{$type}/123");
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $result = ($this->guard())($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_forbidden', $result->get_error_code());
        $this->assertStringContainsString($capability, $result->get_error_message());
    }

    /**
     * @dataProvider deleteRouteProvider
     */
    public function test_delete_succeeds_with_additional_capability(string $type): void
    {
        $this->setLicenseFeatures(['rest_api_read', 'rest_api_write']);
        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $capability = "delete_bookando_{$type}";

        add_filter('bookando_resources_capability_map', function (array $map) use ($type, $capability) {
            $map['DELETE'][$type] = $capability;
            return $map;
        });

        $role = get_role('administrator');
        $this->assertNotNull($role);
        $role->add_cap($capability);

        try {
            $request = new WP_REST_Request('DELETE', "/bookando/v1/resources/{$type}/123");
            $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

            $result = ($this->guard())($request);

            $this->assertTrue($result);
        } finally {
            $role->remove_cap($capability);
        }
    public function test_state_requires_manage_capability(): void
    {
        $this->setLicenseFeatures(['rest_api_read']);
        $userId = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($userId);

        $guard = RestModuleGuard::for(Capabilities::CAPABILITY_MANAGE);
        $request = new WP_REST_Request('GET', '/bookando/v1/resources/state');

        $result = $guard($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_forbidden', $result->get_error_code());
        $this->assertStringContainsString(Capabilities::CAPABILITY_MANAGE, $result->get_error_message());
    }
}
