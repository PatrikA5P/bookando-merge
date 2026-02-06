<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Dossier;

/**
 * Types of dossiers with their legally mandated retention periods.
 *
 * Retention periods based on:
 * - OR Art. 958f (10 years for business records)
 * - ArG Art. 46 (5 years for time tracking)
 * - DSG/DSGVO (personal data — purpose-bound)
 * - Industry best practice for HR (10 years after departure)
 */
enum DossierType: string
{
    /** Personaldossier — HR personnel files (10Y after departure, DSG Art. 6) */
    case PERSONAL = 'personal';

    /** Auftragsdossier — Order/project files (10Y, OR Art. 958f) */
    case ORDER = 'order';

    /** Kursdaten — Training/course data incl. certificates and images (10Y after departure) */
    case COURSE = 'course';

    /** Zeiterfassung — Time tracking records (5Y, ArG Art. 46) */
    case TIME_TRACKING = 'time_tracking';

    /** Lohnausweis — Salary certificates (10Y, OR Art. 958f) */
    case SALARY_CERTIFICATE = 'salary_certificate';

    /** Buchhaltung — Accounting records (10Y, OR Art. 958f / GeBüV) */
    case ACCOUNTING = 'accounting';

    /** Vertragsdossier — Contract files (10Y, OR Art. 958f) */
    case CONTRACT = 'contract';

    /** Bewerbungsdossier — Applicant files (delete after process, DSG) */
    case APPLICANT = 'applicant';

    /** MWST/Immobilien — VAT immovable property docs (20Y, MWSTG) */
    case VAT_PROPERTY = 'vat_property';

    /**
     * Mandatory retention period in years.
     *
     * Returns 0 for APPLICANT (must be deleted immediately after process).
     */
    public function retentionYears(): int
    {
        return match ($this) {
            self::PERSONAL => 10,
            self::ORDER => 10,
            self::COURSE => 10,
            self::TIME_TRACKING => 5,
            self::SALARY_CERTIFICATE => 10,
            self::ACCOUNTING => 10,
            self::CONTRACT => 10,
            self::APPLICANT => 0,
            self::VAT_PROPERTY => 20,
        };
    }

    /**
     * Whether this dossier type contains personal data (DSG/DSGVO relevant).
     */
    public function containsPersonalData(): bool
    {
        return match ($this) {
            self::PERSONAL, self::COURSE, self::TIME_TRACKING,
            self::SALARY_CERTIFICATE, self::APPLICANT => true,
            self::ORDER, self::ACCOUNTING, self::CONTRACT,
            self::VAT_PROPERTY => false,
        };
    }

    /**
     * Whether encryption at rest is required for this dossier type.
     */
    public function requiresEncryption(): bool
    {
        return $this->containsPersonalData();
    }

    public function label(): string
    {
        return match ($this) {
            self::PERSONAL => 'Personaldossier',
            self::ORDER => 'Auftragsdossier',
            self::COURSE => 'Kursdaten',
            self::TIME_TRACKING => 'Zeiterfassung',
            self::SALARY_CERTIFICATE => 'Lohnausweis',
            self::ACCOUNTING => 'Buchhaltung',
            self::CONTRACT => 'Vertragsdossier',
            self::APPLICANT => 'Bewerbungsdossier',
            self::VAT_PROPERTY => 'MWST-Immobilien',
        };
    }
}
