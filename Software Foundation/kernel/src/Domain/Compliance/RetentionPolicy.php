<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Compliance;

/**
 * Immutable retention policy for a data entity or category.
 *
 * Encapsulates the legal retention requirements including the category,
 * legal basis, duration, and whether the system should automatically
 * archive or delete data when the retention period expires.
 *
 * INVARIANTS:
 * - retentionYears is -1 (permanent), 0 (temporary), or a positive integer
 * - autoDelete and autoArchive cannot both be true when retentionYears is -1
 */
final class RetentionPolicy
{
    private RetentionCategory $category;
    private string $reason;
    private int $retentionYears;
    private bool $autoArchive;
    private bool $autoDelete;

    public function __construct(
        RetentionCategory $category,
        string $reason,
        int $retentionYears,
        bool $autoArchive,
        bool $autoDelete,
    ) {
        $this->category = $category;
        $this->reason = $reason;
        $this->retentionYears = $retentionYears;
        $this->autoArchive = $autoArchive;
        $this->autoDelete = $autoDelete;
    }

    /**
     * Create a financial retention policy (10 years, auto-archive).
     */
    public static function financial(string $reason = 'OR Art. 958f / GeBüV'): self
    {
        return new self(
            RetentionCategory::FINANCIAL_10Y,
            $reason,
            10,
            true,
            false,
        );
    }

    /**
     * Create a personal data retention policy (DSG/DSGVO, auto-delete).
     */
    public static function personal(string $reason = 'DSG Art. 6'): self
    {
        return new self(
            RetentionCategory::PERSONAL_DSG,
            $reason,
            3,
            false,
            true,
        );
    }

    /**
     * Check whether the retention period has elapsed.
     *
     * Returns false for permanent retention (-1) since it never expires.
     * Returns true for temporary data (0) since it is always considered expired.
     */
    public function isExpired(\DateTimeImmutable $createdAt, \DateTimeImmutable $now): bool
    {
        if ($this->retentionYears === -1) {
            return false;
        }

        if ($this->retentionYears === 0) {
            return true;
        }

        $expiresAt = $createdAt->modify("+{$this->retentionYears} years");

        return $now >= $expiresAt;
    }

    public function category(): RetentionCategory
    {
        return $this->category;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function retentionYears(): int
    {
        return $this->retentionYears;
    }

    public function autoArchive(): bool
    {
        return $this->autoArchive;
    }

    public function autoDelete(): bool
    {
        return $this->autoDelete;
    }

    /**
     * @return array{category: string, reason: string, retention_years: int, auto_archive: bool, auto_delete: bool}
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category->value,
            'reason' => $this->reason,
            'retention_years' => $this->retentionYears,
            'auto_archive' => $this->autoArchive,
            'auto_delete' => $this->autoDelete,
        ];
    }
}
