<?php

declare(strict_types=1);

namespace Bookando\Modules\finance\Gateways\Stripe;

use Bookando\Modules\finance\Gateways\AbstractGateway;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;

/**
 * Class StripeGateway
 *
 * Stripe payment gateway implementation (PCI-DSS compliant via Stripe Checkout).
 *
 * @package Bookando\Modules\finance\Gateways\Stripe
 */
class StripeGateway extends AbstractGateway
{
    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return 'stripe';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Stripe';
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'USD', 'EUR', 'GBP', 'CHF', 'CAD', 'AUD', 'JPY', 'CNY', 'SEK', 'NOK', 'DKK',
            'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'HRK', 'ISK', 'TRY', 'BRL', 'MXN', 'SGD',
            'HKD', 'NZD', 'KRW', 'INR', 'RUB', 'ZAR', 'AED', 'SAR', 'QAR', 'KWD', 'BHD',
            'OMR', 'JOD', 'ILS', 'MYR', 'THB', 'PHP', 'IDR', 'VND',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'card',          // Credit/Debit Cards
            'sepa_debit',    // SEPA Direct Debit
            'sofort',        // Sofort (DEPRECATED, use 'klarna')
            'giropay',       // Giropay
            'ideal',         // iDEAL
            'bancontact',    // Bancontact
            'eps',           // EPS
            'p24',           // Przelewy24
            'alipay',        // Alipay
            'wechat_pay',    // WeChat Pay
            'klarna',        // Klarna
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function createPayment(array $params): array
    {
        try {
            $this->initStripe();

            // Validate required parameters
            $this->validateCreateParams($params);

            $amount = $params['amount']; // Already in smallest unit (cents)
            $currency = strtolower($params['currency']);

            // Build Checkout Session
            $sessionData = [
                'payment_method_types' => $this->getPaymentMethodTypes($params),
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $params['description'] ?? 'Bookando Payment',
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $params['success_url'],
                'cancel_url' => $params['cancel_url'],
                'client_reference_id' => (string) ($params['customer_id'] ?? ''),
                'customer_email' => $params['customer_email'] ?? null,
                'metadata' => $this->sanitizeMetadata($params['metadata'] ?? []),
            ];

            // Add customer if exists
            if (!empty($params['stripe_customer_id'])) {
                $sessionData['customer'] = $params['stripe_customer_id'];
                unset($sessionData['customer_email']); // Can't set both
            }

            $session = Session::create($sessionData);

            $this->log('create_payment', 'Stripe Checkout Session created', [
                'session_id' => $session->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return $this->buildSuccessResponse([
                'checkout_url' => $session->url,
                'session_id' => $session->id,
                'payment_intent_id' => $session->payment_intent,
            ]);

        } catch (ApiErrorException $e) {
            $this->log('create_payment_error', 'Stripe API error: ' . $e->getMessage(), [
                'error_code' => $e->getError()->code ?? '',
                'error_type' => $e->getError()->type ?? '',
            ], 'ERROR');

            return $this->buildErrorResponse('Stripe Error: ' . $e->getMessage(), $e);
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
            $this->initStripe();

            $intent = PaymentIntent::retrieve($paymentId);

            if ($intent->status === 'requires_capture') {
                $intent->capture();
            }

            $this->log('capture_payment', 'Payment captured', [
                'payment_intent_id' => $paymentId,
                'status' => $intent->status,
            ]);

            return $this->buildSuccessResponse([
                'payment_id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
            ]);

        } catch (ApiErrorException $e) {
            $this->log('capture_payment_error', 'Stripe API error: ' . $e->getMessage(), [
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
            $this->initStripe();

            $refundData = [
                'payment_intent' => $paymentId,
            ];

            if ($amount > 0) {
                $refundData['amount'] = $amount;
            }

            if (!empty($reason)) {
                $refundData['reason'] = $this->mapRefundReason($reason);
                $refundData['metadata'] = ['reason_description' => $reason];
            }

            $refund = Refund::create($refundData);

            $this->log('refund_payment', 'Payment refunded', [
                'payment_intent_id' => $paymentId,
                'refund_id' => $refund->id,
                'amount' => $refund->amount,
            ]);

            return $this->buildSuccessResponse([
                'refund_id' => $refund->id,
                'payment_id' => $paymentId,
                'amount' => $refund->amount,
                'currency' => $refund->currency,
                'status' => $refund->status,
            ]);

        } catch (ApiErrorException $e) {
            $this->log('refund_payment_error', 'Stripe API error: ' . $e->getMessage(), [
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
            $this->initStripe();

            $intent = PaymentIntent::retrieve($paymentId);

            return $this->buildSuccessResponse([
                'payment_id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'customer' => $intent->customer,
                'metadata' => $intent->metadata->toArray(),
            ]);

        } catch (ApiErrorException $e) {
            return $this->buildErrorResponse('Failed to retrieve payment: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $event = \Stripe\Event::constructFrom($payload);

            $data = [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ];

            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $data = array_merge($data, [
                        'event_type' => 'payment.success',
                        'payment_id' => $session->payment_intent,
                        'session_id' => $session->id,
                        'status' => 'completed',
                        'amount' => $session->amount_total,
                        'currency' => $session->currency,
                        'customer_id' => $session->client_reference_id,
                        'customer_email' => $session->customer_email,
                        'metadata' => $session->metadata->toArray(),
                    ]);
                    break;

                case 'payment_intent.succeeded':
                    $intent = $event->data->object;
                    $data = array_merge($data, [
                        'event_type' => 'payment.success',
                        'payment_id' => $intent->id,
                        'status' => 'succeeded',
                        'amount' => $intent->amount,
                        'currency' => $intent->currency,
                        'metadata' => $intent->metadata->toArray(),
                    ]);
                    break;

                case 'payment_intent.payment_failed':
                    $intent = $event->data->object;
                    $data = array_merge($data, [
                        'event_type' => 'payment.failed',
                        'payment_id' => $intent->id,
                        'status' => 'failed',
                        'amount' => $intent->amount,
                        'currency' => $intent->currency,
                        'error_message' => $intent->last_payment_error?->message ?? 'Payment failed',
                        'metadata' => $intent->metadata->toArray(),
                    ]);
                    break;

                case 'charge.refunded':
                    $charge = $event->data->object;
                    $data = array_merge($data, [
                        'event_type' => 'refund.completed',
                        'payment_id' => $charge->payment_intent,
                        'charge_id' => $charge->id,
                        'status' => 'refunded',
                        'amount' => $charge->amount_refunded,
                        'currency' => $charge->currency,
                    ]);
                    break;

                default:
                    $data['event_type'] = 'unknown';
                    break;
            }

            $this->log('webhook_received', 'Stripe webhook processed', [
                'event_type' => $event->type,
                'event_id' => $event->id,
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
            $this->initStripe();

            $webhookSecret = $this->getConfig('webhook_secret');
            if (empty($webhookSecret)) {
                throw new \Exception('Webhook secret not configured');
            }

            Webhook::constructEvent($payload, $signature, $webhookSecret);

            return true;

        } catch (SignatureVerificationException $e) {
            $this->log('webhook_signature_verification_failed', 'Invalid webhook signature', [], 'WARNING');
            return false;
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
                'label' => 'Enable Stripe',
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
            'publishable_key' => [
                'type' => 'text',
                'label' => 'Publishable Key',
                'required' => true,
                'description' => 'Your Stripe publishable key (starts with pk_)',
            ],
            'secret_key' => [
                'type' => 'password',
                'label' => 'Secret Key',
                'required' => true,
                'description' => 'Your Stripe secret key (starts with sk_)',
            ],
            'webhook_secret' => [
                'type' => 'password',
                'label' => 'Webhook Signing Secret',
                'required' => true,
                'description' => 'Webhook signing secret from Stripe Dashboard (starts with whsec_)',
            ],
            'payment_methods' => [
                'type' => 'multiselect',
                'label' => 'Enabled Payment Methods',
                'required' => false,
                'options' => [
                    'card' => 'Credit/Debit Cards',
                    'sepa_debit' => 'SEPA Direct Debit',
                    'giropay' => 'Giropay',
                    'ideal' => 'iDEAL',
                    'bancontact' => 'Bancontact',
                    'eps' => 'EPS',
                    'p24' => 'Przelewy24',
                    'klarna' => 'Klarna',
                ],
                'default' => ['card'],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): array
    {
        try {
            $this->initStripe();

            // Try to retrieve account info
            $account = \Stripe\Account::retrieve();

            return [
                'success' => true,
                'message' => 'Successfully connected to Stripe',
                'account_id' => $account->id,
                'account_name' => $account->business_profile->name ?? $account->email,
                'currency' => $account->default_currency,
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize Stripe SDK
     *
     * @return void
     * @throws \Exception
     */
    private function initStripe(): void
    {
        $secretKey = $this->getConfig('secret_key');
        if (empty($secretKey)) {
            throw new \Exception('Stripe secret key not configured');
        }

        Stripe::setApiKey($secretKey);
        Stripe::setApiVersion('2023-10-16'); // Use latest stable API version
        Stripe::setAppInfo(
            'Bookando WordPress Plugin',
            '1.0.0',
            'https://bookando.com',
            'pp_partner_' . hash('sha256', home_url())
        );
    }

    /**
     * Get payment method types for session
     *
     * @param array $params Payment parameters
     *
     * @return array Payment method types
     */
    private function getPaymentMethodTypes(array $params): array
    {
        $configuredMethods = $this->getConfig('payment_methods', ['card']);

        // If specific method requested, use it
        if (!empty($params['payment_method'])) {
            return [$params['payment_method']];
        }

        return $configuredMethods;
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

        if ($params['amount'] < 50) { // Stripe minimum
            throw new \Exception('Amount must be at least 50 cents');
        }

        if (!in_array(strtoupper($params['currency']), $this->getSupportedCurrencies(), true)) {
            throw new \Exception('Unsupported currency: ' . $params['currency']);
        }
    }

    /**
     * Map refund reason to Stripe reason
     *
     * @param string $reason Custom reason
     *
     * @return string Stripe reason
     */
    private function mapRefundReason(string $reason): string
    {
        $reason = strtolower($reason);

        if (strpos($reason, 'duplicate') !== false) {
            return 'duplicate';
        }
        if (strpos($reason, 'fraud') !== false) {
            return 'fraudulent';
        }

        return 'requested_by_customer';
    }
}
