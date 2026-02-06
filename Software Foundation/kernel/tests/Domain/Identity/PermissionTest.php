<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Identity;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Identity\Permission;

final class PermissionTest extends TestCase
{
    public function test_creates_valid_permission(): void
    {
        $p = Permission::of('booking.create');
        $this->assertSame('booking.create', $p->value());
    }

    public function test_module(): void
    {
        $p = Permission::of('booking.create');
        $this->assertSame('booking', $p->module());
    }

    public function test_action(): void
    {
        $p = Permission::of('booking.create');
        $this->assertSame('create', $p->action());
    }

    public function test_rejects_missing_dot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Permission::of('bookingcreate');
    }

    public function test_rejects_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Permission::of('');
    }

    public function test_rejects_uppercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Permission::of('Booking.Create');
    }

    public function test_rejects_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Permission::of('booking .create');
    }

    public function test_allows_underscores(): void
    {
        $p = Permission::of('customer_mgmt.bulk_delete');
        $this->assertSame('customer_mgmt', $p->module());
        $this->assertSame('bulk_delete', $p->action());
    }

    public function test_equals(): void
    {
        $a = Permission::of('booking.create');
        $b = Permission::of('booking.create');
        $c = Permission::of('booking.cancel');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $p = Permission::of('finance.refund');
        $this->assertSame('finance.refund', (string) $p);
    }
}
