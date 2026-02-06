<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Security\EncryptedField;

/**
 * Port for tenant-scoped encryption key management.
 *
 * Manages encryption keys per tenant, supporting key rotation and
 * crypto-shredding (permanent key destruction to render data unreadable).
 *
 * Crypto-shredding is the preferred deletion strategy for encrypted personal
 * data under DSGVO Art. 17 (right to erasure) — destroying the key makes
 * the ciphertext irrecoverable without physically deleting records.
 */
interface KeyManagementPort
{
    /**
     * Encrypt plaintext using the tenant's current key version.
     *
     * @param string $plaintext The data to encrypt.
     * @param int    $tenantId  Tenant identifier.
     *
     * @return EncryptedField The encrypted value with key version metadata.
     */
    public function encrypt(string $plaintext, int $tenantId): EncryptedField;

    /**
     * Decrypt an encrypted field using the key version stored in the field.
     *
     * @param EncryptedField $field    The encrypted value to decrypt.
     * @param int            $tenantId Tenant identifier.
     *
     * @return string The decrypted plaintext.
     *
     * @throws \RuntimeException If the key version has been destroyed (crypto-shredded).
     */
    public function decrypt(EncryptedField $field, int $tenantId): string;

    /**
     * Rotate the encryption key for a tenant.
     *
     * Creates a new key version. Existing data encrypted with older versions
     * remains readable until those versions are explicitly destroyed.
     *
     * @param int $tenantId Tenant identifier.
     *
     * @return int The new key version number.
     */
    public function rotateKey(int $tenantId): int;

    /**
     * Permanently destroy an encryption key (crypto-shredding).
     *
     * After destruction, any EncryptedField using this key version becomes
     * permanently unreadable. This operation is irreversible.
     *
     * @param int $tenantId   Tenant identifier.
     * @param int $keyVersion The key version to destroy.
     */
    public function destroyKey(int $tenantId, int $keyVersion): void;

    /**
     * Return the current (latest) key version for a tenant.
     *
     * @param int $tenantId Tenant identifier.
     *
     * @return int Current key version number.
     */
    public function currentKeyVersion(int $tenantId): int;
}
