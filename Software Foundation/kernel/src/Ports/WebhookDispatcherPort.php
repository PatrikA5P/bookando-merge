<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Webhook\WebhookDelivery;
use SoftwareFoundation\Kernel\Domain\Webhook\WebhookSubscription;

interface WebhookDispatcherPort
{
    /**
     * Dispatches a webhook event to all active subscriptions for the given tenant.
     *
     * @param array<string, mixed> $payload
     *
     * @return int Number of subscriptions dispatched to
     */
    public function dispatch(int $tenantId, string $eventType, array $payload): int;

    /**
     * Creates a new webhook subscription.
     *
     * @param string[] $eventTypes
     *
     * @return string The subscription ID
     */
    public function subscribe(int $tenantId, string $url, string $secret, array $eventTypes): string;

    /**
     * Removes a webhook subscription.
     */
    public function unsubscribe(int $tenantId, string $subscriptionId): bool;

    /**
     * Returns all webhook subscriptions for the given tenant.
     *
     * @return WebhookSubscription[]
     */
    public function getSubscriptions(int $tenantId): array;

    /**
     * Returns the delivery log for a specific subscription.
     *
     * @return WebhookDelivery[]
     */
    public function getDeliveryLog(int $tenantId, string $subscriptionId, int $limit = 50): array;
}
