<?php
declare(strict_types=1);

namespace Bookando\Modules\Finance;

use Bookando\Core\Security\BaseCapabilities;

class Capabilities extends BaseCapabilities
{
    public static function getAll(): array
    {
        return [
            'manage_bookando_finance',
        ];
    }

    public static function getDefaultRoles(): array
    {
        return ['administrator', 'bookando_manager'];
    }

    protected static function getModuleSlug(): string
    {
        return 'finance';
    }
}
