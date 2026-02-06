<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Security\EncryptedField;
use SoftwareFoundation\Kernel\Ports\KeyManagementPort;

/**
 * In-memory key management for testing. Uses base64 encoding as fake encryption.
 *
 * Keys are random strings generated per tenant per version. "Encryption" is
 * base64-encoding prefixed with the key version, which is sufficient for
 * verifying encrypt/decrypt round-trips and crypto-shredding behaviour.
 */
final class InMemoryKeyManagement implements KeyManagementPort
{
    /** @var array<string, string> "tenantId:version" => key material */
    private array $keys = [];

    /** @var array<int, int> tenantId => current version */
    private array $versions = [];

    public function encrypt(string $plaintext, int $tenantId): EncryptedField
    {
        $version = $this->currentKeyVersion($tenantId);
        $key = $this->resolveKey($tenantId, $version);

        $ciphertext = base64_encode($key . '::' . $plaintext);

        return new EncryptedField($ciphertext, $version, 'test-base64');
    }

    public function decrypt(EncryptedField $field, int $tenantId): string
    {
        $version = $field->keyVersion();
        $key = $this->resolveKey($tenantId, $version);

        $decoded = base64_decode($field->ciphertext(), true);
        if ($decoded === false) {
            throw new \RuntimeException('Failed to decode ciphertext.');
        }

        $prefix = $key . '::';
        if (!str_starts_with($decoded, $prefix)) {
            throw new \RuntimeException(
                "Decryption failed: key mismatch for tenant {$tenantId} version {$version}."
            );
        }

        return substr($decoded, strlen($prefix));
    }

    public function rotateKey(int $tenantId): int
    {
        $newVersion = ($this->versions[$tenantId] ?? 0) + 1;
        $this->versions[$tenantId] = $newVersion;
        $this->keys["{$tenantId}:{$newVersion}"] = bin2hex(random_bytes(16));

        return $newVersion;
    }

    public function destroyKey(int $tenantId, int $keyVersion): void
    {
        $key = "{$tenantId}:{$keyVersion}";
        unset($this->keys[$key]);
    }

    public function currentKeyVersion(int $tenantId): int
    {
        if (!isset($this->versions[$tenantId])) {
            // Auto-initialize with version 1.
            $this->rotateKey($tenantId);
        }

        return $this->versions[$tenantId];
    }

    // --- Test helpers ---

    /** Check whether a specific key version exists (not destroyed). */
    public function hasKey(int $tenantId, int $keyVersion): bool
    {
        return isset($this->keys["{$tenantId}:{$keyVersion}"]);
    }

    private function resolveKey(int $tenantId, int $version): string
    {
        $key = "{$tenantId}:{$version}";

        if (!isset($this->keys[$key])) {
            throw new \RuntimeException(
                "Key version {$version} for tenant {$tenantId} has been destroyed (crypto-shredded)."
            );
        }

        return $this->keys[$key];
    }
}
