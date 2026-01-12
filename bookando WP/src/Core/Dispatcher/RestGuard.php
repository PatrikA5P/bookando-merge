<?php
// src/Core/Dispatcher/RestGuard.php
namespace Bookando\Core\Dispatcher;

use WP_REST_Request;
use WP_Error;
use function _x;

/**
 * @deprecated Use {@see RestModuleGuard} once all modules have been migrated.
 */
class RestGuard {
    /**
     * Verifiziert Nonce (bei Unsafe-Methoden), Capability und Tenant.
     * Gibt true oder WP_Error zurück (kein throw).
     */
    public static function verify(WP_REST_Request $req, string $cap, bool $nonceForGet = false) {
        $method = strtoupper($req->get_method() ?? 'GET');

        if ($nonceForGet || in_array($method, ['POST','PUT','PATCH','DELETE'], true)) {
            $nonce = $req->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error(
                    'rest_nonce_invalid',
                    _x('Invalid nonce', 'REST API error message', 'bookando'),
                    ['status' => 401]
                );
            }
        }

        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_unauthorized',
                _x('Not logged in', 'REST API error message', 'bookando'),
                ['status' => 401]
            );
        }
        if (!current_user_can($cap)) {
            return new \WP_Error(
                'rest_forbidden',
                _x('Insufficient capability', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }

        $tenantParam = $req->get_param('tenant_id');
        $tenant = $tenantParam !== null
            ? (int) $tenantParam
            : \Bookando\Core\Tenant\TenantManager::currentTenantId();

        if ($tenant !== null && !\Bookando\Core\Tenant\TenantManager::isAllowedFor((int)$tenant)) {
            return new \WP_Error(
                'rest_tenant_forbidden',
                _x('Tenant scope invalid', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }
        return true;
    }
}
