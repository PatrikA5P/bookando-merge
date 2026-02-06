<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Storage\FileValidationResult;
use SoftwareFoundation\Kernel\Domain\Storage\MalwareScanResult;

interface FileValidationPort
{
    /**
     * Validates a file against allowed MIME types and maximum size.
     *
     * @param string[] $allowedMimeTypes Empty array means all types are allowed
     * @param int $maxSizeBytes          0 means no size limit
     */
    public function validate(
        string $filePath,
        array $allowedMimeTypes = [],
        int $maxSizeBytes = 0,
    ): FileValidationResult;

    /**
     * Scans a file for malware.
     */
    public function scanForMalware(string $filePath): MalwareScanResult;
}
