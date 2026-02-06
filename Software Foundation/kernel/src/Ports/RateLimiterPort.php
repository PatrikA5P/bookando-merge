<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\RateLimit\RateLimit;

interface RateLimiterPort
{
    /**
     * Increments the counter for the given key and returns the current state.
     */
    public function attempt(string $key, int $maxRequests, int $windowSeconds): RateLimit;

    /**
     * Checks the current state without incrementing.
     */
    public function check(string $key, int $maxRequests, int $windowSeconds): RateLimit;

    /**
     * Resets the counter for the given key.
     */
    public function reset(string $key): void;
}
