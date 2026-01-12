<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Services;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use function array_key_exists;
use function array_reverse;
use function array_slice;
use function array_values;
use function ceil;
use function count;
use function get_option;
use function max;
use function round;
use function sanitize_key;
use function sanitize_text_field;
use function uniqid;
use function update_option;
use function wp_date;
use function wp_parse_args;
use function wp_timezone;

/**
 * Lightweight time tracking service with manual entries and timers.
 */
class TimeTrackingService
{
    private const ENTRIES_OPTION = 'bookando_time_tracking_entries';
    private const RUNNING_OPTION = 'bookando_time_tracking_running';
    private const RULES_OPTION   = 'bookando_time_tracking_rules';
    private const MAX_ENTRIES    = 2000;

    /**
     * @return array<string, mixed>
     */
    public static function getState(): array
    {
        $entries = self::getEntries();
        $running = self::getRunning();
        $rules   = self::getRules();
        $summary = self::buildSummary($entries, $running, $rules);

        return [
            'entries' => array_slice(array_reverse($entries), 0, 50),
            'running' => array_values($running),
            'rules'   => $rules,
            'summary' => $summary,
        ];
    }

    /**
     * Starts a timer for an employee.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function clockIn(array $payload): array
    {
        $employeeId = sanitize_key($payload['employee_id'] ?? '');
        if ($employeeId === '') {
            throw new \InvalidArgumentException('employee_id is required');
        }

        $running = self::getRunning();
        if (isset($running[$employeeId])) {
            return $running[$employeeId];
        }

        $entry = [
            'employee_id'   => $employeeId,
            'employee_name' => sanitize_text_field($payload['employee_name'] ?? ''),
            'role'          => sanitize_key($payload['role'] ?? 'trainer'),
            'started_at'    => wp_date('c'),
            'notes'         => sanitize_text_field($payload['notes'] ?? ''),
        ];

        $running[$employeeId] = $entry;
        update_option(self::RUNNING_OPTION, $running);

        return $entry;
    }

    /**
     * Stops a running timer.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function clockOut(array $payload): array
    {
        $employeeId = sanitize_key($payload['employee_id'] ?? '');
        if ($employeeId === '') {
            throw new \InvalidArgumentException('employee_id is required');
        }

        $running = self::getRunning();
        if (!isset($running[$employeeId])) {
            throw new \RuntimeException('No running entry for employee');
        }

        $entry = $running[$employeeId];
        unset($running[$employeeId]);
        update_option(self::RUNNING_OPTION, $running);

        $endTime = wp_date('c');
        $data = [
            'employee_id'   => $entry['employee_id'],
            'employee_name' => $entry['employee_name'],
            'role'          => $entry['role'],
            'clock_in'      => $entry['started_at'],
            'clock_out'     => $endTime,
            'source'        => 'timer',
            'notes'         => $entry['notes'] ?? '',
        ];

        return self::storeEntry($data);
    }

    /**
     * Creates a manual entry (start/end provided).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function createEntry(array $payload): array
    {
        $employeeId = sanitize_key($payload['employee_id'] ?? '');
        if ($employeeId === '') {
            throw new \InvalidArgumentException('employee_id is required');
        }

        $clockIn  = self::normalizeDateTime($payload['clock_in'] ?? null, $payload['date'] ?? null, $payload['start_time'] ?? null);
        $clockOut = self::normalizeDateTime($payload['clock_out'] ?? null, $payload['date'] ?? null, $payload['end_time'] ?? null);

        $data = [
            'employee_id'   => $employeeId,
            'employee_name' => sanitize_text_field($payload['employee_name'] ?? ''),
            'role'          => sanitize_key($payload['role'] ?? 'trainer'),
            'clock_in'      => $clockIn,
            'clock_out'     => $clockOut,
            'notes'         => sanitize_text_field($payload['notes'] ?? ''),
            'source'        => 'manual',
        ];

        return self::storeEntry($data);
    }

    /**
     * Updates rounding and overtime rules.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function updateRules(array $payload): array
    {
        $rules = self::getRules();

        if (array_key_exists('rounding', $payload)) {
            $rules['rounding'] = max(1, (int) $payload['rounding']);
        }

        if (array_key_exists('overtime_threshold', $payload)) {
            $rules['overtime_threshold'] = max(1, (int) $payload['overtime_threshold']);
        }

        if (array_key_exists('allow_manual', $payload)) {
            $rules['allow_manual'] = (bool) $payload['allow_manual'];
        }

        if (array_key_exists('break_minutes', $payload)) {
            $rules['break_minutes'] = max(0, (int) $payload['break_minutes']);
        }

        update_option(self::RULES_OPTION, $rules);

        return $rules;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getEntries(): array
    {
        return get_option(self::ENTRIES_OPTION, []);
    }

    /**
     * @return array<string, mixed>
     */
    private static function getRunning(): array
    {
        return get_option(self::RUNNING_OPTION, []);
    }

    /**
     * @return array<string, mixed>
     */
    private static function getRules(): array
    {
        return wp_parse_args(
            get_option(self::RULES_OPTION, []),
            [
                'rounding'           => 5,
                'overtime_threshold' => 8,
                'allow_manual'       => true,
                'break_minutes'      => 0,
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function storeEntry(array $data): array
    {
        $clockIn  = new DateTimeImmutable($data['clock_in']);
        $clockOut = new DateTimeImmutable($data['clock_out']);
        if ($clockOut <= $clockIn) {
            throw new \InvalidArgumentException('clock_out must be after clock_in');
        }

        $rules    = self::getRules();
        $duration = $clockOut->getTimestamp() - $clockIn->getTimestamp();
        $minutes  = max(1, (int) round($duration / 60));
        $minutes  = self::applyRounding($minutes, (int) $rules['rounding']);

        $entry = [
            'id'             => uniqid('tt_', true),
            'employee_id'    => $data['employee_id'],
            'employee_name'  => $data['employee_name'],
            'role'           => $data['role'],
            'clock_in'       => $clockIn->format('c'),
            'clock_out'      => $clockOut->format('c'),
            'minutes'        => $minutes,
            'hours'          => round($minutes / 60, 2),
            'source'         => $data['source'],
            'notes'          => $data['notes'] ?? '',
        ];

        $entries   = self::getEntries();
        $entries[] = $entry;
        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, -self::MAX_ENTRIES);
        }
        update_option(self::ENTRIES_OPTION, array_values($entries));

        return $entry;
    }

    private static function applyRounding(int $minutes, int $step): int
    {
        $step = max(1, $step);
        return (int) (ceil($minutes / $step) * $step);
    }

    private static function normalizeDateTime(?string $iso, ?string $date, ?string $time): string
    {
        $tz = wp_timezone();
        if (!empty($iso)) {
            try {
                return (new DateTimeImmutable($iso, $tz))->format('c');
            } catch (Exception $exception) {
                // fallback to manual build
            }
        }

        $datePart = $date ?: wp_date('Y-m-d');
        $timePart = $time ?: '08:00';

        return (new DateTimeImmutable($datePart . ' ' . $timePart, $tz))->format('c');
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array<string, mixed> $running
     * @param array<string, mixed> $rules
     * @return array<string, mixed>
     */
    private static function buildSummary(array $entries, array $running, array $rules): array
    {
        $tz = wp_timezone();
        $weekStart = new DateTimeImmutable('monday this week', $tz);
        $weekEnd   = $weekStart->modify('+7 days');
        $hoursWeek = 0.0;
        $overtime  = 0.0;
        $roleHours = [];

        foreach ($entries as $entry) {
            try {
                $clockIn = new DateTimeImmutable($entry['clock_in'], $tz);
            } catch (Exception $exception) {
                continue;
            }

            if ($clockIn >= $weekStart && $clockIn < $weekEnd) {
                $hoursWeek += (float) ($entry['hours'] ?? 0);
                $dailyHours = (float) ($entry['hours'] ?? 0);
                $threshold  = (float) ($rules['overtime_threshold'] ?? 8);
                if ($dailyHours > $threshold) {
                    $overtime += $dailyHours - $threshold;
                }
            }

            $role = $entry['role'] ?? 'trainer';
            if (!isset($roleHours[$role])) {
                $roleHours[$role] = 0.0;
            }
            $roleHours[$role] += (float) ($entry['hours'] ?? 0);
        }

        return [
            'hours_week'      => round($hoursWeek, 2),
            'overtime_hours'  => round($overtime, 2),
            'active_timers'   => count($running),
            'role_distribution' => $roleHours,
        ];
    }
}
