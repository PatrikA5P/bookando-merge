<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Licensing\License;
use SoftwareFoundation\Kernel\Domain\Licensing\UsageQuota;

/**
 * License resolution port.
 *
 * Provides a host-agnostic interface for resolving the active license for
 * a given tenant at runtime, checking feature entitlements, inspecting
 * usage quotas, and tracking consumption against those quotas.
 *
 * Implementations may resolve licenses from a local database, a remote
 * licensing server, a signed JWT, or any other mechanism appropriate
 * for the host environment.
 */
interface LicenseResolverPort
{
    /**
     * Resolve the active license for the given tenant.
     *
     * @param int $tenantId The tenant identifier.
     *
     * @return License The resolved license object.
     *
     * @throws \RuntimeException If no valid license can be resolved.
     */
    public function resolve(int $tenantId): License;

    /**
     * Check whether the tenant's license includes the given feature.
     *
     * @param int    $tenantId The tenant identifier.
     * @param string $feature  Feature slug to check (e.g. "sms_notifications", "multi_location").
     *
     * @return bool True if the feature is available under the tenant's license.
     */
    public function hasFeature(int $tenantId, string $feature): bool;

    /**
     * Retrieve the current usage quota status for a given quota key.
     *
     * @param int    $tenantId The tenant identifier.
     * @param string $quotaKey Quota slug to check (e.g. "bookings_per_month", "api_calls").
     *
     * @return UsageQuota Current quota status including limit, used, and remaining.
     */
    public function checkQuota(int $tenantId, string $quotaKey): UsageQuota;

    /**
     * Track usage against a quota.
     *
     * Increments the consumed amount for the given quota key by `$delta`.
     *
     * @param int    $tenantId The tenant identifier.
     * @param string $quotaKey Quota slug to increment.
     * @param int    $delta    Amount to add to current usage. Default 1.
     */
    public function trackUsage(int $tenantId, string $quotaKey, int $delta = 1): void;
}
