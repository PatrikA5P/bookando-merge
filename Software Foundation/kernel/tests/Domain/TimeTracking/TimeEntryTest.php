<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\TimeTracking;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\TimeTracking\TimeEntry;
use SoftwareFoundation\Kernel\Domain\TimeTracking\TimeEntryType;

final class TimeEntryTest extends TestCase
{
    public function testValidConstruction(): void
    {
        $entry = new TimeEntry(
            id: 'entry_001',
            tenantId: 42,
            employeeId: 'emp_001',
            startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
            endTime: new DateTimeImmutable('2025-01-15T16:30:00+00:00'),
            breakMinutes: 30,
            type: TimeEntryType::REGULAR
        );

        $this->assertInstanceOf(TimeEntry::class, $entry);
        $this->assertSame('entry_001', $entry->id);
    }

    public function testEndTimeBeforeStartTimeThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimeEntry(
            id: 'entry_002',
            tenantId: 42,
            employeeId: 'emp_001',
            startTime: new DateTimeImmutable('2025-01-15T16:00:00+00:00'),
            endTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
            breakMinutes: 30,
            type: TimeEntryType::REGULAR
        );
    }

    public function testNegativeBreakMinutesThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimeEntry(
            id: 'entry_003',
            tenantId: 42,
            employeeId: 'emp_001',
            startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
            endTime: new DateTimeImmutable('2025-01-15T16:00:00+00:00'),
            breakMinutes: -10,
            type: TimeEntryType::REGULAR
        );
    }

    public function testGrossMinutesCalculatesCorrectly(): void
    {
        $entry = new TimeEntry(
            id: 'entry_004',
            tenantId: 42,
            employeeId: 'emp_001',
            startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
            endTime: new DateTimeImmutable('2025-01-15T16:30:00+00:00'),
            breakMinutes: 30,
            type: TimeEntryType::REGULAR
        );

        // 8:00 to 16:30 = 8.5 hours = 510 minutes
        $this->assertSame(510, $entry->grossMinutes());
    }

    public function testNetMinutesSubtractsBreaksCorrectly(): void
    {
        $entry = new TimeEntry(
            id: 'entry_005',
            tenantId: 42,
            employeeId: 'emp_001',
            startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
            endTime: new DateTimeImmutable('2025-01-15T16:30:00+00:00'),
            breakMinutes: 30,
            type: TimeEntryType::REGULAR
        );

        // 510 minutes - 30 minutes break = 480 minutes
        $this->assertSame(480, $entry->netMinutes());
    }

    public function testNetHoursReturnsDecimal(): void
    {
        $entry = new TimeEntry(
            id: 'entry_006',
            tenantId: 42,
            employeeId: 'emp_001',
            startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
            endTime: new DateTimeImmutable('2025-01-15T16:00:00+00:00'),
            breakMinutes: 0,
            type: TimeEntryType::REGULAR
        );

        // 480 net minutes = 8.0 hours
        $this->assertSame(8.0, $entry->netHours());
    }
}
