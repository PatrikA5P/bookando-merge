<?php
declare(strict_types=1);

namespace Bookando\Modules\resources;

use Bookando\Core\Security\BaseCapabilities;

class Capabilities extends BaseCapabilities
{
    public const CAPABILITY_MANAGE = 'manage_bookando_resources';
    public const CAPABILITY_EXPORT = 'export_bookando_resources';

    public static function getAll(): array
    {
        return [
            self::CAPABILITY_MANAGE,
            // self::CAPABILITY_EXPORT, // optional extra right (e.g. CSV/PDF export)
        ];
    }

    public static function getDefaultRoles(): array
    {
        return ['administrator', 'bookando_manager'];
    }

    protected static function getModuleSlug(): string
    {
        return 'resources';
    }
}
