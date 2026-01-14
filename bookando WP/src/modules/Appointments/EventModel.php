<?php
namespace Bookando\Modules\Appointments;

use Bookando\Core\Model\BaseModel;
use Bookando\Core\Tenant\TenantManager;
use wpdb;

class EventModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->tableName = $this->table('events');
    }

    public function timeline(string $fromUtc, string $toUtc): array
    {
        $periods = $this->table('event_periods');
        $appointments = $this->table('appointments');

        $sql = "
            SELECT
                e.tenant_id,
                e.id AS event_id,
                e.name AS event_name,
                e.type AS event_type,
                e.status AS event_status,
                e.max_capacity,
                e.price,
                ep.id AS period_id,
                ep.period_start_utc,
                ep.period_end_utc,
                ep.time_zone,
                (
                    SELECT COUNT(*)
                    FROM {$appointments} ap
                    WHERE ap.event_id = e.id
                      AND ap.tenant_id = e.tenant_id
                      AND ap.starts_at_utc BETWEEN %s AND %s
                ) AS participant_count
            FROM {$periods} ep
            INNER JOIN {$this->tableName} e ON e.id = ep.event_id
            WHERE ep.period_start_utc BETWEEN %s AND %s
            ORDER BY ep.period_start_utc ASC
        ";

        return $this->fetchAll($sql, [$fromUtc, $toUtc, $fromUtc, $toUtc]);
    }

    public function findPeriod(int $periodId): ?array
    {
        $periods = $this->table('event_periods');
        $sql = "
            SELECT
                e.tenant_id,
                e.id AS event_id,
                e.name AS event_name,
                e.type AS event_type,
                e.status AS event_status,
                e.max_capacity,
                ep.id AS period_id,
                ep.period_start_utc,
                ep.period_end_utc,
                ep.time_zone
            FROM {$periods} ep
            INNER JOIN {$this->tableName} e ON e.id = ep.event_id
            WHERE ep.id = %d
        ";

        return $this->fetchOne($sql, [$periodId]);
    }

    public function getEventOptions(string $search = '', int $limit = 50): array
    {
        global $wpdb;
        $tenantId = TenantManager::currentTenantId();
        if (!$tenantId) {
            return [];
        }

        $events = $this->table('events');
        $periods = $this->table('event_periods');

        $where = "WHERE e.tenant_id = %d";
        $args = [$tenantId];

        if ($search !== '') {
            $where .= " AND e.name LIKE %s";
            $args[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $nowUtc = gmdate('Y-m-d H:i:s');

        $sql = "
            SELECT
                e.id AS event_id,
                e.name AS event_name,
                e.type AS event_type,
                e.status AS event_status,
                ep.id AS period_id,
                ep.period_start_utc,
                ep.period_end_utc,
                ep.time_zone
            FROM {$events} e
            LEFT JOIN {$periods} ep ON ep.event_id = e.id
            {$where}
            AND (ep.period_start_utc IS NULL OR ep.period_start_utc >= %s)
            ORDER BY ep.period_start_utc IS NULL, ep.period_start_utc ASC
            LIMIT %d
        ";
        $args[] = $nowUtc;
        $args[] = max(1, min(200, $limit));

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A);
        return $rows ?: [];
    }
}
