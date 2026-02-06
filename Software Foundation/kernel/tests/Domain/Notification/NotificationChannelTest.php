<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Notification;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Notification\NotificationChannel;

final class NotificationChannelTest extends TestCase
{
    public function test_all_channels_exist(): void
    {
        $this->assertSame('email', NotificationChannel::EMAIL->value);
        $this->assertSame('sms', NotificationChannel::SMS->value);
        $this->assertSame('push', NotificationChannel::PUSH->value);
        $this->assertSame('in_app', NotificationChannel::IN_APP->value);
        $this->assertSame('webhook', NotificationChannel::WEBHOOK->value);
    }

    public function test_from_string(): void
    {
        $this->assertSame(NotificationChannel::EMAIL, NotificationChannel::from('email'));
        $this->assertSame(NotificationChannel::SMS, NotificationChannel::from('sms'));
        $this->assertSame(NotificationChannel::PUSH, NotificationChannel::from('push'));
        $this->assertSame(NotificationChannel::IN_APP, NotificationChannel::from('in_app'));
        $this->assertSame(NotificationChannel::WEBHOOK, NotificationChannel::from('webhook'));
    }
}
