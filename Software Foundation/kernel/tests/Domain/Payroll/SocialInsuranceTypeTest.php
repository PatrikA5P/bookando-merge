<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Payroll;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Payroll\SocialInsuranceType;

final class SocialInsuranceTypeTest extends TestCase
{
    public function testIsParityContributionForAhv(): void
    {
        $this->assertTrue(SocialInsuranceType::AHV->isParityContribution());
    }

    public function testIsParityContributionForUvgBu(): void
    {
        $this->assertFalse(SocialInsuranceType::UVG_BU->isParityContribution());
    }

    public function testIsParityContributionForFak(): void
    {
        $this->assertFalse(SocialInsuranceType::FAK->isParityContribution());
    }

    public function testIsMandatoryForAhv(): void
    {
        $this->assertTrue(SocialInsuranceType::AHV->isMandatory());
    }

    public function testIsMandatoryForKtg(): void
    {
        $this->assertFalse(SocialInsuranceType::KTG->isMandatory());
    }

    public function testIsMandatoryForUvgz(): void
    {
        $this->assertFalse(SocialInsuranceType::UVGZ->isMandatory());
    }

    public function testLabelReturnsNonEmptyString(): void
    {
        foreach (SocialInsuranceType::cases() as $type) {
            $label = $type->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }
}
