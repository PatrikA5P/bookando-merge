<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Dossier\Dossier;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierAccessAction;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierAccessLog;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierEntry;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierStatus;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierType;

/**
 * Port for dossier management — revisionssichere Archivierung (GeBüV).
 *
 * Implements legally compliant document storage with:
 * - Integrity verification (SHA-256 content hashes)
 * - Mandatory access logging (GeBüV Art. 7/8)
 * - Retention period enforcement
 * - Encryption at rest for personal data (DSG/DSGVO)
 *
 * Tax authorities must be able to read and evaluate all documents
 * immediately, unaltered, and completely — even without prior notice.
 */
interface DossierPort
{
    /**
     * Create a new dossier.
     */
    public function createDossier(Dossier $dossier): void;

    /**
     * Retrieve a dossier by ID.
     */
    public function getDossier(int $tenantId, string $dossierId): ?Dossier;

    /**
     * List dossiers by type for a tenant.
     *
     * @return Dossier[]
     */
    public function listDossiers(int $tenantId, ?DossierType $type = null, ?DossierStatus $status = null): array;

    /**
     * Add a document entry to a dossier.
     *
     * The content is stored separately (via StoragePort). This method
     * stores only the metadata and content hash for integrity verification.
     */
    public function addEntry(DossierEntry $entry): void;

    /**
     * Retrieve a specific entry.
     */
    public function getEntry(int $tenantId, string $dossierId, string $entryId): ?DossierEntry;

    /**
     * List all entries in a dossier.
     *
     * @return DossierEntry[]
     */
    public function listEntries(int $tenantId, string $dossierId): array;

    /**
     * Close a dossier and calculate retention deadline.
     */
    public function closeDossier(int $tenantId, string $dossierId, \DateTimeImmutable $closedAt): void;

    /**
     * Log access to a dossier (mandatory for GeBüV compliance).
     *
     * Every VIEW, DOWNLOAD, UPLOAD, DELETE, EXPORT, and PRINT must be logged.
     */
    public function logAccess(DossierAccessLog $log): void;

    /**
     * Retrieve access log for a dossier.
     *
     * @return DossierAccessLog[]
     */
    public function getAccessLog(int $tenantId, string $dossierId): array;

    /**
     * Find dossiers whose retention period has expired.
     *
     * @return Dossier[]
     */
    public function findExpiredDossiers(int $tenantId, \DateTimeImmutable $asOf): array;
}
