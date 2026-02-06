<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Notification\NotificationChannel;
use SoftwareFoundation\Kernel\Domain\Notification\NotificationPreference;

interface NotificationPort
{
    /**
     * Send a notification using a template.
     *
     * @param int                      $tenantId    Tenant identifier
     * @param string                   $userId      UUID of the recipient user
     * @param string                   $templateKey Template key (e.g. 'booking.confirmed')
     * @param array                    $data        Template data for interpolation
     * @param NotificationChannel|null $channel     Specific channel, or null for default
     * @return bool True if sent successfully
     */
    public function send(
        int $tenantId,
        string $userId,
        string $templateKey,
        array $data = [],
        ?NotificationChannel $channel = null,
    ): bool;

    /**
     * Send a notification directly to a channel without a template.
     *
     * @param int                 $tenantId  Tenant identifier
     * @param NotificationChannel $channel   Channel to send to
     * @param string              $recipient Recipient address (email, phone, etc.)
     * @param string              $subject   Notification subject
     * @param string              $body      Notification body
     * @return bool True if sent successfully
     */
    public function sendToChannel(
        int $tenantId,
        NotificationChannel $channel,
        string $recipient,
        string $subject,
        string $body,
    ): bool;

    /**
     * Get notification preferences for a user.
     *
     * @param int    $tenantId Tenant identifier
     * @param string $userId   UUID of the user
     * @return NotificationPreference[]
     */
    public function getPreferences(int $tenantId, string $userId): array;

    /**
     * Update a notification preference for a user.
     *
     * @param int                 $tenantId   Tenant identifier
     * @param string              $userId     UUID of the user
     * @param NotificationChannel $channel    Channel to update preference for
     * @param bool                $enabled    Whether the channel is enabled
     * @param string[]            $eventTypes Event types the user wants notifications for
     */
    public function updatePreference(
        int $tenantId,
        string $userId,
        NotificationChannel $channel,
        bool $enabled,
        array $eventTypes = [],
    ): void;
}
