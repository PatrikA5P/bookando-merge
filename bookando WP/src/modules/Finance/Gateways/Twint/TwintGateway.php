<?php

declare(strict_types=1);

namespace Bookando\Modules\Finance\Gateways\Twint;

use Bookando\Modules\Finance\Gateways\AbstractGateway;

/**
 * Class TwintGateway
 *
 * Twint payment gateway implementation (Swiss mobile payment solution).
 * Uses Twint Merchant API (requires contract with Twint/Swiss Payment Provider).
 *
 * @package Bookando\Modules\Finance\Gateways\Twint
 */
class TwintGateway extends AbstractGateway
{
    private const API_ENDPOINT_TEST = 'https://api.test.twint.ch';
    private const API_ENDPOINT_LIVE = 'https://api.twint.ch';

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return 'twint';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'TWINT';
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedCurrencies(): array
    {
        // TWINT only supports CHF
        return ['CHF'];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'twint_app',      // TWINT App payment
            'twint_beacon',   // TWINT Beacon (in-store)
            'twint_qr',       // QR Code payment
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function createPayment(array $params): array
    {
        try {
            $this->validateCreateParams($params);

            $amount = $params['amount']; // In Rappen (smallest unit)
            $currency = 'CHF'; // TWINT only supports CHF

            // Convert to CHF (from Rappen)
            $amountChf = $this->parseAmount($amount, $currency);

            // Create TWINT payment order
            $orderData = [
                'merchantId' => $this->getConfig('merchant_id'),
                'merchantTransactionReference' => $this->generateTransactionReference(),
                'amount' => [
                    'value' => number_format($amountChf, 2, '.', ''),
                    'currency' => $currency,
                ],
                'type' => 'APP',  // Payment type: APP, BEACON, QR
                'customerInfo' => [
                    'email' => $params['customer_email'] ?? null,
                ],
                'callbackUrl' => home_url('/wp-json/bookando/v1/webhooks/twint'),
                'successUrl' => $params['success_url'],
                'cancelUrl' => $params['cancel_url'] ?? $params['success_url'],
            ];

            if (!empty($params['description'])) {
                $orderData['reason'] = substr($params['description'], 0, 35); // Max 35 chars
            }

            $response = $this->makeApiRequest('POST', '/v1/orders', $orderData);

            if (empty($response['orderId'])) {
                throw new \Exception('Invalid response from TWINT');
            }

            $this->log('create_payment', 'TWINT Order created', [
                'order_id' => $response['orderId'],
                'amount' => $amountChf,
                'currency' => $currency,
            ]);

            return $this->buildSuccessResponse([
                'checkout_url' => $response['appLink'] ?? $response['qrCodeUrl'] ?? '',
                'session_id' => $response['orderId'],
                'order_id' => $response['orderId'],
                'qr_code_url' => $response['qrCodeUrl'] ?? null,
                'app_link' => $response['appLink'] ?? null,
                'token' => $response['token'] ?? null,
            ]);

        } catch (\Exception $e) {
            $this->log('create_payment_error', 'Payment creation failed: ' . $e->getMessage(), [], 'ERROR');
            return $this->buildErrorResponse('Payment creation failed: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function capturePayment(string $paymentId, array $params = []): array
    {
        try {
            // TWINT payments are auto-captured when customer confirms in app
            // Check status via API
            $status = $this->getPaymentStatus($paymentId);

            $this->log('capture_payment', 'TWINT payment status checked', [
                'order_id' => $paymentId,
            ]);

            return $status;

        } catch (\Exception $e) {
            return $this->buildErrorResponse('Capture failed: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refundPayment(string $paymentId, int $amount, string $reason = ''): array
    {
        try {
            $amountChf = $this->parseAmount($amount, 'CHF');

            $refundData = [
                'merchantId' => $this->getConfig('merchant_id'),
                'orderId' => $paymentId,
                'amount' => [
                    'value' => number_format($amountChf, 2, '.', ''),
                    'currency' => 'CHF',
                ],
                'reason' => !empty($reason) ? substr($reason, 0, 100) : 'Refund',
            ];

            $response = $this->makeApiRequest('POST', '/v1/refunds', $refundData);

            $this->log('refund_payment', 'TWINT refund initiated', [
                'order_id' => $paymentId,
                'refund_id' => $response['refundId'] ?? '',
                'amount' => $amountChf,
            ]);

            return $this->buildSuccessResponse([
                'refund_id' => $response['refundId'] ?? uniqid('twint_refund_'),
                'payment_id' => $paymentId,
                'amount' => $amount,
                'currency' => 'CHF',
                'status' => strtolower($response['status'] ?? 'pending'),
            ]);

        } catch (\Exception $e) {
            $this->log('refund_payment_error', 'TWINT refund failed: ' . $e->getMessage(), [
                'payment_id' => $paymentId,
            ], 'ERROR');

            return $this->buildErrorResponse('Refund failed: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $response = $this->makeApiRequest('GET', "/v1/orders/{$paymentId}");

            $status = strtolower($response['status'] ?? 'unknown');

            // Map TWINT status to standard status
            $standardStatus = match ($status) {
                'success', 'completed' => 'completed',
                'pending' => 'pending',
                'failed', 'cancelled' => 'failed',
                default => 'unknown',
            };

            return $this->buildSuccessResponse([
                'payment_id' => $paymentId,
                'status' => $standardStatus,
                'amount' => $this->formatAmount(
                    (float) ($response['amount']['value'] ?? 0),
                    'CHF'
                ),
                'currency' => 'CHF',
                'twint_status' => $status,
            ]);

        } catch (\Exception $e) {
            return $this->buildErrorResponse('Failed to retrieve payment: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $orderId = $payload['orderId'] ?? '';
            $status = strtolower($payload['status'] ?? '');

            $data = [
                'event_id' => $payload['eventId'] ?? uniqid('twint_'),
                'payment_id' => $orderId,
            ];

            switch ($status) {
                case 'success':
                case 'completed':
                    $data['event_type'] = 'payment.success';
                    $data['status'] = 'completed';
                    $data['amount'] = $this->formatAmount(
                        (float) ($payload['amount']['value'] ?? 0),
                        'CHF'
                    );
                    $data['currency'] = 'CHF';
                    break;

                case 'failed':
                case 'cancelled':
                    $data['event_type'] = 'payment.failed';
                    $data['status'] = 'failed';
                    break;

                case 'refunded':
                    $data['event_type'] = 'refund.completed';
                    $data['status'] = 'refunded';
                    $data['amount'] = $this->formatAmount(
                        (float) ($payload['amount']['value'] ?? 0),
                        'CHF'
                    );
                    $data['currency'] = 'CHF';
                    break;

                default:
                    $data['event_type'] = 'unknown';
                    break;
            }

            $this->log('webhook_received', 'TWINT webhook processed', [
                'order_id' => $orderId,
                'status' => $status,
            ]);

            return $data;

        } catch (\Exception $e) {
            $this->log('webhook_error', 'Webhook processing failed: ' . $e->getMessage(), [], 'ERROR');
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            $webhookSecret = $this->getConfig('webhook_secret');
            if (empty($webhookSecret)) {
                $this->log('webhook_verification_warning', 'Webhook secret not configured', [], 'WARNING');
                return $this->isTestMode(); // Allow in test mode
            }

            // TWINT uses HMAC-SHA256 for signature verification
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

            return hash_equals($expectedSignature, $signature);

        } catch (\Exception $e) {
            $this->log('webhook_verification_error', 'Webhook verification error: ' . $e->getMessage(), [], 'ERROR');
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationFields(): array
    {
        return [
            'enabled' => [
                'type' => 'checkbox',
                'label' => 'Enable TWINT',
                'required' => false,
                'default' => false,
            ],
            'mode' => [
                'type' => 'select',
                'label' => 'Mode',
                'required' => true,
                'options' => [
                    'test' => 'Test Mode',
                    'live' => 'Live Mode',
                ],
                'default' => 'test',
            ],
            'merchant_id' => [
                'type' => 'text',
                'label' => 'Merchant ID',
                'required' => true,
                'description' => 'Your TWINT Merchant ID from your payment provider',
            ],
            'api_key' => [
                'type' => 'password',
                'label' => 'API Key',
                'required' => true,
                'description' => 'Your TWINT API Key',
            ],
            'api_secret' => [
                'type' => 'password',
                'label' => 'API Secret',
                'required' => true,
                'description' => 'Your TWINT API Secret',
            ],
            'webhook_secret' => [
                'type' => 'password',
                'label' => 'Webhook Secret',
                'required' => false,
                'description' => 'Webhook secret for signature verification',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): array
    {
        try {
            // Test by attempting to retrieve merchant info or creating a test order
            // This is a simplified test - actual implementation depends on TWINT API

            $testOrder = [
                'merchantId' => $this->getConfig('merchant_id'),
                'merchantTransactionReference' => 'TEST_' . time(),
                'amount' => [
                    'value' => '0.01',
                    'currency' => 'CHF',
                ],
                'type' => 'APP',
                'callbackUrl' => home_url('/webhook-test'),
                'successUrl' => home_url('/success-test'),
            ];

            $response = $this->makeApiRequest('POST', '/v1/orders', $testOrder);

            if (!empty($response['orderId'])) {
                // Cancel the test order immediately
                try {
                    $this->makeApiRequest('DELETE', "/v1/orders/{$response['orderId']}");
                } catch (\Exception $e) {
                    // Ignore cancellation errors
                }
            }

            return [
                'success' => true,
                'message' => 'Successfully connected to TWINT',
                'mode' => $this->isTestMode() ? 'Test' : 'Live',
                'merchant_id' => $this->getConfig('merchant_id'),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Make API request to TWINT
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array  $data Request data
     *
     * @return array Response data
     * @throws \Exception
     */
    private function makeApiRequest(string $method, string $endpoint, array $data = []): array
    {
        $apiKey = $this->getConfig('api_key');
        $apiSecret = $this->getConfig('api_secret');

        if (empty($apiKey) || empty($apiSecret)) {
            throw new \Exception('TWINT credentials not configured');
        }

        $baseUrl = $this->isTestMode() ? self::API_ENDPOINT_TEST : self::API_ENDPOINT_LIVE;
        $url = $baseUrl . $endpoint;

        // Generate request signature
        $timestamp = time();
        $nonce = wp_generate_uuid4();
        $signature = $this->generateSignature($method, $endpoint, $data, $timestamp, $nonce);

        $args = [
            'method' => $method,
            'headers' => [
                'X-API-Key' => $apiKey,
                'X-Timestamp' => (string) $timestamp,
                'X-Nonce' => $nonce,
                'X-Signature' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('TWINT API request failed: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $errorMsg = $result['error']['message'] ?? $result['message'] ?? 'Unknown error';
            throw new \Exception("TWINT API error (HTTP {$statusCode}): {$errorMsg}");
        }

        return $result ?? [];
    }

    /**
     * Generate request signature for TWINT API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array  $data Request data
     * @param int    $timestamp Unix timestamp
     * @param string $nonce Unique nonce
     *
     * @return string HMAC signature
     */
    private function generateSignature(string $method, string $endpoint, array $data, int $timestamp, string $nonce): string
    {
        $apiSecret = $this->getConfig('api_secret');

        $payload = $method . "\n" .
                   $endpoint . "\n" .
                   $timestamp . "\n" .
                   $nonce . "\n" .
                   (!empty($data) ? json_encode($data) : '');

        return base64_encode(hash_hmac('sha256', $payload, $apiSecret, true));
    }

    /**
     * Generate unique transaction reference
     *
     * @return string Transaction reference
     */
    private function generateTransactionReference(): string
    {
        return 'BOOKANDO_' . strtoupper(wp_generate_uuid4());
    }

    /**
     * Validate create payment parameters
     *
     * @param array $params Parameters to validate
     *
     * @return void
     * @throws \Exception
     */
    private function validateCreateParams(array $params): void
    {
        $required = ['amount', 'currency', 'success_url'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new \Exception(sprintf('Missing required parameter: %s', $field));
            }
        }

        if (strtoupper($params['currency']) !== 'CHF') {
            throw new \Exception('TWINT only supports CHF currency');
        }

        if ($params['amount'] < 1) {
            throw new \Exception('Amount must be at least 1 Rappen (0.01 CHF)');
        }
    }
}
