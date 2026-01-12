<?php

declare(strict_types=1);

namespace Bookando\Modules\workday\Services;

use Exception;

/**
 * Vacation Balance Service
 *
 * Manages vacation entitlements, balances, and calculations.
 *
 * @package Bookando\Modules\workday\Services
 */
class VacationBalanceService
{
    /**
     * Get vacation balance for an employee for a specific year.
     *
     * @param int $userId
     * @param int|null $year Defaults to current year
     * @return array<string, mixed>
     */
    public static function getBalance(int $userId, ?int $year = null): array
    {
        global $wpdb;

        if ($year === null) {
            $year = (int) date('Y');
        }

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookando_employee_vacation_balances
                 WHERE user_id = %d AND year = %d",
                $userId,
                $year
            ),
            ARRAY_A
        );

        if ($result) {
            return self::formatBalance($result);
        }

        // Create default balance if it doesn't exist
        return self::createBalance($userId, $year);
    }

    /**
     * Create a vacation balance record for an employee.
     *
     * @param int $userId
     * @param int $year
     * @param float $entitledDays
     * @return array<string, mixed>
     */
    public static function createBalance(int $userId, int $year, float $entitledDays = 25.0): array
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'bookando_employee_vacation_balances',
            [
                'user_id' => $userId,
                'year' => $year,
                'entitled_days' => $entitledDays,
                'carried_over_days' => 0.0,
                'taken_days' => 0.0,
                'planned_days' => 0.0,
            ],
            ['%d', '%d', '%f', '%f', '%f', '%f']
        );

        return self::getBalance($userId, $year);
    }

    /**
     * Update vacation balance.
     *
     * @param int $userId
     * @param int $year
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function updateBalance(int $userId, int $year, array $data): array
    {
        global $wpdb;

        $updateData = [];
        $updateFormats = [];

        $allowedFields = [
            'entitled_days' => '%f',
            'carried_over_days' => '%f',
            'notes' => '%s',
        ];

        foreach ($allowedFields as $field => $format) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
                $updateFormats[] = $format;
            }
        }

        if (!empty($updateData)) {
            $wpdb->update(
                $wpdb->prefix . 'bookando_employee_vacation_balances',
                $updateData,
                ['user_id' => $userId, 'year' => $year],
                $updateFormats,
                ['%d', '%d']
            );
        }

        return self::getBalance($userId, $year);
    }

    /**
     * Recalculate taken and planned days based on approved absences.
     *
     * @param int $userId
     * @param int $year
     * @return array<string, mixed>
     */
    public static function recalculateBalance(int $userId, int $year): array
    {
        global $wpdb;

        // Calculate taken days (approved absences that affect vacation balance)
        $takenDays = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(
                    CASE
                        WHEN hours_per_day IS NOT NULL THEN hours_per_day / 8.0
                        ELSE DATEDIFF(end_date, start_date) + 1
                    END
                ), 0)
                 FROM {$wpdb->prefix}bookando_employees_days_off
                 WHERE user_id = %d
                 AND YEAR(start_date) = %d
                 AND request_status = 'approved'
                 AND affects_vacation_balance = 1",
                $userId,
                $year
            )
        );

        // Calculate planned days (pending absences)
        $plannedDays = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(
                    CASE
                        WHEN hours_per_day IS NOT NULL THEN hours_per_day / 8.0
                        ELSE DATEDIFF(end_date, start_date) + 1
                    END
                ), 0)
                 FROM {$wpdb->prefix}bookando_employees_days_off
                 WHERE user_id = %d
                 AND YEAR(start_date) = %d
                 AND request_status IN ('pending', 'requested')
                 AND affects_vacation_balance = 1",
                $userId,
                $year
            )
        );

        // Update balance
        $wpdb->update(
            $wpdb->prefix . 'bookando_employee_vacation_balances',
            [
                'taken_days' => (float) $takenDays,
                'planned_days' => (float) $plannedDays,
            ],
            ['user_id' => $userId, 'year' => $year],
            ['%f', '%f'],
            ['%d', '%d']
        );

        return self::getBalance($userId, $year);
    }

    /**
     * Check if employee has enough vacation days for a request.
     *
     * @param int $userId
     * @param int $year
     * @param float $requestedDays
     * @return bool
     */
    public static function hasEnoughDays(int $userId, int $year, float $requestedDays): bool
    {
        $balance = self::getBalance($userId, $year);

        return $balance['remaining_days'] >= $requestedDays;
    }

    /**
     * Get vacation overview for multiple years.
     *
     * @param int $userId
     * @param int $startYear
     * @param int $endYear
     * @return array<int, array<string, mixed>>
     */
    public static function getMultiYearOverview(int $userId, int $startYear, int $endYear): array
    {
        $overview = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $overview[] = self::getBalance($userId, $year);
        }

        return $overview;
    }

    /**
     * Carry over unused vacation days to next year.
     *
     * @param int $userId
     * @param int $fromYear
     * @param float|null $maxCarryOver Max days that can be carried over (null = unlimited)
     * @return array<string, mixed>
     */
    public static function carryOverToNextYear(int $userId, int $fromYear, ?float $maxCarryOver = 5.0): array
    {
        $currentBalance = self::getBalance($userId, $fromYear);
        $remainingDays = $currentBalance['remaining_days'];

        if ($remainingDays <= 0) {
            return [
                'carried_over' => 0,
                'message' => 'No vacation days to carry over',
            ];
        }

        $daysToCarryOver = $remainingDays;
        if ($maxCarryOver !== null && $daysToCarryOver > $maxCarryOver) {
            $daysToCarryOver = $maxCarryOver;
        }

        $nextYear = $fromYear + 1;
        $nextYearBalance = self::getBalance($userId, $nextYear);

        self::updateBalance($userId, $nextYear, [
            'carried_over_days' => $nextYearBalance['carried_over_days'] + $daysToCarryOver,
        ]);

        return [
            'carried_over' => $daysToCarryOver,
            'from_year' => $fromYear,
            'to_year' => $nextYear,
            'remaining_in_current_year' => $remainingDays,
            'lost_days' => $remainingDays - $daysToCarryOver,
        ];
    }

    /**
     * Get vacation statistics for an employee.
     *
     * @param int $userId
     * @param int $year
     * @return array<string, mixed>
     */
    public static function getStatistics(int $userId, int $year): array
    {
        global $wpdb;

        $balance = self::getBalance($userId, $year);

        // Get absence breakdown by type
        $absencesByType = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    absence_type,
                    COUNT(*) as count,
                    COALESCE(SUM(
                        CASE
                            WHEN hours_per_day IS NOT NULL THEN hours_per_day / 8.0
                            ELSE DATEDIFF(end_date, start_date) + 1
                        END
                    ), 0) as total_days
                 FROM {$wpdb->prefix}bookando_employees_days_off
                 WHERE user_id = %d
                 AND YEAR(start_date) = %d
                 AND request_status = 'approved'
                 GROUP BY absence_type",
                $userId,
                $year
            ),
            ARRAY_A
        );

        $breakdown = [];
        foreach ($absencesByType as $row) {
            $breakdown[$row['absence_type']] = [
                'count' => (int) $row['count'],
                'total_days' => (float) $row['total_days'],
            ];
        }

        return [
            'balance' => $balance,
            'breakdown' => $breakdown,
            'utilization_percentage' => $balance['entitled_days'] > 0
                ? round(($balance['taken_days'] / $balance['entitled_days']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Format balance data.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function formatBalance(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'year' => (int) $row['year'],
            'entitled_days' => (float) $row['entitled_days'],
            'carried_over_days' => (float) $row['carried_over_days'],
            'taken_days' => (float) $row['taken_days'],
            'planned_days' => (float) $row['planned_days'],
            'remaining_days' => (float) $row['remaining_days'],
            'total_available' => (float) $row['entitled_days'] + (float) $row['carried_over_days'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
