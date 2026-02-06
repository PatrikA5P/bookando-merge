<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Identity;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Identity\Permission;
use SoftwareFoundation\Kernel\Domain\Identity\Role;

final class RoleTest extends TestCase
{
    private function adminRole(): Role
    {
        return new Role('admin', 'Administrator', [
            Permission::of('booking.create'),
            Permission::of('booking.cancel'),
            Permission::of('finance.refund'),
        ]);
    }

    public function test_has_permission(): void
    {
        $role = $this->adminRole();
        $this->assertTrue($role->hasPermission(Permission::of('booking.create')));
        $this->assertFalse($role->hasPermission(Permission::of('system.admin')));
    }

    public function test_has_permission_string(): void
    {
        $role = $this->adminRole();
        $this->assertTrue($role->hasPermissionString('finance.refund'));
        $this->assertFalse($role->hasPermissionString('unknown.action'));
    }

    public function test_permissions(): void
    {
        $role = $this->adminRole();
        $this->assertCount(3, $role->permissions());
    }

    public function test_permission_values(): void
    {
        $role = $this->adminRole();
        $values = $role->permissionValues();

        $this->assertSame(['booking.create', 'booking.cancel', 'finance.refund'], $values);
    }

    public function test_slug_and_name(): void
    {
        $role = $this->adminRole();
        $this->assertSame('admin', $role->slug);
        $this->assertSame('Administrator', $role->name);
    }
}
