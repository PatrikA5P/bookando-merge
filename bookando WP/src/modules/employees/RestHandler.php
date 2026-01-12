<?php

declare(strict_types=1);

namespace Bookando\Modules\employees;

use WP_REST_Request;
use WP_Error;
use Bookando\Core\Api\Response;
use Bookando\Core\Dispatcher\RestModuleGuard;
use WP_REST_Server;
use Bookando\Core\Auth\Gate;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Settings\FormRules;
use Bookando\Core\Util\Sanitizer;
use function __;
use function sanitize_email;
use function sanitize_key;
use function sanitize_text_field;
use function wp_json_encode;

/**
 * REST-Handler für das Modul "employees".
 *
 * Ziele / Highlights:
 * - Gleiches Sanitizing-/Normalisierungsmodell wie bei "customers":
 *     • Leere Strings ⇒ NULL (Datenbank sauber & eindeutig)
 *     • country ⇒ strikt ISO-2 (A–Z), sonst NULL
 *     • gender  ⇒ tolerant (male|female|other|none oder m|f|d|n) ⇒ m|f|d|n
 * - FormRules-Validierung abhängig vom Ziel-Status.
 * - Soft-/Hard-Delete. Hard-Delete anonymisiert PII und bereinigt Subtabellen.
 * - Nested Collections (workday_sets, days_off, special_days(+mappings), calendars):
 *   Full-Replace **nur wenn** Feld im Payload existiert (sonst unverändert).
 * - Days Off: ganztägig (keine Uhrzeiten), optional jährliche Wiederholung.
 * - Ausnahmen/zeitliche Abweichungen via Special Day Sets (+Intervals).
 */
class RestHandler
{
    /**
     * /wp-json/bookando/v1/employees/employees
     *   GET    → Liste
     *   POST   → Neu anlegen (+ working_hours, days_off, special_days(+mappings), calendars)
     * /wp-json/bookando/v1/employees/employees/{id}
     *   GET    → Einzelner Datensatz (+ obige Sammlungen)
     *   PUT    → Update (+ Full-Replace Sammlungen, wenn übergeben)
     *   DELETE → Soft-/Hard-Delete (?hard=1) inkl. Bereinigung der employee-* Tabellen
     */
    public static function employees($params, WP_REST_Request $request)
    {
        $method     = strtoupper($request->get_method());
        $employeeId = self::resolveEmployeeId($params, $request);
        // Strikte Tenant-Isolation: IMMER nur Daten des aktuellen Tenants
        // Entwickler können via X-BOOKANDO-TENANT Header Tenant wechseln (erfordert Capability)
        $tenantId   = TenantManager::currentTenantId();
        $tables     = self::employeeTables();

        if ($method === 'GET' && $employeeId > 0) {
            return self::handleEmployeeDetail($tables, $tenantId, $employeeId, $request);
        }

        if ($method === 'GET') {
            return self::handleEmployeeList($tables, $tenantId, $request);
        }

        if ($method === 'POST') {
            return self::handleEmployeeCreate($tables, $tenantId, $request);
        }

        if ($method === 'PUT' && $employeeId > 0) {
            return self::handleEmployeeUpdate($tables, $tenantId, $employeeId, $request);
        }

        if ($method === 'DELETE' && $employeeId > 0) {
            return self::handleEmployeeDelete($tables, $tenantId, $employeeId, $request);
        }

        return new WP_Error('method_not_allowed', __('Methode nicht unterstützt.', 'bookando'), ['status' => 405]);
    }

    private static function resolveEmployeeId(array $params, WP_REST_Request $request): int
    {
        if (isset($params['id'])) {
            return (int) $params['id'];
        }

        if (isset($params['subkey'])) {
            return (int) $params['subkey'];
        }

        $id = $request->get_param('id');

        return $id !== null ? (int) $id : 0;
    }

    /**
     * @return array<string, string>
     */
    private static function employeeTables(): array
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

    private static function handleEmployeeDetail(array $tables, ?int $tenantId, int $id, WP_REST_Request $request)
    {
        if (!self::canReadRecord($id, $request)) {
            return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
        }

        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        self::dbg('[employees][detail] id=' . $id);

        $sql = "SELECT * FROM {$usersTab} WHERE id = %d";
        if ($tenantId) {
            $sql .= $wpdb->prepare(" AND tenant_id = %d", $tenantId);
        }

        $row = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);
        if (!$row) {
            return new WP_Error('not_found', __('Nicht gefunden.', 'bookando'), ['status' => 404]);
        }

        if (self::isHardDeleted($row)) {
            return new WP_Error('gone', __('Nicht mehr verfügbar.', 'bookando'), ['status' => 410]);
        }

        $sets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, week_day_id, label, sort,
                        DATE_FORMAT(created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                        DATE_FORMAT(updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                FROM {$wSetTab}
                WHERE user_id=%d
                ORDER BY week_day_id ASC, sort ASC, id ASC",
                $id
            ),
            ARRAY_A
        ) ?: [];

        if ($sets) {
            $setIds = array_map('intval', array_column($sets, 'id'));
            $in     = implode(',', array_fill(0, count($setIds), '%d'));

            $ints = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, set_id, is_break, start_time, end_time
                    FROM {$wIntTab}
                    WHERE set_id IN ($in)
                    ORDER BY start_time ASC",
                    ...$setIds
                ), ARRAY_A
            ) ?: [];

            $svcRows = $wpdb->get_results(
                $wpdb->prepare("SELECT set_id, service_id FROM {$wSetSvc} WHERE set_id IN ($in)", ...$setIds),
                ARRAY_A
            ) ?: [];
            $locRows = $wpdb->get_results(
                $wpdb->prepare("SELECT set_id, location_id FROM {$wSetLoc} WHERE set_id IN ($in)", ...$setIds),
                ARRAY_A
            ) ?: [];

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

            foreach ($sets as &$s) {
                $sid = (int) $s['id'];
                $s['intervals'] = $intMap[$sid] ?? [];
                $s['services']  = $svcMap[$sid] ?? [];
                $s['locations'] = $locMap[$sid] ?? [];
                $s['service_id']  = $s['services'][0] ?? null;
                $s['location_id'] = $s['locations'][0] ?? null;
            }
            unset($s);
        }

        $row['workday_sets'] = $sets;

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
                $id
            ),
            ARRAY_A
        ) ?: [];

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
                $id
            ),
            ARRAY_A
        ) ?: [];

        if ($sdSets) {
            $ids = array_map('intval', array_column($sdSets, 'id'));
            $in  = implode(',', array_fill(0, count($ids), '%d'));

            $ints = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, set_id, is_break,
                            TIME_FORMAT(start_time,'%%H:%%i:%%s') AS start_time,
                            TIME_FORMAT(end_time,'%%H:%%i:%%s')   AS end_time
                    FROM {$sdIntTab}
                    WHERE set_id IN ($in)
                    ORDER BY start_time ASC",
                    ...$ids
                ), ARRAY_A
            ) ?: [];

            $svcRows = $wpdb->get_results(
                $wpdb->prepare("SELECT set_id, service_id FROM {$sdSetSvc} WHERE set_id IN ($in)", ...$ids),
                ARRAY_A
            ) ?: [];
            $locRows = $wpdb->get_results(
                $wpdb->prepare("SELECT set_id, location_id FROM {$sdSetLoc} WHERE set_id IN ($in)", ...$ids),
                ARRAY_A
            ) ?: [];

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

            foreach ($sdSets as &$s) {
                $sid = (int) $s['id'];
                $s['intervals'] = $intMap[$sid] ?? [];
                $s['services']  = $svcMap[$sid] ?? [];
                $s['locations'] = $locMap[$sid] ?? [];
            }
            unset($s);
        }

        $row['special_day_sets'] = $sdSets;

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
                $id
            ),
            ARRAY_A
        ) ?: [];

        return rest_ensure_response($row);
    }

    private static function handleEmployeeList(array $tables, ?int $tenantId, WP_REST_Request $request)
    {
        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        $roleCond = "(JSON_CONTAINS(roles, '\"bookando_employee\"') OR JSON_CONTAINS(roles, '\"employee\"'))";
        $where    = "WHERE {$roleCond}";

        if ($tenantId) {
            $where .= $wpdb->prepare(" AND tenant_id = %d", $tenantId);
        }

        $includeDeleted = sanitize_key($request->get_param('include_deleted') ?? 'no');
        $cond = "1=1";

        switch ($includeDeleted) {
            case 'no':
                $cond .= " AND status <> 'deleted'";
                break;
            case 'all':
                $cond .= " AND 1=1";
                break;
            case 'soft':
            default:
                $cond .= " AND (status <> 'deleted' OR deleted_at IS NULL)";
        }

        $search = sanitize_text_field($request->get_param('search') ?? '');
        $limit  = max(1, min(200, (int) ($request->get_param('limit') ?? 50)));
        $offset = max(0, (int) ($request->get_param('offset') ?? 0));
        $order  = sanitize_key($request->get_param('order') ?? 'last_name');
        $dir    = strtoupper($request->get_param('dir') ?? 'ASC');
        $dir    = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';

        $allowedOrder = ['first_name', 'last_name', 'email', 'created_at', 'updated_at', 'id'];
        if (!in_array($order, $allowedOrder, true)) {
            $order = 'last_name';
        }

        $likeSearch = '';
        $argsLike   = [];

        if ($search !== '') {
            $like       = '%' . $wpdb->esc_like($search) . '%';
            $likeSearch = " AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)";
            $argsLike   = [$like, $like, $like];
        }

        $sqlTotal = "SELECT COUNT(*) FROM {$usersTab} {$where} AND {$cond}" . ($likeSearch ? $likeSearch : '');

        if (!empty($argsLike)) {
            $total = (int) $wpdb->get_var($wpdb->prepare($sqlTotal, ...$argsLike));
        } else {
            $total = (int) $wpdb->get_var($sqlTotal);
        }

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

    private static function handleEmployeeCreate(array $tables, ?int $tenantId, WP_REST_Request $request)
    {
        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        $data = self::sanitizeEmployeeInput((array) $request->get_json_params(), true);

        if (!empty($data['email']) && !is_email($data['email'])) {
            return new WP_Error('invalid_email', __('Ungültige E-Mail-Adresse.', 'bookando'), ['status' => 400]);
        }

        $targetStatus = self::normalizeStatus($data['status'] ?? 'active');
        $rules        = FormRules::get('employees', 'admin');

        if ($targetStatus !== 'deleted') {
            $missing = self::validateByRules($data + ['status' => $targetStatus], $rules, $targetStatus);
            if (!empty($missing)) {
                return new WP_Error('validation_error', __('Pflichtfelder fehlen.', 'bookando'), [
                    'status' => 422,
                    'fields' => $missing,
                ]);
            }
        }

        $roles = wp_json_encode(['bookando_employee']);

        $insert = [
            'tenant_id'     => $tenantId ?: (int) ($data['tenant_id'] ?? 1),
            'email'         => $data['email'] ?? null,
            'first_name'    => $data['first_name'] ?? null,
            'last_name'     => $data['last_name'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'address'       => $data['address'] ?? null,
            'address_2'     => $data['address_2'] ?? null,
            'zip'           => $data['zip'] ?? null,
            'city'          => $data['city'] ?? null,
            'country'       => $data['country'] ?? null,
            'birthdate'     => $data['birthdate'] ?? null,
            'gender'        => $data['gender'] ?? null,
            'language'      => $data['language'] ?? 'de',
            'note'          => $data['note'] ?? null,
            'description'   => $data['description'] ?? null,
            'badge_id'      => $data['badge_id'] ?? null,
            'avatar_url'    => $data['avatar_url'] ?? null,
            'timezone'      => $data['timezone'] ?? null,
            'password_hash' => $data['password_hash'] ?? null,
            'roles'         => $roles,
            'status'        => $targetStatus,
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
            'deleted_at'    => null,
        ];

        $wpdb->insert($usersTab, $insert);

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        $userId = (int) $wpdb->insert_id;

        if (!empty($data['workday_sets']) && is_array($data['workday_sets'])) {
            self::replaceWorkdaySets($userId, $data['workday_sets'], $wSetTab, $wIntTab);
        }

        if (!empty($data['days_off']) && is_array($data['days_off'])) {
            self::replaceDaysOff($userId, $data['days_off'], $holTab);
        }

        if (!empty($data['special_day_sets']) && is_array($data['special_day_sets'])) {
            self::replaceSpecialDaySets($userId, $data['special_day_sets'], $sdSetTab, $sdIntTab);
        }

        if (!empty($data['calendars']) && is_array($data['calendars'])) {
            self::replaceCalendars($userId, $data['calendars'], $calConnTab, $calsTab);
        }

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return Response::created(['id' => $userId]);
    }

    private static function handleEmployeeUpdate(array $tables, ?int $tenantId, int $id, WP_REST_Request $request)
    {
        if (!self::canWriteRecord($id, $request)) {
            return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
        }

        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        $data = self::sanitizeEmployeeInput((array) $request->get_json_params(), false);
        if (!empty($data['email']) && !is_email($data['email'])) {
            return new WP_Error('invalid_email', __('Ungültige E-Mail-Adresse.', 'bookando'), ['status' => 400]);
        }

        $sql = "SELECT * FROM {$usersTab} WHERE id=%d";
        if ($tenantId) {
            $sql .= $wpdb->prepare(" AND tenant_id=%d", $tenantId);
        }

        $currRow = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);
        if (!$currRow) {
            return new WP_Error('not_found', __('Nicht gefunden.', 'bookando'), ['status' => 404]);
        }

        $currentStatus = $currRow['status'] ?? 'active';
        $targetStatus  = self::normalizeStatus($data['status'] ?? $currentStatus);

        $rules = FormRules::get('employees', 'admin');
        if ($targetStatus !== 'deleted') {
            $forValidation = array_merge($currRow, $data, ['status' => $targetStatus]);
            $missing       = self::validateByRules($forValidation, $rules, $targetStatus);
            if (!empty($missing)) {
                return new WP_Error('validation_error', __('Pflichtfelder fehlen.', 'bookando'), [
                    'status' => 422,
                    'fields' => $missing,
                ]);
            }
        }

        $upd = [];
        foreach ([
            'first_name', 'last_name', 'email', 'phone', 'address', 'address_2', 'zip', 'city', 'country',
            'birthdate', 'gender', 'language', 'note', 'description', 'avatar_url', 'timezone', 'status',
            'badge_id', 'password_hash',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $upd[$key] = $data[$key];
            }
        }

        $upd['updated_at'] = current_time('mysql');

        $where = ['id' => $id];
        $wf    = ['%d'];
        if ($tenantId) {
            $where['tenant_id'] = $tenantId;
            $wf[]               = '%d';
        }

        $fmt = [];
        foreach ($upd as $value) {
            $fmt[] = is_int($value) ? '%d' : '%s';
        }

        $wpdb->update($usersTab, $upd, $where, $fmt, $wf);
        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        if (array_key_exists('workday_sets', $data)) {
            $sets   = is_array($data['workday_sets']) ? $data['workday_sets'] : [];
            $hasIds = false;
            foreach ($sets as $set) {
                if (!empty($set['id'])) {
                    $hasIds = true;
                    break;
                }
            }

            if ($hasIds || !empty($data['workday_sets_delete_ids'])) {
                $deleteIds = array_values(
                    array_filter(
                        array_map('intval', (array) ($data['workday_sets_delete_ids'] ?? [])),
                        static fn($value) => $value > 0
                    )
                );
                self::mergeWorkdaySets($id, $sets, $deleteIds, $wSetTab, $wIntTab, $wSetLoc, $wSetSvc);
            } else {
                self::replaceWorkdaySets($id, $sets, $wSetTab, $wIntTab);
            }
        }

        if (array_key_exists('days_off', $data)) {
            $deleteIds = array_values(
                array_filter(
                    array_map('intval', (array) ($data['days_off_delete_ids'] ?? [])),
                    static fn($value) => $value > 0
                )
            );
            self::mergeDaysOff($id, is_array($data['days_off']) ? $data['days_off'] : [], $holTab, $deleteIds);
        }

        if (array_key_exists('special_day_sets', $data)) {
            $sets   = is_array($data['special_day_sets']) ? $data['special_day_sets'] : [];
            $hasIds = false;
            foreach ($sets as $set) {
                if (!empty($set['id'])) {
                    $hasIds = true;
                    break;
                }
            }

            if ($hasIds || !empty($data['special_day_sets_delete_ids'])) {
                $deleteIds = array_values(
                    array_filter(
                        array_map('intval', (array) ($data['special_day_sets_delete_ids'] ?? [])),
                        static fn($value) => $value > 0
                    )
                );
                self::mergeSpecialDaySets($id, $sets, $deleteIds, $sdSetTab, $sdIntTab, $sdSetLoc, $sdSetSvc);
            } else {
                self::replaceSpecialDaySets($id, $sets, $sdSetTab, $sdIntTab);
            }
        }

        if (array_key_exists('calendars', $data)) {
            self::replaceCalendars($id, is_array($data['calendars']) ? $data['calendars'] : [], $calConnTab, $calsTab);
        }

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['updated' => true]);
    }

    private static function handleEmployeeDelete(array $tables, ?int $tenantId, int $id, WP_REST_Request $request)
    {
        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        $hard = (bool) $request->get_param('hard');

        if ($hard) {
            self::hardDeleteRecord($usersTab, $id, $tenantId);

            $oldSetIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wSetTab} WHERE user_id=%d", $id));
            if ($oldSetIds) {
                $in = implode(',', array_fill(0, count($oldSetIds), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wIntTab} WHERE set_id IN ($in)", ...$oldSetIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wSetLoc} WHERE set_id IN ($in)", ...$oldSetIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wSetSvc} WHERE set_id IN ($in)", ...$oldSetIds));
            }
            $wpdb->delete($wSetTab, ['user_id' => $id], ['%d']);

            $wpdb->delete($holTab, ['user_id' => $id], ['%d']);

            $sdIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$sdSetTab} WHERE user_id=%d", $id));
            if ($sdIds) {
                $in = implode(',', array_fill(0, count($sdIds), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$sdIntTab} WHERE set_id IN ($in)", ...$sdIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$sdSetLoc} WHERE set_id IN ($in)", ...$sdIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$sdSetSvc} WHERE set_id IN ($in)", ...$sdIds));
            }
            $wpdb->delete($sdSetTab, ['user_id' => $id], ['%d']);

            $connIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$calConnTab} WHERE user_id=%d", $id)) ?: [];
            if ($connIds) {
                $in = implode(',', array_fill(0, count($connIds), '%d'));
                $calIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$calsTab} WHERE connection_id IN ($in)", ...$connIds)) ?: [];
                if ($calIds) {
                    $in2 = implode(',', array_fill(0, count($calIds), '%d'));
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE ce FROM {$eventsTab} ce
                            INNER JOIN {$calsTab} c ON c.id = ce.calendar_id
                            WHERE c.id IN ($in2)",
                            ...$calIds
                        )
                    );
                    $wpdb->query($wpdb->prepare("DELETE FROM {$calsTab} WHERE id IN ($in2)", ...$calIds));
                }
                $wpdb->query($wpdb->prepare("DELETE FROM {$calConnTab} WHERE id IN ($in)", ...$connIds));
            }

            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }

            return rest_ensure_response(['deleted' => true, 'hard' => true]);
        }

        self::softDeleteRecord($usersTab, $id, $tenantId);

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['deleted' => true, 'hard' => false]);
    }
    /**
     * /wp-json/bookando/v1/employees/bulk
     * Body: { action: 'block'|'activate'|'soft_delete'|'hard_delete'|'save', ids?: number[], payload?: any }
     */
    public static function bulk($params, \WP_REST_Request $request)
    {
        global $wpdb;
        $usersTab = $wpdb->prefix . 'bookando_users';
        $tenantId = (Gate::devBypass()) ? null : \Bookando\Core\Tenant\TenantManager::currentTenantId();

        $body    = (array) $request->get_json_params();
        $action  = sanitize_key($body['action'] ?? '');
        $ids     = array_values(array_filter(array_map('intval', (array)($body['ids'] ?? [])), fn($v)=>$v>0));
        $payload = $body['payload'] ?? null;

        if (!$action) return new \WP_Error('bad_request', __('Aktion fehlt.', 'bookando'), ['status' => 400]);

        switch ($action) {
            case 'hard_delete':
                if (empty($ids)) return new \WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
                foreach ($ids as $oneId) { self::hardDeleteRecord($usersTab, (int)$oneId, $tenantId); }
                if ($wpdb->last_error) return new \WP_Error('db_error', $wpdb->last_error, ['status'=>500]);
                return rest_ensure_response(['ok' => true, 'affected' => (int)$wpdb->rows_affected]);

            case 'soft_delete':
                if (empty($ids)) return new \WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
                foreach ($ids as $oneId) { self::softDeleteRecord($usersTab, (int)$oneId, $tenantId); }
                if ($wpdb->last_error) return new \WP_Error('db_error', $wpdb->last_error, ['status'=>500]);
                return rest_ensure_response(['ok' => true, 'affected' => (int)$wpdb->rows_affected]);

            case 'block':
            case 'activate': {
                if (empty($ids)) return new \WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
                $in = implode(',', array_fill(0, count($ids), '%d'));
                $whereTenant = '';
                $args = $ids;
                if ($tenantId) { $whereTenant = " AND tenant_id = %d"; $args[] = $tenantId; }
                $status = ($action === 'block') ? 'blocked' : 'active';
                $sql = "UPDATE {$usersTab}
                        SET status = %s, deleted_at = NULL, updated_at = %s
                        WHERE id IN ($in) {$whereTenant}";
                array_unshift($args, $status, current_time('mysql'));
                $wpdb->query($wpdb->prepare($sql, ...$args));
                if ($wpdb->last_error) return new \WP_Error('db_error', $wpdb->last_error, ['status'=>500]);
                return rest_ensure_response(['ok' => true, 'affected' => (int)$wpdb->rows_affected]);
            }

            case 'save': {
                // Single-Create/Update via Bulk (wie bei customers)
                $isCreate = empty($payload['id']);
                $data = self::sanitizeEmployeeInput((array)$payload, $isCreate);

                if (!empty($data['email']) && !is_email($data['email'])) {
                    return new \WP_Error('invalid_email', __('Ungültige E-Mail-Adresse.', 'bookando'), ['status' => 400]);
                }

                $targetStatus = self::normalizeStatus($data['status'] ?? 'active');
                $rules = \Bookando\Core\Settings\FormRules::get('employees', 'admin');

                if ($targetStatus !== 'deleted') {
                    $missing = self::validateByRules($data + ['status' => $targetStatus], $rules, $targetStatus);
                    if (!empty($missing)) {
                        return new \WP_Error('validation_error', __('Pflichtfelder fehlen.', 'bookando'), ['status' => 422, 'fields' => $missing]);
                    }
                }

                if ($isCreate) {
                    $roles = wp_json_encode(['bookando_employee']);
                    $insert = [
                        'tenant_id'     => $tenantId ?: (int)($data['tenant_id'] ?? 1),
                        'email'         => $data['email'] ?? null,
                        'first_name'    => $data['first_name'] ?? null,
                        'last_name'     => $data['last_name'] ?? null,
                        'phone'         => $data['phone'] ?? null,
                        'address'       => $data['address'] ?? null,
                        'address_2'     => $data['address_2'] ?? null,
                        'zip'           => $data['zip'] ?? null,
                        'city'          => $data['city'] ?? null,
                        'country'       => $data['country'] ?? null,
                        'birthdate'     => $data['birthdate'] ?? null,
                        'gender'        => $data['gender'] ?? null,
                        'language'      => $data['language'] ?? 'de',
                        'note'          => $data['note'] ?? null,
                        'description'   => $data['description'] ?? null,
                        'badge_id'      => $data['badge_id'] ?? null,
                        'avatar_url'    => $data['avatar_url'] ?? null,
                        'timezone'      => $data['timezone'] ?? null,
                        'password_hash' => $data['password_hash'] ?? null,
                        'roles'         => $roles,
                        'status'        => $targetStatus,
                        'created_at'    => current_time('mysql'),
                        'updated_at'    => current_time('mysql'),
                        'deleted_at'    => null,
                    ];
                    $wpdb->insert($usersTab, $insert);
                    if ($wpdb->last_error) return new \WP_Error('db_error', $wpdb->last_error, ['status'=>500]);
                    return rest_ensure_response(['ok' => true, 'id' => (int)$wpdb->insert_id]);
                } else {
                    $id = (int)$payload['id'];

                    // Nur übergebene Keys updaten (analog PUT)
                    $upd = [];
                    foreach ([
                        'first_name','last_name','email','phone','address','address_2','zip','city','country',
                        'birthdate','gender','language','note','description','avatar_url','timezone','status',
                        'badge_id','password_hash'
                    ] as $k) {
                        if (array_key_exists($k, $data)) { $upd[$k] = $data[$k]; }
                    }
                    $upd['updated_at'] = current_time('mysql');

                    $where = ['id' => $id]; $wf = ['%d'];
                    if ($tenantId) { $where['tenant_id'] = $tenantId; $wf[] = '%d'; }

                    $wpdb->update($usersTab, $upd, $where, null, $wf);
                    if ($wpdb->last_error) return new \WP_Error('db_error', $wpdb->last_error, ['status'=>500]);

                    return rest_ensure_response(['ok' => true, 'updated' => true, 'id' => $id]);
                }
            }

            default:
                return new \WP_Error('bad_request', __('Unbekannte Aktion.', 'bookando'), ['status' => 400]);
        }
    }

    /**
     * /wp-json/bookando/v1/employees/{id}/workday-sets
     *   GET  → Sets + Intervals laden
     *   POST → Full-Replace: Body { workday_sets: [...] }
     */
    public static function workdaySets($params, WP_REST_Request $request)
    {
        global $wpdb;
        $p        = $wpdb->prefix . 'bookando_';
        $wSetTab  = $p . 'employees_workday_sets';
        $wIntTab  = $p . 'employees_workday_intervals';
        $wSetLoc  = $p . 'employees_workday_set_locations';
        $wSetSvc  = $p . 'employees_workday_set_services';

        $method = strtoupper($request->get_method());
        $userId = 0;
        if (isset($params['id']))           { $userId = (int)$params['id']; }
        elseif (isset($params['subkey']))   { $userId = (int)$params['subkey']; }
        elseif ($request->get_param('id') !== null) { $userId = (int)$request->get_param('id'); }

        if ($userId <= 0) {
            return new WP_Error('bad_request',__('Benutzer-ID fehlt.', 'bookando'),['status'=>400]);
        }

        if ($method === 'GET') {
            if (!self::canReadRecord($userId, $request)) {
                return new WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
            }

            $sets = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, week_day_id, label, sort,
                            DATE_FORMAT(created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                            DATE_FORMAT(updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                    FROM {$wSetTab}
                    WHERE user_id=%d
                    ORDER BY week_day_id ASC, sort ASC, id ASC", $userId
                ),
                ARRAY_A
            ) ?: [];

            if ($sets) {
                $ids = array_map('intval', array_column($sets, 'id'));
                $in  = implode(',', array_fill(0, count($ids), '%d'));

                $ints = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, set_id, is_break, start_time, end_time
                        FROM {$wIntTab}
                        WHERE set_id IN ($in)
                        ORDER BY start_time ASC",
                        ...$ids
                    ),
                    ARRAY_A
                ) ?: [];
                $intMap=[]; foreach ($ints as $r) { $intMap[(int)$r['set_id']][] = $r; }

                $svcRows = $wpdb->get_results($wpdb->prepare(
                    "SELECT set_id, service_id FROM {$wSetSvc} WHERE set_id IN ($in)", ...$ids
                ), ARRAY_A) ?: [];
                $locRows = $wpdb->get_results($wpdb->prepare(
                    "SELECT set_id, location_id FROM {$wSetLoc} WHERE set_id IN ($in)", ...$ids
                ), ARRAY_A) ?: [];

                $svcMap=[]; foreach($svcRows as $r){ $svcMap[(int)$r['set_id']][] = (int)$r['service_id']; }
                $locMap=[]; foreach($locRows as $r){ $locMap[(int)$r['set_id']][] = (int)$r['location_id']; }

                foreach ($sets as &$s) {
                    $sid = (int)$s['id'];
                    $s['intervals'] = $intMap[$sid] ?? [];
                    $s['services']  = $svcMap[$sid] ?? [];
                    $s['locations'] = $locMap[$sid] ?? [];
                    // Backcompat-Felder nur lesend (ok):
                    $s['service_id']  = $s['services'][0]  ?? null;
                    $s['location_id'] = $s['locations'][0] ?? null;
                }
                unset($s);
            }
            return rest_ensure_response(['workday_sets' => $sets]);
        }

        if ($method === 'POST') {
            if (!self::canWriteRecord($userId, $request)) {
                return new WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
            }
            $data = (array)$request->get_json_params();

            // Merge-Modus (Upsert + gezielte Löschungen)
            $mode = sanitize_key($data['mode'] ?? '');
            if ($mode !== 'merge') {
                if (!empty($data['upsert']) || !empty($data['delete_ids'])) {
                    $mode = 'merge';
                } elseif (is_array($data['workday_sets'] ?? null)) {
                    $hasIds = false;
                    foreach ($data['workday_sets'] as $s) {
                        if (!empty($s['id'])) { $hasIds = true; break; }
                    }
                    if ($hasIds) {
                        $data['upsert'] = $data['workday_sets'];
                        unset($data['workday_sets']);
                        $mode = 'merge';
                    }
                }
            }

            if ($mode === 'merge') {
                $upsert     = is_array($data['upsert'] ?? null) ? $data['upsert'] : [];
                $deleteIds  = array_values(array_filter(array_map('intval', (array)($data['delete_ids'] ?? [])), fn($v)=>$v>0));
                self::mergeWorkdaySets($userId, $upsert, $deleteIds, $wSetTab, $wIntTab, $wSetLoc, $wSetSvc);
                if ($wpdb->last_error) return new WP_Error('db_error',$wpdb->last_error,['status'=>500]);
                return rest_ensure_response(['updated'=>true, 'mode'=>'merge']);
            }

            // Full-Replace (Backcompat innerhalb von workday_sets beibehalten)
            $sets = is_array($data['workday_sets'] ?? null) ? $data['workday_sets'] : [];
            self::replaceWorkdaySets($userId, $sets, $wSetTab, $wIntTab);
            if ($wpdb->last_error) return new WP_Error('db_error',$wpdb->last_error,['status'=>500]);
            return rest_ensure_response(['updated'=>true, 'mode'=>'replace']);
        }

        return new WP_Error('method_not_allowed',__('Methode nicht unterstützt.', 'bookando'),['status'=>405]);
    }

    public static function daysOff(array $params, \WP_REST_Request $request)
    {
        global $wpdb;
        $p   = $wpdb->prefix . 'bookando_';
        $tab = $p . 'employees_days_off';

        // Benutzer-ID robust ermitteln (Backcompat wie in den anderen Subressourcen)
        $userId = 0;
        if (isset($params['id']))               { $userId = (int)$params['id']; }
        elseif (isset($params['subkey']))       { $userId = (int)$params['subkey']; }
        elseif ($request->get_param('id') !== null) { $userId = (int)$request->get_param('id'); }

        if ($userId <= 0) {
            return new \WP_Error('bad_request', __('Benutzer-ID fehlt.', 'bookando'), ['status' => 400]);
        }

        $method = strtoupper($request->get_method());

        /* =========================
        GET /employees/{id}/days-off
        ========================= */
        if ($method === 'GET') {
            if (!self::canReadRecord($userId, $request)) {
                return new \WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
            }

            // start_time bewusst NICHT mehr auswählen.
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id,
                            name,
                            note,
                            DATE_FORMAT(start_date,'%%Y-%%m-%%d') AS start_date,
                            DATE_FORMAT(end_date,'%%Y-%%m-%%d')   AS end_date,
                            repeat_yearly,
                            DATE_FORMAT(created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                            DATE_FORMAT(updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                    FROM {$tab}
                    WHERE user_id=%d
                    ORDER BY start_date ASC, id ASC",
                    $userId
                ),
                ARRAY_A
            ) ?: [];

            return rest_ensure_response(['days_off' => $rows]);
        }

        /* =========================
        PUT/POST – MERGE oder REPLACE
        ========================= */
        if ($method === 'PUT' || $method === 'POST') {
            if (!self::canWriteRecord($userId, $request)) {
                return new \WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
            }

            $body = (array)$request->get_json_params();

            // Modus automatisch erkennen (wie bei workdaySets/specialDaySets):
            // - explizit mode=merge
            // - oder Felder 'upsert'/'delete_ids' vorhanden
            // - oder Items mit 'id' bzw. '_delete' → merge
            $mode = sanitize_key($body['mode'] ?? '');
            $hasUpsert    = is_array($body['upsert'] ?? null);
            $hasDeleteIds = !empty($body['delete_ids']);
            $daysOffItems = is_array($body['days_off'] ?? null) ? $body['days_off'] : [];

            if ($mode !== 'merge') {
                if ($hasUpsert || $hasDeleteIds) {
                    $mode = 'merge';
                } else {
                    foreach ($daysOffItems as $it) {
                        if (!empty($it['id']) || !empty($it['_delete'])) { $mode = 'merge'; break; }
                    }
                }
            }

            if ($mode === 'merge') {
                // Upsert + gezielte Löschungen
                $upsert    = $hasUpsert ? (array)$body['upsert'] : $daysOffItems;
                $deleteIds = array_values(array_filter(array_map('intval', (array)($body['delete_ids'] ?? [])), fn($v)=>$v>0));

                self::mergeDaysOff($userId, $upsert, $tab, $deleteIds);
                if ($wpdb->last_error) return new \WP_Error('db_error', $wpdb->last_error, ['status'=>500]);

                return rest_ensure_response(['updated' => true, 'mode' => 'merge']);
            }

            // Full-Replace (Backcompat: wenn nur days_off übergeben wird)
            self::replaceDaysOff($userId, $daysOffItems, $tab);
            if ($wpdb->last_error) return new \WP_Error('db_error', $wpdb->last_error, ['status'=>500]);

            return rest_ensure_response(['updated' => true, 'mode' => 'replace']);
        }

        return new \WP_Error('method_not_allowed', __('Methode nicht unterstützt.', 'bookando'), ['status' => 405]);
    }

    
    public static function specialDaySets($params, WP_REST_Request $request = null)
    {
        // Falls WordPress nur den Request übergibt:
        if ($params instanceof WP_REST_Request && $request === null) {
            $request = $params;
            $params = [];
        }

        global $wpdb;
        $p        = $wpdb->prefix . 'bookando_';
        $sdSet    = $p . 'employees_specialday_sets';
        $sdInt    = $p . 'employees_specialday_intervals';
        $sdSetLoc = $p . 'employees_specialday_set_locations';
        $sdSetSvc = $p . 'employees_specialday_set_services';

        $method = strtoupper($request->get_method());

        // Benutzer-ID robust ermitteln
        $userId = 0;
        if (isset($params['id']))               { $userId = (int)$params['id']; }
        elseif (isset($params['subkey']))       { $userId = (int)$params['subkey']; }
        elseif ($request->get_param('id') !== null) { $userId = (int)$request->get_param('id'); }

        if ($userId <= 0) return new WP_Error('bad_request',__('Benutzer-ID fehlt.', 'bookando'),['status'=>400]);

        if ($method === 'GET') {
            if (!self::canReadRecord($userId, $request)) {
                return new WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
            }

            $sets = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, user_id,
                            DATE_FORMAT(start_date,'%%Y-%%m-%%d') AS start_date,
                            DATE_FORMAT(COALESCE(end_date,start_date),'%%Y-%%m-%%d') AS end_date,
                            label, sort,
                            DATE_FORMAT(created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                            DATE_FORMAT(updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                    FROM {$sdSet}
                    WHERE user_id=%d
                    ORDER BY start_date ASC, sort ASC, id ASC",
                    $userId
                ),
                ARRAY_A
            ) ?: [];

            if ($sets) {
                $ids = array_map('intval', array_column($sets, 'id'));
                $in  = implode(',', array_fill(0, count($ids), '%d'));

                $ints = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, set_id, is_break,
                                TIME_FORMAT(start_time,'%%H:%%i:%%s') AS start_time,
                                TIME_FORMAT(end_time,'%%H:%%i:%%s')   AS end_time
                        FROM {$sdInt}
                        WHERE set_id IN ($in)
                        ORDER BY start_time ASC",
                        ...$ids
                    ),
                    ARRAY_A
                ) ?: [];

                $svcRows = $wpdb->get_results($wpdb->prepare(
                    "SELECT set_id, service_id FROM {$sdSetSvc} WHERE set_id IN ($in)", ...$ids
                ), ARRAY_A) ?: [];
                $locRows = $wpdb->get_results($wpdb->prepare(
                    "SELECT set_id, location_id FROM {$sdSetLoc} WHERE set_id IN ($in)", ...$ids
                ), ARRAY_A) ?: [];

                $intMap=[]; foreach ($ints as $r) {
                    $intMap[(int)$r['set_id']][] = [
                        'id'         => (int)$r['id'],
                        'set_id'     => (int)$r['set_id'],
                        'start_time' => (string)$r['start_time'],
                        'end_time'   => (string)$r['end_time'],
                        'is_break'   => (int)$r['is_break'],
                    ];
                }
                $svcMap=[]; foreach($svcRows as $r){ $svcMap[(int)$r['set_id']][]=(int)$r['service_id']; }
                $locMap=[]; foreach($locRows as $r){ $locMap[(int)$r['set_id']][]=(int)$r['location_id']; }

                foreach ($sets as &$s) {
                    $sid=(int)$s['id'];
                    $s['intervals'] = $intMap[$sid] ?? [];
                    $s['services']  = $svcMap[$sid] ?? [];
                    $s['locations'] = $locMap[$sid] ?? [];
                }
                unset($s);
            }

            return rest_ensure_response(['special_day_sets' => $sets]);
        }

        if ($method === 'POST') {
            if (!self::canWriteRecord($userId, $request)) {
                return new WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
            }
            $data = (array)$request->get_json_params();

            $mode = sanitize_key($data['mode'] ?? '');

            // Auto-Erkennung für Upsert
            if ($mode !== 'merge') {
                if (!empty($data['upsert']) || !empty($data['delete_ids'])) {
                    $mode = 'merge';
                } elseif (is_array($data['special_day_sets'] ?? null)) {
                    $hasIds = false;
                    foreach ($data['special_day_sets'] as $s) {
                        if (!empty($s['id'])) { $hasIds = true; break; }
                    }
                    if ($hasIds) {
                        $data['upsert'] = $data['special_day_sets'];
                        unset($data['special_day_sets']);
                        $mode = 'merge';
                    }
                }
            }

            if ($mode === 'merge') {
                $upsert    = is_array($data['upsert'] ?? null) ? $data['upsert'] : [];
                $deleteIds = array_values(array_filter(array_map('intval', (array)($data['delete_ids'] ?? [])), fn($v)=>$v>0));
                self::mergeSpecialDaySets($userId, $upsert, $deleteIds, $sdSet, $sdInt, $sdSetLoc, $sdSetSvc);
                if ($wpdb->last_error) return new WP_Error('db_error',$wpdb->last_error,['status'=>500]);
                return rest_ensure_response(['updated'=>true, 'mode'=>'merge']);
            }

            // Full-Replace
            $sets = is_array($data['special_day_sets'] ?? null) ? $data['special_day_sets'] : [];
            self::replaceSpecialDaySets($userId, $sets, $sdSet, $sdInt);
            if ($wpdb->last_error) return new WP_Error('db_error',$wpdb->last_error,['status'=>500]);
            return rest_ensure_response(['updated'=>true, 'mode'=>'replace']);
        }

        return new WP_Error('method_not_allowed',__('Methode nicht unterstützt.', 'bookando'),['status'=>405]);
    }

    /**
     * /wp-json/bookando/v1/employees/{id}/special-days
     *   GET  → Sets + Intervals laden
     *   POST → Full-Replace: Body { special-days: [...] }
     */

    public static function specialDays($params, WP_REST_Request $request)
    {
        return new WP_Error('gone', __('Dieser Endpunkt ist veraltet. Bitte /employees/{id}/special-day-sets verwenden.', 'bookando'), ['status'=>410]);
    }

    /* =========================================================
     * Helpers: Save/Replace nested collections
     * ========================================================= */

    protected static function replaceWorkingHours(int $userId, array $items, string $table): void
    {
        if (function_exists('_deprecated_function')) {
            _deprecated_function(__METHOD__, '1.0.0', 'replaceWorkdaySets');
        }
        global $wpdb;
        $wpdb->delete($table, ['user_id'=>$userId], ['%d']);

        foreach ($items as $it) {
            $day = (int)($it['week_day_id'] ?? 0);
            $st  = self::toDbTime($it['start_time'] ?? '');
            $en  = self::toDbTime($it['end_time'] ?? '');
            $loc = isset($it['location_id']) && $it['location_id'] !== '' ? (int)$it['location_id'] : null;
            $svc = isset($it['service_id'])  && $it['service_id']  !== '' ? (int)$it['service_id']  : null;
            $brk = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
            $cmb = isset($it['combo_id']) && $it['combo_id'] !== '' ? (int)$it['combo_id'] : null; // optional

            if ($day < 1 || $day > 7 || !$st || !$en || $st >= $en) { continue; }

            $row = [
                'user_id'     => $userId,
                'week_day_id' => $day,
                'start_time'  => $st,
                'end_time'    => $en,
                'is_break'    => $brk,
                'created_at'  => current_time('mysql'),
                'updated_at'  => current_time('mysql'),
            ];
            $fmt = ['%d','%d','%s','%s','%d','%s','%s'];

            if ($loc !== null) { $row['location_id'] = $loc; $fmt[] = '%d'; }
            if ($svc !== null) { $row['service_id']  = $svc; $fmt[] = '%d'; }
            if ($cmb !== null) { $row['combo_id']    = $cmb; $fmt[] = '%d'; }

            $wpdb->insert($table, $row, $fmt);
        }
    }

    /** Full-Replace für normalisierte Workday-Sets + Intervals. */
    protected static function replaceWorkdaySets(int $userId, array $sets, string $setTab, string $intTab): void
    {
        global $wpdb;
        $p       = $wpdb->prefix . 'bookando_';
        $wSetLoc = $p . 'employees_workday_set_locations';
        $wSetSvc = $p . 'employees_workday_set_services';

        $wpdb->query('START TRANSACTION');
        try {
            $oldSetIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$setTab} WHERE user_id=%d", $userId)) ?: [];
            if ($oldSetIds) {
                $in = implode(',', array_fill(0, count($oldSetIds), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$intTab}  WHERE set_id IN ($in)", ...$oldSetIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wSetLoc} WHERE set_id IN ($in)", ...$oldSetIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wSetSvc} WHERE set_id IN ($in)", ...$oldSetIds));
                $wpdb->delete($setTab, ['user_id' => $userId], ['%d']);
            }

            foreach ($sets as $idx => $s) {
                $day = (int)($s['week_day_id'] ?? 0);
                if ($day < 1 || $day > 7) continue;

                $label = sanitize_text_field($s['label'] ?? '');
                $sort  = (int)($s['sort'] ?? $idx);

                $setRow = [
                    'user_id'     => $userId,
                    'week_day_id' => $day,
                    'label'       => ($label !== '') ? $label : null,
                    'sort'        => $sort,
                    'created_at'  => current_time('mysql'),
                    'updated_at'  => current_time('mysql'),
                ];
                $wpdb->insert($setTab, $setRow, ['%d','%d','%s','%d','%s','%s']);
                $setId = (int)$wpdb->insert_id;
                if ($setId <= 0) continue;

                // === N:N: Arrays normalisieren (Backcompat: single → array)
                $services  = (array)($s['services']  ?? (isset($s['service_id'])  ? [$s['service_id']]  : []));
                $locations = (array)($s['locations'] ?? (isset($s['location_id']) ? [$s['location_id']] : []));
                $services  = array_values(array_unique(array_filter(array_map('intval', $services),  fn($v)=>$v>0)));
                $locations = array_values(array_unique(array_filter(array_map('intval', $locations), fn($v)=>$v>0)));

                foreach ($services as $sid) {
                    $wpdb->insert($wSetSvc, ['set_id'=>$setId, 'service_id'=>$sid, 'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                }
                foreach ($locations as $lid) {
                    $wpdb->insert($wSetLoc, ['set_id'=>$setId, 'location_id'=>$lid, 'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                }

                // === Intervals wie gehabt
                $intervals = is_array($s['intervals'] ?? null) ? $s['intervals'] : [];
                foreach ($intervals as $it) {
                    $st = self::toDbTime($it['start_time'] ?? '');
                    $en = self::toDbTime($it['end_time']   ?? '');
                    $br = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                    if (!$st || !$en || $st >= $en) continue;

                    $wpdb->insert($intTab, [
                        'set_id'=>$setId, 'start_time'=>$st, 'end_time'=>$en, 'is_break'=>$br,
                        'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql'),
                    ], ['%d','%s','%s','%d','%s','%s']);
                }
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    protected static function mergeWorkdaySets(int $userId, array $sets, array $deleteIds, string $setTab, string $intTab, string $wSetLoc, string $wSetSvc): void
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
            // 0) Löschungen (nur explizit angegebene IDs, inklusive Mappings/Intervals)
            if (!empty($deleteIds)) {
                $ids = array_values(array_unique(array_map('intval', $deleteIds)));
                $in  = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$intTab}  WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wSetLoc} WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wSetSvc} WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$setTab}  WHERE id IN ($in) AND user_id=%d", ...array_merge($ids, [$userId])));
            }

            // 1) Bestehende Sets holen (für Ownership + Diff)
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$setTab} WHERE user_id=%d", $userId), ARRAY_A) ?: [];
            $existing = array_map('intval', array_column($rows, 'id'));
            $exists = array_fill_keys($existing, true);

            foreach ($sets as $idx => $s) {
                $setId = isset($s['id']) ? (int)$s['id'] : 0;
                $day   = (int)($s['week_day_id'] ?? 0);
                if ($day < 1 || $day > 7) continue;

                $label = sanitize_text_field($s['label'] ?? '');
                $sort  = (int)($s['sort'] ?? $idx);

                // Hilfsfunktionen
                $arr = function($v){ $a = is_array($v) ? $v : []; $a = array_values(array_unique(array_map('intval',$a))); return array_filter($a, fn($x)=>$x>0); };
                $services  = $arr($s['services']  ?? (isset($s['service_id'])  ? [$s['service_id']]  : []));
                $locations = $arr($s['locations'] ?? (isset($s['location_id']) ? [$s['location_id']] : []));

                if ($setId > 0 && isset($exists[$setId])) {
                    // 1a) UPDATE Set (created_at bleibt bestehen)
                    $wpdb->update($setTab, [
                        'week_day_id' => $day,
                        'label'       => ($label !== '') ? $label : null,
                        'sort'        => $sort,
                        'updated_at'  => current_time('mysql'),
                    ], ['id'=>$setId,'user_id'=>$userId], ['%d','%s','%d','%s'], ['%d','%d']);

                    // 1b) N:N Services/Locations differenziell
                    $currSvc = $wpdb->get_col($wpdb->prepare("SELECT service_id FROM {$wSetSvc} WHERE set_id=%d", $setId)) ?: [];
                    $currLoc = $wpdb->get_col($wpdb->prepare("SELECT location_id FROM {$wSetLoc} WHERE set_id=%d", $setId)) ?: [];
                    $currSvc = array_map('intval',$currSvc); $currLoc = array_map('intval',$currLoc);

                    $toAddSvc = array_values(array_diff($services, $currSvc));
                    $toDelSvc = array_values(array_diff($currSvc, $services));
                    $toAddLoc = array_values(array_diff($locations, $currLoc));
                    $toDelLoc = array_values(array_diff($currLoc, $locations));

                    if ($toDelSvc) {
                        $in = implode(',', array_fill(0, count($toDelSvc), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$wSetSvc} WHERE set_id=%d AND service_id IN ($in)", $setId, ...$toDelSvc));
                    }
                    foreach ($toAddSvc as $sid) {
                        $wpdb->insert($wSetSvc, ['set_id'=>$setId,'service_id'=>$sid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                    }

                    if ($toDelLoc) {
                        $in = implode(',', array_fill(0, count($toDelLoc), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$wSetLoc} WHERE set_id=%d AND location_id IN ($in)", $setId, ...$toDelLoc));
                    }
                    foreach ($toAddLoc as $lid) {
                        $wpdb->insert($wSetLoc, ['set_id'=>$setId,'location_id'=>$lid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                    }

                    // 1c) Intervals upserten (ID → UPDATE, sonst INSERT)
                    $incoming = is_array($s['intervals'] ?? null) ? $s['intervals'] : [];
                    $currRows = $wpdb->get_results($wpdb->prepare(
                        "SELECT id FROM {$intTab} WHERE set_id=%d", $setId
                    ), ARRAY_A) ?: [];
                    $currMap = [];
                    foreach ($currRows as $r) { $currMap[(int)$r['id']] = true; }

                    $seenIds = [];
                    foreach ($incoming as $it) {
                        $iid = isset($it['id']) ? (int)$it['id'] : 0;
                        $st  = self::toDbTime($it['start_time'] ?? '');
                        $en  = self::toDbTime($it['end_time']   ?? '');
                        $br  = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                        if (!$st || !$en || $st >= $en) continue;

                        if ($iid > 0) {
                            // Upsert mit fixer ID
                            $wpdb->query($wpdb->prepare(
                            "INSERT INTO {$intTab} AS new (id,set_id,start_time,end_time,is_break,created_at,updated_at)
                            VALUES (%d,%d,%s,%s,%d,%s,%s)
                            ON DUPLICATE KEY UPDATE
                            start_time = new.start_time,
                            end_time   = new.end_time,
                            is_break   = new.is_break,
                            updated_at = IF(
                                start_time <> new.start_time
                                OR end_time <> new.end_time
                                OR is_break <> new.is_break,
                                new.updated_at,
                                updated_at
                            )",
                            $iid, $setId, $st, $en, $br, current_time('mysql'), current_time('mysql')
                            ));
                            $seenIds[] = $iid;
                        } else {
                            // Neue Zeile ohne ID → reines INSERT
                            $wpdb->insert($intTab, [
                                'set_id'     => $setId,
                                'start_time' => $st,
                                'end_time'   => $en,
                                'is_break'   => $br,
                                'created_at' => current_time('mysql'),
                                'updated_at' => current_time('mysql'),
                            ], ['%d','%s','%s','%d','%s','%s']);
                            $seenIds[] = (int)$wpdb->insert_id;
                        }
                    }
                    // nicht mehr vorhandene Intervalle löschen:
                    $currIds = array_map('intval', array_keys($currMap));
                    $toDelete = array_diff($currIds, $seenIds);
                    if ($toDelete) {
                        $in = implode(',', array_fill(0, count($toDelete), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$intTab} WHERE set_id=%d AND id IN ($in)", $setId, ...$toDelete));
                    }
                } else {
                    // 2) INSERT Set
                    $wpdb->insert($setTab, [
                        'user_id'     => $userId,
                        'week_day_id' => $day,
                        'label'       => ($label !== '') ? $label : null,
                        'sort'        => $sort,
                        'created_at'  => current_time('mysql'),
                        'updated_at'  => current_time('mysql'),
                    ], ['%d','%d','%s','%d','%s','%s']);
                    $newId = (int)$wpdb->insert_id;
                    if ($newId <= 0) continue;

                    foreach ($services as $sid) {
                        $wpdb->insert($wSetSvc, ['set_id'=>$newId,'service_id'=>$sid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                    }
                    foreach ($locations as $lid) {
                        $wpdb->insert($wSetLoc, ['set_id'=>$newId,'location_id'=>$lid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                    }
                    $incoming = is_array($s['intervals'] ?? null) ? $s['intervals'] : [];
                    foreach ($incoming as $it) {
                        $st = self::toDbTime($it['start_time'] ?? '');
                        $en = self::toDbTime($it['end_time']   ?? '');
                        $br = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                        if (!$st || !$en || $st >= $en) continue;
                        $wpdb->insert($intTab, [
                            'set_id'     => $newId,
                            'start_time' => $st,
                            'end_time'   => $en,
                            'is_break'   => $br,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d','%s','%s','%d','%s','%s']);
                    }
                }
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }


    protected static function mergeSpecialDaySets(
        int $userId,
        array $sets,
        array $deleteIds,
        string $setTab,
        string $intTab,
        string $setLocTab,
        string $setSvcTab
    ): void {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
            // 0) gezielte Löschungen (inkl. Mappings & Intervals)
            if (!empty($deleteIds)) {
                $ids = array_values(array_unique(array_map('intval', $deleteIds)));
                $in  = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$intTab}    WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$setLocTab} WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$setSvcTab} WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$setTab}    WHERE id IN ($in) AND user_id=%d", ...array_merge($ids, [$userId])));
            }

            // 1) vorhandene Sets (Ownership prüfen)
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$setTab} WHERE user_id=%d", $userId), ARRAY_A) ?: [];
            $existing = array_map('intval', array_column($rows, 'id'));
            $exists   = array_fill_keys($existing, true);

            $arr = function($v){ $a = is_array($v) ? $v : []; $a = array_values(array_unique(array_map('intval',$a))); return array_filter($a, fn($x)=>$x>0); };
            $toDbTime = fn(string $v) => self::toDbTime($v);

            foreach ($sets as $idx => $s) {
                $setId = isset($s['id']) ? (int)$s['id'] : 0;

                $start = Sanitizer::date($s['start_date'] ?? ($s['date_start'] ?? null));
                $end   = Sanitizer::date($s['end_date']   ?? ($s['date_end']   ?? $start));
                if (!$start) continue;

                $label = sanitize_text_field($s['label'] ?? '');
                $sort  = (int)($s['sort'] ?? $idx);

                $services  = $arr($s['services']  ?? (isset($s['service_id'])  ? [$s['service_id']]  : []));
                $locations = $arr($s['locations'] ?? (isset($s['location_id']) ? [$s['location_id']] : []));

                if ($setId > 0 && isset($exists[$setId])) {
                    // UPDATE Set (created_at bleibt)
                    $wpdb->update($setTab, [
                        'start_date' => $start,
                        'end_date'   => $end ?: $start,
                        'label'      => ($label !== '') ? $label : null,
                        'sort'       => $sort,
                        'updated_at' => current_time('mysql'),
                    ], ['id'=>$setId,'user_id'=>$userId], ['%s','%s','%s','%d','%s'], ['%d','%d']);

                    // Mappings diffen
                    $currSvc = $wpdb->get_col($wpdb->prepare("SELECT service_id FROM {$setSvcTab} WHERE set_id=%d", $setId)) ?: [];
                    $currLoc = $wpdb->get_col($wpdb->prepare("SELECT location_id FROM {$setLocTab} WHERE set_id=%d", $setId)) ?: [];
                    $currSvc = array_map('intval',$currSvc); $currLoc = array_map('intval',$currLoc);

                    $toAddSvc = array_values(array_diff($services, $currSvc));
                    $toDelSvc = array_values(array_diff($currSvc, $services));
                    $toAddLoc = array_values(array_diff($locations, $currLoc));
                    $toDelLoc = array_values(array_diff($currLoc, $locations));

                    if ($toDelSvc) {
                        $in = implode(',', array_fill(0, count($toDelSvc), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$setSvcTab} WHERE set_id=%d AND service_id IN ($in)", $setId, ...$toDelSvc));
                    }
                    foreach ($toAddSvc as $sid) {
                        $wpdb->insert($setSvcTab, ['set_id'=>$setId,'service_id'=>$sid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                    }

                    if ($toDelLoc) {
                        $in = implode(',', array_fill(0, count($toDelLoc), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$setLocTab} WHERE set_id=%d AND location_id IN ($in)", $setId, ...$toDelLoc));
                    }
                    foreach ($toAddLoc as $lid) {
                        $wpdb->insert($setLocTab, ['set_id'=>$setId,'location_id'=>$lid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                    }

                    // 1 Intervals upserten (ID → UPDATE, sonst INSERT)
                    $incoming = is_array($s['intervals'] ?? null) ? $s['intervals'] : [];
                    $currRows = $wpdb->get_results($wpdb->prepare(
                        "SELECT id FROM {$intTab} WHERE set_id=%d", $setId
                    ), ARRAY_A) ?: [];
                    $currMap = [];
                    foreach ($currRows as $r) { $currMap[(int)$r['id']] = true; }

                    $seenIds = [];
                    foreach ($incoming as $it) {
                        $iid = isset($it['id']) ? (int)$it['id'] : 0;
                        $st  = self::toDbTime($it['start_time'] ?? '');
                        $en  = self::toDbTime($it['end_time']   ?? '');
                        $br  = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                        if (!$st || !$en || $st >= $en) continue;

                        if ($iid > 0) {
                            // Upsert mit fixer ID
                            $wpdb->query($wpdb->prepare(
                            "INSERT INTO {$intTab} AS new (id,set_id,start_time,end_time,is_break,created_at,updated_at)
                            VALUES (%d,%d,%s,%s,%d,%s,%s)
                            ON DUPLICATE KEY UPDATE
                            start_time = new.start_time,
                            end_time   = new.end_time,
                            is_break   = new.is_break,
                            updated_at = IF(
                                start_time <> new.start_time
                                OR end_time <> new.end_time
                                OR is_break <> new.is_break,
                                new.updated_at,
                                updated_at
                            )",
                            $iid, $setId, $st, $en, $br, current_time('mysql'), current_time('mysql')
                            ));
                            $seenIds[] = $iid;
                        } else {
                            // Neue Zeile ohne ID → reines INSERT
                            $wpdb->insert($intTab, [
                                'set_id'     => $setId,
                                'start_time' => $st,
                                'end_time'   => $en,
                                'is_break'   => $br,
                                'created_at' => current_time('mysql'),
                                'updated_at' => current_time('mysql'),
                            ], ['%d','%s','%s','%d','%s','%s']);
                            $seenIds[] = (int)$wpdb->insert_id;
                        }
                    }
                    // nicht mehr vorhandene Intervalle löschen:
                    $currIds = array_map('intval', array_keys($currMap));
                    $toDelete = array_diff($currIds, $seenIds);
                    if ($toDelete) {
                        $in = implode(',', array_fill(0, count($toDelete), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$intTab} WHERE set_id=%d AND id IN ($in)", $setId, ...$toDelete));
                    }
                } else {
                    // INSERT Set
                    $wpdb->insert($setTab, [
                        'user_id'    => $userId,
                        'start_date' => $start,
                        'end_date'   => $end ?: $start,
                        'label'      => ($label !== '') ? $label : null,
                        'sort'       => $sort,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ], ['%d','%s','%s','%s','%d','%s','%s']);
                    $newId = (int)$wpdb->insert_id;
                    if ($newId <= 0) continue;

                    foreach ($services as $sid) {
                        $wpdb->insert($setSvcTab, ['set_id'=>$newId,'service_id'=>$sid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                    }
                    foreach ($locations as $lid) {
                        $wpdb->insert($setLocTab, ['set_id'=>$newId,'location_id'=>$lid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                    }
                    $incoming = is_array($s['intervals'] ?? null) ? $s['intervals'] : [];
                    foreach ($incoming as $it) {
                        $st = self::toDbTime($it['start_time'] ?? '');
                        $en = self::toDbTime($it['end_time']   ?? '');
                        $br = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                        if (!$st || !$en || $st >= $en) continue;
                        $wpdb->insert($intTab, [
                            'set_id'     => $newId,
                            'start_time' => $st,
                            'end_time'   => $en,
                            'is_break'   => $br,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d','%s','%s','%d','%s','%s']);
                    }
                }
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    protected static function mergeDaysOff(
        int $userId,
        array $items,
        string $table,
        array $deleteIds = []
    ): void {
        global $wpdb;

        // _delete-Flags beachten
        foreach ($items as $it) {
            if (!empty($it['_delete']) && !empty($it['id'])) {
                $deleteIds[] = (int)$it['id'];
            }
        }
        $deleteIds = array_values(array_unique(array_filter(array_map('intval', $deleteIds), fn($v)=>$v>0)));

        $wpdb->query('START TRANSACTION');
        try {
            // gezielte Löschungen
            if (!empty($deleteIds)) {
                $in = implode(',', array_fill(0, count($deleteIds), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table} WHERE user_id=%d AND id IN ($in)",
                    $userId, ...$deleteIds
                ));
            }

            // vorhandene IDs (Ownership)
            $rows   = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d", $userId), ARRAY_A) ?: [];
            $exists = array_fill_keys(array_map('intval', array_column($rows, 'id')), true);

            // Upserts
            foreach ($items as $it) {
                if (!empty($it['_delete'])) { continue; }

                $id   = isset($it['id']) ? (int)$it['id'] : 0;
                $name = sanitize_text_field($it['name'] ?? '');
                $note = sanitize_text_field($it['note'] ?? '');

                $sd = self::toDbDate($it['start_date'] ?? '');
                $ed = self::toDbDate($it['end_date']   ?? '');
                if (!$sd) { continue; }
                if (!$ed) { $ed = $sd; }

                $rep = (int) !!($it['repeat_yearly'] ?? $it['repeat'] ?? 0);

                if ($id > 0 && isset($exists[$id])) {
                    $wpdb->update($table, [
                        'name'          => ($name !== '') ? $name : NULL,
                        'note'          => ($note !== '') ? $note : NULL,
                        'start_date'    => $sd,
                        'end_date'      => $ed,
                        'repeat_yearly' => $rep,
                        'updated_at'    => current_time('mysql'),
                    ], ['id'=>$id,'user_id'=>$userId], ['%s','%s','%s','%s','%d','%s'], ['%d','%d']);
                } else {
                    $wpdb->insert($table, [
                        'user_id'       => $userId,
                        'name'          => ($name !== '') ? $name : NULL,
                        'note'          => ($note !== '') ? $note : NULL,
                        'start_date'    => $sd,
                        'end_date'      => $ed,
                        'repeat_yearly' => $rep,
                        'created_at'    => current_time('mysql'),
                        'updated_at'    => current_time('mysql'),
                    ], ['%d','%s','%s','%s','%s','%d','%s','%s']);
                }
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }


    /** Optional: Legacy-Konverter (flache working_hours → ein Set pro Wochentag). */
    protected static function convertFlatWorkingHoursToSets(array $flat): array
    {
        $byDay = [];
        foreach ($flat as $r) {
            $d = (int)($r['week_day_id'] ?? 0);
            if ($d < 1 || $d > 7) continue;
            $byDay[$d] ??= ['week_day_id'=>$d, 'label'=>null, 'sort'=>0, 'intervals'=>[]];
            $byDay[$d]['intervals'][] = [
                'start_time' => $r['start_time'] ?? '',
                'end_time'   => $r['end_time']   ?? '',
                'is_break'   => (int)($r['is_break'] ?? 0),
            ];
        }
        // Stabile Sortierung 1..7
        ksort($byDay);
        return array_values($byDay);
    }


    protected static function replaceDaysOff(int $userId, array $items, string $table): void
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->delete($table, ['user_id'=>$userId], ['%d']);

            foreach ($items as $it) {
                $name = sanitize_text_field($it['name'] ?? '');
                $note = sanitize_text_field($it['note'] ?? '');
                $sd   = self::toDbDate($it['start_date'] ?? '');
                $ed   = self::toDbDate($it['end_date']   ?? '');
                $repY = (int) !!($it['repeat_yearly'] ?? 0);
                if (!$sd) { continue; }
                if (!$ed) { $ed = $sd; }

                $wpdb->insert($table, [
                    'user_id'       => $userId,
                    'name'          => $name !== '' ? $name : NULL,
                    'note'          => $note !== '' ? $note : NULL,
                    'start_date'    => $sd,
                    'end_date'      => $ed,
                    'repeat_yearly' => $repY,
                    'created_at'    => current_time('mysql'),
                    'updated_at'    => current_time('mysql'),
                ], ['%d','%s','%s','%s','%s','%d','%s','%s']);
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    protected static function replaceSpecialDaySets(int $userId, array $sets, string $setTab, string $intTab): void
    {
        global $wpdb;
        $p        = $wpdb->prefix . 'bookando_';
        $sdSetLoc = $p . 'employees_specialday_set_locations';
        $sdSetSvc = $p . 'employees_specialday_set_services';

        $wpdb->query('START TRANSACTION');
        try {
            $oldSetIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$setTab} WHERE user_id=%d", $userId)) ?: [];
            if ($oldSetIds) {
                $in = implode(',', array_fill(0, count($oldSetIds), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$intTab}   WHERE set_id IN ($in)", ...$oldSetIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$sdSetLoc} WHERE set_id IN ($in)", ...$oldSetIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$sdSetSvc} WHERE set_id IN ($in)", ...$oldSetIds));
                $wpdb->delete($setTab, ['user_id' => $userId], ['%d']);
            }

            foreach ($sets as $idx => $s) {
                $start = Sanitizer::date($s['start_date'] ?? ($s['date_start'] ?? null));
                $end   = Sanitizer::date($s['end_date']   ?? ($s['date_end']   ?? $start));
                if (!$start) continue;

                $label = sanitize_text_field($s['label'] ?? '');
                $sort  = (int)($s['sort'] ?? $idx);

                $row = [
                    'user_id'    => $userId,
                    'start_date' => $start,
                    'end_date'   => $end ?: $start,
                    'label'      => ($label !== '') ? $label : null,
                    'sort'       => $sort,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ];
                $wpdb->insert($setTab, $row, ['%d','%s','%s','%s','%d','%s','%s']);
                $setId = (int)$wpdb->insert_id;
                if ($setId <= 0) continue;

                // === N:N: Arrays (Backcompat: single → array)
                $services  = (array)($s['services']  ?? (isset($s['service_id'])  ? [$s['service_id']]  : []));
                $locations = (array)($s['locations'] ?? (isset($s['location_id']) ? [$s['location_id']] : []));
                $services  = array_values(array_unique(array_filter(array_map('intval', $services),  fn($v)=>$v>0)));
                $locations = array_values(array_unique(array_filter(array_map('intval', $locations), fn($v)=>$v>0)));

                foreach ($services as $sid) {
                    $wpdb->insert($sdSetSvc, ['set_id'=>$setId,'service_id'=>$sid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                }
                foreach ($locations as $lid) {
                    $wpdb->insert($sdSetLoc, ['set_id'=>$setId,'location_id'=>$lid,'created_at'=>current_time('mysql')], ['%d','%d','%s']);
                }

                // === Intervals
                $intervals = is_array(($s['intervals'] ?? null)) ? $s['intervals'] : [];
                foreach ($intervals as $it) {
                    $st = self::toDbTime($it['start_time'] ?? '');
                    $en = self::toDbTime($it['end_time']   ?? '');
                    $br = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                    if (!$st || !$en || $st >= $en) continue;

                    $wpdb->insert($intTab, [
                        'set_id'     => $setId,
                        'start_time' => $st,
                        'end_time'   => $en,
                        'is_break'   => $br,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ], ['%d','%s','%s','%d','%s','%s']);
                }
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    protected static function replaceCalendars(int $userId, array $items, string $connTab, string $calTab): void
    {
        global $wpdb;

        // Normalizer
        $normProvider = function ($p) {
            $p = strtolower(trim((string)$p));
            // Tolerantes Mapping
            if ($p === 'outlook') $p = 'microsoft';
            if ($p === 'apple')   $p = 'icloud';
            $allowed = ['google','microsoft','exchange','icloud','ics'];
            return in_array($p, $allowed, true) ? $p : '';
        };
        $toStr = fn($v) => ($v !== null && $v !== '') ? (string)$v : '';
        $toRO  = fn($v) => (in_array($v, ['ro','rw'], true) ? $v : 'ro');

        // Bestehenden Zustand laden
        $conns = $wpdb->get_results($wpdb->prepare(
            "SELECT id, provider, ics_url FROM {$connTab} WHERE user_id=%d ORDER BY id ASC", $userId
        ), ARRAY_A) ?: [];
        $connById = []; $connKeys = []; // provider+key → id  (key = ics_url bei ics, sonst provider)
        foreach ($conns as $c) {
            $connById[(int)$c['id']] = $c;
            $key = ($c['provider'] === 'ics') ? ('ics|'.$c['ics_url']) : ($c['provider']);
            $connKeys[$key] = (int)$c['id'];
        }

        $cals = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.connection_id, c.calendar_id FROM {$calTab} c
            INNER JOIN {$connTab} x ON x.id = c.connection_id
            WHERE x.user_id=%d", $userId
        ), ARRAY_A) ?: [];
        $calIndex = []; // connection_id|calendar_id → id
        foreach ($cals as $r) {
            $calIndex[(int)$r['connection_id'].'|'.$r['calendar_id']] = (int)$r['id'];
        }

        // Alles, was am Ende nicht "gesehen" wurde, löschen
        $keepConn = []; $keepCal = [];

        $wpdb->query('START TRANSACTION');
        try {
            $wantDefaultWrite = null; // cal-id, die default_write sein soll

            foreach ((array)$items as $it) {
                $provider = $normProvider($it['provider'] ?? ($it['calendar'] ?? ''));
                if (!$provider) continue;

                $name    = sanitize_text_field($toStr($it['name'] ?? null));
                $isBusy  = (int) !!($it['is_busy_source']   ?? 1);
                $isWrite = (int) !!($it['is_default_write'] ?? 0);
                $access  = $toRO($toStr($it['access'] ?? 'ro'));

                // Connection ermitteln/erzeugen
                $connKey = '';
                $connectionId = 0;

                if ($provider === 'ics') {
                    $urlRaw = preg_replace('/^webcal:\/\//i', 'https://', trim((string)($it['url'] ?? '')));
                    $url    = esc_url_raw($urlRaw);
                    if (!$url) continue;
                    $connKey = 'ics|'.$url;

                    if (isset($connKeys[$connKey])) {
                        $connectionId = (int)$connKeys[$connKey];
                        // ggf. aktualisieren (name liegt auf calendar, nicht connection)
                        $wpdb->update($connTab, ['updated_at'=>current_time('mysql')], ['id'=>$connectionId], ['%s'], ['%d']);
                    } else {
                        $wpdb->insert($connTab, [
                            'user_id'    => $userId,
                            'provider'   => 'ics',
                            'scope'      => 'ro',
                            'auth_type'  => 'ics',
                            'ics_url'    => $url,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d','%s','%s','%s','%s','%s','%s']);
                        $connectionId = (int)$wpdb->insert_id;
                        $connKeys[$connKey] = $connectionId;
                    }

                    // ICS: calendar_id = HASH(url)
                    $calendarId = substr(sha1($url), 0, 20);
                    $calKey = $connectionId.'|'.$calendarId;

                    if (isset($calIndex[$calKey])) {
                        $calId = $calIndex[$calKey];
                        $wpdb->update($calTab, [
                            'name'            => ($name !== '' ? $name : 'ICS'),
                            'access'          => 'ro',
                            'is_busy_source'  => $isBusy,
                            'is_default_write'=> $isWrite,
                            'updated_at'      => current_time('mysql'),
                        ], ['id'=>$calId], ['%s','%s','%d','%d','%s'], ['%d']);
                    } else {
                        $wpdb->insert($calTab, [
                            'connection_id'   => $connectionId,
                            'calendar_id'     => $calendarId,
                            'name'            => ($name !== '' ? $name : 'ICS'),
                            'access'          => 'ro',
                            'is_busy_source'  => $isBusy,
                            'is_default_write'=> $isWrite,
                            'time_zone'       => null,
                            'color'           => null,
                            'created_at'      => current_time('mysql'),
                            'updated_at'      => current_time('mysql'),
                        ], ['%d','%s','%s','%s','%d','%d','%s','%s','%s','%s']);
                        $calId = (int)$wpdb->insert_id;
                    }

                    $keepConn[$connectionId] = true;
                    $keepCal[$calId] = true;
                    if ($isWrite) $wantDefaultWrite = $calId;

                } else {
                    // OAuth-Provider: eine Connection pro Provider
                    $connKey = $provider;
                    if (isset($connKeys[$connKey])) {
                        $connectionId = (int)$connKeys[$connKey];
                    } else {
                        $wpdb->insert($connTab, [
                            'user_id'    => $userId,
                            'provider'   => $provider,
                            'scope'      => 'ro',
                            'auth_type'  => 'oauth',
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d','%s','%s','%s','%s','%s']);
                        $connectionId = (int)$wpdb->insert_id;
                        $connKeys[$connKey] = $connectionId;
                    }

                    $calendarId = $toStr($it['calendar_id'] ?? null);
                    if ($calendarId === '') continue;

                    $calKey = $connectionId.'|'.$calendarId;
                    if (isset($calIndex[$calKey])) {
                        $calId = $calIndex[$calKey];
                        $wpdb->update($calTab, [
                            'name'            => ($name !== '' ? $name : strtoupper($provider).'/'.$calendarId),
                            'access'          => $access,
                            'is_busy_source'  => $isBusy,
                            'is_default_write'=> $isWrite,
                            'updated_at'      => current_time('mysql'),
                        ], ['id'=>$calId], ['%s','%s','%d','%d','%s'], ['%d']);
                    } else {
                        $wpdb->insert($calTab, [
                            'connection_id'   => $connectionId,
                            'calendar_id'     => $calendarId,
                            'name'            => ($name !== '' ? $name : strtoupper($provider).'/'.$calendarId),
                            'access'          => $access,
                            'is_busy_source'  => $isBusy,
                            'is_default_write'=> $isWrite,
                            'created_at'      => current_time('mysql'),
                            'updated_at'      => current_time('mysql'),
                        ], ['%d','%s','%s','%s','%d','%d','%s','%s']);
                        $calId = (int)$wpdb->insert_id;
                    }

                    $keepConn[$connectionId] = true;
                    $keepCal[$calId] = true;
                    if ($isWrite) $wantDefaultWrite = $calId;
                }
            }

            // Alle nicht mehr gewünschten Kalender löschen (inkl. Events, falls du die Tabelle hast)
            if (!empty($calIndex)) {
                $allCalIds = array_values($calIndex);
                $toDelete  = array_values(array_diff($allCalIds, array_keys($keepCal)));
                if ($toDelete) {
                    $in = implode(',', array_fill(0, count($toDelete), '%d'));
                    // Falls es calendar_events gibt:
                    // $wpdb->query($wpdb->prepare("DELETE FROM {$p}calendar_events WHERE calendar_id IN ($in)", ...$toDelete));
                    $wpdb->query($wpdb->prepare("DELETE FROM {$calTab} WHERE id IN ($in)", ...$toDelete));
                }
            }

            // Verwaiste Connections löschen
            if (!empty($connById)) {
                $allConnIds = array_map('intval', array_keys($connById));
                $toDelete   = array_values(array_diff($allConnIds, array_keys($keepConn)));
                if ($toDelete) {
                    $in = implode(',', array_fill(0, count($toDelete), '%d'));
                    $wpdb->query($wpdb->prepare("DELETE FROM {$connTab} WHERE id IN ($in)", ...$toDelete));
                }
            }

            // Genau EIN default_write erzwingen
            if ($wantDefaultWrite !== null) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$calTab} c
                    INNER JOIN {$connTab} x ON x.id = c.connection_id
                    SET c.is_default_write = CASE WHEN c.id=%d THEN 1 ELSE 0 END,
                        c.updated_at = %s
                    WHERE x.user_id=%d",
                    $wantDefaultWrite, current_time('mysql'), $userId
                ));
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }


    /** Debug-Logger (nur aktiv, wenn WP_DEBUG true ist) */
    protected static function dbg(string $msg): void {
        if (defined('WP_DEBUG') && WP_DEBUG) { error_log($msg); }
    }

    protected static function hasColumn(string $table, string $col): bool {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $col));
        return !empty($exists);
    }

    /* =========================================================
     * Helpers: sanitize / commons
     * ========================================================= */

    protected static function toDbTime(string $v): string {
        $v = trim((string)$v);
        if ($v === '') return '';
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $v)) return $v;
        if (preg_match('/^\d{2}:\d{2}$/', $v))     return $v . ':00';
        return '';
    }

    protected static function toDbDate(string $v): string {
        $v = trim((string)$v);
        if ($v === '') return '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $v, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
        return '';
    }

    protected static function sanitizeRepeat(string $rep): string {
        $rep = strtolower(trim($rep));
        $allowed = ['none','daily','weekly','monthly','yearly'];
        return in_array($rep, $allowed, true) ? $rep : 'none';
    }

    protected static function sanitizeCalendar(string $cal): string {
        $cal = strtolower(trim($cal));
        $allowed = ['google','microsoft','exchange','icloud','ics'];
        return in_array($cal, $allowed, true) ? $cal : '';
    }

    protected static function isHardDeleted(array $row): bool {
        return (isset($row['status'],$row['deleted_at']) && $row['status']==='deleted' && !empty($row['deleted_at']));
    }

    protected static function canReadRecord(int $id, \WP_REST_Request $request): bool {
        if (Gate::devBypass() || Gate::canManage('employees')) return true;
        // wie bei customers: Self-Read nur mit gültigem Nonce
        return Gate::isSelf($id) && Gate::verifyNonce($request);
    }
    protected static function canWriteRecord(int $id, WP_REST_Request $request): bool {
        if (Gate::devBypass() || Gate::canManage('employees')) return true;
        return Gate::isSelf($id) && Gate::verifyNonce($request);
    }

    /**
     * Null-first Sanitizing + tolerantes Mapping (gender, country).
     */
    protected static function sanitizeEmployeeInput(array $in, bool $isCreate): array
    {
        $out = [];

        // Basis-Strings ('' → NULL)
        $copyNullIfEmpty = function(string $key) use (&$out, $in, $isCreate) {
            if ($isCreate || array_key_exists($key, $in)) {
                $out[$key] = Sanitizer::nullIfEmpty($in[$key] ?? null);
            }
        };
        foreach (['first_name','last_name','address','address_2','zip','city','note','avatar_url','timezone','description'] as $k) {
            $copyNullIfEmpty($k);
        }

        // Email
        if ($isCreate || array_key_exists('email', $in)) {
            $e = sanitize_email($in['email'] ?? '');
            $out['email'] = ($e !== '') ? $e : null;
        }

        // Phone
        if ($isCreate || array_key_exists('phone', $in)) {
            $out['phone'] = Sanitizer::phone($in['phone'] ?? null);
        }

        // Country (ISO-2) + Language
        if ($isCreate || array_key_exists('country', $in)) {
            $out['country'] = self::normalizeCountry($in['country'] ?? null);
        }
        if ($isCreate || array_key_exists('language', $in)) {
            $out['language'] = Sanitizer::language($in['language'] ?? null) ?? 'de';
        }

        // birthdate
        if ($isCreate || array_key_exists('birthdate', $in)) {
            $birth = trim((string)($in['birthdate'] ?? ''));
            if ($birth === '') {
                $out['birthdate'] = null;
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) {
                $out['birthdate'] = $birth;
            } elseif (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $birth, $m)) {
                $out['birthdate'] = "{$m[3]}-{$m[2]}-{$m[1]}";
            } else {
                $out['birthdate'] = null;
            }
        }

        // gender – tolerant mappen
        if ($isCreate || array_key_exists('gender', $in)) {
            $out['gender'] = self::normalizeGender($in['gender'] ?? null);
        }

        // Status
        if ($isCreate || array_key_exists('status', $in)) {
            $out['status'] = self::normalizeStatus($in['status'] ?? 'active');
        }

        // optional tenant_id
        if ($isCreate || array_key_exists('tenant_id', $in)) {
            $out['tenant_id'] = isset($in['tenant_id']) ? (int)$in['tenant_id'] : null;
        }

        // Badge
        if ($isCreate || array_key_exists('badge_id', $in)) {
            $out['badge_id'] = isset($in['badge_id']) && $in['badge_id'] !== '' ? (int)$in['badge_id'] : null;
        }

        // Employee-Area-Password (write-only) → Hash
        if ($isCreate || array_key_exists('employee_area_password', $in)) {
            $pwd = (string)($in['employee_area_password'] ?? '');
            // leer lassen ⇒ kein Update; beim Create optional
            if ($pwd !== '') {
                $out['password_hash'] = wp_hash_password($pwd);
            } elseif ($isCreate) {
                $out['password_hash'] = null;
            }
        }

        // Collections (roh übernehmen, wenn vorhanden; Full-Replace im Controller)
        if (array_key_exists('workday_sets', $in)) {
            $out['workday_sets'] = is_array($in['workday_sets']) ? $in['workday_sets'] : [];
        }

        if (array_key_exists('days_off', $in)) {
            $out['days_off'] = is_array($in['days_off']) ? $in['days_off'] : [];
        }

        if (array_key_exists('special_day_sets', $in)) {
            $out['special_day_sets'] = is_array($in['special_day_sets']) ? $in['special_day_sets'] : [];
        }

        if (array_key_exists('calendars', $in)) {
            $out['calendars'] = is_array($in['calendars']) ? $in['calendars'] : [];
        }

        return $out;
    }

    protected static function normalizeStatus(string $s): string {
        $s = strtolower(trim($s));
        if (in_array($s, ['active','blocked','deleted'], true)) return $s;
        if (in_array($s, ['inactive','deactivated'], true)) return 'blocked';
        return 'active';
    }

    /**
     * Gender tolerantes Mapping (wie bei customers).
     */
    protected static function normalizeGender($g): ?string
    {
        $g = strtolower(trim((string)$g));
        if ($g === '') return null;

        $map = [
            // englische UI-Keys
            'male'   => 'm',
            'female' => 'f',
            'other'  => 'd',
            'none'   => 'n',
            // db-codes direkt
            'm' => 'm', 'f' => 'f', 'd' => 'd', 'n' => 'n',
            // einfache dt. Bezeichnungen
            'männlich' => 'm',
            'weiblich' => 'f',
            'divers'   => 'd',
            'keine angabe' => 'n',
        ];
        return $map[$g] ?? null;
    }

    /**
     * Country strikt ISO-2 (A–Z); akzeptiert auch Objekt/Array (z. B. {code:'CH'} oder {value:'CH'}).
     */
    protected static function normalizeCountry($c): ?string
    {
        if (is_array($c)) {
            $c = $c['code'] ?? $c['value'] ?? null;
        }
        $c = strtoupper(trim((string)$c));
        if ($c === '') return null;
        return preg_match('/^[A-Z]{2}$/', $c) ? $c : null;
    }

    protected static function softDeleteRecord(string $table, int $id, ?int $tenantId): void
    {
        global $wpdb;
        $where = ['id'=>$id]; $wf=['%d'];
        if ($tenantId) { $where['tenant_id']=$tenantId; $wf[]='%d'; }
        $wpdb->update($table, [
            'status'=>'deleted',
            'deleted_at'=>null,
            'updated_at'=>current_time('mysql'),
        ], $where, ['%s','%s','%s'], $wf);
    }

    protected static function hardDeleteRecord(string $table, int $id, ?int $tenantId): void
    {
        global $wpdb;
        $anonEmail = 'deleted+' . $id . '@invalid.local';
        $upd = [
            'status' => 'deleted',
            'deleted_at'=>current_time('mysql'),
            'updated_at'=>current_time('mysql'),

            'first_name'=>null,'last_name'=>null,'email'=>$anonEmail,'phone'=>null,'address'=>null,'address_2'=>null,
            'zip'=>null,'city'=>null,'country'=>null,'birthdate'=>null,'gender'=>null,'language'=>null,'note'=>null,
            'description'=>null,'avatar_url'=>null,'timezone'=>null,'external_id'=>null,'badge_id'=>null,
            'password_hash'=>null,'password_reset_token'=>null,'roles'=>wp_json_encode([]),
        ];
        $where=['id'=>$id]; $wf=['%d'];
        if ($tenantId){ $where['tenant_id']=$tenantId; $wf[]='%d'; }
        $fmt = array_fill(0, count($upd), '%s');
        $wpdb->update($table, $upd, $where, $fmt, $wf);
    }

    /* =========================================================
     * Helpers: Required-Checks (FormRules)
     * ========================================================= */

    protected static function fieldRequiredByWhen(array $cfg, string $status): bool {
        if (empty($cfg['required'])) return false;
        $when = $cfg['when'] ?? [];
        $ok = true;
        if (!empty($when['status_is'])) {
            $ok = in_array($status, (array)$when['status_is'], true);
        }
        if (!empty($when['status_not'])) {
            if (in_array($status, (array)$when['status_not'], true)) $ok = false;
        }
        return $ok;
    }

    /** Validiert Pflichtfelder + Gruppen (z. B. at_least_one_of) für Ziel-Status. */
    protected static function validateByRules(array $data, array $rules, string $targetStatus): array {
        $missing = [];

        foreach (($rules['fields'] ?? []) as $field => $cfg) {
            if (self::fieldRequiredByWhen($cfg, $targetStatus)) {
                $v = $data[$field] ?? null;
                if ($v === null || $v === '') $missing[] = $field;
            }
        }

        foreach (($rules['groups']['at_least_one_of'] ?? []) as $group) {
            $any = false;
            foreach ((array)$group as $f) {
                $v = $data[$f] ?? null;
                if ($v !== null && $v !== '') { $any = true; break; }
            }
            if (!$any) {
                $missing[] = 'at_least_one_of:' . implode('|', (array)$group);
            }
        }

        return $missing;
    }

    /**
     * POST/DELETE /employees/{id}/calendar/connections/ics
     *  - POST:  Body { url, name? }
     *  - DELETE: Body { url } oder ?connection_id=...
     *
     * Speichert ICS-Quelle in wp_bookando_employees_calendar:
     *   calendar='apple', calendar_id=<hash>, token=JSON {"type":"ics","url":"...","name":"..."}
     */
    public static function calendarIcs($params, WP_REST_Request $request)
    {
        if (!self::canWriteRecord((int)($params['id'] ?? 0), $request)) {
            return new WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
        }

        global $wpdb;
        $p           = $wpdb->prefix . 'bookando_';
        $calConnTab  = $p . 'calendar_connections';
        $calsTab     = $p . 'calendars';
        $eventsTab   = $p . 'calendar_events';

        $userId = (int)($params['id'] ?? 0);
        if ($userId <= 0) return new WP_Error('bad_request',__('Benutzer-ID fehlt.', 'bookando'),['status'=>400]);

        $method = strtoupper($request->get_method());

        if ($method === 'POST') {
            $body = (array)$request->get_json_params();

            // webcal:// → https://
            $urlRaw = trim((string)($body['url'] ?? ''));
            if ($urlRaw === '') return new WP_Error('bad_request',__('URL fehlt.', 'bookando'),['status'=>400]);
            $urlRaw = preg_replace('/^webcal:\/\//i', 'https://', $urlRaw);
            $url    = esc_url_raw($urlRaw);
            if (!$url) return new WP_Error('bad_request',__('Ungültige URL.', 'bookando'),['status'=>400]);

            $name = sanitize_text_field((string)($body['name'] ?? 'ICS'));
            $hash = substr(sha1($url), 0, 20);
            $now  = current_time('mysql');

            $wpdb->query('START TRANSACTION');
            try {
                // Connection (ics, ro) upserten
                $connId = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$calConnTab}
                    WHERE user_id=%d AND provider='ics' AND ics_url=%s
                    LIMIT 1",
                    $userId, $url
                ));
                if ($connId <= 0) {
                    $wpdb->insert($calConnTab, [
                        'user_id'    => $userId,
                        'provider'   => 'ics',
                        'scope'      => 'ro',
                        'auth_type'  => 'ics',
                        'ics_url'    => $url,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], ['%d','%s','%s','%s','%s','%s','%s']);
                    if ($wpdb->last_error) { throw new \RuntimeException($wpdb->last_error); }
                    $connId = (int)$wpdb->insert_id;
                }

                // Calendar unter dieser Connection upserten
                $calId = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$calsTab}
                    WHERE connection_id=%d AND calendar_id=%s
                    LIMIT 1",
                    $connId, $hash
                ));

                $created = false;
                if ($calId > 0) {
                    $wpdb->update($calsTab, [
                        'name'            => ($name !== '' ? $name : 'ICS'),
                        'is_busy_source'  => 1,
                        'is_default_write'=> 0,
                        'updated_at'      => $now,
                    ], ['id'=>$calId], ['%s','%d','%d','%s'], ['%d']);
                    if ($wpdb->last_error) { throw new \RuntimeException($wpdb->last_error); }
                } else {
                    $wpdb->insert($calsTab, [
                        'connection_id'   => $connId,
                        'calendar_id'     => $hash,
                        'name'            => ($name !== '' ? $name : 'ICS'),
                        'access'          => 'ro',
                        'is_busy_source'  => 1,
                        'is_default_write'=> 0,
                        'time_zone'       => null,
                        'color'           => null,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ], ['%d','%s','%s','%s','%d','%d','%s','%s','%s','%s']);
                    if ($wpdb->last_error) { throw new \RuntimeException($wpdb->last_error); }
                    $calId   = (int)$wpdb->insert_id;
                    $created = true;
                }

                $wpdb->query('COMMIT');
                return rest_ensure_response([
                    'id'               => $calId,
                    'connection_id'    => $connId,
                    'provider'         => 'ics',
                    'calendar_id'      => $hash,
                    'name'             => $name !== '' ? $name : 'ICS',
                    'is_busy_source'   => 1,
                    'is_default_write' => 0,
                    $created ? 'created' : 'updated' => true,
                ]);
            } catch (\Throwable $e) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('db_error', $e->getMessage(), ['status'=>500]);
            }
        }

        if ($method === 'DELETE') {
            $body = (array)$request->get_json_params();

            $connectionId = (int)($params['connection_id'] ?? 0);
            if (!$connectionId) { $connectionId = (int)($body['connection_id'] ?? 0); }

            $hashFromUrl = null;
            if (!$connectionId && !empty($body['url'])) {
                $urlRaw = preg_replace('/^webcal:\/\//i', 'https://', trim((string)$body['url']));
                $url    = esc_url_raw($urlRaw);
                if ($url) { $hashFromUrl = substr(sha1($url), 0, 20); }
            }

            if ($connectionId <= 0 && $hashFromUrl) {
                $connectionId = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT conn.id
                    FROM {$calConnTab} conn
                    INNER JOIN {$calsTab} c ON c.connection_id = conn.id
                    WHERE conn.user_id=%d AND conn.provider='ics' AND c.calendar_id=%s
                    LIMIT 1",
                    $userId, $hashFromUrl
                ));
            }

            if ($connectionId <= 0) {
                return new WP_Error('bad_request',__('connection_id oder URL fehlt.', 'bookando'),['status'=>400]);
            }

            $now = current_time('mysql');

            $wpdb->query('START TRANSACTION');
            try {
                // alle Calendars zu dieser Connection (inkl. Events) löschen
                $calIds = $wpdb->get_col($wpdb->prepare(
                    "SELECT id FROM {$calsTab} WHERE connection_id=%d",
                    $connectionId
                )) ?: [];

                if ($calIds) {
                    $in = implode(',', array_fill(0, count($calIds), '%d'));
                    $wpdb->query($wpdb->prepare("DELETE FROM {$eventsTab} WHERE calendar_id IN ($in)", ...$calIds));
                    if ($wpdb->last_error) { throw new \RuntimeException($wpdb->last_error); }
                    $wpdb->query($wpdb->prepare("DELETE FROM {$calsTab} WHERE id IN ($in)", ...$calIds));
                    if ($wpdb->last_error) { throw new \RuntimeException($wpdb->last_error); }
                }

                $wpdb->delete($calConnTab, ['id'=>$connectionId, 'user_id'=>$userId], ['%d','%d']);
                if ($wpdb->last_error) { throw new \RuntimeException($wpdb->last_error); }

                $wpdb->query('COMMIT');
                return rest_ensure_response(['deleted'=>true, 'deleted_at'=>$now]);
            } catch (\Throwable $e) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('db_error', $e->getMessage(), ['status'=>500]);
            }
        }

        return new WP_Error('method_not_allowed',__('Methode nicht unterstützt.', 'bookando'),['status'=>405]);
    }

    /**
     * GET /employees/{id}/calendars
     * PATCH/DELETE /employees/{id}/calendars/{calId}
     *
     * Flags speichern wir im vorhandenen 'token'-JSON, solange die alte Tabelle genutzt wird.
     * Schema: {"type":"ics"|"oauth","url"?:string,"name"?:string,"is_busy_source"?:1|0,"is_default_write"?:1|0}
     */
    public static function calendars($params, \WP_REST_Request $request)
    {
        global $wpdb;
        $p          = $wpdb->prefix . 'bookando_';
        $connTab    = $p . 'calendar_connections';
        $calTab     = $p . 'calendars';

        $userId = (int)($params['id'] ?? 0);
        if ($userId <= 0) return new \WP_Error('bad_request',__('Benutzer-ID fehlt.', 'bookando'),['status'=>400]);

        $method = strtoupper($request->get_method());

        if ($method === 'GET') {
            if (!self::canReadRecord($userId, $request)) {
                return new \WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
            }
            $rows = $wpdb->get_results($wpdb->prepare(
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
                FROM {$calTab} c
                INNER JOIN {$connTab} conn ON conn.id = c.connection_id
                WHERE conn.user_id = %d
                ORDER BY c.id ASC",
                $userId
            ), ARRAY_A) ?: [];
            return rest_ensure_response(['calendars' => $rows]);
        }

        if ($method === 'PUT') {
            if (!self::canWriteRecord($userId, $request)) {
                return new \WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
            }
            $body  = (array)$request->get_json_params();
            $items = is_array($body['calendars'] ?? null) ? $body['calendars'] : [];
            self::replaceCalendars($userId, $items, $connTab, $calTab);
            if ($wpdb->last_error) return new \WP_Error('db_error',$wpdb->last_error,['status'=>500]);
            return rest_ensure_response(['updated' => true]);
        }

        if ($method === 'PATCH' && isset($params['calId'])) {
            if (!self::canWriteRecord($userId, $request)) {
                return new \WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
            }
            $calId = (int)$params['calId'];
            $body  = (array)$request->get_json_params();
            $isBusy  = isset($body['is_busy_source'])   ? (int)!!$body['is_busy_source']   : null;
            $isWrite = isset($body['is_default_write']) ? (int)!!$body['is_default_write'] : null;

            // Ownership sichern
            $own = $wpdb->get_var($wpdb->prepare(
                "SELECT c.id
                FROM {$calTab} c
                INNER JOIN {$connTab} x ON x.id=c.connection_id
                WHERE c.id=%d AND x.user_id=%d",
                $calId, $userId
            ));
            if (!$own) return new \WP_Error('not_found',__('Nicht gefunden.', 'bookando'),['status'=>404]);

            $upd = ['updated_at'=>current_time('mysql')]; $fmt=['%s'];
            if ($isBusy !== null)  { $upd['is_busy_source']   = $isBusy;  $fmt[]='%d'; }
            if ($isWrite !== null) { $upd['is_default_write'] = $isWrite; $fmt[]='%d'; }
            $wpdb->update($calTab, $upd, ['id'=>$calId], $fmt, ['%d']);

            if ($isWrite === 1) {
                // genau eine Default-Write pro User
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$calTab} c
                    INNER JOIN {$connTab} x ON x.id=c.connection_id
                    SET c.is_default_write = CASE WHEN c.id=%d THEN 1 ELSE 0 END,
                        c.updated_at = %s
                    WHERE x.user_id=%d",
                    $calId, current_time('mysql'), $userId
                ));
            }
            if ($wpdb->last_error) return new \WP_Error('db_error',$wpdb->last_error,['status'=>500]);
            return rest_ensure_response(['updated'=>true]);
        }

        if ($method === 'DELETE' && isset($params['calId'])) {
            if (!self::canWriteRecord($userId, $request)) {
                return new \WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
            }
            $calId = (int)$params['calId'];

            // Events -> Calendar löschen (falls Events-Tabelle existiert, sonst nur Calendar)
            $wpdb->query($wpdb->prepare(
                "DELETE c FROM {$calTab} c
                INNER JOIN {$connTab} x ON x.id=c.connection_id
                WHERE c.id=%d AND x.user_id=%d",
                $calId, $userId
            ));

            if ($wpdb->last_error) return new \WP_Error('db_error',$wpdb->last_error,['status'=>500]);
            return rest_ensure_response(['deleted'=>true]);
        }

        return new \WP_Error('method_not_allowed',__('Methode nicht unterstützt.', 'bookando'),['status'=>405]);
    }

    /**
     * POST /employees/{id}/calendar/invite
     * Body:
     * {
     *   "to": ["mail@example.org", ...],
     *   "subject": "Einladung ...",
     *   "body": "Text (optional)",
     *   "event": {
     *      "uid": "abc-123",
     *      "summary": "Fahrstunde",
     *      "description": "…",
     *      "location": "Bern",
     *      "start": "2025-02-01T10:00:00+01:00",
     *      "end":   "2025-02-01T11:00:00+01:00",
     *      "organizer_email": "you@domain.tld",
     *      "attendees": ["a@b.ch","c@d.ch"]
     *   }
     * }
     */
    public static function calendarInvite($params, WP_REST_Request $request)
    {
        if (!self::canWriteRecord((int)($params['id'] ?? 0), $request)) {
            return new WP_Error('forbidden',__('Keine Berechtigung.', 'bookando'),['status'=>403]);
        }

        $data = (array)$request->get_json_params();
        $to   = array_values(array_filter((array)($data['to'] ?? []), 'is_email'));
        if (empty($to)) return new WP_Error('bad_request',__('Empfänger fehlen.', 'bookando'),['status'=>400]);

        $subject = sanitize_text_field((string)($data['subject'] ?? 'Einladung'));
        $bodyTxt = (string)($data['body'] ?? '');
        $ev      = (array)($data['event'] ?? []);

        if (!class_exists(\Bookando\Core\Util\Ics::class)) {
            return new WP_Error('server_error',__('ICS-Helfer fehlt.', 'bookando'),['status'=>500]);
        }
        $icsStr = \Bookando\Core\Util\Ics::buildEvent([
            'uid'        => $ev['uid'] ?? '',
            'summary'    => $ev['summary'] ?? '',
            'description'=> $ev['description'] ?? '',
            'location'   => $ev['location'] ?? '',
            'start'      => $ev['start'] ?? '',
            'end'        => $ev['end'] ?? '',
            'organizer'  => $ev['organizer_email'] ?? get_bloginfo('admin_email'),
            'attendees'  => array_values(array_filter((array)($ev['attendees'] ?? []), 'is_email')),
        ]);

        $sent = \Bookando\Core\Util\Ics::sendInvite($to, $subject, $bodyTxt, $icsStr);
        if (!$sent) return new WP_Error('send_failed',__('E-Mail-Versand fehlgeschlagen.', 'bookando'),['status'=>500]);

        return rest_ensure_response(['ok'=>true]);
    }

    public static function guardPermissions(WP_REST_Request $request): bool|WP_Error
    {
        if (Gate::devBypass() || Gate::canManage('employees')) {
            return true;
        }

        if (strtoupper($request->get_method()) !== 'GET') {
            return self::forbiddenManageError();
        }

        $employeeId = self::resolveEmployeeIdFromRequest($request);
        if ($employeeId <= 0) {
            return self::forbiddenManageError();
        }

        return true;
    }

    private static function resolveEmployeeIdFromRequest(WP_REST_Request $request): int
    {
        $id = $request->get_param('id');
        if (is_numeric($id)) {
            return (int) $id;
        }

        $subkey = $request->get_param('subkey');
        if (is_numeric($subkey)) {
            return (int) $subkey;
        }

        $route = (string) $request->get_route();
        if (preg_match('#/employees/(?:employees/)?(?P<id>\d+)#', $route, $matches)) {
            return (int) $matches['id'];
        }

        return 0;
    }

    private static function forbiddenManageError(): WP_Error
    {
        return new WP_Error(
            'rest_forbidden',
            __('Zusätzliche Berechtigung manage_bookando_employees erforderlich.', 'bookando'),
            ['status' => 403]
        );
    }
}
