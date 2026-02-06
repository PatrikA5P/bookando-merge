<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Signature\SignatureLevel;
use SoftwareFoundation\Kernel\Domain\Signature\SignedDocument;

interface DigitalSignaturePort
{
    public function sign(
        string $documentContent,
        SignatureLevel $level,
        string $signerId,
        int $tenantId,
    ): SignedDocument;

    /**
     * Verifies the signature of a signed document against the original content.
     */
    public function verify(SignedDocument $document, string $documentContent): bool;

    /**
     * Returns the signature levels supported by this provider.
     *
     * @return SignatureLevel[]
     */
    public function supportedLevels(): array;
}
