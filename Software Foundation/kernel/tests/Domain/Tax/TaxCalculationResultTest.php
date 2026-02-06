<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Tax;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Tax\TaxCalculationResult;
use SoftwareFoundation\Kernel\Domain\Tax\TaxRate;

final class TaxCalculationResultTest extends TestCase
{
    public function test_from_net(): void
    {
        $rate = TaxRate::chfStandard(); // 8.1%
        $result = TaxCalculationResult::fromNet(10000, $rate, 'CHF');

        $this->assertSame(10000, $result->netAmount);
        $this->assertSame(810, $result->taxAmount);
        $this->assertSame(10810, $result->grossAmount);
        $this->assertSame('CHF', $result->currency);
    }

    public function test_from_gross(): void
    {
        $rate = TaxRate::chfStandard(); // 8.1%
        $result = TaxCalculationResult::fromGross(10810, $rate, 'CHF');

        $this->assertSame(10810, $result->grossAmount);
        // net = round(10810 / 1.081) = round(10000.0) = 10000
        $this->assertSame(10000, $result->netAmount);
        // tax = gross - net = 10810 - 10000 = 810
        $this->assertSame(810, $result->taxAmount);
    }

    public function test_to_array(): void
    {
        $rate = TaxRate::chfStandard();
        $result = TaxCalculationResult::fromNet(10000, $rate, 'CHF');
        $arr = $result->toArray();

        $this->assertSame(10000, $arr['netAmount']);
        $this->assertSame(810, $arr['taxAmount']);
        $this->assertSame(10810, $arr['grossAmount']);
        $this->assertSame('CHF', $arr['currency']);
        $this->assertIsArray($arr['rate']);
        $this->assertSame(0.081, $arr['rate']['rate']);
    }
}
