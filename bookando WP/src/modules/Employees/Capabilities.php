<?php
declare(strict_types=1);

namespace Bookando\Modules\Employees;

use Bookando\Core\Security\BaseCapabilities;

class Capabilities extends BaseCapabilities
{
    public static function getAll(): array
    {
        return [
            'manage_bookando_employees',
            // 'export_bookando_employees', // bei Bedarf aktivieren
        ];
    }

    public static function getDefaultRoles(): array
    {
        return ['administrator', 'bookando_manager'];
    }

    protected static function getModuleSlug(): string
    {
        return 'employees';
    }
}
  
