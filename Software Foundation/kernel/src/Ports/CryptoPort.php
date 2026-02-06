<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Cryptography port.
 *
 * Provides a host-agnostic interface for common cryptographic operations
 * including password hashing, HMAC signing/verification, symmetric
 * encryption/decryption, secure random string generation, and UUID creation.
 *
 * Implementations SHOULD use industry-standard algorithms (bcrypt/argon2 for
 * passwords, HMAC-SHA256 for signatures, AES-256-GCM for encryption) and
 * MUST use cryptographically secure random sources.
 */
interface CryptoPort
{
    /**
     * Hash a plaintext password for storage.
     *
     * @param string $password The plaintext password.
     *
     * @return string The hashed password string.
     */
    public function hashPassword(string $password): string;

    /**
     * Verify a plaintext password against a stored hash.
     *
     * @param string $password The plaintext password to verify.
     * @param string $hash     The stored hash to compare against.
     *
     * @return bool True if the password matches the hash.
     */
    public function verifyPassword(string $password, string $hash): bool;

    /**
     * Compute an HMAC signature for the given data.
     *
     * @param string $data The data to sign.
     * @param string $key  The secret key.
     *
     * @return string The hex-encoded HMAC signature.
     */
    public function hmac(string $data, string $key): string;

    /**
     * Verify an HMAC signature in constant time.
     *
     * @param string $data      The original data.
     * @param string $signature The HMAC signature to verify.
     * @param string $key       The secret key used to produce the signature.
     *
     * @return bool True if the signature is valid.
     */
    public function verifyHmac(string $data, string $signature, string $key): bool;

    /**
     * Encrypt plaintext using symmetric encryption.
     *
     * Implementations SHOULD use AES-256-GCM or an equivalent authenticated
     * encryption scheme. The returned ciphertext includes any IV/nonce and
     * authentication tag needed for decryption.
     *
     * @param string $plaintext The data to encrypt.
     * @param string $key       The encryption key.
     *
     * @return string The encrypted ciphertext (base64 or hex encoded).
     */
    public function encrypt(string $plaintext, string $key): string;

    /**
     * Decrypt ciphertext produced by {@see encrypt()}.
     *
     * @param string $ciphertext The encrypted data.
     * @param string $key        The encryption key used during encryption.
     *
     * @return string The decrypted plaintext.
     *
     * @throws \RuntimeException If decryption fails (wrong key, tampered data, etc.).
     */
    public function decrypt(string $ciphertext, string $key): string;

    /**
     * Generate a cryptographically secure random string.
     *
     * The returned string contains only URL-safe characters (alphanumeric,
     * hyphens, underscores).
     *
     * @param int $length Desired string length. Default 32.
     *
     * @return string The random string.
     */
    public function randomString(int $length = 32): string;

    /**
     * Generate a new UUID (v4).
     *
     * @return string A UUID string in the standard 8-4-4-4-12 format.
     */
    public function uuid(): string;
}
