<?php

namespace Bookando\Tests\Integration\Rest;

use ReflectionProperty;
use WP_Error;
use WP_REST_Request;
use Bookando\Core\Dispatcher\RestDispatcher;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\employees\RestHandler as EmployeesRestHandler;
use Bookando\Modules\settings\RestHandler as SettingsRestHandler;

class RestDispatcherPermissionTest extends \WP_UnitTestCase
{
    private ?ReflectionProperty $moduleHandlersProperty = null;
    private array $moduleHandlersBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        TenantManager::reset();
        LicenseManager::clear();

        $this->moduleHandlersProperty = new ReflectionProperty(RestDispatcher::class, 'moduleHandlers');
        $this->moduleHandlersProperty->setAccessible(true);
        $this->moduleHandlersBackup = (array) $this->moduleHandlersProperty->getValue();
        $this->moduleHandlersProperty->setValue(null, []);
    }

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        if ($this->moduleHandlersProperty instanceof ReflectionProperty) {
            $this->moduleHandlersProperty->setValue(null, $this->moduleHandlersBackup);
        }
        TenantManager::reset();
        LicenseManager::clear();
        parent::tearDown();
    }

    private function registerModule(string $slug, string $handler): void
    {
        RestDispatcher::registerModule($slug, $handler);
    }

    private function setLicenseFeatures(array $features, array $modules = ['settings', 'employees']): void
    {
        LicenseManager::setLicenseData([
            'key'      => 'test-key',
            'modules'  => $modules,
            'features' => $features,
            'plan'     => 'enterprise',
        ]);
    }

    public function test_employees_route_allows_request_when_requirements_met(): void
    {
        $this->registerModule('employees', EmployeesRestHandler::class);
        $this->registerModule('settings', SettingsRestHandler::class);
        $this->setLicenseFeatures(['rest_api_read']);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('GET', '/bookando/v1/employees/123/calendars');
        $request->set_param('id', 123);

        $result = RestDispatcher::permission($request);

        $this->assertTrue($result);
    }

    public function test_integrations_route_maps_to_settings_module(): void
    {
        $this->registerModule('settings', SettingsRestHandler::class);
        $this->setLicenseFeatures(['rest_api_read', 'rest_api_write'], ['settings']);

        $userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        $request = new WP_REST_Request('POST', '/bookando/v1/integrations/oauth/start');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

        $result = RestDispatcher::permission($request);

        $this->assertTrue($result);
    }

    public function test_unknown_route_returns_clear_error(): void
    {
        $request = new WP_REST_Request('GET', '/bookando/v1/not-registered/path');

        $result = RestDispatcher::permission($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_unknown_module', $result->get_error_code());
    }

    public function test_unregistered_module_returns_explicit_error(): void
    {
        $request = new WP_REST_Request('GET', '/bookando/v1/ghost/items');
        $request->set_param('module', 'ghost');

        $result = RestDispatcher::permission($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_module_unregistered', $result->get_error_code());
        $this->assertStringContainsString('ghost', $result->get_error_message());
    }
}
