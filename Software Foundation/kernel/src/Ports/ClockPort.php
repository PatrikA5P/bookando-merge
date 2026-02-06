<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Time abstraction port.
 *
 * Provides a host-agnostic, deterministic interface for obtaining the current
 * time. Using this port instead of calling `new \DateTimeImmutable()` directly
 * allows the kernel and all modules to remain fully testable; tests can inject
 * a frozen or advancing clock implementation.
 *
 * All default operations return times in UTC.
 */
interface ClockPort
{
    /**
     * Return the current date-time in UTC.
     *
     * @return \DateTimeImmutable Current instant with the UTC timezone.
     */
    public function now(): \DateTimeImmutable;

    /**
     * Return the current date-time converted to the given timezone.
     *
     * @param \DateTimeZone $tz Target timezone.
     *
     * @return \DateTimeImmutable Current instant expressed in `$tz`.
     */
    public function nowIn(\DateTimeZone $tz): \DateTimeImmutable;

    /**
     * Return the current UTC date-time as an ISO-8601 string.
     *
     * The format is `Y-m-d\TH:i:s\Z` (e.g. "2026-02-06T14:30:00Z").
     *
     * @return string ISO-8601 formatted UTC timestamp.
     */
    public function nowUtcString(): string;
}
