<?php

declare(strict_types=1);

namespace Bookando\Modules\Employees\Handlers;

use WP_REST_Request;
use WP_Error;
use function __;
use function rest_ensure_response;
use function sanitize_text_field;
use function current_time;
use function strtoupper;
use function strtolower;
use function trim;
use function preg_replace;
use function esc_url_raw;
use function sha1;
use function substr;
use function in_array;
use function array_values;
use function array_filter;
use function is_email;
use function get_bloginfo;

/**
 * Calendar & Connection Manager.
 *
 * Verwaltet komplexe Calendar-Infrastruktur:
 * - Connections (OAuth-Provider, ICS-URLs)
 * - Calendars (1:N mit Connection, Flags: is_busy_source, is_default_write)
 * - ICS-Endpunkte (POST/DELETE für ICS-Quellen)
 * - Calendar Invites (iCalendar .ics Versand per E-Mail)
 *
 * Provider:
 * - OAuth: google, microsoft, exchange, icloud
 * - ICS: Externe URLs (webcal://, https://)
 *
 * Datenstruktur:
 * - calendar_connections: user_id, provider, scope, auth_type, ics_url
 * - calendars: connection_id, calendar_id, name, access, is_busy_source, is_default_write
 */
class CalendarManager
{
    /**
     * GET /employees/{id}/calendars → Lädt alle Calendars + Connections
     * PUT /employees/{id}/calendars → Full-Replace aller Calendars (komplexe Logik)
     * PATCH /employees/{id}/calendars/{calId} → Update einzelner Calendar-Flags
     * DELETE /employees/{id}/calendars/{calId} → Löscht einzelnen Calendar
     *
     * @param array $params Route-Parameter (id, calId)
     * @param WP_REST_Request $request REST-Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function calendars(array $params, WP_REST_Request $request)
    {
        global $wpdb;
        $tables = EmployeeRepository::employeeTables();
        $connTab = $tables['calConnTab'];
        $calTab = $tables['calsTab'];

        $userId = (int)($params['id'] ?? 0);
        if ($userId <= 0) {
            return new WP_Error('bad_request', __('Benutzer-ID fehlt.', 'bookando'), ['status' => 400]);
        }

        $method = strtoupper($request->get_method());

        if ($method === 'GET') {
            if (!EmployeeAuthorizationGuard::canReadRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
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
            if (!EmployeeAuthorizationGuard::canWriteRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
            }
            $body = (array)$request->get_json_params();
            $items = is_array($body['calendars'] ?? null) ? $body['calendars'] : [];
            self::replaceCalendars($userId, $items, $connTab, $calTab);
            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }
            return rest_ensure_response(['updated' => true]);
        }

        if ($method === 'PATCH' && isset($params['calId'])) {
            if (!EmployeeAuthorizationGuard::canWriteRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
            }
            $calId = (int)$params['calId'];
            $body = (array)$request->get_json_params();
            $isBusy = isset($body['is_busy_source']) ? (int)!!$body['is_busy_source'] : null;
            $isWrite = isset($body['is_default_write']) ? (int)!!$body['is_default_write'] : null;

            // Ownership sichern
            $own = $wpdb->get_var($wpdb->prepare(
                "SELECT c.id
                FROM {$calTab} c
                INNER JOIN {$connTab} x ON x.id=c.connection_id
                WHERE c.id=%d AND x.user_id=%d",
                $calId,
                $userId
            ));
            if (!$own) {
                return new WP_Error('not_found', __('Nicht gefunden.', 'bookando'), ['status' => 404]);
            }

            $upd = ['updated_at' => current_time('mysql')];
            $fmt = ['%s'];
            if ($isBusy !== null) {
                $upd['is_busy_source'] = $isBusy;
                $fmt[] = '%d';
            }
            if ($isWrite !== null) {
                $upd['is_default_write'] = $isWrite;
                $fmt[] = '%d';
            }
            $wpdb->update($calTab, $upd, ['id' => $calId], $fmt, ['%d']);

            if ($isWrite === 1) {
                // genau eine Default-Write pro User
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$calTab} c
                    INNER JOIN {$connTab} x ON x.id=c.connection_id
                    SET c.is_default_write = CASE WHEN c.id=%d THEN 1 ELSE 0 END,
                        c.updated_at = %s
                    WHERE x.user_id=%d",
                    $calId,
                    current_time('mysql'),
                    $userId
                ));
            }
            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }
            return rest_ensure_response(['updated' => true]);
        }

        if ($method === 'DELETE' && isset($params['calId'])) {
            if (!EmployeeAuthorizationGuard::canWriteRecord($userId, $request)) {
                return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
            }
            $calId = (int)$params['calId'];

            // Events -> Calendar löschen (falls Events-Tabelle existiert, sonst nur Calendar)
            $wpdb->query($wpdb->prepare(
                "DELETE c FROM {$calTab} c
                INNER JOIN {$connTab} x ON x.id=c.connection_id
                WHERE c.id=%d AND x.user_id=%d",
                $calId,
                $userId
            ));

            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }
            return rest_ensure_response(['deleted' => true]);
        }

        return new WP_Error('method_not_allowed', __('Methode nicht unterstützt.', 'bookando'), ['status' => 405]);
    }

    /**
     * POST /employees/{id}/calendar/connections/ics → Fügt ICS-Quelle hinzu
     * DELETE /employees/{id}/calendar/connections/ics → Entfernt ICS-Quelle
     *
     * Body (POST):
     * - url: ICS-URL (webcal:// wird zu https://)
     * - name: Optionaler Name (default: "ICS")
     *
     * Body (DELETE):
     * - url: ICS-URL ODER
     * - connection_id: Connection-ID
     *
     * @param array $params Route-Parameter (id)
     * @param WP_REST_Request $request REST-Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function calendarIcs(array $params, WP_REST_Request $request)
    {
        if (!EmployeeAuthorizationGuard::canWriteRecord((int)($params['id'] ?? 0), $request)) {
            return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
        }

        global $wpdb;
        $tables = EmployeeRepository::employeeTables();
        $calConnTab = $tables['calConnTab'];
        $calsTab = $tables['calsTab'];
        $eventsTab = $tables['eventsTab'];

        $userId = (int)($params['id'] ?? 0);
        if ($userId <= 0) {
            return new WP_Error('bad_request', __('Benutzer-ID fehlt.', 'bookando'), ['status' => 400]);
        }

        $method = strtoupper($request->get_method());

        if ($method === 'POST') {
            $body = (array)$request->get_json_params();

            // webcal:// → https://
            $urlRaw = trim((string)($body['url'] ?? ''));
            if ($urlRaw === '') {
                return new WP_Error('bad_request', __('URL fehlt.', 'bookando'), ['status' => 400]);
            }
            $urlRaw = preg_replace('/^webcal:\/\//i', 'https://', $urlRaw);
            $url = esc_url_raw($urlRaw);
            if (!$url) {
                return new WP_Error('bad_request', __('Ungültige URL.', 'bookando'), ['status' => 400]);
            }

            $name = sanitize_text_field((string)($body['name'] ?? 'ICS'));
            $hash = substr(sha1($url), 0, 20);
            $now = current_time('mysql');

            $wpdb->query('START TRANSACTION');
            try {
                // Connection (ics, ro) upserten
                $connId = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$calConnTab}
                    WHERE user_id=%d AND provider='ics' AND ics_url=%s
                    LIMIT 1",
                    $userId,
                    $url
                ));
                if ($connId <= 0) {
                    $wpdb->insert($calConnTab, [
                        'user_id' => $userId,
                        'provider' => 'ics',
                        'scope' => 'ro',
                        'auth_type' => 'ics',
                        'ics_url' => $url,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);
                    if ($wpdb->last_error) {
                        throw new \RuntimeException($wpdb->last_error);
                    }
                    $connId = (int)$wpdb->insert_id;
                }

                // Calendar unter dieser Connection upserten
                $calId = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$calsTab}
                    WHERE connection_id=%d AND calendar_id=%s
                    LIMIT 1",
                    $connId,
                    $hash
                ));

                $created = false;
                if ($calId > 0) {
                    $wpdb->update($calsTab, [
                        'name' => ($name !== '' ? $name : 'ICS'),
                        'is_busy_source' => 1,
                        'is_default_write' => 0,
                        'updated_at' => $now,
                    ], ['id' => $calId], ['%s', '%d', '%d', '%s'], ['%d']);
                    if ($wpdb->last_error) {
                        throw new \RuntimeException($wpdb->last_error);
                    }
                } else {
                    $wpdb->insert($calsTab, [
                        'connection_id' => $connId,
                        'calendar_id' => $hash,
                        'name' => ($name !== '' ? $name : 'ICS'),
                        'access' => 'ro',
                        'is_busy_source' => 1,
                        'is_default_write' => 0,
                        'time_zone' => null,
                        'color' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']);
                    if ($wpdb->last_error) {
                        throw new \RuntimeException($wpdb->last_error);
                    }
                    $calId = (int)$wpdb->insert_id;
                    $created = true;
                }

                $wpdb->query('COMMIT');
                return rest_ensure_response([
                    'id' => $calId,
                    'connection_id' => $connId,
                    'provider' => 'ics',
                    'calendar_id' => $hash,
                    'name' => $name !== '' ? $name : 'ICS',
                    'is_busy_source' => 1,
                    'is_default_write' => 0,
                    $created ? 'created' : 'updated' => true,
                ]);
            } catch (\Throwable $e) {
                $wpdb->query('ROLLBACK');
                EmployeeRepository::dbg('calendarIcs POST failed: ' . $e->getMessage());
                return new WP_Error('db_error', $e->getMessage(), ['status' => 500]);
            }
        }

        if ($method === 'DELETE') {
            $body = (array)$request->get_json_params();

            $connectionId = (int)($params['connection_id'] ?? 0);
            if (!$connectionId) {
                $connectionId = (int)($body['connection_id'] ?? 0);
            }

            $hashFromUrl = null;
            if (!$connectionId && !empty($body['url'])) {
                $urlRaw = preg_replace('/^webcal:\/\//i', 'https://', trim((string)$body['url']));
                $url = esc_url_raw($urlRaw);
                if ($url) {
                    $hashFromUrl = substr(sha1($url), 0, 20);
                }
            }

            if ($connectionId <= 0 && $hashFromUrl) {
                $connectionId = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT conn.id
                    FROM {$calConnTab} conn
                    INNER JOIN {$calsTab} c ON c.connection_id = conn.id
                    WHERE conn.user_id=%d AND conn.provider='ics' AND c.calendar_id=%s
                    LIMIT 1",
                    $userId,
                    $hashFromUrl
                ));
            }

            if ($connectionId <= 0) {
                return new WP_Error('bad_request', __('connection_id oder URL fehlt.', 'bookando'), ['status' => 400]);
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
                    if ($wpdb->last_error) {
                        throw new \RuntimeException($wpdb->last_error);
                    }
                    $wpdb->query($wpdb->prepare("DELETE FROM {$calsTab} WHERE id IN ($in)", ...$calIds));
                    if ($wpdb->last_error) {
                        throw new \RuntimeException($wpdb->last_error);
                    }
                }

                $wpdb->delete($calConnTab, ['id' => $connectionId, 'user_id' => $userId], ['%d', '%d']);
                if ($wpdb->last_error) {
                    throw new \RuntimeException($wpdb->last_error);
                }

                $wpdb->query('COMMIT');
                return rest_ensure_response(['deleted' => true, 'deleted_at' => $now]);
            } catch (\Throwable $e) {
                $wpdb->query('ROLLBACK');
                EmployeeRepository::dbg('calendarIcs DELETE failed: ' . $e->getMessage());
                return new WP_Error('db_error', $e->getMessage(), ['status' => 500]);
            }
        }

        return new WP_Error('method_not_allowed', __('Methode nicht unterstützt.', 'bookando'), ['status' => 405]);
    }

    /**
     * POST /employees/{id}/calendar/invite → Versendet iCalendar-Einladung per E-Mail
     *
     * Body:
     * - to: Array von E-Mail-Adressen
     * - subject: E-Mail-Betreff
     * - body: E-Mail-Text (optional)
     * - event: Event-Daten (uid, summary, description, location, start, end, organizer_email, attendees)
     *
     * Nutzt \Bookando\Core\Util\Ics::buildEvent() und ::sendInvite()
     *
     * @param array $params Route-Parameter (id)
     * @param WP_REST_Request $request REST-Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function calendarInvite(array $params, WP_REST_Request $request)
    {
        if (!EmployeeAuthorizationGuard::canWriteRecord((int)($params['id'] ?? 0), $request)) {
            return new WP_Error('forbidden', __('Keine Berechtigung.', 'bookando'), ['status' => 403]);
        }

        $data = (array)$request->get_json_params();
        $to = array_values(array_filter((array)($data['to'] ?? []), 'is_email'));
        if (empty($to)) {
            return new WP_Error('bad_request', __('Empfänger fehlen.', 'bookando'), ['status' => 400]);
        }

        $subject = sanitize_text_field((string)($data['subject'] ?? 'Einladung'));
        $bodyTxt = (string)($data['body'] ?? '');
        $ev = (array)($data['event'] ?? []);

        if (!class_exists(\Bookando\Core\Util\Ics::class)) {
            return new WP_Error('server_error', __('ICS-Helfer fehlt.', 'bookando'), ['status' => 500]);
        }
        $icsStr = \Bookando\Core\Util\Ics::buildEvent([
            'uid' => $ev['uid'] ?? '',
            'summary' => $ev['summary'] ?? '',
            'description' => $ev['description'] ?? '',
            'location' => $ev['location'] ?? '',
            'start' => $ev['start'] ?? '',
            'end' => $ev['end'] ?? '',
            'organizer' => $ev['organizer_email'] ?? get_bloginfo('admin_email'),
            'attendees' => array_values(array_filter((array)($ev['attendees'] ?? []), 'is_email')),
        ]);

        $sent = \Bookando\Core\Util\Ics::sendInvite($to, $subject, $bodyTxt, $icsStr);
        if (!$sent) {
            return new WP_Error('send_failed', __('E-Mail-Versand fehlgeschlagen.', 'bookando'), ['status' => 500]);
        }

        return rest_ensure_response(['ok' => true]);
    }

    /**
     * Full-Replace: Löscht alte Calendars + Connections, erstellt neue.
     *
     * Komplexe Logik:
     * - ICS: Eine Connection pro URL, Calendar-ID = HASH(url)
     * - OAuth: Eine Connection pro Provider, mehrere Calendars möglich
     * - Verwaiste Connections werden gelöscht
     * - Genau EIN default_write Calendar erzwingen
     *
     * Transaktional: Bei Fehler wird alles zurückgerollt.
     *
     * @param int $userId Employee ID
     * @param array $items Array von Calendar-Items
     * @param string $connTab Tabelle für Connections
     * @param string $calTab Tabelle für Calendars
     * @throws \Throwable Bei Datenbankfehlern
     */
    protected static function replaceCalendars(int $userId, array $items, string $connTab, string $calTab): void
    {
        global $wpdb;

        // Normalizer
        $normProvider = function ($p) {
            $p = strtolower(trim((string)$p));
            // Tolerantes Mapping
            if ($p === 'outlook') {
                $p = 'microsoft';
            }
            if ($p === 'apple') {
                $p = 'icloud';
            }
            $allowed = ['google', 'microsoft', 'exchange', 'icloud', 'ics'];
            return in_array($p, $allowed, true) ? $p : '';
        };
        $toStr = fn($v) => ($v !== null && $v !== '') ? (string)$v : '';
        $toRO = fn($v) => (in_array($v, ['ro', 'rw'], true) ? $v : 'ro');

        // Bestehenden Zustand laden
        $conns = $wpdb->get_results($wpdb->prepare(
            "SELECT id, provider, ics_url FROM {$connTab} WHERE user_id=%d ORDER BY id ASC",
            $userId
        ), ARRAY_A) ?: [];
        $connById = [];
        $connKeys = []; // provider+key → id  (key = ics_url bei ics, sonst provider)
        foreach ($conns as $c) {
            $connById[(int)$c['id']] = $c;
            $key = ($c['provider'] === 'ics') ? ('ics|' . $c['ics_url']) : ($c['provider']);
            $connKeys[$key] = (int)$c['id'];
        }

        $cals = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.connection_id, c.calendar_id FROM {$calTab} c
            INNER JOIN {$connTab} x ON x.id = c.connection_id
            WHERE x.user_id=%d",
            $userId
        ), ARRAY_A) ?: [];
        $calIndex = []; // connection_id|calendar_id → id
        foreach ($cals as $r) {
            $calIndex[(int)$r['connection_id'] . '|' . $r['calendar_id']] = (int)$r['id'];
        }

        // Alles, was am Ende nicht "gesehen" wurde, löschen
        $keepConn = [];
        $keepCal = [];

        $wpdb->query('START TRANSACTION');
        try {
            $wantDefaultWrite = null; // cal-id, die default_write sein soll

            foreach ((array)$items as $it) {
                $provider = $normProvider($it['provider'] ?? ($it['calendar'] ?? ''));
                if (!$provider) {
                    continue;
                }

                $name = sanitize_text_field($toStr($it['name'] ?? null));
                $isBusy = (int) !!($it['is_busy_source'] ?? 1);
                $isWrite = (int) !!($it['is_default_write'] ?? 0);
                $access = $toRO($toStr($it['access'] ?? 'ro'));

                // Connection ermitteln/erzeugen
                $connKey = '';
                $connectionId = 0;

                if ($provider === 'ics') {
                    $urlRaw = preg_replace('/^webcal:\/\//i', 'https://', trim((string)($it['url'] ?? '')));
                    $url = esc_url_raw($urlRaw);
                    if (!$url) {
                        continue;
                    }
                    $connKey = 'ics|' . $url;

                    if (isset($connKeys[$connKey])) {
                        $connectionId = (int)$connKeys[$connKey];
                        // ggf. aktualisieren (name liegt auf calendar, nicht connection)
                        $wpdb->update($connTab, ['updated_at' => current_time('mysql')], ['id' => $connectionId], ['%s'], ['%d']);
                    } else {
                        $wpdb->insert($connTab, [
                            'user_id' => $userId,
                            'provider' => 'ics',
                            'scope' => 'ro',
                            'auth_type' => 'ics',
                            'ics_url' => $url,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);
                        $connectionId = (int)$wpdb->insert_id;
                        $connKeys[$connKey] = $connectionId;
                    }

                    // ICS: calendar_id = HASH(url)
                    $calendarId = substr(sha1($url), 0, 20);
                    $calKey = $connectionId . '|' . $calendarId;

                    if (isset($calIndex[$calKey])) {
                        $calId = $calIndex[$calKey];
                        $wpdb->update($calTab, [
                            'name' => ($name !== '' ? $name : 'ICS'),
                            'access' => 'ro',
                            'is_busy_source' => $isBusy,
                            'is_default_write' => $isWrite,
                            'updated_at' => current_time('mysql'),
                        ], ['id' => $calId], ['%s', '%s', '%d', '%d', '%s'], ['%d']);
                    } else {
                        $wpdb->insert($calTab, [
                            'connection_id' => $connectionId,
                            'calendar_id' => $calendarId,
                            'name' => ($name !== '' ? $name : 'ICS'),
                            'access' => 'ro',
                            'is_busy_source' => $isBusy,
                            'is_default_write' => $isWrite,
                            'time_zone' => null,
                            'color' => null,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']);
                        $calId = (int)$wpdb->insert_id;
                    }

                    $keepConn[$connectionId] = true;
                    $keepCal[$calId] = true;
                    if ($isWrite) {
                        $wantDefaultWrite = $calId;
                    }
                } else {
                    // OAuth-Provider: eine Connection pro Provider
                    $connKey = $provider;
                    if (isset($connKeys[$connKey])) {
                        $connectionId = (int)$connKeys[$connKey];
                    } else {
                        $wpdb->insert($connTab, [
                            'user_id' => $userId,
                            'provider' => $provider,
                            'scope' => 'ro',
                            'auth_type' => 'oauth',
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d', '%s', '%s', '%s', '%s', '%s']);
                        $connectionId = (int)$wpdb->insert_id;
                        $connKeys[$connKey] = $connectionId;
                    }

                    $calendarId = $toStr($it['calendar_id'] ?? null);
                    if ($calendarId === '') {
                        continue;
                    }

                    $calKey = $connectionId . '|' . $calendarId;
                    if (isset($calIndex[$calKey])) {
                        $calId = $calIndex[$calKey];
                        $wpdb->update($calTab, [
                            'name' => ($name !== '' ? $name : strtoupper($provider) . '/' . $calendarId),
                            'access' => $access,
                            'is_busy_source' => $isBusy,
                            'is_default_write' => $isWrite,
                            'updated_at' => current_time('mysql'),
                        ], ['id' => $calId], ['%s', '%s', '%d', '%d', '%s'], ['%d']);
                    } else {
                        $wpdb->insert($calTab, [
                            'connection_id' => $connectionId,
                            'calendar_id' => $calendarId,
                            'name' => ($name !== '' ? $name : strtoupper($provider) . '/' . $calendarId),
                            'access' => $access,
                            'is_busy_source' => $isBusy,
                            'is_default_write' => $isWrite,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ], ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s']);
                        $calId = (int)$wpdb->insert_id;
                    }

                    $keepConn[$connectionId] = true;
                    $keepCal[$calId] = true;
                    if ($isWrite) {
                        $wantDefaultWrite = $calId;
                    }
                }
            }

            // Alle nicht mehr gewünschten Kalender löschen (inkl. Events, falls du die Tabelle hast)
            if (!empty($calIndex)) {
                $allCalIds = array_values($calIndex);
                $toDelete = array_values(array_diff($allCalIds, array_keys($keepCal)));
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
                $toDelete = array_values(array_diff($allConnIds, array_keys($keepConn)));
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
                    $wantDefaultWrite,
                    current_time('mysql'),
                    $userId
                ));
            }

            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            EmployeeRepository::dbg('replaceCalendars failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
