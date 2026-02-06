<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Webhook;

final class WebhookDelivery
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $eventType,
        public readonly array $payload,
        public readonly ?int $responseCode,
        public readonly bool $success,
        public readonly int $attempt,
        public readonly \DateTimeImmutable $deliveredAt,
    ) {
    }

    public function shouldRetry(int $maxAttempts = 5): bool
    {
        return !$this->success && $this->attempt < $maxAttempts;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subscriptionId' => $this->subscriptionId,
            'eventType' => $this->eventType,
            'payload' => $this->payload,
            'responseCode' => $this->responseCode,
            'success' => $this->success,
            'attempt' => $this->attempt,
            'deliveredAt' => $this->deliveredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
