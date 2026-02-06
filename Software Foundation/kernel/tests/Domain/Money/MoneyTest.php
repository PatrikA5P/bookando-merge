<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Money;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Money\Currency;
use SoftwareFoundation\Kernel\Domain\Money\CurrencyMismatchException;
use SoftwareFoundation\Kernel\Domain\Money\Money;

final class MoneyTest extends TestCase
{
    // --- Construction ---

    public function test_creates_from_minor_units(): void
    {
        $m = Money::of(1050, Currency::CHF);
        $this->assertSame(1050, $m->amount());
        $this->assertSame(Currency::CHF, $m->currency());
    }

    public function test_named_constructor_chf(): void
    {
        $m = Money::CHF(500);
        $this->assertSame(500, $m->amount());
        $this->assertSame(Currency::CHF, $m->currency());
    }

    public function test_named_constructor_eur(): void
    {
        $m = Money::EUR(999);
        $this->assertSame(Currency::EUR, $m->currency());
    }

    public function test_named_constructor_usd(): void
    {
        $m = Money::USD(1);
        $this->assertSame(Currency::USD, $m->currency());
    }

    public function test_zero(): void
    {
        $m = Money::zero(Currency::EUR);
        $this->assertSame(0, $m->amount());
        $this->assertTrue($m->isZero());
    }

    public function test_from_display_standard_currency(): void
    {
        $m = Money::fromDisplay('10.50', Currency::CHF);
        $this->assertSame(1050, $m->amount());
    }

    public function test_from_display_zero_decimal_currency(): void
    {
        $m = Money::fromDisplay('1000', Currency::JPY);
        $this->assertSame(1000, $m->amount());
    }

    public function test_from_display_three_decimal_currency(): void
    {
        $m = Money::fromDisplay('1.234', Currency::BHD);
        $this->assertSame(1234, $m->amount());
    }

    // --- Arithmetic ---

    public function test_add(): void
    {
        $a = Money::CHF(1000);
        $b = Money::CHF(250);
        $result = $a->add($b);

        $this->assertSame(1250, $result->amount());
        // Immutable: original unchanged
        $this->assertSame(1000, $a->amount());
    }

    public function test_subtract(): void
    {
        $result = Money::CHF(1000)->subtract(Money::CHF(300));
        $this->assertSame(700, $result->amount());
    }

    public function test_add_different_currencies_throws(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::CHF(100)->add(Money::EUR(100));
    }

    public function test_subtract_different_currencies_throws(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::CHF(100)->subtract(Money::EUR(100));
    }

    public function test_multiply_integer(): void
    {
        $result = Money::CHF(500)->multiply(3);
        $this->assertSame(1500, $result->amount());
    }

    public function test_multiply_float_rounds_half_up(): void
    {
        // 333 * 1.5 = 499.5 → rounds to 500
        $result = Money::CHF(333)->multiply(1.5);
        $this->assertSame(500, $result->amount());
    }

    public function test_allocate_evenly(): void
    {
        $parts = Money::CHF(900)->allocate(3);

        $this->assertCount(3, $parts);
        $this->assertSame(300, $parts[0]->amount());
        $this->assertSame(300, $parts[1]->amount());
        $this->assertSame(300, $parts[2]->amount());
    }

    public function test_allocate_with_remainder(): void
    {
        $parts = Money::CHF(100)->allocate(3);

        $this->assertCount(3, $parts);
        // Sum must equal original: 34 + 33 + 33 = 100
        $sum = array_sum(array_map(fn(Money $p) => $p->amount(), $parts));
        $this->assertSame(100, $sum);
        $this->assertSame(34, $parts[0]->amount());
        $this->assertSame(33, $parts[1]->amount());
    }

    public function test_allocate_zero_parts_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::CHF(100)->allocate(0);
    }

    public function test_apply_percentage(): void
    {
        // 8.1% of 10000 (100.00 CHF) = 810
        $result = Money::CHF(10000)->applyPercentage(0.081);
        $this->assertSame(810, $result->amount());
    }

    public function test_negate(): void
    {
        $m = Money::CHF(500)->negate();
        $this->assertSame(-500, $m->amount());
    }

    public function test_abs(): void
    {
        $m = Money::CHF(-500)->abs();
        $this->assertSame(500, $m->amount());
    }

    // --- Comparison ---

    public function test_equals(): void
    {
        $this->assertTrue(Money::CHF(100)->equals(Money::CHF(100)));
        $this->assertFalse(Money::CHF(100)->equals(Money::CHF(200)));
        $this->assertFalse(Money::CHF(100)->equals(Money::EUR(100)));
    }

    public function test_greater_than(): void
    {
        $this->assertTrue(Money::CHF(200)->isGreaterThan(Money::CHF(100)));
        $this->assertFalse(Money::CHF(100)->isGreaterThan(Money::CHF(100)));
    }

    public function test_less_than(): void
    {
        $this->assertTrue(Money::CHF(100)->isLessThan(Money::CHF(200)));
    }

    public function test_is_positive_negative_zero(): void
    {
        $this->assertTrue(Money::CHF(1)->isPositive());
        $this->assertTrue(Money::CHF(-1)->isNegative());
        $this->assertTrue(Money::CHF(0)->isZero());
    }

    // --- Display ---

    public function test_to_display_standard(): void
    {
        $this->assertSame('10.50', Money::CHF(1050)->toDisplay());
    }

    public function test_to_display_zero_decimal(): void
    {
        $this->assertSame('1000', Money::of(1000, Currency::JPY)->toDisplay());
    }

    public function test_to_string(): void
    {
        $this->assertSame('10.50 CHF', (string) Money::CHF(1050));
    }

    // --- Serialization ---

    public function test_to_array_from_array_roundtrip(): void
    {
        $original = Money::CHF(1050);
        $restored = Money::fromArray($original->toArray());

        $this->assertTrue($original->equals($restored));
    }
}
