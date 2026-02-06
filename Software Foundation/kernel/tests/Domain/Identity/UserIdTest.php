<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Identity;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Identity\UserId;
use SoftwareFoundation\Kernel\Domain\Shared\EntityId;

final class UserIdTest extends TestCase
{
    public function test_generate(): void
    {
        $id = UserId::generate();
        $this->assertNotEmpty($id->value());
        $this->assertTrue(EntityId::isValid($id->value()));
    }

    public function test_from_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = UserId::fromString($uuid);
        $this->assertSame($uuid, $id->value());
    }

    public function test_equals(): void
    {
        $a = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $b = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $c = UserId::generate();

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = UserId::fromString($uuid);
        $this->assertSame($uuid, (string) $id);
    }
}
