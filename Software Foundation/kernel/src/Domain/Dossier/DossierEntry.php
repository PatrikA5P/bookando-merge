<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Dossier;

/**
 * Immutable value object representing a single document/file within a dossier.
 *
 * Each entry is integrity-protected via a SHA-256 hash of the file content.
 * This supports revisionssichere Archivierung (GeBüV Art. 4):
 * - Authenticity and integrity must be ensured
 * - Documents must be reproducible identically and completely
 */
final class DossierEntry
{
    public function __construct(
        public readonly string $id,
        public readonly string $dossierId,
        public readonly string $fileName,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly string $contentHash,
        public readonly string $uploadedBy,
        public readonly \DateTimeImmutable $uploadedAt,
        public readonly ?string $description,
    ) {
        if ($this->fileName === '') {
            throw new \InvalidArgumentException('File name must not be empty.');
        }

        if ($this->sizeBytes < 0) {
            throw new \InvalidArgumentException('File size must not be negative.');
        }

        if (!preg_match('/^[a-f0-9]{64}$/', $this->contentHash)) {
            throw new \InvalidArgumentException('Content hash must be a valid SHA-256 hex string.');
        }
    }

    /**
     * Verify that a given file content matches this entry's hash.
     */
    public function verifyIntegrity(string $fileContent): bool
    {
        return hash('sha256', $fileContent) === $this->contentHash;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dossierId' => $this->dossierId,
            'fileName' => $this->fileName,
            'mimeType' => $this->mimeType,
            'sizeBytes' => $this->sizeBytes,
            'contentHash' => $this->contentHash,
            'uploadedBy' => $this->uploadedBy,
            'uploadedAt' => $this->uploadedAt->format(\DateTimeInterface::ATOM),
            'description' => $this->description,
        ];
    }
}
