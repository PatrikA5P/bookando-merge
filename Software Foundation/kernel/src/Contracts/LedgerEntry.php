<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Contracts;

/**
 * Contract that modules implement to provide hashable ledger entries.
 *
 * Any entity that must be part of a tamper-proof hash chain (journal entries,
 * invoices, audit records) implements this interface. The kernel's HashChainPort
 * uses the payload to compute and verify hashes.
 */
interface LedgerEntry
{
    /**
     * Unique identifier for this entry within the tenant.
     */
    public function entryId(): string;

    /**
     * Tenant this entry belongs to.
     */
    public function tenantId(): int;

    /**
     * Deterministic string representation of the entry's data for hashing.
     *
     * MUST be deterministic: same data → same string, always.
     * MUST include all business-relevant fields.
     * MUST NOT include metadata (created_at, updated_at).
     */
    public function hashPayload(): string;

    /**
     * ISO-8601 UTC timestamp when the entry was created.
     */
    public function createdAtUtc(): string;

    /**
     * Human-readable type identifier (e.g. 'journal_entry', 'invoice').
     */
    public function entryType(): string;
}
