<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Signature;

final class SignedDocument
{
    public function __construct(
        public readonly string $documentHash,
        public readonly string $signatureBytes,
        public readonly string $signerId,
        public readonly SignatureLevel $level,
        public readonly \DateTimeImmutable $timestamp,
        public readonly ?string $certificateChain,
        public readonly string $provider,
    ) {
        if (!preg_match('/\A[0-9a-f]{64}\z/', $this->documentHash)) {
            throw new \InvalidArgumentException(
                'Document hash must be a 64-character lowercase hexadecimal string (SHA-256).'
            );
        }
    }

    public function isQualified(): bool
    {
        return $this->level === SignatureLevel::QES;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'documentHash' => $this->documentHash,
            'signatureBytes' => $this->signatureBytes,
            'signerId' => $this->signerId,
            'level' => $this->level->value,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ATOM),
            'certificateChain' => $this->certificateChain,
            'provider' => $this->provider,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            documentHash: $data['documentHash'],
            signatureBytes: $data['signatureBytes'],
            signerId: $data['signerId'],
            level: SignatureLevel::from($data['level']),
            timestamp: new \DateTimeImmutable($data['timestamp']),
            certificateChain: $data['certificateChain'] ?? null,
            provider: $data['provider'],
        );
    }
}
