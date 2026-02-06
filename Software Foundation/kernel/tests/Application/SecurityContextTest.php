<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Application;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Application\SecurityContext;
use SoftwareFoundation\Kernel\Domain\Identity\UserId;
use SoftwareFoundation\Kernel\Domain\Licensing\License;
use SoftwareFoundation\Kernel\Domain\Licensing\LicenseStatus;
use SoftwareFoundation\Kernel\Domain\Licensing\Plan;
use SoftwareFoundation\Kernel\Domain\Tenant\TenantId;
use SoftwareFoundation\Kernel\Ports\UnauthorizedException;

final class SecurityContextTest extends TestCase
{
    private function plan(): Plan
    {
        return new Plan(
            id: 'pro',
            name: 'Professional',
            modules: ['booking', 'customers'],
            features: ['export_csv'],
            quotas: [],
            integrations: [],
        );
    }

    private function license(int $tenantId = 1): License
    {
        return new License(
            tenantId: TenantId::of($tenantId),
            plan: $this->plan(),
            status: LicenseStatus::ACTIVE,
        );
    }

    private function context(int $tenantId = 1): SecurityContext
    {
        return new SecurityContext(
            tenantId: TenantId::of($tenantId),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            email: 'admin@example.com',
            roles: ['admin'],
            permissions: ['booking.create', 'booking.cancel', 'customers.view'],
            license: $this->license($tenantId),
            authMethod: 'jwt',
            ip: '127.0.0.1',
            correlationId: 'corr-123',
        );
    }

    public function test_has_permission(): void
    {
        $ctx = $this->context();
        $this->assertTrue($ctx->hasPermission('booking.create'));
        $this->assertFalse($ctx->hasPermission('finance.refund'));
    }

    public function test_assert_permission_passes(): void
    {
        $ctx = $this->context();
        // Should not throw
        $ctx->assertPermission('booking.create');
        $this->assertTrue(true);
    }

    public function test_assert_permission_throws(): void
    {
        $ctx = $this->context();
        $this->expectException(UnauthorizedException::class);
        $ctx->assertPermission('finance.refund');
    }

    public function test_assert_tenant_passes(): void
    {
        $ctx = $this->context(42);
        $ctx->assertTenant(TenantId::of(42));
        $this->assertTrue(true);
    }

    public function test_assert_tenant_throws_on_mismatch(): void
    {
        $ctx = $this->context(42);
        $this->expectException(UnauthorizedException::class);
        $ctx->assertTenant(TenantId::of(99));
    }

    public function test_can_access_module(): void
    {
        $ctx = $this->context();
        $this->assertTrue($ctx->canAccessModule('booking'));
        $this->assertFalse($ctx->canAccessModule('finance'));
    }

    public function test_has_feature(): void
    {
        $ctx = $this->context();
        $this->assertTrue($ctx->hasFeature('export_csv'));
        $this->assertFalse($ctx->hasFeature('white_label'));
    }

    public function test_system_context(): void
    {
        $ctx = SecurityContext::system(TenantId::of(1), $this->license(), 'corr-456');

        $this->assertSame('system', $ctx->authMethod);
        $this->assertSame(['system'], $ctx->roles);
        $this->assertSame(['*'], $ctx->permissions);
        $this->assertSame('system@internal', $ctx->email);
        $this->assertSame('corr-456', $ctx->correlationId);
    }
}
