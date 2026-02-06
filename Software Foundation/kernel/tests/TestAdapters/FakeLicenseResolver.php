<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Licensing\License;
use SoftwareFoundation\Kernel\Domain\Licensing\LicenseStatus;
use SoftwareFoundation\Kernel\Domain\Licensing\Plan;
use SoftwareFoundation\Kernel\Domain\Licensing\UsageQuota;
use SoftwareFoundation\Kernel\Domain\Tenant\TenantId;
use SoftwareFoundation\Kernel\Ports\LicenseResolverPort;

/**
 * Fake license resolver for testing. Allows pre-configuring licenses and quotas.
 */
final class FakeLicenseResolver implements LicenseResolverPort
{
    /** @var array<int, License> tenantId => License */
    private array $licenses = [];

    /** @var array<string, UsageQuota> "tenantId:quotaKey" => UsageQuota */
    private array $quotas = [];

    /** @var array<string, int> "tenantId:quotaKey" => tracked usage */
    private array $trackedUsage = [];

    public function withLicense(int $tenantId, License $license): self
    {
        $this->licenses[$tenantId] = $license;
        return $this;
    }

    public function withQuota(int $tenantId, string $quotaKey, int $limit, int $current = 0): self
    {
        $this->quotas["{$tenantId}:{$quotaKey}"] = new UsageQuota($quotaKey, $limit, $current);
        return $this;
    }

    public function resolve(int $tenantId): License
    {
        if (!isset($this->licenses[$tenantId])) {
            throw new \RuntimeException("No license configured for tenant {$tenantId}");
        }
        return $this->licenses[$tenantId];
    }

    public function hasFeature(int $tenantId, string $feature): bool
    {
        return $this->resolve($tenantId)->hasFeature($feature);
    }

    public function checkQuota(int $tenantId, string $quotaKey): UsageQuota
    {
        $key = "{$tenantId}:{$quotaKey}";
        if (!isset($this->quotas[$key])) {
            return new UsageQuota($quotaKey, 0, 0);
        }
        return $this->quotas[$key];
    }

    public function trackUsage(int $tenantId, string $quotaKey, int $delta = 1): void
    {
        $key = "{$tenantId}:{$quotaKey}";
        $this->trackedUsage[$key] = ($this->trackedUsage[$key] ?? 0) + $delta;

        // Update the quota's current count
        if (isset($this->quotas[$key])) {
            $q = $this->quotas[$key];
            $this->quotas[$key] = $q->withIncrement($delta);
        }
    }

    /** Get total tracked usage for assertions. */
    public function getTrackedUsage(int $tenantId, string $quotaKey): int
    {
        return $this->trackedUsage["{$tenantId}:{$quotaKey}"] ?? 0;
    }

    /** Create a standard "professional" test license for a tenant. */
    public static function professional(int $tenantId): License
    {
        return new License(
            tenantId: TenantId::of($tenantId),
            plan: new Plan(
                id: 'professional',
                name: 'Professional',
                modules: ['booking', 'customers', 'finance', 'reporting'],
                features: ['export_csv', 'api_write', 'sms_notifications'],
                quotas: ['bookings_per_month' => 1000, 'api_calls' => -1],
                integrations: ['stripe', 'google_calendar', 'outlook'],
                maxSeats: 10,
            ),
            status: LicenseStatus::ACTIVE,
        );
    }

    /** Create a minimal "starter" test license. */
    public static function starter(int $tenantId): License
    {
        return new License(
            tenantId: TenantId::of($tenantId),
            plan: new Plan(
                id: 'starter',
                name: 'Starter',
                modules: ['booking', 'customers'],
                features: [],
                quotas: ['bookings_per_month' => 50],
                integrations: [],
                maxSeats: 2,
            ),
            status: LicenseStatus::ACTIVE,
        );
    }
}
