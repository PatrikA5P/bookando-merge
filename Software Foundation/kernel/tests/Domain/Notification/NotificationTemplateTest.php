<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Notification;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Notification\NotificationChannel;
use SoftwareFoundation\Kernel\Domain\Notification\NotificationTemplate;

final class NotificationTemplateTest extends TestCase
{
    public function testCreation(): void
    {
        $tpl = NotificationTemplate::of(
            key: 'booking.confirmed',
            channel: NotificationChannel::EMAIL,
            subject: 'Buchung bestätigt',
            body: 'Ihre Buchung {{booking_id}} wurde bestätigt.',
            locale: 'de-CH',
        );

        self::assertSame('booking.confirmed', $tpl->key);
        self::assertSame(NotificationChannel::EMAIL, $tpl->channel);
        self::assertSame('Buchung bestätigt', $tpl->subject);
        self::assertSame('de-CH', $tpl->locale);
    }

    public function testInvalidKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NotificationTemplate(
            key: 'Invalid-Key!',
            channel: NotificationChannel::EMAIL,
            subject: 'Test',
            body: 'Test body',
            locale: 'en',
        );
    }

    public function testDefaultLocale(): void
    {
        $tpl = NotificationTemplate::of(
            key: 'invoice.sent',
            channel: NotificationChannel::SMS,
            subject: 'Invoice',
            body: 'You have a new invoice.',
        );
        self::assertSame('de-CH', $tpl->locale);
    }

    public function testToArray(): void
    {
        $tpl = NotificationTemplate::of(
            key: 'user.welcome',
            channel: NotificationChannel::PUSH,
            subject: 'Welcome',
            body: 'Welcome aboard!',
            locale: 'en',
        );
        $arr = $tpl->toArray();
        self::assertSame('user.welcome', $arr['key']);
        self::assertSame('push', $arr['channel']);
        self::assertSame('Welcome', $arr['subject']);
        self::assertSame('en', $arr['locale']);
    }
}
