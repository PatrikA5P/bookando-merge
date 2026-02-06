<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Application;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Application\LicenseGuard;
use SoftwareFoundation\Kernel\Application\LicenseViolationException;
use SoftwareFoundation\Kernel\Application\QuotaExhaustedException;
use SoftwareFoundation\Kernel\Application\SecurityContext;
use SoftwareFoundation\Kernel\Domain\Identity\UserId;
use SoftwareFoundation\Kernel\Domain\Tenant\TenantId;
use SoftwareFoundation\Kernel\Tests\TestAdapters\FakeLicenseResolver;

final class LicenseGuardTest extends TestCase
{
    private function contextWithLicense(int $tenantId, string $planType): SecurityContext
    {
        $license = $planType === 'professional'
            ? FakeLicenseResolver::professional($tenantId)
            : FakeLicenseResolver::starter($tenantId);

        return new SecurityContext(
            tenantId: TenantId::of($tenantId),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            email: 'user@example.com',
            roles: ['admin'],
            permissions: ['*'],
            license: $license,
            authMethod: 'jwt',
        );
    }

    public function test_assert_module_passes_when_included(): void
    {
        $resolver = new FakeLicenseResolver();
        $resolver->withLicense(1, FakeLicenseResolver::professional(1));
        $guard = new LicenseGuard($resolver);

        $ctx = $this->contextWithLicense(1, 'professional');

        // booking is in professional plan
        $guard->assertModule($ctx, 'booking');
        $this->assertTrue(true);
    }

    public function test_assert_module_throws_when_not_included(): void
    {
        $resolver = new FakeLicenseResolver();
        $resolver->withLicense(1, FakeLicenseResolver::starter(1));
        $guard = new LicenseGuard($resolver);

        $ctx = $this->contextWithLicense(1, 'starter');

        $this->expectException(LicenseViolationException::class);
        // finance is NOT in starter plan
        $guard->assertModule($ctx, 'finance');
    }

    public function test_assert_feature_passes(): void
    {
        $resolver = new FakeLicenseResolver();
        $resolver->withLicense(1, FakeLicenseResolver::professional(1));
        $guard = new LicenseGuard($resolver);

        $ctx = $this->contextWithLicense(1, 'professional');

        $guard->assertFeature($ctx, 'export_csv');
        $this->assertTrue(true);
    }

    public function test_assert_feature_throws(): void
    {
        $resolver = new FakeLicenseResolver();
        $resolver->withLicense(1, FakeLicenseResolver::starter(1));
        $guard = new LicenseGuard($resolver);

        $ctx = $this->contextWithLicense(1, 'starter');

        $this->expectException(LicenseViolationException::class);
        $guard->assertFeature($ctx, 'export_csv');
    }

    public function test_consume_quota_succeeds(): void
    {
        $resolver = new FakeLicenseResolver();
        $resolver->withLicense(1, FakeLicenseResolver::professional(1));
        $resolver->withQuota(1, 'bookings_per_month', 100, 50);
        $guard = new LicenseGuard($resolver);

        $ctx = $this->contextWithLicense(1, 'professional');

        $guard->consumeQuota($ctx, 'bookings_per_month');

        $this->assertSame(1, $resolver->getTrackedUsage(1, 'bookings_per_month'));
    }

    public function test_consume_quota_throws_when_exhausted(): void
    {
        $resolver = new FakeLicenseResolver();
        $resolver->withLicense(1, FakeLicenseResolver::starter(1));
        $resolver->withQuota(1, 'bookings_per_month', 50, 50);
        $guard = new LicenseGuard($resolver);

        $ctx = $this->contextWithLicense(1, 'starter');

        $this->expectException(QuotaExhaustedException::class);
        $guard->consumeQuota($ctx, 'bookings_per_month');
    }
}
