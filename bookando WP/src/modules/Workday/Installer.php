<?php

declare(strict_types=1);

namespace Bookando\Modules\Workday;

use Bookando\Core\Base\BaseInstaller;

class Installer extends BaseInstaller
{
    protected function getTables(): array
    {
        // Workday module uses tables from Core:
        // - employees_workday_sets, employees_workday_intervals
        // - employees_special_days, employees_days_off
        // - time_entries, active_timers
        // - bookings (for appointments)

        // No module-specific tables needed
        return [];
    }

    protected function getCapabilities(): array
    {
        return Capabilities::getAll();
    }
}
