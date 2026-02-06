<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Licensing;

/**
 * Tracks current usage against a quota limit.
 */
final class UsageQuota
{
    public function __construct(
        public readonly string $key,
        public readonly int $limit,     // -1 = unlimited
        public readonly int $current,
    ) {}

    public function isUnlimited(): bool
    {
        return $this->limit === -1;
    }

    public function isExhausted(): bool
    {
        if ($this->isUnlimited()) {
            return false;
        }
        return $this->current >= $this->limit;
    }

    public function remaining(): int
    {
        if ($this->isUnlimited()) {
            return PHP_INT_MAX;
        }
        return max(0, $this->limit - $this->current);
    }

    public function percentageUsed(): float
    {
        if ($this->isUnlimited() || $this->limit === 0) {
            return 0.0;
        }
        return min(100.0, ($this->current / $this->limit) * 100.0);
    }

    /** Can we consume N more units? */
    public function canConsume(int $amount = 1): bool
    {
        if ($this->isUnlimited()) {
            return true;
        }
        return ($this->current + $amount) <= $this->limit;
    }

    public function withIncrement(int $delta = 1): self
    {
        return new self($this->key, $this->limit, $this->current + $delta);
    }
}
