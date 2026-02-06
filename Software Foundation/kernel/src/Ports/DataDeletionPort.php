<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

use SoftwareFoundation\Kernel\Domain\Compliance\DeletionStrategy;

/**
 * Port for data deletion and anonymization.
 *
 * Supports DSGVO Art. 17 (right to erasure) and DSG requirements for
 * data deletion. Provides multiple deletion strategies depending on the
 * data category and legal retention requirements.
 */
interface DataDeletionPort
{
    /**
     * Delete a specific entity using the given strategy.
     *
     * @param int              $tenantId   Tenant identifier.
     * @param string           $entityType Entity type (e.g. 'customer', 'invoice').
     * @param string           $entityId   Entity identifier.
     * @param DeletionStrategy $strategy   Deletion strategy to apply.
     *
     * @return bool True if the entity was successfully deleted/processed.
     */
    public function delete(int $tenantId, string $entityType, string $entityId, DeletionStrategy $strategy): bool;

    /**
     * Delete all data for an entire tenant.
     *
     * @param int              $tenantId Tenant identifier.
     * @param DeletionStrategy $strategy Deletion strategy to apply.
     *
     * @return bool True if the tenant was successfully deleted/processed.
     */
    public function deleteTenant(int $tenantId, DeletionStrategy $strategy): bool;

    /**
     * Anonymize a specific entity by replacing PII with anonymized data.
     *
     * @param int    $tenantId   Tenant identifier.
     * @param string $entityType Entity type.
     * @param string $entityId   Entity identifier.
     *
     * @return bool True if the entity was successfully anonymized.
     */
    public function anonymize(int $tenantId, string $entityType, string $entityId): bool;

    /**
     * Crypto-shred an entire tenant by destroying all encryption keys.
     *
     * After this operation, all encrypted data for the tenant becomes
     * permanently unreadable. The ciphertext remains in storage but is
     * irrecoverable.
     *
     * @param int $tenantId Tenant identifier.
     *
     * @return bool True if crypto-shredding was successful.
     */
    public function cryptoShred(int $tenantId): bool;
}
