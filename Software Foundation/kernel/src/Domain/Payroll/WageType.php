<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Payroll;

/**
 * Wage type classification for Swissdec salary declarations.
 *
 * Maps wage components to the Swissdec XML schema's salary categories.
 * Each wage type determines which social insurance contributions apply.
 */
enum WageType: string
{
    /** AHV-pflichtiger Lohn (AHV-liable salary). */
    case AHV_SALARY = 'ahv_salary';

    /** UVG-pflichtiger Lohn (accident insurance liable). */
    case UVG_SALARY = 'uvg_salary';

    /** UVGZ-pflichtiger Lohn (supplementary accident insurance). */
    case UVGZ_SALARY = 'uvgz_salary';

    /** KTG-pflichtiger Lohn (daily sickness allowance liable). */
    case KTG_SALARY = 'ktg_salary';

    /** BVG-pflichtiger Lohn (pension fund liable). */
    case BVG_SALARY = 'bvg_salary';

    /** FAK-pflichtiger Lohn (family compensation fund). */
    case FAK_SALARY = 'fak_salary';

    /** Quellensteuerpflichtiger Lohn (withholding tax liable). */
    case TAX_SALARY = 'tax_salary';

    /** Spesen (expenses — not subject to social insurance). */
    case EXPENSES = 'expenses';

    /** Gratifikation / Bonus (bonus payments). */
    case BONUS = 'bonus';

    /** 13. Monatslohn (13th month salary). */
    case THIRTEENTH_SALARY = 'thirteenth_salary';

    /** Feriengeld / Feiertagsentschädigung (holiday compensation). */
    case HOLIDAY_COMPENSATION = 'holiday_compensation';

    /** Kinderzulage (child allowance). */
    case CHILD_ALLOWANCE = 'child_allowance';

    /**
     * Whether this wage type is subject to AHV contributions.
     */
    public function isAhvLiable(): bool
    {
        return match ($this) {
            self::AHV_SALARY, self::BONUS, self::THIRTEENTH_SALARY,
            self::HOLIDAY_COMPENSATION => true,
            default => false,
        };
    }

    /**
     * Whether this wage type appears on the Lohnausweis.
     */
    public function isOnSalaryCertificate(): bool
    {
        return $this !== self::CHILD_ALLOWANCE;
    }

    /**
     * Swissdec XML element name for this wage type.
     */
    public function swissdecElementName(): string
    {
        return match ($this) {
            self::AHV_SALARY => 'AHV-AVS-Salary',
            self::UVG_SALARY => 'UVG-LAA-Salary',
            self::UVGZ_SALARY => 'UVGZ-LAAC-Salary',
            self::KTG_SALARY => 'KTG-AMC-Salary',
            self::BVG_SALARY => 'BVG-LPP-Salary',
            self::FAK_SALARY => 'FAK-CAF-Salary',
            self::TAX_SALARY => 'TaxSalary',
            self::EXPENSES => 'Expenses',
            self::BONUS => 'Gratification',
            self::THIRTEENTH_SALARY => 'ThirteenthSalary',
            self::HOLIDAY_COMPENSATION => 'HolidayCompensation',
            self::CHILD_ALLOWANCE => 'ChildAllowance',
        };
    }
}
