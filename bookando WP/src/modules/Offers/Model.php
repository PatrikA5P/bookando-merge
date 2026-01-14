<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers;

use Bookando\Core\Model\BaseModel;

final class Model extends BaseModel
{
    protected string $tableName;

    public function __construct()
    {
        parent::__construct();
        $this->tableName = $this->table('offers');
    }

    /**
     * @return string[]
     */
    protected function allowedOrderBy(): array
    {
        return ['id', 'title', 'status', 'created_at', 'updated_at'];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, perPage: int}
     */
    public function getPage(int $page, int $perPage, ?string $orderBy = null, string $direction = 'DESC'): array
    {
        $sql = "SELECT id, tenant_id, title, status, created_at, updated_at\n                FROM {$this->tableName}\n                WHERE deleted_at IS NULL";

        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return parent::paginate($sql, [], $page, $perPage, $orderBy, $dir);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT id, tenant_id, title, status, created_at, updated_at\n                FROM {$this->tableName}\n                WHERE id = %d AND deleted_at IS NULL";

        return $this->fetchOne($sql, [$id]);
    }

    public function create(array $data): int
    {
        $payload = $this->filter($data);
        $payload['created_at'] = $payload['created_at'] ?? $this->now();
        $payload['updated_at'] = $payload['updated_at'] ?? $payload['created_at'];

        return $this->insert($payload);
    }

    public function update(int $id, array $data): bool
    {
        $payload = $this->filter($data);
        if ($payload === []) {
            return true;
        }

        $payload['updated_at'] = $this->now();

        $result = parent::update($id, $payload);

        return $result >= 0;
    }

    public function delete(int $id, bool $hard = false): bool
    {
        if ($hard) {
            return parent::delete($id) > 0;
        }

        $result = parent::update($id, ['deleted_at' => $this->now()]);

        return $result > 0;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filter(array $data): array
    {
        $allowed = ['title', 'status', 'created_at', 'updated_at'];

        return array_intersect_key($data, array_flip($allowed));
    }
}
