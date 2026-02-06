<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\Dossier;

enum DossierStatus: string
{
    /** Dossier is actively used and can be modified. */
    case OPEN = 'open';

    /** Dossier is closed (e.g. employment ended) but still within retention period. */
    case CLOSED = 'closed';

    /** Dossier has been moved to long-term archive (GeBüV-compliant). */
    case ARCHIVED = 'archived';

    /** Retention period expired — ready for deletion or crypto-shredding. */
    case RETENTION_EXPIRED = 'retention_expired';

    public function isModifiable(): bool
    {
        return $this === self::OPEN;
    }

    public function isDeletable(): bool
    {
        return $this === self::RETENTION_EXPIRED;
    }
}
