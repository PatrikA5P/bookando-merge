<?php

declare(strict_types=1);

namespace Bookando\Core\Queue;

use Bookando\Core\Service\ActivityLogger;

/**
 * Queue Manager
 *
 * Lightweight async job queue system for WordPress.
 * Uses database-backed queue with WP-Cron processing.
 *
 * Features:
 * - Priority-based job scheduling
 * - Retry logic with exponential backoff
 * - Dead letter queue for failed jobs
 * - Batch processing support
 * - Job deduplication
 *
 * @package Bookando\Core\Queue
 */
class QueueManager
{
    /**
     * Queue table name (without prefix)
     */
    private const TABLE_NAME = 'queue_jobs';

    /**
     * Job statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEAD = 'dead';

    /**
     * Job priorities (lower number = higher priority)
     */
    public const PRIORITY_HIGH = 1;
    public const PRIORITY_NORMAL = 5;
    public const PRIORITY_LOW = 10;

    /**
     * Max retry attempts before moving to dead letter queue
     */
    private const MAX_RETRIES = 3;

    /**
     * Enqueue a new job
     *
     * @param string $job_class Fully qualified job class name
     * @param array<string, mixed> $payload Job data
     * @param int $priority Job priority (1-10, lower = higher priority)
     * @param string|null $unique_key Unique identifier to prevent duplicates
     * @return int|false Job ID or false on failure
     */
    public static function enqueue(
        string $job_class,
        array $payload = [],
        int $priority = self::PRIORITY_NORMAL,
        ?string $unique_key = null
    ) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_NAME;

        // Check for duplicate if unique_key provided
        if ($unique_key !== null) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE unique_key = %s AND status IN (%s, %s)",
                $unique_key,
                self::STATUS_PENDING,
                self::STATUS_PROCESSING
            ));

            if ($exists) {
                ActivityLogger::info('queue.duplicate', 'Job already queued', [
                    'unique_key' => $unique_key,
                    'existing_id' => $exists,
                ]);
                return false;
            }
        }

        // Insert job
        $result = $wpdb->insert(
            $table,
            [
                'job_class' => $job_class,
                'payload' => wp_json_encode($payload),
                'status' => self::STATUS_PENDING,
                'priority' => max(1, min(10, $priority)),
                'attempts' => 0,
                'unique_key' => $unique_key,
                'created_at' => current_time('mysql'),
                'available_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );

        if ($result === false) {
            ActivityLogger::error('queue.enqueue_failed', 'Failed to enqueue job', [
                'job_class' => $job_class,
                'error' => $wpdb->last_error,
            ]);
            return false;
        }

        $job_id = $wpdb->insert_id;

        ActivityLogger::info('queue.enqueued', 'Job enqueued', [
            'job_id' => $job_id,
            'job_class' => $job_class,
            'priority' => $priority,
        ]);

        return $job_id;
    }

    /**
     * Enqueue a delayed job (available after specified time)
     *
     * @param string $job_class Job class name
     * @param array<string, mixed> $payload Job data
     * @param int $delay_seconds Delay in seconds
     * @param int $priority Job priority
     * @return int|false Job ID or false
     */
    public static function enqueueDelayed(
        string $job_class,
        array $payload = [],
        int $delay_seconds = 60,
        int $priority = self::PRIORITY_NORMAL
    ) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_NAME;
        $available_at = gmdate('Y-m-d H:i:s', time() + $delay_seconds);

        $result = $wpdb->insert(
            $table,
            [
                'job_class' => $job_class,
                'payload' => wp_json_encode($payload),
                'status' => self::STATUS_PENDING,
                'priority' => max(1, min(10, $priority)),
                'attempts' => 0,
                'created_at' => current_time('mysql'),
                'available_at' => $available_at,
            ],
            ['%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Process jobs from the queue
     *
     * @param int $batch_size Number of jobs to process in one batch
     * @return int Number of jobs processed
     */
    public static function process(int $batch_size = 10): int
    {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_NAME;
        $processed = 0;

        // Get pending jobs (sorted by priority, then created_at)
        $jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE status = %s
            AND available_at <= %s
            ORDER BY priority ASC, created_at ASC
            LIMIT %d",
            self::STATUS_PENDING,
            current_time('mysql'),
            $batch_size
        ));

        foreach ($jobs as $job) {
            if (self::processJob($job)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Process a single job
     *
     * @param object $job Job data from database
     * @return bool Success
     */
    private static function processJob(object $job): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_NAME;

        // Mark as processing
        $wpdb->update(
            $table,
            [
                'status' => self::STATUS_PROCESSING,
                'started_at' => current_time('mysql'),
            ],
            ['id' => $job->id],
            ['%s', '%s'],
            ['%d']
        );

        try {
            // Instantiate and execute job
            $job_class = $job->job_class;

            if (!class_exists($job_class)) {
                throw new \Exception("Job class not found: {$job_class}");
            }

            $job_instance = new $job_class();

            if (!method_exists($job_instance, 'handle')) {
                throw new \Exception("Job class must have handle() method: {$job_class}");
            }

            $payload = json_decode($job->payload, true);
            $job_instance->handle($payload);

            // Mark as completed
            $wpdb->update(
                $table,
                [
                    'status' => self::STATUS_COMPLETED,
                    'completed_at' => current_time('mysql'),
                ],
                ['id' => $job->id],
                ['%s', '%s'],
                ['%d']
            );

            ActivityLogger::info('queue.job_completed', 'Job completed successfully', [
                'job_id' => $job->id,
                'job_class' => $job_class,
            ]);

            return true;
        } catch (\Throwable $e) {
            // Handle failure
            $attempts = (int) $job->attempts + 1;

            ActivityLogger::error('queue.job_failed', 'Job failed', [
                'job_id' => $job->id,
                'job_class' => $job->job_class,
                'attempt' => $attempts,
                'error' => $e->getMessage(),
            ]);

            if ($attempts >= self::MAX_RETRIES) {
                // Move to dead letter queue
                $wpdb->update(
                    $table,
                    [
                        'status' => self::STATUS_DEAD,
                        'attempts' => $attempts,
                        'failed_at' => current_time('mysql'),
                        'error_message' => $e->getMessage(),
                    ],
                    ['id' => $job->id],
                    ['%s', '%d', '%s', '%s'],
                    ['%d']
                );
            } else {
                // Retry with exponential backoff
                $retry_delay = self::calculateRetryDelay($attempts);
                $available_at = gmdate('Y-m-d H:i:s', time() + $retry_delay);

                $wpdb->update(
                    $table,
                    [
                        'status' => self::STATUS_PENDING,
                        'attempts' => $attempts,
                        'available_at' => $available_at,
                        'error_message' => $e->getMessage(),
                    ],
                    ['id' => $job->id],
                    ['%s', '%d', '%s', '%s'],
                    ['%d']
                );
            }

            return false;
        }
    }

    /**
     * Calculate retry delay with exponential backoff
     *
     * @param int $attempt Attempt number
     * @return int Delay in seconds
     */
    private static function calculateRetryDelay(int $attempt): int
    {
        // Exponential backoff: 2^attempt * 60 seconds
        // Attempt 1: 2 minutes
        // Attempt 2: 4 minutes
        // Attempt 3: 8 minutes
        return (int) pow(2, $attempt) * 60;
    }

    /**
     * Get queue statistics
     *
     * @return array<string, int> Queue statistics
     */
    public static function getStats(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_NAME;

        return [
            'pending' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE status = %s",
                self::STATUS_PENDING
            )),
            'processing' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE status = %s",
                self::STATUS_PROCESSING
            )),
            'completed' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE status = %s",
                self::STATUS_COMPLETED
            )),
            'failed' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE status = %s",
                self::STATUS_FAILED
            )),
            'dead' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE status = %s",
                self::STATUS_DEAD
            )),
        ];
    }

    /**
     * Cleanup completed jobs older than specified days
     *
     * @param int $days Number of days to keep completed jobs
     * @return int Number of jobs deleted
     */
    public static function cleanup(int $days = 7): int
    {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_NAME;
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table}
            WHERE status = %s
            AND completed_at < %s",
            self::STATUS_COMPLETED,
            $cutoff
        ));

        if ($deleted > 0) {
            ActivityLogger::info('queue.cleanup', 'Old jobs cleaned up', [
                'deleted' => $deleted,
                'older_than_days' => $days,
            ]);
        }

        return (int) $deleted;
    }

    /**
     * Retry dead jobs
     *
     * @param int $job_id Specific job ID to retry (optional)
     * @return bool Success
     */
    public static function retry(int $job_id): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->update(
            $table,
            [
                'status' => self::STATUS_PENDING,
                'attempts' => 0,
                'available_at' => current_time('mysql'),
                'error_message' => null,
            ],
            ['id' => $job_id],
            ['%s', '%d', '%s', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Register WP-Cron schedule for queue processing
     *
     * @return void
     */
    public static function registerCron(): void
    {
        // Schedule queue processing every minute
        if (!wp_next_scheduled('bookando_queue_process')) {
            wp_schedule_event(time(), 'bookando_queue_interval', 'bookando_queue_process');
        }

        // Schedule cleanup daily
        if (!wp_next_scheduled('bookando_queue_cleanup')) {
            wp_schedule_event(time(), 'daily', 'bookando_queue_cleanup');
        }
    }

    /**
     * Add custom cron interval (1 minute)
     *
     * @param array<string, array<string, mixed>> $schedules Existing schedules
     * @return array<string, array<string, mixed>> Modified schedules
     */
    public static function addCronInterval(array $schedules): array
    {
        $schedules['bookando_queue_interval'] = [
            'interval' => 60, // 1 minute
            'display' => __('Every Minute (Bookando Queue)', 'bookando'),
        ];

        return $schedules;
    }
}
