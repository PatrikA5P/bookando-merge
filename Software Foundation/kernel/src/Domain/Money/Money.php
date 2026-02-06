<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Money;

/**
 * Immutable Money Value Object.
 *
 * ALL monetary amounts are stored as integer minor units (cents).
 * No floats. Ever. This prevents rounding errors in financial calculations.
 *
 * Examples:
 *   Money::CHF(1050)      → 10.50 CHF
 *   Money::EUR(100)       → 1.00 EUR
 *   Money::JPY(1000)      → 1000 JPY (zero-decimal)
 */
final class Money
{
    private int $amount;
    private Currency $currency;

    public function __construct(int $amountMinorUnits, Currency $currency)
    {
        $this->amount = $amountMinorUnits;
        $this->currency = $currency;
    }

    // --- Named constructors ---

    public static function of(int $amountMinorUnits, Currency $currency): self
    {
        return new self($amountMinorUnits, $currency);
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public static function CHF(int $cents): self
    {
        return new self($cents, Currency::CHF);
    }

    public static function EUR(int $cents): self
    {
        return new self($cents, Currency::EUR);
    }

    public static function USD(int $cents): self
    {
        return new self($cents, Currency::USD);
    }

    /**
     * Create from a display amount (e.g., 10.50 → 1050 cents).
     * Use ONLY at system boundaries (user input, API responses).
     * Internally, always use integer constructors.
     */
    public static function fromDisplay(float|string $displayAmount, Currency $currency): self
    {
        $factor = 10 ** $currency->decimalPlaces();
        $amount = (int) round((float) $displayAmount * $factor);
        return new self($amount, $currency);
    }

    // --- Arithmetic (always returns new instance) ---

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }

    /**
     * Multiply by a scalar (e.g., quantity).
     * Result is rounded to the nearest minor unit using ROUND_HALF_UP.
     */
    public function multiply(int|float $factor): self
    {
        return new self(
            (int) round($this->amount * $factor, 0, PHP_ROUND_HALF_UP),
            $this->currency
        );
    }

    /**
     * Allocate money across N parts, distributing remainder fairly.
     * The sum of all parts always equals the original amount (no pennies lost).
     *
     * Example: Money::CHF(100)->allocate(3) → [34, 33, 33]
     *
     * @param int $parts Number of parts (must be > 0)
     * @return self[]
     */
    public function allocate(int $parts): array
    {
        if ($parts <= 0) {
            throw new \InvalidArgumentException('Parts must be greater than 0');
        }

        $base = intdiv($this->amount, $parts);
        $remainder = abs($this->amount) % $parts;
        $sign = $this->amount >= 0 ? 1 : -1;

        $result = [];
        for ($i = 0; $i < $parts; $i++) {
            $extra = ($i < $remainder) ? $sign : 0;
            $result[] = new self($base + $extra, $this->currency);
        }

        return $result;
    }

    /**
     * Apply a percentage (e.g., tax rate).
     * percentage is in basis points or as float: 8.1% → 0.081
     *
     * Example: Money::CHF(10000)->applyPercentage(0.081) → 810 (8.1% of 100.00 CHF)
     */
    public function applyPercentage(float $percentage): self
    {
        return new self(
            (int) round($this->amount * $percentage, 0, PHP_ROUND_HALF_UP),
            $this->currency
        );
    }

    public function negate(): self
    {
        return new self(-$this->amount, $this->currency);
    }

    public function abs(): self
    {
        return new self(abs($this->amount), $this->currency);
    }

    // --- Comparison ---

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount >= $other->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    // --- Accessors ---

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    /**
     * Format for display purposes.
     * Example: Money::CHF(1050)->toDisplay() → "10.50"
     */
    public function toDisplay(): string
    {
        $places = $this->currency->decimalPlaces();
        if ($places === 0) {
            return (string) $this->amount;
        }
        $factor = 10 ** $places;
        return number_format($this->amount / $factor, $places, '.', '');
    }

    public function __toString(): string
    {
        return $this->toDisplay() . ' ' . $this->currency->value;
    }

    /** Serialize for database/JSON storage. */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->value,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['amount'],
            Currency::from((string) $data['currency'])
        );
    }

    // --- Guards ---

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException(
                "Cannot operate on {$this->currency->value} and {$other->currency->value}"
            );
        }
    }
}
