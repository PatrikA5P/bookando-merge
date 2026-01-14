<?php
namespace Bookando\Modules\Appointments;

use Bookando\Core\Model\BaseModel;
use Bookando\Core\Tenant\TenantManager;
use wpdb;

class Model extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->tableName = $this->table('appointments');
    }

    protected function allowedOrderBy(): array
    {
        return ['starts_at_utc', 'created_at', 'status'];
    }

    public function timeline(string $fromUtc, string $toUtc): array
    {
        $users = $this->table('users');
        $services = $this->table('offers');
        $events = $this->table('events');

        $sql = "
            SELECT
                a.tenant_id,
                a.id,
                a.customer_id,
                a.employee_id,
                a.service_id,
                a.event_id,
                a.status,
                a.starts_at_utc,
                a.ends_at_utc,
                a.client_tz,
                a.price,
                a.persons,
                a.meta,
                a.created_at,
                a.updated_at,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                s.title AS service_title,
                s.status AS service_status,
                e.name AS event_name,
                e.type AS event_type
            FROM {$this->tableName} AS a
            LEFT JOIN {$users} AS u ON u.id = a.customer_id
            LEFT JOIN {$services} AS s ON s.id = a.service_id
            LEFT JOIN {$events} AS e ON e.id = a.event_id
            WHERE a.starts_at_utc BETWEEN %s AND %s
        ";

        return $this->fetchAll($sql, [$fromUtc, $toUtc]);
    }

    public function createAppointment(array $data): int
    {
        $allowedStatus = ['pending','approved','confirmed','cancelled','noshow'];
        $status = isset($data['status']) && in_array($data['status'], $allowedStatus, true)
            ? $data['status']
            : 'confirmed';

        $payload = [
            'customer_id'   => $data['customer_id'] ?? null,
            'employee_id'   => $data['employee_id'] ?? null,
            'service_id'    => $data['service_id'] ?? null,
            'location_id'   => $data['location_id'] ?? null,
            'event_id'      => $data['event_id'] ?? null,
            'status'        => $status,
            'starts_at_utc' => $data['starts_at_utc'],
            'ends_at_utc'   => $data['ends_at_utc'],
            'client_tz'     => $data['client_tz'] ?? null,
            'price'         => isset($data['price']) ? (float) $data['price'] : null,
            'persons'       => isset($data['persons']) ? max(1, (int) $data['persons']) : 1,
            'meta'          => !empty($data['meta']) ? wp_json_encode($data['meta']) : null,
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
        ];

        return $this->insert($payload);
    }

    public function updateAppointment(int $id, array $data): bool
    {
        $allowedStatus = ['pending','approved','confirmed','cancelled','noshow'];

        $updates = [];

        if (isset($data['customer_id'])) {
            $updates['customer_id'] = (int) $data['customer_id'];
        }
        if (isset($data['employee_id'])) {
            $updates['employee_id'] = $data['employee_id'] ? (int) $data['employee_id'] : null;
        }
        if (isset($data['service_id'])) {
            $updates['service_id'] = $data['service_id'] ? (int) $data['service_id'] : null;
        }
        if (isset($data['location_id'])) {
            $updates['location_id'] = $data['location_id'] ? (int) $data['location_id'] : null;
        }
        if (isset($data['event_id'])) {
            $updates['event_id'] = $data['event_id'] ? (int) $data['event_id'] : null;
        }
        if (isset($data['status']) && in_array($data['status'], $allowedStatus, true)) {
            $updates['status'] = $data['status'];
        }
        if (isset($data['starts_at_utc'])) {
            $updates['starts_at_utc'] = $data['starts_at_utc'];
        }
        if (isset($data['ends_at_utc'])) {
            $updates['ends_at_utc'] = $data['ends_at_utc'];
        }
        if (isset($data['client_tz'])) {
            $updates['client_tz'] = $data['client_tz'];
        }
        if (isset($data['price'])) {
            $updates['price'] = (float) $data['price'];
        }
        if (isset($data['persons'])) {
            $updates['persons'] = max(1, (int) $data['persons']);
        }
        if (isset($data['meta'])) {
            $updates['meta'] = !empty($data['meta']) ? wp_json_encode($data['meta']) : null;
        }

        $updates['updated_at'] = current_time('mysql');

        return $this->update($id, $updates) > 0;
    }

    public function getCustomerOptions(string $search = '', int $limit = 25): array
    {
        global $wpdb;

        $tenantId = TenantManager::currentTenantId();
        if (!$tenantId) {
            return [];
        }

        $table = $this->table('users');
        $where = "WHERE tenant_id = %d AND JSON_CONTAINS(roles, '\"customer\"')";
        $args = [$tenantId];

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)";
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        $sql = "
            SELECT id, first_name, last_name, email, phone
            FROM {$table}
            {$where}
            AND (status IS NULL OR status <> 'deleted')
            ORDER BY last_name ASC, first_name ASC
            LIMIT %d
        ";
        $args[] = max(1, min(200, $limit));

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A);
        return $rows ?: [];
    }

    public function getServiceOptions(string $search = '', int $limit = 50): array
    {
        global $wpdb;
        $table = $this->table('offers');

        $tenantId = TenantManager::currentTenantId();
        $hasTenantColumn = $this->columnExists($table, 'tenant_id');

        $where = [];
        $args = [];

        if ($hasTenantColumn && $tenantId) {
            $where[] = 'tenant_id = %d';
            $args[] = $tenantId;
        }

        if ($search !== '') {
            $where[] = 'title LIKE %s';
            $args[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT id, title AS name, status
            FROM {$table}
            {$whereSql}
            ORDER BY title ASC
            LIMIT %d
        ";
        $args[] = max(1, min(200, $limit));

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A);
        return $rows ?: [];
    }

    private function columnExists(string $table, string $column): bool
    {
        $db = $this->db();
        $sql = $db->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column);
        return (bool) $db->get_var($sql);
    }
}
