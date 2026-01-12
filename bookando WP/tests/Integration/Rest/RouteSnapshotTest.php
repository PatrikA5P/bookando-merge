<?php

namespace Bookando\Tests\Integration\Rest;

use WP_UnitTestCase;
use Bookando\Modules\academy\Api\Api as AcademyApi;
use Bookando\Modules\appointments\Api\Api as AppointmentsApi;
use Bookando\Modules\customers\Api\Api as CustomersApi;
use Bookando\Modules\employees\Api\Api as EmployeesApi;
use Bookando\Modules\finance\Api\Api as FinanceApi;
use Bookando\Modules\offers\Api\Api as OffersApi;
use Bookando\Modules\resources\Api\Api as ResourcesApi;
use Bookando\Modules\settings\Api\Api as SettingsApi;

class RouteSnapshotTest extends WP_UnitTestCase
{
    private const SNAPSHOT = __DIR__ . '/__snapshots__/module-routes.json';

    protected function setUp(): void
    {
        parent::setUp();
        global $wp_rest_server;
        $wp_rest_server = null;
        rest_get_server();
    }

    public function test_module_routes_match_snapshot(): void
    {
        $apis = [
            AcademyApi::class,
            AppointmentsApi::class,
            CustomersApi::class,
            EmployeesApi::class,
            FinanceApi::class,
            OffersApi::class,
            ResourcesApi::class,
            SettingsApi::class,
        ];

        foreach ($apis as $api) {
            $api::registerRoutes();
        }

        $routes = rest_get_server()->get_routes();
        $snapshotData = [];

        foreach ($routes as $route => $handlers) {
            if (!str_starts_with($route, '/bookando/v1/')) {
                continue;
            }

            $snapshotData[$route] = array_map(static function (array $handler): array {
                $methods = $handler['methods'] ?? [];
                if (is_string($methods)) {
                    $methods = preg_split('/\s*,\s*/', $methods) ?: [];
                }
                sort($methods);

                return [
                    'methods'       => array_values(array_unique($methods)),
                    'show_in_index' => (bool) ($handler['show_in_index'] ?? true),
                ];
            }, $handlers);
        }

        ksort($snapshotData);

        if (!file_exists(self::SNAPSHOT)) {
            $json = json_encode($snapshotData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                $this->fail('Unable to encode snapshot JSON.');
            }

            file_put_contents(self::SNAPSHOT, $json);
            $this->fail('Snapshot file was missing. A fresh snapshot has been generated and must be reviewed.');
        }

        $expected = json_decode((string) file_get_contents(self::SNAPSHOT), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($expected, $snapshotData, 'Registered module routes no longer match the snapshot.');
    }
}
