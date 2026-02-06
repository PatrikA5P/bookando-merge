<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Dossier;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierEntry;

final class DossierEntryTest extends TestCase
{
    public function testValidConstructionWithSha256Hash(): void
    {
        $content = 'test content';
        $hash = hash('sha256', $content);

        $entry = new DossierEntry(
            id: 'entry_001',
            dossierId: 'dossier_001',
            fileName: 'document.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 1024,
            sha256Hash: $hash
        );

        $this->assertInstanceOf(DossierEntry::class, $entry);
        $this->assertSame('entry_001', $entry->id);
        $this->assertSame('document.pdf', $entry->fileName);
    }

    public function testInvalidHashThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DossierEntry(
            id: 'entry_002',
            dossierId: 'dossier_001',
            fileName: 'document.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 1024,
            sha256Hash: 'invalid_hash'
        );
    }

    public function testEmptyFileNameThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DossierEntry(
            id: 'entry_003',
            dossierId: 'dossier_001',
            fileName: '',
            mimeType: 'application/pdf',
            sizeBytes: 1024,
            sha256Hash: hash('sha256', 'test')
        );
    }

    public function testNegativeSizeBytesThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DossierEntry(
            id: 'entry_004',
            dossierId: 'dossier_001',
            fileName: 'document.pdf',
            mimeType: 'application/pdf',
            sizeBytes: -100,
            sha256Hash: hash('sha256', 'test')
        );
    }

    public function testVerifyIntegrityReturnsTrueForMatchingContent(): void
    {
        $content = 'test content';
        $hash = hash('sha256', $content);

        $entry = new DossierEntry(
            id: 'entry_005',
            dossierId: 'dossier_001',
            fileName: 'document.txt',
            mimeType: 'text/plain',
            sizeBytes: strlen($content),
            sha256Hash: $hash
        );

        $this->assertTrue($entry->verifyIntegrity($content));
    }

    public function testVerifyIntegrityReturnsFalseForWrongContent(): void
    {
        $content = 'test content';
        $hash = hash('sha256', $content);

        $entry = new DossierEntry(
            id: 'entry_006',
            dossierId: 'dossier_001',
            fileName: 'document.txt',
            mimeType: 'text/plain',
            sizeBytes: strlen($content),
            sha256Hash: $hash
        );

        $this->assertFalse($entry->verifyIntegrity('wrong content'));
    }
}
