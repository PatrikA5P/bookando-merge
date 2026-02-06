<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Immutable value object representing the result of a payment or capture operation.
 */
final class PaymentResult
{
    /**
     * @param bool                 $success    Whether the operation succeeded.
     * @param string               $externalId Gateway-assigned transaction/payment identifier.
     * @param string               $status     Gateway status string (e.g. "authorized", "captured", "failed").
     * @param int                  $amount     Amount in minor units (cents/pence).
     * @param string               $currency   ISO 4217 currency code (e.g. "EUR", "USD").
     * @param array<string, mixed> $raw        Raw gateway response for debugging and audit.
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $externalId,
        public readonly string $status,
        public readonly int $amount,
        public readonly string $currency,
        public readonly array $raw = [],
    ) {}
}

/**
 * Immutable value object representing the result of a refund operation.
 */
final class RefundResult
{
    /**
     * @param bool                 $success    Whether the refund succeeded.
     * @param string               $refundId   Gateway-assigned refund identifier.
     * @param string               $status     Gateway status string (e.g. "refunded", "pending", "failed").
     * @param int                  $amount     Refunded amount in minor units (cents/pence).
     * @param string               $currency   ISO 4217 currency code.
     * @param array<string, mixed> $raw        Raw gateway response for debugging and audit.
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $refundId,
        public readonly string $status,
        public readonly int $amount,
        public readonly string $currency,
        public readonly array $raw = [],
    ) {}
}

/**
 * Immutable value object representing the result of processing a webhook.
 */
final class WebhookResult
{
    /**
     * @param bool                 $handled   Whether the webhook was successfully handled.
     * @param string               $eventType The type of event received (e.g. "payment.captured", "refund.completed").
     * @param string               $message   Human-readable result message.
     * @param array<string, mixed> $data      Extracted/normalised data from the webhook payload.
     */
    public function __construct(
        public readonly bool $handled,
        public readonly string $eventType,
        public readonly string $message,
        public readonly array $data = [],
    ) {}
}

/**
 * Payment gateway port.
 *
 * Provides a host-agnostic interface for payment processing operations
 * including creating payments, capturing authorised payments, issuing refunds,
 * and handling inbound webhook notifications from payment gateways.
 *
 * Implementations adapt to the concrete gateway (Stripe, Mollie, PayPal,
 * Adyen, etc.) available in the host environment. All monetary amounts
 * are expressed in minor units (cents/pence) to avoid floating-point issues.
 */
interface PaymentGatewayPort
{
    /**
     * Create (and optionally authorise) a payment.
     *
     * @param int                  $amountMinor Amount in minor units (e.g. 1500 = 15.00 EUR).
     * @param string               $currency    ISO 4217 currency code (e.g. "EUR").
     * @param array<string, mixed> $metadata    Additional data for the gateway (description, customer info, etc.).
     *
     * @return PaymentResult The result of the payment creation.
     */
    public function createPayment(int $amountMinor, string $currency, array $metadata): PaymentResult;

    /**
     * Capture a previously authorised payment.
     *
     * @param string $externalId The gateway-assigned payment/transaction identifier.
     *
     * @return PaymentResult The result of the capture operation.
     */
    public function capturePayment(string $externalId): PaymentResult;

    /**
     * Refund a captured payment, fully or partially.
     *
     * @param string $externalId  The gateway-assigned payment/transaction identifier.
     * @param int    $amountMinor Amount to refund in minor units. Pass 0 for a full refund.
     * @param string $reason      Optional human-readable reason for the refund.
     *
     * @return RefundResult The result of the refund operation.
     */
    public function refundPayment(string $externalId, int $amountMinor, string $reason = ''): RefundResult;

    /**
     * Verify the cryptographic signature of an inbound webhook payload.
     *
     * @param string               $payload Raw webhook request body.
     * @param array<string, mixed> $headers Request headers (used for signature extraction).
     *
     * @return bool True if the signature is valid and the payload is authentic.
     */
    public function verifyWebhookSignature(string $payload, array $headers): bool;

    /**
     * Process and handle a verified webhook payload.
     *
     * Implementations SHOULD normalise the gateway-specific event into a
     * domain-level action (e.g. mark payment as captured, trigger refund event).
     *
     * @param array<string, mixed> $data Parsed webhook payload data.
     *
     * @return WebhookResult The result of webhook processing.
     */
    public function handleWebhook(array $data): WebhookResult;
}
