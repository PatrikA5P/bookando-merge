<?php

declare(strict_types=1);

namespace Bookando\Core\Contracts;

/**
 * Tenant manager contract.
 *
 * Defines the interface for multi-tenant operations,
 * enabling tenant isolation across different platforms.
 */
interface TenantManagerInterface
{
    /**
     * Gets the current tenant ID.
     *
     * @return int|null Current tenant ID or null if not in tenant context
     */
    public function currentTenantId(): ?int;

    /**
     * Switches to a different tenant context.
     *
     * @param int $tenantId Tenant ID to switch to
     * @return void
     */
    public function switchTenant(int $tenantId): void;

    /**
     * Checks if a tenant exists.
     *
     * @param int $tenantId Tenant ID
     * @return bool
     */
    public function tenantExists(int $tenantId): bool;
}
