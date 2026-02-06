<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Dossier;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Dossier\Dossier;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierStatus;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierType;

final class DossierTest extends TestCase
{
    public function testOpenCreatesWithOpenStatusAndEncryptedForPersonalType(): void
    {
        $dossier = Dossier::open(
            id: 'dossier_001',
            tenantId: 42,
            type: DossierType::PERSONAL,
            title: 'Employee Personal Files',
            encrypted: true
        );

        $this->assertInstanceOf(Dossier::class, $dossier);
        $this->assertSame(DossierStatus::OPEN, $dossier->status);
        $this->assertTrue($dossier->encrypted);
    }

    public function testEmptyTitleThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Dossier::open(
            id: 'dossier_002',
            tenantId: 42,
            type: DossierType::PERSONAL,
            title: '',
            encrypted: true
        );
    }

    public function testNonEncryptedPersonalDossierThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Dossier::open(
            id: 'dossier_003',
            tenantId: 42,
            type: DossierType::PERSONAL,
            title: 'Personal Dossier',
            encrypted: false
        );
    }

    public function testCloseTransitionsToClosedAndCalculatesRetentionUntil(): void
    {
        $dossier = Dossier::open(
            id: 'dossier_004',
            tenantId: 42,
            type: DossierType::PERSONAL,
            title: 'Personal Dossier',
            encrypted: true
        );

        $closedAt = new DateTimeImmutable('2025-01-15');
        $closed = $dossier->close($closedAt);

        $this->assertSame(DossierStatus::CLOSED, $closed->status);
        $this->assertNotNull($closed->closedAt);
        $this->assertNotNull($closed->retentionUntil);

        // PERSONAL type has 10 years retention
        $expectedRetentionUntil = $closedAt->modify('+10 years');
        $this->assertEquals($expectedRetentionUntil, $closed->retentionUntil);
    }

    public function testCloseOnNonOpenThrowsLogicException(): void
    {
        $dossier = Dossier::open(
            id: 'dossier_005',
            tenantId: 42,
            type: DossierType::PERSONAL,
            title: 'Personal Dossier',
            encrypted: true
        );

        $closed = $dossier->close(new DateTimeImmutable());

        $this->expectException(LogicException::class);
        $closed->close(new DateTimeImmutable());
    }

    public function testIsRetentionExpiredChecksCorrectly(): void
    {
        $dossier = Dossier::open(
            id: 'dossier_006',
            tenantId: 42,
            type: DossierType::PERSONAL,
            title: 'Personal Dossier',
            encrypted: true
        );

        $closedAt = new DateTimeImmutable('2015-01-15');
        $closed = $dossier->close($closedAt);

        // Check with current date (2025) - should be expired
        $this->assertTrue($closed->isRetentionExpired(new DateTimeImmutable('2026-01-15')));

        // Check with date before retention expiry
        $this->assertFalse($closed->isRetentionExpired(new DateTimeImmutable('2020-01-15')));
    }
}
