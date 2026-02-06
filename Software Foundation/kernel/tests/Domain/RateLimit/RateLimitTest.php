<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\RateLimit;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\RateLimit\RateLimit;

final class RateLimitTest extends TestCase
{
    // --- Exhaustion ---

    public function test_is_exhausted(): void
    {
        $limit = new RateLimit(
            maxRequests: 10,
            windowSeconds: 60,
            currentCount: 10,
            windowStart: new \DateTimeImmutable(),
        );

        $this->assertTrue($limit->isExhausted());
    }

    public function test_is_not_exhausted(): void
    {
        $limit = new RateLimit(
            maxRequests: 10,
            windowSeconds: 60,
            currentCount: 5,
            windowStart: new \DateTimeImmutable(),
        );

        $this->assertFalse($limit->isExhausted());
    }

    // --- Remaining ---

    public function test_remaining(): void
    {
        $limit = new RateLimit(
            maxRequests: 10,
            windowSeconds: 60,
            currentCount: 7,
            windowStart: new \DateTimeImmutable(),
        );

        $this->assertSame(3, $limit->remaining());
    }

    public function test_remaining_never_negative(): void
    {
        $limit = new RateLimit(
            maxRequests: 10,
            windowSeconds: 60,
            currentCount: 15,
            windowStart: new \DateTimeImmutable(),
        );

        $this->assertSame(0, $limit->remaining());
    }

    // --- Increment ---

    public function test_with_increment(): void
    {
        $limit = new RateLimit(
            maxRequests: 10,
            windowSeconds: 60,
            currentCount: 5,
            windowStart: new \DateTimeImmutable(),
        );

        $incremented = $limit->withIncrement();

        $this->assertSame(6, $incremented->currentCount);
        // Original is immutable
        $this->assertSame(5, $limit->currentCount);
    }

    // --- Named constructors ---

    public function test_per_minute(): void
    {
        $limit = RateLimit::perMinute(100);

        $this->assertSame(100, $limit->maxRequests);
        $this->assertSame(60, $limit->windowSeconds);
        $this->assertSame(0, $limit->currentCount);
    }

    public function test_per_hour(): void
    {
        $limit = RateLimit::perHour(1000);

        $this->assertSame(1000, $limit->maxRequests);
        $this->assertSame(3600, $limit->windowSeconds);
        $this->assertSame(0, $limit->currentCount);
    }
}
