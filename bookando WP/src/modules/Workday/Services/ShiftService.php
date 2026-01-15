<?php

declare(strict_types=1);

namespace Bookando\Modules\Workday\Services;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Shift Management Service
 *
 * Manages shift creation, updating, and conflict detection.
 *
 * @package Bookando\Modules\Workday\Services
 */
class ShiftService
{
    /**
     * Create a new shift.
     *
     * @param array<string, mixed> $data Shift data
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function createShift(array $data): array
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['user_id']) || empty($data['shift_date']) || empty($data['start_time']) || empty($data['end_time'])) {
            throw new \InvalidArgumentException('Missing required fields: user_id, shift_date, start_time, end_time');
        }

        // Check for conflicts
        $conflicts = self::detectConflicts(
            (int) $data['user_id'],
            $data['shift_date'],
            $data['start_time'],
            $data['end_time'],
            null // no shift_id for new shifts
        );

        if (!empty($conflicts)) {
            throw new \RuntimeException('Shift conflicts detected: ' . json_encode($conflicts));
        }

        $shiftData = [
            'tenant_id' => $data['tenant_id'] ?? null,
            'user_id' => (int) $data['user_id'],
            'shift_date' => $data['shift_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'break_minutes' => $data['break_minutes'] ?? 0,
            'location_id' => $data['location_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'event_period_id' => $data['event_period_id'] ?? null,
            'shift_type' => $data['shift_type'] ?? 'regular',
            'status' => $data['status'] ?? 'draft',
            'notes' => $data['notes'] ?? null,
            'color' => $data['color'] ?? null,
            'template_id' => $data['template_id'] ?? null,
            'generated_by' => $data['generated_by'] ?? 'manual',
            'recurring_rule' => isset($data['recurring_rule']) ? json_encode($data['recurring_rule']) : null,
            'created_by' => $data['created_by'] ?? get_current_user_id(),
        ];

        $wpdb->insert(
            $wpdb->prefix . 'bookando_shifts',
            $shiftData,
            ['%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d']
        );

        $shiftId = $wpdb->insert_id;

        return self::getShift($shiftId);
    }

    /**
     * Update an existing shift.
     *
     * @param int $shiftId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function updateShift(int $shiftId, array $data): array
    {
        global $wpdb;

        $shift = self::getShift($shiftId);
        if (!$shift) {
            throw new \InvalidArgumentException('Shift not found');
        }

        // If time or date changed, check for conflicts
        if (isset($data['shift_date']) || isset($data['start_time']) || isset($data['end_time'])) {
            $conflicts = self::detectConflicts(
                $shift['user_id'],
                $data['shift_date'] ?? $shift['shift_date'],
                $data['start_time'] ?? $shift['start_time'],
                $data['end_time'] ?? $shift['end_time'],
                $shiftId
            );

            if (!empty($conflicts)) {
                throw new \RuntimeException('Shift conflicts detected: ' . json_encode($conflicts));
            }
        }

        $updateData = [];
        $updateFormats = [];

        $allowedFields = [
            'shift_date' => '%s',
            'start_time' => '%s',
            'end_time' => '%s',
            'break_minutes' => '%d',
            'location_id' => '%d',
            'service_id' => '%d',
            'event_period_id' => '%d',
            'shift_type' => '%s',
            'status' => '%s',
            'notes' => '%s',
            'color' => '%s',
        ];

        foreach ($allowedFields as $field => $format) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
                $updateFormats[] = $format;
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_by'] = get_current_user_id();
            $updateFormats[] = '%d';

            $wpdb->update(
                $wpdb->prefix . 'bookando_shifts',
                $updateData,
                ['id' => $shiftId],
                $updateFormats,
                ['%d']
            );
        }

        return self::getShift($shiftId);
    }

    /**
     * Delete a shift.
     *
     * @param int $shiftId
     * @return bool
     */
    public static function deleteShift(int $shiftId): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            $wpdb->prefix . 'bookando_shifts',
            ['id' => $shiftId],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get a shift by ID.
     *
     * @param int $shiftId
     * @return array<string, mixed>|null
     */
    public static function getShift(int $shiftId): ?array
    {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT s.*, u.first_name, u.last_name
                 FROM {$wpdb->prefix}bookando_shifts s
                 LEFT JOIN {$wpdb->prefix}bookando_users u ON s.user_id = u.id
                 WHERE s.id = %d",
                $shiftId
            ),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return self::formatShift($result);
    }

    /**
     * Get shifts for a date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getShifts(string $startDate, string $endDate, array $filters = []): array
    {
        global $wpdb;

        $where = ['s.shift_date >= %s', 's.shift_date <= %s'];
        $params = [$startDate, $endDate];

        if (!empty($filters['user_id'])) {
            $where[] = 's.user_id = %d';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 's.status = %s';
            $params[] = $filters['status'];
        }

        if (!empty($filters['shift_type'])) {
            $where[] = 's.shift_type = %s';
            $params[] = $filters['shift_type'];
        }

        if (!empty($filters['location_id'])) {
            $where[] = 's.location_id = %d';
            $params[] = $filters['location_id'];
        }

        $whereClause = implode(' AND ', $where);

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, u.first_name, u.last_name
                 FROM {$wpdb->prefix}bookando_shifts s
                 LEFT JOIN {$wpdb->prefix}bookando_users u ON s.user_id = u.id
                 WHERE {$whereClause}
                 ORDER BY s.shift_date ASC, s.start_time ASC",
                ...$params
            ),
            ARRAY_A
        );

        return array_map([self::class, 'formatShift'], $results ?: []);
    }

    /**
     * Publish shifts (make them visible to employees).
     *
     * @param array<int> $shiftIds
     * @param int $publishedBy
     * @return array<string, mixed>
     */
    public static function publishShifts(array $shiftIds, int $publishedBy): array
    {
        global $wpdb;

        if (empty($shiftIds)) {
            return ['published' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($shiftIds), '%d'));

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}bookando_shifts
                 SET status = 'published',
                     published_at = NOW(),
                     published_by = %d
                 WHERE id IN ({$placeholders})
                 AND status = 'draft'",
                $publishedBy,
                ...$shiftIds
            )
        );

        return [
            'published' => $result !== false ? $result : 0,
            'shift_ids' => $shiftIds,
        ];
    }

    /**
     * Detect conflicts for a shift.
     *
     * @param int $userId
     * @param string $shiftDate
     * @param string $startTime
     * @param string $endTime
     * @param int|null $excludeShiftId
     * @return array<string, mixed>
     */
    public static function detectConflicts(
        int $userId,
        string $shiftDate,
        string $startTime,
        string $endTime,
        ?int $excludeShiftId = null
    ): array {
        global $wpdb;

        $conflicts = [];

        // 1. Check for overlapping shifts
        $query = "SELECT id, shift_type, start_time, end_time
                  FROM {$wpdb->prefix}bookando_shifts
                  WHERE user_id = %d
                  AND shift_date = %s
                  AND status NOT IN ('cancelled', 'draft')
                  AND (
                      (start_time < %s AND end_time > %s)
                      OR (start_time < %s AND end_time > %s)
                      OR (start_time >= %s AND end_time <= %s)
                  )";

        $params = [$userId, $shiftDate, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];

        if ($excludeShiftId) {
            $query .= " AND id != %d";
            $params[] = $excludeShiftId;
        }

        $overlappingShifts = $wpdb->get_results(
            $wpdb->prepare($query, ...$params),
            ARRAY_A
        );

        if ($overlappingShifts) {
            $conflicts['overlapping_shifts'] = $overlappingShifts;
        }

        // 2. Check for absence (days_off)
        $absence = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, absence_type
                 FROM {$wpdb->prefix}bookando_employees_days_off
                 WHERE user_id = %d
                 AND %s BETWEEN start_date AND end_date
                 AND request_status = 'approved'",
                $userId,
                $shiftDate
            ),
            ARRAY_A
        );

        if ($absence) {
            $conflicts['absence'] = $absence;
        }

        // 3. Check rest period (11 hours between shifts)
        $previousShift = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT shift_date, end_time
                 FROM {$wpdb->prefix}bookando_shifts
                 WHERE user_id = %d
                 AND shift_date < %s
                 AND status NOT IN ('cancelled', 'draft')
                 ORDER BY shift_date DESC, end_time DESC
                 LIMIT 1",
                $userId,
                $shiftDate
            ),
            ARRAY_A
        );

        if ($previousShift) {
            $prevEnd = new DateTimeImmutable($previousShift['shift_date'] . ' ' . $previousShift['end_time']);
            $currentStart = new DateTimeImmutable($shiftDate . ' ' . $startTime);
            $hoursBetween = ($currentStart->getTimestamp() - $prevEnd->getTimestamp()) / 3600;

            if ($hoursBetween < 11) {
                $conflicts['rest_period_violation'] = [
                    'previous_shift_end' => $prevEnd->format('c'),
                    'current_shift_start' => $currentStart->format('c'),
                    'hours_between' => round($hoursBetween, 2),
                    'required_hours' => 11,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Format shift data.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function formatShift(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'tenant_id' => $row['tenant_id'] ? (int) $row['tenant_id'] : null,
            'user_id' => (int) $row['user_id'],
            'employee_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'shift_date' => $row['shift_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'break_minutes' => (int) $row['break_minutes'],
            'location_id' => $row['location_id'] ? (int) $row['location_id'] : null,
            'service_id' => $row['service_id'] ? (int) $row['service_id'] : null,
            'event_period_id' => $row['event_period_id'] ? (int) $row['event_period_id'] : null,
            'shift_type' => $row['shift_type'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'color' => $row['color'],
            'template_id' => $row['template_id'] ? (int) $row['template_id'] : null,
            'generated_by' => $row['generated_by'],
            'recurring_rule' => $row['recurring_rule'] ? json_decode($row['recurring_rule'], true) : null,
            'published_at' => $row['published_at'],
            'published_by' => $row['published_by'] ? (int) $row['published_by'] : null,
            'created_by' => $row['created_by'] ? (int) $row['created_by'] : null,
            'updated_by' => $row['updated_by'] ? (int) $row['updated_by'] : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
