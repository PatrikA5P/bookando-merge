<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Consent;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Consent\Consent;
use SoftwareFoundation\Kernel\Domain\Consent\ConsentPurpose;

final class ConsentTest extends TestCase
{
    public function testGrantCreatesWithGrantedTrue(): void
    {
        $consent = Consent::grant(
            id: 'consent_001',
            tenantId: 42,
            subjectId: 'emp_001',
            purpose: ConsentPurpose::PHOTO_STORAGE,
            grantedAt: new DateTimeImmutable('2025-01-15T10:00:00+00:00'),
            expiresAt: new DateTimeImmutable('2026-01-15T10:00:00+00:00')
        );

        $this->assertInstanceOf(Consent::class, $consent);
        $this->assertTrue($consent->granted);
    }

    public function testRevokeCreatesWithGrantedFalse(): void
    {
        $consent = Consent::grant(
            id: 'consent_002',
            tenantId: 42,
            subjectId: 'emp_001',
            purpose: ConsentPurpose::MARKETING,
            grantedAt: new DateTimeImmutable('2025-01-15T10:00:00+00:00'),
            expiresAt: new DateTimeImmutable('2026-01-15T10:00:00+00:00')
        );

        $revoked = $consent->revoke(new DateTimeImmutable('2025-06-15T10:00:00+00:00'));

        $this->assertFalse($revoked->granted);
    }

    public function testIsValidReturnsTrueForActiveConsent(): void
    {
        $consent = Consent::grant(
            id: 'consent_003',
            tenantId: 42,
            subjectId: 'emp_001',
            purpose: ConsentPurpose::ANALYTICS,
            grantedAt: new DateTimeImmutable('2025-01-15T10:00:00+00:00'),
            expiresAt: new DateTimeImmutable('2026-01-15T10:00:00+00:00')
        );

        $this->assertTrue($consent->isValid(new DateTimeImmutable('2025-06-15T10:00:00+00:00')));
    }

    public function testIsValidReturnsFalseForRevokedConsent(): void
    {
        $consent = Consent::grant(
            id: 'consent_004',
            tenantId: 42,
            subjectId: 'emp_001',
            purpose: ConsentPurpose::MARKETING,
            grantedAt: new DateTimeImmutable('2025-01-15T10:00:00+00:00'),
            expiresAt: new DateTimeImmutable('2026-01-15T10:00:00+00:00')
        );

        $revoked = $consent->revoke(new DateTimeImmutable('2025-03-15T10:00:00+00:00'));

        $this->assertFalse($revoked->isValid(new DateTimeImmutable('2025-06-15T10:00:00+00:00')));
    }

    public function testIsValidReturnsFalseForExpiredConsent(): void
    {
        $consent = Consent::grant(
            id: 'consent_005',
            tenantId: 42,
            subjectId: 'emp_001',
            purpose: ConsentPurpose::PHOTO_STORAGE,
            grantedAt: new DateTimeImmutable('2024-01-15T10:00:00+00:00'),
            expiresAt: new DateTimeImmutable('2025-01-15T10:00:00+00:00')
        );

        // Check validity after expiration date
        $this->assertFalse($consent->isValid(new DateTimeImmutable('2025-06-15T10:00:00+00:00')));
    }
}
