<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Compliance;

/**
 * Hosting deployment modes.
 *
 * Determines how the application is deployed and affects data residency,
 * encryption requirements, and which storage backends are available.
 */
enum HostingMode: string
{
    case SAAS = 'saas';               // Fully hosted by us
    case SELF_HOSTED = 'self_hosted'; // Customer hosts everything
    case HYBRID = 'hybrid';           // Core SaaS + optional local storage
}
