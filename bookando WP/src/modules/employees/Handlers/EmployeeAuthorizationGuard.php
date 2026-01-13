<?php

declare(strict_types=1);

namespace Bookando\Modules\employees\Handlers;

use WP_REST_Request;
use WP_Error;
use Bookando\Core\Auth\Gate;
use function __;
use function strtoupper;
use function preg_match;

/**
 * Authorization Guard für Employee-Modul.
 *
 * Zentrale Klasse für alle Berechtigungsprüfungen:
 * - Kann User Employee-Daten lesen?
 * - Kann User Employee-Daten schreiben?
 * - REST-Endpoint Permission Guard
 */
class EmployeeAuthorizationGuard
{
    /**
     * Prüft, ob der aktuelle User einen Employee-Record lesen darf.
     *
     * Erlaubt wenn:
     * - Dev-Bypass aktiv (WP_DEBUG)
     * - User hat 'manage_bookando_employees' Capability
     * - User ist der Employee selbst (Self-Read) UND gültiger Nonce
     *
     * @param int $employeeId Employee ID
     * @param WP_REST_Request $request REST Request mit Nonce
     * @return bool True wenn Zugriff erlaubt
     */
    public static function canReadRecord(int $employeeId, WP_REST_Request $request): bool
    {
        if (Gate::devBypass() || Gate::canManage('employees')) {
            return true;
        }

        // Self-Read nur mit gültigem Nonce
        return Gate::isSelf($employeeId) && Gate::verifyNonce($request);
    }

    /**
     * Prüft, ob der aktuelle User einen Employee-Record schreiben darf.
     *
     * Erlaubt wenn:
     * - Dev-Bypass aktiv (WP_DEBUG)
     * - User hat 'manage_bookando_employees' Capability
     * - User ist der Employee selbst (Self-Write) UND gültiger Nonce
     *
     * @param int $employeeId Employee ID
     * @param WP_REST_Request $request REST Request mit Nonce
     * @return bool True wenn Zugriff erlaubt
     */
    public static function canWriteRecord(int $employeeId, WP_REST_Request $request): bool
    {
        if (Gate::devBypass() || Gate::canManage('employees')) {
            return true;
        }

        // Self-Write nur mit gültigem Nonce
        return Gate::isSelf($employeeId) && Gate::verifyNonce($request);
    }

    /**
     * REST-Endpoint Permission Guard.
     *
     * Wird vor jedem REST-Endpoint aufgerufen.
     * Blockiert nicht-GET Requests ohne manage_bookando_employees Capability.
     *
     * @param WP_REST_Request $request REST Request
     * @return bool|WP_Error True wenn erlaubt, WP_Error wenn verboten
     */
    public static function guardPermissions(WP_REST_Request $request)
    {
        if (Gate::devBypass() || Gate::canManage('employees')) {
            return true;
        }

        // Nur GET erlaubt ohne manage-Rechte
        if (strtoupper($request->get_method()) !== 'GET') {
            return self::forbiddenManageError();
        }

        $employeeId = self::resolveEmployeeIdFromRequest($request);
        if ($employeeId <= 0) {
            return self::forbiddenManageError();
        }

        return true;
    }

    /**
     * Extrahiert Employee-ID aus dem REST-Request.
     *
     * Versucht in dieser Reihenfolge:
     * 1. Request-Parameter 'id'
     * 2. Request-Parameter 'subkey'
     * 3. Route-Matching (regex)
     *
     * @param WP_REST_Request $request REST Request
     * @return int Employee ID oder 0 wenn nicht gefunden
     */
    public static function resolveEmployeeIdFromRequest(WP_REST_Request $request): int
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

    /**
     * Erstellt einen 403 Forbidden Error mit i18n-Nachricht.
     *
     * @return WP_Error Fehler-Objekt
     */
    public static function forbiddenManageError(): WP_Error
    {
        return new WP_Error(
            'rest_forbidden',
            __('Zusätzliche Berechtigung manage_bookando_employees erforderlich.', 'bookando'),
            ['status' => 403]
        );
    }

    /**
     * Erstellt einen generischen 403 Forbidden Error.
     *
     * @return WP_Error Fehler-Objekt
     */
    public static function forbiddenError(): WP_Error
    {
        return new WP_Error(
            'forbidden',
            __('Keine Berechtigung.', 'bookando'),
            ['status' => 403]
        );
    }
}
