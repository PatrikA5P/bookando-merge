<?php

namespace Bookando\Modules\Partnerhub\Models;

use Bookando\Core\Database\BaseModel;

class PartnerDataShareModel extends BaseModel
{
    protected string $table = 'bookando_partner_data_shares';
    protected bool $use_soft_deletes = false; // Data shares are permanent records

    protected array $fillable = [
        'tenant_id', 'partner_id', 'consent_id', 'customer_id', 'shared_data', 'data_hash',
        'share_reason', 'booking_id', 'transmission_method', 'transmission_status',
        'transmission_error', 'scheduled_deletion_at', 'deleted_from_partner_at', 'deletion_confirmed',
    ];

    protected array $casts = [
        'shared_data' => 'json',
        'deletion_confirmed' => 'bool',
    ];

    public function create_share(int $partner_id, int $consent_id, int $customer_id, array $data, string $reason): int
    {
        $json_data = json_encode($data);
        $hash = hash('sha256', $json_data);

        return $this->insert([
            'partner_id' => $partner_id,
            'consent_id' => $consent_id,
            'customer_id' => $customer_id,
            'shared_data' => $data,
            'data_hash' => $hash,
            'share_reason' => $reason,
            'transmission_status' => 'pending',
        ]);
    }

    public function get_shares_for_deletion(): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND scheduled_deletion_at IS NOT NULL
             AND scheduled_deletion_at <= NOW()
             AND deletion_confirmed = 0",
            $tenant_id
        );

        return $wpdb->get_results($sql) ?: [];
    }
}
