<?php

namespace Bookando\Modules\Partnerhub\Models;

use Bookando\Core\Database\BaseModel;

/**
 * Partner Audit Log Model
 *
 * Vollständiger Audit-Trail für Partner-Operationen (DSGVO Art. 30)
 */
class PartnerAuditLogModel extends BaseModel
{
    protected string $table = 'bookando_partner_audit_logs';

    // Audit logs have no soft deletes - they must be permanent
    protected bool $use_soft_deletes = false;

    protected array $fillable = [
        'tenant_id',
        'partner_id',
        'action_type',
        'entity_type',
        'entity_id',
        'user_id',
        'user_role',
        'ip_address',
        'user_agent',
        'description',
        'old_values',
        'new_values',
        'involves_personal_data',
        'data_subject_id',
    ];

    protected array $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'involves_personal_data' => 'bool',
        'partner_id' => 'int',
        'entity_id' => 'int',
        'user_id' => 'int',
        'data_subject_id' => 'int',
    ];

    /**
     * Log an action
     */
    public function log_action(string $action_type, array $context = []): int
    {
        $user = wp_get_current_user();

        $data = [
            'tenant_id' => $this->get_tenant_id(),
            'action_type' => $action_type,
            'user_id' => $user->ID ?: null,
            'user_role' => !empty($user->roles) ? $user->roles[0] : null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];

        // Merge context
        $data = array_merge($data, $context);

        return $this->insert($data);
    }

    /**
     * Log partner creation
     */
    public function log_partner_created(int $partner_id, array $data): int
    {
        return $this->log_action('partner_created', [
            'partner_id' => $partner_id,
            'entity_type' => 'partner',
            'entity_id' => $partner_id,
            'description' => 'Partner wurde erstellt',
            'new_values' => $data,
            'involves_personal_data' => true,
        ]);
    }

    /**
     * Log partner updated
     */
    public function log_partner_updated(int $partner_id, array $old_values, array $new_values): int
    {
        return $this->log_action('partner_updated', [
            'partner_id' => $partner_id,
            'entity_type' => 'partner',
            'entity_id' => $partner_id,
            'description' => 'Partner wurde aktualisiert',
            'old_values' => $old_values,
            'new_values' => $new_values,
            'involves_personal_data' => true,
        ]);
    }

    /**
     * Log partner deleted
     */
    public function log_partner_deleted(int $partner_id, array $old_values): int
    {
        return $this->log_action('partner_deleted', [
            'partner_id' => $partner_id,
            'entity_type' => 'partner',
            'entity_id' => $partner_id,
            'description' => 'Partner wurde gelöscht',
            'old_values' => $old_values,
            'involves_personal_data' => true,
        ]);
    }

    /**
     * Log data share
     */
    public function log_data_shared(int $partner_id, int $customer_id, array $data_categories, string $purpose): int
    {
        return $this->log_action('data_shared', [
            'partner_id' => $partner_id,
            'entity_type' => 'consent',
            'description' => sprintf('Kundendaten wurden mit Partner geteilt (Zweck: %s)', $purpose),
            'new_values' => [
                'data_categories' => $data_categories,
                'purpose' => $purpose,
            ],
            'involves_personal_data' => true,
            'data_subject_id' => $customer_id,
        ]);
    }

    /**
     * Log consent given
     */
    public function log_consent_given(int $consent_id, int $partner_id, int $customer_id, array $data): int
    {
        return $this->log_action('consent_given', [
            'partner_id' => $partner_id,
            'entity_type' => 'consent',
            'entity_id' => $consent_id,
            'description' => 'Einwilligung wurde erteilt',
            'new_values' => $data,
            'involves_personal_data' => true,
            'data_subject_id' => $customer_id,
        ]);
    }

    /**
     * Log consent revoked
     */
    public function log_consent_revoked(int $consent_id, int $partner_id, int $customer_id, string $reason): int
    {
        return $this->log_action('consent_revoked', [
            'partner_id' => $partner_id,
            'entity_type' => 'consent',
            'entity_id' => $consent_id,
            'description' => 'Einwilligung wurde widerrufen',
            'new_values' => ['reason' => $reason],
            'involves_personal_data' => true,
            'data_subject_id' => $customer_id,
        ]);
    }

    /**
     * Log mapping created
     */
    public function log_mapping_created(int $mapping_id, int $partner_id, array $data): int
    {
        return $this->log_action('mapping_created', [
            'partner_id' => $partner_id,
            'entity_type' => 'mapping',
            'entity_id' => $mapping_id,
            'description' => 'Mapping wurde erstellt',
            'new_values' => $data,
        ]);
    }

    /**
     * Log feed accessed
     */
    public function log_feed_accessed(int $feed_id, int $partner_id, string $ip_address): int
    {
        return $this->log_action('feed_accessed', [
            'partner_id' => $partner_id,
            'entity_type' => 'feed',
            'entity_id' => $feed_id,
            'description' => 'Feed wurde abgerufen',
            'ip_address' => $ip_address,
        ]);
    }

    /**
     * Get logs for partner
     */
    public function get_partner_logs(int $partner_id, int $limit = 100): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND partner_id = %d
             ORDER BY created_at DESC
             LIMIT %d",
            $tenant_id,
            $partner_id,
            $limit
        );

        return $wpdb->get_results($sql) ?: [];
    }

    /**
     * Get logs for customer (data subject)
     */
    public function get_customer_logs(int $customer_id, int $limit = 100): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND data_subject_id = %d
             ORDER BY created_at DESC
             LIMIT %d",
            $tenant_id,
            $customer_id,
            $limit
        );

        return $wpdb->get_results($sql) ?: [];
    }

    /**
     * Get logs involving personal data
     */
    public function get_personal_data_logs(int $days = 30): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND involves_personal_data = 1
             AND created_at >= %s
             ORDER BY created_at DESC",
            $tenant_id,
            $date
        );

        return $wpdb->get_results($sql) ?: [];
    }

    /**
     * Export logs for DSGVO compliance (Art. 15 - Right to access)
     */
    public function export_customer_logs(int $customer_id): array
    {
        $logs = $this->get_customer_logs($customer_id, 1000);

        $export = [];
        foreach ($logs as $log) {
            $export[] = [
                'date' => $log->created_at,
                'action' => $log->action_type,
                'description' => $log->description,
                'partner_id' => $log->partner_id,
            ];
        }

        return $export;
    }

    /**
     * Get client IP
     */
    private function get_client_ip(): string
    {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * No updates allowed on audit logs
     */
    public function update(int $id, array $data): bool
    {
        // Audit logs are immutable
        return false;
    }

    /**
     * No deletes allowed on audit logs
     */
    public function delete(int $id): bool
    {
        // Audit logs cannot be deleted
        return false;
    }
}
