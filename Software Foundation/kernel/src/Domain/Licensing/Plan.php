<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Licensing;

/**
 * A subscription plan that defines what a tenant can access.
 *
 * Plans are immutable definitions. A tenant's License references a Plan.
 * The plan catalog is managed by the platform operator, not by tenants.
 */
final class Plan
{
    /**
     * @param string   $id           Unique plan identifier (e.g., 'starter', 'professional', 'enterprise')
     * @param string   $name         Display name
     * @param string[] $modules      Module slugs included (e.g., ['booking', 'customers', 'finance'])
     * @param string[] $features     Feature flags included (e.g., ['export_csv', 'api_write', 'white_label'])
     * @param array<string, int> $quotas  Usage limits: key → max (-1 = unlimited)
     * @param string[] $integrations Integration slugs allowed
     * @param int      $maxSeats     Maximum user seats (-1 = unlimited)
     * @param int      $sortOrder    Display order (lower = cheaper)
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $modules,
        public readonly array $features,
        public readonly array $quotas,
        public readonly array $integrations,
        public readonly int $maxSeats = -1,
        public readonly int $sortOrder = 0,
    ) {}

    public function includesModule(string $moduleSlug): bool
    {
        return in_array($moduleSlug, $this->modules, true);
    }

    public function hasFeature(string $featureKey): bool
    {
        return in_array($featureKey, $this->features, true);
    }

    public function hasIntegration(string $integrationSlug): bool
    {
        return in_array($integrationSlug, $this->integrations, true);
    }

    /**
     * Get the quota limit for a key. Returns -1 for unlimited.
     */
    public function quotaLimit(string $key): int
    {
        return $this->quotas[$key] ?? 0;
    }

    public function isUnlimitedQuota(string $key): bool
    {
        return ($this->quotas[$key] ?? 0) === -1;
    }

    public function isUnlimitedSeats(): bool
    {
        return $this->maxSeats === -1;
    }
}
