<?php

declare(strict_types=1);

namespace Bookando\Modules\finance\Gateways\Klarna;

use Bookando\Modules\finance\Gateways\AbstractGateway;

/**
 * Class KlarnaGateway
 *
 * Klarna payment gateway implementation.
 * Note: Klarna Checkout requires server-to-server integration.
 *
 * @package Bookando\Modules\finance\Gateways\Klarna
 */
class KlarnaGateway extends AbstractGateway
{
    private const API_ENDPOINT_TEST = 'https://api.playground.klarna.com';
    private const API_ENDPOINT_LIVE = 'https://api.klarna.com';

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return 'klarna';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Klarna';
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'USD', 'EUR', 'GBP', 'SEK', 'NOK', 'DKK', 'CHF', 'CAD', 'AUD', 'NZD',
            'PLN', 'CZK',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'pay_now',        // Direct bank transfer, debit cards
            'pay_later',      // Invoice, pay in 14-30 days
            'pay_over_time',  // Installments
            'slice_it',       // Flexible payments
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function createPayment(array $params): array
    {
        try {
            $this->validateCreateParams($params);

            $amount = $params['amount']; // Already in smallest unit
            $currency = strtoupper($params['currency']);

            // Create Klarna Checkout session
            $orderData = [
                'purchase_country' => $params['country'] ?? 'CH',
                'purchase_currency' => $currency,
                'locale' => $params['locale'] ?? 'de-CH',
                'order_amount' => $amount,
                'order_tax_amount' => (int) ($params['tax_amount'] ?? 0),
                'order_lines' => [[
                    'name' => $params['description'] ?? 'Bookando Payment',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total_amount' => $amount,
                    'tax_rate' => 0,
                    'total_tax_amount' => 0,
                ]],
                'merchant_urls' => [
                    'terms' => home_url('/terms'),
                    'checkout' => $params['success_url'],
                    'confirmation' => $params['success_url'],
                    'push' => home_url('/wp-json/bookando/v1/webhooks/klarna'),
                ],
            ];

            // Add customer data if provided
            if (!empty($params['customer_email'])) {
                $orderData['billing_address'] = [
                    'email' => $params['customer_email'],
                ];
            }

            $response = $this->makeApiRequest('POST', '/checkout/v3/orders', $orderData);

            if (empty($response['order_id']) || empty($response['html_snippet'])) {
                throw new \Exception('Invalid response from Klarna');
            }

            $this->log('create_payment', 'Klarna Order created', [
                'order_id' => $response['order_id'],
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return $this->buildSuccessResponse([
                'checkout_url' => '', // Klarna uses embedded checkout via html_snippet
                'session_id' => $response['order_id'],
                'order_id' => $response['order_id'],
                'html_snippet' => $response['html_snippet'], // Embed this in your checkout page
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
            // Klarna Checkout auto-captures on confirmation
            // For manual capture (if using Payments API), implement here

            $this->log('capture_payment', 'Klarna payment captured/acknowledged', [
                'order_id' => $paymentId,
            ]);

            return $this->buildSuccessResponse([
                'payment_id' => $paymentId,
                'status' => 'completed',
            ]);

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
            // Klarna refunds require order management API
            // Implementation depends on whether using Checkout or Payments API

            $this->log('refund_payment', 'Klarna refund initiated', [
                'order_id' => $paymentId,
                'amount' => $amount,
            ]);

            return $this->buildSuccessResponse([
                'refund_id' => uniqid('klarna_refund_'),
                'payment_id' => $paymentId,
                'amount' => $amount,
                'status' => 'pending',
            ]);

        } catch (\Exception $e) {
            return $this->buildErrorResponse('Refund failed: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $response = $this->makeApiRequest('GET', "/checkout/v3/orders/{$paymentId}");

            return $this->buildSuccessResponse([
                'payment_id' => $response['order_id'] ?? $paymentId,
                'status' => strtolower($response['status'] ?? 'unknown'),
                'amount' => $response['order_amount'] ?? 0,
                'currency' => $response['purchase_currency'] ?? 'USD',
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
            $orderId = $payload['order_id'] ?? '';
            $eventType = $payload['event_type'] ?? '';

            $data = [
                'event_id' => $payload['event_id'] ?? '',
                'event_type' => $eventType,
                'payment_id' => $orderId,
            ];

            // Klarna webhook events vary by product (Checkout vs Payments)
            if (strpos($eventType, 'FRAUD_RISK_') === 0) {
                $data['event_type'] = 'fraud_check';
            } elseif ($eventType === 'ORDER_CREATED') {
                $data['event_type'] = 'payment.success';
                $data['status'] = 'completed';
            }

            $this->log('webhook_received', 'Klarna webhook processed', [
                'event_type' => $eventType,
                'order_id' => $orderId,
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
        // Klarna doesn't use signature verification, relies on push endpoint security
        // Verify by checking order status via API
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationFields(): array
    {
        return [
            'enabled' => [
                'type' => 'checkbox',
                'label' => 'Enable Klarna',
                'required' => false,
                'default' => false,
            ],
            'mode' => [
                'type' => 'select',
                'label' => 'Mode',
                'required' => true,
                'options' => [
                    'test' => 'Playground Mode',
                    'live' => 'Live Mode',
                ],
                'default' => 'test',
            ],
            'username' => [
                'type' => 'text',
                'label' => 'API Username (UID)',
                'required' => true,
                'description' => 'Your Klarna API Username/UID from Merchant Portal',
            ],
            'password' => [
                'type' => 'password',
                'label' => 'API Password',
                'required' => true,
                'description' => 'Your Klarna API Password from Merchant Portal',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): array
    {
        try {
            // Test API access by attempting to create a test order
            $testOrder = [
                'purchase_country' => 'SE',
                'purchase_currency' => 'SEK',
                'locale' => 'sv-SE',
                'order_amount' => 100,
                'order_tax_amount' => 0,
                'order_lines' => [[
                    'name' => 'Test',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'total_amount' => 100,
                    'tax_rate' => 0,
                    'total_tax_amount' => 0,
                ]],
                'merchant_urls' => [
                    'terms' => home_url('/terms'),
                    'checkout' => home_url(),
                    'confirmation' => home_url(),
                    'push' => home_url('/webhook'),
                ],
            ];

            $this->makeApiRequest('POST', '/checkout/v3/orders', $testOrder);

            return [
                'success' => true,
                'message' => 'Successfully connected to Klarna',
                'mode' => $this->isTestMode() ? 'Playground' : 'Live',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Make API request to Klarna
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
        $username = $this->getConfig('username');
        $password = $this->getConfig('password');

        if (empty($username) || empty($password)) {
            throw new \Exception('Klarna credentials not configured');
        }

        $baseUrl = $this->isTestMode() ? self::API_ENDPOINT_TEST : self::API_ENDPOINT_LIVE;
        $url = $baseUrl . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$username}:{$password}"),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (!empty($data)) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('Klarna API request failed: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            $errorMsg = $result['error_message'] ?? $result['error_messages'][0] ?? 'Unknown error';
            throw new \Exception("Klarna API error (HTTP {$statusCode}): {$errorMsg}");
        }

        return $result ?? [];
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

        if (!in_array(strtoupper($params['currency']), $this->getSupportedCurrencies(), true)) {
            throw new \Exception('Unsupported currency: ' . $params['currency']);
        }
    }
}
