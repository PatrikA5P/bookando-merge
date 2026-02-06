<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Contracts;

/**
 * Contract that modules implement to provide integrity checks for audit reports.
 *
 * When a "Prüfer-Export" (auditor export) is generated, the system collects
 * AuditReportContract implementations from all active modules and includes
 * their integrity check results in the report.
 */
interface AuditReportContract
{
    /**
     * Human-readable module name for the report.
     */
    public function moduleName(): string;

    /**
     * Run all integrity checks for the given tenant.
     *
     * Returns an array of check results, each containing:
     * - 'check' (string): Name of the check
     * - 'passed' (bool): Whether the check passed
     * - 'details' (string): Human-readable result
     * - 'checked_records' (int): Number of records verified
     *
     * @param int $tenantId The tenant to check.
     *
     * @return array<int, array{check: string, passed: bool, details: string, checked_records: int}>
     */
    public function runIntegrityChecks(int $tenantId): array;

    /**
     * Export all auditable data for the given tenant in a structured format.
     *
     * @param int    $tenantId The tenant to export.
     * @param string $format   Export format ('json', 'csv', 'xml').
     *
     * @return array<string, mixed> Structured data ready for serialization.
     */
    public function exportAuditData(int $tenantId, string $format = 'json'): array;
}
