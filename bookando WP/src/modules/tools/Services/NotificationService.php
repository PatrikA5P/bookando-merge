<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Services;

use Bookando\Core\Database\Database;
use function wp_json_encode;
use function json_decode;

class NotificationService
{
    private const TABLE_NAME = 'bookando_notification_matrices';
    private const LOGS_TABLE_NAME = 'bookando_notification_logs';

    /**
     * Get all notification matrices
     *
     * @return array
     */
    public static function getAll(): array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        $results = $wpdb->get_results(
            "SELECT * FROM `{$table}` ORDER BY id DESC",
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        return array_map([self::class, 'formatMatrix'], $results);
    }

    /**
     * Get a single notification matrix by ID
     *
     * @param int $id
     * @return array|null
     */
    public static function get(int $id): ?array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return self::formatMatrix($result);
    }

    /**
     * Create a new notification matrix
     *
     * @param array $data
     * @return array|null
     */
    public static function create(array $data): ?array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        $insertData = [
            'tenant_id'  => get_current_user_id(), // TODO: Replace with actual tenant_id
            'name'       => $data['name'],
            'variants'   => wp_json_encode($data['variants'] ?? []),
            'is_active'  => !empty($data['is_active']) ? 1 : 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $inserted = $wpdb->insert(
            $table,
            $insertData,
            ['%d', '%s', '%s', '%d', '%s', '%s']
        );

        if (!$inserted) {
            return null;
        }

        return self::get($wpdb->insert_id);
    }

    /**
     * Update an existing notification matrix
     *
     * @param int $id
     * @param array $data
     * @return array|null
     */
    public static function update(int $id, array $data): ?array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        $updateData = [
            'updated_at' => current_time('mysql'),
        ];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['variants'])) {
            $updateData['variants'] = wp_json_encode($data['variants']);
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = !empty($data['is_active']) ? 1 : 0;
        }

        $updated = $wpdb->update(
            $table,
            $updateData,
            ['id' => $id],
            ['%s', '%s', '%s', '%d'],
            ['%d']
        );

        if ($updated === false) {
            return null;
        }

        return self::get($id);
    }

    /**
     * Delete a notification matrix
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        $deleted = $wpdb->delete(
            $table,
            ['id' => $id],
            ['%d']
        );

        return $deleted !== false;
    }

    /**
     * Get notification logs
     *
     * @param array $filters
     * @return array
     */
    public static function getLogs(array $filters = []): array
    {
        global $wpdb;
        $table = Database::table(self::LOGS_TABLE_NAME);

        $where = [];
        $whereValues = [];

        if (!empty($filters['channel'])) {
            $where[] = 'channel = %s';
            $whereValues[] = $filters['channel'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $whereValues[] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(sent_at) = %s';
            $whereValues[] = $filters['date'];
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT * FROM `{$table}` {$whereSql} ORDER BY sent_at DESC LIMIT 100";

        if (!empty($whereValues)) {
            $query = $wpdb->prepare($query, ...$whereValues);
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        if (!$results) {
            return [];
        }

        return array_map([self::class, 'formatLog'], $results);
    }

    /**
     * Get a single log by ID
     *
     * @param int $id
     * @return array|null
     */
    public static function getLog(int $id): ?array
    {
        global $wpdb;
        $table = Database::table(self::LOGS_TABLE_NAME);

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return self::formatLog($result);
    }

    /**
     * Create a notification log entry
     *
     * @param array $data
     * @return int|null
     */
    public static function createLog(array $data): ?int
    {
        global $wpdb;
        $table = Database::table(self::LOGS_TABLE_NAME);

        $insertData = [
            'tenant_id'           => get_current_user_id(), // TODO: Replace with actual tenant_id
            'notification_id'     => $data['notification_id'] ?? null,
            'notification_name'   => $data['notification_name'] ?? '',
            'recipient'           => $data['recipient'] ?? '',
            'channel'             => $data['channel'] ?? 'email',
            'status'              => $data['status'] ?? 'pending',
            'error_message'       => $data['error_message'] ?? null,
            'sent_at'             => $data['sent_at'] ?? current_time('mysql'),
            'metadata'            => wp_json_encode($data['metadata'] ?? []),
        ];

        $inserted = $wpdb->insert(
            $table,
            $insertData,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            return null;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get notifications for a specific trigger/recipient/channel combination
     *
     * @param string $trigger
     * @param string $recipient
     * @param string $channel
     * @return array
     */
    public static function getForTrigger(string $trigger, string $recipient, string $channel): array
    {
        $allMatrices = self::getAll();
        $matching = [];

        foreach ($allMatrices as $matrix) {
            if (!$matrix['is_active']) {
                continue;
            }

            $key = "{$recipient}:{$trigger}:{$channel}";
            if (isset($matrix['variants'][$key]) && $matrix['variants'][$key]['enabled']) {
                $matching[] = [
                    'matrix_id' => $matrix['id'],
                    'name'      => $matrix['name'],
                    'variant'   => $matrix['variants'][$key],
                ];
            }
        }

        return $matching;
    }

    /**
     * Format a notification matrix for output
     *
     * @param array $matrix
     * @return array
     */
    private static function formatMatrix(array $matrix): array
    {
        return [
            'id'         => (int) $matrix['id'],
            'name'       => $matrix['name'],
            'variants'   => json_decode($matrix['variants'] ?? '{}', true),
            'is_active'  => (bool) $matrix['is_active'],
            'created_at' => $matrix['created_at'],
            'updated_at' => $matrix['updated_at'],
        ];
    }

    /**
     * Format a notification log for output
     *
     * @param array $log
     * @return array
     */
    private static function formatLog(array $log): array
    {
        return [
            'id'                => (int) $log['id'],
            'notification_id'   => isset($log['notification_id']) ? (int) $log['notification_id'] : null,
            'notification_name' => $log['notification_name'] ?? '',
            'recipient'         => $log['recipient'] ?? '',
            'channel'           => $log['channel'] ?? '',
            'status'            => $log['status'] ?? '',
            'error_message'     => $log['error_message'] ?? null,
            'sent_at'           => $log['sent_at'] ?? '',
            'metadata'          => json_decode($log['metadata'] ?? '{}', true),
        ];
    }
}
