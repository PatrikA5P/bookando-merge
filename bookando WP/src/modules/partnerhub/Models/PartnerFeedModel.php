<?php

namespace Bookando\Modules\Partnerhub\Models;

use Bookando\Core\Database\BaseModel;

class PartnerFeedModel extends BaseModel
{
    protected string $table = 'bookando_partner_feeds';

    protected array $fillable = [
        'tenant_id', 'partner_id', 'feed_type', 'feed_name', 'feed_slug',
        'include_types', 'include_categories', 'include_locations', 'custom_filters',
        'access_token', 'ip_whitelist', 'rate_limit_per_hour',
        'total_requests', 'last_accessed_at', 'last_accessed_ip', 'status',
        'created_by', 'updated_by',
    ];

    protected array $casts = [
        'include_categories' => 'json',
        'include_locations' => 'json',
        'custom_filters' => 'json',
        'ip_whitelist' => 'json',
    ];

    public function generate_access_token(): string
    {
        return 'feed_' . bin2hex(random_bytes(32));
    }

    public function get_by_token(string $token): ?object
    {
        global $wpdb;
        $table = $this->get_table_name();

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE access_token = %s AND status = 'active' AND deleted_at IS NULL LIMIT 1",
            $token
        );

        return $wpdb->get_row($sql) ?: null;
    }

    public function record_access(int $feed_id, string $ip): bool
    {
        global $wpdb;
        $table = $this->get_table_name();

        return $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET total_requests = total_requests + 1, last_accessed_at = NOW(), last_accessed_ip = %s WHERE id = %d",
            $ip, $feed_id
        ));
    }

    public function check_rate_limit(int $feed_id): bool
    {
        global $wpdb;
        $table = $this->get_table_name();

        $feed = $this->get_by_id($feed_id);
        if (!$feed || !$feed->rate_limit_per_hour) {
            return true;
        }

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) as count FROM {$wpdb->prefix}bookando_partner_audit_logs
             WHERE entity_type = 'feed' AND entity_id = %d
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $feed_id
        );

        $count = $wpdb->get_var($sql);
        return $count < $feed->rate_limit_per_hour;
    }
}
