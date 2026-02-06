<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Integrity\HashChain;
use SoftwareFoundation\Kernel\Domain\Integrity\IntegrityCheckResult;

/**
 * Port for managing tamper-evident hash chains.
 *
 * Each tenant has an independent hash chain that provides cryptographic
 * proof of data integrity. Every mutation to financial records must be
 * appended to the chain, creating an immutable audit trail.
 *
 * Required by: GoBD (Unveränderbarkeit), OR Art. 958f (Datenintegrität).
 */
interface HashChainPort
{
    /**
     * Append a new entry to the tenant's hash chain.
     *
     * Computes the entry hash from the previous entry's hash, the payload,
     * and the creation timestamp. If no previous entry exists, creates a
     * genesis entry.
     *
     * @param int                $tenantId     Tenant identifier.
     * @param string             $entryPayload Serialized payload to hash.
     * @param \DateTimeImmutable $createdAt    Timestamp of the entry.
     *
     * @return HashChain The newly created hash chain entry.
     */
    public function append(int $tenantId, string $entryPayload, \DateTimeImmutable $createdAt): HashChain;

    /**
     * Verify the integrity of a tenant's hash chain.
     *
     * Walks the chain from `$fromSequence` to `$toSequence` (or the latest
     * entry if null), recomputing each hash and verifying the linkage.
     *
     * @param int      $tenantId     Tenant identifier.
     * @param int      $fromSequence Start sequence number (default: 1).
     * @param int|null $toSequence   End sequence number (default: latest).
     *
     * @return IntegrityCheckResult Result of the verification.
     */
    public function verify(int $tenantId, int $fromSequence = 1, ?int $toSequence = null): IntegrityCheckResult;

    /**
     * Retrieve a specific hash chain entry.
     *
     * @param int $tenantId       Tenant identifier.
     * @param int $sequenceNumber Sequence number to retrieve.
     *
     * @return HashChain|null The entry, or null if not found.
     */
    public function getEntry(int $tenantId, int $sequenceNumber): ?HashChain;

    /**
     * Retrieve the latest hash chain entry for a tenant.
     *
     * @param int $tenantId Tenant identifier.
     *
     * @return HashChain|null The latest entry, or null if chain is empty.
     */
    public function getLatest(int $tenantId): ?HashChain;
}
