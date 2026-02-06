<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Storage;

final class FileValidationResult
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        public readonly bool $valid,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly array $errors,
    ) {
    }

    public static function pass(string $mimeType, int $sizeBytes): self
    {
        return new self(
            valid: true,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            errors: [],
        );
    }

    /**
     * @param string[] $errors
     */
    public static function fail(string $mimeType, int $sizeBytes, array $errors): self
    {
        return new self(
            valid: false,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            errors: $errors,
        );
    }
}
