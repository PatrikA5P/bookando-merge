<?php

namespace App\Http\Controllers\Concerns;

trait TransformsResponse
{
    protected function transformModel($model, array $extra = []): array
    {
        if (!$model) {
            return [];
        }

        $data = $model->toArray();
        $result = [];

        foreach ($data as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            // Handle nested associative arrays/relations
            if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $value = $this->transformArray($value);
            }

            $result[$camelKey] = $value;
        }

        return array_merge($result, $extra);
    }

    protected function transformArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $camelKey = is_string($key)
                ? lcfirst(str_replace('_', '', ucwords($key, '_')))
                : $key;

            $result[$camelKey] = is_array($value) ? $this->transformArray($value) : $value;
        }

        return $result;
    }

    protected function transformCollection($items): array
    {
        return $items->map(fn ($item) => $this->transformModel($item))->toArray();
    }

    protected function paginatedResponse($paginator): array
    {
        return [
            'data' => collect($paginator->items())
                ->map(fn ($item) => $this->transformModel($item))
                ->values()
                ->toArray(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
            ],
        ];
    }
}
