<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Payroll;

/**
 * Swissdec ELM transmission domains.
 *
 * ELM (Einheitliches Lohnmeldeverfahren) defines 7 recipient domains
 * for standardized payroll data transmission. Current version: ELM 5.0/5.5.
 *
 * Each domain corresponds to a specific Swiss authority or insurer
 * that receives salary declarations via the Swissdec Distributor.
 */
enum SwissdecDomain: string
{
    /** AHV/IV/EO — Old-age, disability, income compensation insurance + FAK. */
    case AHV_IV_EO = 'ahv_iv_eo';

    /** ALV — Unemployment insurance. */
    case ALV = 'alv';

    /** BVG — Occupational pension (2nd pillar). */
    case BVG = 'bvg';

    /** UVG — Accident insurance (SUVA or private). Includes UVGZ. */
    case UVG = 'uvg';

    /** KTG — Daily sickness allowance insurance. */
    case KTG = 'ktg';

    /** Quellensteuer — Withholding tax (requires ELM 5.3+). */
    case QUELLENSTEUER = 'quellensteuer';

    /** Statistik BFS — Federal Statistical Office reporting. */
    case STATISTIK_BFS = 'statistik_bfs';

    /**
     * Minimum ELM version required for this domain.
     */
    public function minimumElmVersion(): string
    {
        return match ($this) {
            self::QUELLENSTEUER => '5.3',
            default => '5.0',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AHV_IV_EO => 'AHV/IV/EO/FAK',
            self::ALV => 'Arbeitslosenversicherung',
            self::BVG => 'Berufliche Vorsorge',
            self::UVG => 'Unfallversicherung',
            self::KTG => 'Krankentaggeld',
            self::QUELLENSTEUER => 'Quellensteuer',
            self::STATISTIK_BFS => 'Bundesamt für Statistik',
        };
    }
}
