<?php

declare(strict_types=1);

namespace Bookando\Modules\Finance;

use Bookando\Core\Api\Response;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Modules\Finance\Gateways\GatewayManager;
use WP_REST_Request;
use WP_REST_Response;
use function __;

/**
 * Class PaymentRestHandler
 *
 * REST API handler for payment gateway operations.
 *
 * @package Bookando\Modules\finance
 */
class PaymentRestHandler
{
    /**
     * Get all available payment gateways
     *
     * GET /wp-json/bookando/v1/finance/payment/gateways
     *
     * @param array           $params Route parameters
     * @param WP_REST_Request $request REST request
     *
     * @return WP_REST_Response
     */
    public static function getGateways(array $params, WP_REST_Request $request): WP_REST_Response
    {
        $tenantId = TenantManager::getCurrentTenantId();
        $gateways = GatewayManager::getGatewaysSummary($tenantId);

        return Response::success(['gateways' => $gateways]);
    }

    /**
     * Get or update gateway configuration
     *
     * GET/POST /wp-json/bookando/v1/finance/payment/gateways/{gateway_id}/config
     *
     * @param array           $params Route parameters
     * @param WP_REST_Request $request REST request
     *
     * @return WP_REST_Response
     */
    public static function gatewayConfig(array $params, WP_REST_Request $request): WP_REST_Response
    {
        $gatewayId = $params['gateway_id'] ?? '';
        $tenantId = TenantManager::getCurrentTenantId();

        if (empty($gatewayId)) {
            return Response::error([
                'code' => 'missing_gateway_id',
                'message' => __('Gateway ID is required', 'bookando'),
            ], 400);
        }

        // GET - Retrieve configuration
        if ($request->get_method() === 'GET') {
            $config = GatewayManager::getGatewayConfig($gatewayId, $tenantId);
            $gateway = GatewayManager::getGateway($gatewayId, $tenantId);

            if (!$gateway) {
                return Response::error([
                    'code' => 'gateway_not_found',
                    'message' => __('Gateway not found', 'bookando'),
                ], 404);
            }

            // Remove sensitive data
            $safeConfig = $config;
            $fields = $gateway->getConfigurationFields();
            foreach ($fields as $key => $field) {
                if (($field['type'] ?? '') === 'password' && isset($safeConfig[$key])) {
                    $safeConfig[$key] = str_repeat('*', 8);
                }
            }

            return Response::success([
                'gateway_id' => $gatewayId,
                'config' => $safeConfig,
                'fields' => $fields,
            ]);
        }

        // POST - Update configuration
        $body = $request->get_json_params();
        if (!is_array($body)) {
            return Response::error([
                'code' => 'invalid_json',
                'message' => __('Invalid JSON payload', 'bookando'),
            ], 400);
        }

        $success = GatewayManager::saveGatewayConfig($gatewayId, $body, $tenantId);

        if (!$success) {
            return Response::error([
                'code' => 'save_failed',
                'message' => __('Failed to save configuration', 'bookando'),
            ], 500);
        }

        return Response::success([
            'message' => __('Configuration saved successfully', 'bookando'),
            'gateway_id' => $gatewayId,
        ]);
    }

    /**
     * Test gateway connection
     *
     * POST /wp-json/bookando/v1/finance/payment/gateways/{gateway_id}/test
     *
     * @param array           $params Route parameters
     * @param WP_REST_Request $request REST request
     *
     * @return WP_REST_Response
     */
    public static function testGateway(array $params, WP_REST_Request $request): WP_REST_Response
    {
        $gatewayId = $params['gateway_id'] ?? '';
        $tenantId = TenantManager::getCurrentTenantId();

        if (empty($gatewayId)) {
            return Response::error([
                'code' => 'missing_gateway_id',
                'message' => __('Gateway ID is required', 'bookando'),
            ], 400);
        }

        $gateway = GatewayManager::getGateway($gatewayId, $tenantId);

        if (!$gateway) {
            return Response::error([
                'code' => 'gateway_not_found',
                'message' => __('Gateway not found', 'bookando'),
            ], 404);
        }

        $result = $gateway->testConnection();

        if ($result['success']) {
            return Response::success($result);
        }

        return Response::error([
            'code' => 'test_failed',
            'message' => $result['message'] ?? __('Connection test failed', 'bookando'),
            'details' => $result,
        ], 400);
    }

    /**
     * Create a payment
     *
     * POST /wp-json/bookando/v1/finance/payment/create
     *
     * Body:
     * {
     *   "gateway": "stripe|paypal|klarna|twint",
     *   "amount": 1000,  // in smallest unit (cents/rappen)
     *   "currency": "CHF",
     *   "customer_id": 123,
     *   "customer_email": "user@example.com",
     *   "description": "Payment description",
     *   "success_url": "https://...",
     *   "cancel_url": "https://...",
     *   "metadata": {...}
     * }
     *
     * @param array           $params Route parameters
     * @param WP_REST_Request $request REST request
     *
     * @return WP_REST_Response
     */
    public static function createPayment(array $params, WP_REST_Request $request): WP_REST_Response
    {
        $body = $request->get_json_params();
        if (!is_array($body)) {
            return Response::error([
                'code' => 'invalid_json',
                'message' => __('Invalid JSON payload', 'bookando'),
            ], 400);
        }

        $gatewayId = $body['gateway'] ?? '';
        $tenantId = TenantManager::getCurrentTenantId();

        if (empty($gatewayId)) {
            return Response::error([
                'code' => 'missing_gateway',
                'message' => __('Gateway is required', 'bookando'),
            ], 400);
        }

        $gateway = GatewayManager::getGateway($gatewayId, $tenantId);

        if (!$gateway || !$gateway->isEnabled() || !$gateway->isConfigured()) {
            return Response::error([
                'code' => 'gateway_not_available',
                'message' => __('Gateway is not available', 'bookando'),
            ], 400);
        }

        // Validate required fields
        $required = ['amount', 'currency', 'success_url'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return Response::error([
                    'code' => 'missing_field',
                    'message' => sprintf(__('Missing required field: %s', 'bookando'), $field),
                ], 400);
            }
        }

        // Add tenant_id to metadata
        if (!isset($body['metadata'])) {
            $body['metadata'] = [];
        }
        $body['metadata']['tenant_id'] = (string) $tenantId;

        $result = $gateway->createPayment($body);

        if (!$result['success']) {
            return Response::error([
                'code' => 'payment_creation_failed',
                'message' => $result['error'] ?? __('Payment creation failed', 'bookando'),
                'details' => $result,
            ], 400);
        }

        return Response::success([
            'payment' => $result,
            'gateway' => $gatewayId,
        ]);
    }

    /**
     * Capture a payment
     *
     * POST /wp-json/bookando/v1/finance/payment/{payment_id}/capture
     *
     * @param array           $params Route parameters
     * @param WP_REST_Request $request REST request
     *
     * @return WP_REST_Response
     */
    public static function capturePayment(array $params, WP_REST_Request $request): WP_REST_Response
    {
        $paymentId = $params['payment_id'] ?? '';
        $body = $request->get_json_params() ?? [];

        if (empty($paymentId)) {
            return Response::error([
                'code' => 'missing_payment_id',
                'message' => __('Payment ID is required', 'bookando'),
            ], 400);
        }

        $gatewayId = $body['gateway'] ?? '';
        $tenantId = TenantManager::getCurrentTenantId();

        if (empty($gatewayId)) {
            return Response::error([
                'code' => 'missing_gateway',
                'message' => __('Gateway is required', 'bookando'),
            ], 400);
        }

        $gateway = GatewayManager::getGateway($gatewayId, $tenantId);

        if (!$gateway) {
            return Response::error([
                'code' => 'gateway_not_found',
                'message' => __('Gateway not found', 'bookando'),
            ], 404);
        }

        $result = $gateway->capturePayment($paymentId, $body);

        if (!$result['success']) {
            return Response::error([
                'code' => 'capture_failed',
                'message' => $result['error'] ?? __('Payment capture failed', 'bookando'),
                'details' => $result,
            ], 400);
        }

        return Response::success($result);
    }

    /**
     * Refund a payment
     *
     * POST /wp-json/bookando/v1/finance/payment/{payment_id}/refund
     *
     * Body:
     * {
     *   "gateway": "stripe",
     *   "amount": 500,  // optional, full refund if not specified
     *   "reason": "Customer requested refund"
     * }
     *
     * @param array           $params Route parameters
     * @param WP_REST_Request $request REST request
     *
     * @return WP_REST_Response
     */
    public static function refundPayment(array $params, WP_REST_Request $request): WP_REST_Response
    {
        $paymentId = $params['payment_id'] ?? '';
        $body = $request->get_json_params() ?? [];

        if (empty($paymentId)) {
            return Response::error([
                'code' => 'missing_payment_id',
                'message' => __('Payment ID is required', 'bookando'),
            ], 400);
        }

        $gatewayId = $body['gateway'] ?? '';
        $tenantId = TenantManager::getCurrentTenantId();

        if (empty($gatewayId)) {
            return Response::error([
                'code' => 'missing_gateway',
                'message' => __('Gateway is required', 'bookando'),
            ], 400);
        }

        $gateway = GatewayManager::getGateway($gatewayId, $tenantId);

        if (!$gateway) {
            return Response::error([
                'code' => 'gateway_not_found',
                'message' => __('Gateway not found', 'bookando'),
            ], 404);
        }

        $amount = (int) ($body['amount'] ?? 0);
        $reason = $body['reason'] ?? '';

        $result = $gateway->refundPayment($paymentId, $amount, $reason);

        if (!$result['success']) {
            return Response::error([
                'code' => 'refund_failed',
                'message' => $result['error'] ?? __('Payment refund failed', 'bookando'),
                'details' => $result,
            ], 400);
        }

        return Response::success($result);
    }

    /**
     * Get payment status
     *
     * GET /wp-json/bookando/v1/finance/payment/{payment_id}/status?gateway=stripe
     *
     * @param array           $params Route parameters
     * @param WP_REST_Request $request REST request
     *
     * @return WP_REST_Response
     */
    public static function paymentStatus(array $params, WP_REST_Request $request): WP_REST_Response
    {
        $paymentId = $params['payment_id'] ?? '';
        $gatewayId = $request->get_param('gateway') ?? '';
        $tenantId = TenantManager::getCurrentTenantId();

        if (empty($paymentId)) {
            return Response::error([
                'code' => 'missing_payment_id',
                'message' => __('Payment ID is required', 'bookando'),
            ], 400);
        }

        if (empty($gatewayId)) {
            return Response::error([
                'code' => 'missing_gateway',
                'message' => __('Gateway is required', 'bookando'),
            ], 400);
        }

        $gateway = GatewayManager::getGateway($gatewayId, $tenantId);

        if (!$gateway) {
            return Response::error([
                'code' => 'gateway_not_found',
                'message' => __('Gateway not found', 'bookando'),
            ], 404);
        }

        $result = $gateway->getPaymentStatus($paymentId);

        if (!$result['success']) {
            return Response::error([
                'code' => 'status_retrieval_failed',
                'message' => $result['error'] ?? __('Failed to retrieve payment status', 'bookando'),
                'details' => $result,
            ], 400);
        }

        return Response::success($result);
    }
}
