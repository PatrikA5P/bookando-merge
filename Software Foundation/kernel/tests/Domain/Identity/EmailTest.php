<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Identity;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Identity\Email;

final class EmailTest extends TestCase
{
    public function test_creates_valid_email(): void
    {
        $email = Email::of('user@example.com');
        $this->assertSame('user@example.com', $email->value());
    }

    public function test_normalizes_to_lowercase(): void
    {
        $email = Email::of('USER@Example.COM');
        $this->assertSame('user@example.com', $email->value());
    }

    public function test_trims_whitespace(): void
    {
        $email = Email::of('  user@example.com  ');
        $this->assertSame('user@example.com', $email->value());
    }

    public function test_rejects_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Email::of('not-an-email');
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Email::of('');
    }

    public function test_domain(): void
    {
        $email = Email::of('admin@bookando.io');
        $this->assertSame('bookando.io', $email->domain());
    }

    public function test_equals(): void
    {
        $a = Email::of('user@example.com');
        $b = Email::of('USER@example.com');
        $c = Email::of('other@example.com');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $email = Email::of('user@example.com');
        $this->assertSame('user@example.com', (string) $email);
    }
}
