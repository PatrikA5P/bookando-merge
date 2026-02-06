<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Identity;

use SoftwareFoundation\Kernel\Domain\Shared\EntityId;

/**
 * User identifier (UUID-based, host-agnostic).
 *
 * This is NOT a WordPress user ID or a database auto-increment.
 * External system IDs are mapped via the IdentityPort adapter.
 */
final class UserId
{
    private EntityId $id;

    public function __construct(EntityId $id)
    {
        $this->id = $id;
    }

    public static function generate(): self
    {
        return new self(EntityId::generate());
    }

    public static function fromString(string $uuid): self
    {
        return new self(EntityId::fromString($uuid));
    }

    public function value(): string
    {
        return $this->id->value();
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }

    public function __toString(): string
    {
        return $this->id->value();
    }
}
