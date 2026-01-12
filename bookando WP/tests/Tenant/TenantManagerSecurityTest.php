<?php

namespace Bookando\Tests\Tenant;

use Bookando\Core\Tenant\TenantManager;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;

/**
 * @covers \Bookando\Core\Tenant\TenantManager
 */
final class TenantManagerSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (function_exists('bookando_test_reset_stubs')) {
            bookando_test_reset_stubs();
        }

        TenantManager::reset();
        unset($_SERVER['HTTP_X_BOOKANDO_TENANT'], $_SERVER['HTTP_HOST']);
        $GLOBALS['bookando_test_user_caps'] = [];
        $GLOBALS['bookando_test_filter_callbacks'] = [];
    }

    protected function tearDown(): void
    {
        TenantManager::reset();
        unset($_SERVER['HTTP_X_BOOKANDO_TENANT'], $_SERVER['HTTP_HOST']);
        $GLOBALS['bookando_test_user_caps'] = [];
        $GLOBALS['bookando_test_filter_callbacks'] = [];

        parent::tearDown();
    }

    public function test_header_override_requires_management_capability(): void
    {
        $_SERVER['HTTP_X_BOOKANDO_TENANT'] = '7';
        $request = new WP_REST_Request('GET', '/bookando');

        $this->assertSame(1, TenantManager::resolveFromRequest($request));

        $GLOBALS['bookando_test_user_caps'] = ['manage_options'];
        TenantManager::reset();
        $this->assertSame(7, TenantManager::resolveFromRequest($request));

        $GLOBALS['bookando_test_user_caps'] = ['bookando_switch_tenant'];
        TenantManager::reset();
        $this->assertSame(7, TenantManager::resolveFromRequest($request));
    }

    public function test_header_override_can_be_blocked_by_filter(): void
    {
        $_SERVER['HTTP_X_BOOKANDO_TENANT'] = '9';
        $GLOBALS['bookando_test_user_caps'] = ['manage_options'];

        add_filter(
            'bookando_tenant_allow_header_switch',
            static fn(bool $allowed, int $tenantId): bool => $tenantId === 9 ? false : $allowed,
            10,
            2
        );

        $this->assertSame(1, TenantManager::resolveFromRequest(null));
    }

    public function test_is_allowed_for_considers_shared_tenant_option_and_filters(): void
    {
        TenantManager::setCurrentTenantId(5);
        update_option('bookando_shared_tenants', [
            5 => ['6', ' 7 ', 'invalid'],
        ]);

        $this->assertTrue(TenantManager::isAllowedFor(5));
        $this->assertTrue(TenantManager::isAllowedFor(6));
        $this->assertTrue(TenantManager::isAllowedFor(7));
        $this->assertFalse(TenantManager::isAllowedFor(8));

        add_filter('bookando_tenant_allowed_targets', static fn(array $targets): array => array_merge($targets, [42]));
        $this->assertTrue(TenantManager::isAllowedFor(42));
    }

    public function test_subdomain_resolution_prefers_filter_result(): void
    {
        if (!defined('BOOKANDO_SUBDOMAIN_MULTI_TENANT')) {
            define('BOOKANDO_SUBDOMAIN_MULTI_TENANT', true);
        }

        $_SERVER['HTTP_HOST'] = 'kunde1.example.test';
        update_option('bookando_subdomain_map', ['kunde1' => 77]);

        add_filter(
            'bookando_tenant_map_subdomain',
            static fn(int $default, string $subdomain): int => $subdomain === 'kunde1' ? 88 : $default,
            10,
            2
        );

        $this->assertSame(88, TenantManager::resolveFromRequest(null));
    }
}
