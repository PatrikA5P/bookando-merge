<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Payroll;

/**
 * Status of a Swissdec salary declaration.
 */
enum SalaryDeclarationStatus: string
{
    /** Declaration created but not yet validated. */
    case DRAFT = 'draft';

    /** XML validated locally against Swissdec XSD. */
    case VALIDATED = 'validated';

    /** Transmitted to Swissdec Distributor. */
    case TRANSMITTED = 'transmitted';

    /** Accepted by all recipient domains. */
    case ACCEPTED = 'accepted';

    /** Rejected by one or more domains — needs correction. */
    case REJECTED = 'rejected';

    /** Correction declaration submitted. */
    case CORRECTED = 'corrected';

    public function isEditable(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    public function isTransmitted(): bool
    {
        return match ($this) {
            self::TRANSMITTED, self::ACCEPTED, self::CORRECTED => true,
            default => false,
        };
    }
}
