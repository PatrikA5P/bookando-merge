<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Tenant;

/**
 * Immutable Tenant identifier.
 *
 * INVARIANTS:
 * - Always positive integer
 * - Never null, never 0, never implicit
 * - No fallback to "default tenant" — missing tenant is an error
 */
final class TenantId
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(
                "TenantId must be a positive integer, got {$value}"
            );
        }
        $this->value = $value;
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
