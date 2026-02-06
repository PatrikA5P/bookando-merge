<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Sequence;

/**
 * Immutable key identifying a numbering sequence.
 *
 * Sequences are scoped per tenant, per prefix (document type), and per year.
 * This ensures gap-free, legally compliant numbering for invoices, journal
 * entries, and other fiscal documents as required by GoBD and OR Art. 958f.
 *
 * INVARIANTS:
 * - prefix contains only lowercase alphanumeric characters and underscores
 * - year >= 2000
 * - tenantId > 0
 */
final class SequenceKey
{
    private string $prefix;
    private int $year;
    private int $tenantId;

    public function __construct(string $prefix, int $year, int $tenantId)
    {
        if (!preg_match('/^[a-z0-9_]+$/', $prefix)) {
            throw new \InvalidArgumentException(
                "prefix must be lowercase alphanumeric with underscores, got '{$prefix}'"
            );
        }

        if ($year < 2000) {
            throw new \InvalidArgumentException(
                "year must be >= 2000, got {$year}"
            );
        }

        if ($tenantId <= 0) {
            throw new \InvalidArgumentException(
                "tenantId must be a positive integer, got {$tenantId}"
            );
        }

        $this->prefix = $prefix;
        $this->year = $year;
        $this->tenantId = $tenantId;
    }

    public static function of(string $prefix, int $year, int $tenantId): self
    {
        return new self($prefix, $year, $tenantId);
    }

    /**
     * Return the composite key string: "prefix:year:tenantId".
     */
    public function key(): string
    {
        return "{$this->prefix}:{$this->year}:{$this->tenantId}";
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function tenantId(): int
    {
        return $this->tenantId;
    }

    public function equals(self $other): bool
    {
        return $this->prefix === $other->prefix
            && $this->year === $other->year
            && $this->tenantId === $other->tenantId;
    }

    public function __toString(): string
    {
        return $this->key();
    }
}
