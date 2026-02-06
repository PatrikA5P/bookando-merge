<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Dossier;

/**
 * Immutable audit log entry for dossier access.
 *
 * Required by GeBüV Art. 7/8 for revisionssichere Archivierung:
 * - All access to information must be documented and logged
 * - Tax authorities must be able to verify access trails
 */
final class DossierAccessLog
{
    public function __construct(
        public readonly string $dossierId,
        public readonly ?string $entryId,
        public readonly string $userId,
        public readonly DossierAccessAction $action,
        public readonly \DateTimeImmutable $timestamp,
        public readonly string $ipAddress,
        public readonly ?string $reason,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dossierId' => $this->dossierId,
            'entryId' => $this->entryId,
            'userId' => $this->userId,
            'action' => $this->action->value,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ATOM),
            'ipAddress' => $this->ipAddress,
            'reason' => $this->reason,
        ];
    }
}
