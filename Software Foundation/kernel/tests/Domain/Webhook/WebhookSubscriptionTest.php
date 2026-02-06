<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Webhook;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Webhook\WebhookSubscription;

final class WebhookSubscriptionTest extends TestCase
{
    private function make(
        string $url = 'https://example.com/hook',
        string $secret = 'sec_abc123',
        array $eventTypes = ['booking.created', 'booking.cancelled'],
        bool $active = true,
    ): WebhookSubscription {
        return new WebhookSubscription(
            id: 'sub_001',
            tenantId: 42,
            url: $url,
            secret: $secret,
            eventTypes: $eventTypes,
            active: $active,
            createdAt: new \DateTimeImmutable('2025-01-15T10:00:00+00:00'),
        );
    }

    public function testCreation(): void
    {
        $sub = $this->make();
        self::assertSame('sub_001', $sub->id);
        self::assertSame(42, $sub->tenantId);
        self::assertSame('https://example.com/hook', $sub->url);
        self::assertTrue($sub->active);
    }

    public function testUrlMustBeHttps(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->make(url: 'http://example.com/hook');
    }

    public function testSecretMustNotBeEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->make(secret: '');
    }

    public function testIsSubscribedTo(): void
    {
        $sub = $this->make();
        self::assertTrue($sub->isSubscribedTo('booking.created'));
        self::assertTrue($sub->isSubscribedTo('booking.cancelled'));
        self::assertFalse($sub->isSubscribedTo('invoice.paid'));
    }

    public function testToArray(): void
    {
        $sub = $this->make();
        $arr = $sub->toArray();
        self::assertSame('sub_001', $arr['id']);
        self::assertSame(42, $arr['tenantId']);
        self::assertSame('https://example.com/hook', $arr['url']);
        self::assertCount(2, $arr['eventTypes']);
        self::assertTrue($arr['active']);
    }
}
