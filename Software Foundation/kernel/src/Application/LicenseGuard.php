<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Application;

use SoftwareFoundation\Kernel\Ports\LicenseResolverPort;

/**
 * Checks licensing constraints before command execution.
 * Used as middleware in the command dispatch chain.
 */
final class LicenseGuard
{
    public function __construct(
        private readonly LicenseResolverPort $licenseResolver,
    ) {}

    /** Assert that the tenant's license allows access to a module. */
    public function assertModule(SecurityContext $ctx, string $moduleSlug): void
    {
        if (!$ctx->canAccessModule($moduleSlug)) {
            throw new LicenseViolationException(
                "Module '{$moduleSlug}' is not included in your plan ({$ctx->license->plan->id})"
            );
        }
    }

    /** Assert that a specific feature is enabled. */
    public function assertFeature(SecurityContext $ctx, string $feature): void
    {
        if (!$ctx->hasFeature($feature)) {
            throw new LicenseViolationException(
                "Feature '{$feature}' is not available in your plan ({$ctx->license->plan->id})"
            );
        }
    }

    /** Check and track quota usage. Throws if quota exhausted. */
    public function consumeQuota(SecurityContext $ctx, string $quotaKey, int $amount = 1): void
    {
        $quota = $this->licenseResolver->checkQuota($ctx->tenantId->value(), $quotaKey);

        if (!$quota->canConsume($amount)) {
            throw new QuotaExhaustedException(
                "Quota '{$quotaKey}' exhausted: {$quota->current}/{$quota->limit}"
            );
        }

        $this->licenseResolver->trackUsage($ctx->tenantId->value(), $quotaKey, $amount);
    }
}
