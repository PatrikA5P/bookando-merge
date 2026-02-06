<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Currency;

final class ExchangeRate
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly float $rate,
        public readonly \DateTimeImmutable $date,
        public readonly string $source,
    ) {
        if (!preg_match('/\A[A-Z]{3}\z/', $this->from)) {
            throw new \InvalidArgumentException(
                sprintf('Currency code "from" must be 3 uppercase letters, got "%s".', $this->from)
            );
        }

        if (!preg_match('/\A[A-Z]{3}\z/', $this->to)) {
            throw new \InvalidArgumentException(
                sprintf('Currency code "to" must be 3 uppercase letters, got "%s".', $this->to)
            );
        }

        if ($this->rate <= 0.0) {
            throw new \InvalidArgumentException(
                sprintf('Exchange rate must be greater than 0, got %f.', $this->rate)
            );
        }
    }

    /**
     * Converts an amount in minor units from one currency to another using this rate.
     *
     * @param int $amountMinorUnits Amount in minor units (e.g. cents)
     * @param int $fromDecimals     Number of decimal places in the source currency
     * @param int $toDecimals       Number of decimal places in the target currency
     *
     * @return int Converted amount in minor units of the target currency
     */
    public function convert(int $amountMinorUnits, int $fromDecimals, int $toDecimals): int
    {
        $majorAmount = $amountMinorUnits / (10 ** $fromDecimals);
        $converted = $majorAmount * $this->rate;
        $minorConverted = $converted * (10 ** $toDecimals);

        return (int) round($minorConverted, 0, \PHP_ROUND_HALF_UP);
    }

    /**
     * Returns the inverse exchange rate (swap from/to, rate = 1/rate).
     */
    public function inverse(): self
    {
        return new self(
            from: $this->to,
            to: $this->from,
            rate: 1.0 / $this->rate,
            date: $this->date,
            source: $this->source,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'rate' => $this->rate,
            'date' => $this->date->format('Y-m-d'),
            'source' => $this->source,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            from: $data['from'],
            to: $data['to'],
            rate: (float) $data['rate'],
            date: new \DateTimeImmutable($data['date']),
            source: $data['source'],
        );
    }
}
