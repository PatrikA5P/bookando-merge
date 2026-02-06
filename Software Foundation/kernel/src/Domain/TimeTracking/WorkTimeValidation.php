<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\TimeTracking;

/**
 * ArG-compliant work time validation result.
 *
 * Swiss Arbeitsgesetz (ArG) limits:
 * - Max 45h/week for industrial workers, office staff, retail, large enterprises
 * - Max 50h/week for all other employees
 * - Mandatory breaks: 15min (>5.5h), 30min (>7h), 60min (>9h)
 * - Max 2h overtime per day, 170h (45h) or 140h (50h) per year
 */
final class WorkTimeValidation
{
    /**
     * @param string[] $violations
     * @param string[] $warnings
     */
    public function __construct(
        public readonly bool $compliant,
        public readonly array $violations,
        public readonly array $warnings,
        public readonly int $totalNetMinutes,
        public readonly int $totalBreakMinutes,
        public readonly int $totalOvertimeMinutes,
    ) {
    }

    public static function pass(int $netMinutes, int $breakMinutes, int $overtimeMinutes): self
    {
        return new self(
            compliant: true,
            violations: [],
            warnings: [],
            totalNetMinutes: $netMinutes,
            totalBreakMinutes: $breakMinutes,
            totalOvertimeMinutes: $overtimeMinutes,
        );
    }

    /**
     * @param string[] $violations
     * @param string[] $warnings
     */
    public static function fail(
        array $violations,
        int $netMinutes,
        int $breakMinutes,
        int $overtimeMinutes,
        array $warnings = [],
    ): self {
        return new self(
            compliant: false,
            violations: $violations,
            warnings: $warnings,
            totalNetMinutes: $netMinutes,
            totalBreakMinutes: $breakMinutes,
            totalOvertimeMinutes: $overtimeMinutes,
        );
    }

    /**
     * Validate a single day's entries against ArG break requirements.
     *
     * @param TimeEntry[] $entries Entries for a single day
     */
    public static function validateDay(array $entries): self
    {
        $totalNet = 0;
        $totalBreak = 0;
        $totalOvertime = 0;
        $violations = [];
        $warnings = [];

        foreach ($entries as $entry) {
            $totalNet += $entry->netMinutes();
            $totalBreak += $entry->breakMinutes;
            if ($entry->type === TimeEntryType::OVERTIME) {
                $totalOvertime += $entry->netMinutes();
            }
        }

        // ArG mandatory break rules
        if ($totalNet > 540 && $totalBreak < 60) { // >9h → 60min break
            $violations[] = 'ArG: >9h requires minimum 60 minutes break.';
        } elseif ($totalNet > 420 && $totalBreak < 30) { // >7h → 30min break
            $violations[] = 'ArG: >7h requires minimum 30 minutes break.';
        } elseif ($totalNet > 330 && $totalBreak < 15) { // >5.5h → 15min break
            $violations[] = 'ArG: >5.5h requires minimum 15 minutes break.';
        }

        // Daily overtime limit
        if ($totalOvertime > 120) {
            $violations[] = 'ArG: Maximum 2 hours overtime per day exceeded.';
        }

        // Warning for long days
        if ($totalNet > 600) {
            $warnings[] = 'Working day exceeds 10 hours — consider rest requirements.';
        }

        return count($violations) > 0
            ? self::fail($violations, $totalNet, $totalBreak, $totalOvertime, $warnings)
            : self::pass($totalNet, $totalBreak, $totalOvertime);
    }
}
