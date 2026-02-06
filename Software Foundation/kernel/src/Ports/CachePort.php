<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Cache port with built-in tenant scoping.
 *
 * Provides a host-agnostic interface for key/value caching. Implementations
 * may use the WordPress object cache, APCu, Redis, Memcached, a file-based
 * cache, or any other mechanism available in the host environment.
 *
 * IMPORTANT: Implementations **MUST** prefix every cache key with the current
 * `tenant_id` so that cached data is always isolated per tenant. Callers
 * should use plain, logical keys (e.g. "settings:general"); the tenant prefix
 * is applied transparently by the adapter.
 */
interface CachePort
{
    /**
     * Retrieve a cached value by key.
     *
     * @param string $key     Logical cache key (tenant prefix applied by implementation).
     * @param mixed  $default Value to return when the key does not exist.
     *
     * @return mixed The cached value, or `$default` if not found.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in the cache.
     *
     * @param string $key        Logical cache key (tenant prefix applied by implementation).
     * @param mixed  $value      The value to cache. Must be serialisable.
     * @param int    $ttlSeconds Time-to-live in seconds. Default 3600 (1 hour).
     *
     * @return bool True on success, false on failure.
     */
    public function set(string $key, mixed $value, int $ttlSeconds = 3600): bool;

    /**
     * Remove a single entry from the cache.
     *
     * @param string $key Logical cache key (tenant prefix applied by implementation).
     *
     * @return bool True if the key was deleted (or did not exist), false on failure.
     */
    public function delete(string $key): bool;

    /**
     * Check whether a key exists in the cache.
     *
     * @param string $key Logical cache key (tenant prefix applied by implementation).
     *
     * @return bool True if the key exists and has not expired.
     */
    public function has(string $key): bool;

    /**
     * Flush cache entries matching the given prefix.
     *
     * When `$prefix` is an empty string, all entries for the current tenant
     * are flushed.
     *
     * @param string $prefix Optional key prefix to scope the flush.
     *
     * @return bool True on success, false on failure.
     */
    public function flush(string $prefix = ''): bool;
}
