<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Tenant;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Tenant\TenantId;

final class TenantIdTest extends TestCase
{
    public function test_creates_with_positive_integer(): void
    {
        $id = TenantId::of(42);
        $this->assertSame(42, $id->value());
    }

    public function test_rejects_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TenantId::of(0);
    }

    public function test_rejects_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TenantId::of(-1);
    }

    public function test_equals(): void
    {
        $a = TenantId::of(1);
        $b = TenantId::of(1);
        $c = TenantId::of(2);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $this->assertSame('42', (string) TenantId::of(42));
    }
}
