<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Compliance\RetentionCategory;

/**
 * Port for long-term document archival.
 *
 * Supports legally compliant archival of financial and commercial documents
 * in immutable formats (e.g. PDF/A). Archived documents are stored for the
 * duration required by their retention category and can be purged once expired.
 *
 * Required by: OR Art. 958f, GeBüV Art. 9 (archival in unalterable format).
 */
interface ArchivalPort
{
    /**
     * Archive an entity in the specified format.
     *
     * Creates an immutable archived copy of the entity. The default format
     * is PDF/A, which is the legally accepted long-term archival format.
     *
     * @param int    $tenantId   Tenant identifier.
     * @param string $entityType Entity type (e.g. 'invoice', 'journal_entry').
     * @param string $entityId   Entity identifier.
     * @param string $format     Archive format (default: 'pdf_a').
     *
     * @return string Path or identifier of the archived document.
     */
    public function archive(int $tenantId, string $entityType, string $entityId, string $format = 'pdf_a'): string;

    /**
     * Retrieve the content of an archived document.
     *
     * @param int    $tenantId  Tenant identifier.
     * @param string $archiveId Archive identifier.
     *
     * @return string|null The archived document content, or null if not found.
     */
    public function retrieve(int $tenantId, string $archiveId): ?string;

    /**
     * Check whether an entity has been archived.
     *
     * @param int    $tenantId   Tenant identifier.
     * @param string $entityType Entity type.
     * @param string $entityId   Entity identifier.
     *
     * @return bool True if an archived copy exists.
     */
    public function isArchived(int $tenantId, string $entityType, string $entityId): bool;

    /**
     * Purge archived documents that have exceeded their retention period.
     *
     * Scans all archives in the given retention category and deletes those
     * whose retention period has elapsed.
     *
     * @param RetentionCategory $category Retention category to check.
     *
     * @return int Number of archives purged.
     */
    public function purgeExpired(RetentionCategory $category): int;
}
