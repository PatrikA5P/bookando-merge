<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Licensing;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Licensing\License;
use SoftwareFoundation\Kernel\Domain\Licensing\LicenseStatus;
use SoftwareFoundation\Kernel\Domain\Licensing\Plan;
use SoftwareFoundation\Kernel\Domain\Tenant\TenantId;

final class LicenseTest extends TestCase
{
    private function plan(): Plan
    {
        return new Plan(
            id: 'pro',
            name: 'Professional',
            modules: ['booking', 'customers'],
            features: ['export_csv'],
            quotas: ['bookings_per_month' => 500],
            integrations: ['stripe'],
            maxSeats: 5,
        );
    }

    private function activeLicense(): License
    {
        return new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::ACTIVE,
        );
    }

    public function test_active_license_is_valid(): void
    {
        $this->assertTrue($this->activeLicense()->isValid());
    }

    public function test_expired_license_is_not_valid(): void
    {
        $license = new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::EXPIRED,
        );
        $this->assertFalse($license->isValid());
    }

    public function test_suspended_license_is_not_valid(): void
    {
        $license = new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::SUSPENDED,
        );
        $this->assertFalse($license->isValid());
    }

    public function test_cancelled_license_is_not_valid(): void
    {
        $license = new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::CANCELLED,
        );
        $this->assertFalse($license->isValid());
    }

    public function test_can_access_module_when_active(): void
    {
        $license = $this->activeLicense();
        $this->assertTrue($license->canAccessModule('booking'));
        $this->assertFalse($license->canAccessModule('finance'));
    }

    public function test_cannot_access_module_when_expired(): void
    {
        $license = new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::EXPIRED,
        );
        $this->assertFalse($license->canAccessModule('booking'));
    }

    public function test_has_feature(): void
    {
        $license = $this->activeLicense();
        $this->assertTrue($license->hasFeature('export_csv'));
        $this->assertFalse($license->hasFeature('white_label'));
    }

    public function test_can_use_integration(): void
    {
        $license = $this->activeLicense();
        $this->assertTrue($license->canUseIntegration('stripe'));
        $this->assertFalse($license->canUseIntegration('paypal'));
    }

    public function test_quota_limit(): void
    {
        $license = $this->activeLicense();
        $this->assertSame(500, $license->quotaLimit('bookings_per_month'));
        $this->assertSame(0, $license->quotaLimit('unknown'));
    }

    public function test_quota_limit_zero_when_invalid(): void
    {
        $license = new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::EXPIRED,
        );
        $this->assertSame(0, $license->quotaLimit('bookings_per_month'));
    }

    public function test_max_seats(): void
    {
        $license = $this->activeLicense();
        $this->assertSame(5, $license->maxSeats());
    }

    public function test_max_seats_zero_when_invalid(): void
    {
        $license = new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::EXPIRED,
        );
        $this->assertSame(0, $license->maxSeats());
    }

    public function test_grace_period_license_is_valid(): void
    {
        // Grace: status=GRACE, expiresAt in the past but within 7 days
        $license = new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::GRACE,
            expiresAt: new \DateTimeImmutable('-3 days', new \DateTimeZone('UTC')),
        );
        $this->assertTrue($license->isValid());
    }

    public function test_grace_period_expired_after_7_days(): void
    {
        $license = new License(
            tenantId: TenantId::of(1),
            plan: $this->plan(),
            status: LicenseStatus::GRACE,
            expiresAt: new \DateTimeImmutable('-10 days', new \DateTimeZone('UTC')),
        );
        $this->assertFalse($license->isValid());
    }
}
