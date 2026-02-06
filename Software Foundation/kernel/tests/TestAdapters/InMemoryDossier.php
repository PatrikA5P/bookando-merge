<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Dossier\Dossier;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierAccessLog;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierEntry;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierStatus;
use SoftwareFoundation\Kernel\Domain\Dossier\DossierType;
use SoftwareFoundation\Kernel\Ports\DossierPort;

/**
 * In-memory test implementation of DossierPort.
 *
 * Stores dossiers, entries, and access logs in arrays keyed by tenantId
 * for isolated testing without external dependencies.
 */
final class InMemoryDossier implements DossierPort
{
    /**
     * @var array<int, array<string, Dossier>>
     */
    private array $dossiers = [];

    /**
     * @var array<int, array<string, array<string, DossierEntry>>>
     */
    private array $entries = [];

    /**
     * @var array<int, array<string, DossierAccessLog[]>>
     */
    private array $accessLogs = [];

    public function createDossier(Dossier $dossier): void
    {
        if (!isset($this->dossiers[$dossier->tenantId])) {
            $this->dossiers[$dossier->tenantId] = [];
        }

        $this->dossiers[$dossier->tenantId][$dossier->id] = $dossier;
    }

    public function getDossier(int $tenantId, string $dossierId): ?Dossier
    {
        return $this->dossiers[$tenantId][$dossierId] ?? null;
    }

    public function listDossiers(int $tenantId, ?DossierType $type = null, ?DossierStatus $status = null): array
    {
        if (!isset($this->dossiers[$tenantId])) {
            return [];
        }

        $result = $this->dossiers[$tenantId];

        if ($type !== null) {
            $result = array_filter($result, fn(Dossier $d) => $d->type === $type);
        }

        if ($status !== null) {
            $result = array_filter($result, fn(Dossier $d) => $d->status === $status);
        }

        return array_values($result);
    }

    public function addEntry(DossierEntry $entry): void
    {
        if (!isset($this->entries[$entry->dossierId])) {
            $this->entries[$entry->dossierId] = [];
        }

        $this->entries[$entry->dossierId][$entry->id] = $entry;
    }

    public function getEntry(int $tenantId, string $dossierId, string $entryId): ?DossierEntry
    {
        return $this->entries[$dossierId][$entryId] ?? null;
    }

    public function listEntries(int $tenantId, string $dossierId): array
    {
        return array_values($this->entries[$dossierId] ?? []);
    }

    public function closeDossier(int $tenantId, string $dossierId, \DateTimeImmutable $closedAt): void
    {
        $dossier = $this->getDossier($tenantId, $dossierId);
        if ($dossier === null) {
            throw new \RuntimeException("Dossier {$dossierId} not found.");
        }

        $this->dossiers[$tenantId][$dossierId] = $dossier->close($closedAt);
    }

    public function logAccess(DossierAccessLog $log): void
    {
        if (!isset($this->accessLogs[$log->dossierId])) {
            $this->accessLogs[$log->dossierId] = [];
        }

        $this->accessLogs[$log->dossierId][] = $log;
    }

    public function getAccessLog(int $tenantId, string $dossierId): array
    {
        return $this->accessLogs[$dossierId] ?? [];
    }

    public function findExpiredDossiers(int $tenantId, \DateTimeImmutable $asOf): array
    {
        if (!isset($this->dossiers[$tenantId])) {
            return [];
        }

        $expired = [];
        foreach ($this->dossiers[$tenantId] as $dossier) {
            if ($dossier->isRetentionExpired($asOf)) {
                $expired[] = $dossier;
            }
        }

        return $expired;
    }

    /**
     * Clear all data (for test isolation).
     */
    public function clear(): void
    {
        $this->dossiers = [];
        $this->entries = [];
        $this->accessLogs = [];
    }
}
