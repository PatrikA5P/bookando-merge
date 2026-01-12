<?php
namespace Bookando\Core\Dispatcher;

use WP_Error;
use WP_REST_Request;
use Bookando\Core\Auth\Gate;
use Bookando\Core\Dispatcher\RestModuleGuard;
use function _x;

class RestPermissions
{
    /**
     * Standard-Permissions für das Modul "customers".
     */
    public static function customers(WP_REST_Request $request)
    {
        $guard = RestModuleGuard::for('customers', static fn(\WP_REST_Request $req) => self::customersExtra($req));
        return $guard($request);
    }

    public static function customersExtra(WP_REST_Request $request)
    {
        $method = strtoupper($request->get_method() ?? 'GET');
        $route  = method_exists($request, 'get_route') ? (string) $request->get_route() : '';

        $idParam = $request->get_param('subkey');
        if ($idParam === null) {
            $idParam = $request->get_param('id');
        }
        $id = is_numeric($idParam) ? (int) $idParam : 0;

        // Collections (GET /customers) erfordern Manage-Recht
        if ($method === 'GET' && $id === 0 && !self::isBulkRoute($route)) {
            return Gate::canManage('customers')
                ? true
                : new WP_Error(
                    'rest_forbidden',
                    _x('Missing capability to list customers.', 'REST API error message', 'bookando'),
                    ['status' => 403]
                );
        }

        // Detail-Reads erlauben wir Lesenden nur für den eigenen Datensatz + gültigem Nonce
        if ($method === 'GET' && $id > 0) {
            return self::customerDetailRead($request, $id);
        }

        return true;
    }

    protected static function isBulkRoute(string $route): bool
    {
        return str_contains($route, '/customers/bulk');
    }

    public static function customerDetailRead(WP_REST_Request $request, int $id)
    {
        if (Gate::canManage('customers')) {
            return true;
        }

        if (!Gate::isSelf($id)) {
            return new WP_Error(
                'rest_forbidden',
                _x('Missing permission for this customer.', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }

        if (!Gate::verifyNonce($request)) {
            return new WP_Error(
                'rest_nonce_invalid',
                _x('Invalid nonce', 'REST API error message', 'bookando'),
                ['status' => 401]
            );
        }

        return true;
    }
}
