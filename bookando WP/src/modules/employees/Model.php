<?php
/**
 * Datenmodell für Employees (basiert auf wp_bookando_users)
 *
 * Rolle wird per role='employee' gefiltert.
 */
namespace Bookando\Modules\employees;

use Bookando\Core\Model\BaseModel;

class Model extends BaseModel
{
    /** Voll qualifizierter Tabellenname, z. B. "wp_bookando_users" */
    protected string $tableName;

    public function __construct()
    {
        parent::__construct();
        // ergibt z. B. "wp_bookando_users"
        $this->tableName = $this->table('users');
    }

    /**
     * Liste aller Mitarbeitenden (role='employee'), optional tenant-gefiltet
     */
    public function all(int $tenantId = null): array
    {
        if ($tenantId !== null) {
            $sql = "SELECT * FROM {$this->tableName}
                    WHERE role = %s
                      AND (tenant_id IS NULL OR tenant_id = %d)
                      AND deleted_at IS NULL";
            return $this->db->get_results($this->db->prepare($sql, 'employee', $tenantId), ARRAY_A) ?: [];
        }

        $sql = "SELECT * FROM {$this->tableName}
                WHERE role = %s
                  AND deleted_at IS NULL";
        return $this->db->get_results($this->db->prepare($sql, 'employee'), ARRAY_A) ?: [];
    }

    /**
     * Einzelnes Element (role='employee')
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->tableName}
                WHERE id = %d
                  AND role = %s
                  AND deleted_at IS NULL";
        $row = $this->db->get_row($this->db->prepare($sql, $id, 'employee'), ARRAY_A);
        return $row ?: null;
    }

    /**
     * Erstellen (legt Datensatz in wp_bookando_users an, Rolle wird erzwungen)
     */
    public function create(array $data): int
    {
        $data = $this->sanitize($data);
        $data['role']       = 'employee';
        $data['created_at'] = $data['created_at'] ?? $this->now();
        $data['updated_at'] = $data['updated_at'] ?? $data['created_at'];

        $ok = $this->db->insert($this->tableName, $data);
        return $ok ? (int) $this->db->insert_id : 0;
    }

    /**
     * Aktualisieren
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->sanitize($data);
        unset($data['id'], $data['role']); // Rolle bleibt 'employee'
        $data['updated_at'] = $this->now();

        $ok = $this->db->update($this->tableName, $data, ['id' => $id]);
        return (bool) $ok;
    }

    /**
     * Löschen (soft/hard)
     */
    public function delete(int $id, bool $hard = false): bool
    {
        if ($hard) {
            $ok = $this->db->delete($this->tableName, ['id' => $id]);
            return (bool) $ok;
        }
        $ok = $this->db->update($this->tableName, ['deleted_at' => $this->now()], ['id' => $id]);
        return (bool) $ok;
    }

    /**
     * Erlaubte Spalten whitelisten
     */
    protected function sanitize(array $data): array
    {
        $allowed = [
            'first_name','last_name','email','phone',
            'address','address_2','zip','city','country',
            'gender','birthdate','language','timezone',
            'badge','employee_area_password',
            'description','note','avatar_url','status',
            'tenant_id','created_at','updated_at','deleted_at'
        ];
        return array_intersect_key($data, array_flip($allowed));
    }
}
