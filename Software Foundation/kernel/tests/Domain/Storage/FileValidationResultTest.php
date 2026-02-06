<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Storage;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Storage\FileValidationResult;

final class FileValidationResultTest extends TestCase
{
    public function testPassResult(): void
    {
        $result = FileValidationResult::pass('application/pdf', 1024);
        self::assertTrue($result->valid);
        self::assertSame('application/pdf', $result->mimeType);
        self::assertSame(1024, $result->sizeBytes);
        self::assertSame([], $result->errors);
    }

    public function testFailResult(): void
    {
        $result = FileValidationResult::fail(
            'application/exe',
            2048,
            ['Disallowed MIME type', 'File too large']
        );
        self::assertFalse($result->valid);
        self::assertSame('application/exe', $result->mimeType);
        self::assertSame(2048, $result->sizeBytes);
        self::assertCount(2, $result->errors);
        self::assertSame('Disallowed MIME type', $result->errors[0]);
    }
}
