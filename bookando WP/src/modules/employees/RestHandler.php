<?php

declare(strict_types=1);

namespace Bookando\Modules\employees;

use WP_REST_Request;
use WP_Error;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\employees\Handlers\EmployeeRepository;
use Bookando\Modules\employees\Handlers\EmployeeAuthorizationGuard;
use Bookando\Modules\employees\Handlers\EmployeeQueryHandler;
use Bookando\Modules\employees\Handlers\EmployeeCommandHandler;
use Bookando\Modules\employees\Handlers\EmployeeDeleteHandler;
use Bookando\Modules\employees\Handlers\BulkEmployeeHandler;
use Bookando\Modules\employees\Handlers\WorkdaySetManager;
use Bookando\Modules\employees\Handlers\DaysOffManager;
use Bookando\Modules\employees\Handlers\SpecialDaySetManager;
use Bookando\Modules\employees\Handlers\CalendarManager;
use function __;
use function strtoupper;

/**
 * REST-Handler für das Modul "employees".
 *
 * Thin Router Pattern:
 * Alle Business-Logik wurde in spezialisierte Handler-Klassen ausgelagert.
 * Dieser Router delegiert nur noch an die entsprechenden Handler.
 *
 * Handler-Architektur:
 * - EmployeeQueryHandler: GET operations (list, detail)
 * - EmployeeCommandHandler: POST/PUT operations (create, update)
 * - EmployeeDeleteHandler: DELETE operations (soft/hard delete)
 * - BulkEmployeeHandler: Bulk operations (block, activate, delete, save)
 * - WorkdaySetManager: Workday sets lifecycle
 * - DaysOffManager: Days off lifecycle
 * - SpecialDaySetManager: Special day sets lifecycle
 * - CalendarManager: Calendar + connection management
 *
 * Foundation Classes:
 * - EmployeeRepository: Database operations
 * - EmployeeAuthorizationGuard: Permission checks
 * - EmployeeInputValidator: Input sanitization
 * - EmployeeFormValidator: FormRules validation
 * - EmployeeDataTransformer: Data transformation utilities
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
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function employees($params, WP_REST_Request $request)
    {
        $method     = strtoupper($request->get_method());
        $employeeId = self::resolveEmployeeId($params, $request);
        $tenantId   = TenantManager::currentTenantId();
        $tables     = EmployeeRepository::employeeTables();

        if ($method === 'GET' && $employeeId > 0) {
            return EmployeeQueryHandler::handleEmployeeDetail($tables, $tenantId, $employeeId, $request);
        }

        if ($method === 'GET') {
            return EmployeeQueryHandler::handleEmployeeList($tables, $tenantId, $request);
        }

        if ($method === 'POST') {
            return EmployeeCommandHandler::handleEmployeeCreate($tables, $tenantId, $request);
        }

        if ($method === 'PUT' && $employeeId > 0) {
            return EmployeeCommandHandler::handleEmployeeUpdate($tables, $tenantId, $employeeId, $request);
        }

        if ($method === 'DELETE' && $employeeId > 0) {
            return EmployeeDeleteHandler::handleEmployeeDelete($tables, $tenantId, $employeeId, $request);
        }

        return new WP_Error('method_not_allowed', __('Methode nicht unterstützt.', 'bookando'), ['status' => 405]);
    }

    /**
     * /wp-json/bookando/v1/employees/bulk
     * Body: { action: 'block'|'activate'|'soft_delete'|'hard_delete'|'save', ids?: number[], payload?: any }
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function bulk($params, WP_REST_Request $request)
    {
        return BulkEmployeeHandler::bulk($params, $request);
    }

    /**
     * /wp-json/bookando/v1/employees/{id}/workday-sets
     *   GET  → Sets + Intervals laden
     *   POST → Full-Replace: Body { workday_sets: [...] }
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function workdaySets($params, WP_REST_Request $request)
    {
        return WorkdaySetManager::workdaySets($params, $request);
    }

    /**
     * /wp-json/bookando/v1/employees/{id}/days-off
     *   GET    → Days off laden
     *   POST   → Full-Replace
     *   PUT    → Partial (Merge)
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function daysOff($params, WP_REST_Request $request)
    {
        return DaysOffManager::daysOff($params, $request);
    }

    /**
     * /wp-json/bookando/v1/employees/{id}/special-day-sets
     *   GET  → Sets + Intervals laden
     *   POST → Full-Replace/Partial
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function specialDaySets($params, WP_REST_Request $request)
    {
        return SpecialDaySetManager::specialDaySets($params, $request);
    }

    /**
     * /wp-json/bookando/v1/employees/{id}/special-days (DEPRECATED)
     *
     * @deprecated Use specialDaySets instead
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return WP_Error 410 Gone
     */
    public static function specialDays($params, WP_REST_Request $request)
    {
        return SpecialDaySetManager::specialDays($params, $request);
    }

    /**
     * POST/DELETE /employees/{id}/calendar/connections/ics
     *  - POST:  Body { url, name? }
     *  - DELETE: Body { url } oder ?connection_id=...
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function calendarIcs($params, WP_REST_Request $request)
    {
        return CalendarManager::calendarIcs($params, $request);
    }

    /**
     * /wp-json/bookando/v1/employees/{id}/calendars
     *   GET    → Calendars laden
     *   PUT    → Full-Replace
     *   PATCH  → Partial (Merge)
     *   DELETE → Löschen ?connection_id=... oder ?calendar_id=...
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function calendars($params, WP_REST_Request $request)
    {
        return CalendarManager::calendars($params, $request);
    }

    /**
     * POST /employees/{id}/calendar/invite
     * Body: { to, event_id?, subject?, bodyText?, calendar_id?, connection_id? }
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function calendarInvite($params, WP_REST_Request $request)
    {
        return CalendarManager::calendarInvite($params, $request);
    }

    /**
     * REST-Endpoint Permission Guard.
     *
     * Wird vor jedem REST-Endpoint aufgerufen.
     * Delegiert an EmployeeAuthorizationGuard.
     *
     * @param WP_REST_Request $request REST Request
     * @return bool|WP_Error True wenn erlaubt, WP_Error wenn verboten
     */
    public static function guardPermissions(WP_REST_Request $request)
    {
        return EmployeeAuthorizationGuard::guardPermissions($request);
    }

    /**
     * Extrahiert Employee-ID aus URL-Parametern oder Request.
     *
     * Versucht in dieser Reihenfolge:
     * 1. $params['id']
     * 2. $params['subkey']
     * 3. $request->get_param('id')
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return int Employee ID oder 0
     */
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
}
