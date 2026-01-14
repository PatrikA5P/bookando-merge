<?php

declare(strict_types=1);

namespace Bookando\Modules\Customers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Bookando\Core\Dispatcher\RestModuleGuard;
use WP_REST_Server;
use Bookando\Core\Api\Response;
use Bookando\Core\Auth\Gate;
use Bookando\Core\Dispatcher\RestPermissions;
use Bookando\Core\Tenant\TenantManager;
use function __;
use function is_wp_error;

/**
 * REST API handler for customer operations.
 *
 * Handles all customer-related HTTP requests (GET, POST, PUT, DELETE)
 * with strict tenant isolation. All operations are scoped to the current
 * tenant to ensure data privacy and security.
 */
class RestHandler
{
    /**
     * Handles customer CRUD operations via REST API.
     *
     * Supports the following operations:
     * - GET /customers/{id} - Retrieve a single customer
     * - GET /customers - List all customers with optional filters
     * - POST /customers - Create a new customer
     * - PUT /customers/{id} - Update an existing customer
     * - DELETE /customers/{id} - Delete a customer (soft or hard delete)
     *
     * All operations enforce strict tenant isolation using the current tenant ID
     * from TenantManager. Developers can switch tenants via X-BOOKANDO-TENANT header
     * if they have the required capability.
     *
     * @param array $params URL parameters including optional 'subkey' for customer ID
     * @param WP_REST_Request $request The REST request object containing method, params, and body
     * @return WP_REST_Response Response object with customer data or error
     */
    public static function customers($params, WP_REST_Request $request): WP_REST_Response
    {
        $permission = RestPermissions::customers($request);
        if (is_wp_error($permission)) {
            return Response::error($permission);
        }

        $id       = isset($params['subkey']) ? (int) $params['subkey'] : 0;
        $method   = strtoupper($request->get_method());
        // Strikte Tenant-Isolation: Zeigt IMMER nur Daten des aktuellen Tenants
        // Entwickler können via X-BOOKANDO-TENANT Header Tenant wechseln (erfordert Capability)
        $tenantId = TenantManager::currentTenantId();

        // Use DI Container for service resolution
        $service = container()->get(CustomerService::class);

        if ($id && $method === 'GET') {
            $result = $service->getCustomer($id, $tenantId);
            if ($result instanceof WP_Error) {
                return Response::error($result);
            }

            return Response::ok($result);
        }

        if ($method === 'GET' && !$id) {
            $query = [
                'include_deleted' => $request->get_param('include_deleted'),
                'search'          => $request->get_param('search'),
                'limit'           => $request->get_param('limit'),
                'offset'          => $request->get_param('offset'),
                'order'           => $request->get_param('order'),
                'dir'             => $request->get_param('dir'),
            ];

            $result = $service->listCustomers($query, $tenantId);
            if ($result instanceof WP_Error) {
                return Response::error($result);
            }

            return Response::ok($result);
        }

        if ($method === 'POST') {
            $payload = (array) $request->get_json_params();
            $result  = $service->createCustomer($payload, $tenantId);
            if ($result instanceof WP_Error) {
                return Response::error($result);
            }

            return Response::created($result);
        }

        if ($id && $method === 'PUT') {
            $payload = (array) $request->get_json_params();
            $result  = $service->updateCustomer($id, $payload, $tenantId);
            if ($result instanceof WP_Error) {
                return Response::error($result);
            }

            return Response::ok($result);
        }

        if ($id && $method === 'DELETE') {
            $hard   = (bool) $request->get_param('hard');
            $result = $service->deleteCustomer($id, $hard, $tenantId);
            if ($result instanceof WP_Error) {
                return Response::error($result);
            }

            return Response::ok($result);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    /**
     * Handles bulk operations on multiple customers.
     *
     * Allows performing actions on multiple customers simultaneously:
     * - Bulk delete (soft or hard)
     * - Bulk status update
     * - Bulk export
     *
     * Request payload should include:
     * - 'action': The bulk action to perform (e.g., 'delete', 'export')
     * - 'ids': Array of customer IDs to process
     * - Additional action-specific parameters
     *
     * All operations enforce strict tenant isolation.
     *
     * @param array $params URL parameters (unused for bulk operations)
     * @param WP_REST_Request $request The REST request object with JSON payload
     * @return WP_REST_Response Response with results or error details
     */
    public static function bulk($params, WP_REST_Request $request): WP_REST_Response
    {
        $permission = RestPermissions::customers($request);
        if (is_wp_error($permission)) {
            return Response::error($permission);
        }

        // Strikte Tenant-Isolation
        $tenantId = TenantManager::currentTenantId();

        // Use DI Container for service resolution
        $service = container()->get(CustomerService::class);
        $payload = (array) $request->get_json_params();

        $result = $service->bulkAction($payload, $tenantId);

        if ($result instanceof WP_Error) {
            return Response::error($result);
        }

        return Response::ok($result);
    }
}
