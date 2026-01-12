<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Services;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function count;
use function explode;
use function get_option;
use function in_array;
use function max;
use function round;
use function sanitize_key;
use function sanitize_text_field;
use function strtolower;
use function uniqid;
use function update_option;
use function wp_date;
use function wp_parse_args;
use function wp_timezone;
use function strtotime;

/**
 * Duty scheduler with shift templates and availability.
 */
class DutySchedulerService
{
    private const STATE_OPTION = 'bookando_duty_scheduler';

    /**
     * @return array<string, mixed>
     */
    public static function getState(): array
    {
        $state = self::getRawState();
        $state['analytics'] = self::buildAnalytics($state['roster']['assignments'] ?? []);

        return $state;
    }

    /**
     * @param array<string, mixed> $template
     * @return array<string, mixed>
     */
    public static function saveTemplate(array $template): array
    {
        $state = self::getRawState();
        $templates = $state['templates'];

        $id = sanitize_key($template['id'] ?? '');
        if ($id === '') {
            $id = sanitize_key($template['label'] ?? '') ?: uniqid('shift_');
        }

        $entry = [
            'id'    => $id,
            'label' => sanitize_text_field($template['label'] ?? 'Schicht'),
            'start' => $template['start'] ?? '08:00',
            'end'   => $template['end'] ?? '16:00',
            'days'  => array_values(array_map('sanitize_key', $template['days'] ?? [])),
            'roles' => array_values(array_map(static function ($role) {
                return [
                    'role'     => sanitize_key($role['role'] ?? 'trainer'),
                    'required' => max(1, (int) ($role['required'] ?? 1)),
                ];
            }, $template['roles'] ?? [])),
        ];

        $templates = array_values(array_filter($templates, static fn ($row) => $row['id'] !== $id));
        $templates[] = $entry;
        $state['templates'] = $templates;

        self::persist($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function saveAvailability(array $payload): array
    {
        $state = self::getRawState();

        $employeeId = sanitize_key($payload['employee_id'] ?? '');
        if ($employeeId === '') {
            throw new \InvalidArgumentException('employee_id is required');
        }

        $state['availability'][$employeeId] = [
            'employee_id'     => $employeeId,
            'name'            => sanitize_text_field($payload['name'] ?? ''),
            'roles'           => array_values(array_map('sanitize_key', $payload['roles'] ?? [])),
            'weekly_capacity' => max(8, (int) ($payload['weekly_capacity'] ?? 40)),
            'preferred_shifts'=> array_values(array_map('sanitize_key', $payload['preferred_shifts'] ?? [])),
            'unavailable_days'=> array_values(array_map('sanitize_key', $payload['unavailable_days'] ?? [])),
        ];

        self::persist($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function updateConstraints(array $payload): array
    {
        $state = self::getRawState();
        $constraints = $state['constraints'];

        if (array_key_exists('max_hours_per_week', $payload)) {
            $constraints['max_hours_per_week'] = max(8, (int) $payload['max_hours_per_week']);
        }

        if (array_key_exists('min_rest_hours', $payload)) {
            $constraints['min_rest_hours'] = max(1, (int) $payload['min_rest_hours']);
        }

        if (array_key_exists('allow_overtime', $payload)) {
            $constraints['allow_overtime'] = (bool) $payload['allow_overtime'];
        }

        $state['constraints'] = $constraints;
        self::persist($state);

        return $state;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function generateRoster(array $payload): array
    {
        $state = self::getRawState();
        $tz    = wp_timezone();
        $start = self::normalizeDate($payload['period_start'] ?? null, $tz) ?: wp_date('Y-m-d');
        $end   = self::normalizeDate($payload['period_end'] ?? null, $tz)
            ?: (new DateTimeImmutable($start, $tz))->modify('+6 days')->format('Y-m-d');

        $assignments = self::buildAssignments(
            $state['templates'],
            $state['availability'],
            $state['constraints'],
            $start,
            $end,
            $tz
        );

        $state['roster'] = [
            'period_start' => $start,
            'period_end'   => $end,
            'assignments'  => $assignments,
            'generated_at' => wp_date('c'),
        ];

        self::persist($state);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    private static function getRawState(): array
    {
        return wp_parse_args(
            get_option(self::STATE_OPTION, []),
            [
                'templates'    => [],
                'availability' => [],
                'constraints'  => [
                    'max_hours_per_week' => 40,
                    'min_rest_hours'     => 11,
                    'allow_overtime'     => false,
                ],
                'roster'       => [
                    'period_start' => wp_date('Y-m-d'),
                    'period_end'   => wp_date('Y-m-d', strtotime('+6 days')),
                    'assignments'  => [],
                    'generated_at' => null,
                ],
            ]
        );
    }

    private static function persist(array $state): void
    {
        update_option(self::STATE_OPTION, $state);
    }

    private static function normalizeDate(?string $date, DateTimeZone $tz): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return (new DateTimeImmutable($date, $tz))->format('Y-m-d');
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $assignments
     * @return array<string, mixed>
     */
    private static function buildAnalytics(array $assignments): array
    {
        if ($assignments === []) {
            return [
                'by_day'    => [],
                'fill_rate' => 0,
            ];
        }

        $coverage = [];
        $filled   = 0;

        foreach ($assignments as $assignment) {
            $date = $assignment['date'] ?? null;
            if ($date === null) {
                continue;
            }

            if (!isset($coverage[$date])) {
                $coverage[$date] = [
                    'total'  => 0,
                    'open'   => 0,
                    'filled' => 0,
                ];
            }

            $coverage[$date]['total']++;
            if (($assignment['status'] ?? 'open') === 'assigned') {
                $coverage[$date]['filled']++;
                $filled++;
            } else {
                $coverage[$date]['open']++;
            }
        }

        $fillRate = round(($filled / count($assignments)) * 100, 2);

        return [
            'by_day'    => $coverage,
            'fill_rate' => $fillRate,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $templates
     * @param array<string, array<string, mixed>> $availability
     * @param array<string, mixed> $constraints
     * @return array<int, array<string, mixed>>
     */
    private static function buildAssignments(
        array $templates,
        array $availability,
        array $constraints,
        string $periodStart,
        string $periodEnd,
        DateTimeZone $tz
    ): array {
        $start = new DateTimeImmutable($periodStart, $tz);
        $end   = (new DateTimeImmutable($periodEnd, $tz))->modify('+1 day');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);

        $assignments = [];
        $hoursUsed   = array_fill_keys(array_keys($availability), 0);
        $lastShiftEnd = [];

        foreach ($period as $date) {
            $day = strtolower($date->format('l'));
            foreach ($templates as $template) {
                if (!in_array($day, $template['days'], true)) {
                    continue;
                }

                foreach ($template['roles'] as $role) {
                    for ($i = 0; $i < $role['required']; $i++) {
                        $assignment = self::assignEmployee(
                            $role['role'],
                            $date,
                            $template,
                            $availability,
                            $hoursUsed,
                            $lastShiftEnd,
                            $constraints,
                            $tz
                        );

                        $assignments[] = $assignment;
                        if ($assignment['employee_id']) {
                            $hoursUsed[$assignment['employee_id']] += $assignment['duration_hours'];
                            $lastShiftEnd[$assignment['employee_id']] = $assignment['end_iso'];
                        }
                    }
                }
            }
        }

        return $assignments;
    }

    /**
     * @param array<string, array<string, mixed>> $availability
     * @param array<string, float> $hoursUsed
     * @param array<string, string> $lastShiftEnd
     * @param array<string, mixed> $constraints
     * @return array<string, mixed>
     */
    private static function assignEmployee(
        string $role,
        DateTimeImmutable $date,
        array $template,
        array $availability,
        array &$hoursUsed,
        array &$lastShiftEnd,
        array $constraints,
        DateTimeZone $tz
    ): array {
        $duration = self::calculateDuration($template['start'], $template['end']);
        $start    = new DateTimeImmutable($date->format('Y-m-d') . ' ' . $template['start'], $tz);
        $end      = $start->modify('+' . $duration . ' minutes');

        foreach ($availability as $employee) {
            $employeeId = $employee['employee_id'];
            $roles      = $employee['roles'];
            $unavailable = $employee['unavailable_days'];
            $preferred   = $employee['preferred_shifts'];

            if (!in_array($role, $roles, true)) {
                continue;
            }

            if (in_array(strtolower($date->format('l')), $unavailable, true)) {
                continue;
            }

            if ($preferred !== [] && !in_array($template['id'], $preferred, true)) {
                continue;
            }

            $maxHours = min(
                (int) $employee['weekly_capacity'],
                (int) ($constraints['max_hours_per_week'] ?? 40)
            );

            if (($hoursUsed[$employeeId] ?? 0) + ($duration / 60) > $maxHours && !($constraints['allow_overtime'] ?? false)) {
                continue;
            }

            if (isset($lastShiftEnd[$employeeId])) {
                $lastEnd = new DateTimeImmutable($lastShiftEnd[$employeeId]);
                $diff    = $start->getTimestamp() - $lastEnd->getTimestamp();
                $requiredRest = ((int) ($constraints['min_rest_hours'] ?? 11)) * 3600;
                if ($diff < $requiredRest) {
                    continue;
                }
            }

            return [
                'date'           => $date->format('Y-m-d'),
                'weekday'        => strtolower($date->format('l')),
                'shift_id'       => $template['id'],
                'shift_label'    => $template['label'],
                'start'          => $template['start'],
                'end'            => $template['end'],
                'start_iso'      => $start->format('c'),
                'end_iso'        => $end->format('c'),
                'role'           => $role,
                'employee_id'    => $employeeId,
                'employee_name'  => $employee['name'],
                'status'         => 'assigned',
                'duration_hours' => round($duration / 60, 2),
            ];
        }

        return [
            'date'           => $date->format('Y-m-d'),
            'weekday'        => strtolower($date->format('l')),
            'shift_id'       => $template['id'],
            'shift_label'    => $template['label'],
            'start'          => $template['start'],
            'end'            => $template['end'],
            'start_iso'      => $start->format('c'),
            'end_iso'        => $end->format('c'),
            'role'           => $role,
            'employee_id'    => null,
            'employee_name'  => null,
            'status'         => 'open',
            'duration_hours' => round($duration / 60, 2),
        ];
    }

    private static function calculateDuration(string $start, string $end): int
    {
        $startParts = explode(':', $start);
        $endParts   = explode(':', $end);
        $startMinutes = ((int) $startParts[0] * 60) + (int) ($startParts[1] ?? 0);
        $endMinutes   = ((int) $endParts[0] * 60) + (int) ($endParts[1] ?? 0);

        if ($endMinutes <= $startMinutes) {
            $endMinutes += 24 * 60;
        }

        return $endMinutes - $startMinutes;
    }
}
