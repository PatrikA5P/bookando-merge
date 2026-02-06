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
    case FINANCIAL_10Y = 'financial_10y';         // OR Art. 958f, GeBüV, GoBD (balance sheets, invoices, journals)
    case COMMERCIAL_6Y = 'commercial_6y';         // GoBD (commercial letters, costings)
    case PERSONAL_DSG = 'personal_dsg';           // DSG/DSGVO (personal data — delete when purpose fulfilled)
    case AUDIT_PERMANENT = 'audit_permanent';     // Audit trails — never delete
    case TEMPORARY = 'temporary';                 // Session data, caches — short-lived

    // CH-specific retention categories
    case HR_PERSONNEL_10Y = 'hr_personnel_10y';   // Personaldossier — 10Y after departure (OR/DSG best practice)
    case TIME_TRACKING_5Y = 'time_tracking_5y';   // ArG Art. 46 / ArGV 1 Art. 73 — 5 years
    case SALARY_CERT_10Y = 'salary_cert_10y';     // Lohnausweis — 10Y (OR Art. 958f)
    case VAT_PROPERTY_20Y = 'vat_property_20y';   // MWSTG Immobilien — 20 years
    case APPLICANT_0 = 'applicant_0';             // Bewerbungsdossier — delete after process (DSG)

    /**
     * Return the number of years data in this category must be retained.
     *
     * Returns -1 for permanent retention (never delete).
     * Returns 0 for temporary data or immediate deletion.
     */
    public function retentionYears(): int
    {
        return match ($this) {
            self::FINANCIAL_10Y => 10,
            self::COMMERCIAL_6Y => 6,
            self::PERSONAL_DSG => 3,
            self::AUDIT_PERMANENT => -1,
            self::TEMPORARY => 0,
            self::HR_PERSONNEL_10Y => 10,
            self::TIME_TRACKING_5Y => 5,
            self::SALARY_CERT_10Y => 10,
            self::VAT_PROPERTY_20Y => 20,
            self::APPLICANT_0 => 0,
        };
    }

    /**
     * Legal basis for this retention category.
     */
    public function legalBasis(): string
    {
        return match ($this) {
            self::FINANCIAL_10Y => 'OR Art. 958f, GeBüV',
            self::COMMERCIAL_6Y => 'GoBD',
            self::PERSONAL_DSG => 'DSG Art. 6, DSGVO Art. 17',
            self::AUDIT_PERMANENT => 'GeBüV Art. 7/8',
            self::TEMPORARY => '—',
            self::HR_PERSONNEL_10Y => 'OR Art. 958f, DSG best practice',
            self::TIME_TRACKING_5Y => 'ArG Art. 46, ArGV 1 Art. 73',
            self::SALARY_CERT_10Y => 'OR Art. 958f',
            self::VAT_PROPERTY_20Y => 'MWSTG',
            self::APPLICANT_0 => 'DSG Art. 6 (Zweckbindung)',
        };
    }
}
