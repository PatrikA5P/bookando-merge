<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Tax;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Tax\TaxRate;

final class TaxRateTest extends TestCase
{
    // --- Named constructors ---

    public function test_chf_standard(): void
    {
        $rate = TaxRate::chfStandard();

        $this->assertSame(0.081, $rate->rate);
        $this->assertSame('Standard', $rate->label);
        $this->assertSame('CH', $rate->country);
    }

    public function test_de_standard(): void
    {
        $rate = TaxRate::deStandard();

        $this->assertSame(0.19, $rate->rate);
        $this->assertSame('DE', $rate->country);
    }

    // --- Calculation ---

    public function test_apply_to(): void
    {
        $rate = TaxRate::chfStandard();
        // 10000 minor units * 0.081 = 810
        $this->assertSame(810, $rate->applyTo(10000));
    }

    // --- Display ---

    public function test_display_percentage(): void
    {
        $chf = TaxRate::chfStandard();
        $this->assertSame('8.1%', $chf->displayPercentage());

        $de = TaxRate::deStandard();
        $this->assertSame('19%', $de->displayPercentage());
    }

    // --- Validity period ---

    public function test_is_valid_at_within_range(): void
    {
        $rate = new TaxRate(
            rate: 0.081,
            label: 'Standard',
            country: 'CH',
            validFrom: new \DateTimeImmutable('2024-01-01'),
            validUntil: new \DateTimeImmutable('2025-12-31'),
        );

        $this->assertTrue($rate->isValidAt(new \DateTimeImmutable('2024-06-15')));
    }

    public function test_is_valid_at_before_range(): void
    {
        $rate = new TaxRate(
            rate: 0.081,
            label: 'Standard',
            country: 'CH',
            validFrom: new \DateTimeImmutable('2024-01-01'),
            validUntil: new \DateTimeImmutable('2025-12-31'),
        );

        $this->assertFalse($rate->isValidAt(new \DateTimeImmutable('2023-06-15')));
    }

    public function test_is_valid_at_after_range(): void
    {
        $rate = new TaxRate(
            rate: 0.081,
            label: 'Standard',
            country: 'CH',
            validFrom: new \DateTimeImmutable('2024-01-01'),
            validUntil: new \DateTimeImmutable('2025-12-31'),
        );

        $this->assertFalse($rate->isValidAt(new \DateTimeImmutable('2026-06-15')));
    }

    // --- Validation ---

    public function test_rejects_negative_rate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TaxRate(
            rate: -0.01,
            label: 'Invalid',
            country: 'CH',
            validFrom: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function test_rejects_invalid_country(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TaxRate(
            rate: 0.081,
            label: 'Standard',
            country: 'ch', // lowercase
            validFrom: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function test_rejects_country_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TaxRate(
            rate: 0.081,
            label: 'Standard',
            country: 'CHE', // too long
            validFrom: new \DateTimeImmutable('2024-01-01'),
        );
    }

    // --- Serialization ---

    public function test_to_array_from_array_roundtrip(): void
    {
        $original = TaxRate::chfStandard();
        $restored = TaxRate::fromArray($original->toArray());

        $this->assertSame($original->rate, $restored->rate);
        $this->assertSame($original->label, $restored->label);
        $this->assertSame($original->country, $restored->country);
        $this->assertSame(
            $original->validFrom->format('Y-m-d'),
            $restored->validFrom->format('Y-m-d'),
        );
    }
}
