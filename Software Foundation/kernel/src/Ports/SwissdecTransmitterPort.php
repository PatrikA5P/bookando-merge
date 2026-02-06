<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Payroll\SalaryDeclaration;
use SoftwareFoundation\Kernel\Domain\Payroll\SwissdecDomain;

/**
 * Port for Swissdec ELM (Einheitliches Lohnmeldeverfahren) transmission.
 *
 * Swissdec ELM 5.0/5.5 enables standardized electronic transmission of
 * payroll data to Swiss social insurance agencies and tax authorities.
 *
 * Data flow:
 * Payroll System → XML Generation → Local Validation →
 * SUA Authentication → TLS Encrypted Transmission →
 * Swissdec Distributor → Recipients (AHV, BVG, UVG, Tax, BFS)
 *
 * Technical requirements:
 * - TLS 1.2/1.3 for encrypted transmission
 * - SUA (Swissdec Unternehmens-Authentifizierung) X.509 certificates
 * - XML validation against official Swissdec XSD schemas
 * - ELM 5.3+ mandatory for Quellensteuer (as of January 2026)
 *
 * IMPORTANT: The actual Swissdec-certified implementation requires
 * formal certification by the Swissdec association. This port defines
 * the interface; the adapter implements the certified logic.
 */
interface SwissdecTransmitterPort
{
    /**
     * Validate a salary declaration against Swissdec XSD schemas.
     *
     * @return array{valid: bool, errors: string[]}
     */
    public function validate(SalaryDeclaration $declaration): array;

    /**
     * Generate Swissdec-compliant XML for a salary declaration.
     */
    public function generateXml(SalaryDeclaration $declaration): string;

    /**
     * Transmit a validated declaration to the Swissdec Distributor.
     *
     * @return array{success: bool, transmissionId: string, errors: string[]}
     */
    public function transmit(SalaryDeclaration $declaration): array;

    /**
     * Get the transmission status for a declaration.
     *
     * @return array{status: string, domain_results: array<string, array{accepted: bool, message: string}>}
     */
    public function getTransmissionStatus(string $transmissionId): array;

    /**
     * Check which ELM version the adapter supports.
     */
    public function supportedElmVersion(): string;

    /**
     * Get supported Swissdec domains.
     *
     * @return SwissdecDomain[]
     */
    public function supportedDomains(): array;
}
