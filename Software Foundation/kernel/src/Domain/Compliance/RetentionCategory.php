<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Compliance;

/**
 * Retention categories based on Swiss and EU legal requirements.
 *
 * Each category defines how long data must be retained before it can
 * (or must) be deleted. These are driven by:
 * - OR Art. 958f / GeBüV (Swiss accounting law)
 * - GoBD (German fiscal compliance, applicable to DACH region)
 * - DSG / DSGVO (Swiss / EU data protection)
 */
enum RetentionCategory: string
{
    case FINANCIAL_10Y = 'financial_10y';     // OR Art. 958f, GeBüV, GoBD (balance sheets, invoices, journals)
    case COMMERCIAL_6Y = 'commercial_6y';     // GoBD (commercial letters, costings)
    case PERSONAL_DSG = 'personal_dsg';       // DSG/DSGVO (personal data — delete when purpose fulfilled)
    case AUDIT_PERMANENT = 'audit_permanent'; // Audit trails — never delete
    case TEMPORARY = 'temporary';             // Session data, caches — short-lived

    /**
     * Return the number of years data in this category must be retained.
     *
     * Returns -1 for permanent retention (never delete).
     * Returns 0 for temporary data (no retention requirement).
     */
    public function retentionYears(): int
    {
        return match ($this) {
            self::FINANCIAL_10Y => 10,
            self::COMMERCIAL_6Y => 6,
            self::PERSONAL_DSG => 3,
            self::AUDIT_PERMANENT => -1,
            self::TEMPORARY => 0,
        };
    }
}
