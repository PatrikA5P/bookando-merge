<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Compliance;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Compliance\RetentionCategory;
use SoftwareFoundation\Kernel\Domain\Compliance\RetentionPolicy;

final class RetentionPolicyTest extends TestCase
{
    // --- Named constructors ---

    public function test_financial_policy(): void
    {
        $policy = RetentionPolicy::financial();

        $this->assertSame(RetentionCategory::FINANCIAL_10Y, $policy->category());
        $this->assertSame(10, $policy->retentionYears());
        $this->assertTrue($policy->autoArchive());
        $this->assertFalse($policy->autoDelete());
        $this->assertSame('OR Art. 958f / GeBüV', $policy->reason());
    }

    public function test_personal_policy(): void
    {
        $policy = RetentionPolicy::personal();

        $this->assertSame(RetentionCategory::PERSONAL_DSG, $policy->category());
        $this->assertSame(3, $policy->retentionYears());
        $this->assertFalse($policy->autoArchive());
        $this->assertTrue($policy->autoDelete());
        $this->assertSame('DSG Art. 6', $policy->reason());
    }

    // --- Expiration ---

    public function test_is_expired_after_retention_period(): void
    {
        $policy = RetentionPolicy::financial(); // 10 years

        $createdAt = new \DateTimeImmutable('2014-01-01');
        $now = new \DateTimeImmutable('2025-01-01'); // 11 years later

        $this->assertTrue($policy->isExpired($createdAt, $now));
    }

    public function test_is_not_expired_within_retention_period(): void
    {
        $policy = RetentionPolicy::financial(); // 10 years

        $createdAt = new \DateTimeImmutable('2020-01-01');
        $now = new \DateTimeImmutable('2025-01-01'); // 5 years later

        $this->assertFalse($policy->isExpired($createdAt, $now));
    }
}
