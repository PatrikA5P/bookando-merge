<?php

declare(strict_types=1);

namespace Bookando\Modules\Customers;

use Bookando\Core\Contracts\CustomerRepositoryInterface;
use RuntimeException;
use wpdb;
use function current_time;
use function wp_json_encode;

class CustomerRepository implements CustomerRepositoryInterface
{
    private wpdb $db;

    private string $table;

    public function __construct(?wpdb $wpdbInstance = null)
    {
        global $wpdb;
        $this->db    = $wpdbInstance ?? $wpdb;
        $this->table = $this->db->prefix . 'bookando_users';
    }

    /**
     * Finds a customer by ID within a tenant (implements interface).
     *
     * @param int $id Customer ID
     * @param int $tenantId Tenant ID for isolation
     * @return array<string, mixed>|null Customer data or null if not found
     */
    public function findById(int $id, int $tenantId): ?array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE id = %d";
        $args = [$id];

        // Strikte Tenant-Filterung: Zeige nur Daten des aktuellen Tenants + Legacy-Daten (NULL)
        $sql   .= ' AND (tenant_id = %d OR tenant_id IS NULL)';
        $args[] = $tenantId;

        $row = $this->db->get_row($this->db->prepare($sql, ...$args), ARRAY_A);
        $this->assertNoError();

        return $row ?: null;
    }

    /**
     * Alias for backward compatibility
     *
     * @param int $id Customer ID
     * @param int $tenantId Tenant ID
     * @return array<string, mixed>|null
     */
    public function find(int $id, int $tenantId): ?array
    {
        return $this->findById($id, $tenantId);
    }

    public function getStatus(int $id): ?string
    {
        $sql = "SELECT status FROM {$this->table} WHERE id = %d";
        $status = $this->db->get_var($this->db->prepare($sql, $id));
        $this->assertNoError();

        return is_string($status) ? $status : null;
    }

    /**
     * Lists customers with optional filtering and pagination (implements interface).
     *
     * @param array<string, mixed> $filters Filtering options (search, limit, offset, etc.)
     * @param int $tenantId Tenant ID for isolation
     * @return array{items: array<int, array<string, mixed>>, total: int} Customer list with total count
     */
    public function list(array $filters, int $tenantId): array
    {
        $roleCondition = "(JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))";
        $where = "WHERE {$roleCondition}";
        $args  = [];

        // Strikte Tenant-Filterung: Zeige nur Daten des aktuellen Tenants + Legacy-Daten (NULL)
        $where .= ' AND (tenant_id = %d OR tenant_id IS NULL)';
        $args[] = $tenantId;

        $includeDeleted = $filters['include_deleted'] ?? 'no';
        switch ($includeDeleted) {
            case 'all':
                // no additional condition
                break;
            case 'soft':
                $where .= " AND (status <> 'deleted' OR deleted_at IS NULL)";
                break;
            case 'no':
            default:
                $where .= " AND status <> 'deleted'";
                break;
        }

        $search = $filters['search'] ?? '';
        if ($search !== '') {
            $like = '%' . $this->db->esc_like($search) . '%';
            $where .= " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)";
            array_push($args, $like, $like, $like);
        }

        $limit  = (int) ($filters['limit'] ?? 50);
        $offset = (int) ($filters['offset'] ?? 0);
        $order  = $filters['order'] ?? 'last_name';
        $dir    = $filters['dir'] ?? 'ASC';

        $sqlTotal = "SELECT COUNT(*) FROM {$this->table} {$where}";
        $total = !empty($args)
            ? (int) $this->db->get_var($this->db->prepare($sqlTotal, ...$args))
            : (int) $this->db->get_var($sqlTotal);
        $this->assertNoError();

        $sqlRows = "SELECT * FROM {$this->table} {$where} ORDER BY {$order} {$dir}, id ASC LIMIT %d OFFSET %d";
        $rowArgs = array_merge($args, [$limit, $offset]);
        $rows = $this->db->get_results($this->db->prepare($sqlRows, ...$rowArgs), ARRAY_A) ?: [];
        $this->assertNoError();

        // Return interface-compliant format
        return [
            'items' => $rows,
            'total' => $total,
        ];
    }

    /**
     * Creates a new customer (implements interface).
     *
     * @param array<string, mixed> $data Customer data
     * @param int $tenantId Tenant ID
     * @return int The new customer ID
     */
    public function create(array $data, int $tenantId): int
    {
        // Ensure tenant_id is set
        $data['tenant_id'] = $tenantId;

        $this->db->insert($this->table, $data);
        $this->assertNoError();

        return (int) $this->db->insert_id;
    }

    /**
     * Alias for backward compatibility
     *
     * @param array<string, mixed> $data
     * @return int
     */
    public function insert(array $data): int
    {
        $tenantId = $data['tenant_id'] ?? 1; // Fallback to default tenant
        return $this->create($data, $tenantId);
    }

    /**
     * Updates an existing customer (implements interface).
     *
     * @param int $id Customer ID
     * @param array<string, mixed> $data Updated customer data
     * @param int $tenantId Tenant ID for isolation
     * @return bool True on success, false on failure
     */
    public function update(int $id, array $data, int $tenantId): bool
    {
        // Strikte Tenant-Filterung: Update nur erlaubt wenn tenant_id übereinstimmt
        $where = ['id' => $id, 'tenant_id' => $tenantId];

        $result = $this->db->update($this->table, $data, $where);
        $this->assertNoError();

        return $result !== false && $this->db->rows_affected > 0;
    }

    /**
     * Deletes a customer (implements interface).
     *
     * @param int $id Customer ID
     * @param bool $hard If true, permanently delete; if false, soft delete
     * @param int $tenantId Tenant ID for isolation
     * @return bool True on success, false on failure
     */
    public function delete(int $id, bool $hard, int $tenantId): bool
    {
        if ($hard) {
            $this->hardDelete($id, $tenantId);
        } else {
            $this->softDelete($id, $tenantId);
        }

        return true; // Methods above throw on error
    }

    /**
     * @deprecated Use delete($id, false, $tenantId) instead
     */
    public function softDelete(int $id, int $tenantId): void
    {
        $data = [
            'status'     => 'deleted',
            'deleted_at' => null,
            'updated_at' => current_time('mysql'),
        ];

        // Update returns bool in interface implementation
        $this->update($id, $data, $tenantId);
    }

    /**
     * @deprecated Use delete($id, true, $tenantId) instead
     */
    public function hardDelete(int $id, int $tenantId): void
    {
        $anonEmail = 'deleted+' . $id . '@invalid.local';

        $data = [
            'status'               => 'deleted',
            'deleted_at'           => current_time('mysql'),
            'updated_at'           => current_time('mysql'),
            'first_name'           => null,
            'last_name'            => null,
            'email'                => $anonEmail,
            'phone'                => null,
            'address'              => null,
            'address_2'            => null,
            'zip'                  => null,
            'city'                 => null,
            'country'              => null,
            'birthdate'            => null,
            'gender'               => null,
            'language'             => null,
            'note'                 => null,
            'description'          => null,
            'avatar_url'           => null,
            'timezone'             => null,
            'external_id'          => null,
            'badge_id'             => null,
            'password_hash'        => null,
            'password_reset_token' => null,
            'roles'                => wp_json_encode([]),
        ];

        $this->update($id, $data, $tenantId);
    }

    /**
     * @param list<int> $ids
     */
    public function bulkUpdateStatus(array $ids, string $status, int $tenantId): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $args = [$status, current_time('mysql')];
        $args = array_merge($args, $ids);

        // Strikte Tenant-Filterung
        $tenantWhere = ' AND (tenant_id = %d OR tenant_id IS NULL)';
        $args[] = $tenantId;

        $sql = "UPDATE {$this->table}
                SET status = %s, deleted_at = NULL, updated_at = %s
                WHERE id IN ({$placeholders}){$tenantWhere}";

        $this->db->query($this->db->prepare($sql, ...$args));
        $this->assertNoError();

        return (int) $this->db->rows_affected;
    }

    /**
     * @param list<int> $ids
     */
    public function bulkSoftDelete(array $ids, int $tenantId): void
    {
        foreach ($ids as $id) {
            $this->softDelete($id, $tenantId);
        }
    }

    /**
     * @param list<int> $ids
     */
    public function bulkHardDelete(array $ids, int $tenantId): void
    {
        foreach ($ids as $id) {
            $this->hardDelete($id, $tenantId);
        }
    }

    private function assertNoError(): void
    {
        if ($this->db->last_error) {
            throw new RuntimeException($this->db->last_error);
        }
    }
}
