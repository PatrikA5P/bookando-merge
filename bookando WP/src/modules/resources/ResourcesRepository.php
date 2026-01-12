<?php

declare(strict_types=1);

namespace Bookando\Modules\resources;

final class ResourcesRepository extends StateRepository
{
    public const TYPES = ['locations', 'rooms', 'materials'];

    public function getState(): array
    {
        return parent::getState();
    }

    public function listByType(string $type): array
    {
        $state = parent::getState();

        return $state[$type] ?? [];
    }

    public function save(string $type, array $payload): array
    {
        return parent::upsertResource($type, $payload);
    }

    public function delete(string $type, string $id): bool
    {
        return parent::deleteResource($type, $id);
    }

    public function seedDefaultsForTenant(int $tenantId, bool $force = false): array
    {
        return parent::seedDefaultsForTenant($tenantId, $force);
    }

    public function resetCache(): void
    {
        parent::resetCache();
    }
}
