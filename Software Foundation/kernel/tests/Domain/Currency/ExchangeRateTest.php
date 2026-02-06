<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Currency;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Currency\ExchangeRate;

final class ExchangeRateTest extends TestCase
{
    // --- Construction ---

    public function test_creates_valid_rate(): void
    {
        $rate = new ExchangeRate(
            from: 'EUR',
            to: 'CHF',
            rate: 1.08,
            date: new \DateTimeImmutable('2026-01-15'),
            source: 'ecb',
        );

        $this->assertSame('EUR', $rate->from);
        $this->assertSame('CHF', $rate->to);
        $this->assertSame(1.08, $rate->rate);
        $this->assertSame('ecb', $rate->source);
    }

    // --- Conversion ---

    public function test_convert(): void
    {
        $rate = new ExchangeRate(
            from: 'EUR',
            to: 'CHF',
            rate: 1.08,
            date: new \DateTimeImmutable('2026-01-15'),
            source: 'ecb',
        );

        // 1000 EUR cents (10.00 EUR) at 1.08 → 1080 CHF cents (10.80 CHF)
        // Both currencies have 2 decimal places
        $result = $rate->convert(1000, 2, 2);
        $this->assertSame(1080, $result);
    }

    // --- Inverse ---

    public function test_inverse(): void
    {
        $rate = new ExchangeRate(
            from: 'EUR',
            to: 'CHF',
            rate: 1.08,
            date: new \DateTimeImmutable('2026-01-15'),
            source: 'ecb',
        );

        $inverse = $rate->inverse();

        $this->assertSame('CHF', $inverse->from);
        $this->assertSame('EUR', $inverse->to);
        $this->assertEqualsWithDelta(1.0 / 1.08, $inverse->rate, 0.0001);
        $this->assertSame('ecb', $inverse->source);
    }

    // --- Validation ---

    public function test_rejects_negative_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ExchangeRate(
            from: 'EUR',
            to: 'CHF',
            rate: -1.0,
            date: new \DateTimeImmutable('2026-01-15'),
            source: 'ecb',
        );
    }

    public function test_rejects_invalid_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ExchangeRate(
            from: 'eur', // lowercase
            to: 'CHF',
            rate: 1.08,
            date: new \DateTimeImmutable('2026-01-15'),
            source: 'ecb',
        );
    }

    // --- Serialization ---

    public function test_to_array_from_array_roundtrip(): void
    {
        $original = new ExchangeRate(
            from: 'EUR',
            to: 'CHF',
            rate: 1.08,
            date: new \DateTimeImmutable('2026-01-15'),
            source: 'ecb',
        );

        $restored = ExchangeRate::fromArray($original->toArray());

        $this->assertSame($original->from, $restored->from);
        $this->assertSame($original->to, $restored->to);
        $this->assertSame($original->rate, $restored->rate);
        $this->assertSame($original->source, $restored->source);
        $this->assertSame(
            $original->date->format('Y-m-d'),
            $restored->date->format('Y-m-d'),
        );
    }
}
