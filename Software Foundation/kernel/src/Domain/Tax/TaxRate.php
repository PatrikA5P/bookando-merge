<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Tax;

use DateTimeImmutable;
use InvalidArgumentException;

final class TaxRate
{
    public function __construct(
        public readonly float $rate,
        public readonly string $label,
        public readonly string $country,
        public readonly DateTimeImmutable $validFrom,
        public readonly ?DateTimeImmutable $validUntil = null,
    ) {
        if ($this->rate < 0.0) {
            throw new InvalidArgumentException(
                sprintf('Tax rate must be non-negative, got %f.', $this->rate)
            );
        }

        if (!preg_match('/^[A-Z]{2}$/', $this->country)) {
            throw new InvalidArgumentException(
                sprintf('Country must be a 2-letter ISO code, got "%s".', $this->country)
            );
        }
    }

    public static function chfStandard(): self
    {
        return new self(
            rate: 0.081,
            label: 'Standard',
            country: 'CH',
            validFrom: new DateTimeImmutable('2024-01-01'),
        );
    }

    public static function chfReduced(): self
    {
        return new self(
            rate: 0.026,
            label: 'Reduziert',
            country: 'CH',
            validFrom: new DateTimeImmutable('2024-01-01'),
        );
    }

    public static function chfAccommodation(): self
    {
        return new self(
            rate: 0.037,
            label: 'Beherbergung',
            country: 'CH',
            validFrom: new DateTimeImmutable('2024-01-01'),
        );
    }

    public static function deStandard(): self
    {
        return new self(
            rate: 0.19,
            label: 'Standard',
            country: 'DE',
            validFrom: new DateTimeImmutable('2007-01-01'),
        );
    }

    public static function deReduced(): self
    {
        return new self(
            rate: 0.07,
            label: 'Ermässigt',
            country: 'DE',
            validFrom: new DateTimeImmutable('2007-01-01'),
        );
    }

    public function isValidAt(DateTimeImmutable $date): bool
    {
        if ($date < $this->validFrom) {
            return false;
        }

        if ($this->validUntil !== null && $date > $this->validUntil) {
            return false;
        }

        return true;
    }

    public function applyTo(int $amountMinorUnits): int
    {
        return (int) round($amountMinorUnits * $this->rate, 0, PHP_ROUND_HALF_UP);
    }

    public function displayPercentage(): string
    {
        $percent = $this->rate * 100;

        // Remove trailing zeros but keep at least one decimal if needed
        $formatted = rtrim(rtrim(number_format($percent, 4, '.', ''), '0'), '.');

        return $formatted . '%';
    }

    /**
     * @return array{rate: float, label: string, country: string, validFrom: string, validUntil: string|null}
     */
    public function toArray(): array
    {
        return [
            'rate' => $this->rate,
            'label' => $this->label,
            'country' => $this->country,
            'validFrom' => $this->validFrom->format('Y-m-d'),
            'validUntil' => $this->validUntil?->format('Y-m-d'),
        ];
    }

    /**
     * @param array{rate: float, label: string, country: string, validFrom: string, validUntil?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            rate: (float) $data['rate'],
            label: (string) $data['label'],
            country: (string) $data['country'],
            validFrom: new DateTimeImmutable($data['validFrom']),
            validUntil: isset($data['validUntil']) ? new DateTimeImmutable($data['validUntil']) : null,
        );
    }
}
