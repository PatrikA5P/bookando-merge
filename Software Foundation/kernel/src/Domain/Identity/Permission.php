<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Identity;

/**
 * A specific permission that can be granted to a role.
 *
 * Naming convention: {module}.{action}
 * Examples: 'booking.create', 'booking.cancel', 'customers.view', 'finance.refund'
 */
final class Permission
{
    private string $value;

    public function __construct(string $value)
    {
        if (!preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/', $value)) {
            throw new \InvalidArgumentException(
                "Permission must be in format 'module.action', got '{$value}'"
            );
        }
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function module(): string
    {
        return explode('.', $this->value)[0];
    }

    public function action(): string
    {
        return explode('.', $this->value)[1];
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
