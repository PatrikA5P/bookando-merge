<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Services;

use Bookando\Modules\offers\Model as OffersModel;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use function array_filter;
use function array_intersect;
use function array_key_exists;
use function array_map;
use function array_shift;
use function array_slice;
use function array_sum;
use function array_values;
use function class_exists;
use function count;
use function delete_option;
use function explode;
use function get_option;
use function in_array;
use function max;
use function sanitize_key;
use function sanitize_text_field;
use function str_replace;
use function ucwords;
use function uniqid;
use function update_option;
use function uasort;
use function usort;
use function wp_date;
use function wp_parse_args;
use function wp_timezone;

/**
 * Handles smart planning for physical courses and events.
 */
class CoursePlannerService
{
    private const HISTORY_OPTION = 'bookando_course_planner_history';
    private const SETTINGS_OPTION = 'bookando_course_planner_settings';
    private const PLAN_OPTION = 'bookando_course_planner_plan';
    private const MAX_HISTORY = 1000;

    /**
     * Returns planner settings, analytics and latest plan.
     *
     * @return array<string, mixed>
     */
    public static function getState(): array
    {
        $history     = self::getHistory();
        $preferences = self::getPreferences();
        $analytics   = self::buildAnalytics($history);
        $plan        = get_option(self::PLAN_OPTION, []);

        return [
            'history'     => [
                'items' => array_slice(array_reverse($history), 0, 50),
                'total' => count($history),
            ],
            'preferences' => $preferences,
            'analytics'   => $analytics,
            'plan'        => $plan,
            'offers'      => self::getOffersCatalog(),
        ];
    }

    /**
     * Adds a new historical course entry.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function importHistory(array $payload): array
    {
        $history = self::getHistory();

        $entry = self::normalizeHistoryEntry($payload);
        $history[] = $entry;

        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, -self::MAX_HISTORY);
        }

        update_option(self::HISTORY_OPTION, array_values($history));
        // Force plan regeneration next time.
        delete_option(self::PLAN_OPTION);

        return $entry;
    }

    /**
     * Persists planner preferences.
     *
     * @param array<string, mixed> $preferences
     * @return array<string, mixed>
     */
    public static function savePreferences(array $preferences): array
    {
        $current = self::getPreferences();
        $merged  = array_merge($current, self::filterPreferences($preferences));

        update_option(self::SETTINGS_OPTION, $merged);
        // remove generated plan because constraints changed
        delete_option(self::PLAN_OPTION);

        return $merged;
    }

    /**
     * Generates a plan for the provided period and saves it.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function generatePlan(array $payload = []): array
    {
        $preferences = self::getPreferences();
        if (!empty($payload['preferences']) && is_array($payload['preferences'])) {
            $preferences = array_merge($preferences, self::filterPreferences($payload['preferences']));
            update_option(self::SETTINGS_OPTION, $preferences);
        }

        $tz          = wp_timezone();
        $periodStart = self::normalizeDateOnly($payload['period_start'] ?? null) ?: wp_date('Y-m-d');
        $periodEnd   = self::normalizeDateOnly($payload['period_end'] ?? null)
            ?: (new DateTimeImmutable($periodStart, $tz))->modify('+6 days')->format('Y-m-d');

        $history   = self::getHistory();
        $analytics = self::buildAnalytics($history);
        $targets   = self::normalizeTargets($preferences['course_type_targets'] ?? []);
        $linked    = self::normalizeLinkedGroups($preferences['linked_course_groups'] ?? []);

        $dates   = self::buildDatePool($periodStart, $periodEnd, $preferences['allowed_days'] ?? [], $tz);
        $slots   = self::buildSlotScores($history, $tz);
        $plan    = self::allocatePlan($dates, $slots, $targets, $preferences, $linked, $tz);

        $result = [
            'generated_at' => wp_date('c'),
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
            'entries'      => $plan['entries'],
            'notes'        => $payload['notes'] ?? null,
            'constraints'  => $preferences,
            'coverage'     => $plan['coverage'],
            'analytics'    => $analytics,
        ];

        update_option(self::PLAN_OPTION, $result);

        return $result;
    }

    /**
     * Returns offers for cross-module import.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getOffersCatalog(): array
    {
        if (!class_exists(OffersModel::class)) {
            return [];
        }

        $model  = new OffersModel();
        $result = $model->getPage(1, 200, 'title', 'ASC');

        return array_map(
            static fn (array $row): array => [
                'id'        => (int) $row['id'],
                'title'     => $row['title'],
                'status'    => $row['status'],
                'tenant_id' => $row['tenant_id'],
            ],
            $result['items']
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getHistory(): array
    {
        return get_option(self::HISTORY_OPTION, []);
    }

    /**
     * @return array<string, mixed>
     */
    private static function getPreferences(): array
    {
        return wp_parse_args(
            get_option(self::SETTINGS_OPTION, []),
            self::getDefaultPreferences()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function getDefaultPreferences(): array
    {
        return [
            'allowed_days'          => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'preferred_time_window' => [
                'start' => '08:00',
                'end'   => '21:00',
            ],
            'daylight_window'       => [
                'start' => '07:00',
                'end'   => '20:30',
            ],
            'require_daylight'      => false,
            'max_parallel_courses'  => 2,
            'require_employee_assignment' => false,
            'location_constraints'  => [],
            'course_type_targets'   => [
                ['type' => 'course', 'count' => 2],
            ],
            'linked_course_groups'  => [],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function normalizeHistoryEntry(array $payload): array
    {
        $title     = sanitize_text_field($payload['title'] ?? 'Kurs');
        $type      = sanitize_key($payload['type'] ?? 'course');
        $location  = sanitize_text_field($payload['location'] ?? '');
        $offerId   = isset($payload['offer_id']) ? (int) $payload['offer_id'] : null;
        $startDate = self::normalizeDateTime($payload['start'] ?? null, $payload['date'] ?? null, $payload['start_time'] ?? null);
        $endDate   = self::normalizeDateTime($payload['end'] ?? null, $payload['date'] ?? null, $payload['end_time'] ?? null, $startDate);
        $capacity  = max(1, (int) ($payload['capacity'] ?? 1));
        $attendance = max(0, (int) ($payload['attendance'] ?? 0));
        $status    = sanitize_key($payload['status'] ?? 'held');

        $entry = [
            'id'          => uniqid('course_', true),
            'offer_id'    => $offerId,
            'title'       => $title,
            'type'        => $type,
            'location'    => $location,
            'start'       => $startDate,
            'end'         => $endDate,
            'status'      => $status,
            'attendance'  => $attendance,
            'capacity'    => $capacity,
            'fill_rate'   => min(1, $capacity > 0 ? $attendance / $capacity : 0),
            'success'     => self::calculateSuccessScore($attendance, $capacity, $status),
            'source'      => $offerId ? 'offers' : 'manual',
            'imported_at' => wp_date('c'),
        ];

        return $entry;
    }

    private static function calculateSuccessScore(int $attendance, int $capacity, string $status): float
    {
        $fill = $capacity > 0 ? $attendance / $capacity : 0;
        $penalty = $status === 'cancelled' ? -0.6 : ($status === 'waitlist' ? 0.15 : 0);

        return max(0.0, min(1.0, $fill + $penalty));
    }

    /**
     * @param array<int, array{type: string, count: int}> $targets
     * @return array<string, int>
     */
    private static function normalizeTargets(array $targets): array
    {
        $normalized = [];
        foreach ($targets as $row) {
            if (empty($row['type'])) {
                continue;
            }
            $type = sanitize_key($row['type']);
            $normalized[$type] = max(1, (int) ($row['count'] ?? 1));
        }

        if ($normalized === []) {
            $normalized['course'] = 2;
        }

        return $normalized;
    }

    /**
     * @param array<int, array<int, string>> $groups
     * @return array<string, array<int, string>>
     */
    private static function normalizeLinkedGroups(array $groups): array
    {
        $map = [];
        foreach ($groups as $group) {
            $types = array_filter(array_map('sanitize_key', (array) $group));
            if (count($types) < 2) {
                continue;
            }
            foreach ($types as $type) {
                $map[$type] = $types;
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private static function filterPreferences(array $preferences): array
    {
        $allowedDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        $pref = [];

        if (!empty($preferences['allowed_days']) && is_array($preferences['allowed_days'])) {
            $pref['allowed_days'] = array_values(array_intersect(
                $allowedDays,
                array_map('sanitize_key', $preferences['allowed_days'])
            ));
        }

        if (!empty($preferences['preferred_time_window'])) {
            $pref['preferred_time_window'] = [
                'start' => self::normalizeTime($preferences['preferred_time_window']['start'] ?? '08:00'),
                'end'   => self::normalizeTime($preferences['preferred_time_window']['end'] ?? '21:00'),
            ];
        }

        if (!empty($preferences['daylight_window'])) {
            $pref['daylight_window'] = [
                'start' => self::normalizeTime($preferences['daylight_window']['start'] ?? '07:00'),
                'end'   => self::normalizeTime($preferences['daylight_window']['end'] ?? '20:30'),
            ];
        }

        if (array_key_exists('require_daylight', $preferences)) {
            $pref['require_daylight'] = (bool) $preferences['require_daylight'];
        }

        if (array_key_exists('max_parallel_courses', $preferences)) {
            $pref['max_parallel_courses'] = max(1, (int) $preferences['max_parallel_courses']);
        }

        if (array_key_exists('require_employee_assignment', $preferences)) {
            $pref['require_employee_assignment'] = (bool) $preferences['require_employee_assignment'];
        }

        if (!empty($preferences['location_constraints']) && is_array($preferences['location_constraints'])) {
            $pref['location_constraints'] = array_values($preferences['location_constraints']);
        }

        if (!empty($preferences['course_type_targets']) && is_array($preferences['course_type_targets'])) {
            $pref['course_type_targets'] = array_values($preferences['course_type_targets']);
        }

        if (!empty($preferences['linked_course_groups']) && is_array($preferences['linked_course_groups'])) {
            $pref['linked_course_groups'] = array_values($preferences['linked_course_groups']);
        }

        return $pref;
    }

    private static function normalizeDateTime(?string $iso, ?string $date, ?string $time, ?string $fallbackStart = null): string
    {
        $tz = wp_timezone();
        if (!empty($iso)) {
            try {
                return (new DateTimeImmutable($iso, $tz))->format('c');
            } catch (Exception $exception) {
                // continue with fallback
            }
        }

        $datePart = $date ?: wp_date('Y-m-d');
        $timePart = self::normalizeTime($time ?? '08:00');
        $combined = $datePart . ' ' . $timePart;

        try {
            return (new DateTimeImmutable($combined, $tz))->format('c');
        } catch (Exception $exception) {
            if ($fallbackStart !== null) {
                return $fallbackStart;
            }

            return wp_date('c');
        }
    }

    private static function normalizeDateOnly(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return (new DateTimeImmutable($date, wp_timezone()))->format('Y-m-d');
        } catch (Exception $exception) {
            return null;
        }
    }

    private static function normalizeTime(?string $time): string
    {
        if (empty($time)) {
            return '08:00';
        }

        $parts = explode(':', $time);
        $hours = isset($parts[0]) ? max(0, min(23, (int) $parts[0])) : 8;
        $minutes = isset($parts[1]) ? max(0, min(59, (int) $parts[1])) : 0;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @return array<string, mixed>
     */
    private static function buildAnalytics(array $history): array
    {
        if ($history === []) {
            return [
                'total_sessions'    => 0,
                'avg_attendance'    => 0,
                'cancellation_rate' => 0,
                'popular_slots'     => [],
                'types'             => [],
            ];
        }

        $totalAttendance = 0;
        $cancelled       = 0;
        $types           = [];
        $slots           = [];
        $tz              = wp_timezone();

        foreach ($history as $entry) {
            $totalAttendance += (int) ($entry['attendance'] ?? 0);
            if (($entry['status'] ?? '') === 'cancelled') {
                $cancelled++;
            }

            $type = $entry['type'] ?? 'course';
            if (!isset($types[$type])) {
                $types[$type] = [
                    'count'        => 0,
                    'avg_fill'     => 0,
                    'cancel_rate'  => 0,
                    'attendance'   => 0,
                ];
            }

            $types[$type]['count']++;
            $types[$type]['attendance'] += (int) ($entry['attendance'] ?? 0);
            $types[$type]['avg_fill'] += (float) ($entry['fill_rate'] ?? 0);
            if (($entry['status'] ?? '') === 'cancelled') {
                $types[$type]['cancel_rate']++;
            }

            try {
                $start = new DateTimeImmutable($entry['start'] ?? 'now', $tz);
            } catch (Exception $exception) {
                $start = new DateTimeImmutable('now', $tz);
            }

            $day  = strtolower($start->format('l'));
            $time = $start->format('H:i');
            $slotKey = $day . '@' . $time;

            if (!isset($slots[$slotKey])) {
                $slots[$slotKey] = [
                    'day'   => $day,
                    'time'  => $time,
                    'score' => 0,
                    'count' => 0,
                    'type'  => $type,
                ];
            }

            $slots[$slotKey]['score'] += (float) ($entry['success'] ?? 0);
            $slots[$slotKey]['count']++;
        }

        foreach ($types as $type => $data) {
            $types[$type]['avg_fill'] = $data['count'] > 0 ? round($data['avg_fill'] / $data['count'] * 100, 1) : 0;
            $types[$type]['cancel_rate'] = $data['count'] > 0 ? round(($data['cancel_rate'] / $data['count']) * 100, 1) : 0;
            $types[$type]['avg_attendance'] = $data['count'] > 0 ? round($data['attendance'] / $data['count'], 1) : 0;
        }

        $popular = array_map(
            static function (array $slot): array {
                $slot['score'] = $slot['count'] > 0 ? round($slot['score'] / $slot['count'], 2) : 0;
                $slot['label'] = ucwords($slot['day']) . ' ' . $slot['time'];

                return $slot;
            },
            $slots
        );

        usort($popular, static fn ($a, $b) => $b['score'] <=> $a['score']);

        return [
            'total_sessions'    => count($history),
            'avg_attendance'    => round($totalAttendance / max(1, count($history)), 1),
            'cancellation_rate' => round(($cancelled / max(1, count($history))) * 100, 1),
            'popular_slots'     => array_slice($popular, 0, 6),
            'types'             => $types,
        ];
    }

    /**
     * @param string $start
     * @param string $end
     * @param array<int, string> $days
     * @return array<string, array<int, DateTimeImmutable>>
     */
    private static function buildDatePool(string $start, string $end, array $days, DateTimeZone $tz): array
    {
        $startDate = new DateTimeImmutable($start, $tz);
        $endDate   = (new DateTimeImmutable($end, $tz))->modify('+1 day');
        $period    = new DatePeriod($startDate, new DateInterval('P1D'), $endDate);
        $pool      = [];

        $allowed = $days ?: ['monday','tuesday','wednesday','thursday','friday'];

        foreach ($period as $date) {
            $day = strtolower($date->format('l'));
            if (!in_array($day, $allowed, true)) {
                continue;
            }

            $pool[$day][] = $date;
        }

        return $pool;
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function buildSlotScores(array $history, DateTimeZone $tz): array
    {
        $slots = [];

        foreach ($history as $entry) {
            $type = $entry['type'] ?? 'course';
            try {
                $start = new DateTimeImmutable($entry['start'] ?? 'now', $tz);
            } catch (Exception $exception) {
                $start = new DateTimeImmutable('now', $tz);
            }

            $day  = strtolower($start->format('l'));
            $time = $start->format('H:i');
            $key  = $day . '@' . $time;

            if (!isset($slots[$type])) {
                $slots[$type] = [];
            }

            if (!isset($slots[$type][$key])) {
                $slots[$type][$key] = [
                    'day'    => $day,
                    'time'   => $time,
                    'score'  => 0,
                    'count'  => 0,
                    'location' => $entry['location'] ?? '',
                    'title'  => $entry['title'] ?? '',
                ];
            }

            $slots[$type][$key]['score'] += (float) ($entry['success'] ?? 0);
            $slots[$type][$key]['count']++;
        }

        foreach ($slots as $type => $typedSlots) {
            uasort($typedSlots, static fn ($a, $b) => ($b['score'] / max(1, $b['count'])) <=> ($a['score'] / max(1, $a['count'])));
            $slots[$type] = $typedSlots;
        }

        return $slots;
    }

    /**
     * @param array<string, array<int, DateTimeImmutable>> $dates
     * @param array<string, array<string, mixed>> $slots
     * @param array<string, int> $targets
     * @param array<string, mixed> $preferences
     * @param array<string, array<int, string>> $linked
     * @return array{entries: array<int, array<string, mixed>>, coverage: array<string, mixed>}
     */
    private static function allocatePlan(
        array $dates,
        array $slots,
        array $targets,
        array $preferences,
        array $linked,
        DateTimeZone $tz
    ): array {
        $entries    = [];
        $progress   = array_fill_keys(array_keys($targets), 0);
        $slotUsage  = [];
        $datesByDay = $dates;
        $maxParallel = (int) ($preferences['max_parallel_courses'] ?? 2);
        $timeWindow = $preferences['preferred_time_window'] ?? ['start' => '08:00', 'end' => '21:00'];
        $requireDaylight = (bool) ($preferences['require_daylight'] ?? false);
        $daylight = $preferences['daylight_window'] ?? ['start' => '07:00', 'end' => '20:30'];
        $requireEmployee = (bool) ($preferences['require_employee_assignment'] ?? false);
        $locationConstraints = $preferences['location_constraints'] ?? [];

        // Load employee availability if required
        $employeeAvailability = $requireEmployee ? self::getEmployeeAvailability() : [];

        $loopGuard = 0;
        while (array_sum($progress) < array_sum($targets) && $loopGuard < 500) {
            $loopGuard++;
            $progressed = false;

            foreach ($targets as $type => $target) {
                if ($progress[$type] >= $target) {
                    continue;
                }

                $slot = self::pickSlotForType(
                    $type,
                    $datesByDay,
                    $slots[$type] ?? [],
                    $slotUsage,
                    $timeWindow,
                    $requireDaylight,
                    $daylight,
                    $maxParallel,
                    $requireEmployee,
                    $employeeAvailability,
                    $locationConstraints,
                    $entries, // Pass existing entries for location conflict check
                    $tz
                );

                if ($slot === null) {
                    continue;
                }

                $entries[] = $slot;
                $progress[$type]++;
                $progressed = true;

                $slotKey = $slot['date'] . '@' . $slot['start'];
                $slotUsage[$slotKey] = ($slotUsage[$slotKey] ?? 0) + 1;

                if (isset($linked[$type])) {
                    foreach ($linked[$type] as $linkedType) {
                        if ($linkedType === $type || !isset($targets[$linkedType])) {
                            continue;
                        }
                        if ($progress[$linkedType] >= $targets[$linkedType]) {
                            continue;
                        }

                        $linkedEntry = $slot;
                        $linkedEntry['type']  = $linkedType;
                        $linkedEntry['title'] = ucwords($linkedType) . ' ' . $linkedEntry['title'];
                        $entries[] = $linkedEntry;
                        $progress[$linkedType]++;
                        $slotUsage[$slotKey] = ($slotUsage[$slotKey] ?? 0) + 1;
                    }
                }
            }

            if (!$progressed) {
                break;
            }
        }

        usort($entries, static fn ($a, $b) => strcmp($a['start_iso'], $b['start_iso']));

        $coverage = [
            'scheduled_sessions' => count($entries),
            'types'              => $progress,
        ];

        return [
            'entries'  => $entries,
            'coverage' => $coverage,
        ];
    }

    /**
     * @param array<string, array<int, DateTimeImmutable>> $datesByDay
     * @param array<string, array<string, mixed>> $slotScores
     * @param array<string, int> $slotUsage
     * @param array<int, array<string, mixed>> $employeeAvailability
     * @param array<int, array<string, mixed>> $locationConstraints
     * @param array<int, array<string, mixed>> $existingEntries
     * @return array<string, mixed>|null
     */
    private static function pickSlotForType(
        string $type,
        array &$datesByDay,
        array $slotScores,
        array $slotUsage,
        array $timeWindow,
        bool $requireDaylight,
        array $daylight,
        int $maxParallel,
        bool $requireEmployee,
        array $employeeAvailability,
        array $locationConstraints,
        array $existingEntries,
        DateTimeZone $tz
    ): ?array {
        $preferredSlots = $slotScores === [] ? [] : $slotScores;
        if ($preferredSlots === []) {
            // fallback create synthetic slots from available days
            foreach ($datesByDay as $day => $dates) {
                foreach ($dates as $date) {
                    $preferredSlots[$day . '@' . $timeWindow['start']] = [
                        'day'  => $day,
                        'time' => $timeWindow['start'],
                        'score'=> 0.5,
                        'count'=> 1,
                    ];
                }
            }
        }

        foreach ($preferredSlots as $slotKey => $slot) {
            $day = $slot['day'];
            if (empty($datesByDay[$day])) {
                continue;
            }

            $date = array_shift($datesByDay[$day]);
            if (!$date instanceof DateTimeImmutable) {
                continue;
            }
            $datesByDay[$day][] = $date;
            $time = $slot['time'];

            if (!self::isTimeWithinWindow($time, $timeWindow)) {
                continue;
            }

            if ($requireDaylight && !self::isTimeWithinWindow($time, $daylight)) {
                continue;
            }

            $dateKey = $date->format('Y-m-d') . '@' . $time;
            if (($slotUsage[$dateKey] ?? 0) >= $maxParallel) {
                continue;
            }

            $duration = self::estimateDuration($type);
            $start = new DateTimeImmutable($date->format('Y-m-d') . ' ' . $time, $tz);
            $end = $start->modify('+' . $duration . ' minutes');

            // Check location constraints
            $location = $slot['location'] ?? '';
            if (!empty($locationConstraints) && $location) {
                $locationAllowed = self::checkLocationConstraints(
                    $location,
                    $day,
                    $time,
                    $locationConstraints,
                    $existingEntries,
                    $start,
                    $end
                );

                if (!$locationAllowed) {
                    continue; // Location doesn't meet constraints
                }
            }

            // Check employee availability if required
            $assignedEmployee = null;
            if ($requireEmployee && !empty($employeeAvailability)) {
                $assignedEmployee = self::findAvailableEmployee(
                    $employeeAvailability,
                    $date->format('Y-m-d'),
                    $time,
                    $duration
                );

                // If no employee available, skip this slot
                if ($assignedEmployee === null) {
                    continue;
                }
            }

            return [
                'type'       => $type,
                'title'      => $slot['title'] ?? ucwords($type),
                'date'       => $date->format('Y-m-d'),
                'weekday'    => $day,
                'start'      => $time,
                'end'        => $end->format('H:i'),
                'start_iso'  => $start->format('c'),
                'end_iso'    => $end->format('c'),
                'location'   => $location,
                'score'      => $slot['count'] > 0 ? round($slot['score'] / $slot['count'], 2) : 0.4,
                'employee_id' => $assignedEmployee['id'] ?? null,
                'employee_name' => $assignedEmployee['name'] ?? null,
            ];
        }

        return null;
    }

    private static function isTimeWithinWindow(string $time, array $window): bool
    {
        $timeInt   = (int) str_replace(':', '', $time);
        $startInt  = (int) str_replace(':', '', $window['start'] ?? '00:00');
        $endInt    = (int) str_replace(':', '', $window['end'] ?? '23:59');

        return $timeInt >= $startInt && $timeInt <= $endInt;
    }

    private static function estimateDuration(string $type): int
    {
        return match ($type) {
            'event'   => 180,
            'intense' => 120,
            default   => 90,
        };
    }

    /**
     * Get employee availability from employees module.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getEmployeeAvailability(): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT id, first_name, last_name
             FROM {$wpdb->prefix}bookando_users
             WHERE JSON_CONTAINS(roles, '\"bookando_employee\"')
             AND status = 'active'
             ORDER BY first_name, last_name",
            ARRAY_A
        );

        return array_map(static function ($row) {
            return [
                'id' => (int) $row['id'],
                'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            ];
        }, $results ?: []);
    }

    /**
     * Find an available employee for a specific date and time.
     *
     * @param array<int, array<string, mixed>> $employees
     * @param string $date Date in Y-m-d format
     * @param string $time Time in H:i format
     * @param int $durationMinutes Duration in minutes
     * @return array<string, mixed>|null
     */
    private static function findAvailableEmployee(
        array $employees,
        string $date,
        string $time,
        int $durationMinutes
    ): ?array {
        global $wpdb;

        $tz = wp_timezone();
        $start = new DateTimeImmutable($date . ' ' . $time, $tz);
        $end = $start->modify('+' . $durationMinutes . ' minutes');

        foreach ($employees as $employee) {
            $employeeId = $employee['id'];

            // Check if employee has approved absence on this date
            $absence = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                     FROM {$wpdb->prefix}bookando_employees_days_off
                     WHERE user_id = %d
                     AND request_status = 'approved'
                     AND start_date <= %s
                     AND end_date >= %s",
                    $employeeId,
                    $date,
                    $date
                )
            );

            if ((int) $absence > 0) {
                continue; // Employee has absence
            }

            // Check if employee has overlapping booking
            $booking = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                     FROM {$wpdb->prefix}bookando_bookings
                     WHERE employee_id = %d
                     AND status NOT IN ('cancelled', 'rejected')
                     AND (
                         (start_datetime <= %s AND end_datetime > %s)
                         OR (start_datetime < %s AND end_datetime >= %s)
                         OR (start_datetime >= %s AND end_datetime <= %s)
                     )",
                    $employeeId,
                    $start->format('Y-m-d H:i:s'),
                    $start->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s'),
                    $start->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s')
                )
            );

            if ((int) $booking > 0) {
                continue; // Employee has conflicting booking
            }

            // Employee is available
            return $employee;
        }

        return null;
    }

    /**
     * Check if location meets constraints.
     *
     * @param string $location Location name
     * @param string $day Day of week
     * @param string $time Time (H:i)
     * @param array<int, array<string, mixed>> $locationConstraints
     * @param array<int, array<string, mixed>> $existingEntries
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     * @return bool
     */
    private static function checkLocationConstraints(
        string $location,
        string $day,
        string $time,
        array $locationConstraints,
        array $existingEntries,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): bool {
        foreach ($locationConstraints as $constraint) {
            $constraintLocation = $constraint['location'] ?? '';

            // Skip if constraint is for a different location
            if ($constraintLocation !== $location) {
                continue;
            }

            // Check available days
            $availableDays = $constraint['available_days'] ?? [];
            if (!empty($availableDays) && !in_array($day, $availableDays, true)) {
                return false; // Location not available on this day
            }

            // Check max concurrent courses at this location
            $maxConcurrent = (int) ($constraint['max_concurrent_at_location'] ?? PHP_INT_MAX);

            if ($maxConcurrent < PHP_INT_MAX) {
                $concurrentCount = 0;

                foreach ($existingEntries as $entry) {
                    if (($entry['location'] ?? '') !== $location) {
                        continue;
                    }

                    $entryStart = new DateTimeImmutable($entry['start_iso']);
                    $entryEnd = new DateTimeImmutable($entry['end_iso']);

                    // Check for time overlap
                    if ($start < $entryEnd && $end > $entryStart) {
                        $concurrentCount++;
                    }
                }

                if ($concurrentCount >= $maxConcurrent) {
                    return false; // Too many concurrent courses at this location
                }
            }
        }

        return true; // All constraints met
    }

    /**
     * Validate a plan entry for conflicts.
     *
     * @param array<string, mixed> $entry Entry to validate
     * @param array<int, array<string, mixed>> $existingPlan Existing plan entries
     * @param int $maxParallel Max parallel courses allowed
     * @return array<int, array<string, mixed>> Array of conflicts (empty if no conflicts)
     */
    public static function validatePlanEntry(array $entry, array $existingPlan, int $maxParallel = 2): array
    {
        $conflicts = [];

        $entryStart = new DateTimeImmutable($entry['start_iso']);
        $entryEnd = new DateTimeImmutable($entry['end_iso']);
        $entryLocation = $entry['location'] ?? null;
        $entryEmployeeId = $entry['employee_id'] ?? null;
        $entryDate = $entry['date'];

        // Track parallel courses on same date/time
        $parallelCount = 0;

        foreach ($existingPlan as $existingEntry) {
            // Skip if not same entry
            if (isset($entry['id']) && isset($existingEntry['id']) && $entry['id'] === $existingEntry['id']) {
                continue;
            }

            $existingStart = new DateTimeImmutable($existingEntry['start_iso']);
            $existingEnd = new DateTimeImmutable($existingEntry['end_iso']);

            // Check for time overlap
            $hasTimeOverlap = (
                ($entryStart < $existingEnd && $entryEnd > $existingStart)
            );

            if (!$hasTimeOverlap) {
                continue; // No overlap, no possible conflicts
            }

            // Count parallel courses
            $parallelCount++;

            // Conflict 1: Location conflict (same location at same time)
            if ($entryLocation && $entryLocation === ($existingEntry['location'] ?? null)) {
                $conflicts[] = [
                    'type' => 'location_conflict',
                    'message' => 'Location "' . $entryLocation . '" ist zur gleichen Zeit bereits belegt',
                    'conflicting_entry' => [
                        'title' => $existingEntry['title'] ?? 'Unbekannt',
                        'start' => $existingEntry['start_iso'],
                        'end' => $existingEntry['end_iso'],
                    ],
                ];
            }

            // Conflict 2: Employee double booking
            if ($entryEmployeeId && $entryEmployeeId === ($existingEntry['employee_id'] ?? null)) {
                $conflicts[] = [
                    'type' => 'employee_conflict',
                    'message' => 'Mitarbeiter "' . ($entry['employee_name'] ?? 'ID ' . $entryEmployeeId) . '" ist zur gleichen Zeit bereits eingeteilt',
                    'conflicting_entry' => [
                        'title' => $existingEntry['title'] ?? 'Unbekannt',
                        'start' => $existingEntry['start_iso'],
                        'end' => $existingEntry['end_iso'],
                    ],
                ];
            }
        }

        // Conflict 3: Max parallel courses exceeded
        if ($parallelCount >= $maxParallel) {
            $conflicts[] = [
                'type' => 'parallel_limit_exceeded',
                'message' => 'Maximum von ' . $maxParallel . ' parallelen Kursen zur gleichen Zeit überschritten',
                'parallel_count' => $parallelCount + 1, // +1 for the new entry
                'max_allowed' => $maxParallel,
            ];
        }

        return $conflicts;
    }

    /**
     * Validate entire plan for conflicts.
     *
     * @param array<int, array<string, mixed>> $plan Plan to validate
     * @param int $maxParallel Max parallel courses
     * @return array<int, array<string, mixed>> Array of all conflicts with entry references
     */
    public static function validatePlan(array $plan, int $maxParallel = 2): array
    {
        $allConflicts = [];

        foreach ($plan as $index => $entry) {
            $conflicts = self::validatePlanEntry($entry, $plan, $maxParallel);

            if (!empty($conflicts)) {
                $allConflicts[] = [
                    'entry_index' => $index,
                    'entry_title' => $entry['title'] ?? 'Unbekannt',
                    'entry_date' => $entry['date'],
                    'entry_start' => $entry['start'],
                    'conflicts' => $conflicts,
                ];
            }
        }

        return $allConflicts;
    }
}
