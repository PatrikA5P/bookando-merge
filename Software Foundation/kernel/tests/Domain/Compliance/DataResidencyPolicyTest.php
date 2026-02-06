<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Domain\Compliance;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Domain\Compliance\DataResidencyPolicy;

final class DataResidencyPolicyTest extends TestCase
{
    public function test_switzerland_policy(): void
    {
        $policy = DataResidencyPolicy::switzerland();

        $this->assertSame('CH', $policy->country());
        $this->assertTrue($policy->requiresEncryption());
        $this->assertFalse($policy->allowsCrossBorderTransfer());
        $this->assertSame('DSG Art. 16', $policy->legalBasis());
        $this->assertContains('exoscale', $policy->allowedStorageProviders());
        $this->assertContains('infomaniak', $policy->allowedStorageProviders());
        $this->assertContains('azure_ch', $policy->allowedStorageProviders());
    }

    public function test_eu_policy(): void
    {
        $policy = DataResidencyPolicy::eu();

        $this->assertSame('EU', $policy->country());
        $this->assertTrue($policy->requiresEncryption());
        $this->assertTrue($policy->allowsCrossBorderTransfer());
        $this->assertSame('DSGVO Art. 44-49', $policy->legalBasis());
        $this->assertContains('aws_eu', $policy->allowedStorageProviders());
        $this->assertContains('hetzner', $policy->allowedStorageProviders());
    }

    public function test_rejects_invalid_country(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DataResidencyPolicy(
            country: 'ch', // lowercase — must be uppercase
            allowedStorageProviders: ['test'],
            requiresEncryption: true,
            allowsCrossBorderTransfer: false,
            legalBasis: 'Test',
        );
    }
}
