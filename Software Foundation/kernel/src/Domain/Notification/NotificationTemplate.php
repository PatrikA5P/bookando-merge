<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Notification;

use InvalidArgumentException;

final class NotificationTemplate
{
    public function __construct(
        public readonly string $key,
        public readonly NotificationChannel $channel,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $locale,
    ) {
        if (!preg_match('/^[a-z]+(\.[a-z]+)*$/', $this->key)) {
            throw new InvalidArgumentException(
                sprintf('Template key must be lowercase with dots (e.g. "booking.confirmed"), got "%s".', $this->key)
            );
        }
    }

    public static function of(
        string $key,
        NotificationChannel $channel,
        string $subject,
        string $body,
        string $locale = 'de-CH',
    ): self {
        return new self(
            key: $key,
            channel: $channel,
            subject: $subject,
            body: $body,
            locale: $locale,
        );
    }

    /**
     * @return array{key: string, channel: string, subject: string, body: string, locale: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'channel' => $this->channel->value,
            'subject' => $this->subject,
            'body' => $this->body,
            'locale' => $this->locale,
        ];
    }
}
