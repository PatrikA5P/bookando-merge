<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Time;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Time\TimeRange;

final class TimeRangeTest extends TestCase
{
    public function test_creates_from_strings(): void
    {
        $range = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:00:00');
        $this->assertSame('2026-01-15 10:00:00', $range->startString());
        $this->assertSame('2026-01-15 11:00:00', $range->endString());
    }

    public function test_rejects_start_after_end(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TimeRange::fromStrings('2026-01-15 12:00:00', '2026-01-15 10:00:00');
    }

    public function test_allows_zero_duration(): void
    {
        $range = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 10:00:00');
        $this->assertSame(0, $range->durationMinutes());
    }

    public function test_from_local_converts_to_utc(): void
    {
        // CET is UTC+1 in winter
        $range = TimeRange::fromLocal('2026-01-15 10:00:00', '2026-01-15 11:00:00', 'Europe/Zurich');
        $this->assertSame('2026-01-15 09:00:00', $range->startString());
        $this->assertSame('2026-01-15 10:00:00', $range->endString());
    }

    public function test_duration_minutes(): void
    {
        $range = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:30:00');
        $this->assertSame(90, $range->durationMinutes());
    }

    public function test_duration_seconds(): void
    {
        $range = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 10:01:30');
        $this->assertSame(90, $range->durationSeconds());
    }

    public function test_overlaps(): void
    {
        $a = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:00:00');
        $b = TimeRange::fromStrings('2026-01-15 10:30:00', '2026-01-15 11:30:00');
        $c = TimeRange::fromStrings('2026-01-15 11:00:00', '2026-01-15 12:00:00');

        $this->assertTrue($a->overlaps($b));
        $this->assertTrue($b->overlaps($a));
        // Adjacent ranges do not overlap (start < end, not start <= end)
        $this->assertFalse($a->overlaps($c));
    }

    public function test_contains_range(): void
    {
        $outer = TimeRange::fromStrings('2026-01-15 09:00:00', '2026-01-15 12:00:00');
        $inner = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:00:00');

        $this->assertTrue($outer->contains($inner));
        $this->assertFalse($inner->contains($outer));
    }

    public function test_contains_point(): void
    {
        $range = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:00:00');
        $inside = new \DateTimeImmutable('2026-01-15 10:30:00', new \DateTimeZone('UTC'));
        $outside = new \DateTimeImmutable('2026-01-15 12:00:00', new \DateTimeZone('UTC'));
        $atEnd = new \DateTimeImmutable('2026-01-15 11:00:00', new \DateTimeZone('UTC'));

        $this->assertTrue($range->containsPoint($inside));
        $this->assertFalse($range->containsPoint($outside));
        // End is exclusive
        $this->assertFalse($range->containsPoint($atEnd));
    }

    public function test_has_minimum_gap_before(): void
    {
        $a = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:00:00');
        $b = TimeRange::fromStrings('2026-01-15 11:30:00', '2026-01-15 12:00:00');

        $this->assertTrue($a->hasMinimumGapBefore($b, 15));  // 30min gap >= 15min
        $this->assertTrue($a->hasMinimumGapBefore($b, 30));  // 30min gap >= 30min
        $this->assertFalse($a->hasMinimumGapBefore($b, 45)); // 30min gap < 45min
    }

    public function test_equals(): void
    {
        $a = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:00:00');
        $b = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:00:00');
        $c = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 12:00:00');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_local(): void
    {
        $range = TimeRange::fromStrings('2026-01-15 09:00:00', '2026-01-15 10:00:00');
        $local = $range->toLocal('Europe/Zurich');

        $this->assertSame('2026-01-15 10:00:00', $local['start']);
        $this->assertSame('2026-01-15 11:00:00', $local['end']);
        $this->assertSame('Europe/Zurich', $local['timezone']);
    }

    public function test_to_array(): void
    {
        $range = TimeRange::fromStrings('2026-01-15 10:00:00', '2026-01-15 11:00:00');
        $arr = $range->toArray();

        $this->assertSame('2026-01-15 10:00:00', $arr['start_utc']);
        $this->assertSame('2026-01-15 11:00:00', $arr['end_utc']);
    }
}
