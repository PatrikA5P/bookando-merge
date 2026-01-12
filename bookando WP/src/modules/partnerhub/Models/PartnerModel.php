<?php

namespace Bookando\Modules\Partnerhub\Models;

use Bookando\Core\Database\BaseModel;

/**
 * Partner Model
 *
 * Verwaltet Partner-Entitäten und deren Vertragsdaten
 */
class PartnerModel extends BaseModel
{
    protected string $table = 'bookando_partners';

    protected array $fillable = [
        'tenant_id',
        'partner_type',
        'name',
        'company_name',
        'website_url',
        'api_endpoint',
        'contact_name',
        'contact_email',
        'contact_phone',
        'contract_type',
        'contract_signed_at',
        'contract_valid_until',
        'contract_file_id',
        'api_key',
        'api_secret',
        'webhook_url',
        'webhook_secret',
        'commission_type',
        'commission_value',
        'commission_currency',
        'status',
        'settings',
        'data_processing_agreement_accepted',
        'data_retention_days',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'settings' => 'json',
        'data_processing_agreement_accepted' => 'bool',
        'data_retention_days' => 'int',
        'commission_value' => 'float',
    ];

    /**
     * Get active partners
     */
    public function get_active_partners(array $filters = []): array
    {
        $filters['status'] = 'active';
        return $this->get_all($filters);
    }

    /**
     * Get partner by API key
     */
    public function get_by_api_key(string $api_key): ?object
    {
        global $wpdb;
        $table = $this->get_table_name();

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE api_key = %s
             AND status = 'active'
             AND deleted_at IS NULL
             LIMIT 1",
            $api_key
        );

        $result = $wpdb->get_row($sql);
        return $result ?: null;
    }

    /**
     * Generate unique API key
     */
    public function generate_api_key(): string
    {
        do {
            $api_key = 'pk_' . bin2hex(random_bytes(32));
            $exists = $this->get_by_api_key($api_key);
        } while ($exists);

        return $api_key;
    }

    /**
     * Generate API secret
     */
    public function generate_api_secret(): string
    {
        return 'sk_' . bin2hex(random_bytes(32));
    }

    /**
     * Check if contract is valid
     */
    public function is_contract_valid(int $partner_id): bool
    {
        $partner = $this->get_by_id($partner_id);

        if (!$partner || !$partner->contract_signed_at) {
            return false;
        }

        if ($partner->contract_valid_until) {
            $valid_until = strtotime($partner->contract_valid_until);
            if ($valid_until < time()) {
                return false;
            }
        }

        return $partner->data_processing_agreement_accepted;
    }

    /**
     * Get partners with expiring contracts
     */
    public function get_expiring_contracts(int $days = 30): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $date = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND status = 'active'
             AND contract_valid_until IS NOT NULL
             AND contract_valid_until <= %s
             AND contract_valid_until >= NOW()
             AND deleted_at IS NULL
             ORDER BY contract_valid_until ASC",
            $tenant_id,
            $date
        );

        return $wpdb->get_results($sql) ?: [];
    }

    /**
     * Validate partner data
     */
    protected function validate(array $data): array
    {
        $errors = [];

        // Name required
        if (empty($data['name'])) {
            $errors['name'] = __('Name ist erforderlich', 'bookando');
        }

        // Email validation
        if (!empty($data['contact_email']) && !is_email($data['contact_email'])) {
            $errors['contact_email'] = __('Ungültige E-Mail-Adresse', 'bookando');
        }

        // URL validation
        if (!empty($data['website_url']) && !filter_var($data['website_url'], FILTER_VALIDATE_URL)) {
            $errors['website_url'] = __('Ungültige Website-URL', 'bookando');
        }

        if (!empty($data['api_endpoint']) && !filter_var($data['api_endpoint'], FILTER_VALIDATE_URL)) {
            $errors['api_endpoint'] = __('Ungültige API-Endpoint-URL', 'bookando');
        }

        // Commission validation
        if (!empty($data['commission_type']) && $data['commission_type'] !== 'none') {
            if (empty($data['commission_value']) || $data['commission_value'] < 0) {
                $errors['commission_value'] = __('Provisions-Wert muss größer als 0 sein', 'bookando');
            }

            if ($data['commission_type'] === 'percentage' && $data['commission_value'] > 100) {
                $errors['commission_value'] = __('Prozentsatz darf nicht größer als 100 sein', 'bookando');
            }
        }

        return $errors;
    }

    /**
     * Before insert hook
     */
    protected function before_insert(array &$data): void
    {
        // Auto-generate API credentials if not provided
        if (empty($data['api_key'])) {
            $data['api_key'] = $this->generate_api_key();
        }

        if (empty($data['api_secret'])) {
            $data['api_secret'] = password_hash($this->generate_api_secret(), PASSWORD_BCRYPT);
        }

        // Set default data retention
        if (!isset($data['data_retention_days'])) {
            $data['data_retention_days'] = 365;
        }
    }
}
