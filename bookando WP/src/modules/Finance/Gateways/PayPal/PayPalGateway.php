<?php

declare(strict_types=1);

namespace Bookando\Modules\Finance\Gateways\PayPal;

use Bookando\Modules\Finance\Gateways\AbstractGateway;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
use PayPalHttp\HttpException;

/**
 * Class PayPalGateway
 *
 * PayPal payment gateway implementation using PayPal Checkout.
 *
 * @package Bookando\Modules\Finance\Gateways\PayPal
 */
class PayPalGateway extends AbstractGateway
{
    /**
     * @var PayPalHttpClient|null PayPal client instance
     */
    private ?PayPalHttpClient $client = null;

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return 'paypal';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'PayPal';
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SEK', 'NOK', 'DKK',
            'PLN', 'CZK', 'HUF', 'BRL', 'MXN', 'SGD', 'HKD', 'NZD', 'KRW', 'INR',
            'RUB', 'ZAR', 'AED', 'ILS', 'MYR', 'THB', 'PHP', 'TWD',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'paypal',        // PayPal Wallet
            'card',          // Credit/Debit Cards via PayPal
            'venmo',         // Venmo (US only)
            'paylater',      // Pay in 4 / Pay Later
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function createPayment(array $params): array
    {
        try {
            $this->initClient();

            $this->validateCreateParams($params);

            $amount = $this->parseAmount($params['amount'], $params['currency']);
            $currency = strtoupper($params['currency']);

            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) ($params['reference_id'] ?? uniqid('bookando_')),
                    'description' => $params['description'] ?? 'Bookando Payment',
                    'custom_id' => (string) ($params['customer_id'] ?? ''),
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => get_bloginfo('name'),
                    'landing_page' => 'NO_PREFERENCE',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $params['success_url'],
                    'cancel_url' => $params['cancel_url'],
                ],
            ];

            // Add payer info if provided
            if (!empty($params['customer_email'])) {
                $request->body['payer'] = [
                    'email_address' => $params['customer_email'],
                ];
            }

            $response = $this->client->execute($request);

            if ($response->statusCode !== 201) {
                throw new \Exception('Failed to create PayPal order');
            }

            $order = $response->result;

            // Find approval URL
            $approvalUrl = '';
            foreach ($order->links as $link) {
                if ($link->rel === 'approve') {
                    $approvalUrl = $link->href;
                    break;
                }
            }

            $this->log('create_payment', 'PayPal Order created', [
                'order_id' => $order->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return $this->buildSuccessResponse([
                'checkout_url' => $approvalUrl,
                'session_id' => $order->id,
                'order_id' => $order->id,
            ]);

        } catch (HttpException $e) {
            $this->log('create_payment_error', 'PayPal API error: ' . $e->getMessage(), [
                'status_code' => $e->statusCode,
            ], 'ERROR');

            return $this->buildErrorResponse('PayPal Error: ' . $e->getMessage(), $e);
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
            $this->initClient();

            $request = new OrdersCaptureRequest($paymentId);
            $request->prefer('return=representation');

            $response = $this->client->execute($request);

            if ($response->statusCode !== 201) {
                throw new \Exception('Failed to capture PayPal order');
            }

            $order = $response->result;

            $this->log('capture_payment', 'Payment captured', [
                'order_id' => $paymentId,
                'status' => $order->status,
            ]);

            $capture = $order->purchase_units[0]->payments->captures[0] ?? null;

            return $this->buildSuccessResponse([
                'payment_id' => $paymentId,
                'capture_id' => $capture->id ?? null,
                'status' => strtolower($order->status),
                'amount' => $this->formatAmount(
                    (float) ($capture->amount->value ?? 0),
                    $capture->amount->currency_code ?? 'USD'
                ),
                'currency' => $capture->amount->currency_code ?? 'USD',
            ]);

        } catch (HttpException $e) {
            $this->log('capture_payment_error', 'PayPal API error: ' . $e->getMessage(), [
                'payment_id' => $paymentId,
            ], 'ERROR');

            return $this->buildErrorResponse('Capture failed: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refundPayment(string $paymentId, int $amount, string $reason = ''): array
    {
        try {
            $this->initClient();

            // PayPal requires capture ID for refunds, not order ID
            // We need to get the order first to find the capture ID
            $orderRequest = new OrdersGetRequest($paymentId);
            $orderResponse = $this->client->execute($orderRequest);
            $order = $orderResponse->result;

            $captureId = $order->purchase_units[0]->payments->captures[0]->id ?? null;

            if (!$captureId) {
                throw new \Exception('No capture found for this order');
            }

            $request = new CapturesRefundRequest($captureId);
            $request->prefer('return=representation');

            $currency = $order->purchase_units[0]->amount->currency_code;

            if ($amount > 0) {
                $request->body = [
                    'amount' => [
                        'value' => number_format($this->parseAmount($amount, $currency), 2, '.', ''),
                        'currency_code' => $currency,
                    ],
                ];
            }

            if (!empty($reason)) {
                $request->body['note_to_payer'] = $reason;
            }

            $response = $this->client->execute($request);

            if ($response->statusCode !== 201) {
                throw new \Exception('Failed to refund payment');
            }

            $refund = $response->result;

            $this->log('refund_payment', 'Payment refunded', [
                'order_id' => $paymentId,
                'capture_id' => $captureId,
                'refund_id' => $refund->id,
                'amount' => $refund->amount->value,
            ]);

            return $this->buildSuccessResponse([
                'refund_id' => $refund->id,
                'payment_id' => $paymentId,
                'capture_id' => $captureId,
                'amount' => $this->formatAmount((float) $refund->amount->value, $currency),
                'currency' => $currency,
                'status' => strtolower($refund->status),
            ]);

        } catch (HttpException $e) {
            $this->log('refund_payment_error', 'PayPal API error: ' . $e->getMessage(), [
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
            $this->initClient();

            $request = new OrdersGetRequest($paymentId);
            $response = $this->client->execute($request);
            $order = $response->result;

            $capture = $order->purchase_units[0]->payments->captures[0] ?? null;

            return $this->buildSuccessResponse([
                'payment_id' => $order->id,
                'status' => strtolower($order->status),
                'amount' => $this->formatAmount(
                    (float) ($capture->amount->value ?? $order->purchase_units[0]->amount->value),
                    $order->purchase_units[0]->amount->currency_code
                ),
                'currency' => $order->purchase_units[0]->amount->currency_code,
                'payer_email' => $order->payer->email_address ?? null,
                'custom_id' => $order->purchase_units[0]->custom_id ?? null,
            ]);

        } catch (HttpException $e) {
            return $this->buildErrorResponse('Failed to retrieve payment: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $eventType = $payload['event_type'] ?? '';
            $resource = $payload['resource'] ?? [];

            $data = [
                'event_id' => $payload['id'] ?? '',
                'event_type' => $eventType,
            ];

            switch ($eventType) {
                case 'CHECKOUT.ORDER.COMPLETED':
                case 'PAYMENT.CAPTURE.COMPLETED':
                    $data = array_merge($data, [
                        'event_type' => 'payment.success',
                        'payment_id' => $resource['supplementary_data']['related_ids']['order_id'] ?? $resource['id'],
                        'status' => 'completed',
                        'amount' => $this->formatAmount(
                            (float) ($resource['amount']['value'] ?? 0),
                            $resource['amount']['currency_code'] ?? 'USD'
                        ),
                        'currency' => $resource['amount']['currency_code'] ?? 'USD',
                    ]);
                    break;

                case 'PAYMENT.CAPTURE.DENIED':
                case 'CHECKOUT.ORDER.DECLINED':
                    $data = array_merge($data, [
                        'event_type' => 'payment.failed',
                        'payment_id' => $resource['id'] ?? '',
                        'status' => 'failed',
                    ]);
                    break;

                case 'PAYMENT.CAPTURE.REFUNDED':
                    $data = array_merge($data, [
                        'event_type' => 'refund.completed',
                        'payment_id' => $resource['supplementary_data']['related_ids']['order_id'] ?? '',
                        'refund_id' => $resource['id'] ?? '',
                        'status' => 'refunded',
                        'amount' => $this->formatAmount(
                            (float) ($resource['amount']['value'] ?? 0),
                            $resource['amount']['currency_code'] ?? 'USD'
                        ),
                        'currency' => $resource['amount']['currency_code'] ?? 'USD',
                    ]);
                    break;

                default:
                    $data['event_type'] = 'unknown';
                    break;
            }

            $this->log('webhook_received', 'PayPal webhook processed', [
                'event_type' => $eventType,
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
        // PayPal webhook verification requires additional headers and validation
        // This is a simplified version - full implementation would use PayPal's webhook verification API
        try {
            $webhookId = $this->getConfig('webhook_id');
            if (empty($webhookId)) {
                $this->log('webhook_verification_warning', 'Webhook ID not configured', [], 'WARNING');
                // In test mode, allow without verification
                return $this->isTestMode();
            }

            // TODO: Implement full PayPal webhook verification using their API
            // https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature

            return true;

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
                'label' => 'Enable PayPal',
                'required' => false,
                'default' => false,
            ],
            'mode' => [
                'type' => 'select',
                'label' => 'Mode',
                'required' => true,
                'options' => [
                    'test' => 'Sandbox Mode',
                    'live' => 'Live Mode',
                ],
                'default' => 'test',
            ],
            'client_id' => [
                'type' => 'text',
                'label' => 'Client ID',
                'required' => true,
                'description' => 'Your PayPal REST API Client ID',
            ],
            'client_secret' => [
                'type' => 'password',
                'label' => 'Client Secret',
                'required' => true,
                'description' => 'Your PayPal REST API Client Secret',
            ],
            'webhook_id' => [
                'type' => 'text',
                'label' => 'Webhook ID',
                'required' => false,
                'description' => 'Webhook ID from PayPal Developer Dashboard (for signature verification)',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): array
    {
        try {
            $this->initClient();

            // Create a minimal order to test API access
            $request = new OrdersCreateRequest();
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => '1.00',
                    ],
                ]],
            ];

            $response = $this->client->execute($request);

            if ($response->statusCode === 201) {
                return [
                    'success' => true,
                    'message' => 'Successfully connected to PayPal',
                    'mode' => $this->isTestMode() ? 'Sandbox' : 'Live',
                ];
            }

            return [
                'success' => false,
                'message' => 'Unexpected response from PayPal',
            ];

        } catch (HttpException $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize PayPal client
     *
     * @return void
     * @throws \Exception
     */
    private function initClient(): void
    {
        if ($this->client !== null) {
            return;
        }

        $clientId = $this->getConfig('client_id');
        $clientSecret = $this->getConfig('client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('PayPal credentials not configured');
        }

        $environment = $this->isTestMode()
            ? new SandboxEnvironment($clientId, $clientSecret)
            : new ProductionEnvironment($clientId, $clientSecret);

        $this->client = new PayPalHttpClient($environment);
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
        $required = ['amount', 'currency', 'success_url', 'cancel_url'];
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
