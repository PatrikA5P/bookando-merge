<?php

declare(strict_types=1);

namespace Bookando\Modules\finance\Gateways;

/**
 * Interface GatewayInterface
 *
 * Defines the contract for all payment gateways in Bookando.
 * All payment providers must implement this interface.
 *
 * @package Bookando\Modules\finance\Gateways
 */
interface GatewayInterface
{
    /**
     * Get the unique identifier for this gateway
     *
     * @return string Gateway ID (e.g., 'stripe', 'paypal', 'klarna', 'twint')
     */
    public function getId(): string;

    /**
     * Get the human-readable name of this gateway
     *
     * @return string Gateway name
     */
    public function getName(): string;

    /**
     * Check if the gateway is configured and ready to use
     *
     * @return bool True if configured
     */
    public function isConfigured(): bool;

    /**
     * Check if the gateway is enabled
     *
     * @return bool True if enabled
     */
    public function isEnabled(): bool;

    /**
     * Get supported currencies for this gateway
     *
     * @return array Array of currency codes (e.g., ['USD', 'EUR', 'CHF'])
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get supported payment methods
     *
     * @return array Array of payment methods (e.g., ['card', 'sepa_debit', 'klarna'])
     */
    public function getSupportedPaymentMethods(): array;

    /**
     * Create a payment session/checkout
     *
     * @param array $params Payment parameters including:
     *                      - amount: int (in smallest currency unit, e.g., cents)
     *                      - currency: string (e.g., 'EUR', 'CHF')
     *                      - customer_id: int (Bookando customer ID)
     *                      - customer_email: string
     *                      - description: string
     *                      - metadata: array
     *                      - success_url: string
     *                      - cancel_url: string
     *
     * @return array Response with checkout URL and session ID
     *               [
     *                 'success' => true,
     *                 'checkout_url' => 'https://...',
     *                 'session_id' => 'cs_...',
     *                 'payment_intent_id' => 'pi_...' (optional)
     *               ]
     *
     * @throws \Exception If payment creation fails
     */
    public function createPayment(array $params): array;

    /**
     * Capture/confirm a payment
     *
     * @param string $paymentId Payment/transaction ID
     * @param array  $params    Additional parameters
     *
     * @return array Response with payment status
     *
     * @throws \Exception If capture fails
     */
    public function capturePayment(string $paymentId, array $params = []): array;

    /**
     * Refund a payment
     *
     * @param string $paymentId Payment/transaction ID
     * @param int    $amount    Amount to refund (in smallest currency unit)
     * @param string $reason    Refund reason
     *
     * @return array Response with refund status
     *
     * @throws \Exception If refund fails
     */
    public function refundPayment(string $paymentId, int $amount, string $reason = ''): array;

    /**
     * Get payment status
     *
     * @param string $paymentId Payment/transaction ID
     *
     * @return array Payment details and status
     *
     * @throws \Exception If retrieval fails
     */
    public function getPaymentStatus(string $paymentId): array;

    /**
     * Handle webhook from payment provider
     *
     * @param array $payload Raw webhook payload
     *
     * @return array Processed webhook data
     *               [
     *                 'event_type' => 'payment.success|payment.failed|refund.completed',
     *                 'payment_id' => '...',
     *                 'status' => '...',
     *                 'amount' => 1000,
     *                 'currency' => 'EUR',
     *                 'metadata' => [...]
     *               ]
     *
     * @throws \Exception If webhook validation fails
     */
    public function handleWebhook(array $payload): array;

    /**
     * Verify webhook signature
     *
     * @param string $payload  Raw webhook body
     * @param string $signature Signature from headers
     *
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Get gateway configuration requirements
     *
     * @return array Configuration fields needed
     *               [
     *                 'api_key' => ['type' => 'text', 'required' => true, 'label' => '...'],
     *                 'webhook_secret' => ['type' => 'text', 'required' => true, 'label' => '...'],
     *                 ...
     *               ]
     */
    public function getConfigurationFields(): array;

    /**
     * Test the gateway connection/credentials
     *
     * @return array Test result
     *               ['success' => true|false, 'message' => '...']
     */
    public function testConnection(): array;
}
