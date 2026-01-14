<?php

declare(strict_types=1);

namespace Bookando\Modules\Employees\Handlers;

use WP_REST_Request;
use WP_Error;
use Bookando\Core\Api\Response;
use Bookando\Core\Settings\FormRules;
use function rest_ensure_response;
use function __;
use function is_email;
use function wp_json_encode;
use function current_time;

/**
 * Handler für Employee-Befehle (Command Operations).
 *
 * Verantwortlich für:
 * - Employee erstellen (POST /employees)
 * - Employee aktualisieren (PUT /employees/{id})
 * - Nested Collections Management (Delegation an Manager)
 * - FormRules-Validierung
 * - Transaction-Handling
 */
class EmployeeCommandHandler
{
    /**
     * Erstellt einen neuen Employee mit optionalen Nested Collections.
     *
     * Nested Collections (Full-Replace wenn vorhanden):
     * - workday_sets: WorkdaySetManager::replaceWorkdaySets()
     * - days_off: DaysOffManager::replaceDaysOff()
     * - special_day_sets: SpecialDaySetManager::replaceSpecialDaySets()
     * - calendars: CalendarManager::replaceCalendars()
     *
     * @param int|null $tenantId Tenant ID für Isolation
     * @param WP_REST_Request $request REST Request mit Employee-Daten
     * @return \WP_REST_Response|WP_Error Response mit neuer ID oder Fehler
     */
    public static function handleEmployeeCreate(
        ?int $tenantId,
        WP_REST_Request $request
    ) {
        $tables = EmployeeRepository::employeeTables();
        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        // Input sanitizen (Null-First, tolerantes Mapping)
        $data = EmployeeInputValidator::sanitizeEmployeeInput(
            (array) $request->get_json_params(),
            true
        );

        // Email-Validierung
        if (!empty($data['email']) && !is_email($data['email'])) {
            return new WP_Error(
                'invalid_email',
                __('Ungültige E-Mail-Adresse.', 'bookando'),
                ['status' => 400]
            );
        }

        // Status normalisieren
        $targetStatus = EmployeeInputValidator::normalizeStatus($data['status'] ?? 'active');
        $rules        = FormRules::get('employees', 'admin');

        // FormRules-Validierung (nur wenn nicht deleted)
        if ($targetStatus !== 'deleted') {
            $missing = EmployeeFormValidator::validateByRules(
                $data + ['status' => $targetStatus],
                $rules,
                $targetStatus
            );
            if (!empty($missing)) {
                return new WP_Error(
                    'validation_error',
                    __('Pflichtfelder fehlen.', 'bookando'),
                    [
                        'status' => 422,
                        'fields' => $missing,
                    ]
                );
            }
        }

        // Roles setzen (immer bookando_employee)
        $roles = wp_json_encode(['bookando_employee']);

        // Insert-Daten vorbereiten
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

        // Employee-Hauptdatensatz anlegen
        $wpdb->insert($usersTab, $insert);

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        $userId = (int) $wpdb->insert_id;

        // Nested Collections verarbeiten (nur wenn vorhanden)
        // TODO: Diese Manager-Klassen müssen noch erstellt werden

        // Workday Sets (Arbeitszeiten)
        if (!empty($data['workday_sets']) && is_array($data['workday_sets'])) {
            // WorkdaySetManager::replaceWorkdaySets($userId, $data['workday_sets'], $wSetTab, $wIntTab);
            // PLACEHOLDER: Manager wird in Phase D implementiert
            EmployeeRepository::dbg('[CREATE] workday_sets present but manager not implemented yet');
        }

        // Days Off (Urlaubstage, Feiertage)
        if (!empty($data['days_off']) && is_array($data['days_off'])) {
            // DaysOffManager::replaceDaysOff($userId, $data['days_off'], $holTab);
            // PLACEHOLDER: Manager wird in Phase D implementiert
            EmployeeRepository::dbg('[CREATE] days_off present but manager not implemented yet');
        }

        // Special Day Sets (Abweichende Arbeitszeiten)
        if (!empty($data['special_day_sets']) && is_array($data['special_day_sets'])) {
            // SpecialDaySetManager::replaceSpecialDaySets($userId, $data['special_day_sets'], $sdSetTab, $sdIntTab);
            // PLACEHOLDER: Manager wird in Phase D implementiert
            EmployeeRepository::dbg('[CREATE] special_day_sets present but manager not implemented yet');
        }

        // Calendars (Kalender-Integrationen)
        if (!empty($data['calendars']) && is_array($data['calendars'])) {
            // CalendarManager::replaceCalendars($userId, $data['calendars'], $calConnTab, $calsTab);
            // PLACEHOLDER: Manager wird in Phase D implementiert
            EmployeeRepository::dbg('[CREATE] calendars present but manager not implemented yet');
        }

        // Finaler Fehler-Check
        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return Response::created(['id' => $userId]);
    }

    /**
     * Aktualisiert einen existierenden Employee.
     *
     * Nested Collections werden nur aktualisiert wenn im Payload vorhanden:
     * - workday_sets: Merge (mit IDs) oder Replace (ohne IDs)
     * - days_off: Merge (immer)
     * - special_day_sets: Merge (mit IDs) oder Replace (ohne IDs)
     * - calendars: Replace (immer)
     *
     * @param int $employeeId Employee ID
     * @param int|null $tenantId Tenant ID für Isolation
     * @param WP_REST_Request $request REST Request mit Update-Daten
     * @return \WP_REST_Response|WP_Error Response mit Success-Flag oder Fehler
     */
    public static function handleEmployeeUpdate(
        int $employeeId,
        ?int $tenantId,
        WP_REST_Request $request
    ) {
        // Authorization Check
        if (!EmployeeAuthorizationGuard::canWriteRecord($employeeId, $request)) {
            return EmployeeAuthorizationGuard::forbiddenError();
        }

        $tables = EmployeeRepository::employeeTables();
        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        // Input sanitizen (nur übergebene Felder)
        $data = EmployeeInputValidator::sanitizeEmployeeInput(
            (array) $request->get_json_params(),
            false
        );

        // Email-Validierung
        if (!empty($data['email']) && !is_email($data['email'])) {
            return new WP_Error(
                'invalid_email',
                __('Ungültige E-Mail-Adresse.', 'bookando'),
                ['status' => 400]
            );
        }

        // Aktuellen Datensatz laden (mit Tenant-Isolation)
        $sql = "SELECT * FROM {$usersTab} WHERE id=%d";
        if ($tenantId) {
            $sql .= $wpdb->prepare(" AND tenant_id=%d", $tenantId);
        }

        $currRow = $wpdb->get_row($wpdb->prepare($sql, $employeeId), ARRAY_A);
        if (!$currRow) {
            return new WP_Error('not_found', __('Nicht gefunden.', 'bookando'), ['status' => 404]);
        }

        // Status-Handling
        $currentStatus = $currRow['status'] ?? 'active';
        $targetStatus  = EmployeeInputValidator::normalizeStatus($data['status'] ?? $currentStatus);

        // FormRules-Validierung (Merge current + new data)
        $rules = FormRules::get('employees', 'admin');
        if ($targetStatus !== 'deleted') {
            $forValidation = array_merge($currRow, $data, ['status' => $targetStatus]);
            $missing       = EmployeeFormValidator::validateByRules($forValidation, $rules, $targetStatus);
            if (!empty($missing)) {
                return new WP_Error(
                    'validation_error',
                    __('Pflichtfelder fehlen.', 'bookando'),
                    [
                        'status' => 422,
                        'fields' => $missing,
                    ]
                );
            }
        }

        // Update-Daten vorbereiten (nur übergebene Felder)
        $upd = [];
        $updatableFields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'address',
            'address_2',
            'zip',
            'city',
            'country',
            'birthdate',
            'gender',
            'language',
            'note',
            'description',
            'avatar_url',
            'timezone',
            'status',
            'badge_id',
            'password_hash',
        ];

        foreach ($updatableFields as $key) {
            if (array_key_exists($key, $data)) {
                $upd[$key] = $data[$key];
            }
        }

        $upd['updated_at'] = current_time('mysql');

        // WHERE-Bedingung mit Tenant-Isolation
        $where = ['id' => $employeeId];
        $wf    = ['%d'];
        if ($tenantId) {
            $where['tenant_id'] = $tenantId;
            $wf[]               = '%d';
        }

        // Format-Array für wpdb->update (auto-detect %d/%s)
        $fmt = [];
        foreach ($upd as $value) {
            $fmt[] = is_int($value) ? '%d' : '%s';
        }

        // Hauptdatensatz aktualisieren
        $wpdb->update($usersTab, $upd, $where, $fmt, $wf);
        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        // Nested Collections verarbeiten (nur wenn im Payload vorhanden)
        // TODO: Diese Manager-Klassen müssen noch erstellt werden

        // Workday Sets (Merge oder Replace je nach IDs)
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
                // WorkdaySetManager::mergeWorkdaySets($employeeId, $sets, $deleteIds, $wSetTab, $wIntTab, $wSetLoc, $wSetSvc);
                // PLACEHOLDER: Manager wird in Phase D implementiert
                EmployeeRepository::dbg('[UPDATE] workday_sets merge but manager not implemented yet');
            } else {
                // WorkdaySetManager::replaceWorkdaySets($employeeId, $sets, $wSetTab, $wIntTab);
                // PLACEHOLDER: Manager wird in Phase D implementiert
                EmployeeRepository::dbg('[UPDATE] workday_sets replace but manager not implemented yet');
            }
        }

        // Days Off (immer Merge)
        if (array_key_exists('days_off', $data)) {
            $deleteIds = array_values(
                array_filter(
                    array_map('intval', (array) ($data['days_off_delete_ids'] ?? [])),
                    static fn($value) => $value > 0
                )
            );
            // DaysOffManager::mergeDaysOff($employeeId, is_array($data['days_off']) ? $data['days_off'] : [], $holTab, $deleteIds);
            // PLACEHOLDER: Manager wird in Phase D implementiert
            EmployeeRepository::dbg('[UPDATE] days_off merge but manager not implemented yet');
        }

        // Special Day Sets (Merge oder Replace je nach IDs)
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
                // SpecialDaySetManager::mergeSpecialDaySets($employeeId, $sets, $deleteIds, $sdSetTab, $sdIntTab, $sdSetLoc, $sdSetSvc);
                // PLACEHOLDER: Manager wird in Phase D implementiert
                EmployeeRepository::dbg('[UPDATE] special_day_sets merge but manager not implemented yet');
            } else {
                // SpecialDaySetManager::replaceSpecialDaySets($employeeId, $sets, $sdSetTab, $sdIntTab);
                // PLACEHOLDER: Manager wird in Phase D implementiert
                EmployeeRepository::dbg('[UPDATE] special_day_sets replace but manager not implemented yet');
            }
        }

        // Calendars (immer Replace)
        if (array_key_exists('calendars', $data)) {
            // CalendarManager::replaceCalendars($employeeId, is_array($data['calendars']) ? $data['calendars'] : [], $calConnTab, $calsTab);
            // PLACEHOLDER: Manager wird in Phase D implementiert
            EmployeeRepository::dbg('[UPDATE] calendars replace but manager not implemented yet');
        }

        // Finaler Fehler-Check
        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['updated' => true]);
    }
}
