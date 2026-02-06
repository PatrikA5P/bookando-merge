<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Webhook;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Webhook\WebhookDelivery;

final class WebhookDeliveryTest extends TestCase
{
    private function make(
        bool $success = false,
        int $attempt = 1,
        ?int $responseCode = 500,
    ): WebhookDelivery {
        return new WebhookDelivery(
            subscriptionId: 'sub_001',
            eventType: 'booking.created',
            payload: ['booking_id' => 'b_123'],
            responseCode: $responseCode,
            success: $success,
            attempt: $attempt,
            deliveredAt: new \DateTimeImmutable('2025-01-15T10:00:00+00:00'),
        );
    }

    public function testSuccessfulDelivery(): void
    {
        $delivery = $this->make(success: true, attempt: 1, responseCode: 200);
        self::assertTrue($delivery->success);
        self::assertSame(200, $delivery->responseCode);
        self::assertFalse($delivery->shouldRetry());
    }

    public function testFailedDeliveryShouldRetry(): void
    {
        $delivery = $this->make(success: false, attempt: 2);
        self::assertTrue($delivery->shouldRetry());
        self::assertTrue($delivery->shouldRetry(5));
    }

    public function testMaxAttemptsReachedStopsRetry(): void
    {
        $delivery = $this->make(success: false, attempt: 5);
        self::assertFalse($delivery->shouldRetry(5));
    }

    public function testToArray(): void
    {
        $delivery = $this->make();
        $arr = $delivery->toArray();
        self::assertSame('sub_001', $arr['subscriptionId']);
        self::assertSame('booking.created', $arr['eventType']);
        self::assertSame(500, $arr['responseCode']);
        self::assertFalse($arr['success']);
        self::assertSame(1, $arr['attempt']);
    }
}
