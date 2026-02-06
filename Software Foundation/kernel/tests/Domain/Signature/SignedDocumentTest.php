<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Signature;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Signature\SignatureLevel;
use SoftwareFoundation\Kernel\Domain\Signature\SignedDocument;

final class SignedDocumentTest extends TestCase
{
    private function validHash(): string
    {
        return hash('sha256', 'test-document-content');
    }

    private function createDocument(SignatureLevel $level): SignedDocument
    {
        return new SignedDocument(
            documentHash: $this->validHash(),
            signatureBytes: base64_encode('signature-bytes'),
            signerId: 'user-123',
            level: $level,
            timestamp: new \DateTimeImmutable('2026-01-15T10:00:00Z'),
            certificateChain: 'cert-chain-pem',
            provider: 'swisscom-ais',
        );
    }

    // --- Construction ---

    public function test_creates_signed_document(): void
    {
        $doc = $this->createDocument(SignatureLevel::QES);

        $this->assertSame($this->validHash(), $doc->documentHash);
        $this->assertSame('user-123', $doc->signerId);
        $this->assertSame(SignatureLevel::QES, $doc->level);
        $this->assertSame('swisscom-ais', $doc->provider);
        $this->assertSame('cert-chain-pem', $doc->certificateChain);
    }

    // --- Qualification check ---

    public function test_is_qualified(): void
    {
        $qes = $this->createDocument(SignatureLevel::QES);
        $this->assertTrue($qes->isQualified());

        $aes = $this->createDocument(SignatureLevel::AES);
        $this->assertFalse($aes->isQualified());

        $ses = $this->createDocument(SignatureLevel::SES);
        $this->assertFalse($ses->isQualified());
    }

    // --- Validation ---

    public function test_rejects_invalid_document_hash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SignedDocument(
            documentHash: 'not-a-valid-hash',
            signatureBytes: 'sig',
            signerId: 'user-1',
            level: SignatureLevel::SES,
            timestamp: new \DateTimeImmutable(),
            certificateChain: null,
            provider: 'test',
        );
    }

    // --- Serialization ---

    public function test_to_array_from_array_roundtrip(): void
    {
        $original = $this->createDocument(SignatureLevel::QES);
        $restored = SignedDocument::fromArray($original->toArray());

        $this->assertSame($original->documentHash, $restored->documentHash);
        $this->assertSame($original->signatureBytes, $restored->signatureBytes);
        $this->assertSame($original->signerId, $restored->signerId);
        $this->assertSame($original->level, $restored->level);
        $this->assertSame($original->provider, $restored->provider);
        $this->assertSame($original->certificateChain, $restored->certificateChain);
    }
}
