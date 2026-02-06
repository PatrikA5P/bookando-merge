<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Shared;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Shared\EntityId;

final class EntityIdTest extends TestCase
{
    public function test_generate_creates_valid_uuid_v4(): void
    {
        $id = EntityId::generate();
        $this->assertTrue(EntityId::isValid($id->value()));
    }

    public function test_generated_ids_are_unique(): void
    {
        $a = EntityId::generate();
        $b = EntityId::generate();
        $this->assertFalse($a->equals($b));
    }

    public function test_from_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = EntityId::fromString($uuid);
        $this->assertSame($uuid, $id->value());
    }

    public function test_normalizes_to_lowercase(): void
    {
        $upper = '550E8400-E29B-41D4-A716-446655440000';
        $id = EntityId::fromString($upper);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $id->value());
    }

    public function test_rejects_invalid_uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        EntityId::fromString('not-a-uuid');
    }

    public function test_rejects_uuid_v1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // UUID v1 has version digit '1' instead of '4'
        EntityId::fromString('550e8400-e29b-11d4-a716-446655440000');
    }

    public function test_equals(): void
    {
        $a = EntityId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $b = EntityId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $c = EntityId::generate();

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = EntityId::fromString($uuid);
        $this->assertSame($uuid, (string) $id);
    }
}
