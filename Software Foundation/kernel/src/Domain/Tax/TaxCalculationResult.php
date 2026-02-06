<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Tax;

use InvalidArgumentException;

final class TaxCalculationResult
{
    public function __construct(
        public readonly int $netAmount,
        public readonly int $taxAmount,
        public readonly int $grossAmount,
        public readonly TaxRate $rate,
        public readonly string $currency,
    ) {
        if ($this->grossAmount !== $this->netAmount + $this->taxAmount) {
            throw new InvalidArgumentException(
                sprintf(
                    'grossAmount (%d) must equal netAmount (%d) + taxAmount (%d).',
                    $this->grossAmount,
                    $this->netAmount,
                    $this->taxAmount,
                )
            );
        }
    }

    public static function fromNet(int $net, TaxRate $rate, string $currency): self
    {
        $taxAmount = $rate->applyTo($net);

        return new self(
            netAmount: $net,
            taxAmount: $taxAmount,
            grossAmount: $net + $taxAmount,
            rate: $rate,
            currency: $currency,
        );
    }

    public static function fromGross(int $gross, TaxRate $rate, string $currency): self
    {
        $netAmount = (int) round($gross / (1 + $rate->rate), 0, PHP_ROUND_HALF_UP);
        $taxAmount = $gross - $netAmount;

        return new self(
            netAmount: $netAmount,
            taxAmount: $taxAmount,
            grossAmount: $gross,
            rate: $rate,
            currency: $currency,
        );
    }

    /**
     * @return array{netAmount: int, taxAmount: int, grossAmount: int, rate: array, currency: string}
     */
    public function toArray(): array
    {
        return [
            'netAmount' => $this->netAmount,
            'taxAmount' => $this->taxAmount,
            'grossAmount' => $this->grossAmount,
            'rate' => $this->rate->toArray(),
            'currency' => $this->currency,
        ];
    }
}
