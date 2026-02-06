<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Contracts;

/**
 * Contract that modules implement to provide country-specific tax rules.
 *
 * Each tax jurisdiction (Switzerland, Germany, Austria, etc.) provides its own
 * implementation with the applicable rates, categories, and calculation logic.
 */
interface TaxRule
{
    /**
     * ISO 3166-1 alpha-2 country code this rule applies to.
     */
    public function country(): string;

    /**
     * List of tax categories supported (e.g., 'standard', 'reduced', 'accommodation').
     *
     * @return string[]
     */
    public function categories(): array;

    /**
     * Resolve the tax rate for a given category at a specific date.
     *
     * @param string              $category Tax category slug.
     * @param \DateTimeImmutable  $date     Date for which the rate is requested.
     *
     * @return array{rate: float, label: string} Rate as decimal (0.081 = 8.1%) and label.
     *
     * @throws \InvalidArgumentException If the category is not supported.
     */
    public function rateForCategory(string $category, \DateTimeImmutable $date): array;

    /**
     * Whether reverse charge applies for cross-border B2B transactions.
     */
    public function supportsReverseCharge(): bool;
}
