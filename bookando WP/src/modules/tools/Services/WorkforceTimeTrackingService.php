<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Services;

use Bookando\Modules\employees\Model as EmployeesModel;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use function wp_date;
use function wp_timezone;

/**
 * Enhanced Time Tracking Service integrated with Employee module.
 *
 * @deprecated Use Bookando\Modules\workday\Services\WorkforceTimeTrackingService instead
 * @todo Remove in next major version - functionality moved to workday module
 *
 * Features:
 * - Clock-in/out timers with employee data
 * - Automatic break calculation based on work duration
 * - Integration with employee workday_sets
 * - Export capabilities for payroll
 * - Multi-tenant support
 */
class WorkforceTimeTrackingService
{
    /**
     * Get all active employees for time tracking selection.
     *
     * @param string $status Filter by status (active, blocked, all)
     * @return array<int, array<string, mixed>>
     */
    public static function getActiveEmployees(string $status = 'active'): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_users';

        $where = $status !== 'all' ? $wpdb->prepare('WHERE status = %s', $status) : '';
        $where .= ($where ? ' AND ' : 'WHERE ') . "JSON_CONTAINS(roles, '\"bookando_employee\"')";

        $results = $wpdb->get_results(
            "SELECT id, first_name, last_name, email, avatar_url, status, tenant_id
             FROM {$table}
             {$where}
             ORDER BY first_name, last_name",
            ARRAY_A
        );

        return array_map(static function ($row) {
            return [
                'id' => (int) $row['id'],
                'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'email' => $row['email'] ?? '',
                'avatar_url' => $row['avatar_url'] ?? null,
                'status' => $row['status'] ?? 'active',
                'tenant_id' => $row['tenant_id'] ? (int) $row['tenant_id'] : null,
            ];
        }, $results ?: []);
    }

    /**
     * Get current state including active timers and recent entries.
     *
     * @param int|null $userId Filter for specific user
     * @param int $limit Number of recent entries to return
     * @return array<string, mixed>
     */
    public static function getState(?int $userId = null, int $limit = 50): array
    {
        $employees = self::getActiveEmployees();
        $activeTimers = self::getActiveTimers($userId);
        $recentEntries = self::getRecentEntries($userId, $limit);
        $summary = self::buildSummary($userId);

        return [
            'employees' => $employees,
            'active_timers' => $activeTimers,
            'recent_entries' => $recentEntries,
            'summary' => $summary,
        ];
    }

    /**
     * Start a timer for an employee.
     *
     * @param int $userId Employee user ID
     * @param array<string, mixed> $data Optional data (location_id, service_id, notes)
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function clockIn(int $userId, array $data = []): array
    {
        global $wpdb;

        // Check if timer already running
        $existing = self::getActiveTimer($userId);
        if ($existing) {
            throw new \RuntimeException('Timer already running for this employee');
        }

        // Get employee data for tenant_id
        $employee = self::getEmployee($userId);
        if (!$employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $tz = wp_timezone();
        $now = new DateTimeImmutable('now', $tz);

        $insertData = [
            'tenant_id' => $employee['tenant_id'],
            'user_id' => $userId,
            'started_at' => $now->format('Y-m-d H:i:s'),
            'location_id' => $data['location_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        $wpdb->insert(
            $wpdb->prefix . 'bookando_active_timers',
            $insertData,
            ['%d', '%d', '%s', '%d', '%d', '%s']
        );

        return [
            'id' => $wpdb->insert_id,
            'user_id' => $userId,
            'employee_name' => $employee['name'],
            'started_at' => $now->format('c'),
            'location_id' => $insertData['location_id'],
            'service_id' => $insertData['service_id'],
            'notes' => $insertData['notes'],
        ];
    }

    /**
     * Stop a timer and create time entry.
     *
     * @param int $userId Employee user ID
     * @param array<string, mixed> $data Optional data (break_minutes, notes)
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function clockOut(int $userId, array $data = []): array
    {
        global $wpdb;

        $timer = self::getActiveTimer($userId);
        if (!$timer) {
            throw new \RuntimeException('No active timer found for this employee');
        }

        $tz = wp_timezone();
        $now = new DateTimeImmutable('now', $tz);
        $startedAt = new DateTimeImmutable($timer['started_at'], $tz);

        $totalMinutes = (int) (($now->getTimestamp() - $startedAt->getTimestamp()) / 60);
        $breakMinutes = isset($data['break_minutes']) ? (int) $data['break_minutes'] : self::calculateAutoBreak($totalMinutes);
        $workMinutes = $totalMinutes - $breakMinutes;

        $employee = self::getEmployee($userId);

        $entryData = [
            'tenant_id' => $timer['tenant_id'],
            'user_id' => $userId,
            'clock_in_at' => $startedAt->format('Y-m-d H:i:s'),
            'clock_out_at' => $now->format('Y-m-d H:i:s'),
            'break_minutes' => $breakMinutes,
            'total_minutes' => $workMinutes,
            'total_hours' => round($workMinutes / 60, 2),
            'source' => 'timer',
            'status' => 'completed',
            'notes' => $data['notes'] ?? $timer['notes'],
            'location_id' => $timer['location_id'],
            'service_id' => $timer['service_id'],
            'created_by' => $userId,
        ];

        $wpdb->insert(
            $wpdb->prefix . 'bookando_time_entries',
            $entryData,
            ['%d', '%d', '%s', '%s', '%d', '%d', '%f', '%s', '%s', '%s', '%d', '%d', '%d']
        );

        $entryId = $wpdb->insert_id;

        // Delete timer
        $wpdb->delete(
            $wpdb->prefix . 'bookando_active_timers',
            ['id' => $timer['id']],
            ['%d']
        );

        return [
            'id' => $entryId,
            'user_id' => $userId,
            'employee_name' => $employee['name'] ?? '',
            'clock_in_at' => $startedAt->format('c'),
            'clock_out_at' => $now->format('c'),
            'break_minutes' => $breakMinutes,
            'total_minutes' => $workMinutes,
            'total_hours' => $entryData['total_hours'],
            'source' => 'timer',
        ];
    }

    /**
     * Create a manual time entry.
     *
     * @param int $userId Employee user ID
     * @param array<string, mixed> $data Entry data
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function createManualEntry(int $userId, array $data): array
    {
        global $wpdb;

        $employee = self::getEmployee($userId);
        if (!$employee) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $tz = wp_timezone();
        $clockInAt = new DateTimeImmutable($data['clock_in_at'] ?? 'now', $tz);
        $clockOutAt = new DateTimeImmutable($data['clock_out_at'] ?? 'now', $tz);

        if ($clockOutAt <= $clockInAt) {
            throw new \InvalidArgumentException('Clock out time must be after clock in time');
        }

        $totalMinutes = (int) (($clockOutAt->getTimestamp() - $clockInAt->getTimestamp()) / 60);
        $breakMinutes = isset($data['break_minutes']) ? (int) $data['break_minutes'] : self::calculateAutoBreak($totalMinutes);
        $workMinutes = $totalMinutes - $breakMinutes;

        $entryData = [
            'tenant_id' => $employee['tenant_id'],
            'user_id' => $userId,
            'clock_in_at' => $clockInAt->format('Y-m-d H:i:s'),
            'clock_out_at' => $clockOutAt->format('Y-m-d H:i:s'),
            'break_minutes' => $breakMinutes,
            'total_minutes' => $workMinutes,
            'total_hours' => round($workMinutes / 60, 2),
            'source' => 'manual',
            'status' => 'completed',
            'notes' => $data['notes'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'created_by' => $data['created_by'] ?? $userId,
        ];

        $wpdb->insert(
            $wpdb->prefix . 'bookando_time_entries',
            $entryData,
            ['%d', '%d', '%s', '%s', '%d', '%d', '%f', '%s', '%s', '%s', '%d', '%d', '%d']
        );

        return [
            'id' => $wpdb->insert_id,
            'user_id' => $userId,
            'employee_name' => $employee['name'],
            'clock_in_at' => $clockInAt->format('c'),
            'clock_out_at' => $clockOutAt->format('c'),
            'break_minutes' => $breakMinutes,
            'total_minutes' => $workMinutes,
            'total_hours' => $entryData['total_hours'],
            'source' => 'manual',
        ];
    }

    /**
     * Calculate automatic break time based on German labor law.
     *
     * @param int $totalMinutes Total work duration in minutes
     * @return int Break minutes
     */
    private static function calculateAutoBreak(int $totalMinutes): int
    {
        // German labor law: 30 min break after 6 hours, 45 min after 9 hours
        if ($totalMinutes > 540) { // > 9 hours
            return 45;
        } elseif ($totalMinutes > 360) { // > 6 hours
            return 30;
        }
        return 0;
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
            'tenant_id' => $result['tenant_id'] ? (int) $result['tenant_id'] : null,
            'user_id' => (int) $result['user_id'],
            'started_at' => $result['started_at'],
            'location_id' => $result['location_id'] ? (int) $result['location_id'] : null,
            'service_id' => $result['service_id'] ? (int) $result['service_id'] : null,
            'notes' => $result['notes'],
        ];
    }

    /**
     * Get all active timers.
     *
     * @param int|null $userId Filter by user
     * @return array<int, array<string, mixed>>
     */
    private static function getActiveTimers(?int $userId = null): array
    {
        global $wpdb;

        $where = $userId ? $wpdb->prepare('WHERE user_id = %d', $userId) : '';

        $results = $wpdb->get_results(
            "SELECT t.*, u.first_name, u.last_name
             FROM {$wpdb->prefix}bookando_active_timers t
             LEFT JOIN {$wpdb->prefix}bookando_users u ON t.user_id = u.id
             {$where}
             ORDER BY t.started_at DESC",
            ARRAY_A
        );

        return array_map(static function ($row) {
            return [
                'id' => (int) $row['id'],
                'user_id' => (int) $row['user_id'],
                'employee_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'started_at' => $row['started_at'],
                'duration_minutes' => self::calculateDuration($row['started_at']),
                'location_id' => $row['location_id'] ? (int) $row['location_id'] : null,
                'service_id' => $row['service_id'] ? (int) $row['service_id'] : null,
                'notes' => $row['notes'],
            ];
        }, $results ?: []);
    }

    /**
     * Get recent time entries.
     *
     * @param int|null $userId Filter by user
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private static function getRecentEntries(?int $userId = null, int $limit = 50): array
    {
        global $wpdb;

        $where = $userId ? $wpdb->prepare('WHERE t.user_id = %d', $userId) : '';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, u.first_name, u.last_name
                 FROM {$wpdb->prefix}bookando_time_entries t
                 LEFT JOIN {$wpdb->prefix}bookando_users u ON t.user_id = u.id
                 {$where}
                 ORDER BY t.clock_in_at DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return array_map(static function ($row) {
            return [
                'id' => (int) $row['id'],
                'user_id' => (int) $row['user_id'],
                'employee_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'clock_in_at' => $row['clock_in_at'],
                'clock_out_at' => $row['clock_out_at'],
                'break_minutes' => (int) $row['break_minutes'],
                'total_minutes' => (int) $row['total_minutes'],
                'total_hours' => (float) $row['total_hours'],
                'source' => $row['source'],
                'status' => $row['status'],
                'notes' => $row['notes'],
                'location_id' => $row['location_id'] ? (int) $row['location_id'] : null,
                'service_id' => $row['service_id'] ? (int) $row['service_id'] : null,
            ];
        }, $results ?: []);
    }

    /**
     * Build summary statistics.
     *
     * @param int|null $userId Filter by user
     * @return array<string, mixed>
     */
    private static function buildSummary(?int $userId = null): array
    {
        global $wpdb;

        $tz = wp_timezone();
        $weekStart = new DateTimeImmutable('monday this week', $tz);
        $monthStart = new DateTimeImmutable('first day of this month', $tz);

        $userWhere = $userId ? $wpdb->prepare('AND user_id = %d', $userId) : '';

        // Week stats
        $weekStats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as entries,
                    SUM(total_hours) as hours,
                    SUM(break_minutes) as breaks
                 FROM {$wpdb->prefix}bookando_time_entries
                 WHERE clock_in_at >= %s
                 AND status = 'completed'
                 {$userWhere}",
                $weekStart->format('Y-m-d H:i:s')
            ),
            ARRAY_A
        );

        // Month stats
        $monthStats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as entries,
                    SUM(total_hours) as hours
                 FROM {$wpdb->prefix}bookando_time_entries
                 WHERE clock_in_at >= %s
                 AND status = 'completed'
                 {$userWhere}",
                $monthStart->format('Y-m-d H:i:s')
            ),
            ARRAY_A
        );

        return [
            'this_week' => [
                'entries' => (int) ($weekStats['entries'] ?? 0),
                'total_hours' => round((float) ($weekStats['hours'] ?? 0), 2),
                'break_minutes' => (int) ($weekStats['breaks'] ?? 0),
            ],
            'this_month' => [
                'entries' => (int) ($monthStats['entries'] ?? 0),
                'total_hours' => round((float) ($monthStats['hours'] ?? 0), 2),
            ],
        ];
    }

    /**
     * Get employee data.
     *
     * @param int $userId
     * @return array<string, mixed>|null
     */
    private static function getEmployee(int $userId): ?array
    {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, tenant_id, first_name, last_name, email, status
                 FROM {$wpdb->prefix}bookando_users
                 WHERE id = %d",
                $userId
            ),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return [
            'id' => (int) $result['id'],
            'tenant_id' => $result['tenant_id'] ? (int) $result['tenant_id'] : null,
            'name' => trim(($result['first_name'] ?? '') . ' ' . ($result['last_name'] ?? '')),
            'email' => $result['email'] ?? '',
            'status' => $result['status'] ?? 'active',
        ];
    }

    /**
     * Calculate duration in minutes from start time to now.
     *
     * @param string $startedAt
     * @return int
     */
    private static function calculateDuration(string $startedAt): int
    {
        try {
            $tz = wp_timezone();
            $start = new DateTimeImmutable($startedAt, $tz);
            $now = new DateTimeImmutable('now', $tz);
            return (int) (($now->getTimestamp() - $start->getTimestamp()) / 60);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get calendar data combining workdays, vacations, and bookings.
     *
     * @param int|null $userId Filter for specific user
     * @param string|null $month Month in format 'YYYY-MM'
     * @param int|null $year Year
     * @return array<string, mixed>
     */
    public static function getCalendarData(?int $userId = null, ?string $month = null, ?int $year = null): array
    {
        global $wpdb;

        $tz = wp_timezone();
        $now = new DateTimeImmutable('now', $tz);

        if (!$year) {
            $year = (int) $now->format('Y');
        }

        if (!$month) {
            $month = $now->format('Y-m');
        }

        // Parse month
        $monthStart = new DateTimeImmutable($month . '-01', $tz);
        $monthEnd = $monthStart->modify('last day of this month');

        $data = [
            'user_id' => $userId,
            'month' => $month,
            'year' => $year,
            'workdays' => [],
            'special_days' => [],
            'vacations' => [],
            'bookings' => [],
            'time_entries' => [],
        ];

        if (!$userId) {
            return $data;
        }

        // Get employee's workday set
        $employee = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT workday_set_id FROM {$wpdb->prefix}bookando_users WHERE id = %d",
                $userId
            ),
            ARRAY_A
        );

        if ($employee && !empty($employee['workday_set_id'])) {
            $workdays = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bookando_employees_workdays
                     WHERE set_id = %d
                     ORDER BY day_of_week",
                    $employee['workday_set_id']
                ),
                ARRAY_A
            );
            $data['workdays'] = $workdays ?: [];
        }

        // Get special days in month
        $specialDays = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_employees_special_days
                 WHERE user_id = %d
                 AND date BETWEEN %s AND %s
                 ORDER BY date",
                $userId,
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d')
            ),
            ARRAY_A
        );
        $data['special_days'] = $specialDays ?: [];

        // Get vacation days in month
        $vacations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_employees_days_off
                 WHERE user_id = %d
                 AND request_status = 'approved'
                 AND (
                     (start_date BETWEEN %s AND %s)
                     OR (end_date BETWEEN %s AND %s)
                     OR (start_date <= %s AND end_date >= %s)
                 )
                 ORDER BY start_date",
                $userId,
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d')
            ),
            ARRAY_A
        );
        $data['vacations'] = $vacations ?: [];

        // Get bookings in month (where employee is assigned)
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT b.*, s.name as service_name, l.name as location_name
                 FROM {$wpdb->prefix}bookando_bookings b
                 LEFT JOIN {$wpdb->prefix}bookando_services s ON b.service_id = s.id
                 LEFT JOIN {$wpdb->prefix}bookando_locations l ON b.location_id = l.id
                 WHERE b.employee_id = %d
                 AND b.start_datetime BETWEEN %s AND %s
                 AND b.status NOT IN ('cancelled', 'rejected')
                 ORDER BY b.start_datetime",
                $userId,
                $monthStart->format('Y-m-d 00:00:00'),
                $monthEnd->format('Y-m-d 23:59:59')
            ),
            ARRAY_A
        );
        $data['bookings'] = $bookings ?: [];

        // Get time entries in month
        $timeEntries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_time_entries
                 WHERE user_id = %d
                 AND clock_in_at BETWEEN %s AND %s
                 AND status = 'completed'
                 ORDER BY clock_in_at",
                $userId,
                $monthStart->format('Y-m-d 00:00:00'),
                $monthEnd->format('Y-m-d 23:59:59')
            ),
            ARRAY_A
        );
        $data['time_entries'] = $timeEntries ?: [];

        return $data;
    }
}
