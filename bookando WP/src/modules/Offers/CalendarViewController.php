<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers;

/**
 * Calendar View Controller
 *
 * Handles calendar and list views for courses grouped by date
 */
final class CalendarViewController
{
    /**
     * Get calendar view data for a month
     *
     * @return array{dates: array<string, array>, meta: array}
     */
    public static function getMonthView(int $year, int $month): array
    {
        $model = new Model();

        // Get first and last day of month
        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = date('Y-m-t', strtotime($firstDay));

        // Get all courses in this month
        $courses = $model->getCoursesGroupedByDate($firstDay, $lastDay);

        // Build calendar structure
        $daysInMonth = (int)date('t', strtotime($firstDay));
        $dates = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dates[$date] = [
                'date' => $date,
                'day_name' => date('l', strtotime($date)),
                'day_number' => $day,
                'is_today' => $date === date('Y-m-d'),
                'courses' => $courses[$date] ?? [],
                'course_count' => count($courses[$date] ?? []),
            ];
        }

        return [
            'dates' => $dates,
            'meta' => [
                'year' => $year,
                'month' => $month,
                'month_name' => date('F', strtotime($firstDay)),
                'total_courses' => array_sum(array_map(fn($d) => $d['course_count'], $dates)),
            ],
        ];
    }

    /**
     * Get list view for a specific date
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDateView(string $date): array
    {
        $model = new Model();
        return $model->getByDateRange($date, $date, OfferType::KURSE);
    }

    /**
     * Get week view
     *
     * @return array{dates: array<string, array>, meta: array}
     */
    public static function getWeekView(string $startDate): array
    {
        $model = new Model();

        // Calculate week end date (6 days later)
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));

        // Get all courses in this week
        $courses = $model->getCoursesGroupedByDate($startDate, $endDate);

        // Build week structure
        $dates = [];
        $current = strtotime($startDate);

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', $current);
            $dates[$date] = [
                'date' => $date,
                'day_name' => date('l', $current),
                'day_short' => date('D', $current),
                'day_number' => date('j', $current),
                'is_today' => $date === date('Y-m-d'),
                'courses' => $courses[$date] ?? [],
                'course_count' => count($courses[$date] ?? []),
            ];
            $current = strtotime('+1 day', $current);
        }

        return [
            'dates' => $dates,
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'week_number' => date('W', strtotime($startDate)),
                'total_courses' => array_sum(array_map(fn($d) => $d['course_count'], $dates)),
            ],
        ];
    }

    /**
     * Get upcoming courses list
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUpcomingList(int $limit = 20): array
    {
        $model = new Model();
        return $model->getUpcomingCourses($limit);
    }

    /**
     * Get courses for date range with grouping options
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param string $groupBy How to group: 'date', 'week', 'month', 'none'
     * @return array
     */
    public static function getRangeView(string $startDate, string $endDate, string $groupBy = 'date'): array
    {
        $model = new Model();
        $courses = $model->getByDateRange($startDate, $endDate, OfferType::KURSE);

        if ($groupBy === 'none') {
            return ['courses' => $courses];
        }

        $grouped = [];

        foreach ($courses as $course) {
            $key = match ($groupBy) {
                'week' => date('Y-W', strtotime($course['start_date'])),
                'month' => date('Y-m', strtotime($course['start_date'])),
                default => $course['start_date'],
            };

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'courses' => [],
                    'count' => 0,
                ];

                // Add metadata based on grouping
                if ($groupBy === 'week') {
                    $grouped[$key]['week_number'] = date('W', strtotime($course['start_date']));
                    $grouped[$key]['year'] = date('Y', strtotime($course['start_date']));
                } elseif ($groupBy === 'month') {
                    $grouped[$key]['month_name'] = date('F', strtotime($course['start_date']));
                    $grouped[$key]['year'] = date('Y', strtotime($course['start_date']));
                } elseif ($groupBy === 'date') {
                    $grouped[$key]['date'] = $course['start_date'];
                    $grouped[$key]['day_name'] = date('l', strtotime($course['start_date']));
                }
            }

            $grouped[$key]['courses'][] = $course;
            $grouped[$key]['count']++;
        }

        return [
            'groups' => array_values($grouped),
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy,
                'total_courses' => count($courses),
                'total_groups' => count($grouped),
            ],
        ];
    }

    /**
     * Search courses by criteria
     *
     * @param array $criteria Search criteria (category_id, tag_id, employee_id, location_id, etc.)
     * @return array<int, array<string, mixed>>
     */
    public static function searchCourses(array $criteria): array
    {
        global $wpdb;
        $model = new Model();
        $table = $wpdb->prefix . 'bookando_offers';

        $sql = "SELECT *
                FROM {$table}
                WHERE deleted_at IS NULL
                  AND status = 'active'
                  AND offer_type = %s";

        $params = [OfferType::KURSE];

        // Date filters
        if (!empty($criteria['start_date'])) {
            $sql .= " AND start_date >= %s";
            $params[] = $criteria['start_date'];
        }

        if (!empty($criteria['end_date'])) {
            $sql .= " AND start_date <= %s";
            $params[] = $criteria['end_date'];
        }

        // JSON field filters (requires JSON functions)
        if (!empty($criteria['category_id'])) {
            $sql .= " AND JSON_CONTAINS(category_ids, %s, '$')";
            $params[] = '"' . (int)$criteria['category_id'] . '"';
        }

        if (!empty($criteria['tag_id'])) {
            $sql .= " AND JSON_CONTAINS(tag_ids, %s, '$')";
            $params[] = '"' . (int)$criteria['tag_id'] . '"';
        }

        if (!empty($criteria['employee_id'])) {
            $sql .= " AND JSON_CONTAINS(employee_ids, %s, '$')";
            $params[] = '"' . (int)$criteria['employee_id'] . '"';
        }

        if (!empty($criteria['location_id'])) {
            $sql .= " AND JSON_CONTAINS(location_ids, %s, '$')";
            $params[] = '"' . (int)$criteria['location_id'] . '"';
        }

        // Featured filter
        if (isset($criteria['featured'])) {
            $sql .= " AND featured = %d";
            $params[] = $criteria['featured'] ? 1 : 0;
        }

        // Available spots filter
        if (!empty($criteria['has_spots'])) {
            $sql .= " AND (max_participants IS NULL OR current_participants < max_participants)";
        }

        $sql .= " ORDER BY start_date ASC, start_time ASC";

        if (!empty($criteria['limit'])) {
            $sql .= " LIMIT %d";
            $params[] = (int)$criteria['limit'];
        }

        $results = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);

        // Decode JSON fields
        foreach ($results as &$row) {
            $row['category_ids'] = !empty($row['category_ids']) ? json_decode($row['category_ids'], true) : [];
            $row['tag_ids'] = !empty($row['tag_ids']) ? json_decode($row['tag_ids'], true) : [];
            $row['employee_ids'] = !empty($row['employee_ids']) ? json_decode($row['employee_ids'], true) : [];
            $row['location_ids'] = !empty($row['location_ids']) ? json_decode($row['location_ids'], true) : [];
            $row['recurrence_pattern'] = !empty($row['recurrence_pattern']) ? json_decode($row['recurrence_pattern'], true) : null;
        }

        return $results ?: [];
    }
}
