<?php
declare(strict_types=1);

namespace Bookando\Modules\Partnerhub;

use Bookando\Core\Security\BaseCapabilities;

class Capabilities extends BaseCapabilities
{
    public const CAPABILITY_VIEW = 'view_bookando_partnerhub';
    public const CAPABILITY_VIEW_LEGACY = 'view_bookando_partners';
    public const CAPABILITY_MANAGE = 'manage_bookando_partnerhub';
    public const CAPABILITY_MANAGE_LEGACY = 'manage_bookando_partners';

    public static function getAll(): array
    {
        return [
            // Basis-Berechtigungen (Slug-konsistent + Legacy-Alias)
            self::CAPABILITY_VIEW,
            self::CAPABILITY_VIEW_LEGACY,
            self::CAPABILITY_MANAGE,
            self::CAPABILITY_MANAGE_LEGACY,

            // Erweiterte Berechtigungen
            'manage_bookando_partner_mappings',
            'manage_bookando_partner_rules',
            'manage_bookando_partner_feeds',
            'view_bookando_partner_transactions',
            'manage_bookando_partner_transactions',

            // DSGVO-sensitive Berechtigungen
            'view_bookando_partner_consents',
            'manage_bookando_partner_consents',
            'export_bookando_partner_data',
            'view_bookando_partner_audit_logs',
        ];
    }

    public static function getDefaultRoles(): array
    {
        return ['administrator', 'bookando_manager'];
    }

    protected static function getModuleSlug(): string
    {
        return 'partnerhub';
    }
}
