<?php
declare(strict_types=1);

namespace Bookando\Core\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Bookando\Core\Partnership\PartnershipService;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Auth\Gate;

/**
 * Partnership REST API.
 *
 * Endpoints für Partner-Management:
 * - GET    /bookando/v1/partnerships                  - Liste aller Partnerships
 * - POST   /bookando/v1/partnerships                  - Neue Partnership erstellen
 * - GET    /bookando/v1/partnerships/{id}             - Partnership-Details
 * - PATCH  /bookando/v1/partnerships/{id}             - Partnership aktualisieren
 * - DELETE /bookando/v1/partnerships/{id}             - Partnership beenden
 * - POST   /bookando/v1/partnerships/{id}/permissions - Berechtigung gewähren
 * - DELETE /bookando/v1/partnerships/{id}/permissions - Berechtigung entziehen
 */
final class PartnershipApi
{
    private static ?PartnershipService $service = null;

    /**
     * Registriert alle Partnership-Endpoints.
     */
    public static function register(): void
    {
        register_rest_route('bookando/v1', '/partnerships', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'list'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'create'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);

        register_rest_route('bookando/v1', '/partnerships/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'get'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'PATCH',
                'callback' => [self::class, 'update'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [self::class, 'delete'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);

        register_rest_route('bookando/v1', '/partnerships/(?P<id>\d+)/permissions', [
            [
                'methods' => 'POST',
                'callback' => [self::class, 'grantPermission'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [self::class, 'revokePermission'],
                'permission_callback' => [self::class, 'checkPermission'],
            ],
        ]);
    }

    /**
     * Permission-Callback für alle Endpoints.
     */
    public static function checkPermission(WP_REST_Request $request): bool
    {
        // User muss eingeloggt sein
        if (!is_user_logged_in()) {
            return false;
        }

        // Nonce-Prüfung für Write-Operations
        if (Gate::isWrite($request)) {
            if (!Gate::verifyNonce($request)) {
                return false;
            }
        }

        // User muss Berechtigung für Settings haben (Partnerships sind Settings-Level)
        return Gate::canManage('settings');
    }

    /**
     * Liste aller Partnerships des aktuellen Tenants.
     *
     * GET /bookando/v1/partnerships?direction=outgoing|incoming
     */
    public static function list(WP_REST_Request $request): WP_REST_Response
    {
        $tenantId = TenantManager::currentTenantId();
        $direction = $request->get_param('direction') ?? 'outgoing';

        $service = self::getService();
        $partnerships = $service->getPartnerships($tenantId, $direction);

        return new WP_REST_Response([
            'partnerships' => $partnerships,
            'total' => count($partnerships),
            'direction' => $direction,
        ], 200);
    }

    /**
     * Erstellt eine neue Partnership.
     *
     * POST /bookando/v1/partnerships
     * Body: {
     *   "partner_tenant_id": 2,
     *   "relationship_type": "trusted_partner",
     *   "sharing_permissions": {
     *     "customers": ["view"],
     *     "events": ["view", "edit"]
     *   },
     *   "commission_type": "percentage",
     *   "commission_value": 15.00,
     *   "expires_at": "2025-12-31 23:59:59"
     * }
     */
    public static function create(WP_REST_Request $request): WP_REST_Response
    {
        $tenantId = TenantManager::currentTenantId();
        $partnerTenantId = (int) $request->get_param('partner_tenant_id');

        if (!$partnerTenantId) {
            return new WP_REST_Response([
                'code' => 'missing_partner_tenant',
                'message' => __('Partner tenant ID is required.', 'bookando'),
            ], 400);
        }

        $options = [
            'relationship_type' => sanitize_text_field($request->get_param('relationship_type') ?? 'trusted_partner'),
            'sharing_permissions' => $request->get_param('sharing_permissions') ?? [],
            'commission_type' => sanitize_text_field($request->get_param('commission_type') ?? 'percentage'),
            'commission_value' => (float) ($request->get_param('commission_value') ?? 10.00),
            'expires_at' => $request->get_param('expires_at'),
            'metadata' => $request->get_param('metadata') ?? [],
        ];

        $service = self::getService();
        $result = $service->createPartnership($tenantId, $partnerTenantId, $options);

        if ($result instanceof WP_Error) {
            return new WP_REST_Response([
                'code' => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response($result, 201);
    }

    /**
     * Liefert Details zu einer Partnership.
     *
     * GET /bookando/v1/partnerships/{id}
     */
    public static function get(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $service = self::getService();

        $partnership = $service->getService()->repository->findById($id);

        if (!$partnership) {
            return new WP_REST_Response([
                'code' => 'partnership_not_found',
                'message' => __('Partnership not found.', 'bookando'),
            ], 404);
        }

        // Security: Nur beteiligten Tenants dürfen sehen
        $currentTenant = TenantManager::currentTenantId();
        if ($partnership['primary_tenant'] !== $currentTenant && $partnership['partner_tenant'] !== $currentTenant) {
            return new WP_REST_Response([
                'code' => 'partnership_unauthorized',
                'message' => __('You are not authorized to view this partnership.', 'bookando'),
            ], 403);
        }

        return new WP_REST_Response($partnership, 200);
    }

    /**
     * Aktualisiert eine Partnership.
     *
     * PATCH /bookando/v1/partnerships/{id}
     * Body: {
     *   "sharing_permissions": {...},
     *   "commission_value": 20.00,
     *   "status": "suspended"
     * }
     */
    public static function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $tenantId = TenantManager::currentTenantId();

        $updates = [];

        if ($request->has_param('sharing_permissions')) {
            $updates['sharing_permissions'] = $request->get_param('sharing_permissions');
        }

        if ($request->has_param('commission_value')) {
            $updates['commission_value'] = (float) $request->get_param('commission_value');
        }

        if ($request->has_param('commission_type')) {
            $updates['commission_type'] = sanitize_text_field($request->get_param('commission_type'));
        }

        if ($request->has_param('status')) {
            $updates['status'] = sanitize_text_field($request->get_param('status'));
        }

        if ($request->has_param('metadata')) {
            $updates['metadata'] = $request->get_param('metadata');
        }

        $service = self::getService();
        $result = $service->updatePartnership($id, $tenantId, $updates);

        if ($result instanceof WP_Error) {
            return new WP_REST_Response([
                'code' => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Partnership updated successfully.', 'bookando'),
        ], 200);
    }

    /**
     * Beendet eine Partnership.
     *
     * DELETE /bookando/v1/partnerships/{id}
     */
    public static function delete(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $tenantId = TenantManager::currentTenantId();

        $service = self::getService();
        $result = $service->terminatePartnership($id, $tenantId);

        if ($result instanceof WP_Error) {
            return new WP_REST_Response([
                'code' => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Partnership terminated successfully.', 'bookando'),
        ], 200);
    }

    /**
     * Gewährt Berechtigung für eine Partnership.
     *
     * POST /bookando/v1/partnerships/{id}/permissions
     * Body: {
     *   "resource": "customers",
     *   "permission": "edit"
     * }
     */
    public static function grantPermission(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $resource = sanitize_text_field($request->get_param('resource'));
        $permission = sanitize_text_field($request->get_param('permission'));

        if (!$resource || !$permission) {
            return new WP_REST_Response([
                'code' => 'missing_parameters',
                'message' => __('Resource and permission are required.', 'bookando'),
            ], 400);
        }

        $service = self::getService();
        $result = $service->grantPermission($id, $resource, $permission);

        if ($result instanceof WP_Error) {
            return new WP_REST_Response([
                'code' => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Permission granted successfully.', 'bookando'),
        ], 200);
    }

    /**
     * Entzieht Berechtigung für eine Partnership.
     *
     * DELETE /bookando/v1/partnerships/{id}/permissions?resource=customers&permission=edit
     */
    public static function revokePermission(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $resource = sanitize_text_field($request->get_param('resource'));
        $permission = sanitize_text_field($request->get_param('permission'));

        if (!$resource || !$permission) {
            return new WP_REST_Response([
                'code' => 'missing_parameters',
                'message' => __('Resource and permission are required.', 'bookando'),
            ], 400);
        }

        $service = self::getService();
        $result = $service->revokePermission($id, $resource, $permission);

        if ($result instanceof WP_Error) {
            return new WP_REST_Response([
                'code' => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Permission revoked successfully.', 'bookando'),
        ], 200);
    }

    /**
     * Liefert Service-Instanz (lazy loading).
     */
    private static function getService(): PartnershipService
    {
        if (self::$service === null) {
            self::$service = new PartnershipService();
        }

        return self::$service;
    }
}
