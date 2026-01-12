<?php
// src/Core/Model/Traits/MultiTenantTrait.php

namespace Bookando\Core\Model\Traits;

use wpdb;

trait MultiTenantTrait {
    protected function applyTenant(string $sql, array $args = []): array {
        /** @var wpdb $wpdb */
        global $wpdb;
        $tenantId = \Bookando\Core\Tenant\TenantManager::currentTenantId();
        if ($tenantId === null) {
            // harte Abriegelung: ohne Tenant kein Query
            throw new \RuntimeException('Tenant context missing');
        }
        // Erweitere WHERE sicher
        $sqlWrapped = "SELECT * FROM ({$sql}) as t WHERE t.tenant_id = %d";
        array_push($args, $tenantId);
        return [$sqlWrapped, $args];
    }
}
