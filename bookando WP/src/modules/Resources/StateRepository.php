<?php

declare(strict_types=1);

namespace Bookando\Modules\Resources;

use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Util\Sanitizer;
use function __;
use function get_option;
use function update_option;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function wp_generate_uuid4;
use function wp_parse_args;

class StateRepository
{
    private const OPTION_KEY_BASE = 'bookando_resources_state';

    /** @var array<int, array> */
    private static array $stateCache = [];

    public static function getState(): array
    {
        $tenantId = TenantManager::currentTenantId();

        if (isset(self::$stateCache[$tenantId])) {
            return self::$stateCache[$tenantId];
        }

        $optionKey = self::optionKey($tenantId);
        self::migrateLegacyState($optionKey);

        $stored = get_option($optionKey, null);
        if (!is_array($stored) || $stored === []) {
            return self::seedDefaultsForTenant($tenantId);
        }

        $state = self::normalizeState($stored);

        return self::$stateCache[$tenantId] = $state;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listResources(string $type): array
    {
        $state = self::getState();

        return $state[$type] ?? [];
    }

    public static function findResource(string $type, string $id): ?array
    {
        foreach (self::listResources($type) as $resource) {
            if (($resource['id'] ?? '') === $id) {
                return $resource;
            }
        }

        return null;
    }

    public static function upsertResource(string $type, array $payload): array
    {
        $state = self::getState();
        $resource = self::sanitizeResource($type, $payload);
        if (empty($resource['id'])) {
            $resource['id'] = wp_generate_uuid4();
            $resource['created_at'] = current_time('mysql');
        }
        $resource['updated_at'] = current_time('mysql');

        $found = false;
        foreach ($state[$type] as $index => $existing) {
            if (!empty($existing['id']) && $existing['id'] === $resource['id']) {
                $state[$type][$index] = array_merge($existing, $resource);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $state[$type][] = $resource;
        }

        self::saveState($state);
        return $resource;
    }

    public static function deleteResource(string $type, string $id): bool
    {
        $state = self::getState();
        $before = count($state[$type]);
        $state[$type] = array_values(array_filter($state[$type], static fn($entry) => ($entry['id'] ?? null) !== $id));
        self::saveState($state);
        return $before !== count($state[$type]);
    }

    private static function saveState(array $state): void
    {
        $tenantId = TenantManager::currentTenantId();
        $optionKey = self::optionKey($tenantId);
        $normalized = self::normalizeState($state);
        update_option($optionKey, $normalized, false);
        self::$stateCache[$tenantId] = $normalized;
    }

    public static function seedDefaultsForTenant(int $tenantId, bool $force = false): array
    {
        $tenantId = max(1, $tenantId);
        $optionKey = self::optionKey($tenantId);

        $existing = get_option($optionKey, null);
        if (!$force && is_array($existing) && !empty($existing)) {
            $normalized = self::normalizeState($existing);
            return self::$stateCache[$tenantId] = $normalized;
        }

        $defaults = self::buildDefaultState();
        $normalized = self::normalizeState($defaults);
        update_option($optionKey, $normalized, false);
        self::$stateCache[$tenantId] = $normalized;

        return $normalized;
    }

    private static function sanitizeResource(string $type, array $resource): array
    {
        $availability = array_map(function ($slot) {
            $slot = is_array($slot) ? $slot : [];
            return [
                'id'       => isset($slot['id']) ? Sanitizer::text((string) $slot['id']) : wp_generate_uuid4(),
                'date'     => Sanitizer::date($slot['date'] ?? null),
                'start'    => Sanitizer::time($slot['start'] ?? null),
                'end'      => Sanitizer::time($slot['end'] ?? null),
                'capacity' => isset($slot['capacity']) ? (int) $slot['capacity'] : null,
                'notes'    => Sanitizer::text(is_scalar($slot['notes'] ?? null) ? (string) $slot['notes'] : ''),
            ];
        }, (array)($resource['availability'] ?? []));

        return [
            'id'           => isset($resource['id']) ? Sanitizer::text((string) $resource['id']) : '',
            'name'         => Sanitizer::text(is_scalar($resource['name'] ?? null) ? (string) $resource['name'] : ''),
            'description'  => Sanitizer::textarea(is_scalar($resource['description'] ?? null) ? (string) $resource['description'] : ''),
            'capacity'     => isset($resource['capacity']) ? (int) $resource['capacity'] : null,
            'tags'         => array_values(array_filter(array_map(
                static fn($tag) => Sanitizer::text(is_scalar($tag) ? (string) $tag : ''),
                (array)($resource['tags'] ?? [])
            ))),
            'availability' => $availability,
            'created_at'   => $resource['created_at'] ?? current_time('mysql'),
            'updated_at'   => $resource['updated_at'] ?? current_time('mysql'),
            'type'         => $type,
        ];
    }

    private static function emptyState(): array
    {
        return [
            'locations' => [],
            'rooms' => [],
            'materials' => [],
        ];
    }

    private static function normalizeState(array $state): array
    {
        $state = wp_parse_args($state, self::emptyState());

        foreach (['locations', 'rooms', 'materials'] as $type) {
            $state[$type] = array_values(array_map(fn($item) => self::sanitizeResource($type, $item), $state[$type] ?? []));
        }

        return $state;
    }

    private static function buildDefaultState(): array
    {
        $now = current_time('mysql');
        $today = current_time('Y-m-d');
        return [
            'locations' => [
                [
                    'id' => wp_generate_uuid4(),
                    'name' => __('Hauptstandort', 'bookando'),
                    'description' => __('Zentrale Anlaufstelle für Kunden.', 'bookando'),
                    'capacity' => 5,
                    'tags' => ['Empfang'],
                    'availability' => [
                        [
                            'id' => wp_generate_uuid4(),
                            'date' => $today,
                            'start' => '08:00',
                            'end' => '18:00',
                            'capacity' => 5,
                            'notes' => '',
                        ],
                    ],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'rooms' => [
                [
                    'id' => wp_generate_uuid4(),
                    'name' => __('Seminarraum', 'bookando'),
                    'description' => __('Geeignet für Theorieunterricht.', 'bookando'),
                    'capacity' => 12,
                    'tags' => ['Theorie'],
                    'availability' => [],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'materials' => [
                [
                    'id' => wp_generate_uuid4(),
                    'name' => __('Fahrzeug A', 'bookando'),
                    'description' => __('Limousine Automatik', 'bookando'),
                    'capacity' => 1,
                    'tags' => ['Auto'],
                    'availability' => [],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
        ];
    }

    private static function optionKey(int $tenantId): string
    {
        return self::OPTION_KEY_BASE . '_' . max(1, $tenantId);
    }

    private static function migrateLegacyState(string $targetKey): void
    {
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return;
        }

        $sentinel = new \stdClass();
        $current = get_option($targetKey, $sentinel);
        if ($current !== $sentinel) {
            return;
        }

        $legacy = get_option(self::OPTION_KEY_BASE, $sentinel);
        if ($legacy === $sentinel) {
            return;
        }

        if (!is_array($legacy)) {
            $legacy = [];
        }

        update_option($targetKey, $legacy, false);

        if (function_exists('delete_option')) {
            \delete_option(self::OPTION_KEY_BASE);
        }
    }

    public static function resetCache(): void
    {
        self::$stateCache = [];
    }
}
