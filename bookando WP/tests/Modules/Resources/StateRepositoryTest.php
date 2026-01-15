<?php
declare(strict_types=1);

use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\Resources\StateRepository;

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return (string) $text;
    }
}

if (!function_exists('get_option')) {
    function get_option(string $name, $default = false)
    {
        return array_key_exists($name, StateRepositoryTestStubs::$options)
            ? StateRepositoryTestStubs::$options[$name]
            : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $name, $value, $autoload = null): bool
    {
        StateRepositoryTestStubs::$options[$name] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $name): bool
    {
        unset(StateRepositoryTestStubs::$options[$name]);
        return true;
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = [])
    {
        return array_merge((array) $defaults, (array) $args);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value): string
    {
        return trim((string) $value);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value): string
    {
        return trim((string) $value);
    }
}

if (!function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4(): string
    {
        return StateRepositoryTestStubs::nextUuid();
    }
}

if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        return $type === 'Y-m-d'
            ? substr(StateRepositoryTestStubs::$currentTime, 0, 10)
            : StateRepositoryTestStubs::$currentTime;
    }
}

require_once __DIR__ . '/../../../Core/Tenant/TenantManager.php';
require_once __DIR__ . '/../StateRepository.php';

final class StateRepositoryTestStubs
{
    /** @var array<string, mixed> */
    public static array $options = [];

    public static string $currentTime = '2025-01-01 12:00:00';

    private static int $uuidCounter = 0;

    public static function reset(): void
    {
        self::$options = [];
        self::$currentTime = '2025-01-01 12:00:00';
        self::$uuidCounter = 0;
    }

    public static function nextUuid(): string
    {
        self::$uuidCounter++;
        return sprintf('uuid-%d', self::$uuidCounter);
    }
}

final class StateRepositoryTest
{
    public static function run(): void
    {
        self::testMigrationAssignsLegacyState();
        self::testStatesAreTenantScoped();
        self::testUpsertPersistsPerTenant();
        self::testListAndFindResources();

        echo "StateRepositoryTest: OK\n";
    }

    private static function resetEnvironment(): void
    {
        TenantManager::reset();
        TenantManager::setCurrentTenantId(1);
        StateRepository::resetCache();
        StateRepositoryTestStubs::reset();
    }

    private static function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private static function assertSame($expected, $actual, string $message): void
    {
        if ($expected !== $actual) {
            $expectedExport = var_export($expected, true);
            $actualExport = var_export($actual, true);
            throw new RuntimeException($message . "\nExpected: {$expectedExport}\nActual:   {$actualExport}");
        }
    }

    private static function hasLocationWithName(int $tenantId, string $name): bool
    {
        $key = sprintf('bookando_resources_state_%d', $tenantId);
        $locations = StateRepositoryTestStubs::$options[$key]['locations'] ?? [];

        foreach ($locations as $location) {
            if (($location['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    private static function hasLocationWithId(int $tenantId, string $id): bool
    {
        $key = sprintf('bookando_resources_state_%d', $tenantId);
        $locations = StateRepositoryTestStubs::$options[$key]['locations'] ?? [];

        foreach ($locations as $location) {
            if (($location['id'] ?? null) === $id) {
                return true;
            }
        }

        return false;
    }

    private static function testMigrationAssignsLegacyState(): void
    {
        self::resetEnvironment();

        StateRepositoryTestStubs::$options['bookando_resources_state'] = [
            'locations' => [
                [
                    'id' => 'legacy-loc',
                    'name' => 'Legacy Location',
                    'description' => 'Legacy description',
                    'capacity' => 3,
                    'tags' => ['legacy'],
                    'availability' => [],
                    'created_at' => '2024-01-01 10:00:00',
                    'updated_at' => '2024-01-01 10:00:00',
                    'type' => 'locations',
                ],
            ],
            'rooms' => [],
            'materials' => [],
        ];

        TenantManager::setCurrentTenantId(7);

        $state = StateRepository::getState();

        self::assertSame('legacy-loc', $state['locations'][0]['id'], 'Legacy state should be returned for tenant after migration.');
        self::assertTrue(isset(StateRepositoryTestStubs::$options['bookando_resources_state_7']), 'Legacy option must migrate to tenant-specific key.');
        self::assertTrue(!isset(StateRepositoryTestStubs::$options['bookando_resources_state']), 'Legacy global option should be removed after migration.');
    }

    private static function testStatesAreTenantScoped(): void
    {
        self::resetEnvironment();

        StateRepositoryTestStubs::$options['bookando_resources_state_3'] = [
            'locations' => [['id' => 'tenant-3']],
            'rooms' => [],
            'materials' => [],
        ];
        StateRepositoryTestStubs::$options['bookando_resources_state_4'] = [
            'locations' => [['id' => 'tenant-4']],
            'rooms' => [],
            'materials' => [],
        ];

        TenantManager::setCurrentTenantId(3);
        $tenant3 = StateRepository::getState();

        TenantManager::reset();
        StateRepository::resetCache();
        TenantManager::setCurrentTenantId(4);
        $tenant4 = StateRepository::getState();

        self::assertSame('tenant-3', $tenant3['locations'][0]['id'], 'Tenant 3 should receive its own resources.');
        self::assertSame('tenant-4', $tenant4['locations'][0]['id'], 'Tenant 4 should receive its own resources.');
    }

    private static function testUpsertPersistsPerTenant(): void
    {
        self::resetEnvironment();

        TenantManager::setCurrentTenantId(11);
        $resource = StateRepository::upsertResource('locations', ['name' => 'Warehouse']);

        self::assertTrue(isset(StateRepositoryTestStubs::$options['bookando_resources_state_11']), 'Tenant 11 option should be created.');
        self::assertTrue(self::hasLocationWithName(11, 'Warehouse'), 'Tenant 11 resource must be stored.');

        TenantManager::reset();
        StateRepository::resetCache();
        TenantManager::setCurrentTenantId(12);

        StateRepository::getState();

        self::assertTrue(!self::hasLocationWithId(12, $resource['id']), 'Tenant 12 must not see tenant 11 resources.');
    }

    private static function testListAndFindResources(): void
    {
        self::resetEnvironment();

        TenantManager::setCurrentTenantId(21);
        $expected = StateRepository::upsertResource('rooms', [
            'id'   => 'room-21',
            'name' => 'Planning Room',
        ]);

        $list = StateRepository::listResources('rooms');
        self::assertSame(1, count($list), 'List should return a single resource.');
        self::assertSame('room-21', $list[0]['id'], 'List should expose the stored ID.');

        $found = StateRepository::findResource('rooms', 'room-21');
        self::assertSame($expected['id'], $found['id'], 'Find must return the stored resource.');

        $missing = StateRepository::findResource('rooms', 'does-not-exist');
        self::assertSame(null, $missing, 'Find must return null for missing resources.');
    }
}

StateRepositoryTest::run();
