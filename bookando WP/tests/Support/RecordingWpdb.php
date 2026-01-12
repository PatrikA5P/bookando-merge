<?php

namespace Bookando\Tests\Support;

class RecordingWpdb extends \wpdb
{
    public string $prefix = 'wp_';

    /** @var array<string, object> */
    public array $rows = [];

    public array $inserts = [];

    public array $updates = [];

    public array $queries = [];

    /**
     * @param array<int, array<string, mixed>> $initialRows
     */
    public function __construct(array $initialRows = [])
    {
        foreach ($initialRows as $row) {
            if (!isset($row['slug'])) {
                continue;
            }
            $slug = (string) $row['slug'];
            $this->rows[$slug] = (object) array_merge(['slug' => $slug], $row);
        }
    }

    public function prepare(string $query, ...$args): array
    {
        return ['query' => $query, 'args' => $args];
    }

    public function get_var($query)
    {
        [$sql, $args] = $this->normalizeQuery($query);

        if (str_contains($sql, 'SHOW TABLES LIKE')) {
            return $args[0] ?? null;
        }

        if (str_contains($sql, 'SELECT status')) {
            $slug = (string) ($args[0] ?? '');
            return $this->rows[$slug]->status ?? null;
        }

        if (str_contains($sql, 'SELECT installed_at')) {
            $slug = (string) ($args[0] ?? '');
            return $this->rows[$slug]->installed_at ?? null;
        }

        return null;
    }

    public function get_col($query): array
    {
        [$sql, $args] = $this->normalizeQuery($query);

        if (is_string($sql) && str_contains($sql, "WHERE status = 'active'")) {
            return array_values(array_map(
                static fn(object $row) => (string) $row->slug,
                array_filter($this->rows, static fn(object $row): bool => ($row->status ?? null) === 'active')
            ));
        }

        if (str_contains($sql, "status = 'inactive'")) {
            $threshold = $args[0] ?? null;
            return array_values(array_map(
                static fn(object $row) => (string) $row->slug,
                array_filter($this->rows, static function (object $row) use ($threshold): bool {
                    if (($row->status ?? null) !== 'inactive') {
                        return false;
                    }
                    if ($threshold === null) {
                        return true;
                    }
                    $updated = $row->updated_at ?? null;
                    return is_string($updated) && $updated < $threshold;
                })
            ));
        }

        return [];
    }

    public function insert($table, $data, $format = null)
    {
        $slug = (string) ($data['slug'] ?? '');
        $record = array_merge(['slug' => $slug], $data);
        $this->rows[$slug] = (object) $record;
        $this->inserts[] = [$table, $record, $format];
        return true;
    }

    public function update($table, $data, $where, $format = null, $whereFormat = null)
    {
        $slug = (string) ($where['slug'] ?? '');
        $existing = (array) ($this->rows[$slug] ?? ['slug' => $slug]);
        $record = array_merge($existing, $data);
        $this->rows[$slug] = (object) $record;
        $this->updates[] = [$table, $data, $where, $format, $whereFormat];
        return true;
    }

    public function get_results($query)
    {
        return array_values($this->rows);
    }

    public function query($query)
    {
        [$sql, $args] = $this->normalizeQuery($query);
        if (str_contains($sql, 'deactivated_at = NULL')) {
            $slug = (string) ($args[0] ?? '');
            if (isset($this->rows[$slug])) {
                $this->rows[$slug]->deactivated_at = null;
                $this->rows[$slug]->deactivated_by = null;
            }
        }
        $this->queries[] = [$sql, $args];
        return 1;
    }

    public function esc_like($text)
    {
        return addslashes((string) $text);
    }

    /**
     * @return array{0:string,1:array<int,mixed>}
     */
    private function normalizeQuery($query): array
    {
        if (is_array($query) && isset($query['query'])) {
            return [$query['query'], $query['args'] ?? []];
        }

        return [is_string($query) ? $query : '', []];
    }
}
