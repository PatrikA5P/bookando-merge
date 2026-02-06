<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

/**
 * Port for data export (data portability).
 *
 * Supports DSGVO Art. 20 (right to data portability) by enabling tenants
 * and individuals to export their data in structured, machine-readable formats.
 */
interface DataExportPort
{
    /**
     * Export all data for a tenant.
     *
     * Generates a complete export of the tenant's data in the specified format
     * and writes it to a file.
     *
     * @param int    $tenantId Tenant identifier.
     * @param string $format   Export format ('json', 'csv', 'xml').
     *
     * @return string File path of the generated export.
     */
    public function exportTenantData(int $tenantId, string $format = 'json'): string;

    /**
     * Export a single entity.
     *
     * Returns the entity data as a structured array in the specified format.
     *
     * @param int    $tenantId   Tenant identifier.
     * @param string $entityType Entity type (e.g. 'customer', 'invoice').
     * @param string $entityId   Entity identifier.
     * @param string $format     Export format ('json', 'csv', 'xml').
     *
     * @return array The exported entity data.
     */
    public function exportEntity(int $tenantId, string $entityType, string $entityId, string $format = 'json'): array;

    /**
     * Return the list of supported export formats.
     *
     * @return string[] Supported format identifiers (e.g. ['json', 'csv', 'xml']).
     */
    public function supportedFormats(): array;
}
