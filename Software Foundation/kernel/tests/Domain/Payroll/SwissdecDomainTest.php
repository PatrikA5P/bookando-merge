<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Payroll;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Payroll\SwissdecDomain;

final class SwissdecDomainTest extends TestCase
{
    public function testAllSevenCasesExist(): void
    {
        $cases = SwissdecDomain::cases();
        $this->assertCount(7, $cases);
    }

    public function testMinimumElmVersionForQuellensteuer(): void
    {
        $this->assertSame('5.3', SwissdecDomain::QUELLENSTEUER->minimumElmVersion());
    }

    public function testMinimumElmVersionForAhvIvEo(): void
    {
        $this->assertSame('5.0', SwissdecDomain::AHV_IV_EO->minimumElmVersion());
    }

    public function testLabelReturnsNonEmptyStringForEachCase(): void
    {
        foreach (SwissdecDomain::cases() as $domain) {
            $label = $domain->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }
}
