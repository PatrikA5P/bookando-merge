<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Consent;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Consent\ConsentPurpose;

final class ConsentPurposeTest extends TestCase
{
    public function testIsSensitiveDataForPhotoStorage(): void
    {
        $this->assertTrue(ConsentPurpose::PHOTO_STORAGE->isSensitiveData());
    }

    public function testIsSensitiveDataForBiometric(): void
    {
        $this->assertTrue(ConsentPurpose::BIOMETRIC->isSensitiveData());
    }

    public function testIsSensitiveDataForMarketing(): void
    {
        $this->assertFalse(ConsentPurpose::MARKETING->isSensitiveData());
    }

    public function testRequiresExplicitConsentForPhotoStorage(): void
    {
        $this->assertTrue(ConsentPurpose::PHOTO_STORAGE->requiresExplicitConsent());
    }

    public function testRequiresExplicitConsentForCrossBorderTransfer(): void
    {
        $this->assertTrue(ConsentPurpose::CROSS_BORDER_TRANSFER->requiresExplicitConsent());
    }

    public function testRequiresExplicitConsentForAnalytics(): void
    {
        $this->assertFalse(ConsentPurpose::ANALYTICS->requiresExplicitConsent());
    }
}
