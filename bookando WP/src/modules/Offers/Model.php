<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers;

use Bookando\Core\Model\BaseModel;

final class Model extends BaseModel
{
    protected string $tableName;

    public function __construct()
    {
        parent::__construct();
        $this->tableName = $this->table('offers');
    }

    /**
     * @return string[]
     */
    protected function allowedOrderBy(): array
    {
        return ['id', 'title', 'offer_type', 'price', 'start_date', 'featured', 'display_order', 'status', 'created_at', 'updated_at'];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, perPage: int}
     */
    public function getPage(int $page, int $perPage, ?string $orderBy = null, string $direction = 'DESC', ?string $offerType = null): array
    {
        $sql = "SELECT *\n                FROM {$this->tableName}\n                WHERE deleted_at IS NULL";

        $params = [];

        if ($offerType !== null) {
            $sql .= " AND offer_type = %s";
            $params[] = $offerType;
        }

        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return parent::paginate($sql, $params, $page, $perPage, $orderBy, $dir);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT *\n                FROM {$this->tableName}\n                WHERE id = %d AND deleted_at IS NULL";

        $row = $this->fetchOne($sql, [$id]);

        if ($row) {
            // Decode JSON fields
            $row['category_ids'] = !empty($row['category_ids']) ? json_decode($row['category_ids'], true) : [];
            $row['tag_ids'] = !empty($row['tag_ids']) ? json_decode($row['tag_ids'], true) : [];
            $row['employee_ids'] = !empty($row['employee_ids']) ? json_decode($row['employee_ids'], true) : [];
            $row['location_ids'] = !empty($row['location_ids']) ? json_decode($row['location_ids'], true) : [];
            $row['recurrence_pattern'] = !empty($row['recurrence_pattern']) ? json_decode($row['recurrence_pattern'], true) : null;
            $row['academy_course_ids'] = !empty($row['academy_course_ids']) ? json_decode($row['academy_course_ids'], true) : [];
        }

        return $row;
    }

    public function create(array $data): int
    {
        $payload = $this->filter($data);
        $payload['created_at'] = $payload['created_at'] ?? $this->now();
        $payload['updated_at'] = $payload['updated_at'] ?? $payload['created_at'];

        return $this->insert($payload);
    }

    public function update(int $id, array $data): bool
    {
        $payload = $this->filter($data);
        if ($payload === []) {
            return true;
        }

        $payload['updated_at'] = $this->now();

        $result = parent::update($id, $payload);

        return $result >= 0;
    }

    public function delete(int $id, bool $hard = false): bool
    {
        if ($hard) {
            return parent::delete($id) > 0;
        }

        $result = parent::update($id, ['deleted_at' => $this->now()]);

        return $result > 0;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filter(array $data): array
    {
        $allowed = [
            'title', 'description', 'offer_type', 'price', 'currency',
            'duration_minutes', 'schedule_type', 'start_date', 'end_date',
            'start_time', 'end_time', 'recurrence_pattern', 'max_participants',
            'current_participants', 'access_duration_days', 'course_url', 'platform',
            'booking_enabled', 'requires_approval', 'advance_booking_min',
            'advance_booking_max', 'category_ids', 'tag_ids', 'employee_ids',
            'location_ids', 'featured', 'display_order', 'image_url',
            'academy_course_ids', 'academy_access_type', 'academy_access_duration_days',
            'auto_enroll_academy', 'academy_certificate_on_completion',
            'status', 'created_at', 'updated_at'
        ];

        $filtered = array_intersect_key($data, array_flip($allowed));

        // Encode JSON fields if they're arrays
        if (isset($filtered['category_ids']) && is_array($filtered['category_ids'])) {
            $filtered['category_ids'] = wp_json_encode($filtered['category_ids']);
        }
        if (isset($filtered['tag_ids']) && is_array($filtered['tag_ids'])) {
            $filtered['tag_ids'] = wp_json_encode($filtered['tag_ids']);
        }
        if (isset($filtered['employee_ids']) && is_array($filtered['employee_ids'])) {
            $filtered['employee_ids'] = wp_json_encode($filtered['employee_ids']);
        }
        if (isset($filtered['location_ids']) && is_array($filtered['location_ids'])) {
            $filtered['location_ids'] = wp_json_encode($filtered['location_ids']);
        }
        if (isset($filtered['recurrence_pattern']) && is_array($filtered['recurrence_pattern'])) {
            $filtered['recurrence_pattern'] = wp_json_encode($filtered['recurrence_pattern']);
        }
        if (isset($filtered['academy_course_ids']) && is_array($filtered['academy_course_ids'])) {
            $filtered['academy_course_ids'] = wp_json_encode($filtered['academy_course_ids']);
        }

        return $filtered;
    }

    /**
     * Get offers by type
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByType(string $offerType): array
    {
        $sql = "SELECT *
                FROM {$this->tableName}
                WHERE deleted_at IS NULL
                  AND offer_type = %s
                  AND status = 'active'
                ORDER BY display_order ASC, featured DESC, start_date ASC";

        return $this->fetchAll($sql, [$offerType]) ?: [];
    }

    /**
     * Get courses by date range (calendar view)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByDateRange(string $startDate, string $endDate, ?string $offerType = null): array
    {
        $sql = "SELECT *
                FROM {$this->tableName}
                WHERE deleted_at IS NULL
                  AND status = 'active'
                  AND start_date IS NOT NULL
                  AND start_date >= %s
                  AND start_date <= %s";

        $params = [$startDate, $endDate];

        if ($offerType !== null) {
            $sql .= " AND offer_type = %s";
            $params[] = $offerType;
        }

        $sql .= " ORDER BY start_date ASC, start_time ASC";

        $results = $this->fetchAll($sql, $params) ?: [];

        // Decode JSON fields
        foreach ($results as &$row) {
            $row['category_ids'] = !empty($row['category_ids']) ? json_decode($row['category_ids'], true) : [];
            $row['tag_ids'] = !empty($row['tag_ids']) ? json_decode($row['tag_ids'], true) : [];
            $row['employee_ids'] = !empty($row['employee_ids']) ? json_decode($row['employee_ids'], true) : [];
            $row['location_ids'] = !empty($row['location_ids']) ? json_decode($row['location_ids'], true) : [];
            $row['recurrence_pattern'] = !empty($row['recurrence_pattern']) ? json_decode($row['recurrence_pattern'], true) : null;
            $row['academy_course_ids'] = !empty($row['academy_course_ids']) ? json_decode($row['academy_course_ids'], true) : [];
        }

        return $results;
    }

    /**
     * Get upcoming courses (next 30 days)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingCourses(int $limit = 10): array
    {
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days'));

        $sql = "SELECT *
                FROM {$this->tableName}
                WHERE deleted_at IS NULL
                  AND status = 'active'
                  AND offer_type = %s
                  AND start_date >= %s
                  AND start_date <= %s
                ORDER BY start_date ASC, start_time ASC
                LIMIT %d";

        $results = $this->fetchAll($sql, [OfferType::KURSE, $today, $endDate, $limit]) ?: [];

        // Decode JSON fields
        foreach ($results as &$row) {
            $row['category_ids'] = !empty($row['category_ids']) ? json_decode($row['category_ids'], true) : [];
            $row['tag_ids'] = !empty($row['tag_ids']) ? json_decode($row['tag_ids'], true) : [];
            $row['employee_ids'] = !empty($row['employee_ids']) ? json_decode($row['employee_ids'], true) : [];
            $row['location_ids'] = !empty($row['location_ids']) ? json_decode($row['location_ids'], true) : [];
            $row['recurrence_pattern'] = !empty($row['recurrence_pattern']) ? json_decode($row['recurrence_pattern'], true) : null;
            $row['academy_course_ids'] = !empty($row['academy_course_ids']) ? json_decode($row['academy_course_ids'], true) : [];
        }

        return $results;
    }

    /**
     * Get courses grouped by date (for calendar view)
     *
     * @return array<string, array<int, array<string, mixed>>> Array keyed by date (Y-m-d)
     */
    public function getCoursesGroupedByDate(string $startDate, string $endDate): array
    {
        $courses = $this->getByDateRange($startDate, $endDate, OfferType::KURSE);

        $grouped = [];
        foreach ($courses as $course) {
            $date = $course['start_date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $course;
        }

        return $grouped;
    }

    /**
     * Check if offer has available spots (for courses)
     */
    public function hasAvailableSpots(int $id): bool
    {
        $offer = $this->find($id);
        if (!$offer) {
            return false;
        }

        // If no max_participants set, unlimited spots
        if ($offer['max_participants'] === null || $offer['max_participants'] <= 0) {
            return true;
        }

        return (int)$offer['current_participants'] < (int)$offer['max_participants'];
    }

    /**
     * Increment participant count
     */
    public function incrementParticipants(int $id): bool
    {
        global $wpdb;

        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tableName}
             SET current_participants = current_participants + 1
             WHERE id = %d AND deleted_at IS NULL",
            $id
        ));

        return $result !== false;
    }

    /**
     * Decrement participant count
     */
    public function decrementParticipants(int $id): bool
    {
        global $wpdb;

        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tableName}
             SET current_participants = GREATEST(0, current_participants - 1)
             WHERE id = %d AND deleted_at IS NULL",
            $id
        ));

        return $result !== false;
    }
}
