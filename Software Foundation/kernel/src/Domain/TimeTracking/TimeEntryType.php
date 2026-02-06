<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\TimeTracking;

/**
 * Types of time entries as required by ArG Art. 46 / ArGV 1 Art. 73.
 */
enum TimeEntryType: string
{
    /** Regular working time. */
    case REGULAR = 'regular';

    /** Overtime (Überstunden / Überzeit). */
    case OVERTIME = 'overtime';

    /** Compensatory time off (Kompensation). */
    case COMPENSATORY = 'compensatory';

    /** Absence — illness, vacation, etc. */
    case ABSENCE = 'absence';

    /** Break of 30+ minutes (must be recorded per ArGV 1 Art. 73). */
    case BREAK = 'break';
}
