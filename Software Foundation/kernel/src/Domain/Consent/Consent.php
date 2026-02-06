<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Consent;

/**
 * Immutable value object representing a user's consent for a specific purpose.
 *
 * DSG Art. 6 / DSGVO Art. 7:
 * - Consent must be freely given, specific, informed, and unambiguous
 * - Must be as easy to withdraw as to give
 * - Must record when consent was granted and by whom
 */
final class Consent
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $userId,
        public readonly ConsentPurpose $purpose,
        public readonly bool $granted,
        public readonly \DateTimeImmutable $decidedAt,
        public readonly ?\DateTimeImmutable $expiresAt,
        public readonly ?string $ipAddress,
        public readonly ?string $legalBasis,
    ) {
    }

    /**
     * Grant consent for a purpose.
     */
    public static function grant(
        string $id,
        int $tenantId,
        string $userId,
        ConsentPurpose $purpose,
        \DateTimeImmutable $at,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $ipAddress = null,
    ): self {
        return new self(
            id: $id,
            tenantId: $tenantId,
            userId: $userId,
            purpose: $purpose,
            granted: true,
            decidedAt: $at,
            expiresAt: $expiresAt,
            ipAddress: $ipAddress,
            legalBasis: 'consent',
        );
    }

    /**
     * Revoke consent.
     */
    public static function revoke(
        string $id,
        int $tenantId,
        string $userId,
        ConsentPurpose $purpose,
        \DateTimeImmutable $at,
        ?string $ipAddress = null,
    ): self {
        return new self(
            id: $id,
            tenantId: $tenantId,
            userId: $userId,
            purpose: $purpose,
            granted: false,
            decidedAt: $at,
            expiresAt: null,
            ipAddress: $ipAddress,
            legalBasis: null,
        );
    }

    /**
     * Check if consent is currently valid.
     */
    public function isValid(\DateTimeImmutable $now): bool
    {
        if (!$this->granted) {
            return false;
        }

        if ($this->expiresAt !== null && $now >= $this->expiresAt) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'userId' => $this->userId,
            'purpose' => $this->purpose->value,
            'granted' => $this->granted,
            'decidedAt' => $this->decidedAt->format(\DateTimeInterface::ATOM),
            'expiresAt' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'ipAddress' => $this->ipAddress,
            'legalBasis' => $this->legalBasis,
        ];
    }
}
