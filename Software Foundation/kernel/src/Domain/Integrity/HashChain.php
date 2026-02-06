<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Integrity;

/**
 * Immutable value object representing a single hash chain entry.
 *
 * Each entry is cryptographically linked to its predecessor via SHA-256,
 * forming a tamper-evident chain per tenant. This satisfies the integrity
 * requirements of GoBD (Grundsätze ordnungsmäßiger Buchführung) and
 * OR Art. 958f for financial record immutability.
 *
 * INVARIANTS:
 * - sequenceNumber >= 1
 * - entryHash is a 64-character lowercase hex string (SHA-256)
 * - previousHash is a 64-character lowercase hex string, or '0' only when sequenceNumber is 1
 * - Genesis entry (seq=1) has previousHash = 64 zeros
 */
final class HashChain
{
    private int $tenantId;
    private int $sequenceNumber;
    private string $entryHash;
    private string $previousHash;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $tenantId,
        int $sequenceNumber,
        string $entryHash,
        string $previousHash,
        \DateTimeImmutable $createdAt,
    ) {
        if ($tenantId <= 0) {
            throw new \InvalidArgumentException(
                "tenantId must be a positive integer, got {$tenantId}"
            );
        }

        if ($sequenceNumber < 1) {
            throw new \InvalidArgumentException(
                "sequenceNumber must be >= 1, got {$sequenceNumber}"
            );
        }

        if (!self::isValidHash($entryHash)) {
            throw new \InvalidArgumentException(
                "entryHash must be a 64-character hex string, got '{$entryHash}'"
            );
        }

        if ($sequenceNumber === 1) {
            if (!self::isValidHash($previousHash) && $previousHash !== '0') {
                throw new \InvalidArgumentException(
                    "previousHash for genesis entry must be a 64-character hex string or '0', got '{$previousHash}'"
                );
            }
        } else {
            if (!self::isValidHash($previousHash)) {
                throw new \InvalidArgumentException(
                    "previousHash must be a 64-character hex string, got '{$previousHash}'"
                );
            }
        }

        $this->tenantId = $tenantId;
        $this->sequenceNumber = $sequenceNumber;
        $this->entryHash = $entryHash;
        $this->previousHash = $previousHash;
        $this->createdAt = $createdAt;
    }

    /**
     * Create the genesis (first) entry for a tenant's hash chain.
     *
     * The genesis entry has sequenceNumber=1 and previousHash set to 64 zeros.
     */
    public static function genesis(int $tenantId, string $entryHash, \DateTimeImmutable $createdAt): self
    {
        return new self(
            $tenantId,
            1,
            $entryHash,
            str_repeat('0', 64),
            $createdAt,
        );
    }

    /**
     * Compute a SHA-256 hash from the concatenation of previousHash, payload, and timestamp.
     */
    public static function computeHash(string $previousHash, string $payload, string $timestamp): string
    {
        return hash('sha256', $previousHash . $payload . $timestamp);
    }

    /**
     * Verify this entry by recomputing the hash from the expected payload and timestamp.
     */
    public function verify(string $expectedPayload, string $expectedTimestamp): bool
    {
        $recomputed = self::computeHash($this->previousHash, $expectedPayload, $expectedTimestamp);

        return hash_equals($this->entryHash, $recomputed);
    }

    public function tenantId(): int
    {
        return $this->tenantId;
    }

    public function sequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function entryHash(): string
    {
        return $this->entryHash;
    }

    public function previousHash(): string
    {
        return $this->previousHash;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array{tenant_id: int, sequence_number: int, entry_hash: string, previous_hash: string, created_at: string}
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'sequence_number' => $this->sequenceNumber,
            'entry_hash' => $this->entryHash,
            'previous_hash' => $this->previousHash,
            'created_at' => $this->createdAt->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * @param array{tenant_id: int, sequence_number: int, entry_hash: string, previous_hash: string, created_at: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['tenant_id'],
            $data['sequence_number'],
            $data['entry_hash'],
            $data['previous_hash'],
            new \DateTimeImmutable($data['created_at']),
        );
    }

    private static function isValidHash(string $hash): bool
    {
        return (bool) preg_match('/^[0-9a-f]{64}$/', $hash);
    }
}
