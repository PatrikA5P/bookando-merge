<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Sequence;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Sequence\SequenceGap;
use SoftwareFoundation\Kernel\Domain\Sequence\SequenceKey;

final class SequenceGapTest extends TestCase
{
    public function testConstruction(): void
    {
        $key = SequenceKey::of('INV', 2025, 42);
        $gap = new SequenceGap(5, 7, $key);

        self::assertSame(5, $gap->expectedNumber());
        self::assertSame(7, $gap->actualNumber());
        self::assertSame($key, $gap->key());
    }

    public function testToArray(): void
    {
        $key = SequenceKey::of('INV', 2025, 42);
        $gap = new SequenceGap(10, 13, $key);
        $arr = $gap->toArray();

        self::assertSame(10, $arr['expected_number']);
        self::assertSame(13, $arr['actual_number']);
        self::assertSame('INV:2025:42', $arr['key']);
    }
}
