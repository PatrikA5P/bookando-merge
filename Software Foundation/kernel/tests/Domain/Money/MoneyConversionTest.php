<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Money;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Money\Currency;
use SoftwareFoundation\Kernel\Domain\Money\Money;

final class MoneyConversionTest extends TestCase
{
    public function test_convert_eur_to_chf(): void
    {
        $eur = Money::EUR(1000); // 10.00 EUR
        $chf = $eur->convertTo(Currency::CHF, 0.93);

        $this->assertSame(930, $chf->amount());
        $this->assertSame(Currency::CHF, $chf->currency());
    }

    public function test_convert_chf_to_jpy(): void
    {
        // CHF has 2 decimals, JPY has 0 decimals
        $chf = Money::CHF(1000); // 10.00 CHF
        $jpy = $chf->convertTo(Currency::JPY, 170.0);

        // 10.00 CHF * 170.0 = 1700 JPY
        // convertTo: amount * rate * 10^(toDecimals - fromDecimals)
        // 1000 * 170.0 * 10^(0-2) = 1000 * 170.0 * 0.01 = 1700
        $this->assertSame(1700, $jpy->amount());
        $this->assertSame(Currency::JPY, $jpy->currency());
    }

    public function test_convert_rejects_negative_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::EUR(1000)->convertTo(Currency::CHF, -1.0);
    }

    public function test_convert_preserves_immutability(): void
    {
        $original = Money::EUR(1000);
        $converted = $original->convertTo(Currency::CHF, 0.93);

        // Original unchanged
        $this->assertSame(1000, $original->amount());
        $this->assertSame(Currency::EUR, $original->currency());

        // Converted is a different instance
        $this->assertSame(930, $converted->amount());
        $this->assertSame(Currency::CHF, $converted->currency());
    }
}
