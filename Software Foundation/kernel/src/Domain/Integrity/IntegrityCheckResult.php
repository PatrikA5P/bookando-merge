<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Integrity;

/**
 * Immutable result of a hash chain integrity verification.
 *
 * After verifying a range of hash chain entries, this object captures
 * whether the chain is intact or which specific entries failed validation.
 *
 * INVARIANTS:
 * - If passed is true, failedEntries is empty
 * - If passed is false, failedEntries is non-empty
 * - checkedEntries >= 0
 */
final class IntegrityCheckResult
{
    private bool $passed;
    private int $checkedEntries;
    /** @var array<int, array{sequenceNumber: int, reason: string}> */
    private array $failedEntries;
    private \DateTimeImmutable $checkedAt;

    /**
     * @param array<int, array{sequenceNumber: int, reason: string}> $failedEntries
     */
    public function __construct(
        bool $passed,
        int $checkedEntries,
        array $failedEntries,
        \DateTimeImmutable $checkedAt,
    ) {
        $this->passed = $passed;
        $this->checkedEntries = $checkedEntries;
        $this->failedEntries = $failedEntries;
        $this->checkedAt = $checkedAt;
    }

    /**
     * Create a successful integrity check result.
     */
    public static function success(int $checkedEntries, \DateTimeImmutable $at): self
    {
        return new self(true, $checkedEntries, [], $at);
    }

    /**
     * Create a failed integrity check result.
     *
     * @param array<int, array{sequenceNumber: int, reason: string}> $failedEntries
     */
    public static function failure(int $checkedEntries, array $failedEntries, \DateTimeImmutable $at): self
    {
        return new self(false, $checkedEntries, $failedEntries, $at);
    }

    public function passed(): bool
    {
        return $this->passed;
    }

    public function hasFailed(): bool
    {
        return !$this->passed;
    }

    public function checkedEntries(): int
    {
        return $this->checkedEntries;
    }

    /**
     * @return array<int, array{sequenceNumber: int, reason: string}>
     */
    public function failedEntries(): array
    {
        return $this->failedEntries;
    }

    public function checkedAt(): \DateTimeImmutable
    {
        return $this->checkedAt;
    }
}
