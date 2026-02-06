<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Ports\CachePort;

/**
 * In-memory cache for testing. No persistence, no TTL enforcement.
 */
final class InMemoryCache implements CachePort
{
    /** @var array<string, mixed> */
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttlSeconds = 3600): bool
    {
        $this->store[$key] = $value;
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    public function flush(string $prefix = ''): bool
    {
        if ($prefix === '') {
            $this->store = [];
        } else {
            foreach (array_keys($this->store) as $key) {
                if (str_starts_with($key, $prefix)) {
                    unset($this->store[$key]);
                }
            }
        }
        return true;
    }

    /** Inspect stored keys (test helper). */
    public function all(): array
    {
        return $this->store;
    }
}
