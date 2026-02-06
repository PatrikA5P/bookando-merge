<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Integrity;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Integrity\HashChain;

final class HashChainTest extends TestCase
{
    // --- Genesis ---

    public function test_genesis_creates_first_entry(): void
    {
        $hash = HashChain::computeHash(str_repeat('0', 64), 'payload', '2026-01-15T10:00:00Z');

        $entry = HashChain::genesis(
            tenantId: 1,
            entryHash: $hash,
            createdAt: new \DateTimeImmutable('2026-01-15T10:00:00Z'),
        );

        $this->assertSame(1, $entry->sequenceNumber());
        $this->assertSame(str_repeat('0', 64), $entry->previousHash());
        $this->assertSame($hash, $entry->entryHash());
        $this->assertSame(1, $entry->tenantId());
    }

    // --- Hash computation ---

    public function test_compute_hash_is_deterministic(): void
    {
        $previousHash = str_repeat('0', 64);
        $payload = 'test-payload';
        $timestamp = '2026-01-15T10:00:00Z';

        $hash1 = HashChain::computeHash($previousHash, $payload, $timestamp);
        $hash2 = HashChain::computeHash($previousHash, $payload, $timestamp);

        $this->assertSame($hash1, $hash2);
        $this->assertSame(64, strlen($hash1));
    }

    public function test_compute_hash_changes_with_different_payload(): void
    {
        $previousHash = str_repeat('0', 64);
        $timestamp = '2026-01-15T10:00:00Z';

        $hash1 = HashChain::computeHash($previousHash, 'payload-a', $timestamp);
        $hash2 = HashChain::computeHash($previousHash, 'payload-b', $timestamp);

        $this->assertNotSame($hash1, $hash2);
    }

    // --- Verification ---

    public function test_verify_returns_true_for_correct_payload(): void
    {
        $previousHash = str_repeat('0', 64);
        $payload = 'invoice:12345';
        $timestamp = '2026-01-15T10:00:00Z';
        $hash = HashChain::computeHash($previousHash, $payload, $timestamp);

        $entry = HashChain::genesis(
            tenantId: 1,
            entryHash: $hash,
            createdAt: new \DateTimeImmutable($timestamp),
        );

        $this->assertTrue($entry->verify($payload, $timestamp));
    }

    public function test_verify_returns_false_for_tampered_payload(): void
    {
        $previousHash = str_repeat('0', 64);
        $payload = 'invoice:12345';
        $timestamp = '2026-01-15T10:00:00Z';
        $hash = HashChain::computeHash($previousHash, $payload, $timestamp);

        $entry = HashChain::genesis(
            tenantId: 1,
            entryHash: $hash,
            createdAt: new \DateTimeImmutable($timestamp),
        );

        $this->assertFalse($entry->verify('invoice:99999', $timestamp));
    }

    // --- Validation ---

    public function test_rejects_invalid_sequence_number(): void
    {
        $hash = HashChain::computeHash(str_repeat('0', 64), 'data', '2026-01-15T10:00:00Z');

        $this->expectException(\InvalidArgumentException::class);
        new HashChain(
            tenantId: 1,
            sequenceNumber: 0,
            entryHash: $hash,
            previousHash: str_repeat('0', 64),
            createdAt: new \DateTimeImmutable('2026-01-15T10:00:00Z'),
        );
    }

    public function test_rejects_negative_sequence_number(): void
    {
        $hash = HashChain::computeHash(str_repeat('0', 64), 'data', '2026-01-15T10:00:00Z');

        $this->expectException(\InvalidArgumentException::class);
        new HashChain(
            tenantId: 1,
            sequenceNumber: -1,
            entryHash: $hash,
            previousHash: str_repeat('0', 64),
            createdAt: new \DateTimeImmutable('2026-01-15T10:00:00Z'),
        );
    }

    public function test_rejects_invalid_entry_hash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HashChain(
            tenantId: 1,
            sequenceNumber: 1,
            entryHash: 'not-a-valid-hash',
            previousHash: str_repeat('0', 64),
            createdAt: new \DateTimeImmutable('2026-01-15T10:00:00Z'),
        );
    }

    // --- Serialization ---

    public function test_to_array_from_array_roundtrip(): void
    {
        $previousHash = str_repeat('0', 64);
        $payload = 'roundtrip-test';
        $timestamp = '2026-01-15T10:00:00Z';
        $hash = HashChain::computeHash($previousHash, $payload, $timestamp);

        $original = HashChain::genesis(
            tenantId: 1,
            entryHash: $hash,
            createdAt: new \DateTimeImmutable($timestamp),
        );

        $restored = HashChain::fromArray($original->toArray());

        $this->assertSame($original->tenantId(), $restored->tenantId());
        $this->assertSame($original->sequenceNumber(), $restored->sequenceNumber());
        $this->assertSame($original->entryHash(), $restored->entryHash());
        $this->assertSame($original->previousHash(), $restored->previousHash());
    }
}
