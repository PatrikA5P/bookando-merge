<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Tenant-scoped settings port.
 *
 * Provides a host-agnostic interface for reading and writing configuration
 * settings. All operations are implicitly scoped to the current tenant;
 * implementations MUST ensure that one tenant cannot read or modify another
 * tenant's settings.
 *
 * Settings are organised by namespace (e.g. "general", "email", "booking")
 * and key within that namespace.
 */
interface SettingsPort
{
    /**
     * Retrieve a single setting value.
     *
     * @param string $namespace Settings namespace (e.g. "general").
     * @param string $key       Setting key within the namespace.
     * @param mixed  $default   Value to return if the setting does not exist.
     *
     * @return mixed The setting value, or `$default` if not found.
     */
    public function get(string $namespace, string $key, mixed $default = null): mixed;

    /**
     * Store a single setting value.
     *
     * If the setting already exists it is overwritten; otherwise it is created.
     *
     * @param string $namespace Settings namespace.
     * @param string $key       Setting key within the namespace.
     * @param mixed  $value     The value to store. Must be serialisable.
     */
    public function set(string $namespace, string $key, mixed $value): void;

    /**
     * Retrieve all settings within a namespace.
     *
     * @param string $namespace Settings namespace.
     *
     * @return array<string, mixed> Key => value map of all settings in the namespace.
     */
    public function getAll(string $namespace): array;

    /**
     * Delete a single setting.
     *
     * @param string $namespace Settings namespace.
     * @param string $key       Setting key within the namespace.
     */
    public function delete(string $namespace, string $key): void;
}
