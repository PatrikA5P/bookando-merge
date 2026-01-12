<?php
namespace Bookando\Tests\Integration\Rest;

use WP_Error;
use WP_REST_Request;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Modules\appointments\Api\Api as AppointmentsApi;

class AppointmentsRoutesTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = null;
        rest_get_server();
        LicenseManager::clear();
        delete_option('bookando_module_installed_at_appointments');
    }

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        LicenseManager::clear();
        delete_option('bookando_module_installed_at_appointments');
        parent::tearDown();
    }

    private function setLicense(array $modules, array $features): void
    {
        LicenseManager::setLicenseData([
            'key'      => 'test-key',
            'modules'  => $modules,
            'features' => $features,
            'plan'     => 'pro',
        ]);
    }

    private function getPermissionCallback(): callable
    {
        AppointmentsApi::registerRoutes();
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/bookando/v1/appointments/appointments', $routes);

        $route = $routes['/bookando/v1/appointments/appointments'][0];
        $this->assertArrayHasKey('permission_callback', $route);

        return $route['permission_callback'];
    }

    public function test_permission_denied_when_module_not_allowed(): void
    {
        update_option('bookando_module_installed_at_appointments', time() - (LicenseManager::GRACE_PERIOD_DAYS + 1) * DAY_IN_SECONDS);

        $permission = $this->getPermissionCallback();
        $this->assertIsCallable($permission);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        $this->setLicense([], ['rest_api_read']);

        $request = new WP_REST_Request('GET', '/bookando/v1/appointments/appointments');
        $result  = $permission($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('module_not_allowed', $result->get_error_code());
    }

    public function test_permission_allows_when_module_is_licensed(): void
    {
        update_option('bookando_module_installed_at_appointments', time() - (LicenseManager::GRACE_PERIOD_DAYS + 1) * DAY_IN_SECONDS);

        $permission = $this->getPermissionCallback();
        $this->assertIsCallable($permission);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        $this->setLicense(['appointments'], ['rest_api_read']);

        $request = new WP_REST_Request('GET', '/bookando/v1/appointments/appointments');
        $result  = $permission($request);

        $this->assertTrue($result);
    }
}
