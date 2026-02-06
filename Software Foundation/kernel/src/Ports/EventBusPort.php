<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Shared\DomainEvent;

/**
 * Domain event bus port.
 *
 * Provides a host-agnostic interface for publishing and subscribing to domain
 * events. Implementations may use an in-process dispatcher, a message broker
 * (e.g. RabbitMQ, Redis Streams), WordPress action hooks, or any other
 * pub/sub mechanism available in the host environment.
 *
 * Events are dispatched to all registered listeners for the matching event
 * type. Listeners are identified by their fully-qualified class name and are
 * resolved through the service container at dispatch time.
 */
interface EventBusPort
{
    /**
     * Publish a single domain event to all registered listeners.
     *
     * @param DomainEvent $event The domain event to publish.
     */
    public function publish(DomainEvent $event): void;

    /**
     * Register a listener for a specific event type.
     *
     * @param string $eventType     Fully-qualified class name of the domain event.
     * @param string $listenerClass Fully-qualified class name of the listener to invoke.
     */
    public function subscribe(string $eventType, string $listenerClass): void;

    /**
     * Publish multiple domain events in order.
     *
     * This is a convenience method for flushing a batch of events (e.g. all
     * events recorded by an aggregate root after a use-case completes).
     * Implementations MAY optimise batch delivery but MUST preserve ordering.
     *
     * @param DomainEvent[] $events List of domain events to publish.
     */
    public function publishBatch(array $events): void;
}
