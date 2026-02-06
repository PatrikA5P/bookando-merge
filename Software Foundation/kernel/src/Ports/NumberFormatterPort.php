<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

interface NumberFormatterPort
{
    /**
     * Format a number with locale-specific separators.
     *
     * Example: 1000.5 with decimals=2, locale "de-CH" -> "1'000.50"
     *
     * @param int|float   $value    The number to format
     * @param int         $decimals Number of decimal places
     * @param string|null $locale   BCP-47 locale tag, null for current locale
     */
    public function formatNumber(int|float $value, int $decimals = 2, ?string $locale = null): string;

    /**
     * Format an amount in minor units as a currency string.
     *
     * Example: 1050, "CHF", "de-CH" -> "CHF 10.50"
     *
     * @param int         $minorUnits   Amount in minor units (e.g. cents)
     * @param string      $currencyCode ISO 4217 currency code
     * @param string|null $locale       BCP-47 locale tag, null for current locale
     */
    public function formatCurrency(int $minorUnits, string $currencyCode, ?string $locale = null): string;

    /**
     * Format a decimal value as a percentage string.
     *
     * Example: 0.081 with decimals=1 -> "8.1%"
     *
     * @param float       $value    Decimal value (e.g. 0.081 for 8.1%)
     * @param int         $decimals Number of decimal places
     * @param string|null $locale   BCP-47 locale tag, null for current locale
     */
    public function formatPercent(float $value, int $decimals = 1, ?string $locale = null): string;

    /**
     * Parse a formatted number string back to a float.
     *
     * @param string      $formatted Locale-formatted number string
     * @param string|null $locale    BCP-47 locale tag, null for current locale
     */
    public function parseNumber(string $formatted, ?string $locale = null): float;
}
