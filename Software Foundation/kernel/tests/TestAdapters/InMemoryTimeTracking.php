<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\TimeTracking\TimeEntry;
use SoftwareFoundation\Kernel\Domain\TimeTracking\WorkTimeValidation;
use SoftwareFoundation\Kernel\Ports\TimeTrackingPort;

/**
 * In-memory test implementation of TimeTrackingPort.
 *
 * Stores TimeEntry objects in array for isolated testing
 * without external dependencies.
 */
final class InMemoryTimeTracking implements TimeTrackingPort
{
    /**
     * @var array<string, TimeEntry>
     */
    private array $entries = [];

    public function record(TimeEntry $entry): void
    {
        $this->entries[$entry->id] = $entry;
    }

    public function getEntriesForDate(int $tenantId, string $userId, \DateTimeImmutable $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $result = [];

        foreach ($this->entries as $entry) {
            if (
                $entry->tenantId === $tenantId
                && $entry->userId === $userId
                && $entry->date->format('Y-m-d') === $dateStr
            ) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    public function getEntriesForPeriod(
        int $tenantId,
        string $userId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        $result = [];

        foreach ($this->entries as $entry) {
            if (
                $entry->tenantId === $tenantId
                && $entry->userId === $userId
                && $entry->date >= $from
                && $entry->date <= $to
            ) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    public function validateDay(int $tenantId, string $userId, \DateTimeImmutable $date): WorkTimeValidation
    {
        $entries = $this->getEntriesForDate($tenantId, $userId, $date);
        return WorkTimeValidation::validateDay($entries);
    }

    public function calculateTotals(
        int $tenantId,
        string $userId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        $entries = $this->getEntriesForPeriod($tenantId, $userId, $from, $to);

        $netMinutes = 0;
        $grossMinutes = 0;
        $overtimeMinutes = 0;
        $breakMinutes = 0;

        foreach ($entries as $entry) {
            $netMinutes += $entry->netMinutes();
            $grossMinutes += $entry->grossMinutes();
            $breakMinutes += $entry->breakMinutes;

            if ($entry->type->value === 'overtime') {
                $overtimeMinutes += $entry->netMinutes();
            }
        }

        return [
            'net_minutes' => $netMinutes,
            'gross_minutes' => $grossMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'break_minutes' => $breakMinutes,
        ];
    }

    public function delete(int $tenantId, string $entryId): void
    {
        if (isset($this->entries[$entryId])) {
            unset($this->entries[$entryId]);
        }
    }

    public function exportForPeriod(
        int $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        $result = [];

        foreach ($this->entries as $entry) {
            if (
                $entry->tenantId === $tenantId
                && $entry->date >= $from
                && $entry->date <= $to
            ) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    /**
     * Clear all data (for test isolation).
     */
    public function clear(): void
    {
        $this->entries = [];
    }
}
