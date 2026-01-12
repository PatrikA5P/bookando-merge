<?php

declare(strict_types=1);

namespace Bookando\Core\Model;

use wpdb;
use Bookando\Core\Model\Traits\MultiTenantTrait;
use Bookando\Core\Tenant\TenantManager;

/**
 * Abstraktes Basismodell mit erzwungener Mandanten-Isolation.
 *
 * Konventionen:
 * - Jede Tabelle hat eine Spalte `tenant_id` (BIGINT UNSIGNED).
 * - Selektions-Queries MÜSSEN über fetchAll/fetchOne/paginate laufen
 *   (MultiTenantTrait -> applyTenant() erzwingt WHERE tenant_id = ...).
 * - Schreiboperationen setzen/prüfen tenant_id automatisch.
 */
abstract class BaseModel
{
    use MultiTenantTrait;

    /** @var wpdb */
    protected wpdb $db;

    /**
     * Vollqualifizierter Tabellenname (z. B. "wp_bookando_customers").
     * Subklassen setzen diesen Wert typischerweise im Konstruktor:
     * $this->tableName = $this->table('customers');
     */
    protected string $tableName;

    /**
     * Optional: Whitelist für ORDER BY (Spaltennamen).
     * Subklassen können das überschreiben (z. B. ['id','name','created_at']).
     * Wird in buildOrderBy() verwendet.
     *
     * @return string[]
     */
    protected function allowedOrderBy(): array
    {
        return [];
    }

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    /** Aktuelle WP-Serverzeit (mysql-format) */
    protected function now(): string
    {
        return current_time('mysql');
    }

    /** Prefix + bookando_* */
    protected function table(string $name): string
    {
        // Hart absichern, damit keine Sonderzeichen in den Tabellennamen wandern
        $safe = sanitize_key($name);
        return $this->db->prefix . 'bookando_' . $safe;
    }

    /** LIKE '%value%' sicher (esc_like) */
    protected function escLike(string $value): string
    {
        return '%' . $this->db->esc_like($value) . '%';
    }

    /** Direktzugriff für Subklassen, wenn noetig */
    protected function db(): wpdb
    {
        return $this->db;
    }

    // ---------------------------------------------------------------------
    // Lese-Operationen (erzwingen Tenant-Scope)
    // ---------------------------------------------------------------------

    /**
     * Fuehrt einen SELECT (ohne LIMIT) mit Mandanten-Filter aus.
     * $baseSql darf Platzhalter enthalten ( %s, %d, ... ), aber KEIN tenant_id-Filter.
     *
     * @param string $baseSql  z. B. "SELECT * FROM {$this->tableName} WHERE status=%s"
     * @param array  $args     korrespondierende Werte für prepare()
     * @return array<int,array<string,mixed>>
     */
    protected function fetchAll(string $baseSql, array $args = []): array
    {
        [$scopedSql, $scopedArgs] = $this->applyTenant($baseSql, $args);
        $prepared = $this->db->prepare($scopedSql, $scopedArgs);
        return $this->db->get_results($prepared, ARRAY_A) ?: [];
    }

    /**
     * Wie fetchAll, aber nur eine Zeile.
     *
     * @param string $baseSql
     * @param array  $args
     * @return array<string,mixed>|null
     */
    protected function fetchOne(string $baseSql, array $args = []): ?array
    {
        [$scopedSql, $scopedArgs] = $this->applyTenant($baseSql, $args);
        $scopedSql .= ' LIMIT 1';
        $prepared = $this->db->prepare($scopedSql, $scopedArgs);
        $row = $this->db->get_row($prepared, ARRAY_A);
        return $row ?: null;
    }

    /**
     * Paginierter Abruf inkl. total.
     * Achtung: $orderBy wird strikt gegen allowedOrderBy() geprueft.
     *
     * @param string      $baseSql  SELECT-Query ohne tenant_id-Filter
     * @param array       $args     Platzhalter-Werte
     * @param int         $page     1-basierend
     * @param int         $perPage  1..500
     * @param string|null $orderBy  Spaltenname (Whitelist)
     * @param string      $dir      'ASC'|'DESC'
     * @param string[]    $allow    Optionale Whitelist, ueberschreibt allowedOrderBy()
     * @return array{items: array<int,array<string,mixed>>, total: int, page: int, perPage: int}
     */
    protected function paginate(
        string $baseSql,
        array $args = [],
        int $page = 1,
        int $perPage = 25,
        ?string $orderBy = null,
        string $dir = 'ASC',
        array $allow = []
    ): array {
        $page    = max(1, $page);
        $perPage = max(1, min(500, $perPage));
        $offset  = ($page - 1) * $perPage;

        [$scopedSql, $scopedArgs] = $this->applyTenant($baseSql, $args);

        // ORDER BY (nur Whitelist)
        $orderPart = $this->buildOrderBy($orderBy, $dir, $allow ?: $this->allowedOrderBy());
        $pagedSql  = $scopedSql . $orderPart . ' LIMIT %d OFFSET %d';
        $pagedArgs = array_merge($scopedArgs, [$perPage, $offset]);

        $preparedPaged = $this->db->prepare($pagedSql, $pagedArgs);
        $items = $this->db->get_results($preparedPaged, ARRAY_A) ?: [];

        // Total via COUNT(*) über denselben gescopten Select
        $countSql = 'SELECT COUNT(*) FROM (' . $scopedSql . ') cnt';
        $preparedCount = $this->db->prepare($countSql, $scopedArgs);
        $total = (int) $this->db->get_var($preparedCount);

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * ORDER BY Builder mit Whitelist und sicherem Richtungs-Check.
     */
    protected function buildOrderBy(?string $column, string $dir, array $whitelist): string
    {
        if (!$column) {
            return '';
        }
        if (!in_array($column, $whitelist, true)) {
            // Unbekannte Spalte: kein ORDER BY
            return '';
        }
        $direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        // Spaltennamen als Backticks, KEINE Platzhalter (wpdb->prepare unterstuetzt keine Identifier)
        $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $column) ?: $column;
        return " ORDER BY `{$safeCol}` {$direction}";
    }

    // ---------------------------------------------------------------------
    // Schreib-Operationen (setzen/erzwingen tenant_id)
    // ---------------------------------------------------------------------

    /**
     * Insert mit auto-tenant: wenn tenant_id fehlt, wird currentTenantId() gesetzt.
     *
     * @param array<string,mixed> $data
     * @return int Insert-ID
     */
    protected function insert(array $data): int
    {
        $tenantId = TenantManager::currentTenantId();
        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context missing on insert');
        }
        if (!array_key_exists('tenant_id', $data)) {
            $data['tenant_id'] = $tenantId;
        }

        $formats = $this->formats($data);
        $ok = $this->db->insert($this->tableName, $data, $formats);
        if (!$ok) {
            throw new \RuntimeException('Insert failed on ' . $this->tableName);
        }
        return (int)$this->db->insert_id;
    }

    /**
     * Update gescopet auf (id, tenant_id).
     *
     * @param int                   $id
     * @param array<string,mixed>   $data
     * @return int betroffene Zeilen
     */
    protected function update(int $id, array $data): int
    {
        $tenantId = TenantManager::currentTenantId();
        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context missing on update');
        }
        // tenant_id darf nicht mutiert werden
        unset($data['tenant_id']);

        if (empty($data)) {
            return 0;
        }

        $formats = $this->formats($data);
        $where   = ['id' => $id, 'tenant_id' => $tenantId];
        $whereF  = ['%d', '%d'];

        $ok = $this->db->update($this->tableName, $data, $where, $formats, $whereF);
        if ($ok === false) {
            throw new \RuntimeException('Update failed on ' . $this->tableName);
        }
        return (int)$ok; // 0 = nichts geaendert, >0 = geaendert
    }

    /**
     * Delete gescopet auf (id, tenant_id).
     *
     * @param int $id
     * @return int betroffene Zeilen
     */
    protected function delete(int $id): int
    {
        $tenantId = TenantManager::currentTenantId();
        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context missing on delete');
        }
        $ok = $this->db->delete($this->tableName, ['id' => $id, 'tenant_id' => $tenantId], ['%d', '%d']);
        if ($ok === false) {
            throw new \RuntimeException('Delete failed on ' . $this->tableName);
        }
        return (int)$ok;
    }

    // ---------------------------------------------------------------------
    // Hilfen
    // ---------------------------------------------------------------------

    /**
     * Ermittelt wpdb-Format-Strings passend zu den Werten.
     *
     * @param array<string,mixed> $data
     * @return array<int,string>  z. B. ['%s','%d','%f']
     */
    protected function formats(array $data): array
    {
        $formats = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $formats[] = '%d';
            } elseif (is_float($value)) {
                $formats[] = '%f';
            } elseif (is_bool($value)) {
                $formats[] = '%d';
            } elseif ($value === null) {
                // wpdb kennt kein NULL-Format; NULL muss im Query-Kontext gehandhabt werden.
                $formats[] = '%s';
            } else {
                $formats[] = '%s';
            }
        }
        return $formats;
    }

    // ---------------------------------------------------------------------
    // 🔒 Utilities für Cross-Tenant-Szenarien (gezielt, nicht default!)
    // ---------------------------------------------------------------------
    /**
     * Führt eine Callback-Operation temporär **als anderer Tenant** aus.
     * Nutzt den globalen Request-Cache im TenantManager.
     */
    protected function runAsTenant(int $tenantId, callable $fn)
    {
        $prev = TenantManager::currentTenantId();
        TenantManager::setCurrentTenantId($tenantId);
        try {
            return $fn();
        } finally {
            TenantManager::setCurrentTenantId($prev);
        }
    }

    /**
     * ⚠️ Nur für ShareService/Diagnose: SELECT *ohne* tenant-Scope.
     * Verwende **nur** nach vorgelagerter ACL-Prüfung!
     */
    protected function fetchOneUnsafeNoScope(string $sql, array $args = []): ?array
    {
        $prepared = $this->db->prepare($sql, $args);
        $row = $this->db->get_row($prepared, ARRAY_A);
        return $row ?: null;
    }
}
