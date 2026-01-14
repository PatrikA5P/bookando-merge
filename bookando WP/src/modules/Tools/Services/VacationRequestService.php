<?php

declare(strict_types=1);

namespace Bookando\Modules\Tools\Services;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use function wp_date;
use function wp_timezone;

/**
 * Vacation Request Service with approval workflow.
 *
 * @deprecated Use Bookando\Modules\workday\Services\VacationRequestService instead
 * @todo Remove in next major version - functionality moved to workday module
 *
 * Features:
 * - Employee vacation requests
 * - Approval/rejection workflow
 * - Remaining vacation days calculation
 * - Integration with days_off table
 */
class VacationRequestService
{
    private const STATUS_APPROVED = 'approved';
    private const STATUS_PENDING = 'pending';
    private const STATUS_REJECTED = 'rejected';
    private const STATUS_CANCELLED = 'cancelled';

    /**
     * Get vacation requests for display.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public static function getRequests(array $filters = []): array
    {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'd.user_id = %d';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'd.request_status = %s';
            $params[] = $filters['status'];
        }

        if (!empty($filters['year'])) {
            $where[] = 'YEAR(d.start_date) = %d';
            $params[] = $filters['year'];
        }

        // Multi-tenant isolation: only show requests for employees in same tenant
        $currentUser = wp_get_current_user();
        if ($currentUser->ID) {
            $currentUserData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT tenant_id FROM {$wpdb->prefix}bookando_users WHERE id = %d",
                    $currentUser->ID
                ),
                ARRAY_A
            );
            if ($currentUserData && !empty($currentUserData['tenant_id'])) {
                $where[] = '(u.tenant_id = %d OR u.tenant_id IS NULL)';
                $params[] = (int) $currentUserData['tenant_id'];
            }
        }

        $whereClause = implode(' AND ', $where);
        if (!empty($params)) {
            $whereClause = $wpdb->prepare($whereClause, ...$params);
        }

        $results = $wpdb->get_results(
            "SELECT d.*,
                    u.first_name, u.last_name, u.tenant_id,
                    req.first_name as requester_first_name, req.last_name as requester_last_name,
                    rev.first_name as reviewer_first_name, rev.last_name as reviewer_last_name
             FROM {$wpdb->prefix}bookando_employees_days_off d
             LEFT JOIN {$wpdb->prefix}bookando_users u ON d.user_id = u.id
             LEFT JOIN {$wpdb->prefix}bookando_users req ON d.requested_by = req.id
             LEFT JOIN {$wpdb->prefix}bookando_users rev ON d.reviewed_by = rev.id
             WHERE {$whereClause}
             ORDER BY d.requested_at DESC, d.start_date DESC",
            ARRAY_A
        );

        return array_map(static function ($row) {
            return self::formatRequest($row);
        }, $results ?: []);
    }

    /**
     * Create a new vacation request.
     *
     * @param int $userId Employee requesting vacation
     * @param array<string, mixed> $data Request data
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function createRequest(int $userId, array $data): array
    {
        global $wpdb;

        $tz = wp_timezone();
        $startDate = new DateTimeImmutable($data['start_date'], $tz);
        $endDate = new DateTimeImmutable($data['end_date'] ?? $data['start_date'], $tz);

        if ($endDate < $startDate) {
            throw new \InvalidArgumentException('End date must be after start date');
        }

        // Check for overlapping requests
        $overlap = self::checkOverlap($userId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        if ($overlap) {
            throw new \RuntimeException('Overlapping vacation request exists');
        }

        $requestData = [
            'user_id' => $userId,
            'name' => $data['name'] ?? 'Urlaub',
            'note' => $data['note'] ?? null,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'repeat_yearly' => 0,
            'request_status' => self::STATUS_PENDING,
            'requested_by' => $userId,
            'requested_at' => wp_date('Y-m-d H:i:s'),
        ];

        $wpdb->insert(
            $wpdb->prefix . 'bookando_employees_days_off',
            $requestData,
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s']
        );

        $requestData['id'] = $wpdb->insert_id;

        return self::formatRequest($requestData);
    }

    /**
     * Approve a vacation request.
     *
     * @param int $requestId Request ID
     * @param int $reviewerId User ID of reviewer
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function approveRequest(int $requestId, int $reviewerId): array
    {
        global $wpdb;

        $request = self::getRequestById($requestId);
        if (!$request) {
            throw new \InvalidArgumentException('Request not found');
        }

        if ($request['request_status'] !== self::STATUS_PENDING) {
            throw new \RuntimeException('Only pending requests can be approved');
        }

        $wpdb->update(
            $wpdb->prefix . 'bookando_employees_days_off',
            [
                'request_status' => self::STATUS_APPROVED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => wp_date('Y-m-d H:i:s'),
            ],
            ['id' => $requestId],
            ['%s', '%d', '%s'],
            ['%d']
        );

        return self::getRequestById($requestId);
    }

    /**
     * Reject a vacation request.
     *
     * @param int $requestId Request ID
     * @param int $reviewerId User ID of reviewer
     * @param string|null $reason Rejection reason
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function rejectRequest(int $requestId, int $reviewerId, ?string $reason = null): array
    {
        global $wpdb;

        $request = self::getRequestById($requestId);
        if (!$request) {
            throw new \InvalidArgumentException('Request not found');
        }

        if ($request['request_status'] !== self::STATUS_PENDING) {
            throw new \RuntimeException('Only pending requests can be rejected');
        }

        $wpdb->update(
            $wpdb->prefix . 'bookando_employees_days_off',
            [
                'request_status' => self::STATUS_REJECTED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => wp_date('Y-m-d H:i:s'),
                'rejection_reason' => $reason,
            ],
            ['id' => $requestId],
            ['%s', '%d', '%s', '%s'],
            ['%d']
        );

        return self::getRequestById($requestId);
    }

    /**
     * Cancel a vacation request (employee initiated).
     *
     * @param int $requestId Request ID
     * @param int $userId User ID (must be requester)
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function cancelRequest(int $requestId, int $userId): array
    {
        global $wpdb;

        $request = self::getRequestById($requestId);
        if (!$request) {
            throw new \InvalidArgumentException('Request not found');
        }

        if ((int) $request['requested_by'] !== $userId) {
            throw new \RuntimeException('Only the requester can cancel this request');
        }

        if ($request['request_status'] === self::STATUS_CANCELLED) {
            throw new \RuntimeException('Request already cancelled');
        }

        $wpdb->update(
            $wpdb->prefix . 'bookando_employees_days_off',
            ['request_status' => self::STATUS_CANCELLED],
            ['id' => $requestId],
            ['%s'],
            ['%d']
        );

        return self::getRequestById($requestId);
    }

    /**
     * Calculate remaining vacation days for an employee.
     *
     * @param int $userId Employee ID
     * @param int $year Year to calculate for
     * @param int $annualEntitlement Total days per year
     * @return array<string, mixed>
     */
    public static function calculateRemainingDays(int $userId, int $year, int $annualEntitlement = 30): array
    {
        global $wpdb;

        // Verify tenant access
        $employee = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT tenant_id FROM {$wpdb->prefix}bookando_users WHERE id = %d",
                $userId
            ),
            ARRAY_A
        );

        if (!$employee) {
            return [
                'year' => $year,
                'annual_entitlement' => $annualEntitlement,
                'used_days' => 0,
                'pending_days' => 0,
                'remaining_days' => $annualEntitlement,
                'available_days' => $annualEntitlement,
            ];
        }

        // Get approved vacation days for the year
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    SUM(DATEDIFF(d.end_date, d.start_date) + 1) as used_days
                 FROM {$wpdb->prefix}bookando_employees_days_off d
                 WHERE d.user_id = %d
                 AND d.request_status = %s
                 AND YEAR(d.start_date) = %d
                 AND d.repeat_yearly = 0",
                $userId,
                self::STATUS_APPROVED,
                $year
            ),
            ARRAY_A
        );

        $usedDays = (int) ($result['used_days'] ?? 0);
        $remainingDays = $annualEntitlement - $usedDays;

        // Get pending requests
        $pendingResult = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    SUM(DATEDIFF(d.end_date, d.start_date) + 1) as pending_days
                 FROM {$wpdb->prefix}bookando_employees_days_off d
                 WHERE d.user_id = %d
                 AND d.request_status = %s
                 AND YEAR(d.start_date) = %d",
                $userId,
                self::STATUS_PENDING,
                $year
            ),
            ARRAY_A
        );

        $pendingDays = (int) ($pendingResult['pending_days'] ?? 0);

        return [
            'year' => $year,
            'annual_entitlement' => $annualEntitlement,
            'used_days' => $usedDays,
            'pending_days' => $pendingDays,
            'remaining_days' => $remainingDays,
            'available_days' => $remainingDays - $pendingDays,
        ];
    }

    /**
     * Get vacation overview for an employee.
     *
     * @param int $userId Employee ID
     * @param int|null $year Year (null = current year)
     * @return array<string, mixed>
     */
    public static function getEmployeeOverview(int $userId, ?int $year = null): array
    {
        $tz = wp_timezone();
        $year = $year ?? (int) (new DateTimeImmutable('now', $tz))->format('Y');

        $requests = self::getRequests(['user_id' => $userId, 'year' => $year]);
        $remaining = self::calculateRemainingDays($userId, $year);

        return [
            'user_id' => $userId,
            'year' => $year,
            'requests' => $requests,
            'remaining_days' => $remaining,
        ];
    }

    /**
     * Check for overlapping vacation requests.
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeId Exclude this request ID from check
     * @return bool
     */
    private static function checkOverlap(int $userId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        global $wpdb;

        $excludeClause = $excludeId ? $wpdb->prepare('AND id != %d', $excludeId) : '';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->prefix}bookando_employees_days_off
                 WHERE user_id = %d
                 AND request_status IN (%s, %s)
                 AND (
                     (start_date <= %s AND end_date >= %s)
                     OR (start_date <= %s AND end_date >= %s)
                     OR (start_date >= %s AND end_date <= %s)
                 )
                 {$excludeClause}",
                $userId,
                self::STATUS_APPROVED,
                self::STATUS_PENDING,
                $startDate, $startDate,
                $endDate, $endDate,
                $startDate, $endDate
            )
        );

        return (int) $count > 0;
    }

    /**
     * Get request by ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    private static function getRequestById(int $id): ?array
    {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT d.*,
                        u.first_name, u.last_name,
                        req.first_name as requester_first_name, req.last_name as requester_last_name,
                        rev.first_name as reviewer_first_name, rev.last_name as reviewer_last_name
                 FROM {$wpdb->prefix}bookando_employees_days_off d
                 LEFT JOIN {$wpdb->prefix}bookando_users u ON d.user_id = u.id
                 LEFT JOIN {$wpdb->prefix}bookando_users req ON d.requested_by = req.id
                 LEFT JOIN {$wpdb->prefix}bookando_users rev ON d.reviewed_by = rev.id
                 WHERE d.id = %d",
                $id
            ),
            ARRAY_A
        );

        return $result ? self::formatRequest($result) : null;
    }

    /**
     * Format request data.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function formatRequest(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'employee_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'name' => $row['name'] ?? '',
            'note' => $row['note'] ?? null,
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'days_count' => self::calculateDaysCount($row['start_date'], $row['end_date']),
            'repeat_yearly' => (bool) $row['repeat_yearly'],
            'request_status' => $row['request_status'] ?? self::STATUS_APPROVED,
            'requested_by' => $row['requested_by'] ? (int) $row['requested_by'] : null,
            'requester_name' => isset($row['requester_first_name'])
                ? trim(($row['requester_first_name'] ?? '') . ' ' . ($row['requester_last_name'] ?? ''))
                : null,
            'requested_at' => $row['requested_at'] ?? null,
            'reviewed_by' => $row['reviewed_by'] ? (int) $row['reviewed_by'] : null,
            'reviewer_name' => isset($row['reviewer_first_name'])
                ? trim(($row['reviewer_first_name'] ?? '') . ' ' . ($row['reviewer_last_name'] ?? ''))
                : null,
            'reviewed_at' => $row['reviewed_at'] ?? null,
            'rejection_reason' => $row['rejection_reason'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    /**
     * Calculate number of days between two dates (inclusive).
     *
     * @param string $startDate
     * @param string $endDate
     * @return int
     */
    private static function calculateDaysCount(string $startDate, string $endDate): int
    {
        try {
            $start = new DateTimeImmutable($startDate);
            $end = new DateTimeImmutable($endDate);
            $diff = $start->diff($end);
            return $diff->days + 1;
        } catch (Exception $e) {
            return 1;
        }
    }
}
