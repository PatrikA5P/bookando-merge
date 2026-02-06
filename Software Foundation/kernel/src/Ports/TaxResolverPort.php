<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use DateTimeImmutable;
use SoftwareFoundation\Kernel\Domain\Tax\TaxCalculationResult;
use SoftwareFoundation\Kernel\Domain\Tax\TaxRate;

interface TaxResolverPort
{
    /**
     * Resolve the applicable tax rate for a given country, category, and date.
     *
     * @param string             $country  ISO 3166-1 alpha-2 country code
     * @param string             $category Tax category (e.g. 'standard', 'reduced', 'accommodation')
     * @param DateTimeImmutable  $date     Date for which to resolve the rate
     */
    public function resolve(string $country, string $category, DateTimeImmutable $date): TaxRate;

    /**
     * Get all tax rates for a country valid at a specific date.
     *
     * @param string            $country ISO 3166-1 alpha-2 country code
     * @param DateTimeImmutable $date    Date for which to retrieve rates
     * @return TaxRate[]
     */
    public function allRatesForCountry(string $country, DateTimeImmutable $date): array;

    /**
     * Calculate tax from a net amount in minor units.
     *
     * @param int               $netMinorUnits Net amount in minor units (e.g. cents)
     * @param string            $country       ISO 3166-1 alpha-2 country code
     * @param string            $category      Tax category
     * @param string            $currency      ISO 4217 currency code
     * @param DateTimeImmutable $date          Date for which to calculate
     */
    public function calculateFromNet(
        int $netMinorUnits,
        string $country,
        string $category,
        string $currency,
        DateTimeImmutable $date,
    ): TaxCalculationResult;

    /**
     * Calculate tax from a gross amount in minor units.
     *
     * @param int               $grossMinorUnits Gross amount in minor units (e.g. cents)
     * @param string            $country         ISO 3166-1 alpha-2 country code
     * @param string            $category        Tax category
     * @param string            $currency        ISO 4217 currency code
     * @param DateTimeImmutable $date            Date for which to calculate
     */
    public function calculateFromGross(
        int $grossMinorUnits,
        string $country,
        string $category,
        string $currency,
        DateTimeImmutable $date,
    ): TaxCalculationResult;
}
