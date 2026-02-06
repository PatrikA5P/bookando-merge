<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Application;

use SoftwareFoundation\Kernel\Domain\Tenant\TenantId;
use SoftwareFoundation\Kernel\Domain\Identity\UserId;
use SoftwareFoundation\Kernel\Domain\Licensing\License;

/**
 * Immutable security context for the current request/job.
 * Created at the system boundary (HTTP middleware, job worker) and passed through all layers.
 * Contains: who (user), where (tenant), what they can do (permissions + license).
 */
final class SecurityContext
{
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly UserId $userId,
        public readonly string $email,
        public readonly array $roles,
        public readonly array $permissions,
        public readonly License $license,
        public readonly string $authMethod,  // 'jwt', 'api_key', 'session', 'system'
        public readonly ?string $ip = null,
        public readonly ?string $correlationId = null,
    ) {}

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function assertPermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            throw new \SoftwareFoundation\Kernel\Ports\UnauthorizedException(
                "Missing permission: {$permission}"
            );
        }
    }

    public function assertTenant(TenantId $expected): void
    {
        if (!$this->tenantId->equals($expected)) {
            throw new \SoftwareFoundation\Kernel\Ports\UnauthorizedException(
                'Tenant context mismatch'
            );
        }
    }

    public function canAccessModule(string $moduleSlug): bool
    {
        return $this->license->canAccessModule($moduleSlug);
    }

    public function hasFeature(string $feature): bool
    {
        return $this->license->hasFeature($feature);
    }

    /** Create a system context for background jobs / internal operations. */
    public static function system(TenantId $tenantId, License $license, ?string $correlationId = null): self
    {
        return new self(
            tenantId: $tenantId,
            userId: UserId::fromString('00000000-0000-4000-8000-000000000000'),
            email: 'system@internal',
            roles: ['system'],
            permissions: ['*'],
            license: $license,
            authMethod: 'system',
            correlationId: $correlationId,
        );
    }
}
