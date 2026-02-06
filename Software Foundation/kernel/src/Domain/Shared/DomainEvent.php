<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Shared;

use SoftwareFoundation\Kernel\Domain\Tenant\TenantId;

/**
 * Base class for all domain events.
 *
 * INVARIANTS:
 * - Events are immutable
 * - Events always carry tenantId, eventId, occurredAt, version
 * - Events must be JSON-serializable
 * - Event handlers must be idempotent (same event 2x → same state)
 * - New fields may be added (backward-compatible), fields must never be removed
 */
abstract class DomainEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly TenantId $tenantId,
        public readonly \DateTimeImmutable $occurredAt,
        public readonly int $schemaVersion = 1,
    ) {}

    /** Event type string (e.g., 'booking.appointment_created'). */
    abstract public function eventType(): string;

    /** Serialize to array for transport/storage. */
    abstract public function toPayload(): array;

    /** Full serialization including envelope. */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => $this->eventType(),
            'tenant_id' => $this->tenantId->value(),
            'occurred_at' => $this->occurredAt->format('Y-m-d\TH:i:s.u\Z'),
            'schema_version' => $this->schemaVersion,
            'payload' => $this->toPayload(),
        ];
    }
}
