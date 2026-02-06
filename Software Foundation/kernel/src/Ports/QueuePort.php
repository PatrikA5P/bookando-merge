<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Asynchronous job-queue port.
 *
 * Provides a host-agnostic interface for enqueuing and processing background
 * jobs. Implementations may use Action Scheduler (WordPress), Symfony
 * Messenger, a Redis-backed queue, or any other async mechanism available in
 * the host environment.
 *
 * IMPORTANT: The `$payload` array passed to {@see enqueue()} and
 * {@see enqueueDelayed()} **MUST** contain a `tenant_id` key so that every
 * job can be correctly scoped to its owning tenant at processing time. Failure
 * to include `tenant_id` in the payload will result in undefined behaviour.
 */
interface QueuePort
{
    /**
     * Enqueue a job for asynchronous processing.
     *
     * @param string      $jobClass  Fully-qualified class name of the job handler.
     * @param array       $payload   Job data. **MUST** contain a `tenant_id` key.
     * @param int         $priority  Priority level (1 = highest, 10 = lowest). Default 5.
     * @param string|null $uniqueKey Optional deduplication key. When provided, a job
     *                               with the same key that is still pending will not
     *                               be enqueued again.
     *
     * @return string A unique job identifier.
     */
    public function enqueue(
        string $jobClass,
        array $payload,
        int $priority = 5,
        ?string $uniqueKey = null,
    ): string;

    /**
     * Enqueue a job that should not be processed before the given delay.
     *
     * @param string $jobClass     Fully-qualified class name of the job handler.
     * @param array  $payload      Job data. **MUST** contain a `tenant_id` key.
     * @param int    $delaySeconds Number of seconds to wait before the job becomes eligible.
     * @param int    $priority     Priority level (1 = highest, 10 = lowest). Default 5.
     *
     * @return string A unique job identifier.
     */
    public function enqueueDelayed(
        string $jobClass,
        array $payload,
        int $delaySeconds,
        int $priority = 5,
    ): string;

    /**
     * Process a batch of pending jobs.
     *
     * Implementations should claim up to `$batchSize` jobs, execute them, and
     * mark them as completed or failed. This method is typically invoked by a
     * cron runner or worker loop.
     *
     * @param int $batchSize Maximum number of jobs to process in this invocation.
     *
     * @return int The number of jobs that were actually processed.
     */
    public function process(int $batchSize = 10): int;

    /**
     * Return queue statistics.
     *
     * The returned array SHOULD contain at least the following keys:
     *  - `pending`   (int) Number of jobs waiting to be processed.
     *  - `running`   (int) Number of jobs currently being processed.
     *  - `completed` (int) Total completed jobs.
     *  - `failed`    (int) Total failed jobs.
     *
     * @return array<string, mixed>
     */
    public function stats(): array;
}
