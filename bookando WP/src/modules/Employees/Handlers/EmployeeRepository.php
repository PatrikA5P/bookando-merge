<?php

declare(strict_types=1);

namespace Bookando\Modules\Employees\Handlers;

use function __;

/**
 * Repository für Employee-Datenbankoperationen.
 *
 * Zentrale Klasse für alle Datenbank-Zugriffe auf employee-bezogene Tabellen.
 * Implementiert Tenant-Isolation und bietet Hilfsmethoden für häufige Abfragen.
 */
class EmployeeRepository
{
    /**
     * Gibt alle relevanten Tabellennamen für das Employee-Modul zurück.
     *
     * @return array<string, string> Mapping von Alias zu Tabellenname
     */
    public static function employeeTables(): array
    {
        global $wpdb;

        $prefix = $wpdb->prefix . 'bookando_';

        return [
            'usersTab'   => $prefix . 'users',
            'wSetTab'    => $prefix . 'employees_workday_sets',
            'wIntTab'    => $prefix . 'employees_workday_intervals',
            'wSetLoc'    => $prefix . 'employees_workday_set_locations',
            'wSetSvc'    => $prefix . 'employees_workday_set_services',
            'holTab'     => $prefix . 'employees_days_off',
            'sdSetTab'   => $prefix . 'employees_specialday_sets',
            'sdIntTab'   => $prefix . 'employees_specialday_intervals',
            'sdSetLoc'   => $prefix . 'employees_specialday_set_locations',
            'sdSetSvc'   => $prefix . 'employees_specialday_set_services',
            'calConnTab' => $prefix . 'calendar_connections',
            'calsTab'    => $prefix . 'calendars',
            'eventsTab'  => $prefix . 'calendar_events',
        ];
    }

    /**
     * Prüft, ob eine Spalte in einer Tabelle existiert.
     *
     * @param string $table Tabellenname
     * @param string $column Spaltenname
     * @return bool True wenn Spalte existiert
     */
    public static function hasColumn(string $table, string $column): bool
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $table,
            $column
        );

        return (int) $wpdb->get_var($sql) > 0;
    }

    /**
     * Findet einen Employee anhand der ID mit Tenant-Isolation.
     *
     * @param int $id Employee ID
     * @param int|null $tenantId Tenant ID (NULL = aktueller Tenant)
     * @return array|null Employee-Daten oder null wenn nicht gefunden
     */
    public static function findById(int $id, ?int $tenantId): ?array
    {
        global $wpdb;
        $tables = self::employeeTables();
        $usersTab = $tables['usersTab'];

        $sql = "SELECT * FROM {$usersTab} WHERE id = %d";
        if ($tenantId !== null) {
            $sql .= $wpdb->prepare(" AND tenant_id = %d", $tenantId);
        }

        $row = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);

        return $row ?: null;
    }

    /**
     * Findet einen Employee anhand der E-Mail-Adresse mit Tenant-Isolation.
     *
     * @param string $email E-Mail-Adresse
     * @param int|null $tenantId Tenant ID (NULL = aktueller Tenant)
     * @return array|null Employee-Daten oder null wenn nicht gefunden
     */
    public static function findByEmail(string $email, ?int $tenantId): ?array
    {
        global $wpdb;
        $tables = self::employeeTables();
        $usersTab = $tables['usersTab'];

        $sql = $wpdb->prepare("SELECT * FROM {$usersTab} WHERE email = %s", $email);
        if ($tenantId !== null) {
            $sql .= $wpdb->prepare(" AND tenant_id = %d", $tenantId);
        }

        $row = $wpdb->get_row($sql, ARRAY_A);

        return $row ?: null;
    }

    /**
     * Zählt alle Employees für einen Tenant.
     *
     * @param int|null $tenantId Tenant ID (NULL = aktueller Tenant)
     * @param bool $includeDeleted Auch gelöschte Employees zählen?
     * @return int Anzahl der Employees
     */
    public static function countAll(?int $tenantId, bool $includeDeleted = false): int
    {
        global $wpdb;
        $tables = self::employeeTables();
        $usersTab = $tables['usersTab'];

        $sql = "SELECT COUNT(*) FROM {$usersTab} WHERE 1=1";

        if ($tenantId !== null) {
            $sql .= $wpdb->prepare(" AND tenant_id = %d", $tenantId);
        }

        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Debug-Logging-Hilfsfunktion.
     *
     * @param string $msg Debug-Nachricht
     */
    public static function dbg(string $msg): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[bookando][employees] ' . $msg);
        }
    }
}
