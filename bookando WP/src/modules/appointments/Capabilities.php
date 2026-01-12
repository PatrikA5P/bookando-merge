<?php
declare(strict_types=1);

namespace Bookando\Modules\appointments;

use Bookando\Core\Security\BaseCapabilities;

class Capabilities extends BaseCapabilities
{
    public static function getAll(): array
    {
        return [
            'manage_bookando_appointments',
        ];
    }

    public static function getDefaultRoles(): array
    {
        return ['administrator', 'bookando_manager'];
    }

    protected static function getModuleSlug(): string
    {
        return 'appointments';
    }
}
