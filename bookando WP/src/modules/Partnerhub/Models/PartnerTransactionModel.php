<?php

namespace Bookando\Modules\Partnerhub\Models;

use Bookando\Core\Database\BaseModel;

class PartnerTransactionModel extends BaseModel
{
    protected string $table = 'bookando_partner_transactions';

    protected array $fillable = [
        'tenant_id', 'partner_id', 'mapping_id', 'booking_id', 'booking_type', 'booking_date',
        'transaction_type', 'direction', 'gross_amount', 'commission_rate', 'commission_amount',
        'net_amount', 'currency', 'status', 'paid_at', 'payment_reference', 'notes',
        'created_by', 'updated_by',
    ];

    protected array $casts = [
        'gross_amount' => 'float',
        'commission_rate' => 'float',
        'commission_amount' => 'float',
        'net_amount' => 'float',
    ];

    public function get_partner_revenue(int $partner_id, ?string $from = null, ?string $to = null): float
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $sql = "SELECT SUM(commission_amount) FROM {$table}
                WHERE tenant_id = %d AND partner_id = %d
                AND transaction_type = 'commission_earned' AND status = 'confirmed'
                AND deleted_at IS NULL";

        $params = [$tenant_id, $partner_id];

        if ($from) {
            $sql .= " AND booking_date >= %s";
            $params[] = $from;
        }

        if ($to) {
            $sql .= " AND booking_date <= %s";
            $params[] = $to;
        }

        return (float) $wpdb->get_var($wpdb->prepare($sql, $params));
    }

    public function get_outstanding_payments(int $partner_id): array
    {
        return $this->get_all([
            'partner_id' => $partner_id,
            'status' => 'confirmed',
            'paid_at' => null,
        ]);
    }
}
