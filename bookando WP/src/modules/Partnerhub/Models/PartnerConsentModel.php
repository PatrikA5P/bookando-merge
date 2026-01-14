<?php

namespace Bookando\Modules\Partnerhub\Models;

use Bookando\Core\Database\BaseModel;

/**
 * Partner Consent Model
 *
 * DSGVO-konforme Verwaltung von Einwilligungen für Kundendatenaustausch
 */
class PartnerConsentModel extends BaseModel
{
    protected string $table = 'bookando_partner_consents';

    protected array $fillable = [
        'tenant_id',
        'partner_id',
        'customer_id',
        'purpose',
        'purpose_description',
        'data_categories',
        'consent_given',
        'consent_method',
        'consent_text',
        'consent_version',
        'consent_language',
        'consent_ip_address',
        'consent_user_agent',
        'consent_timestamp',
        'valid_from',
        'valid_until',
        'revoked',
        'revoked_at',
        'revocation_reason',
        'status',
        'legal_basis',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'data_categories' => 'json',
        'consent_given' => 'bool',
        'revoked' => 'bool',
        'partner_id' => 'int',
        'customer_id' => 'int',
    ];

    /**
     * Get active consent for customer and partner
     */
    public function get_active_consent(int $customer_id, int $partner_id, string $purpose): ?object
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();
        $now = current_time('mysql');

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND customer_id = %d
             AND partner_id = %d
             AND purpose = %s
             AND status = 'active'
             AND consent_given = 1
             AND revoked = 0
             AND (valid_from IS NULL OR valid_from <= %s)
             AND (valid_until IS NULL OR valid_until >= %s)
             AND deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT 1",
            $tenant_id,
            $customer_id,
            $partner_id,
            $purpose,
            $now,
            $now
        );

        $result = $wpdb->get_row($sql);
        return $result ?: null;
    }

    /**
     * Check if customer has given consent
     */
    public function has_consent(int $customer_id, int $partner_id, string $purpose, array $data_categories = []): bool
    {
        $consent = $this->get_active_consent($customer_id, $partner_id, $purpose);

        if (!$consent) {
            return false;
        }

        // Check if all required data categories are covered
        if (!empty($data_categories)) {
            $allowed_categories = json_decode($consent->data_categories, true) ?: [];

            foreach ($data_categories as $category) {
                if (!in_array($category, $allowed_categories)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create consent record
     */
    public function create_consent(array $data): int
    {
        // Add IP and User Agent if not provided
        if (empty($data['consent_ip_address'])) {
            $data['consent_ip_address'] = $this->get_client_ip();
        }

        if (empty($data['consent_user_agent'])) {
            $data['consent_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }

        if (empty($data['consent_timestamp'])) {
            $data['consent_timestamp'] = current_time('mysql');
        }

        // Set default status
        if (empty($data['status'])) {
            $data['status'] = $data['consent_given'] ? 'active' : 'pending';
        }

        return $this->insert($data);
    }

    /**
     * Revoke consent
     */
    public function revoke_consent(int $consent_id, string $reason = ''): bool
    {
        $data = [
            'revoked' => true,
            'revoked_at' => current_time('mysql'),
            'revocation_reason' => $reason,
            'status' => 'revoked',
        ];

        return $this->update($consent_id, $data);
    }

    /**
     * Get expiring consents
     */
    public function get_expiring_consents(int $days = 30): array
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();

        $date = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $now = current_time('mysql');

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE tenant_id = %d
             AND status = 'active'
             AND valid_until IS NOT NULL
             AND valid_until <= %s
             AND valid_until >= %s
             AND deleted_at IS NULL
             ORDER BY valid_until ASC",
            $tenant_id,
            $date,
            $now
        );

        return $wpdb->get_results($sql) ?: [];
    }

    /**
     * Get all consents for a customer
     */
    public function get_customer_consents(int $customer_id, bool $active_only = false): array
    {
        $filters = ['customer_id' => $customer_id];

        if ($active_only) {
            $filters['status'] = 'active';
            $filters['revoked'] = 0;
        }

        return $this->get_all($filters);
    }

    /**
     * Auto-expire consents
     */
    public function auto_expire_consents(): int
    {
        global $wpdb;
        $table = $this->get_table_name();
        $tenant_id = $this->get_tenant_id();
        $now = current_time('mysql');

        $sql = $wpdb->prepare(
            "UPDATE {$table}
             SET status = 'expired'
             WHERE tenant_id = %d
             AND status = 'active'
             AND valid_until IS NOT NULL
             AND valid_until < %s
             AND deleted_at IS NULL",
            $tenant_id,
            $now
        );

        return $wpdb->query($sql);
    }

    /**
     * Get client IP address
     */
    private function get_client_ip(): string
    {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Validate consent data
     */
    protected function validate(array $data): array
    {
        $errors = [];

        // Required fields
        if (empty($data['partner_id'])) {
            $errors['partner_id'] = __('Partner ist erforderlich', 'bookando');
        }

        if (empty($data['customer_id'])) {
            $errors['customer_id'] = __('Kunde ist erforderlich', 'bookando');
        }

        if (empty($data['purpose'])) {
            $errors['purpose'] = __('Zweck ist erforderlich', 'bookando');
        }

        if (empty($data['data_categories']) || !is_array($data['data_categories'])) {
            $errors['data_categories'] = __('Datenkategorien sind erforderlich', 'bookando');
        }

        if (empty($data['legal_basis'])) {
            $errors['legal_basis'] = __('Rechtsgrundlage ist erforderlich', 'bookando');
        }

        // Date validation
        if (!empty($data['valid_from']) && !empty($data['valid_until'])) {
            if (strtotime($data['valid_from']) > strtotime($data['valid_until'])) {
                $errors['valid_until'] = __('Enddatum muss nach Startdatum liegen', 'bookando');
            }
        }

        return $errors;
    }
}
