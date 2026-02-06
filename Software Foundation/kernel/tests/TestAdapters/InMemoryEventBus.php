<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Shared\DomainEvent;
use SoftwareFoundation\Kernel\Ports\EventBusPort;

/**
 * In-memory event bus for testing. Records published events for assertions.
 */
final class InMemoryEventBus implements EventBusPort
{
    /** @var DomainEvent[] */
    private array $publishedEvents = [];

    /** @var array<string, string[]> eventType => listenerClass[] */
    private array $subscriptions = [];

    public function publish(DomainEvent $event): void
    {
        $this->publishedEvents[] = $event;
    }

    public function subscribe(string $eventType, string $listenerClass): void
    {
        $this->subscriptions[$eventType][] = $listenerClass;
    }

    public function publishBatch(array $events): void
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }

    // --- Test helpers ---

    /** @return DomainEvent[] */
    public function publishedEvents(): array
    {
        return $this->publishedEvents;
    }

    public function eventCount(): int
    {
        return count($this->publishedEvents);
    }

    public function hasPublishedEventOfType(string $eventType): bool
    {
        foreach ($this->publishedEvents as $event) {
            if ($event->eventType() === $eventType) {
                return true;
            }
        }
        return false;
    }

    public function reset(): void
    {
        $this->publishedEvents = [];
    }
}
