<?php

declare(strict_types=1);

namespace Bookando\Core\Queue\Jobs;

use Bookando\Core\Service\ActivityLogger;

/**
 * Example Job
 *
 * Example job class demonstrating the queue system usage.
 * All job classes must implement a handle() method that accepts the payload array.
 *
 * Usage:
 * ```php
 * use Bookando\Core\Queue\QueueManager;
 * use Bookando\Core\Queue\Jobs\ExampleJob;
 *
 * // Enqueue a job
 * $job_id = QueueManager::enqueue(
 *     ExampleJob::class,
 *     ['user_id' => 123, 'action' => 'send_email'],
 *     QueueManager::PRIORITY_NORMAL
 * );
 *
 * // Enqueue a delayed job (5 minutes)
 * $job_id = QueueManager::enqueueDelayed(
 *     ExampleJob::class,
 *     ['user_id' => 123],
 *     300 // 5 minutes
 * );
 * ```
 *
 * @package Bookando\Core\Queue\Jobs
 */
class ExampleJob
{
    /**
     * Handle the job
     *
     * This method is called by the queue worker when processing the job.
     *
     * @param array<string, mixed> $payload Job data
     * @return void
     * @throws \Exception On failure (will trigger retry)
     */
    public function handle(array $payload): void
    {
        $userId = $payload['user_id'] ?? null;
        $action = $payload['action'] ?? 'default';

        ActivityLogger::info('queue.example_job', 'Processing example job', [
            'user_id' => $userId,
            'action' => $action,
        ]);

        // Simulate work
        sleep(1);

        // Example: Send email, process data, call external API, etc.
        switch ($action) {
            case 'send_email':
                $this->sendEmail($userId);
                break;

            case 'generate_report':
                $this->generateReport($userId);
                break;

            default:
                ActivityLogger::warning('queue.example_job', 'Unknown action', [
                    'action' => $action,
                ]);
        }
    }

    /**
     * Example: Send email
     *
     * @param int|null $userId User ID
     * @return void
     */
    private function sendEmail(?int $userId): void
    {
        if (!$userId) {
            throw new \Exception('User ID required for send_email action');
        }

        // Send email logic here
        wp_mail(
            'user@example.com',
            'Example Email',
            'This is an example email sent from the queue.'
        );

        ActivityLogger::info('queue.email_sent', 'Email sent', ['user_id' => $userId]);
    }

    /**
     * Example: Generate report
     *
     * @param int|null $userId User ID
     * @return void
     */
    private function generateReport(?int $userId): void
    {
        if (!$userId) {
            throw new \Exception('User ID required for generate_report action');
        }

        // Report generation logic here
        ActivityLogger::info('queue.report_generated', 'Report generated', [
            'user_id' => $userId,
        ]);
    }
}
