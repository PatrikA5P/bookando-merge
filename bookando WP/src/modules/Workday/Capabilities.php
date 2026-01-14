<?php

declare(strict_types=1);

namespace Bookando\Modules\Workday;

use Bookando\Core\Security\BaseCapabilities;

class Capabilities extends BaseCapabilities
{
    // Primary capability required for menu access - MUST match Admin::getCapability()
    public const CAPABILITY_MANAGE = 'manage_bookando_workday';

    // Additional workday-specific capabilities
    public const CAPABILITY_VIEW_CALENDAR = 'view_bookando_calendar';
    public const CAPABILITY_MANAGE_APPOINTMENTS = 'manage_bookando_appointments';
    public const CAPABILITY_MANAGE_TIME_TRACKING = 'manage_bookando_time_tracking';
    public const CAPABILITY_MANAGE_DUTY_SCHEDULING = 'manage_bookando_duty_scheduling';
    public const CAPABILITY_APPROVE_VACATION = 'approve_bookando_vacation';
    public const CAPABILITY_VIEW_OWN_CALENDAR = 'view_own_bookando_calendar';
    public const CAPABILITY_MANAGE_OWN_TIME = 'manage_own_bookando_time';
    public const CAPABILITY_REQUEST_VACATION = 'request_bookando_vacation';

    /**
     * Get all capabilities for the module.
     *
     * @return list<string>
     */
    public static function getAll(): array
    {
        return [
            self::CAPABILITY_MANAGE,
            // Additional capabilities can be enabled as needed:
            // self::CAPABILITY_VIEW_CALENDAR,
            // self::CAPABILITY_MANAGE_APPOINTMENTS,
            // self::CAPABILITY_MANAGE_TIME_TRACKING,
            // self::CAPABILITY_MANAGE_DUTY_SCHEDULING,
            // self::CAPABILITY_APPROVE_VACATION,
            // self::CAPABILITY_VIEW_OWN_CALENDAR,
            // self::CAPABILITY_MANAGE_OWN_TIME,
            // self::CAPABILITY_REQUEST_VACATION,
        ];
    }

    /**
     * Get default roles that should receive these capabilities.
     *
     * @return list<string>
     */
    public static function getDefaultRoles(): array
    {
        return ['administrator', 'bookando_manager'];
    }

    /**
     * Get the module slug for filter hooks.
     *
     * @return string
     */
    protected static function getModuleSlug(): string
    {
        return 'workday';
    }
}
