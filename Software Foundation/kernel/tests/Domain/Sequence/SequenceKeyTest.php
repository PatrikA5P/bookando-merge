<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Sequence;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Sequence\SequenceKey;

final class SequenceKeyTest extends TestCase
{
    // --- Construction ---

    public function test_creates_valid_key(): void
    {
        $key = SequenceKey::of('invoice', 2026, 42);

        $this->assertSame('invoice', $key->prefix());
        $this->assertSame(2026, $key->year());
        $this->assertSame(42, $key->tenantId());
    }

    // --- Key format ---

    public function test_key_format(): void
    {
        $key = SequenceKey::of('invoice', 2026, 42);
        $this->assertSame('invoice:2026:42', $key->key());
    }

    // --- Validation ---

    public function test_rejects_invalid_prefix_uppercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SequenceKey::of('Invoice', 2026, 1);
    }

    public function test_rejects_invalid_prefix_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SequenceKey::of('my invoice', 2026, 1);
    }

    public function test_rejects_invalid_year(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SequenceKey::of('invoice', 1999, 1);
    }

    // --- Comparison ---

    public function test_equals(): void
    {
        $a = SequenceKey::of('invoice', 2026, 1);
        $b = SequenceKey::of('invoice', 2026, 1);
        $c = SequenceKey::of('invoice', 2025, 1);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    // --- String representation ---

    public function test_to_string(): void
    {
        $key = SequenceKey::of('invoice', 2026, 42);
        $this->assertSame('invoice:2026:42', (string) $key);
    }
}
