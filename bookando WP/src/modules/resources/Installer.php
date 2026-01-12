<?php
declare(strict_types=1);

namespace Bookando\Modules\resources;

use Bookando\Core\Tenant\TenantManager;

final class Installer
{
    /**
     * Seeds the default resource configuration for the primary tenant. The defaults are
     * persisted into the option store so that subsequent requests can reuse the stored
     * IDs instead of regenerating them on every call to {@see StateRepository::getState()}.
     */
    public static function install(): void
    {
        $tenantId = TenantManager::currentTenantId();

        // Ensure tenant resolution starts from a clean slate when the installer runs
        // during activation or CLI contexts.
        TenantManager::setCurrentTenantId($tenantId);

        StateRepository::seedDefaultsForTenant($tenantId);
    }

    /**
     * Helper to seed a specific tenant, e.g. when provisioning tenants via CLI tools.
     */
    public static function seedTenant(int $tenantId): void
    {
        StateRepository::seedDefaultsForTenant($tenantId, true);
    }
}
