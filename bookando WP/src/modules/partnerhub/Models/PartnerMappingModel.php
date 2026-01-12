<?php

namespace Bookando\Modules\Partnerhub\Models;

use Bookando\Core\Database\BaseModel;

/**
 * Partner Mapping Model
 *
 * Verwaltet Verknüpfungen zwischen lokalen Angeboten und Remote-Listings
 */
class PartnerMappingModel extends BaseModel
{
    protected string $table = 'bookando_partner_mappings';

    protected array $fillable = [
        'tenant_id',
        'partner_id',
        'local_type',
        'local_id',
        'remote_type',
        'remote_id',
        'remote_url',
        'sync_status',
        'sync_direction',
        'last_synced_at',
        'sync_error_message',
        'override_title',
        'override_description',
        'override_price',
        'settings',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'settings' => 'json',
        'override_price' => 'float',
        'local_id' => 'int',
        'partner_id' => 'int',
    ];

    /**
     * Get mappings for a partner
     */
    public function get_by_partner(int $partner_id, array $filters = []): array
    {
        $filters['partner_id'] = $partner_id;
        return $this->get_all($filters);
    }

    /**
     * Get mapping for a local item
     */
    public function get_by_local_item(string $type, int $id, ?int $partner_id = null): ?object
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $sql = "SELECT * FROM {$table}
                WHERE tenant_id = %d
                AND local_type = %s
                AND local_id = %d";

        $params = [$tenant_id, $type, $id];

        if ($partner_id) {
            $sql .= " AND partner_id = %d";
            $params[] = $partner_id;
        }

        $sql .= " AND deleted_at IS NULL LIMIT 1";

        $result = $wpdb->get_row($wpdb->prepare($sql, $params));
        return $result ?: null;
    }

    /**
     * Get active mappings
     */
    public function get_active_mappings(array $filters = []): array
    {
        $filters['sync_status'] = 'active';
        return $this->get_all($filters);
    }

    /**
     * Get mappings that need sync
     */
    public function get_pending_sync(int $minutes = 60): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $cutoff = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND sync_status IN ('active', 'pending')
             AND (last_synced_at IS NULL OR last_synced_at < %s)
             AND deleted_at IS NULL
             ORDER BY last_synced_at ASC
             LIMIT 100",
            $tenant_id,
            $cutoff
        );

        return $wpdb->get_results($sql) ?: [];
    }

    /**
     * Update sync status
     */
    public function update_sync_status(int $mapping_id, string $status, ?string $error_message = null): bool
    {
        $data = [
            'sync_status' => $status,
            'last_synced_at' => current_time('mysql'),
        ];

        if ($error_message) {
            $data['sync_error_message'] = $error_message;
        }

        return $this->update($mapping_id, $data);
    }

    /**
     * Get mappings with errors
     */
    public function get_error_mappings(): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND sync_status = 'error'
             AND deleted_at IS NULL
             ORDER BY updated_at DESC",
            $tenant_id
        );

        return $wpdb->get_results($sql) ?: [];
    }

    /**
     * Validate mapping data
     */
    protected function validate(array $data): array
    {
        $errors = [];

        // Partner required
        if (empty($data['partner_id'])) {
            $errors['partner_id'] = __('Partner ist erforderlich', 'bookando');
        }

        // Local item required
        if (empty($data['local_type'])) {
            $errors['local_type'] = __('Lokaler Typ ist erforderlich', 'bookando');
        }

        if (empty($data['local_id'])) {
            $errors['local_id'] = __('Lokale ID ist erforderlich', 'bookando');
        }

        // Valid local type
        $valid_types = ['service', 'event', 'package', 'voucher'];
        if (!empty($data['local_type']) && !in_array($data['local_type'], $valid_types)) {
            $errors['local_type'] = __('Ungültiger lokaler Typ', 'bookando');
        }

        return $errors;
    }
}
