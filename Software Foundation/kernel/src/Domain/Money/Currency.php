<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Money;

/**
 * ISO 4217 Currency codes with decimal place information.
 *
 * Zero-decimal currencies (JPY, KRW, etc.) have 0 decimal places.
 * Standard currencies (CHF, EUR, USD, etc.) have 2 decimal places.
 * Some currencies (BHD, KWD, etc.) have 3 decimal places.
 */
enum Currency: string
{
    // Standard (2 decimal places)
    case CHF = 'CHF';
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    case CAD = 'CAD';
    case AUD = 'AUD';
    case SEK = 'SEK';
    case NOK = 'NOK';
    case DKK = 'DKK';
    case PLN = 'PLN';
    case CZK = 'CZK';
    case HUF = 'HUF';
    case RON = 'RON';
    case BGN = 'BGN';
    case HRK = 'HRK';
    case TRY = 'TRY';
    case BRL = 'BRL';
    case MXN = 'MXN';
    case INR = 'INR';
    case CNY = 'CNY';
    case SGD = 'SGD';
    case HKD = 'HKD';
    case NZD = 'NZD';
    case THB = 'THB';
    case MYR = 'MYR';
    case PHP = 'PHP';
    case IDR = 'IDR';
    case ZAR = 'ZAR';
    case AED = 'AED';
    case SAR = 'SAR';
    case ILS = 'ILS';

    // Zero-decimal currencies (0 decimal places)
    case JPY = 'JPY';
    case KRW = 'KRW';
    case VND = 'VND';
    case CLP = 'CLP';
    case ISK = 'ISK';
    case UGX = 'UGX';
    case RWF = 'RWF';
    case XOF = 'XOF';
    case XAF = 'XAF';

    // Three-decimal currencies (3 decimal places)
    case BHD = 'BHD';
    case KWD = 'KWD';
    case OMR = 'OMR';

    public function decimalPlaces(): int
    {
        return match ($this) {
            self::JPY, self::KRW, self::VND, self::CLP,
            self::ISK, self::UGX, self::RWF, self::XOF, self::XAF => 0,

            self::BHD, self::KWD, self::OMR => 3,

            default => 2,
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::CHF => 'CHF',
            self::EUR => '€',
            self::USD => '$',
            self::GBP => '£',
            self::JPY => '¥',
            self::KRW => '₩',
            self::INR => '₹',
            self::TRY => '₺',
            self::BRL => 'R$',
            self::PLN => 'zł',
            self::CZK => 'Kč',
            self::ILS => '₪',
            self::THB => '฿',
            self::ZAR => 'R',
            default => $this->value,
        };
    }

    /**
     * Is this a zero-decimal currency?
     * Important for payment gateway integration (Stripe, etc.)
     */
    public function isZeroDecimal(): bool
    {
        return $this->decimalPlaces() === 0;
    }
}
