<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\TimeTracking;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\TimeTracking\TimeEntry;
use SoftwareFoundation\Kernel\Domain\TimeTracking\TimeEntryType;
use SoftwareFoundation\Kernel\Domain\TimeTracking\WorkTimeValidation;

final class WorkTimeValidationTest extends TestCase
{
    public function testPassCreatesCompliantResult(): void
    {
        $result = WorkTimeValidation::pass();

        $this->assertTrue($result->isCompliant);
        $this->assertEmpty($result->violations);
    }

    public function testFailCreatesNonCompliantResult(): void
    {
        $result = WorkTimeValidation::fail(['Excessive work hours']);

        $this->assertFalse($result->isCompliant);
        $this->assertCount(1, $result->violations);
        $this->assertSame('Excessive work hours', $result->violations[0]);
    }

    public function testValidateDayWithMoreThan9HoursAndLessThan60MinBreakReturnsViolation(): void
    {
        $entries = [
            new TimeEntry(
                id: 'entry_001',
                tenantId: 42,
                employeeId: 'emp_001',
                startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
                endTime: new DateTimeImmutable('2025-01-15T18:00:00+00:00'),
                breakMinutes: 45, // Less than 60 minutes
                type: TimeEntryType::REGULAR
            ),
        ];

        $result = WorkTimeValidation::validateDay($entries);

        $this->assertFalse($result->isCompliant);
        $this->assertNotEmpty($result->violations);
    }

    public function testValidateDayWithMoreThan7HoursAndLessThan30MinBreakReturnsViolation(): void
    {
        $entries = [
            new TimeEntry(
                id: 'entry_002',
                tenantId: 42,
                employeeId: 'emp_001',
                startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
                endTime: new DateTimeImmutable('2025-01-15T16:00:00+00:00'),
                breakMinutes: 20, // Less than 30 minutes
                type: TimeEntryType::REGULAR
            ),
        ];

        $result = WorkTimeValidation::validateDay($entries);

        $this->assertFalse($result->isCompliant);
        $this->assertNotEmpty($result->violations);
    }

    public function testValidateDayWithProperBreaksReturnsPass(): void
    {
        $entries = [
            new TimeEntry(
                id: 'entry_003',
                tenantId: 42,
                employeeId: 'emp_001',
                startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
                endTime: new DateTimeImmutable('2025-01-15T16:30:00+00:00'),
                breakMinutes: 30,
                type: TimeEntryType::REGULAR
            ),
        ];

        $result = WorkTimeValidation::validateDay($entries);

        $this->assertTrue($result->isCompliant);
        $this->assertEmpty($result->violations);
    }

    public function testValidateDayWithMoreThan2HoursOvertimeReturnsViolation(): void
    {
        $entries = [
            new TimeEntry(
                id: 'entry_004',
                tenantId: 42,
                employeeId: 'emp_001',
                startTime: new DateTimeImmutable('2025-01-15T08:00:00+00:00'),
                endTime: new DateTimeImmutable('2025-01-15T19:00:00+00:00'),
                breakMinutes: 60,
                type: TimeEntryType::REGULAR
            ),
        ];

        // 11 hours gross - 1 hour break = 10 hours net
        // Assuming 8 hours is standard, this is 2+ hours overtime
        $result = WorkTimeValidation::validateDay($entries);

        $this->assertFalse($result->isCompliant);
        $this->assertNotEmpty($result->violations);
    }
}
