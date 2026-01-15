<?php

declare(strict_types=1);

namespace Bookando\Modules\Finance\Gateways;

use Bookando\Core\Service\ActivityLogger;

/**
 * Class AbstractGateway
 *
 * Base class for all payment gateways providing common functionality.
 *
 * @package Bookando\Modules\Finance\Gateways
 */
abstract class AbstractGateway implements GatewayInterface
{
    /**
     * @var string Gateway mode: 'test' or 'live'
     */
    protected string $mode = 'test';

    /**
     * @var array Gateway configuration
     */
    protected array $config = [];

    /**
     * @var int|null Current tenant ID
     */
    protected ?int $tenantId = null;

    /**
     * AbstractGateway constructor.
     *
     * @param array    $config   Gateway configuration
     * @param int|null $tenantId Tenant ID (for multi-tenant support)
     */
    public function __construct(array $config = [], ?int $tenantId = null)
    {
        $this->config = $config;
        $this->tenantId = $tenantId;
        $this->mode = $config['mode'] ?? 'test';
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return ($this->config['enabled'] ?? false) === true;
    }

    /**
     * {@inheritDoc}
     */
    public function isConfigured(): bool
    {
        $fields = $this->getConfigurationFields();
        foreach ($fields as $key => $field) {
            if (($field['required'] ?? false) && empty($this->config[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get configuration value
     *
     * @param string $key     Configuration key
     * @param mixed  $default Default value
     *
     * @return mixed Configuration value
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Check if gateway is in test mode
     *
     * @return bool True if in test mode
     */
    protected function isTestMode(): bool
    {
        return $this->mode === 'test';
    }

    /**
     * Log gateway activity
     *
     * @param string $context Context/action
     * @param string $message Log message
     * @param array  $payload Additional data
     * @param string $level   Log level (INFO, WARNING, ERROR)
     *
     * @return void
     */
    protected function log(string $context, string $message, array $payload = [], string $level = 'INFO'): void
    {
        ActivityLogger::log(
            'payment_gateway_' . $this->getId() . '_' . $context,
            $message,
            $payload,
            $level,
            $this->tenantId,
            'finance'
        );
    }

    /**
     * Format amount for gateway (convert to smallest currency unit)
     *
     * @param float  $amount   Amount in major currency unit
     * @param string $currency Currency code
     *
     * @return int Amount in smallest currency unit (cents)
     */
    protected function formatAmount(float $amount, string $currency): int
    {
        // Zero-decimal currencies (e.g., JPY, KRW)
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

        if (in_array(strtoupper($currency), $zeroDecimalCurrencies, true)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }

    /**
     * Parse amount from smallest currency unit to major unit
     *
     * @param int    $amount   Amount in smallest currency unit
     * @param string $currency Currency code
     *
     * @return float Amount in major currency unit
     */
    protected function parseAmount(int $amount, string $currency): float
    {
        // Zero-decimal currencies
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

        if (in_array(strtoupper($currency), $zeroDecimalCurrencies, true)) {
            return (float) $amount;
        }

        return $amount / 100.0;
    }

    /**
     * Sanitize metadata for gateway
     *
     * @param array $metadata Raw metadata
     *
     * @return array Sanitized metadata
     */
    protected function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];
        foreach ($metadata as $key => $value) {
            // Only include scalar values and convert to strings
            if (is_scalar($value)) {
                $sanitized[$key] = (string) $value;
            }
        }
        return $sanitized;
    }

    /**
     * Build error response
     *
     * @param string          $message Error message
     * @param \Exception|null $exception Original exception
     *
     * @return array Error response
     */
    protected function buildErrorResponse(string $message, ?\Exception $exception = null): array
    {
        $response = [
            'success' => false,
            'error' => $message,
        ];

        if ($exception && $this->isTestMode()) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }

        return $response;
    }

    /**
     * Build success response
     *
     * @param array $data Response data
     *
     * @return array Success response
     */
    protected function buildSuccessResponse(array $data): array
    {
        return array_merge(['success' => true], $data);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getId(): string;

    /**
     * {@inheritDoc}
     */
    abstract public function getName(): string;

    /**
     * {@inheritDoc}
     */
    abstract public function getSupportedCurrencies(): array;

    /**
     * {@inheritDoc}
     */
    abstract public function getSupportedPaymentMethods(): array;

    /**
     * {@inheritDoc}
     */
    abstract public function createPayment(array $params): array;

    /**
     * {@inheritDoc}
     */
    abstract public function capturePayment(string $paymentId, array $params = []): array;

    /**
     * {@inheritDoc}
     */
    abstract public function refundPayment(string $paymentId, int $amount, string $reason = ''): array;

    /**
     * {@inheritDoc}
     */
    abstract public function getPaymentStatus(string $paymentId): array;

    /**
     * {@inheritDoc}
     */
    abstract public function handleWebhook(array $payload): array;

    /**
     * {@inheritDoc}
     */
    abstract public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function getConfigurationFields(): array;

    /**
     * {@inheritDoc}
     */
    abstract public function testConnection(): array;
}
