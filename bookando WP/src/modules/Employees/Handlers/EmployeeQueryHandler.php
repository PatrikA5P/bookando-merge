<?php

declare(strict_types=1);

namespace Bookando\Modules\Employees\Handlers;

use WP_REST_Request;
use WP_Error;
use function rest_ensure_response;
use function __;
use function sanitize_key;
use function sanitize_text_field;
use function current_time;

/**
 * Handler für Employee-Abfragen (Query Operations).
 *
 * Verantwortlich für:
 * - Employee-Detail-Abfragen (GET /employees/{id})
 * - Employee-Listen-Abfragen (GET /employees)
 * - Vollständige Datensatz-Transformation mit Nested Collections
 * - Pagination und Filtering
 */
class EmployeeQueryHandler
{
    /**
     * Lädt einen einzelnen Employee mit allen Nested Collections.
     *
     * Nested Collections:
     * - workday_sets (+ intervals, services, locations)
     * - days_off
     * - special_day_sets (+ intervals, services, locations)
     * - calendars
     *
     * @param int $employeeId Employee ID
     * @param int|null $tenantId Tenant ID für Isolation
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response mit Employee-Daten oder Fehler
     */
    public static function handleEmployeeDetail(
        int $employeeId,
        ?int $tenantId,
        WP_REST_Request $request
    ) {
        // Authorization Check
        if (!EmployeeAuthorizationGuard::canReadRecord($employeeId, $request)) {
            return EmployeeAuthorizationGuard::forbiddenError();
        }

        $tables = EmployeeRepository::employeeTables();
        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        EmployeeRepository::dbg('[employees][detail] id=' . $employeeId);

        // Hauptdatensatz laden mit Tenant-Isolation
        $sql = "SELECT * FROM {$usersTab} WHERE id = %d";
        if ($tenantId) {
            $sql .= $wpdb->prepare(" AND tenant_id = %d", $tenantId);
        }

        $row = $wpdb->get_row($wpdb->prepare($sql, $employeeId), ARRAY_A);
        if (!$row) {
            return new WP_Error('not_found', __('Nicht gefunden.', 'bookando'), ['status' => 404]);
        }

        // Hard-Delete Check (anonymisierte Datensätze)
        if (EmployeeDataTransformer::isHardDeleted($row)) {
            return new WP_Error('gone', __('Nicht mehr verfügbar.', 'bookando'), ['status' => 410]);
        }

        // Workday Sets laden (Arbeitszeiten pro Wochentag)
        $sets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, week_day_id, label, sort,
                        DATE_FORMAT(created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                        DATE_FORMAT(updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                FROM {$wSetTab}
                WHERE user_id=%d
                ORDER BY week_day_id ASC, sort ASC, id ASC",
                $employeeId
            ),
            ARRAY_A
        ) ?: [];

        if ($sets) {
            $setIds = array_map('intval', array_column($sets, 'id'));
            $in     = implode(',', array_fill(0, count($setIds), '%d'));

            // Intervals laden (Zeitblöcke innerhalb eines Sets)
            $ints = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, set_id, is_break, start_time, end_time
                    FROM {$wIntTab}
                    WHERE set_id IN ($in)
                    ORDER BY start_time ASC",
                    ...$setIds
                ),
                ARRAY_A
            ) ?: [];

            // Services & Locations Mappings laden
            $svcRows = $wpdb->get_results(
                $wpdb->prepare("SELECT set_id, service_id FROM {$wSetSvc} WHERE set_id IN ($in)", ...$setIds),
                ARRAY_A
            ) ?: [];
            $locRows = $wpdb->get_results(
                $wpdb->prepare("SELECT set_id, location_id FROM {$wSetLoc} WHERE set_id IN ($in)", ...$setIds),
                ARRAY_A
            ) ?: [];

            // Maps erstellen für effizienten Zugriff
            $intMap = [];
            foreach ($ints as $r) {
                $intMap[(int) $r['set_id']][] = [
                    'id'         => (int) $r['id'],
                    'set_id'     => (int) $r['set_id'],
                    'start_time' => (string) $r['start_time'],
                    'end_time'   => (string) $r['end_time'],
                    'is_break'   => (int) $r['is_break'],
                ];
            }

            $svcMap = [];
            foreach ($svcRows as $r) {
                $svcMap[(int) $r['set_id']][] = (int) $r['service_id'];
            }

            $locMap = [];
            foreach ($locRows as $r) {
                $locMap[(int) $r['set_id']][] = (int) $r['location_id'];
            }

            // Nested Collections an Sets anhängen
            foreach ($sets as &$s) {
                $sid = (int) $s['id'];
                $s['intervals'] = $intMap[$sid] ?? [];
                $s['services']  = $svcMap[$sid] ?? [];
                $s['locations'] = $locMap[$sid] ?? [];
                // Backcompat-Felder für alte Clients
                $s['service_id']  = $s['services'][0] ?? null;
                $s['location_id'] = $s['locations'][0] ?? null;
            }
            unset($s);
        }

        $row['workday_sets'] = $sets;

        // Days Off laden (Urlaubstage, Feiertage, etc.)
        $row['days_off'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name, note,
                        DATE_FORMAT(start_date,'%%Y-%%m-%%d') AS start_date,
                        DATE_FORMAT(end_date,'%%Y-%%m-%%d')   AS end_date,
                        repeat_yearly,
                        DATE_FORMAT(created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                        DATE_FORMAT(updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                FROM {$holTab}
                WHERE user_id=%d
                ORDER BY start_date ASC, id ASC",
                $employeeId
            ),
            ARRAY_A
        ) ?: [];

        // Special Day Sets laden (Abweichende Arbeitszeiten für spezielle Tage)
        $sdSets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, user_id,
                        DATE_FORMAT(start_date,'%%Y-%%m-%%d') AS start_date,
                        DATE_FORMAT(COALESCE(end_date,start_date),'%%Y-%%m-%%d') AS end_date,
                        label, sort,
                        DATE_FORMAT(created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                        DATE_FORMAT(updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                FROM {$sdSetTab}
                WHERE user_id=%d
                ORDER BY start_date ASC, sort ASC, id ASC",
                $employeeId
            ),
            ARRAY_A
        ) ?: [];

        if ($sdSets) {
            $ids = array_map('intval', array_column($sdSets, 'id'));
            $in  = implode(',', array_fill(0, count($ids), '%d'));

            // Special Day Intervals laden
            $ints = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, set_id, is_break,
                            TIME_FORMAT(start_time,'%%H:%%i:%%s') AS start_time,
                            TIME_FORMAT(end_time,'%%H:%%i:%%s')   AS end_time
                    FROM {$sdIntTab}
                    WHERE set_id IN ($in)
                    ORDER BY start_time ASC",
                    ...$ids
                ),
                ARRAY_A
            ) ?: [];

            // Special Day Services & Locations Mappings laden
            $svcRows = $wpdb->get_results(
                $wpdb->prepare("SELECT set_id, service_id FROM {$sdSetSvc} WHERE set_id IN ($in)", ...$ids),
                ARRAY_A
            ) ?: [];
            $locRows = $wpdb->get_results(
                $wpdb->prepare("SELECT set_id, location_id FROM {$sdSetLoc} WHERE set_id IN ($in)", ...$ids),
                ARRAY_A
            ) ?: [];

            // Maps erstellen
            $intMap = [];
            foreach ($ints as $r) {
                $intMap[(int) $r['set_id']][] = [
                    'id'         => (int) $r['id'],
                    'set_id'     => (int) $r['set_id'],
                    'start_time' => (string) $r['start_time'],
                    'end_time'   => (string) $r['end_time'],
                    'is_break'   => (int) $r['is_break'],
                ];
            }

            $svcMap = [];
            foreach ($svcRows as $r) {
                $svcMap[(int) $r['set_id']][] = (int) $r['service_id'];
            }

            $locMap = [];
            foreach ($locRows as $r) {
                $locMap[(int) $r['set_id']][] = (int) $r['location_id'];
            }

            // Nested Collections an Special Day Sets anhängen
            foreach ($sdSets as &$s) {
                $sid = (int) $s['id'];
                $s['intervals'] = $intMap[$sid] ?? [];
                $s['services']  = $svcMap[$sid] ?? [];
                $s['locations'] = $locMap[$sid] ?? [];
            }
            unset($s);
        }

        $row['special_day_sets'] = $sdSets;

        // Calendars laden (Kalender-Integrationen)
        $row['calendars'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.id,
                        conn.provider,
                        c.calendar_id,
                        c.name,
                        c.access,
                        c.is_busy_source,
                        c.is_default_write,
                        c.time_zone,
                        c.color,
                        DATE_FORMAT(c.created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                        DATE_FORMAT(c.updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                FROM {$calsTab} c
                INNER JOIN {$calConnTab} conn ON conn.id = c.connection_id
                WHERE conn.user_id = %d
                ORDER BY c.id ASC",
                $employeeId
            ),
            ARRAY_A
        ) ?: [];

        return rest_ensure_response($row);
    }

    /**
     * Lädt Employee-Liste mit Pagination und Filtering.
     *
     * Query-Parameter:
     * - search: Suchbegriff (first_name, last_name, email)
     * - limit: Max. Anzahl Ergebnisse (1-200, default: 50)
     * - offset: Skip erste N Einträge (default: 0)
     * - order: Sortierfeld (default: last_name)
     * - dir: Sortierrichtung (ASC|DESC, default: ASC)
     * - include_deleted: Gelöschte einbeziehen? (no|soft|all, default: no)
     *
     * @param int|null $tenantId Tenant ID für Isolation
     * @param WP_REST_Request $request REST Request mit Query-Parametern
     * @return \WP_REST_Response Response mit paginierten Employee-Daten
     */
    public static function handleEmployeeList(
        ?int $tenantId,
        WP_REST_Request $request
    ) {
        $tables = EmployeeRepository::employeeTables();
        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        // Nur Employees mit bookando_employee oder employee Rolle
        $roleCond = "(JSON_CONTAINS(roles, '\"bookando_employee\"') OR JSON_CONTAINS(roles, '\"employee\"'))";
        $where    = "WHERE {$roleCond}";

        // Tenant-Isolation
        if ($tenantId) {
            $where .= $wpdb->prepare(" AND tenant_id = %d", $tenantId);
        }

        // Include Deleted Filter
        $includeDeleted = sanitize_key($request->get_param('include_deleted') ?? 'no');
        $cond = "1=1";

        switch ($includeDeleted) {
            case 'no':
                // Keine gelöschten Datensätze
                $cond .= " AND status <> 'deleted'";
                break;
            case 'all':
                // Alle Datensätze (inkl. hard deleted)
                $cond .= " AND 1=1";
                break;
            case 'soft':
            default:
                // Nur soft deleted (deleted_at IS NULL)
                $cond .= " AND (status <> 'deleted' OR deleted_at IS NULL)";
        }

        // Query-Parameter auslesen und validieren
        $search = sanitize_text_field($request->get_param('search') ?? '');
        $limit  = max(1, min(200, (int) ($request->get_param('limit') ?? 50)));
        $offset = max(0, (int) ($request->get_param('offset') ?? 0));
        $order  = sanitize_key($request->get_param('order') ?? 'last_name');
        $dir    = strtoupper($request->get_param('dir') ?? 'ASC');
        $dir    = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';

        // Erlaubte Sortierfelder (SQL-Injection Protection)
        $allowedOrder = ['first_name', 'last_name', 'email', 'created_at', 'updated_at', 'id'];
        if (!in_array($order, $allowedOrder, true)) {
            $order = 'last_name';
        }

        // Suchbedingung vorbereiten
        $likeSearch = '';
        $argsLike   = [];

        if ($search !== '') {
            $like       = '%' . $wpdb->esc_like($search) . '%';
            $likeSearch = " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)";
            $argsLike   = [$like, $like, $like];
        }

        // Total Count (für Pagination)
        $sqlTotal = "SELECT COUNT(*) FROM {$usersTab} {$where} AND {$cond}" . ($likeSearch ? $likeSearch : '');

        if (!empty($argsLike)) {
            $total = (int) $wpdb->get_var($wpdb->prepare($sqlTotal, ...$argsLike));
        } else {
            $total = (int) $wpdb->get_var($sqlTotal);
        }

        // Rows Query mit Pagination
        $sqlRows = "SELECT * FROM {$usersTab} {$where} AND {$cond}" . ($likeSearch ? $likeSearch : '');
        $sqlRows .= " ORDER BY {$order} {$dir}, id ASC LIMIT %d OFFSET %d";

        $rows = $wpdb->get_results(
            $wpdb->prepare($sqlRows, ...array_merge($argsLike, [$limit, $offset])),
            ARRAY_A
        );

        $response = rest_ensure_response([
            'data'   => $rows ?: [],
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ]);

        $response->set_status(200);

        return $response;
    }
}
