<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Consent\Consent;
use SoftwareFoundation\Kernel\Domain\Consent\ConsentPurpose;
use SoftwareFoundation\Kernel\Ports\ConsentPort;

/**
 * In-memory test implementation of ConsentPort.
 *
 * Stores Consent objects in array for isolated testing
 * without external dependencies.
 */
final class InMemoryConsent implements ConsentPort
{
    /**
     * @var array<string, Consent>
     */
    private array $consents = [];

    public function record(Consent $consent): void
    {
        $this->consents[$consent->id] = $consent;
    }

    public function hasConsent(
        int $tenantId,
        string $userId,
        ConsentPurpose $purpose,
        \DateTimeImmutable $asOf,
    ): bool {
        $latest = $this->getLatest($tenantId, $userId, $purpose);

        if ($latest === null) {
            return false;
        }

        return $latest->isValid($asOf);
    }

    public function getLatest(
        int $tenantId,
        string $userId,
        ConsentPurpose $purpose,
    ): ?Consent {
        $matching = [];

        foreach ($this->consents as $consent) {
            if (
                $consent->tenantId === $tenantId
                && $consent->userId === $userId
                && $consent->purpose === $purpose
            ) {
                $matching[] = $consent;
            }
        }

        if (empty($matching)) {
            return null;
        }

        // Sort by decidedAt descending to get the latest
        usort($matching, fn(Consent $a, Consent $b) => $b->decidedAt <=> $a->decidedAt);

        return $matching[0];
    }

    public function getAllForUser(int $tenantId, string $userId): array
    {
        $result = [];

        foreach ($this->consents as $consent) {
            if ($consent->tenantId === $tenantId && $consent->userId === $userId) {
                $result[] = $consent;
            }
        }

        return $result;
    }

    public function revokeAll(int $tenantId, string $userId, \DateTimeImmutable $revokedAt): void
    {
        $allConsents = $this->getAllForUser($tenantId, $userId);

        foreach ($allConsents as $consent) {
            if ($consent->granted) {
                $revoked = Consent::revoke(
                    id: 'revoked_' . $consent->id . '_' . $revokedAt->getTimestamp(),
                    tenantId: $tenantId,
                    userId: $userId,
                    purpose: $consent->purpose,
                    at: $revokedAt,
                    ipAddress: null,
                );
                $this->record($revoked);
            }
        }
    }

    public function findExpired(int $tenantId, \DateTimeImmutable $asOf): array
    {
        $expired = [];

        foreach ($this->consents as $consent) {
            if (
                $consent->tenantId === $tenantId
                && $consent->granted
                && !$consent->isValid($asOf)
            ) {
                $expired[] = $consent;
            }
        }

        return $expired;
    }

    /**
     * Clear all data (for test isolation).
     */
    public function clear(): void
    {
        $this->consents = [];
    }
}
