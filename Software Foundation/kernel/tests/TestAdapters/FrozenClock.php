<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Ports\ClockPort;

/**
 * Deterministic clock for testing. Always returns the frozen time.
 */
final class FrozenClock implements ClockPort
{
    private \DateTimeImmutable $frozen;

    public function __construct(?\DateTimeImmutable $frozen = null)
    {
        $this->frozen = $frozen ?? new \DateTimeImmutable('2026-01-15T10:00:00Z');
    }

    public static function at(string $datetime): self
    {
        return new self(new \DateTimeImmutable($datetime, new \DateTimeZone('UTC')));
    }

    public function now(): \DateTimeImmutable
    {
        return $this->frozen;
    }

    public function nowIn(\DateTimeZone $tz): \DateTimeImmutable
    {
        return $this->frozen->setTimezone($tz);
    }

    public function nowUtcString(): string
    {
        return $this->frozen->format('Y-m-d\TH:i:s\Z');
    }

    /** Advance the frozen clock by the given interval. */
    public function advance(string $interval): void
    {
        $this->frozen = $this->frozen->modify($interval);
    }

    /** Set the clock to a new time. */
    public function setTo(\DateTimeImmutable $time): void
    {
        $this->frozen = $time;
    }
}
