<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Webhook;

final class WebhookSubscription
{
    /**
     * @param string[] $eventTypes
     */
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $url,
        public readonly string $secret,
        public readonly array $eventTypes,
        public readonly bool $active,
        public readonly \DateTimeImmutable $createdAt,
    ) {
        if (!str_starts_with($this->url, 'https://')) {
            throw new \InvalidArgumentException(
                sprintf('Webhook URL must start with "https://", got "%s".', $this->url)
            );
        }

        if ($this->secret === '') {
            throw new \InvalidArgumentException('Webhook secret must not be empty.');
        }
    }

    public function isSubscribedTo(string $eventType): bool
    {
        return in_array($eventType, $this->eventTypes, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'url' => $this->url,
            'secret' => $this->secret,
            'eventTypes' => $this->eventTypes,
            'active' => $this->active,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
