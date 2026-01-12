<?php
declare(strict_types=1);

use Bookando\Core\Tenant\TenantManager;

require_once __DIR__ . '/../TenantManager.php';

if (!function_exists('current_user_can')) {
    function current_user_can(string $cap): bool
    {
        return TenantManagerTestStubs::$caps[$cap] ?? false;
    }
}

if (!function_exists('get_option')) {
    function get_option(string $name, $default = false)
    {
        return TenantManagerTestStubs::$options[$name] ?? $default;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, $value, ...$args)
    {
        if (empty(TenantManagerTestStubs::$filters[$hook])) {
            return $value;
        }

        foreach (TenantManagerTestStubs::$filters[$hook] as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }
}

final class TenantManagerTestStubs
{
    public static array $caps = [];
    public static array $options = [];
    public static array $filters = [];

    public static function reset(): void
    {
        self::$caps = [];
        self::$options = [];
        self::$filters = [];
    }

    public static function addFilter(string $hook, callable $callback): void
    {
        self::$filters[$hook][] = $callback;
    }
}

final class TenantManagerTest
{
    public static function run(): void
    {
        self::testSameTenantAllowed();
        self::testForeignTenantDeniedByDefault();
        self::testCapabilityAllowsSwitch();
        self::testSharedOptionAllowsAccess();
        self::testFilterCanGrantAccess();
        self::testFilterCanDenyAccess();
        self::testInvalidTenantId();

        echo "TenantManagerTest: OK\n";
    }

    private static function setUp(int $tenantId): void
    {
        TenantManager::reset();
        TenantManager::setCurrentTenantId($tenantId);
        TenantManagerTestStubs::reset();
    }

    private static function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private static function assertFalse(bool $condition, string $message): void
    {
        self::assertTrue(!$condition, $message);
    }

    private static function testSameTenantAllowed(): void
    {
        self::setUp(5);
        self::assertTrue(TenantManager::isAllowedFor(5), 'Current tenant must be allowed.');
    }

    private static function testForeignTenantDeniedByDefault(): void
    {
        self::setUp(5);
        self::assertFalse(TenantManager::isAllowedFor(7), 'Foreign tenant should be denied without permissions.');
    }

    private static function testCapabilityAllowsSwitch(): void
    {
        self::setUp(5);
        TenantManagerTestStubs::$caps['bookando_switch_tenant'] = true;
        self::assertTrue(TenantManager::isAllowedFor(7), 'Capability should allow switching tenant.');
    }

    private static function testSharedOptionAllowsAccess(): void
    {
        self::setUp(3);
        TenantManagerTestStubs::$options['bookando_shared_tenants'] = [
            3 => [4, '7', 'not-a-number'],
        ];
        self::assertTrue(TenantManager::isAllowedFor(4), 'Shared option must allow configured tenant.');
        self::assertTrue(TenantManager::isAllowedFor(7), 'Shared option must normalize numeric strings.');
    }

    private static function testFilterCanGrantAccess(): void
    {
        self::setUp(2);
        TenantManagerTestStubs::addFilter(
            'bookando_tenant_is_allowed',
            static function (bool $allowed, int $target, int $current): bool {
                if ($current === 2 && $target === 9) {
                    return true;
                }
                return $allowed;
            }
        );
        self::assertTrue(TenantManager::isAllowedFor(9), 'Filter should be able to grant access.');
    }

    private static function testFilterCanDenyAccess(): void
    {
        self::setUp(8);
        TenantManagerTestStubs::$caps['manage_options'] = true;
        TenantManagerTestStubs::addFilter(
            'bookando_tenant_is_allowed',
            static fn (bool $allowed): bool => false
        );
        self::assertFalse(TenantManager::isAllowedFor(11), 'Filter should be able to deny access.');
    }

    private static function testInvalidTenantId(): void
    {
        self::setUp(4);
        self::assertFalse(TenantManager::isAllowedFor(0), 'Tenant 0 must be rejected.');
    }
}

TenantManagerTest::run();
