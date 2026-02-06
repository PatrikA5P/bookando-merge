<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Licensing;

enum LicenseStatus: string
{
    case ACTIVE = 'active';
    case GRACE = 'grace';         // Expired but within grace period
    case EXPIRED = 'expired';     // Past grace period
    case SUSPENDED = 'suspended'; // Manually suspended (e.g., payment dispute)
    case TRIAL = 'trial';         // Trial period
    case CANCELLED = 'cancelled'; // Tenant cancelled subscription
}
