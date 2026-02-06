<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Currency\ExchangeRate;

interface ExchangeRatePort
{
    /**
     * Gets the exchange rate between two currencies.
     * A null date means the latest available rate.
     */
    public function getRate(string $from, string $to, ?\DateTimeImmutable $date = null): ExchangeRate;

    /**
     * Converts an amount in minor units from one currency to another.
     *
     * @param int $amountMinorUnits Amount in minor units (e.g. cents)
     * @param string $from          Source currency code (ISO 4217)
     * @param string $to            Target currency code (ISO 4217)
     * @param int $fromDecimals     Decimal places of the source currency
     * @param int $toDecimals       Decimal places of the target currency
     * @param \DateTimeImmutable|null $date Date for the rate; null = latest
     *
     * @return int Converted amount in minor units of the target currency
     */
    public function convert(
        int $amountMinorUnits,
        string $from,
        string $to,
        int $fromDecimals,
        int $toDecimals,
        ?\DateTimeImmutable $date = null,
    ): int;

    /**
     * Returns the list of supported currency codes.
     *
     * @return string[]
     */
    public function availableCurrencies(): array;
}
