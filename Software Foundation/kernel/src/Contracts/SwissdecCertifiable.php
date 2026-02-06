<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Contracts;

use SoftwareFoundation\Kernel\Domain\Payroll\SwissdecDomain;

/**
 * Contract for modules that participate in Swissdec ELM certification.
 *
 * Swissdec certification is a formal process where the Swissdec association
 * validates that payroll software correctly generates and transmits ELM data.
 *
 * Modules implementing this contract declare which Swissdec domains they
 * support and provide data in the correct format for XML generation.
 *
 * @see https://swissdec.ch/elm
 */
interface SwissdecCertifiable
{
    /**
     * Swissdec domains this module provides data for.
     *
     * @return SwissdecDomain[]
     */
    public function supportedDomains(): array;

    /**
     * The ELM version this module targets.
     */
    public function targetElmVersion(): string;

    /**
     * Run self-validation of Swissdec data completeness.
     *
     * @return array{valid: bool, errors: string[]}
     */
    public function validateCompleteness(int $tenantId, int $year): array;
}
