<?php

declare(strict_types=1);

namespace Bookando\Modules\finance\Gateways;

use Bookando\Core\Service\ActivityLogger;
use function get_option;
use function update_option;

/**
 * Class GatewayManager
 *
 * Manages all payment gateways and their configurations.
 *
 * @package Bookando\Modules\finance\Gateways
 */
class GatewayManager
{
    private const OPTION_KEY = 'bookando_payment_gateways_config';

    /**
     * @var array Registered gateway classes
     */
    private static array $registeredGateways = [
        'stripe' => Stripe\StripeGateway::class,
        'paypal' => PayPal\PayPalGateway::class,
        'klarna' => Klarna\KlarnaGateway::class,
        'twint' => Twint\TwintGateway::class,
        'mollie' => Mollie\MollieGateway::class,
    ];

    /**
     * @var array Cached gateway instances
     */
    private static array $instances = [];

    /**
     * Get all available gateways
     *
     * @param int|null $tenantId Tenant ID
     *
     * @return array Array of gateway IDs
     */
    public static function getAvailableGateways(?int $tenantId = null): array
    {
        return array_keys(self::$registeredGateways);
    }

    /**
     * Get gateway instance
     *
     * @param string   $gatewayId Gateway ID
     * @param int|null $tenantId  Tenant ID
     *
     * @return GatewayInterface|null Gateway instance or null if not found
     */
    public static function getGateway(string $gatewayId, ?int $tenantId = null): ?GatewayInterface
    {
        $cacheKey = $gatewayId . '_' . ($tenantId ?? 'global');

        if (isset(self::$instances[$cacheKey])) {
            return self::$instances[$cacheKey];
        }

        if (!isset(self::$registeredGateways[$gatewayId])) {
            return null;
        }

        $class = self::$registeredGateways[$gatewayId];
        if (!class_exists($class)) {
            return null;
        }

        $config = self::getGatewayConfig($gatewayId, $tenantId);
        $instance = new $class($config, $tenantId);

        self::$instances[$cacheKey] = $instance;

        return $instance;
    }

    /**
     * Get all enabled gateways
     *
     * @param int|null $tenantId Tenant ID
     *
     * @return array Array of enabled gateway instances
     */
    public static function getEnabledGateways(?int $tenantId = null): array
    {
        $enabled = [];

        foreach (self::getAvailableGateways($tenantId) as $gatewayId) {
            $gateway = self::getGateway($gatewayId, $tenantId);
            if ($gateway && $gateway->isEnabled() && $gateway->isConfigured()) {
                $enabled[$gatewayId] = $gateway;
            }
        }

        return $enabled;
    }

    /**
     * Get gateway configuration
     *
     * @param string   $gatewayId Gateway ID
     * @param int|null $tenantId  Tenant ID
     *
     * @return array Gateway configuration
     */
    public static function getGatewayConfig(string $gatewayId, ?int $tenantId = null): array
    {
        $allConfig = get_option(self::OPTION_KEY, []);

        // Tenant-specific or global config
        $key = $tenantId ? "tenant_{$tenantId}_{$gatewayId}" : "global_{$gatewayId}";

        return $allConfig[$key] ?? [];
    }

    /**
     * Save gateway configuration
     *
     * @param string   $gatewayId Gateway ID
     * @param array    $config    Configuration data
     * @param int|null $tenantId  Tenant ID
     *
     * @return bool True on success
     */
    public static function saveGatewayConfig(string $gatewayId, array $config, ?int $tenantId = null): bool
    {
        $allConfig = get_option(self::OPTION_KEY, []);

        $key = $tenantId ? "tenant_{$tenantId}_{$gatewayId}" : "global_{$gatewayId}";

        // Sanitize config
        $config = self::sanitizeConfig($config);

        $allConfig[$key] = $config;

        $result = update_option(self::OPTION_KEY, $allConfig, false);

        // Clear instance cache
        $cacheKey = $gatewayId . '_' . ($tenantId ?? 'global');
        unset(self::$instances[$cacheKey]);

        ActivityLogger::log(
            'payment_gateway_config_updated',
            sprintf('Payment gateway configuration updated: %s', $gatewayId),
            ['gateway' => $gatewayId, 'tenant_id' => $tenantId],
            'INFO',
            $tenantId,
            'finance'
        );

        return $result;
    }

    /**
     * Delete gateway configuration
     *
     * @param string   $gatewayId Gateway ID
     * @param int|null $tenantId  Tenant ID
     *
     * @return bool True on success
     */
    public static function deleteGatewayConfig(string $gatewayId, ?int $tenantId = null): bool
    {
        $allConfig = get_option(self::OPTION_KEY, []);

        $key = $tenantId ? "tenant_{$tenantId}_{$gatewayId}" : "global_{$gatewayId}";

        if (!isset($allConfig[$key])) {
            return false;
        }

        unset($allConfig[$key]);

        $result = update_option(self::OPTION_KEY, $allConfig, false);

        // Clear instance cache
        $cacheKey = $gatewayId . '_' . ($tenantId ?? 'global');
        unset(self::$instances[$cacheKey]);

        return $result;
    }

    /**
     * Get gateways summary for API
     *
     * @param int|null $tenantId Tenant ID
     *
     * @return array Gateways summary
     */
    public static function getGatewaysSummary(?int $tenantId = null): array
    {
        $summary = [];

        foreach (self::getAvailableGateways($tenantId) as $gatewayId) {
            $gateway = self::getGateway($gatewayId, $tenantId);

            if (!$gateway) {
                continue;
            }

            $summary[] = [
                'id' => $gateway->getId(),
                'name' => $gateway->getName(),
                'enabled' => $gateway->isEnabled(),
                'configured' => $gateway->isConfigured(),
                'supported_currencies' => $gateway->getSupportedCurrencies(),
                'supported_payment_methods' => $gateway->getSupportedPaymentMethods(),
            ];
        }

        return $summary;
    }

    /**
     * Sanitize configuration data
     *
     * @param array $config Raw configuration
     *
     * @return array Sanitized configuration
     */
    private static function sanitizeConfig(array $config): array
    {
        $sanitized = [];

        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeConfig($value);
            }
        }

        return $sanitized;
    }

    /**
     * Register a new gateway
     *
     * @param string $id    Gateway ID
     * @param string $class Gateway class name
     *
     * @return void
     */
    public static function registerGateway(string $id, string $class): void
    {
        self::$registeredGateways[$id] = $class;
    }
}
