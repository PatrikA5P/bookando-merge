<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Authorization / permission-checking port.
 *
 * Provides a host-agnostic interface for verifying that the currently
 * authenticated user has the required permissions to perform an action.
 * Implementations resolve permissions from the host's role/capability
 * system (e.g. WordPress capabilities, Symfony voters, RBAC tables, etc.).
 */
interface AuthorizationPort
{
    /**
     * Check whether the current user holds the given permission.
     *
     * @param string $permission Permission slug to check (e.g. "booking.create").
     *
     * @return bool True if the current user has the permission.
     */
    public function can(string $permission): bool;

    /**
     * Check whether the current user can manage (access) the given module.
     *
     * @param string $moduleSlug Module slug identifier (e.g. "reservations").
     *
     * @return bool True if the current user has management access to the module.
     */
    public function canManageModule(string $moduleSlug): bool;

    /**
     * Assert that the current user holds the given permission.
     *
     * This is a guard method: if the user does NOT hold the permission, an
     * {@see UnauthorizedException} is thrown. Use this in use-case handlers
     * to enforce access control as a precondition.
     *
     * @param string $permission Permission slug to assert.
     *
     * @throws UnauthorizedException If the current user lacks the permission.
     */
    public function authorize(string $permission): void;

    /**
     * Check whether the given user ID matches the currently authenticated user.
     *
     * Useful for "self-only" operations where a user may edit their own
     * profile but not another user's.
     *
     * @param string $userId The user ID to compare against the current user.
     *
     * @return bool True if `$userId` matches the current user's ID.
     */
    public function isSelf(string $userId): bool;
}
