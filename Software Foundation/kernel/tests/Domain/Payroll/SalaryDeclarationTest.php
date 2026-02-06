<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Payroll;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Payroll\SalaryDeclaration;
use SoftwareFoundation\Kernel\Domain\Payroll\SalaryDeclarationStatus;
use SoftwareFoundation\Kernel\Domain\Payroll\SwissdecDomain;

final class SalaryDeclarationTest extends TestCase
{
    public function testValidConstruction(): void
    {
        $declaration = new SalaryDeclaration(
            id: 'sd_001',
            tenantId: 42,
            employeeId: 'emp_001',
            year: 2025,
            domains: [SwissdecDomain::AHV_IV_EO, SwissdecDomain::BVG],
            wageComponents: [
                'ahv_salary' => 7200000,
                'bvg_salary' => 6000000,
            ],
            status: SalaryDeclarationStatus::DRAFT
        );

        $this->assertInstanceOf(SalaryDeclaration::class, $declaration);
        $this->assertSame('sd_001', $declaration->id);
        $this->assertSame(2025, $declaration->year);
    }

    public function testInvalidYearThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SalaryDeclaration(
            id: 'sd_002',
            tenantId: 42,
            employeeId: 'emp_001',
            year: 1899,
            domains: [SwissdecDomain::AHV_IV_EO],
            wageComponents: ['ahv_salary' => 7200000],
            status: SalaryDeclarationStatus::DRAFT
        );
    }

    public function testEmptyDomainsThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SalaryDeclaration(
            id: 'sd_003',
            tenantId: 42,
            employeeId: 'emp_001',
            year: 2025,
            domains: [],
            wageComponents: ['ahv_salary' => 7200000],
            status: SalaryDeclarationStatus::DRAFT
        );
    }

    public function testTotalGrossSumsAllWageComponents(): void
    {
        $declaration = new SalaryDeclaration(
            id: 'sd_004',
            tenantId: 42,
            employeeId: 'emp_001',
            year: 2025,
            domains: [SwissdecDomain::AHV_IV_EO, SwissdecDomain::BVG],
            wageComponents: [
                'ahv_salary' => 7200000,
                'bvg_salary' => 6000000,
            ],
            status: SalaryDeclarationStatus::DRAFT
        );

        $this->assertSame(13200000, $declaration->totalGross());
    }

    public function testWageForTypeReturnsCorrectValue(): void
    {
        $declaration = new SalaryDeclaration(
            id: 'sd_005',
            tenantId: 42,
            employeeId: 'emp_001',
            year: 2025,
            domains: [SwissdecDomain::AHV_IV_EO, SwissdecDomain::BVG],
            wageComponents: [
                'ahv_salary' => 7200000,
                'bvg_salary' => 6000000,
            ],
            status: SalaryDeclarationStatus::DRAFT
        );

        $this->assertSame(7200000, $declaration->wageForType('ahv_salary'));
        $this->assertSame(6000000, $declaration->wageForType('bvg_salary'));
    }

    public function testWageForTypeReturnsZeroForMissing(): void
    {
        $declaration = new SalaryDeclaration(
            id: 'sd_006',
            tenantId: 42,
            employeeId: 'emp_001',
            year: 2025,
            domains: [SwissdecDomain::AHV_IV_EO],
            wageComponents: [
                'ahv_salary' => 7200000,
            ],
            status: SalaryDeclarationStatus::DRAFT
        );

        $this->assertSame(0, $declaration->wageForType('nonexistent_type'));
    }

    public function testIncludesDomainReturnsCorrectly(): void
    {
        $declaration = new SalaryDeclaration(
            id: 'sd_007',
            tenantId: 42,
            employeeId: 'emp_001',
            year: 2025,
            domains: [SwissdecDomain::AHV_IV_EO, SwissdecDomain::BVG],
            wageComponents: [
                'ahv_salary' => 7200000,
                'bvg_salary' => 6000000,
            ],
            status: SalaryDeclarationStatus::DRAFT
        );

        $this->assertTrue($declaration->includesDomain(SwissdecDomain::AHV_IV_EO));
        $this->assertTrue($declaration->includesDomain(SwissdecDomain::BVG));
        $this->assertFalse($declaration->includesDomain(SwissdecDomain::QUELLENSTEUER));
    }
}
