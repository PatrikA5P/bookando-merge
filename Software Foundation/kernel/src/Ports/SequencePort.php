<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Sequence\SequenceGap;

/**
 * Port for gap-free document numbering sequences.
 *
 * Provides monotonically increasing, gap-free numbering for fiscal documents
 * such as invoices and journal entries. Sequences are scoped per tenant,
 * document type (prefix), and fiscal year.
 *
 * Required by: GoBD (lückenlose Nummerierung), OR Art. 958f.
 */
interface SequencePort
{
    /**
     * Generate the next formatted sequence number.
     *
     * Atomically increments the counter and returns the formatted value
     * according to the configured pattern (e.g. "RE-2026-000042").
     *
     * @param int    $tenantId Tenant identifier.
     * @param string $prefix   Document type prefix (e.g. 'invoice', 'journal').
     * @param int    $year     Fiscal year.
     *
     * @return string Formatted sequence number.
     */
    public function next(int $tenantId, string $prefix, int $year): string;

    /**
     * Return the current counter value without incrementing.
     *
     * @param int    $tenantId Tenant identifier.
     * @param string $prefix   Document type prefix.
     * @param int    $year     Fiscal year.
     *
     * @return int Current counter value (0 if no entries yet).
     */
    public function current(int $tenantId, string $prefix, int $year): int;

    /**
     * Detect gaps in the numbering sequence.
     *
     * Scans the issued numbers for the given tenant/prefix/year and returns
     * any gaps found. An empty array means the sequence is gap-free.
     *
     * @param int    $tenantId Tenant identifier.
     * @param string $prefix   Document type prefix.
     * @param int    $year     Fiscal year.
     *
     * @return SequenceGap[] List of detected gaps.
     */
    public function detectGaps(int $tenantId, string $prefix, int $year): array;

    /**
     * Set the format pattern for a document type prefix.
     *
     * Patterns use placeholders: {PREFIX}, {YYYY}, {000000} (zero-padded number).
     * Example: "{PREFIX}-{YYYY}-{000000}" produces "RE-2026-000042".
     *
     * @param string $prefix  Document type prefix.
     * @param string $pattern Format pattern string.
     */
    public function setFormat(string $prefix, string $pattern): void;
}
