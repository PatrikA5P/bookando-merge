<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Money;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Money\Currency;

final class CurrencyTest extends TestCase
{
    public function test_standard_currency_has_two_decimals(): void
    {
        $this->assertSame(2, Currency::CHF->decimalPlaces());
        $this->assertSame(2, Currency::EUR->decimalPlaces());
        $this->assertSame(2, Currency::USD->decimalPlaces());
    }

    public function test_zero_decimal_currency(): void
    {
        $this->assertSame(0, Currency::JPY->decimalPlaces());
        $this->assertSame(0, Currency::KRW->decimalPlaces());
        $this->assertTrue(Currency::JPY->isZeroDecimal());
    }

    public function test_three_decimal_currency(): void
    {
        $this->assertSame(3, Currency::BHD->decimalPlaces());
        $this->assertSame(3, Currency::KWD->decimalPlaces());
        $this->assertSame(3, Currency::OMR->decimalPlaces());
    }

    public function test_symbols(): void
    {
        $this->assertSame('€', Currency::EUR->symbol());
        $this->assertSame('$', Currency::USD->symbol());
        $this->assertSame('£', Currency::GBP->symbol());
        $this->assertSame('CHF', Currency::CHF->symbol());
        $this->assertSame('¥', Currency::JPY->symbol());
    }

    public function test_is_zero_decimal(): void
    {
        $this->assertTrue(Currency::JPY->isZeroDecimal());
        $this->assertFalse(Currency::CHF->isZeroDecimal());
        $this->assertFalse(Currency::BHD->isZeroDecimal());
    }

    public function test_from_string(): void
    {
        $this->assertSame(Currency::CHF, Currency::from('CHF'));
        $this->assertSame(Currency::EUR, Currency::from('EUR'));
    }
}
