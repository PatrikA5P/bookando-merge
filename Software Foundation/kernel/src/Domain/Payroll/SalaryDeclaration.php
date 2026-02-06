<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Payroll;

/**
 * Immutable value object representing a Swissdec salary declaration.
 *
 * A salary declaration is generated per employee per year and transmitted
 * via ELM to multiple recipients (AHV, BVG, UVG, KTG, tax, BFS).
 *
 * This is the kernel-level abstraction. The actual XML generation
 * and transmission happens via SwissdecTransmitterPort.
 */
final class SalaryDeclaration
{
    /**
     * @param SwissdecDomain[] $domains Domains this declaration covers
     * @param array<string, int> $wageComponents Wage type → amount in minor units
     */
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $employeeId,
        public readonly int $year,
        public readonly array $domains,
        public readonly array $wageComponents,
        public readonly SalaryDeclarationStatus $status,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $transmittedAt,
    ) {
        if ($this->year < 2000 || $this->year > 2100) {
            throw new \InvalidArgumentException("Invalid declaration year: {$this->year}");
        }

        if (empty($this->domains)) {
            throw new \InvalidArgumentException('At least one Swissdec domain must be specified.');
        }
    }

    /**
     * Total gross salary in minor units across all wage components.
     */
    public function totalGross(): int
    {
        return array_sum($this->wageComponents);
    }

    /**
     * Get the wage amount for a specific type (in minor units).
     */
    public function wageForType(WageType $type): int
    {
        return $this->wageComponents[$type->value] ?? 0;
    }

    /**
     * Whether this declaration includes a specific domain.
     */
    public function includesDomain(SwissdecDomain $domain): bool
    {
        foreach ($this->domains as $d) {
            if ($d === $domain) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'employeeId' => $this->employeeId,
            'year' => $this->year,
            'domains' => array_map(fn(SwissdecDomain $d) => $d->value, $this->domains),
            'wageComponents' => $this->wageComponents,
            'totalGross' => $this->totalGross(),
            'status' => $this->status->value,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'transmittedAt' => $this->transmittedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
