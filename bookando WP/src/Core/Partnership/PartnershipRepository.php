<?php
declare(strict_types=1);

namespace Bookando\Core\Partnership;

use wpdb;

/**
 * Repository für Partner-Beziehungen (B2B-Kooperationen).
 *
 * Verwaltet die Beziehungen zwischen Tenants:
 * - Vertrauenswürdige Partner
 * - Provisionsvereinbarungen
 * - Freigabeberechtigungen
 * - Zeitlich begrenzte Kooperationen
 */
final class PartnershipRepository
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'bookando_partner_relationships';
    }

    /**
     * Erstellt eine neue Partner-Beziehung.
     *
     * @param array $data {
     *   @type int    $primary_tenant        ID des Haupt-Tenants
     *   @type int    $partner_tenant        ID des Partner-Tenants
     *   @type string $relationship_type     'trusted_partner', 'temporary_referral'
     *   @type string $status                'active', 'suspended', 'terminated'
     *   @type array  $sharing_permissions   ['customers' => ['view', 'edit'], ...]
     *   @type string $commission_type       'fixed', 'percentage'
     *   @type float  $commission_value      Provisions-Wert
     *   @type array  $metadata              Zusätzliche Metadaten
     *   @type string $expires_at            Ablaufdatum (optional)
     * }
     * @return int|false Partnership-ID oder false bei Fehler
     */
    public function create(array $data)
    {
        $defaults = [
            'relationship_type' => 'trusted_partner',
            'status' => 'active',
            'sharing_permissions' => null,
            'commission_type' => 'percentage',
            'commission_value' => 0.00,
            'metadata' => null,
            'expires_at' => null,
        ];

        $data = array_merge($defaults, $data);

        // JSON-Felder encodieren
        if (is_array($data['sharing_permissions'])) {
            $data['sharing_permissions'] = wp_json_encode($data['sharing_permissions']);
        }
        if (is_array($data['metadata'])) {
            $data['metadata'] = wp_json_encode($data['metadata']);
        }

        $result = $this->wpdb->insert(
            $this->table,
            $data,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s']
        );

        return $result ? (int) $this->wpdb->insert_id : false;
    }

    /**
     * Aktualisiert eine Partner-Beziehung.
     *
     * @param int   $id   Partnership-ID
     * @param array $data Zu aktualisierende Felder
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        // JSON-Felder encodieren
        if (isset($data['sharing_permissions']) && is_array($data['sharing_permissions'])) {
            $data['sharing_permissions'] = wp_json_encode($data['sharing_permissions']);
        }
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = wp_json_encode($data['metadata']);
        }

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $id],
            null,
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Findet Partnership-Beziehung zwischen zwei Tenants.
     *
     * @param int $primaryTenant Haupt-Tenant-ID
     * @param int $partnerTenant Partner-Tenant-ID
     * @return array|null
     */
    public function findRelationship(int $primaryTenant, int $partnerTenant): ?array
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE primary_tenant = %d AND partner_tenant = %d",
            $primaryTenant,
            $partnerTenant
        ), ARRAY_A);

        return $row ? $this->decode($row) : null;
    }

    /**
     * Prüft, ob eine aktive Partnership existiert.
     *
     * @param int $primaryTenant
     * @param int $partnerTenant
     * @return bool
     */
    public function isActive(int $primaryTenant, int $partnerTenant): bool
    {
        $partnership = $this->findRelationship($primaryTenant, $partnerTenant);

        if (!$partnership) {
            return false;
        }

        // Status prüfen
        if ($partnership['status'] !== 'active') {
            return false;
        }

        // Ablaufdatum prüfen
        if ($partnership['expires_at'] && strtotime($partnership['expires_at']) < time()) {
            return false;
        }

        return true;
    }

    /**
     * Liefert alle Partner eines Tenants.
     *
     * @param int    $tenantId Tenant-ID
     * @param string $status   Filter nach Status (optional)
     * @return array
     */
    public function findAllPartners(int $tenantId, ?string $status = 'active'): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE primary_tenant = %d";
        $args = [$tenantId];

        if ($status !== null) {
            $sql .= " AND status = %s";
            $args[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$args), ARRAY_A);

        return array_map([$this, 'decode'], $rows);
    }

    /**
     * Liefert alle Tenants, die diesen Tenant als Partner haben.
     *
     * @param int    $tenantId Tenant-ID
     * @param string $status   Filter nach Status (optional)
     * @return array
     */
    public function findAllPrimaries(int $tenantId, ?string $status = 'active'): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE partner_tenant = %d";
        $args = [$tenantId];

        if ($status !== null) {
            $sql .= " AND status = %s";
            $args[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$args), ARRAY_A);

        return array_map([$this, 'decode'], $rows);
    }

    /**
     * Prüft, ob ein Tenant bestimmte Berechtigung für einen anderen Tenant hat.
     *
     * @param int    $primaryTenant
     * @param int    $partnerTenant
     * @param string $resource     z.B. 'customers', 'events'
     * @param string $permission   z.B. 'view', 'edit', 'delete'
     * @return bool
     */
    public function hasPermission(int $primaryTenant, int $partnerTenant, string $resource, string $permission): bool
    {
        $partnership = $this->findRelationship($primaryTenant, $partnerTenant);

        if (!$partnership || !$this->isActive($primaryTenant, $partnerTenant)) {
            return false;
        }

        $permissions = $partnership['sharing_permissions'] ?? [];

        if (empty($permissions[$resource])) {
            return false;
        }

        return in_array($permission, $permissions[$resource], true);
    }

    /**
     * Beendet eine Partnership (soft delete via Status).
     *
     * @param int $id Partnership-ID
     * @return bool
     */
    public function terminate(int $id): bool
    {
        return $this->update($id, ['status' => 'terminated']);
    }

    /**
     * Suspendiert eine Partnership.
     *
     * @param int $id Partnership-ID
     * @return bool
     */
    public function suspend(int $id): bool
    {
        return $this->update($id, ['status' => 'suspended']);
    }

    /**
     * Reaktiviert eine suspendierte Partnership.
     *
     * @param int $id Partnership-ID
     * @return bool
     */
    public function activate(int $id): bool
    {
        return $this->update($id, ['status' => 'active']);
    }

    /**
     * Löscht eine Partnership (hard delete).
     *
     * @param int $id Partnership-ID
     * @return bool
     */
    public function delete(int $id): bool
    {
        $result = $this->wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Findet Partnership by ID.
     *
     * @param int $id Partnership-ID
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        ), ARRAY_A);

        return $row ? $this->decode($row) : null;
    }

    /**
     * Dekodiert JSON-Felder.
     *
     * @param array $row
     * @return array
     */
    private function decode(array $row): array
    {
        if (isset($row['sharing_permissions']) && is_string($row['sharing_permissions'])) {
            $row['sharing_permissions'] = json_decode($row['sharing_permissions'], true);
        }
        if (isset($row['metadata']) && is_string($row['metadata'])) {
            $row['metadata'] = json_decode($row['metadata'], true);
        }

        // Numerische Felder casten
        $row['id'] = (int) $row['id'];
        $row['primary_tenant'] = (int) $row['primary_tenant'];
        $row['partner_tenant'] = (int) $row['partner_tenant'];
        $row['commission_value'] = (float) $row['commission_value'];

        return $row;
    }
}
