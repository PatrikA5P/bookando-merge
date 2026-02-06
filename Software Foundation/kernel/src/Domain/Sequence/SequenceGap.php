<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Sequence;

/**
 * Immutable value object representing a detected gap in a numbering sequence.
 *
 * Gaps in fiscal numbering sequences are a compliance violation under GoBD
 * and must be detected, reported, and explained.
 */
final class SequenceGap
{
    private int $expectedNumber;
    private int $actualNumber;
    private SequenceKey $key;

    public function __construct(int $expectedNumber, int $actualNumber, SequenceKey $key)
    {
        $this->expectedNumber = $expectedNumber;
        $this->actualNumber = $actualNumber;
        $this->key = $key;
    }

    public function expectedNumber(): int
    {
        return $this->expectedNumber;
    }

    public function actualNumber(): int
    {
        return $this->actualNumber;
    }

    public function key(): SequenceKey
    {
        return $this->key;
    }

    /**
     * @return array{expected_number: int, actual_number: int, key: string}
     */
    public function toArray(): array
    {
        return [
            'expected_number' => $this->expectedNumber,
            'actual_number' => $this->actualNumber,
            'key' => $this->key->key(),
        ];
    }
}
