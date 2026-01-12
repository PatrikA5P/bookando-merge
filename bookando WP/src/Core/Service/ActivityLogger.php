<?php

namespace Bookando\Core\Service;

use wpdb;

/**
 * Persistentes Logging in die bookando_activity_log-Tabelle mit Fallback auf error_log.
 */
final class ActivityLogger
{
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    private const ALLOWED_LEVELS = [
        self::LEVEL_INFO,
        self::LEVEL_WARNING,
        self::LEVEL_ERROR,
    ];

    private static ?bool $tableExists = null;

    /**
     * Schreibe einen Logeintrag.
     */
    public static function log(
        string $context,
        string $message,
        array $payload = [],
        string $level = self::LEVEL_INFO,
        ?int $tenantId = null,
        ?string $moduleSlug = null
    ): void {
        $level = strtolower($level);
        if (!in_array($level, self::ALLOWED_LEVELS, true)) {
            $level = self::LEVEL_INFO;
        }

        $record = [
            'logged_at'   => current_time('mysql'),
            'severity'    => $level,
            'context'     => substr($context, 0, 190),
            'message'     => $message,
            'payload'     => !empty($payload) ? wp_json_encode($payload) : null,
            'tenant_id'   => $tenantId,
            'module_slug' => $moduleSlug ? substr($moduleSlug, 0, 190) : null,
        ];

        if (self::ensureTableExists()) {
            self::insertRecord($record);
            return;
        }

        $fallback = sprintf(
            '[Bookando][%s][%s] %s %s',
            strtoupper($record['severity']),
            $record['context'],
            $record['message'],
            $record['payload'] ? $record['payload'] : ''
        );
        error_log($fallback);
    }

    public static function info(string $context, string $message, array $payload = []): void
    {
        self::log($context, $message, $payload, self::LEVEL_INFO);
    }

    public static function warning(string $context, string $message, array $payload = []): void
    {
        self::log($context, $message, $payload, self::LEVEL_WARNING);
    }

    public static function error(string $context, string $message, array $payload = []): void
    {
        self::log($context, $message, $payload, self::LEVEL_ERROR);
    }

    /**
     * Liefert die letzten Logeinträge – mandantenbewusst, optional gefiltert.
     */
    public static function recent(int $limit = 50, array $filters = []): array
    {
        if (!self::ensureTableExists()) {
            return [];
        }

        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return [];
        }

        $limit = max(1, min(500, (int) $limit));

        $where = [];
        $params = [];

        $tenantId = $filters['tenant_id'] ?? \Bookando\Core\Tenant\TenantManager::currentTenantId();
        $includeGlobal = array_key_exists('include_global', $filters) ? (bool) $filters['include_global'] : true;

        if ($tenantId !== null) {
            $where[] = $includeGlobal ? '(tenant_id = %d OR tenant_id IS NULL)' : 'tenant_id = %d';
            $params[] = (int) $tenantId;
        }

        $severityFilter = $filters['severity'] ?? [];
        if (is_string($severityFilter)) {
            $severityFilter = array_filter(array_map('trim', explode(',', $severityFilter)));
        }
        if (is_array($severityFilter) && $severityFilter !== []) {
            $severity = [];
            foreach ($severityFilter as $value) {
                $value = strtolower((string) $value);
                if (in_array($value, self::ALLOWED_LEVELS, true)) {
                    $severity[] = $value;
                }
            }
            if ($severity !== []) {
                $placeholders = implode(', ', array_fill(0, count($severity), '%s'));
                $where[] = sprintf('severity IN (%s)', $placeholders);
                array_push($params, ...$severity);
            }
        }

        if (!empty($filters['context']) && is_string($filters['context'])) {
            $where[] = 'context LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['context']) . '%';
        }

        if (!empty($filters['module_slug']) && is_string($filters['module_slug'])) {
            $where[] = 'module_slug = %s';
            $params[] = substr($filters['module_slug'], 0, 190);
        }

        if (!empty($filters['message']) && is_string($filters['message'])) {
            $where[] = 'message LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['message']) . '%';
        }

        if (!empty($filters['since'])) {
            $from = self::normalizeDate($filters['since']);
            if ($from !== null) {
                $where[] = 'logged_at >= %s';
                $params[] = $from;
            }
        }

        if (!empty($filters['until'])) {
            $until = self::normalizeDate($filters['until'], true);
            if ($until !== null) {
                $where[] = 'logged_at <= %s';
                $params[] = $until;
            }
        }

        $table = self::tableName($wpdb);
        $sql = "SELECT id, logged_at, severity, context, message, payload, tenant_id, module_slug FROM {$table}";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY logged_at DESC, id DESC LIMIT %d';
        $params[] = $limit;

        $prepared = $wpdb->prepare($sql, $params);
        $rows = $wpdb->get_results($prepared, ARRAY_A) ?: [];

        return array_map(static function (array $row): array {
            $decoded = null;
            if (!empty($row['payload'])) {
                $json = json_decode($row['payload'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decoded = $json;
                }
            }

            return [
                'id'         => isset($row['id']) ? (int) $row['id'] : null,
                'logged_at'  => $row['logged_at'] ?? '',
                'severity'   => $row['severity'] ?? '',
                'context'    => $row['context'] ?? '',
                'message'    => $row['message'] ?? '',
                'payload'    => $decoded,
                'payload_raw'=> $row['payload'] ?? null,
                'tenant_id'  => isset($row['tenant_id']) ? (int) $row['tenant_id'] : null,
                'module_slug'=> $row['module_slug'] ?? null,
            ];
        }, $rows);
    }

    private static function ensureTableExists(): bool
    {
        if (self::$tableExists !== null) {
            return self::$tableExists;
        }

        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            self::$tableExists = false;
            return false;
        }

        $table = self::tableName($wpdb);
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        self::$tableExists = ($found === $table);
        return self::$tableExists;
    }

    private static function insertRecord(array $record): void
    {
        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return;
        }

        try {
            $wpdb->insert(
                self::tableName($wpdb),
                $record,
                ['%s', '%s', '%s', '%s', '%s', '%d', '%s']
            );
        } catch (\Throwable $exception) {
            error_log('[Bookando][LOGGER] ' . $exception->getMessage());
        }
    }

    private static function tableName(wpdb $wpdb): string
    {
        return $wpdb->prefix . 'bookando_activity_log';
    }

    private static function normalizeDate($date, bool $endOfDay = false): ?string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        if (!is_string($date) || $date === '') {
            return null;
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        $timezoneString = function_exists('wp_timezone_string') ? wp_timezone_string() : 'UTC';
        $timezone = function_exists('wp_timezone')
            ? wp_timezone()
            : new \DateTimeZone($timezoneString ?: 'UTC');
        $dateTime = (new \DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);

        if ($endOfDay) {
            $dateTime = $dateTime->setTime(23, 59, 59);
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * Bereinigt alte Log-Einträge die älter als X Tage sind.
     *
     * Diese Methode wird per WP-Cron täglich aufgerufen.
     * Löscht maximal 1000 Einträge pro Durchlauf, um DB-Locks zu vermeiden.
     *
     * @param int $days Anzahl Tage nach denen Logs gelöscht werden (Standard: 90)
     * @return int Anzahl gelöschter Einträge
     */
    public static function cleanupOldLogs(int $days = 90): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_activity_log';

        // Tabelle existiert?
        if (!self::ensureTableExists()) {
            return 0;
        }

        // Cutoff-Datum berechnen
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        // Lösche in Batches von 1000 Einträgen (verhindert lange Locks)
        $deleted_total = 0;
        $batch_size = 1000;
        $max_iterations = 100; // Sicherheitsnetz gegen Endlosschleifen

        for ($i = 0; $i < $max_iterations; $i++) {
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM `{$table}` WHERE logged_at < %s LIMIT %d",
                $cutoff,
                $batch_size
            ));

            if ($deleted === false) {
                // DB-Fehler
                self::error('core.log_cleanup', 'Log-Cleanup fehlgeschlagen', [
                    'error' => $wpdb->last_error,
                    'iteration' => $i
                ]);
                break;
            }

            $deleted_total += (int) $deleted;

            // Wenn weniger als $batch_size gelöscht wurden, sind wir fertig
            if ($deleted < $batch_size) {
                break;
            }

            // Kurze Pause zwischen Batches
            if ($i < $max_iterations - 1) {
                usleep(100000); // 100ms
            }
        }

        if ($deleted_total > 0) {
            self::info('core.log_cleanup', 'Log-Cleanup durchgeführt', [
                'deleted_count' => $deleted_total,
                'cutoff_date' => $cutoff,
                'retention_days' => $days
            ]);
        }

        return $deleted_total;
    }

    /**
     * Gibt Statistiken über die Log-Tabelle zurück.
     *
     * @return array{total: int, by_severity: array, oldest: string|null, newest: string|null}
     */
    public static function getLogStats(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_activity_log';

        if (!self::ensureTableExists()) {
            return [
                'total' => 0,
                'by_severity' => [],
                'oldest' => null,
                'newest' => null
            ];
        }

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");

        $by_severity = [];
        $rows = $wpdb->get_results("SELECT severity, COUNT(*) as count FROM `{$table}` GROUP BY severity");
        foreach ($rows as $row) {
            $by_severity[$row->severity] = (int) $row->count;
        }

        $oldest = $wpdb->get_var("SELECT logged_at FROM `{$table}` ORDER BY logged_at ASC LIMIT 1");
        $newest = $wpdb->get_var("SELECT logged_at FROM `{$table}` ORDER BY logged_at DESC LIMIT 1");

        return [
            'total' => $total,
            'by_severity' => $by_severity,
            'oldest' => $oldest,
            'newest' => $newest
        ];
    }
}
