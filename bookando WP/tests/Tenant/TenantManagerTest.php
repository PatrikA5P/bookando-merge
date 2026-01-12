<?php

namespace Bookando {
    if (!class_exists(Logger::class)) {
        class Logger
        {
            /** @var list<array{message: string, context: array}> */
            public static array $entries = [];

            public static function error(string $message, array $context = []): void
            {
                self::$entries[] = [
                    'message' => $message,
                    'context' => $context,
                ];
            }

            public static function reset(): void
            {
                self::$entries = [];
            }
        }
    }
}

namespace Bookando\Tests\Tenant {

use Bookando\Core\Tenant\TenantManager;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;

final class TenantManagerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('BOOKANDO_SUBDOMAIN_MULTI_TENANT')) {
            define('BOOKANDO_SUBDOMAIN_MULTI_TENANT', true);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        bookando_test_reset_stubs();
        TenantManager::reset();
        if (method_exists(\Bookando\Logger::class, 'reset')) {
            \Bookando\Logger::reset();
        }

        unset($_SERVER['HTTP_X_BOOKANDO_TENANT'], $_SERVER['HTTP_HOST']);
        $GLOBALS['bookando_test_rest_state']['capabilities'] = [];
    }

    public function testResolvesTenantFromHeaderWithSanitization(): void
    {
        $GLOBALS['bookando_test_rest_state']['capabilities'] = ['manage_options'];
        $_SERVER['HTTP_X_BOOKANDO_TENANT'] = '  42 ';

        $this->assertSame(42, TenantManager::resolveFromRequest());
    }

    public function testResolvesTenantFromRequestParameterWithSanitization(): void
    {
        $request = new WP_REST_Request('GET', '/test');
        $request->set_param('tenant_id', "\n 77 \n");

        $this->assertSame(77, TenantManager::resolveFromRequest($request));
    }

    public function testResolvesTenantFromSubdomainOptionMap(): void
    {
        update_option('bookando_subdomain_map', ['tenant-a' => '0099']);
        $_SERVER['HTTP_HOST'] = 'tenant-a.example.test';

        $this->assertSame(99, TenantManager::resolveFromRequest());
    }

    public function testResolvesTenantFromSubdomainConfigMap(): void
    {
        add_filter('bookando_tenant_config', static fn () => [
            'subdomain_map'  => ['config-tenant' => '303'],
            'default_tenant' => 5,
        ]);

        TenantManager::reset();
        $_SERVER['HTTP_HOST'] = 'config-tenant.example.test';

        $this->assertSame(303, TenantManager::resolveFromRequest());
    }

    public function testFallsBackToConfiguredTenantAndLogsWhenNoMatch(): void
    {
        update_option('bookando_default_tenant_id', '15');
        add_filter('bookando_tenant_default_id', static fn (int $value): int => $value + 2);

        TenantManager::reset();

        $result = TenantManager::resolveFromRequest();

        $this->assertSame(17, $result);

        $this->assertNotEmpty(\Bookando\Logger::$entries);
        $entry = \Bookando\Logger::$entries[0];
        $this->assertSame('tenant.fallback', $entry['message']);
        $this->assertSame(17, $entry['context']['tenant_id']);
    }
}

}

