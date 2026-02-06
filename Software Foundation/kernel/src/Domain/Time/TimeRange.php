<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Time;

/**
 * Immutable UTC time range.
 *
 * INVARIANTS:
 * - Start and end are always UTC
 * - Start must be before or equal to end
 * - All calculations are DST-safe (using DateTimeImmutable)
 */
final class TimeRange
{
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    public function __construct(\DateTimeImmutable $startUtc, \DateTimeImmutable $endUtc)
    {
        $utc = new \DateTimeZone('UTC');

        // Normalize to UTC
        $this->start = $startUtc->setTimezone($utc);
        $this->end = $endUtc->setTimezone($utc);

        if ($this->start > $this->end) {
            throw new \InvalidArgumentException(
                "TimeRange start ({$this->start->format('c')}) must be before end ({$this->end->format('c')})"
            );
        }
    }

    public static function fromStrings(string $startUtc, string $endUtc): self
    {
        $utc = new \DateTimeZone('UTC');
        return new self(
            new \DateTimeImmutable($startUtc, $utc),
            new \DateTimeImmutable($endUtc, $utc)
        );
    }

    /**
     * Create from local times + timezone (DST-safe conversion to UTC).
     * Use this at system boundaries when receiving user input with timezone.
     */
    public static function fromLocal(
        string $startLocal,
        string $endLocal,
        string $ianaTimezone
    ): self {
        $tz = new \DateTimeZone($ianaTimezone);
        $utc = new \DateTimeZone('UTC');

        $start = (new \DateTimeImmutable($startLocal, $tz))->setTimezone($utc);
        $end = (new \DateTimeImmutable($endLocal, $tz))->setTimezone($utc);

        return new self($start, $end);
    }

    /** Check if this range overlaps with another. */
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end && $other->start < $this->end;
    }

    /** Check if this range fully contains another. */
    public function contains(self $other): bool
    {
        return $this->start <= $other->start && $this->end >= $other->end;
    }

    /** Check if a point in time falls within this range. */
    public function containsPoint(\DateTimeImmutable $point): bool
    {
        $utcPoint = $point->setTimezone(new \DateTimeZone('UTC'));
        return $utcPoint >= $this->start && $utcPoint < $this->end;
    }

    /** Check if there is enough gap after this range before another starts. */
    public function hasMinimumGapBefore(self $next, int $minimumMinutes): bool
    {
        $gapSeconds = $next->start->getTimestamp() - $this->end->getTimestamp();
        return $gapSeconds >= ($minimumMinutes * 60);
    }

    /** Duration in minutes. */
    public function durationMinutes(): int
    {
        return (int) (($this->end->getTimestamp() - $this->start->getTimestamp()) / 60);
    }

    /** Duration in seconds. */
    public function durationSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    public function start(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): \DateTimeImmutable
    {
        return $this->end;
    }

    public function startString(): string
    {
        return $this->start->format('Y-m-d H:i:s');
    }

    public function endString(): string
    {
        return $this->end->format('Y-m-d H:i:s');
    }

    /** Display in a specific timezone (for UI). */
    public function toLocal(string $ianaTimezone): array
    {
        $tz = new \DateTimeZone($ianaTimezone);
        return [
            'start' => $this->start->setTimezone($tz)->format('Y-m-d H:i:s'),
            'end' => $this->end->setTimezone($tz)->format('Y-m-d H:i:s'),
            'timezone' => $ianaTimezone,
        ];
    }

    public function equals(self $other): bool
    {
        return $this->start == $other->start && $this->end == $other->end;
    }

    public function toArray(): array
    {
        return [
            'start_utc' => $this->startString(),
            'end_utc' => $this->endString(),
        ];
    }
}
