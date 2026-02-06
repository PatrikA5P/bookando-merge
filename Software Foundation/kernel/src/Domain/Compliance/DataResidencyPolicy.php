<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Compliance;

/**
 * Immutable data residency policy defining where tenant data may be stored.
 *
 * Swiss law (DSG) and EU law (DSGVO) impose strict rules on where personal
 * and financial data can be stored and whether cross-border transfers are
 * permitted. This value object captures those constraints per deployment.
 *
 * INVARIANTS:
 * - country is a 2-letter uppercase ISO 3166-1 alpha-2 code
 * - allowedStorageProviders is a non-empty list for production use
 */
final class DataResidencyPolicy
{
    private string $country;
    /** @var string[] */
    private array $allowedStorageProviders;
    private bool $requiresEncryption;
    private bool $allowsCrossBorderTransfer;
    private string $legalBasis;

    /**
     * @param string   $country                   ISO 3166-1 alpha-2 country code
     * @param string[] $allowedStorageProviders    List of permitted storage providers
     * @param bool     $requiresEncryption         Whether data must be encrypted at rest
     * @param bool     $allowsCrossBorderTransfer  Whether data may leave the country/region
     * @param string   $legalBasis                 Legal basis for the policy
     */
    public function __construct(
        string $country,
        array $allowedStorageProviders,
        bool $requiresEncryption,
        bool $allowsCrossBorderTransfer,
        string $legalBasis,
    ) {
        if (!preg_match('/^[A-Z]{2}$/', $country)) {
            throw new \InvalidArgumentException(
                "country must be a 2-letter uppercase ISO 3166-1 alpha-2 code, got '{$country}'"
            );
        }

        $this->country = $country;
        $this->allowedStorageProviders = $allowedStorageProviders;
        $this->requiresEncryption = $requiresEncryption;
        $this->allowsCrossBorderTransfer = $allowsCrossBorderTransfer;
        $this->legalBasis = $legalBasis;
    }

    /**
     * Default policy for Switzerland: data stays in CH, encrypted, no cross-border.
     */
    public static function switzerland(): self
    {
        return new self(
            'CH',
            ['exoscale', 'infomaniak', 'azure_ch'],
            true,
            false,
            'DSG Art. 16',
        );
    }

    /**
     * Default policy for EU: data stays within EU, encrypted, cross-border within EU allowed.
     */
    public static function eu(): self
    {
        return new self(
            'EU',
            ['aws_eu', 'azure_eu', 'gcp_eu', 'hetzner'],
            true,
            true,
            'DSGVO Art. 44-49',
        );
    }

    public function country(): string
    {
        return $this->country;
    }

    /**
     * @return string[]
     */
    public function allowedStorageProviders(): array
    {
        return $this->allowedStorageProviders;
    }

    public function requiresEncryption(): bool
    {
        return $this->requiresEncryption;
    }

    public function allowsCrossBorderTransfer(): bool
    {
        return $this->allowsCrossBorderTransfer;
    }

    public function legalBasis(): string
    {
        return $this->legalBasis;
    }

    /**
     * @return array{country: string, allowed_storage_providers: string[], requires_encryption: bool, allows_cross_border_transfer: bool, legal_basis: string}
     */
    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'allowed_storage_providers' => $this->allowedStorageProviders,
            'requires_encryption' => $this->requiresEncryption,
            'allows_cross_border_transfer' => $this->allowsCrossBorderTransfer,
            'legal_basis' => $this->legalBasis,
        ];
    }
}
