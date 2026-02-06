<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Dossier;

/**
 * Immutable value object representing a dossier (document collection).
 *
 * A dossier is a legally mandated container for related documents.
 * It enforces retention periods, encryption requirements, and access control
 * as required by Swiss OR Art. 958f, GeBüV, DSG, and ArG.
 *
 * Revisionssichere Archivierung (GeBüV):
 * - All access must be logged (DossierAccessLog)
 * - Documents must be stored completely, immutably, and available at all times
 * - Changes must be traceable
 */
final class Dossier
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly DossierType $type,
        public readonly DossierStatus $status,
        public readonly string $title,
        public readonly ?string $ownerId,
        public readonly bool $encrypted,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $closedAt,
        public readonly ?\DateTimeImmutable $retentionUntil,
    ) {
        if ($this->title === '') {
            throw new \InvalidArgumentException('Dossier title must not be empty.');
        }

        if ($this->type->requiresEncryption() && !$this->encrypted) {
            throw new \InvalidArgumentException(
                sprintf('Dossier type "%s" requires encryption at rest.', $this->type->value)
            );
        }
    }

    /**
     * Create an open dossier with automatic retention calculation.
     */
    public static function open(
        string $id,
        int $tenantId,
        DossierType $type,
        string $title,
        ?string $ownerId,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            tenantId: $tenantId,
            type: $type,
            status: DossierStatus::OPEN,
            title: $title,
            ownerId: $ownerId,
            encrypted: $type->requiresEncryption(),
            createdAt: $createdAt,
            closedAt: null,
            retentionUntil: null,
        );
    }

    /**
     * Close the dossier and calculate retention deadline.
     *
     * For HR dossiers: retention starts from closure (= departure date).
     * For accounting dossiers: retention starts from end of financial year.
     */
    public function close(\DateTimeImmutable $closedAt): self
    {
        if (!$this->status->isModifiable()) {
            throw new \LogicException(
                sprintf('Cannot close dossier in status "%s".', $this->status->value)
            );
        }

        $retentionYears = $this->type->retentionYears();
        $retentionUntil = $retentionYears > 0
            ? $closedAt->modify("+{$retentionYears} years")
            : $closedAt; // APPLICANT: immediate deletion allowed

        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            status: DossierStatus::CLOSED,
            title: $this->title,
            ownerId: $this->ownerId,
            encrypted: $this->encrypted,
            createdAt: $this->createdAt,
            closedAt: $closedAt,
            retentionUntil: $retentionUntil,
        );
    }

    /**
     * Check if retention period has expired as of the given date.
     */
    public function isRetentionExpired(\DateTimeImmutable $now): bool
    {
        if ($this->retentionUntil === null) {
            return false;
        }

        return $now >= $this->retentionUntil;
    }
}
