<?php
namespace Bookando\Tests\Integration\Rest;

use WP_Error;
use WP_REST_Request;
use Bookando\Modules\settings\Api\Api as SettingsApi;
use Bookando\Modules\settings\RestHandler;

class SettingsRoutesTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = null;
        rest_get_server();
    }

    public function test_company_route_uses_rest_module_guard(): void
    {
        SettingsApi::registerRoutes();

        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/bookando/v1/settings/company', $routes);

        $route = $routes['/bookando/v1/settings/company'][0];
        $this->assertSame([RestHandler::class, 'company'], $route['callback']);

        $permission = $route['permission_callback'];
        $this->assertIsCallable($permission);

        $result = $permission(new WP_REST_Request('GET', '/bookando/v1/settings/company'));
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_unauthorized', $result->get_error_code());
    }

    public function test_roles_route_registers_validation_rules(): void
    {
        SettingsApi::registerRoutes();

        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/bookando/v1/settings/roles/(?P<role_slug>[a-z0-9_-]+)', $routes);

        $route = $routes['/bookando/v1/settings/roles/(?P<role_slug>[a-z0-9_-]+)'][0];
        $this->assertArrayHasKey('role_slug', $route['args']);

        $validate = $route['args']['role_slug']['validate_callback'];
        $this->assertTrue($validate('team_lead'));
        $this->assertFalse($validate(''));
    }

    public function test_feature_route_requires_feature_key(): void
    {
        SettingsApi::registerRoutes();

        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/bookando/v1/settings/feature/(?P<feature_key>[a-z0-9_-]+)', $routes);

        $route    = $routes['/bookando/v1/settings/feature/(?P<feature_key>[a-z0-9_-]+)'][0];
        $validate = $route['args']['feature_key']['validate_callback'];

        $this->assertTrue($validate('labels'));
        $this->assertFalse($validate('   '));
    }
}
