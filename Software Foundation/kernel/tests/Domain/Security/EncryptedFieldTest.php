<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Security;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Security\EncryptedField;

final class EncryptedFieldTest extends TestCase
{
    public function test_creates_field(): void
    {
        $field = EncryptedField::of('base64ciphertext==', 3, 'aes-256-gcm');

        $this->assertSame('base64ciphertext==', $field->ciphertext());
        $this->assertSame(3, $field->keyVersion());
        $this->assertSame('aes-256-gcm', $field->algorithm());
    }

    public function test_to_array_from_array_roundtrip(): void
    {
        $original = EncryptedField::of('encrypted-data', 5, 'aes-256-gcm');
        $restored = EncryptedField::fromArray($original->toArray());

        $this->assertSame($original->ciphertext(), $restored->ciphertext());
        $this->assertSame($original->keyVersion(), $restored->keyVersion());
        $this->assertSame($original->algorithm(), $restored->algorithm());
    }

    public function test_default_algorithm(): void
    {
        $field = new EncryptedField('ciphertext', 1);

        $this->assertSame('aes-256-gcm', $field->algorithm());
    }
}
