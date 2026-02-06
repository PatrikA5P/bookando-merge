<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\RateLimit;

final class RateLimit
{
    public function __construct(
        public readonly int $maxRequests,
        public readonly int $windowSeconds,
        public readonly int $currentCount,
        public readonly \DateTimeImmutable $windowStart,
    ) {
    }

    public function isExhausted(): bool
    {
        return $this->currentCount >= $this->maxRequests;
    }

    public function remaining(): int
    {
        return max(0, $this->maxRequests - $this->currentCount);
    }

    /**
     * Returns the number of seconds until the current window resets.
     */
    public function retryAfterSeconds(\DateTimeImmutable $now): int
    {
        $windowEnd = $this->windowStart->modify('+' . $this->windowSeconds . ' seconds');
        $diff = $windowEnd->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }

    /**
     * Returns a new instance with currentCount incremented by 1.
     */
    public function withIncrement(): self
    {
        return new self(
            maxRequests: $this->maxRequests,
            windowSeconds: $this->windowSeconds,
            currentCount: $this->currentCount + 1,
            windowStart: $this->windowStart,
        );
    }

    /**
     * Creates a rate limit configured for per-minute windows.
     */
    public static function perMinute(int $max): self
    {
        return new self(
            maxRequests: $max,
            windowSeconds: 60,
            currentCount: 0,
            windowStart: new \DateTimeImmutable(),
        );
    }

    /**
     * Creates a rate limit configured for per-hour windows.
     */
    public static function perHour(int $max): self
    {
        return new self(
            maxRequests: $max,
            windowSeconds: 3600,
            currentCount: 0,
            windowStart: new \DateTimeImmutable(),
        );
    }
}
