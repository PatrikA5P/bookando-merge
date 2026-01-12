<?php

namespace Bookando\Modules\Partnerhub\Models;

use Bookando\Core\Database\BaseModel;

/**
 * Partner Rule Model
 *
 * Verwaltet Preis- und Verfügbarkeitsregeln pro Partner
 */
class PartnerRuleModel extends BaseModel
{
    protected string $table = 'bookando_partner_rules';

    protected array $fillable = [
        'tenant_id',
        'partner_id',
        'mapping_id',
        'rule_type',
        'priority',
        'pricing_strategy',
        'pricing_value',
        'min_price',
        'max_price',
        'availability_buffer_minutes',
        'max_bookings_per_day',
        'max_bookings_per_week',
        'require_approval',
        'valid_from',
        'valid_until',
        'valid_days_of_week',
        'valid_time_from',
        'valid_time_until',
        'conditions',
        'status',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'conditions' => 'json',
        'pricing_value' => 'float',
        'min_price' => 'float',
        'max_price' => 'float',
        'priority' => 'int',
        'require_approval' => 'bool',
    ];

    /**
     * Get active rules for partner
     */
    public function get_active_rules(int $partner_id, ?int $mapping_id = null): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();
        $now = current_time('mysql');

        $sql = "SELECT * FROM {$table}
                WHERE tenant_id = %d
                AND partner_id = %d
                AND status = 'active'
                AND (valid_from IS NULL OR valid_from <= %s)
                AND (valid_until IS NULL OR valid_until >= %s)
                AND deleted_at IS NULL";

        $params = [$tenant_id, $partner_id, $now, $now];

        if ($mapping_id !== null) {
            $sql .= " AND (mapping_id IS NULL OR mapping_id = %d)";
            $params[] = $mapping_id;
        }

        $sql .= " ORDER BY priority DESC, created_at DESC";

        return $wpdb->get_results($wpdb->prepare($sql, $params)) ?: [];
    }

    /**
     * Apply pricing rules to a base price
     */
    public function apply_pricing_rules(float $base_price, int $partner_id, ?int $mapping_id = null): float
    {
        $rules = $this->get_active_rules($partner_id, $mapping_id);
        $final_price = $base_price;

        foreach ($rules as $rule) {
            if ($rule->rule_type !== 'pricing') {
                continue;
            }

            switch ($rule->pricing_strategy) {
                case 'fixed':
                    $final_price = (float) $rule->pricing_value;
                    break;

                case 'percentage_markup':
                    $final_price = $base_price * (1 + ($rule->pricing_value / 100));
                    break;

                case 'percentage_discount':
                    $final_price = $base_price * (1 - ($rule->pricing_value / 100));
                    break;
            }

            // Apply min/max constraints
            if ($rule->min_price && $final_price < $rule->min_price) {
                $final_price = (float) $rule->min_price;
            }

            if ($rule->max_price && $final_price > $rule->max_price) {
                $final_price = (float) $rule->max_price;
            }
        }

        return round($final_price, 2);
    }

    /**
     * Check availability rules
     */
    public function check_availability(int $partner_id, ?int $mapping_id = null, ?string $date = null): array
    {
        $rules = $this->get_active_rules($partner_id, $mapping_id);
        $result = [
            'available' => true,
            'requires_approval' => false,
            'buffer_minutes' => 0,
            'restrictions' => [],
        ];

        $check_date = $date ?: current_time('Y-m-d');
        $day_of_week = date('w', strtotime($check_date));
        $time_now = current_time('H:i:s');

        foreach ($rules as $rule) {
            // Check blackout rules
            if ($rule->rule_type === 'blackout') {
                $result['available'] = false;
                $result['restrictions'][] = 'Blackout-Regel aktiv';
                continue;
            }

            // Check availability rules
            if ($rule->rule_type === 'availability') {
                // Day of week check
                if ($rule->valid_days_of_week) {
                    $valid_days = json_decode($rule->valid_days_of_week, true);
                    if (!in_array($day_of_week, $valid_days)) {
                        $result['available'] = false;
                        $result['restrictions'][] = 'Nicht verfügbar an diesem Wochentag';
                    }
                }

                // Time range check
                if ($rule->valid_time_from && $rule->valid_time_until) {
                    if ($time_now < $rule->valid_time_from || $time_now > $rule->valid_time_until) {
                        $result['available'] = false;
                        $result['restrictions'][] = 'Außerhalb der verfügbaren Zeiten';
                    }
                }

                // Buffer minutes
                if ($rule->availability_buffer_minutes > $result['buffer_minutes']) {
                    $result['buffer_minutes'] = $rule->availability_buffer_minutes;
                }

                // Approval required
                if ($rule->require_approval) {
                    $result['requires_approval'] = true;
                }
            }
        }

        return $result;
    }
}
