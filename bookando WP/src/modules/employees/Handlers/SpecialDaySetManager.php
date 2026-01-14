<?php

declare(strict_types=1);

namespace Bookando\Modules\employees\Handlers;

use WP_REST_Request;
use WP_Error;
use Bookando\Core\Util\Sanitizer;
use function __;
use function rest_ensure_response;
use function sanitize_key;
use function sanitize_text_field;
use function current_time;

/**
 * Special Day Set Collection Manager.
 *
 * Verwaltet die komplette Special-Day-Set-Lifecycle:
 * - GET: Lesen von Sets + Intervals + N:N-Mappings (Services/Locations)
 * - POST: Full-Replace oder Merge-Mode (Upsert + gezielte Löschungen)
 * - Legacy: specialDays() Endpunkt (410 Gone)
 *
 * Datenstruktur:
 * - special_day_sets (1:N mit intervals, N:N mit services/locations)
 * - Ein Set = Datumsbereich (start_date, end_date) + Label + Sort + Intervals
 * - Intervals = Zeitfenster (start_time, end_time, is_break)
 * - Verwendung: Ausnahmen/zeitliche Abweichungen (Feiertage, Sondertage, etc.)
 */
class SpecialDaySetManager
{
    /**
     * GET /employees/{id}/special-day-sets → Lädt alle Sets + Intervals + Mappings
     * POST /employees/{id}/special-day-sets → Replace oder Merge (je nach Body)
     *
     * Merge-Modus wird aktiviert wenn:
     * - mode=merge explizit gesetzt
     * - Body enthält 'upsert' oder 'delete_ids'
     * - Items in special_day_sets haben 'id'-Feld
     *
     * @param array|WP_REST_Request $params Route-Parameter (id) oder Request
     * @param WP_REST_Request|null $request REST-Request (oder null wenn $params = Request)
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function specialDaySets($params, WP_REST_Request $request = null)
    {
        // Falls WordPress nur den Request übergibt:
        if ($params instanceof WP_REST_Request && $request === null) {
            $request = $params;
            $params = [];
        }

        global $wpdb;
        $tables = EmployeeRepository::employeeTables();
        $sdSet = $tables['sdSetTab'];
        $sdInt = $tables['sdIntTab'];
        $sdSetLoc = $tables['sdSetLoc'];
        $sdSetSvc = $tables['sdSetSvc'];

        $method = strtoupper($request->get_method());

        // Benutzer-ID robust ermitteln
        $userId = 0;
        if (isset($params['id'])) {
            $userId = (int)$params['id'];
        } elseif (isset($params['subkey'])) {
            $userId = (int)$params['subkey'];
        } elseif ($request->get_param('id') !== null) {
            $userId = (int)$request->get_param('id');
        }

        if ($userId <= 0) {
            return new WP_Error('bad_request', __('Benutzer-ID fehlt.', 'bookando'), ['status' => 400]);
        }

        if ($method === 'GET') {
            if (!EmployeeAuthorizationGuard::canReadRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
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
                $in = implode(',', array_fill(0, count($ids), '%d'));

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
                    "SELECT set_id, service_id FROM {$sdSetSvc} WHERE set_id IN ($in)",
                    ...$ids
                ), ARRAY_A) ?: [];
                $locRows = $wpdb->get_results($wpdb->prepare(
                    "SELECT set_id, location_id FROM {$sdSetLoc} WHERE set_id IN ($in)",
                    ...$ids
                ), ARRAY_A) ?: [];

                $intMap = [];
                foreach ($ints as $r) {
                    $intMap[(int)$r['set_id']][] = [
                        'id' => (int)$r['id'],
                        'set_id' => (int)$r['set_id'],
                        'start_time' => (string)$r['start_time'],
                        'end_time' => (string)$r['end_time'],
                        'is_break' => (int)$r['is_break'],
                    ];
                }
                $svcMap = [];
                foreach ($svcRows as $r) {
                    $svcMap[(int)$r['set_id']][] = (int)$r['service_id'];
                }
                $locMap = [];
                foreach ($locRows as $r) {
                    $locMap[(int)$r['set_id']][] = (int)$r['location_id'];
                }

                foreach ($sets as &$s) {
                    $sid = (int)$s['id'];
                    $s['intervals'] = $intMap[$sid] ?? [];
                    $s['services'] = $svcMap[$sid] ?? [];
                    $s['locations'] = $locMap[$sid] ?? [];
                }
                unset($s);
            }

            return rest_ensure_response(['special_day_sets' => $sets]);
        }

        if ($method === 'POST') {
            if (!EmployeeAuthorizationGuard::canWriteRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
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
                        if (!empty($s['id'])) {
                            $hasIds = true;
                            break;
                        }
                    }
                    if ($hasIds) {
                        $data['upsert'] = $data['special_day_sets'];
                        unset($data['special_day_sets']);
                        $mode = 'merge';
                    }
                }
            }

            if ($mode === 'merge') {
                $upsert = is_array($data['upsert'] ?? null) ? $data['upsert'] : [];
                $deleteIds = array_values(array_filter(array_map('intval', (array)($data['delete_ids'] ?? [])), fn($v) => $v > 0));
                self::mergeSpecialDaySets($userId, $upsert, $deleteIds, $sdSet, $sdInt, $sdSetLoc, $sdSetSvc);
                if ($wpdb->last_error) {
                    return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
                }
                return rest_ensure_response(['updated' => true, 'mode' => 'merge']);
            }

            // Full-Replace
            $sets = is_array($data['special_day_sets'] ?? null) ? $data['special_day_sets'] : [];
            self::replaceSpecialDaySets($userId, $sets, $sdSet, $sdInt);
            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }
            return rest_ensure_response(['updated' => true, 'mode' => 'replace']);
        }

        return new WP_Error('method_not_allowed', __('Methode nicht unterstützt.', 'bookando'), ['status' => 405]);
    }

    /**
     * Legacy-Endpunkt: /employees/{id}/special-days
     *
     * @deprecated Seit 1.0.0 - Verwende stattdessen /employees/{id}/special-day-sets
     * @param array $params Route-Parameter
     * @param WP_REST_Request $request REST-Request
     * @return WP_Error 410 Gone Error
     */
    public static function specialDays(array $params, WP_REST_Request $request): WP_Error
    {
        return new WP_Error('gone', __('Dieser Endpunkt ist veraltet. Bitte /employees/{id}/special-day-sets verwenden.', 'bookando'), ['status' => 410]);
    }

    /**
     * Full-Replace: Löscht alle alten Sets + Intervals + Mappings und erstellt neue.
     *
     * Transaktional: Bei Fehler wird alles zurückgerollt.
     *
     * @param int $userId Employee ID
     * @param array $sets Array von Special Day Sets
     * @param string $setTab Tabelle für Sets
     * @param string $intTab Tabelle für Intervals
     * @throws \Throwable Bei Datenbankfehlern
     */
    protected static function replaceSpecialDaySets(int $userId, array $sets, string $setTab, string $intTab): void
    {
        global $wpdb;
        $tables = EmployeeRepository::employeeTables();
        $sdSetLoc = $tables['sdSetLoc'];
        $sdSetSvc = $tables['sdSetSvc'];

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
                $end = Sanitizer::date($s['end_date'] ?? ($s['date_end'] ?? $start));
                if (!$start) {
                    continue;
                }

                $label = sanitize_text_field($s['label'] ?? '');
                $sort = (int)($s['sort'] ?? $idx);

                $row = [
                    'user_id' => $userId,
                    'start_date' => $start,
                    'end_date' => $end ?: $start,
                    'label' => ($label !== '') ? $label : null,
                    'sort' => $sort,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ];
                $wpdb->insert($setTab, $row, ['%d', '%s', '%s', '%s', '%d', '%s', '%s']);
                $setId = (int)$wpdb->insert_id;
                if ($setId <= 0) {
                    continue;
                }

                // === N:N: Arrays (Backcompat: single → array)
                $services = (array)($s['services'] ?? (isset($s['service_id']) ? [$s['service_id']] : []));
                $locations = (array)($s['locations'] ?? (isset($s['location_id']) ? [$s['location_id']] : []));
                $services = array_values(array_unique(array_filter(array_map('intval', $services), fn($v) => $v > 0)));
                $locations = array_values(array_unique(array_filter(array_map('intval', $locations), fn($v) => $v > 0)));

                foreach ($services as $sid) {
                    $wpdb->insert($sdSetSvc, ['set_id' => $setId, 'service_id' => $sid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                }
                foreach ($locations as $lid) {
                    $wpdb->insert($sdSetLoc, ['set_id' => $setId, 'location_id' => $lid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                }

                // === Intervals
                $intervals = is_array(($s['intervals'] ?? null)) ? $s['intervals'] : [];
                foreach ($intervals as $it) {
                    $st = EmployeeDataTransformer::toDbTime($it['start_time'] ?? '');
                    $en = EmployeeDataTransformer::toDbTime($it['end_time'] ?? '');
                    $br = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                    if (!$st || !$en || $st >= $en) {
                        continue;
                    }

                    $wpdb->insert($intTab, [
                        'set_id' => $setId,
                        'start_time' => $st,
                        'end_time' => $en,
                        'is_break' => $br,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ], ['%d', '%s', '%s', '%d', '%s', '%s']);
                }
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            EmployeeRepository::dbg('replaceSpecialDaySets failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Merge-Mode: Upsert Sets (mit ID → UPDATE, ohne → INSERT) + gezielte Löschungen.
     *
     * Differenzielle Updates für:
     * - Set-Metadaten (start_date, end_date, label, sort)
     * - N:N Services/Locations (Diff berechnen, nur Änderungen)
     * - Intervals (ID → Upsert, ohne ID → INSERT, fehlende → DELETE)
     *
     * Transaktional: Bei Fehler wird alles zurückgerollt.
     *
     * @param int $userId Employee ID
     * @param array $sets Array von Special Day Sets (mit/ohne 'id')
     * @param array $deleteIds Array von Set-IDs zum Löschen
     * @param string $setTab Tabelle für Sets
     * @param string $intTab Tabelle für Intervals
     * @param string $setLocTab Tabelle für Location-Mappings
     * @param string $setSvcTab Tabelle für Service-Mappings
     * @throws \Throwable Bei Datenbankfehlern
     */
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
                $in = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$intTab}    WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$setLocTab} WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$setSvcTab} WHERE set_id IN ($in)", ...$ids));
                $wpdb->query($wpdb->prepare("DELETE FROM {$setTab}    WHERE id IN ($in) AND user_id=%d", ...array_merge($ids, [$userId])));
            }

            // 1) vorhandene Sets (Ownership prüfen)
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$setTab} WHERE user_id=%d", $userId), ARRAY_A) ?: [];
            $existing = array_map('intval', array_column($rows, 'id'));
            $exists = array_fill_keys($existing, true);

            $arr = function ($v) {
                $a = is_array($v) ? $v : [];
                $a = array_values(array_unique(array_map('intval', $a)));
                return array_filter($a, fn($x) => $x > 0);
            };

            foreach ($sets as $idx => $s) {
                $setId = isset($s['id']) ? (int)$s['id'] : 0;

                $start = Sanitizer::date($s['start_date'] ?? ($s['date_start'] ?? null));
                $end = Sanitizer::date($s['end_date'] ?? ($s['date_end'] ?? $start));
                if (!$start) {
                    continue;
                }

                $label = sanitize_text_field($s['label'] ?? '');
                $sort = (int)($s['sort'] ?? $idx);

                $services = $arr($s['services'] ?? (isset($s['service_id']) ? [$s['service_id']] : []));
                $locations = $arr($s['locations'] ?? (isset($s['location_id']) ? [$s['location_id']] : []));

                if ($setId > 0 && isset($exists[$setId])) {
                    // UPDATE Set (created_at bleibt)
                    $wpdb->update($setTab, [
                        'start_date' => $start,
                        'end_date' => $end ?: $start,
                        'label' => ($label !== '') ? $label : null,
                        'sort' => $sort,
                        'updated_at' => current_time('mysql'),
                    ], ['id' => $setId, 'user_id' => $userId], ['%s', '%s', '%s', '%d', '%s'], ['%d', '%d']);

                    // Mappings diffen
                    $currSvc = $wpdb->get_col($wpdb->prepare("SELECT service_id FROM {$setSvcTab} WHERE set_id=%d", $setId)) ?: [];
                    $currLoc = $wpdb->get_col($wpdb->prepare("SELECT location_id FROM {$setLocTab} WHERE set_id=%d", $setId)) ?: [];
                    $currSvc = array_map('intval', $currSvc);
                    $currLoc = array_map('intval', $currLoc);

                    $toAddSvc = array_values(array_diff($services, $currSvc));
                    $toDelSvc = array_values(array_diff($currSvc, $services));
                    $toAddLoc = array_values(array_diff($locations, $currLoc));
                    $toDelLoc = array_values(array_diff($currLoc, $locations));

                    if ($toDelSvc) {
                        $in = implode(',', array_fill(0, count($toDelSvc), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$setSvcTab} WHERE set_id=%d AND service_id IN ($in)", $setId, ...$toDelSvc));
                    }
                    foreach ($toAddSvc as $sid) {
                        $wpdb->insert($setSvcTab, ['set_id' => $setId, 'service_id' => $sid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                    }

                    if ($toDelLoc) {
                        $in = implode(',', array_fill(0, count($toDelLoc), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$setLocTab} WHERE set_id=%d AND location_id IN ($in)", $setId, ...$toDelLoc));
                    }
                    foreach ($toAddLoc as $lid) {
                        $wpdb->insert($setLocTab, ['set_id' => $setId, 'location_id' => $lid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                    }

                    // Intervals upserten (ID → UPDATE, sonst INSERT)
                    $incoming = is_array($s['intervals'] ?? null) ? $s['intervals'] : [];
                    $currRows = $wpdb->get_results($wpdb->prepare(
                        "SELECT id FROM {$intTab} WHERE set_id=%d",
                        $setId
                    ), ARRAY_A) ?: [];
                    $currMap = [];
                    foreach ($currRows as $r) {
                        $currMap[(int)$r['id']] = true;
                    }

                    $seenIds = [];
                    foreach ($incoming as $it) {
                        $iid = isset($it['id']) ? (int)$it['id'] : 0;
                        $st = EmployeeDataTransformer::toDbTime($it['start_time'] ?? '');
                        $en = EmployeeDataTransformer::toDbTime($it['end_time'] ?? '');
                        $br = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                        if (!$st || !$en || $st >= $en) {
                            continue;
                        }

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
                                $iid,
                                $setId,
                                $st,
                                $en,
                                $br,
                                current_time('mysql'),
                                current_time('mysql')
                            ));
                            $seenIds[] = $iid;
                        } else {
                            // Neue Zeile ohne ID → reines INSERT
                            $wpdb->insert($intTab, [
                                'set_id' => $setId,
                                'start_time' => $st,
                                'end_time' => $en,
                                'is_break' => $br,
                                'created_at' => current_time('mysql'),
                                'updated_at' => current_time('mysql'),
                            ], ['%d', '%s', '%s', '%d', '%s', '%s']);
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
                        'user_id' => $userId,
                        'start_date' => $start,
                        'end_date' => $end ?: $start,
                        'label' => ($label !== '') ? $label : null,
                        'sort' => $sort,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ], ['%d', '%s', '%s', '%s', '%d', '%s', '%s']);
                    $newId = (int)$wpdb->insert_id;
                    if ($newId <= 0) {
                        continue;
                    }

                    foreach ($services as $sid) {
                        $wpdb->insert($setSvcTab, ['set_id' => $newId, 'service_id' => $sid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                    }
                    foreach ($locations as $lid) {
                        $wpdb->insert($setLocTab, ['set_id' => $newId, 'location_id' => $lid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                    }
                    $incoming = is_array($s['intervals'] ?? null) ? $s['intervals'] : [];
                    foreach ($incoming as $it) {
                        $st = EmployeeDataTransformer::toDbTime($it['start_time'] ?? '');
                        $en = EmployeeDataTransformer::toDbTime($it['end_time'] ?? '');
                        $br = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                        if (!$st || !$en || $st >= $en) {
                            continue;
                        }
                        $wpdb->insert($intTab, [
                            'set_id' => $newId,
                            'start_time' => $st,
                            'end_time' => $en,
                            'is_break' => $br,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d', '%s', '%s', '%d', '%s', '%s']);
                    }
                }
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            EmployeeRepository::dbg('mergeSpecialDaySets failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
