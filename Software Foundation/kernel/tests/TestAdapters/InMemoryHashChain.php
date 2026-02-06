<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Integrity\HashChain;
use SoftwareFoundation\Kernel\Domain\Integrity\IntegrityCheckResult;
use SoftwareFoundation\Kernel\Ports\HashChainPort;

/**
 * In-memory hash chain for testing. Stores entries per tenant with real SHA-256 hashing.
 */
final class InMemoryHashChain implements HashChainPort
{
    /** @var array<string, HashChain> "tenantId:sequenceNumber" => HashChain */
    private array $entries = [];

    /** @var array<int, string> tenantId => payload of last entry (for verification) */
    private array $payloads = [];

    /** @var array<string, string> "tenantId:sequenceNumber" => payload */
    private array $entryPayloads = [];

    public function append(int $tenantId, string $entryPayload, \DateTimeImmutable $createdAt): HashChain
    {
        $latest = $this->getLatest($tenantId);
        $sequenceNumber = $latest !== null ? $latest->sequenceNumber() + 1 : 1;

        $previousHash = $latest !== null
            ? $latest->entryHash()
            : str_repeat('0', 64);

        $timestamp = $createdAt->format('Y-m-d\TH:i:s\Z');
        $entryHash = HashChain::computeHash($previousHash, $entryPayload, $timestamp);

        $entry = new HashChain(
            $tenantId,
            $sequenceNumber,
            $entryHash,
            $previousHash,
            $createdAt,
        );

        $key = "{$tenantId}:{$sequenceNumber}";
        $this->entries[$key] = $entry;
        $this->entryPayloads[$key] = $entryPayload;

        return $entry;
    }

    public function verify(int $tenantId, int $fromSequence = 1, ?int $toSequence = null): IntegrityCheckResult
    {
        $latest = $this->getLatest($tenantId);
        $toSequence ??= $latest?->sequenceNumber() ?? 0;

        if ($toSequence < $fromSequence) {
            return IntegrityCheckResult::success(0, new \DateTimeImmutable());
        }

        $failedEntries = [];
        $checked = 0;

        for ($seq = $fromSequence; $seq <= $toSequence; $seq++) {
            $checked++;
            $entry = $this->getEntry($tenantId, $seq);

            if ($entry === null) {
                $failedEntries[] = ['sequenceNumber' => $seq, 'reason' => 'Entry not found'];
                continue;
            }

            $payload = $this->entryPayloads["{$tenantId}:{$seq}"] ?? '';
            $timestamp = $entry->createdAt()->format('Y-m-d\TH:i:s\Z');

            $expectedPreviousHash = $seq === 1
                ? str_repeat('0', 64)
                : ($this->getEntry($tenantId, $seq - 1)?->entryHash() ?? '');

            if ($entry->previousHash() !== $expectedPreviousHash) {
                $failedEntries[] = ['sequenceNumber' => $seq, 'reason' => 'Previous hash mismatch'];
                continue;
            }

            $recomputed = HashChain::computeHash($entry->previousHash(), $payload, $timestamp);
            if (!hash_equals($entry->entryHash(), $recomputed)) {
                $failedEntries[] = ['sequenceNumber' => $seq, 'reason' => 'Hash mismatch'];
            }
        }

        $now = new \DateTimeImmutable();

        if ($failedEntries === []) {
            return IntegrityCheckResult::success($checked, $now);
        }

        return IntegrityCheckResult::failure($checked, $failedEntries, $now);
    }

    public function getEntry(int $tenantId, int $sequenceNumber): ?HashChain
    {
        return $this->entries["{$tenantId}:{$sequenceNumber}"] ?? null;
    }

    public function getLatest(int $tenantId): ?HashChain
    {
        $latest = null;

        foreach ($this->entries as $key => $entry) {
            if ($entry->tenantId() === $tenantId) {
                if ($latest === null || $entry->sequenceNumber() > $latest->sequenceNumber()) {
                    $latest = $entry;
                }
            }
        }

        return $latest;
    }

    // --- Test helpers ---

    /** @return HashChain[] All entries for a tenant, ordered by sequence number. */
    public function allEntries(int $tenantId): array
    {
        $result = [];

        foreach ($this->entries as $entry) {
            if ($entry->tenantId() === $tenantId) {
                $result[$entry->sequenceNumber()] = $entry;
            }
        }

        ksort($result);

        return array_values($result);
    }

    /** Return the number of entries for a tenant. */
    public function count(int $tenantId): int
    {
        return count($this->allEntries($tenantId));
    }
}
