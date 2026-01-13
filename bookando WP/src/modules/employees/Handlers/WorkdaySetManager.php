<?php

declare(strict_types=1);

namespace Bookando\Modules\employees\Handlers;

use WP_REST_Request;
use WP_Error;
use function __;
use function rest_ensure_response;
use function sanitize_key;
use function sanitize_text_field;
use function current_time;

/**
 * Workday Set Collection Manager.
 *
 * Verwaltet die komplette Workday-Set-Lifecycle:
 * - GET: Lesen von Sets + Intervals + N:N-Mappings (Services/Locations)
 * - POST: Full-Replace oder Merge-Mode (Upsert + gezielte Löschungen)
 * - Legacy: replaceWorkingHours() für Abwärtskompatibilität
 *
 * Datenstruktur:
 * - workday_sets (1:N mit intervals, N:N mit services/locations)
 * - Ein Set = ein Wochentag + Label + Sort + Intervals
 * - Intervals = Zeitfenster (start_time, end_time, is_break)
 */
class WorkdaySetManager
{
    /**
     * GET /employees/{id}/workday-sets → Lädt alle Sets + Intervals + Mappings
     * POST /employees/{id}/workday-sets → Replace oder Merge (je nach Body)
     *
     * Merge-Modus wird aktiviert wenn:
     * - mode=merge explizit gesetzt
     * - Body enthält 'upsert' oder 'delete_ids'
     * - Items in workday_sets haben 'id'-Feld
     *
     * @param array $params Route-Parameter (id)
     * @param WP_REST_Request $request REST-Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function workdaySets(array $params, WP_REST_Request $request)
    {
        global $wpdb;
        $tables = EmployeeRepository::employeeTables();
        $wSetTab = $tables['wSetTab'];
        $wIntTab = $tables['wIntTab'];
        $wSetLoc = $tables['wSetLoc'];
        $wSetSvc = $tables['wSetSvc'];

        $method = strtoupper($request->get_method());
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
                    "SELECT id, week_day_id, label, sort,
                            DATE_FORMAT(created_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS created_at,
                            DATE_FORMAT(updated_at,'%%Y-%%m-%%d %%H:%%i:%%s') AS updated_at
                    FROM {$wSetTab}
                    WHERE user_id=%d
                    ORDER BY week_day_id ASC, sort ASC, id ASC",
                    $userId
                ),
                ARRAY_A
            ) ?: [];

            if ($sets) {
                $ids = array_map('intval', array_column($sets, 'id'));
                $in = implode(',', array_fill(0, count($ids), '%d'));

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
                $intMap = [];
                foreach ($ints as $r) {
                    $intMap[(int)$r['set_id']][] = $r;
                }

                $svcRows = $wpdb->get_results($wpdb->prepare(
                    "SELECT set_id, service_id FROM {$wSetSvc} WHERE set_id IN ($in)",
                    ...$ids
                ), ARRAY_A) ?: [];
                $locRows = $wpdb->get_results($wpdb->prepare(
                    "SELECT set_id, location_id FROM {$wSetLoc} WHERE set_id IN ($in)",
                    ...$ids
                ), ARRAY_A) ?: [];

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
                    // Backcompat-Felder nur lesend (ok):
                    $s['service_id'] = $s['services'][0] ?? null;
                    $s['location_id'] = $s['locations'][0] ?? null;
                }
                unset($s);
            }
            return rest_ensure_response(['workday_sets' => $sets]);
        }

        if ($method === 'POST') {
            if (!EmployeeAuthorizationGuard::canWriteRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
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
                        if (!empty($s['id'])) {
                            $hasIds = true;
                            break;
                        }
                    }
                    if ($hasIds) {
                        $data['upsert'] = $data['workday_sets'];
                        unset($data['workday_sets']);
                        $mode = 'merge';
                    }
                }
            }

            if ($mode === 'merge') {
                $upsert = is_array($data['upsert'] ?? null) ? $data['upsert'] : [];
                $deleteIds = array_values(array_filter(array_map('intval', (array)($data['delete_ids'] ?? [])), fn($v) => $v > 0));
                self::mergeWorkdaySets($userId, $upsert, $deleteIds, $wSetTab, $wIntTab, $wSetLoc, $wSetSvc);
                if ($wpdb->last_error) {
                    return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
                }
                return rest_ensure_response(['updated' => true, 'mode' => 'merge']);
            }

            // Full-Replace (Backcompat innerhalb von workday_sets beibehalten)
            $sets = is_array($data['workday_sets'] ?? null) ? $data['workday_sets'] : [];
            self::replaceWorkdaySets($userId, $sets, $wSetTab, $wIntTab);
            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }
            return rest_ensure_response(['updated' => true, 'mode' => 'replace']);
        }

        return new WP_Error('method_not_allowed', __('Methode nicht unterstützt.', 'bookando'), ['status' => 405]);
    }

    /**
     * Full-Replace: Löscht alle alten Sets + Intervals + Mappings und erstellt neue.
     *
     * Transaktional: Bei Fehler wird alles zurückgerollt.
     *
     * @param int $userId Employee ID
     * @param array $sets Array von Workday-Sets
     * @param string $setTab Tabelle für Sets
     * @param string $intTab Tabelle für Intervals
     * @throws \Throwable Bei Datenbankfehlern
     */
    protected static function replaceWorkdaySets(int $userId, array $sets, string $setTab, string $intTab): void
    {
        global $wpdb;
        $tables = EmployeeRepository::employeeTables();
        $wSetLoc = $tables['wSetLoc'];
        $wSetSvc = $tables['wSetSvc'];

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
                if ($day < 1 || $day > 7) {
                    continue;
                }

                $label = sanitize_text_field($s['label'] ?? '');
                $sort = (int)($s['sort'] ?? $idx);

                $setRow = [
                    'user_id' => $userId,
                    'week_day_id' => $day,
                    'label' => ($label !== '') ? $label : null,
                    'sort' => $sort,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ];
                $wpdb->insert($setTab, $setRow, ['%d', '%d', '%s', '%d', '%s', '%s']);
                $setId = (int)$wpdb->insert_id;
                if ($setId <= 0) {
                    continue;
                }

                // === N:N: Arrays normalisieren (Backcompat: single → array)
                $services = (array)($s['services'] ?? (isset($s['service_id']) ? [$s['service_id']] : []));
                $locations = (array)($s['locations'] ?? (isset($s['location_id']) ? [$s['location_id']] : []));
                $services = array_values(array_unique(array_filter(array_map('intval', $services), fn($v) => $v > 0)));
                $locations = array_values(array_unique(array_filter(array_map('intval', $locations), fn($v) => $v > 0)));

                foreach ($services as $sid) {
                    $wpdb->insert($wSetSvc, ['set_id' => $setId, 'service_id' => $sid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                }
                foreach ($locations as $lid) {
                    $wpdb->insert($wSetLoc, ['set_id' => $setId, 'location_id' => $lid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                }

                // === Intervals wie gehabt
                $intervals = is_array($s['intervals'] ?? null) ? $s['intervals'] : [];
                foreach ($intervals as $it) {
                    $st = EmployeeDataTransformer::toDbTime($it['start_time'] ?? '');
                    $en = EmployeeDataTransformer::toDbTime($it['end_time'] ?? '');
                    $br = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
                    if (!$st || !$en || $st >= $en) {
                        continue;
                    }

                    $wpdb->insert($intTab, [
                        'set_id' => $setId, 'start_time' => $st, 'end_time' => $en, 'is_break' => $br,
                        'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql'),
                    ], ['%d', '%s', '%s', '%d', '%s', '%s']);
                }
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            EmployeeRepository::dbg('replaceWorkdaySets failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Merge-Mode: Upsert Sets (mit ID → UPDATE, ohne → INSERT) + gezielte Löschungen.
     *
     * Differenzielle Updates für:
     * - Set-Metadaten (week_day_id, label, sort)
     * - N:N Services/Locations (Diff berechnen, nur Änderungen)
     * - Intervals (ID → Upsert, ohne ID → INSERT, fehlende → DELETE)
     *
     * Transaktional: Bei Fehler wird alles zurückgerollt.
     *
     * @param int $userId Employee ID
     * @param array $sets Array von Workday-Sets (mit/ohne 'id')
     * @param array $deleteIds Array von Set-IDs zum Löschen
     * @param string $setTab Tabelle für Sets
     * @param string $intTab Tabelle für Intervals
     * @param string $wSetLoc Tabelle für Location-Mappings
     * @param string $wSetSvc Tabelle für Service-Mappings
     * @throws \Throwable Bei Datenbankfehlern
     */
    protected static function mergeWorkdaySets(int $userId, array $sets, array $deleteIds, string $setTab, string $intTab, string $wSetLoc, string $wSetSvc): void
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
            // 0) Löschungen (nur explizit angegebene IDs, inklusive Mappings/Intervals)
            if (!empty($deleteIds)) {
                $ids = array_values(array_unique(array_map('intval', $deleteIds)));
                $in = implode(',', array_fill(0, count($ids), '%d'));
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
                $day = (int)($s['week_day_id'] ?? 0);
                if ($day < 1 || $day > 7) {
                    continue;
                }

                $label = sanitize_text_field($s['label'] ?? '');
                $sort = (int)($s['sort'] ?? $idx);

                // Hilfsfunktionen
                $arr = function ($v) {
                    $a = is_array($v) ? $v : [];
                    $a = array_values(array_unique(array_map('intval', $a)));
                    return array_filter($a, fn($x) => $x > 0);
                };
                $services = $arr($s['services'] ?? (isset($s['service_id']) ? [$s['service_id']] : []));
                $locations = $arr($s['locations'] ?? (isset($s['location_id']) ? [$s['location_id']] : []));

                if ($setId > 0 && isset($exists[$setId])) {
                    // 1a) UPDATE Set (created_at bleibt bestehen)
                    $wpdb->update($setTab, [
                        'week_day_id' => $day,
                        'label' => ($label !== '') ? $label : null,
                        'sort' => $sort,
                        'updated_at' => current_time('mysql'),
                    ], ['id' => $setId, 'user_id' => $userId], ['%d', '%s', '%d', '%s'], ['%d', '%d']);

                    // 1b) N:N Services/Locations differenziell
                    $currSvc = $wpdb->get_col($wpdb->prepare("SELECT service_id FROM {$wSetSvc} WHERE set_id=%d", $setId)) ?: [];
                    $currLoc = $wpdb->get_col($wpdb->prepare("SELECT location_id FROM {$wSetLoc} WHERE set_id=%d", $setId)) ?: [];
                    $currSvc = array_map('intval', $currSvc);
                    $currLoc = array_map('intval', $currLoc);

                    $toAddSvc = array_values(array_diff($services, $currSvc));
                    $toDelSvc = array_values(array_diff($currSvc, $services));
                    $toAddLoc = array_values(array_diff($locations, $currLoc));
                    $toDelLoc = array_values(array_diff($currLoc, $locations));

                    if ($toDelSvc) {
                        $in = implode(',', array_fill(0, count($toDelSvc), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$wSetSvc} WHERE set_id=%d AND service_id IN ($in)", $setId, ...$toDelSvc));
                    }
                    foreach ($toAddSvc as $sid) {
                        $wpdb->insert($wSetSvc, ['set_id' => $setId, 'service_id' => $sid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                    }

                    if ($toDelLoc) {
                        $in = implode(',', array_fill(0, count($toDelLoc), '%d'));
                        $wpdb->query($wpdb->prepare("DELETE FROM {$wSetLoc} WHERE set_id=%d AND location_id IN ($in)", $setId, ...$toDelLoc));
                    }
                    foreach ($toAddLoc as $lid) {
                        $wpdb->insert($wSetLoc, ['set_id' => $setId, 'location_id' => $lid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                    }

                    // 1c) Intervals upserten (ID → UPDATE, sonst INSERT)
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
                    // 2) INSERT Set
                    $wpdb->insert($setTab, [
                        'user_id' => $userId,
                        'week_day_id' => $day,
                        'label' => ($label !== '') ? $label : null,
                        'sort' => $sort,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ], ['%d', '%d', '%s', '%d', '%s', '%s']);
                    $newId = (int)$wpdb->insert_id;
                    if ($newId <= 0) {
                        continue;
                    }

                    foreach ($services as $sid) {
                        $wpdb->insert($wSetSvc, ['set_id' => $newId, 'service_id' => $sid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
                    }
                    foreach ($locations as $lid) {
                        $wpdb->insert($wSetLoc, ['set_id' => $newId, 'location_id' => $lid, 'created_at' => current_time('mysql')], ['%d', '%d', '%s']);
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
            EmployeeRepository::dbg('mergeWorkdaySets failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Legacy-Methode: Konvertiert altes working_hours Format in neue workday_sets.
     *
     * @deprecated Seit 1.0.0 - Verwende stattdessen replaceWorkdaySets()
     * @param int $userId Employee ID
     * @param array $items Alte Working Hours (flat structure)
     * @param string $table Tabelle (wird ignoriert, nutzt neue Struktur)
     */
    protected static function replaceWorkingHours(int $userId, array $items, string $table): void
    {
        if (function_exists('_deprecated_function')) {
            _deprecated_function(__METHOD__, '1.0.0', 'replaceWorkdaySets');
        }
        global $wpdb;
        $tables = EmployeeRepository::employeeTables();

        // Alte Struktur: jedes Item = ein Interval mit week_day_id
        // Konvertiere in neue Struktur: ein Set pro Wochentag
        $byDay = [];
        foreach ($items as $it) {
            $day = (int)($it['week_day_id'] ?? 0);
            if ($day < 1 || $day > 7) {
                continue;
            }
            $st = EmployeeDataTransformer::toDbTime($it['start_time'] ?? '');
            $en = EmployeeDataTransformer::toDbTime($it['end_time'] ?? '');
            $brk = isset($it['is_break']) ? (int)!!$it['is_break'] : 0;
            $loc = isset($it['location_id']) && $it['location_id'] !== '' ? (int)$it['location_id'] : null;
            $svc = isset($it['service_id']) && $it['service_id'] !== '' ? (int)$it['service_id'] : null;

            if (!$st || !$en || $st >= $en) {
                continue;
            }

            if (!isset($byDay[$day])) {
                $byDay[$day] = [
                    'week_day_id' => $day,
                    'label' => null,
                    'sort' => 0,
                    'intervals' => [],
                    'services' => [],
                    'locations' => [],
                ];
            }

            $byDay[$day]['intervals'][] = [
                'start_time' => $st,
                'end_time' => $en,
                'is_break' => $brk,
            ];
            if ($svc !== null && !in_array($svc, $byDay[$day]['services'], true)) {
                $byDay[$day]['services'][] = $svc;
            }
            if ($loc !== null && !in_array($loc, $byDay[$day]['locations'], true)) {
                $byDay[$day]['locations'][] = $loc;
            }
        }

        // Stabile Sortierung 1..7
        ksort($byDay);
        $sets = array_values($byDay);

        self::replaceWorkdaySets($userId, $sets, $tables['wSetTab'], $tables['wIntTab']);
    }
}
