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
 * Days Off Collection Manager.
 *
 * Verwaltet die komplette Days-Off-Lifecycle:
 * - GET: Lesen aller Days Off (ganztägig, optional jährliche Wiederholung)
 * - POST/PUT: Full-Replace oder Merge-Mode (Upsert + gezielte Löschungen)
 *
 * Datenstruktur:
 * - days_off (flache Struktur, keine Subtabellen)
 * - Felder: name, note, start_date, end_date, repeat_yearly
 * - Ganztägig (keine Uhrzeiten)
 */
class DaysOffManager
{
    /**
     * GET /employees/{id}/days-off → Lädt alle Days Off
     * POST/PUT /employees/{id}/days-off → Replace oder Merge (je nach Body)
     *
     * Merge-Modus wird aktiviert wenn:
     * - mode=merge explizit gesetzt
     * - Body enthält 'upsert' oder 'delete_ids'
     * - Items in days_off haben 'id'-Feld oder '_delete'-Flag
     *
     * @param array $params Route-Parameter (id)
     * @param WP_REST_Request $request REST-Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function daysOff(array $params, WP_REST_Request $request)
    {
        global $wpdb;
        $tables = EmployeeRepository::employeeTables();
        $tab = $tables['holTab'];

        // Benutzer-ID robust ermitteln (Backcompat wie in den anderen Subressourcen)
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

        $method = strtoupper($request->get_method());

        /* =========================
        GET /employees/{id}/days-off
        ========================= */
        if ($method === 'GET') {
            if (!EmployeeAuthorizationGuard::canReadRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
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
            if (!EmployeeAuthorizationGuard::canWriteRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
            }

            $body = (array)$request->get_json_params();

            // Modus automatisch erkennen (wie bei workdaySets/specialDaySets):
            // - explizit mode=merge
            // - oder Felder 'upsert'/'delete_ids' vorhanden
            // - oder Items mit 'id' bzw. '_delete' → merge
            $mode = sanitize_key($body['mode'] ?? '');
            $hasUpsert = is_array($body['upsert'] ?? null);
            $hasDeleteIds = !empty($body['delete_ids']);
            $daysOffItems = is_array($body['days_off'] ?? null) ? $body['days_off'] : [];

            if ($mode !== 'merge') {
                if ($hasUpsert || $hasDeleteIds) {
                    $mode = 'merge';
                } else {
                    foreach ($daysOffItems as $it) {
                        if (!empty($it['id']) || !empty($it['_delete'])) {
                            $mode = 'merge';
                            break;
                        }
                    }
                }
            }

            if ($mode === 'merge') {
                // Upsert + gezielte Löschungen
                $upsert = $hasUpsert ? (array)$body['upsert'] : $daysOffItems;
                $deleteIds = array_values(array_filter(array_map('intval', (array)($body['delete_ids'] ?? [])), fn($v) => $v > 0));

                self::mergeDaysOff($userId, $upsert, $tab, $deleteIds);
                if ($wpdb->last_error) {
                    return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
                }

                return rest_ensure_response(['updated' => true, 'mode' => 'merge']);
            }

            // Full-Replace (Backcompat: wenn nur days_off übergeben wird)
            self::replaceDaysOff($userId, $daysOffItems, $tab);
            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }

            return rest_ensure_response(['updated' => true, 'mode' => 'replace']);
        }

        return new WP_Error('method_not_allowed', __('Methode nicht unterstützt.', 'bookando'), ['status' => 405]);
    }

    /**
     * Full-Replace: Löscht alle alten Days Off und erstellt neue.
     *
     * Transaktional: Bei Fehler wird alles zurückgerollt.
     *
     * @param int $userId Employee ID
     * @param array $items Array von Days Off
     * @param string $table Tabelle für Days Off
     * @throws \Throwable Bei Datenbankfehlern
     */
    protected static function replaceDaysOff(int $userId, array $items, string $table): void
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->delete($table, ['user_id' => $userId], ['%d']);

            foreach ($items as $it) {
                $name = sanitize_text_field($it['name'] ?? '');
                $note = sanitize_text_field($it['note'] ?? '');
                $sd = EmployeeDataTransformer::toDbDate($it['start_date'] ?? '');
                $ed = EmployeeDataTransformer::toDbDate($it['end_date'] ?? '');
                $repY = (int) !!($it['repeat_yearly'] ?? 0);
                if (!$sd) {
                    continue;
                }
                if (!$ed) {
                    $ed = $sd;
                }

                $wpdb->insert($table, [
                    'user_id' => $userId,
                    'name' => $name !== '' ? $name : null,
                    'note' => $note !== '' ? $note : null,
                    'start_date' => $sd,
                    'end_date' => $ed,
                    'repeat_yearly' => $repY,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ], ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']);
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            EmployeeRepository::dbg('replaceDaysOff failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Merge-Mode: Upsert Days Off (mit ID → UPDATE, ohne → INSERT) + gezielte Löschungen.
     *
     * Unterstützt '_delete'-Flags in Items:
     * - Item mit '_delete' und 'id' → wird zur Löschliste hinzugefügt
     * - Item ohne '_delete' → Upsert (CREATE/UPDATE)
     *
     * Transaktional: Bei Fehler wird alles zurückgerollt.
     *
     * @param int $userId Employee ID
     * @param array $items Array von Days Off (mit/ohne 'id', mit/ohne '_delete')
     * @param string $table Tabelle für Days Off
     * @param array $deleteIds Array von IDs zum Löschen
     * @throws \Throwable Bei Datenbankfehlern
     */
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
        $deleteIds = array_values(array_unique(array_filter(array_map('intval', $deleteIds), fn($v) => $v > 0)));

        $wpdb->query('START TRANSACTION');
        try {
            // gezielte Löschungen
            if (!empty($deleteIds)) {
                $in = implode(',', array_fill(0, count($deleteIds), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table} WHERE user_id=%d AND id IN ($in)",
                    $userId,
                    ...$deleteIds
                ));
            }

            // vorhandene IDs (Ownership)
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d", $userId), ARRAY_A) ?: [];
            $exists = array_fill_keys(array_map('intval', array_column($rows, 'id')), true);

            // Upserts
            foreach ($items as $it) {
                if (!empty($it['_delete'])) {
                    continue;
                }

                $id = isset($it['id']) ? (int)$it['id'] : 0;
                $name = sanitize_text_field($it['name'] ?? '');
                $note = sanitize_text_field($it['note'] ?? '');

                $sd = EmployeeDataTransformer::toDbDate($it['start_date'] ?? '');
                $ed = EmployeeDataTransformer::toDbDate($it['end_date'] ?? '');
                if (!$sd) {
                    continue;
                }
                if (!$ed) {
                    $ed = $sd;
                }

                $rep = (int) !!($it['repeat_yearly'] ?? $it['repeat'] ?? 0);

                if ($id > 0 && isset($exists[$id])) {
                    $wpdb->update($table, [
                        'name' => ($name !== '') ? $name : null,
                        'note' => ($note !== '') ? $note : null,
                        'start_date' => $sd,
                        'end_date' => $ed,
                        'repeat_yearly' => $rep,
                        'updated_at' => current_time('mysql'),
                    ], ['id' => $id, 'user_id' => $userId], ['%s', '%s', '%s', '%s', '%d', '%s'], ['%d', '%d']);
                } else {
                    $wpdb->insert($table, [
                        'user_id' => $userId,
                        'name' => ($name !== '') ? $name : null,
                        'note' => ($note !== '') ? $note : null,
                        'start_date' => $sd,
                        'end_date' => $ed,
                        'repeat_yearly' => $rep,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ], ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']);
                }
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            EmployeeRepository::dbg('mergeDaysOff failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
