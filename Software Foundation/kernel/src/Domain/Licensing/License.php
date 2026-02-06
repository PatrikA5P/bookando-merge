<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Licensing;

use SoftwareFoundation\Kernel\Domain\Tenant\TenantId;

/**
 * A tenant's active license binding them to a Plan.
 *
 * The License knows what the tenant is allowed to do.
 * It is resolved by the LicenseResolverPort (different per runtime).
 */
final class License
{
    public const GRACE_PERIOD_DAYS = 7;

    public function __construct(
        public readonly TenantId $tenantId,
        public readonly Plan $plan,
        public readonly LicenseStatus $status,
        public readonly ?\DateTimeImmutable $expiresAt = null,
        public readonly ?string $licenseKey = null,
        public readonly ?string $externalSubscriptionId = null,
    ) {}

    /** Is this license currently valid (active or in grace period)? */
    public function isValid(): bool
    {
        if ($this->status === LicenseStatus::ACTIVE) {
            return true;
        }

        if ($this->status === LicenseStatus::GRACE) {
            return $this->isWithinGracePeriod();
        }

        return false;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }
        return $this->expiresAt < new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function isWithinGracePeriod(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $graceEnd = $this->expiresAt->modify('+' . self::GRACE_PERIOD_DAYS . ' days');

        return $now > $this->expiresAt && $now <= $graceEnd;
    }

    public function graceDaysRemaining(): int
    {
        if (!$this->isWithinGracePeriod()) {
            return 0;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $graceEnd = $this->expiresAt->modify('+' . self::GRACE_PERIOD_DAYS . ' days');

        return max(0, (int) $now->diff($graceEnd)->days);
    }

    // --- Delegation to Plan ---

    public function canAccessModule(string $moduleSlug): bool
    {
        return $this->isValid() && $this->plan->includesModule($moduleSlug);
    }

    public function hasFeature(string $featureKey): bool
    {
        return $this->isValid() && $this->plan->hasFeature($featureKey);
    }

    public function canUseIntegration(string $integrationSlug): bool
    {
        return $this->isValid() && $this->plan->hasIntegration($integrationSlug);
    }

    public function quotaLimit(string $key): int
    {
        if (!$this->isValid()) {
            return 0;
        }
        return $this->plan->quotaLimit($key);
    }

    public function isUnlimitedQuota(string $key): bool
    {
        return $this->isValid() && $this->plan->isUnlimitedQuota($key);
    }

    public function maxSeats(): int
    {
        if (!$this->isValid()) {
            return 0;
        }
        return $this->plan->maxSeats;
    }
}
