<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Notification\NotificationChannel;
use SoftwareFoundation\Kernel\Domain\Notification\NotificationPreference;
use SoftwareFoundation\Kernel\Ports\NotificationPort;

/**
 * In-memory notification recorder for testing. Records all sent notifications.
 */
final class InMemoryNotification implements NotificationPort
{
    /** @var array<int, array{tenantId: int, userId: string, templateKey: string, data: array, channel: ?NotificationChannel}> */
    private array $sent = [];

    /** @var array<int, array{tenantId: int, channel: NotificationChannel, recipient: string, subject: string, body: string}> */
    private array $directSent = [];

    /** @var array<string, NotificationPreference[]> "tenantId:userId" => preferences */
    private array $preferences = [];

    public function send(
        int $tenantId,
        string $userId,
        string $templateKey,
        array $data = [],
        ?NotificationChannel $channel = null,
    ): bool {
        $this->sent[] = [
            'tenantId' => $tenantId,
            'userId' => $userId,
            'templateKey' => $templateKey,
            'data' => $data,
            'channel' => $channel,
        ];

        return true;
    }

    public function sendToChannel(
        int $tenantId,
        NotificationChannel $channel,
        string $recipient,
        string $subject,
        string $body,
    ): bool {
        $this->directSent[] = [
            'tenantId' => $tenantId,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
        ];

        return true;
    }

    /**
     * @return NotificationPreference[]
     */
    public function getPreferences(int $tenantId, string $userId): array
    {
        return $this->preferences["{$tenantId}:{$userId}"] ?? [];
    }

    public function updatePreference(
        int $tenantId,
        string $userId,
        NotificationChannel $channel,
        bool $enabled,
        array $eventTypes = [],
    ): void {
        $key = "{$tenantId}:{$userId}";
        $existing = $this->preferences[$key] ?? [];

        // Replace existing preference for this channel, or add new one.
        $updated = [];
        $found = false;

        foreach ($existing as $pref) {
            if ($pref->channel === $channel) {
                $updated[] = new NotificationPreference($userId, $channel, $enabled, $eventTypes);
                $found = true;
            } else {
                $updated[] = $pref;
            }
        }

        if (!$found) {
            $updated[] = new NotificationPreference($userId, $channel, $enabled, $eventTypes);
        }

        $this->preferences[$key] = $updated;
    }

    // --- Test helpers ---

    /** @return array[] All template-based notifications sent via send(). */
    public function sentNotifications(): array
    {
        return $this->sent;
    }

    /** Total number of template-based notifications sent. */
    public function sentCount(): int
    {
        return count($this->sent);
    }

    /** @return array[] All direct channel notifications sent via sendToChannel(). */
    public function directSentNotifications(): array
    {
        return $this->directSent;
    }

    /** Reset all recorded notifications and preferences. */
    public function reset(): void
    {
        $this->sent = [];
        $this->directSent = [];
        $this->preferences = [];
    }
}
