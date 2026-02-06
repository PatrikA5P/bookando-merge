<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Dossier;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierType;

final class DossierTypeTest extends TestCase
{
    public function testRetentionYearsForPersonal(): void
    {
        $this->assertSame(10, DossierType::PERSONAL->retentionYears());
    }

    public function testRetentionYearsForOrder(): void
    {
        $this->assertSame(10, DossierType::ORDER->retentionYears());
    }

    public function testRetentionYearsForCourse(): void
    {
        $this->assertSame(10, DossierType::COURSE->retentionYears());
    }

    public function testRetentionYearsForTimeTracking(): void
    {
        $this->assertSame(5, DossierType::TIME_TRACKING->retentionYears());
    }

    public function testRetentionYearsForSalaryCertificate(): void
    {
        $this->assertSame(10, DossierType::SALARY_CERTIFICATE->retentionYears());
    }

    public function testRetentionYearsForAccounting(): void
    {
        $this->assertSame(10, DossierType::ACCOUNTING->retentionYears());
    }

    public function testRetentionYearsForContract(): void
    {
        $this->assertSame(10, DossierType::CONTRACT->retentionYears());
    }

    public function testRetentionYearsForApplicant(): void
    {
        $this->assertSame(0, DossierType::APPLICANT->retentionYears());
    }

    public function testRetentionYearsForVatProperty(): void
    {
        $this->assertSame(20, DossierType::VAT_PROPERTY->retentionYears());
    }

    public function testContainsPersonalDataForPersonal(): void
    {
        $this->assertTrue(DossierType::PERSONAL->containsPersonalData());
    }

    public function testContainsPersonalDataForOrder(): void
    {
        $this->assertFalse(DossierType::ORDER->containsPersonalData());
    }

    public function testContainsPersonalDataForApplicant(): void
    {
        $this->assertTrue(DossierType::APPLICANT->containsPersonalData());
    }

    public function testRequiresEncryptionMatchesContainsPersonalDataForPersonal(): void
    {
        $type = DossierType::PERSONAL;
        $this->assertSame($type->containsPersonalData(), $type->requiresEncryption());
    }

    public function testRequiresEncryptionMatchesContainsPersonalDataForOrder(): void
    {
        $type = DossierType::ORDER;
        $this->assertSame($type->containsPersonalData(), $type->requiresEncryption());
    }

    public function testRequiresEncryptionMatchesContainsPersonalDataForApplicant(): void
    {
        $type = DossierType::APPLICANT;
        $this->assertSame($type->containsPersonalData(), $type->requiresEncryption());
    }

    public function testLabelReturnsNonEmptyString(): void
    {
        foreach (DossierType::cases() as $type) {
            $label = $type->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }
}
