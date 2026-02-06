<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Identity;

/**
 * A named role with a set of permissions.
 */
final class Role
{
    /**
     * @param string $slug Unique identifier (e.g., 'admin', 'manager', 'employee', 'customer')
     * @param string $name Display name
     * @param Permission[] $permissions
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        private readonly array $permissions,
    ) {}

    public function hasPermission(Permission $permission): bool
    {
        foreach ($this->permissions as $p) {
            if ($p->equals($permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasPermissionString(string $permissionValue): bool
    {
        return $this->hasPermission(Permission::of($permissionValue));
    }

    /** @return Permission[] */
    public function permissions(): array
    {
        return $this->permissions;
    }

    /** @return string[] */
    public function permissionValues(): array
    {
        return array_map(fn(Permission $p) => $p->value(), $this->permissions);
    }
}
