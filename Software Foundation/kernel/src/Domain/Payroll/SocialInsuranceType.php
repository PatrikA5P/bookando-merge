<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Payroll;

/**
 * Swiss social insurance contribution types.
 *
 * Each type defines the legal framework, who pays (employer, employee, or both),
 * and the basis for calculating contributions. Rates are determined by
 * specific insurers and are not hardcoded here — they are resolved via TaxResolverPort.
 */
enum SocialInsuranceType: string
{
    /** AHV — Alters- und Hinterlassenenversicherung (old-age and survivors). */
    case AHV = 'ahv';

    /** IV — Invalidenversicherung (disability insurance). */
    case IV = 'iv';

    /** EO — Erwerbsersatzordnung (income compensation for military/maternity). */
    case EO = 'eo';

    /** ALV — Arbeitslosenversicherung (unemployment insurance). */
    case ALV = 'alv';

    /** ALV2 — Solidarity contribution on income above threshold. */
    case ALV_SOLIDARITY = 'alv_solidarity';

    /** BVG — Berufliche Vorsorge (occupational pension, 2nd pillar). */
    case BVG = 'bvg';

    /** UVG — Unfallversicherung Berufsunfall (occupational accident). */
    case UVG_BU = 'uvg_bu';

    /** UVG — Unfallversicherung Nichtberufsunfall (non-occupational accident). */
    case UVG_NBU = 'uvg_nbu';

    /** UVGZ — Zusatzversicherung Unfall (supplementary accident insurance). */
    case UVGZ = 'uvgz';

    /** KTG — Krankentaggeldversicherung (daily sickness allowance). */
    case KTG = 'ktg';

    /** FAK — Familienausgleichskasse (family compensation fund). */
    case FAK = 'fak';

    /**
     * Whether both employer and employee contribute (parity).
     */
    public function isParityContribution(): bool
    {
        return match ($this) {
            self::AHV, self::IV, self::EO, self::ALV, self::ALV_SOLIDARITY => true,
            self::BVG => true, // typically 50/50 but can be higher employer share
            self::UVG_BU => false, // employer only
            self::UVG_NBU => false, // employee only (typically)
            self::UVGZ, self::KTG => false, // depends on contract
            self::FAK => false, // employer only
        };
    }

    /**
     * Whether this contribution is mandatory for all employees.
     */
    public function isMandatory(): bool
    {
        return match ($this) {
            self::AHV, self::IV, self::EO, self::ALV,
            self::UVG_BU, self::UVG_NBU, self::FAK => true,
            self::ALV_SOLIDARITY => true, // above threshold
            self::BVG => true, // above entry threshold (Eintrittsschwelle)
            self::UVGZ, self::KTG => false, // optional
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AHV => 'AHV (Alters- und Hinterlassenenversicherung)',
            self::IV => 'IV (Invalidenversicherung)',
            self::EO => 'EO (Erwerbsersatzordnung)',
            self::ALV => 'ALV (Arbeitslosenversicherung)',
            self::ALV_SOLIDARITY => 'ALV Solidaritätsbeitrag',
            self::BVG => 'BVG (Berufliche Vorsorge)',
            self::UVG_BU => 'UVG Berufsunfall',
            self::UVG_NBU => 'UVG Nichtberufsunfall',
            self::UVGZ => 'UVGZ (Zusatzversicherung)',
            self::KTG => 'KTG (Krankentaggeld)',
            self::FAK => 'FAK (Familienausgleichskasse)',
        };
    }
}
