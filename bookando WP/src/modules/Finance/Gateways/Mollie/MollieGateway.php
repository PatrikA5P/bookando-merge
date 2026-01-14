<?php

declare(strict_types=1);

namespace Bookando\Modules\Finance\Gateways\Mollie;

use Bookando\Modules\finance\Gateways\AbstractGateway;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Exceptions\ApiException;

/**
 * Class MollieGateway
 *
 * Mollie payment gateway implementation.
 * Popular in Netherlands, Belgium, Germany - supports iDEAL, Bancontact, SEPA, etc.
 *
 * @package Bookando\Modules\finance\Gateways\Mollie
 */
class MollieGateway extends AbstractGateway
{
    /**
     * @var MollieApiClient|null Mollie client instance
     */
    private ?MollieApiClient $client = null;

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return 'mollie';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Mollie';
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'EUR', 'USD', 'GBP', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF',
            'RON', 'BGN', 'ISK', 'CAD', 'AUD', 'NZD', 'HKD', 'SGD', 'JPY',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'ideal',          // iDEAL (Netherlands)
            'bancontact',     // Bancontact (Belgium)
            'creditcard',     // Credit/Debit Cards
            'sofort',         // SOFORT Banking
            'giropay',        // Giropay (Germany)
            'eps',            // EPS (Austria)
            'paypal',         // PayPal
            'sepadirectdebit', // SEPA Direct Debit
            'applepay',       // Apple Pay
            'banktransfer',   // Bank Transfer
            'belfius',        // Belfius (Belgium)
            'kbc',            // KBC/CBC (Belgium)
            'przelewy24',     // Przelewy24 (Poland)
            'klarna',         // Klarna Pay Later/Slice It
            'klarnapaylater',
            'klarnasliceit',
            'in3',            // in3 (Netherlands)
            'voucher',        // Gift cards
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

            // Build payment data
            $paymentData = [
                'amount' => [
                    'currency' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'description' => $params['description'] ?? 'Bookando Payment',
                'redirectUrl' => $params['success_url'],
                'webhookUrl' => home_url('/wp-json/bookando/v1/webhooks/payments/mollie'),
                'metadata' => $this->sanitizeMetadata($params['metadata'] ?? []),
            ];

            // Add cancel URL (Mollie calls it cancelUrl)
            if (!empty($params['cancel_url'])) {
                $paymentData['cancelUrl'] = $params['cancel_url'];
            }

            // Add customer email if provided
            if (!empty($params['customer_email'])) {
                $paymentData['metadata']['customer_email'] = $params['customer_email'];
            }

            // Add specific payment method if requested
            if (!empty($params['payment_method']) && in_array($params['payment_method'], $this->getSupportedPaymentMethods(), true)) {
                $paymentData['method'] = $params['payment_method'];
            } else {
                // Allow customer to choose
                $enabledMethods = $this->getConfig('payment_methods', ['ideal', 'creditcard', 'bancontact']);
                if (!empty($enabledMethods) && is_array($enabledMethods)) {
                    $paymentData['methods'] = $enabledMethods;
                }
            }

            // Add locale
            $paymentData['locale'] = $this->getLocale($params);

            $payment = $this->client->payments->create($paymentData);

            $this->log('create_payment', 'Mollie Payment created', [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return $this->buildSuccessResponse([
                'checkout_url' => $payment->getCheckoutUrl(),
                'session_id' => $payment->id,
                'payment_id' => $payment->id,
                'status' => $payment->status,
            ]);

        } catch (ApiException $e) {
            $this->log('create_payment_error', 'Mollie API error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
            ], 'ERROR');

            return $this->buildErrorResponse('Mollie Error: ' . $e->getMessage(), $e);
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

            // Mollie payments are auto-captured on success
            // Just retrieve status
            $payment = $this->client->payments->get($paymentId);

            $this->log('capture_payment', 'Mollie payment status checked', [
                'payment_id' => $paymentId,
                'status' => $payment->status,
            ]);

            return $this->buildSuccessResponse([
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'amount' => $this->formatAmount((float) $payment->amount->value, $payment->amount->currency),
                'currency' => $payment->amount->currency,
            ]);

        } catch (ApiException $e) {
            $this->log('capture_payment_error', 'Mollie API error: ' . $e->getMessage(), [
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

            $payment = $this->client->payments->get($paymentId);

            $refundData = [];

            if ($amount > 0) {
                $refundData['amount'] = [
                    'currency' => $payment->amount->currency,
                    'value' => number_format($this->parseAmount($amount, $payment->amount->currency), 2, '.', ''),
                ];
            }

            if (!empty($reason)) {
                $refundData['description'] = substr($reason, 0, 255);
            }

            $refund = $payment->refund($refundData);

            $this->log('refund_payment', 'Mollie refund created', [
                'payment_id' => $paymentId,
                'refund_id' => $refund->id,
                'amount' => $refund->amount->value ?? null,
            ]);

            return $this->buildSuccessResponse([
                'refund_id' => $refund->id,
                'payment_id' => $paymentId,
                'amount' => $this->formatAmount((float) ($refund->amount->value ?? 0), $refund->amount->currency),
                'currency' => $refund->amount->currency,
                'status' => $refund->status,
            ]);

        } catch (ApiException $e) {
            $this->log('refund_payment_error', 'Mollie API error: ' . $e->getMessage(), [
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

            $payment = $this->client->payments->get($paymentId);

            return $this->buildSuccessResponse([
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'amount' => $this->formatAmount((float) $payment->amount->value, $payment->amount->currency),
                'currency' => $payment->amount->currency,
                'method' => $payment->method,
                'metadata' => $payment->metadata ? (array) $payment->metadata : [],
            ]);

        } catch (ApiException $e) {
            return $this->buildErrorResponse('Failed to retrieve payment: ' . $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $paymentId = $payload['id'] ?? '';

            if (empty($paymentId)) {
                throw new \Exception('No payment ID in webhook payload');
            }

            $this->initClient();
            $payment = $this->client->payments->get($paymentId);

            $data = [
                'event_id' => uniqid('mollie_'),
                'payment_id' => $payment->id,
                'status' => $payment->status,
            ];

            // Map Mollie status to standard event types
            switch ($payment->status) {
                case 'paid':
                    $data['event_type'] = 'payment.success';
                    $data['amount'] = $this->formatAmount((float) $payment->amount->value, $payment->amount->currency);
                    $data['currency'] = $payment->amount->currency;
                    $data['metadata'] = $payment->metadata ? (array) $payment->metadata : [];
                    break;

                case 'failed':
                case 'canceled':
                case 'expired':
                    $data['event_type'] = 'payment.failed';
                    break;

                case 'refunded':
                case 'partially_refunded':
                    $data['event_type'] = 'refund.completed';
                    $data['amount'] = $this->formatAmount((float) $payment->amountRefunded->value, $payment->amountRefunded->currency);
                    $data['currency'] = $payment->amountRefunded->currency;
                    break;

                default:
                    $data['event_type'] = 'unknown';
                    break;
            }

            $this->log('webhook_received', 'Mollie webhook processed', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
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
        // Mollie doesn't use webhook signatures
        // Instead, we verify by fetching the payment from their API
        // This is done in handleWebhook()
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
                'label' => 'Enable Mollie',
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
            'api_key' => [
                'type' => 'password',
                'label' => 'API Key',
                'required' => true,
                'description' => 'Your Mollie API key (starts with test_ or live_)',
            ],
            'payment_methods' => [
                'type' => 'multiselect',
                'label' => 'Enabled Payment Methods',
                'required' => false,
                'options' => [
                    'ideal' => 'iDEAL',
                    'bancontact' => 'Bancontact',
                    'creditcard' => 'Credit Card',
                    'sofort' => 'SOFORT Banking',
                    'giropay' => 'Giropay',
                    'eps' => 'EPS',
                    'paypal' => 'PayPal',
                    'sepadirectdebit' => 'SEPA Direct Debit',
                    'applepay' => 'Apple Pay',
                    'klarna' => 'Klarna',
                ],
                'default' => ['ideal', 'creditcard', 'bancontact'],
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

            // Test by retrieving payment methods
            $methods = $this->client->methods->allActive();

            return [
                'success' => true,
                'message' => 'Successfully connected to Mollie',
                'mode' => $this->isTestMode() ? 'Test' : 'Live',
                'available_methods' => count($methods),
            ];

        } catch (ApiException $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize Mollie client
     *
     * @return void
     * @throws \Exception
     */
    private function initClient(): void
    {
        if ($this->client !== null) {
            return;
        }

        $apiKey = $this->getConfig('api_key');
        if (empty($apiKey)) {
            throw new \Exception('Mollie API key not configured');
        }

        $this->client = new MollieApiClient();
        $this->client->setApiKey($apiKey);

        // Add user agent for tracking
        $this->client->addVersionString('Bookando/1.0.0');
    }

    /**
     * Get locale for Mollie checkout
     *
     * @param array $params Payment parameters
     *
     * @return string Mollie locale code
     */
    private function getLocale(array $params): string
    {
        // User-provided locale
        if (!empty($params['locale'])) {
            return $params['locale'];
        }

        // WordPress locale
        $wpLocale = get_locale();

        // Map WordPress locale to Mollie locale
        $localeMap = [
            'de_DE' => 'de_DE',
            'de_AT' => 'de_AT',
            'de_CH' => 'de_CH',
            'en_US' => 'en_US',
            'en_GB' => 'en_GB',
            'es_ES' => 'es_ES',
            'fr_FR' => 'fr_FR',
            'fr_BE' => 'fr_BE',
            'it_IT' => 'it_IT',
            'nl_NL' => 'nl_NL',
            'nl_BE' => 'nl_BE',
            'pl_PL' => 'pl_PL',
            'pt_PT' => 'pt_PT',
        ];

        return $localeMap[$wpLocale] ?? 'en_US';
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

        // Mollie minimum amount (depends on currency, but generally 0.01)
        $amount = $this->parseAmount($params['amount'], $params['currency']);
        if ($amount < 0.01) {
            throw new \Exception('Amount too small for Mollie');
        }
    }
}
