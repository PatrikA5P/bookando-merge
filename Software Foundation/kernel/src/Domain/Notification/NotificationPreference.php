<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Notification;

use InvalidArgumentException;

final class NotificationPreference
{
    /**
     * @param string   $userId     UUID of the user
     * @param NotificationChannel $channel
     * @param bool     $enabled
     * @param string[] $eventTypes List of event types the user wants notifications for
     */
    public function __construct(
        public readonly string $userId,
        public readonly NotificationChannel $channel,
        public readonly bool $enabled,
        public readonly array $eventTypes,
    ) {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $this->userId)) {
            throw new InvalidArgumentException(
                sprintf('userId must be a valid UUID, got "%s".', $this->userId)
            );
        }
    }

    public function isEnabledFor(string $eventType): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return in_array($eventType, $this->eventTypes, true);
    }

    /**
     * @return array{userId: string, channel: string, enabled: bool, eventTypes: string[]}
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'channel' => $this->channel->value,
            'enabled' => $this->enabled,
            'eventTypes' => $this->eventTypes,
        ];
    }
}
