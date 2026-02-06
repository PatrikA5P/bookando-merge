<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Signature;

enum SignatureLevel: string
{
    case SES = 'ses';
    case AES = 'aes';
    case QES = 'qes';

    public function label(): string
    {
        return match ($this) {
            self::SES => 'Simple Electronic Signature',
            self::AES => 'Advanced Electronic Signature',
            self::QES => 'Qualified Electronic Signature',
        };
    }

    public function isLegallyBinding(): bool
    {
        return $this === self::QES;
    }
}
