<?php
namespace Bookando\Tests\Integration\Rest;

use WP_Error;
use WP_REST_Request;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Modules\offers\Api\Api as OffersApi;

class OffersRoutesTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = null;
        rest_get_server();
        LicenseManager::clear();
        delete_option('bookando_module_installed_at_offers');
    }

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        LicenseManager::clear();
        delete_option('bookando_module_installed_at_offers');
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

    private function getPermissionCallback(string $path, string $method): callable
    {
        OffersApi::registerRoutes();
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey($path, $routes);

        foreach ($routes[$path] as $route) {
            $routeMethods = $route['methods'];
            $routeMethods = is_array($routeMethods) ? $routeMethods : [$routeMethods];

            if (in_array(strtoupper($method), $routeMethods, true)) {
                $this->assertArrayHasKey('permission_callback', $route);

                return $route['permission_callback'];
            }
        }

        $this->fail(sprintf('Route %s with method %s not registered.', $path, $method));
    }

    public function test_permission_denied_without_module_license(): void
    {
        update_option('bookando_module_installed_at_offers', time() - (LicenseManager::GRACE_PERIOD_DAYS + 1) * DAY_IN_SECONDS);

        $permission = $this->getPermissionCallback('/bookando/v1/offers/offers', 'GET');
        $this->assertIsCallable($permission);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        $this->setLicense([], ['rest_api_read']);

        $request = new WP_REST_Request('GET', '/bookando/v1/offers/offers');
        $result  = $permission($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('module_not_allowed', $result->get_error_code());
    }

    public function test_permission_allows_with_valid_license(): void
    {
        update_option('bookando_module_installed_at_offers', time() - (LicenseManager::GRACE_PERIOD_DAYS + 1) * DAY_IN_SECONDS);

        $permission = $this->getPermissionCallback('/bookando/v1/offers/offers', 'GET');
        $this->assertIsCallable($permission);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        $this->setLicense(['offers'], ['rest_api_read']);

        $request = new WP_REST_Request('GET', '/bookando/v1/offers/offers');
        $result  = $permission($request);

        $this->assertTrue($result);
    }
    public function test_create_permission_requires_write_feature(): void
    {
        update_option('bookando_module_installed_at_offers', time() - (LicenseManager::GRACE_PERIOD_DAYS + 1) * DAY_IN_SECONDS);

        $permission = $this->getPermissionCallback('/bookando/v1/offers/offers', 'POST');
        $this->assertIsCallable($permission);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        $this->setLicense(['offers'], ['rest_api_read']);

        $request = new WP_REST_Request('POST', '/bookando/v1/offers/offers');
        $result  = $permission($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_license_write_disabled', $result->get_error_code());
    }

    public function test_create_permission_allows_with_write_feature(): void
    {
        update_option('bookando_module_installed_at_offers', time() - (LicenseManager::GRACE_PERIOD_DAYS + 1) * DAY_IN_SECONDS);

        $permission = $this->getPermissionCallback('/bookando/v1/offers/offers', 'POST');
        $this->assertIsCallable($permission);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);
        $this->setLicense(['offers'], ['rest_api_read', 'rest_api_write']);

        $request = new WP_REST_Request('POST', '/bookando/v1/offers/offers');
        $result  = $permission($request);

        $this->assertTrue($result);
    }
}
