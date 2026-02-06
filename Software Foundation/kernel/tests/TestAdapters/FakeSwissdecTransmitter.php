<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Payroll\SalaryDeclaration;
use SoftwareFoundation\Kernel\Domain\Payroll\SwissdecDomain;
use SoftwareFoundation\Kernel\Ports\SwissdecTransmitterPort;

/**
 * Fake test implementation of SwissdecTransmitterPort.
 *
 * Returns pre-configured responses for testing without
 * actual Swissdec transmission or XML generation.
 */
final class FakeSwissdecTransmitter implements SwissdecTransmitterPort
{
    /**
     * @var array<string, array{status: string, domain_results: array<string, array{accepted: bool, message: string}>}>
     */
    private array $transmissionStatuses = [];

    public function validate(SalaryDeclaration $declaration): array
    {
        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    public function generateXml(SalaryDeclaration $declaration): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<SalaryDeclaration version="5.5" xmlns="http://www.swissdec.ch/schema/elm/5.5">' . "\n" .
            '  <DeclarationId>%s</DeclarationId>' . "\n" .
            '  <TenantId>%d</TenantId>' . "\n" .
            '  <EmployeeId>%s</EmployeeId>' . "\n" .
            '  <Year>%d</Year>' . "\n" .
            '  <TotalGross>%d</TotalGross>' . "\n" .
            '  <!-- This is a placeholder XML for testing purposes -->' . "\n" .
            '</SalaryDeclaration>',
            $declaration->id,
            $declaration->tenantId,
            $declaration->employeeId,
            $declaration->year,
            $declaration->totalGross(),
        );
    }

    public function transmit(SalaryDeclaration $declaration): array
    {
        $transmissionId = 'tx_' . $declaration->id;

        // Store status for later retrieval
        $this->transmissionStatuses[$transmissionId] = [
            'status' => 'accepted',
            'domain_results' => $this->buildDomainResults($declaration),
        ];

        return [
            'success' => true,
            'transmissionId' => $transmissionId,
            'errors' => [],
        ];
    }

    public function getTransmissionStatus(string $transmissionId): array
    {
        if (isset($this->transmissionStatuses[$transmissionId])) {
            return $this->transmissionStatuses[$transmissionId];
        }

        // Default status for unknown transmissions
        return [
            'status' => 'accepted',
            'domain_results' => [],
        ];
    }

    public function supportedElmVersion(): string
    {
        return '5.5';
    }

    public function supportedDomains(): array
    {
        return [
            SwissdecDomain::AHV_IV_EO,
            SwissdecDomain::ALV,
            SwissdecDomain::BVG,
            SwissdecDomain::UVG,
            SwissdecDomain::KTG,
            SwissdecDomain::QUELLENSTEUER,
            SwissdecDomain::STATISTIK_BFS,
        ];
    }

    /**
     * Build fake domain results for a declaration.
     *
     * @return array<string, array{accepted: bool, message: string}>
     */
    private function buildDomainResults(SalaryDeclaration $declaration): array
    {
        $results = [];

        foreach ($declaration->domains as $domain) {
            $results[$domain->value] = [
                'accepted' => true,
                'message' => sprintf('Declaration accepted for %s', $domain->label()),
            ];
        }

        return $results;
    }

    /**
     * Clear all stored transmission statuses (for test isolation).
     */
    public function clear(): void
    {
        $this->transmissionStatuses = [];
    }
}
