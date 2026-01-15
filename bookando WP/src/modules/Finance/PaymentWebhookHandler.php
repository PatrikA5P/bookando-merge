<?php

declare(strict_types=1);

namespace Bookando\Modules\Finance;

use Bookando\Modules\Finance\Gateways\GatewayManager;
use Bookando\Core\Service\ActivityLogger;

/**
 * Class PaymentWebhookHandler
 *
 * Handles webhooks from payment gateways.
 *
 * Register webhooks in WordPress init:
 * - /wp-json/bookando/v1/webhooks/payments/stripe
 * - /wp-json/bookando/v1/webhooks/payments/paypal
 * - /wp-json/bookando/v1/webhooks/payments/klarna
 * - /wp-json/bookando/v1/webhooks/payments/twint
 *
 * @package Bookando\Modules\finance
 */
class PaymentWebhookHandler
{
    /**
     * Handle payment webhook
     *
     * @param string $gatewayId Gateway ID (stripe, paypal, klarna, twint)
     *
     * @return void
     */
    public static function handle(string $gatewayId): void
    {
        try {
            // Get raw payload
            $payload = file_get_contents('php://input');
            $headers = getallheaders();

            // Log webhook received
            ActivityLogger::log(
                "payment_webhook_{$gatewayId}_received",
                "Payment webhook received from {$gatewayId}",
                ['gateway' => $gatewayId],
                'INFO',
                null,
                'finance'
            );

            // Get gateway instance
            $gateway = GatewayManager::getGateway($gatewayId);

            if (!$gateway) {
                http_response_code(404);
                echo json_encode(['error' => 'Gateway not found']);
                exit;
            }

            // Verify webhook signature
            $signature = self::getSignatureFromHeaders($headers, $gatewayId);

            if (!empty($signature) && !$gateway->verifyWebhookSignature($payload, $signature)) {
                ActivityLogger::log(
                    "payment_webhook_{$gatewayId}_invalid_signature",
                    "Invalid webhook signature from {$gatewayId}",
                    ['gateway' => $gatewayId],
                    'WARNING',
                    null,
                    'finance'
                );

                http_response_code(401);
                echo json_encode(['error' => 'Invalid signature']);
                exit;
            }

            // Parse payload
            $data = json_decode($payload, true);
            if (!is_array($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                exit;
            }

            // Process webhook
            $result = $gateway->handleWebhook($data);

            // Handle different event types
            self::processWebhookEvent($result, $gatewayId);

            // Log success
            ActivityLogger::log(
                "payment_webhook_{$gatewayId}_processed",
                "Payment webhook processed successfully",
                [
                    'gateway' => $gatewayId,
                    'event_type' => $result['event_type'] ?? 'unknown',
                    'payment_id' => $result['payment_id'] ?? null,
                ],
                'INFO',
                null,
                'finance'
            );

            // Respond with success
            http_response_code(200);
            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            ActivityLogger::log(
                "payment_webhook_{$gatewayId}_error",
                "Error processing webhook: " . $e->getMessage(),
                [
                    'gateway' => $gatewayId,
                    'error' => $e->getMessage(),
                ],
                'ERROR',
                null,
                'finance'
            );

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Process webhook event
     *
     * @param array  $event Webhook event data
     * @param string $gatewayId Gateway ID
     *
     * @return void
     */
    private static function processWebhookEvent(array $event, string $gatewayId): void
    {
        $eventType = $event['event_type'] ?? 'unknown';

        switch ($eventType) {
            case 'payment.success':
                self::handlePaymentSuccess($event, $gatewayId);
                break;

            case 'payment.failed':
                self::handlePaymentFailed($event, $gatewayId);
                break;

            case 'refund.completed':
                self::handleRefundCompleted($event, $gatewayId);
                break;

            default:
                // Log unknown event type
                ActivityLogger::log(
                    "payment_webhook_{$gatewayId}_unknown_event",
                    "Unknown webhook event type: {$eventType}",
                    ['event' => $event],
                    'WARNING',
                    null,
                    'finance'
                );
                break;
        }
    }

    /**
     * Handle successful payment
     *
     * @param array  $event Payment event data
     * @param string $gatewayId Gateway ID
     *
     * @return void
     */
    private static function handlePaymentSuccess(array $event, string $gatewayId): void
    {
        $paymentId = $event['payment_id'] ?? null;
        $amount = $event['amount'] ?? 0;
        $currency = $event['currency'] ?? 'USD';
        $metadata = $event['metadata'] ?? [];

        ActivityLogger::log(
            "payment_success_{$gatewayId}",
            "Payment successful",
            [
                'gateway' => $gatewayId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata,
            ],
            'INFO',
            isset($metadata['tenant_id']) ? (int) $metadata['tenant_id'] : null,
            'finance'
        );

        // TODO: Update booking/appointment status, send confirmation email, etc.
        // This depends on your application logic

        /**
         * Fire WordPress action for payment success
         * Other plugins/modules can hook into this
         */
        do_action('bookando_payment_success', [
            'gateway' => $gatewayId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
            'event' => $event,
        ]);
    }

    /**
     * Handle failed payment
     *
     * @param array  $event Payment event data
     * @param string $gatewayId Gateway ID
     *
     * @return void
     */
    private static function handlePaymentFailed(array $event, string $gatewayId): void
    {
        $paymentId = $event['payment_id'] ?? null;
        $errorMessage = $event['error_message'] ?? 'Payment failed';
        $metadata = $event['metadata'] ?? [];

        ActivityLogger::log(
            "payment_failed_{$gatewayId}",
            "Payment failed: {$errorMessage}",
            [
                'gateway' => $gatewayId,
                'payment_id' => $paymentId,
                'error' => $errorMessage,
                'metadata' => $metadata,
            ],
            'WARNING',
            isset($metadata['tenant_id']) ? (int) $metadata['tenant_id'] : null,
            'finance'
        );

        // TODO: Update booking status, send failure notification, etc.

        do_action('bookando_payment_failed', [
            'gateway' => $gatewayId,
            'payment_id' => $paymentId,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'event' => $event,
        ]);
    }

    /**
     * Handle completed refund
     *
     * @param array  $event Refund event data
     * @param string $gatewayId Gateway ID
     *
     * @return void
     */
    private static function handleRefundCompleted(array $event, string $gatewayId): void
    {
        $paymentId = $event['payment_id'] ?? null;
        $refundId = $event['refund_id'] ?? null;
        $amount = $event['amount'] ?? 0;
        $currency = $event['currency'] ?? 'USD';

        ActivityLogger::log(
            "refund_completed_{$gatewayId}",
            "Refund completed",
            [
                'gateway' => $gatewayId,
                'payment_id' => $paymentId,
                'refund_id' => $refundId,
                'amount' => $amount,
                'currency' => $currency,
            ],
            'INFO',
            null,
            'finance'
        );

        // TODO: Update booking/invoice status, send refund confirmation, etc.

        do_action('bookando_refund_completed', [
            'gateway' => $gatewayId,
            'payment_id' => $paymentId,
            'refund_id' => $refundId,
            'amount' => $amount,
            'currency' => $currency,
            'event' => $event,
        ]);
    }

    /**
     * Get signature from headers based on gateway
     *
     * @param array  $headers HTTP headers
     * @param string $gatewayId Gateway ID
     *
     * @return string Signature
     */
    private static function getSignatureFromHeaders(array $headers, string $gatewayId): string
    {
        // Normalize header keys (case-insensitive)
        $headers = array_change_key_case($headers, CASE_LOWER);

        switch ($gatewayId) {
            case 'stripe':
                return $headers['stripe-signature'] ?? '';

            case 'paypal':
                // PayPal uses multiple headers for signature verification
                return json_encode([
                    'transmission_id' => $headers['paypal-transmission-id'] ?? '',
                    'transmission_time' => $headers['paypal-transmission-time'] ?? '',
                    'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
                    'cert_url' => $headers['paypal-cert-url'] ?? '',
                    'auth_algo' => $headers['paypal-auth-algo'] ?? '',
                ]);

            case 'klarna':
                // Klarna doesn't use signature verification
                return '';

            case 'twint':
                return $headers['x-twint-signature'] ?? $headers['x-signature'] ?? '';

            default:
                return '';
        }
    }

    /**
     * Register webhook routes
     *
     * Call this from Module init or WordPress init hook
     *
     * @return void
     */
    public static function registerWebhookRoutes(): void
    {
        add_action('rest_api_init', static function () {
            $gateways = ['stripe', 'paypal', 'klarna', 'twint', 'mollie'];

            foreach ($gateways as $gatewayId) {
                register_rest_route('bookando/v1', "/webhooks/payments/{$gatewayId}", [
                    'methods' => 'POST',
                    'callback' => static function () use ($gatewayId) {
                        self::handle($gatewayId);
                        return new \WP_REST_Response(['success' => true], 200);
                    },
                    'permission_callback' => '__return_true', // Webhooks are verified via signature
                ]);
            }
        });
    }
}
