<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Security;

/**
 * Immutable value object wrapping an encrypted value.
 *
 * This object carries the ciphertext together with the key version and
 * algorithm used for encryption. Decryption is performed exclusively
 * through the KeyManagementPort — this object never exposes plaintext.
 *
 * Supports crypto-shredding: when the key for a given version is destroyed,
 * all EncryptedField instances using that version become permanently unreadable.
 */
final class EncryptedField
{
    private string $ciphertext;
    private int $keyVersion;
    private string $algorithm;

    public function __construct(string $ciphertext, int $keyVersion, string $algorithm = 'aes-256-gcm')
    {
        $this->ciphertext = $ciphertext;
        $this->keyVersion = $keyVersion;
        $this->algorithm = $algorithm;
    }

    public static function of(string $ciphertext, int $keyVersion, string $algorithm = 'aes-256-gcm'): self
    {
        return new self($ciphertext, $keyVersion, $algorithm);
    }

    public function ciphertext(): string
    {
        return $this->ciphertext;
    }

    public function keyVersion(): int
    {
        return $this->keyVersion;
    }

    public function algorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * @return array{ciphertext: string, key_version: int, algorithm: string}
     */
    public function toArray(): array
    {
        return [
            'ciphertext' => $this->ciphertext,
            'key_version' => $this->keyVersion,
            'algorithm' => $this->algorithm,
        ];
    }

    /**
     * @param array{ciphertext: string, key_version: int, algorithm: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['ciphertext'],
            $data['key_version'],
            $data['algorithm'] ?? 'aes-256-gcm',
        );
    }
}
