<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\TimeTracking\TimeEntry;
use SoftwareFoundation\Kernel\Domain\TimeTracking\WorkTimeValidation;

/**
 * Port for ArG-compliant time tracking.
 *
 * Swiss Arbeitsgesetz (ArG) Art. 46 / ArGV 1 Art. 73 requires:
 * - Recording duration, start, and end of daily/weekly working time
 * - Recording compensatory and overtime work
 * - Recording breaks of 30 minutes or more
 * - Retention for at least 5 years
 *
 * Records must be stored completely, comprehensibly, and in an auditable manner.
 */
interface TimeTrackingPort
{
    /**
     * Record a time entry.
     */
    public function record(TimeEntry $entry): void;

    /**
     * Get all entries for a user on a specific date.
     *
     * @return TimeEntry[]
     */
    public function getEntriesForDate(int $tenantId, string $userId, \DateTimeImmutable $date): array;

    /**
     * Get all entries for a user in a date range.
     *
     * @return TimeEntry[]
     */
    public function getEntriesForPeriod(
        int $tenantId,
        string $userId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;

    /**
     * Validate a day's entries against ArG requirements.
     */
    public function validateDay(int $tenantId, string $userId, \DateTimeImmutable $date): WorkTimeValidation;

    /**
     * Calculate total working hours for a period.
     *
     * @return array{net_minutes: int, gross_minutes: int, overtime_minutes: int, break_minutes: int}
     */
    public function calculateTotals(
        int $tenantId,
        string $userId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;

    /**
     * Delete a time entry (only allowed for entries in OPEN dossiers).
     */
    public function delete(int $tenantId, string $entryId): void;

    /**
     * Export time entries for a period (e.g. for payroll or auditor).
     *
     * @return TimeEntry[]
     */
    public function exportForPeriod(
        int $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;
}
