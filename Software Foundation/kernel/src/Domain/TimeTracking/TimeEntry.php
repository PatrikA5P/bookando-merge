<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\TimeTracking;

/**
 * Immutable time tracking entry compliant with ArG Art. 46 / ArGV 1 Art. 73.
 *
 * Swiss Arbeitsgesetz (ArG) requires recording:
 * - Duration, start, and end of daily/weekly working time
 * - Compensatory and overtime work
 * - Breaks of 30 minutes or more
 *
 * Records must be retained for 5 years (ArG Art. 46).
 */
final class TimeEntry
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $userId,
        public readonly \DateTimeImmutable $date,
        public readonly TimeEntryType $type,
        public readonly \DateTimeImmutable $startTime,
        public readonly \DateTimeImmutable $endTime,
        public readonly int $breakMinutes,
        public readonly ?string $projectId,
        public readonly ?string $note,
    ) {
        if ($this->endTime <= $this->startTime) {
            throw new \InvalidArgumentException('End time must be after start time.');
        }

        if ($this->breakMinutes < 0) {
            throw new \InvalidArgumentException('Break minutes must not be negative.');
        }
    }

    /**
     * Gross duration in minutes (including breaks).
     */
    public function grossMinutes(): int
    {
        $diff = $this->startTime->diff($this->endTime);
        return ($diff->h * 60) + $diff->i + ($diff->days * 1440);
    }

    /**
     * Net working time in minutes (excluding breaks).
     */
    public function netMinutes(): int
    {
        return max(0, $this->grossMinutes() - $this->breakMinutes);
    }

    /**
     * Net working hours as decimal (e.g. 8.5 for 8h30m).
     */
    public function netHours(): float
    {
        return round($this->netMinutes() / 60, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'userId' => $this->userId,
            'date' => $this->date->format('Y-m-d'),
            'type' => $this->type->value,
            'startTime' => $this->startTime->format(\DateTimeInterface::ATOM),
            'endTime' => $this->endTime->format(\DateTimeInterface::ATOM),
            'breakMinutes' => $this->breakMinutes,
            'netMinutes' => $this->netMinutes(),
            'projectId' => $this->projectId,
            'note' => $this->note,
        ];
    }
}
