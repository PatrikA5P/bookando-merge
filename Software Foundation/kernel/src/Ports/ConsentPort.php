<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Consent\Consent;
use SoftwareFoundation\Kernel\Domain\Consent\ConsentPurpose;

/**
 * Port for DSG/DSGVO-compliant consent management.
 *
 * DSG Art. 6 / DSGVO Art. 7 require:
 * - Consent must be freely given, specific, informed, and unambiguous
 * - Must be as easy to withdraw as to give
 * - Controller must be able to demonstrate that consent was given
 * - Separate consent for each purpose
 *
 * Critical for:
 * - Storing employee photos (Personaldossier)
 * - Processing sensitive personal data (biometric, health)
 * - Cross-border data transfers (e.g. cloud providers in non-CH/EU countries)
 * - Marketing communications
 */
interface ConsentPort
{
    /**
     * Record a consent decision (grant or revoke).
     */
    public function record(Consent $consent): void;

    /**
     * Check if a user has active consent for a specific purpose.
     */
    public function hasConsent(
        int $tenantId,
        string $userId,
        ConsentPurpose $purpose,
        \DateTimeImmutable $asOf,
    ): bool;

    /**
     * Get the latest consent decision for a user and purpose.
     */
    public function getLatest(
        int $tenantId,
        string $userId,
        ConsentPurpose $purpose,
    ): ?Consent;

    /**
     * Get all consent decisions for a user (for DSGVO Art. 20 export).
     *
     * @return Consent[]
     */
    public function getAllForUser(int $tenantId, string $userId): array;

    /**
     * Revoke all consents for a user (e.g. on account deletion).
     */
    public function revokeAll(int $tenantId, string $userId, \DateTimeImmutable $revokedAt): void;

    /**
     * Find expired consents that need renewal.
     *
     * @return Consent[]
     */
    public function findExpired(int $tenantId, \DateTimeImmutable $asOf): array;
}
