<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Licensing;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Licensing\UsageQuota;

final class UsageQuotaTest extends TestCase
{
    public function test_limited_quota(): void
    {
        $q = new UsageQuota('bookings_per_month', 100, 75);

        $this->assertFalse($q->isUnlimited());
        $this->assertFalse($q->isExhausted());
        $this->assertSame(25, $q->remaining());
        $this->assertSame(75.0, $q->percentageUsed());
    }

    public function test_exhausted_quota(): void
    {
        $q = new UsageQuota('bookings_per_month', 100, 100);

        $this->assertTrue($q->isExhausted());
        $this->assertSame(0, $q->remaining());
        $this->assertSame(100.0, $q->percentageUsed());
    }

    public function test_over_limit_quota(): void
    {
        $q = new UsageQuota('bookings_per_month', 100, 110);

        $this->assertTrue($q->isExhausted());
        $this->assertSame(0, $q->remaining());
        // Capped at 100%
        $this->assertSame(100.0, $q->percentageUsed());
    }

    public function test_unlimited_quota(): void
    {
        $q = new UsageQuota('api_calls', -1, 999999);

        $this->assertTrue($q->isUnlimited());
        $this->assertFalse($q->isExhausted());
        $this->assertSame(PHP_INT_MAX, $q->remaining());
        $this->assertSame(0.0, $q->percentageUsed());
    }

    public function test_can_consume(): void
    {
        $q = new UsageQuota('bookings_per_month', 100, 98);

        $this->assertTrue($q->canConsume(1));
        $this->assertTrue($q->canConsume(2));
        $this->assertFalse($q->canConsume(3));
    }

    public function test_can_consume_unlimited(): void
    {
        $q = new UsageQuota('api_calls', -1, 0);
        $this->assertTrue($q->canConsume(1000000));
    }

    public function test_with_increment(): void
    {
        $q = new UsageQuota('bookings_per_month', 100, 50);
        $next = $q->withIncrement(5);

        // Immutable: original unchanged
        $this->assertSame(50, $q->current);
        $this->assertSame(55, $next->current);
        $this->assertSame(100, $next->limit);
        $this->assertSame('bookings_per_month', $next->key);
    }
}
