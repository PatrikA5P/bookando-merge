<?php

declare(strict_types=1);

namespace Bookando\Modules\Dashboard;

class Capabilities
{
    public static function register(): void
    {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_bookando_dashboard');
        }

        $role = get_role('bookando_admin');
        if ($role) {
            $role->add_cap('manage_bookando_dashboard');
        }

        $role = get_role('bookando_employee');
        if ($role) {
            $role->add_cap('manage_bookando_dashboard');
        }
    }
}
