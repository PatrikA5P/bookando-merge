<?php

declare(strict_types=1);

namespace Bookando\Modules\Workday\Services;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Break Tracking Service
 *
 * Manages break tracking for time entries and active timers.
 *
 * @package Bookando\Modules\Workday\Services
 */
class BreakService
{
    /**
     * Start a break for an active timer.
     *
     * @param int $userId Employee user ID
     * @param array<string, mixed> $data Break data (type, notes)
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function startBreak(int $userId, array $data = []): array
    {
        global $wpdb;

        // Check if there's an active timer
        $timer = self::getActiveTimer($userId);
        if (!$timer) {
            throw new \RuntimeException('No active timer found for this employee');
        }

        // Check if there's already an active break
        $activeBreak = self::getActiveBreakForTimer($timer['id']);
        if ($activeBreak) {
            throw new \RuntimeException('A break is already in progress');
        }

        $tz = wp_timezone();
        $now = new DateTimeImmutable('now', $tz);

        $breakData = [
            'time_entry_id' => null, // Will be set when timer is closed
            'break_start_at' => $now->format('Y-m-d H:i:s'),
            'break_end_at' => null,
            'break_minutes' => null,
            'break_type' => $data['type'] ?? 'unpaid',
            'is_automatic' => 0,
            'notes' => $data['notes'] ?? null,
        ];

        $wpdb->insert(
            $wpdb->prefix . 'bookando_time_entry_breaks',
            $breakData,
            ['%d', '%s', '%s', '%d', '%s', '%d', '%s']
        );

        $breakId = $wpdb->insert_id;

        // Store the break ID in timer meta (we'll use notes field as JSON)
        $timerMeta = json_decode($timer['notes'] ?? '{}', true) ?: [];
        $timerMeta['active_break_id'] = $breakId;

        $wpdb->update(
            $wpdb->prefix . 'bookando_active_timers',
            ['notes' => json_encode($timerMeta)],
            ['id' => $timer['id']],
            ['%s'],
            ['%d']
        );

        return [
            'id' => $breakId,
            'user_id' => $userId,
            'break_start_at' => $now->format('c'),
            'break_type' => $breakData['break_type'],
            'notes' => $breakData['notes'],
        ];
    }

    /**
     * End a break for an active timer.
     *
     * @param int $userId Employee user ID
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function endBreak(int $userId): array
    {
        global $wpdb;

        // Check if there's an active timer
        $timer = self::getActiveTimer($userId);
        if (!$timer) {
            throw new \RuntimeException('No active timer found for this employee');
        }

        // Get active break
        $activeBreak = self::getActiveBreakForTimer($timer['id']);
        if (!$activeBreak) {
            throw new \RuntimeException('No active break found');
        }

        $tz = wp_timezone();
        $now = new DateTimeImmutable('now', $tz);
        $startedAt = new DateTimeImmutable($activeBreak['break_start_at'], $tz);

        $breakMinutes = (int) (($now->getTimestamp() - $startedAt->getTimestamp()) / 60);

        // Update break
        $wpdb->update(
            $wpdb->prefix . 'bookando_time_entry_breaks',
            [
                'break_end_at' => $now->format('Y-m-d H:i:s'),
                'break_minutes' => $breakMinutes,
            ],
            ['id' => $activeBreak['id']],
            ['%s', '%d'],
            ['%d']
        );

        // Remove active break from timer meta
        $timerMeta = json_decode($timer['notes'] ?? '{}', true) ?: [];
        unset($timerMeta['active_break_id']);

        $wpdb->update(
            $wpdb->prefix . 'bookando_active_timers',
            ['notes' => json_encode($timerMeta)],
            ['id' => $timer['id']],
            ['%s'],
            ['%d']
        );

        return [
            'id' => $activeBreak['id'],
            'user_id' => $userId,
            'break_start_at' => $startedAt->format('c'),
            'break_end_at' => $now->format('c'),
            'break_minutes' => $breakMinutes,
            'break_type' => $activeBreak['break_type'],
        ];
    }

    /**
     * Get breaks for a time entry.
     *
     * @param int $timeEntryId
     * @return array<int, array<string, mixed>>
     */
    public static function getBreaksForTimeEntry(int $timeEntryId): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_time_entry_breaks
                 WHERE time_entry_id = %d
                 ORDER BY break_start_at ASC",
                $timeEntryId
            ),
            ARRAY_A
        );

        return array_map(static function ($row) {
            return [
                'id' => (int) $row['id'],
                'time_entry_id' => (int) $row['time_entry_id'],
                'break_start_at' => $row['break_start_at'],
                'break_end_at' => $row['break_end_at'],
                'break_minutes' => $row['break_minutes'] ? (int) $row['break_minutes'] : null,
                'break_type' => $row['break_type'],
                'is_automatic' => (bool) $row['is_automatic'],
                'notes' => $row['notes'],
            ];
        }, $results ?: []);
    }

    /**
     * Add automatic breaks to a time entry based on work duration.
     *
     * @param int $timeEntryId
     * @param int $totalWorkMinutes
     * @return array<int, array<string, mixed>>
     */
    public static function addAutomaticBreaks(int $timeEntryId, int $totalWorkMinutes): array
    {
        global $wpdb;

        $breaks = [];

        // German labor law: 30 min break after 6 hours, 45 min after 9 hours
        if ($totalWorkMinutes > 540) { // > 9 hours
            $breakMinutes = 45;
        } elseif ($totalWorkMinutes > 360) { // > 6 hours
            $breakMinutes = 30;
        } else {
            return $breaks; // No automatic break required
        }

        // Get time entry to calculate break time
        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_time_entries WHERE id = %d",
                $timeEntryId
            ),
            ARRAY_A
        );

        if (!$entry) {
            return $breaks;
        }

        $tz = wp_timezone();
        $clockIn = new DateTimeImmutable($entry['clock_in_at'], $tz);
        $clockOut = new DateTimeImmutable($entry['clock_out_at'], $tz);

        // Calculate break start time (approximately in the middle)
        $breakStartTimestamp = $clockIn->getTimestamp() + (($clockOut->getTimestamp() - $clockIn->getTimestamp()) / 2);
        $breakStart = DateTimeImmutable::createFromFormat('U', (string) $breakStartTimestamp);
        $breakStart = $breakStart->setTimezone($tz);
        $breakEnd = $breakStart->modify("+{$breakMinutes} minutes");

        $breakData = [
            'time_entry_id' => $timeEntryId,
            'break_start_at' => $breakStart->format('Y-m-d H:i:s'),
            'break_end_at' => $breakEnd->format('Y-m-d H:i:s'),
            'break_minutes' => $breakMinutes,
            'break_type' => 'automatic',
            'is_automatic' => 1,
            'notes' => 'Automatically added per labor law requirements',
        ];

        $wpdb->insert(
            $wpdb->prefix . 'bookando_time_entry_breaks',
            $breakData,
            ['%d', '%s', '%s', '%d', '%s', '%d', '%s']
        );

        $breaks[] = [
            'id' => $wpdb->insert_id,
            'time_entry_id' => $timeEntryId,
            'break_start_at' => $breakStart->format('c'),
            'break_end_at' => $breakEnd->format('c'),
            'break_minutes' => $breakMinutes,
            'break_type' => 'automatic',
            'is_automatic' => true,
            'notes' => $breakData['notes'],
        ];

        return $breaks;
    }

    /**
     * Calculate total break minutes for a time entry.
     *
     * @param int $timeEntryId
     * @return int
     */
    public static function calculateTotalBreakMinutes(int $timeEntryId): int
    {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(break_minutes), 0) FROM {$wpdb->prefix}bookando_time_entry_breaks
                 WHERE time_entry_id = %d AND break_end_at IS NOT NULL",
                $timeEntryId
            )
        );

        return (int) $result;
    }

    /**
     * Get active timer for a user.
     *
     * @param int $userId
     * @return array<string, mixed>|null
     */
    private static function getActiveTimer(int $userId): ?array
    {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_active_timers WHERE user_id = %d",
                $userId
            ),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return [
            'id' => (int) $result['id'],
            'user_id' => (int) $result['user_id'],
            'started_at' => $result['started_at'],
            'notes' => $result['notes'],
            'tenant_id' => $result['tenant_id'] ? (int) $result['tenant_id'] : null,
        ];
    }

    /**
     * Get active break for a timer.
     *
     * @param int $timerId
     * @return array<string, mixed>|null
     */
    private static function getActiveBreakForTimer(int $timerId): ?array
    {
        global $wpdb;

        // Get timer to check meta
        $timer = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_active_timers WHERE id = %d",
                $timerId
            ),
            ARRAY_A
        );

        if (!$timer) {
            return null;
        }

        $timerMeta = json_decode($timer['notes'] ?? '{}', true) ?: [];
        $breakId = $timerMeta['active_break_id'] ?? null;

        if (!$breakId) {
            return null;
        }

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_time_entry_breaks
                 WHERE id = %d AND break_end_at IS NULL",
                $breakId
            ),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return [
            'id' => (int) $result['id'],
            'break_start_at' => $result['break_start_at'],
            'break_type' => $result['break_type'],
            'notes' => $result['notes'],
        ];
    }
}
