<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Integrity;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Integrity\IntegrityCheckResult;

final class IntegrityCheckResultTest extends TestCase
{
    public function test_success_result(): void
    {
        $at = new \DateTimeImmutable('2026-01-15T10:00:00Z');
        $result = IntegrityCheckResult::success(100, $at);

        $this->assertTrue($result->passed());
        $this->assertFalse($result->hasFailed());
        $this->assertSame(100, $result->checkedEntries());
        $this->assertSame([], $result->failedEntries());
        $this->assertSame($at, $result->checkedAt());
    }

    public function test_failure_result(): void
    {
        $at = new \DateTimeImmutable('2026-01-15T10:00:00Z');
        $failures = [
            ['sequenceNumber' => 5, 'reason' => 'hash mismatch'],
            ['sequenceNumber' => 12, 'reason' => 'missing predecessor'],
        ];
        $result = IntegrityCheckResult::failure(50, $failures, $at);

        $this->assertFalse($result->passed());
        $this->assertSame(50, $result->checkedEntries());
        $this->assertCount(2, $result->failedEntries());
        $this->assertSame(5, $result->failedEntries()[0]['sequenceNumber']);
        $this->assertSame('hash mismatch', $result->failedEntries()[0]['reason']);
    }

    public function test_has_failed(): void
    {
        $at = new \DateTimeImmutable('2026-01-15T10:00:00Z');

        $success = IntegrityCheckResult::success(10, $at);
        $this->assertFalse($success->hasFailed());

        $failure = IntegrityCheckResult::failure(10, [
            ['sequenceNumber' => 3, 'reason' => 'tampered'],
        ], $at);
        $this->assertTrue($failure->hasFailed());
    }
}
