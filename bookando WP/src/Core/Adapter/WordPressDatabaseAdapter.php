<?php

declare(strict_types=1);

namespace Bookando\Core\Adapter;

/**
 * WordPress Database Adapter
 *
 * Implements DatabaseAdapter using WordPress $wpdb global.
 * This adapter allows WordPress-specific code to use the
 * platform-agnostic DatabaseAdapter interface.
 *
 * @package Bookando\Core\Adapter
 */
class WordPressDatabaseAdapter implements DatabaseAdapter
{
    /** @var \wpdb WordPress database object */
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * @inheritDoc
     */
    public function query(string $sql, array $params = []): array
    {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }

        $results = $this->wpdb->get_results($sql, ARRAY_A);
        return $results ?: [];
    }

    /**
     * @inheritDoc
     */
    public function queryRow(string $sql, array $params = []): ?array
    {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }

        $result = $this->wpdb->get_row($sql, ARRAY_A);
        return $result ?: null;
    }

    /**
     * @inheritDoc
     */
    public function queryValue(string $sql, array $params = [])
    {
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }

        return $this->wpdb->get_var($sql);
    }

    /**
     * @inheritDoc
     */
    public function insert(string $table, array $data): int
    {
        $fullTable = $this->getTableName($table);

        $this->wpdb->insert($fullTable, $data);

        return (int) $this->wpdb->insert_id;
    }

    /**
     * @inheritDoc
     */
    public function update(string $table, array $data, array $where): int
    {
        $fullTable = $this->getTableName($table);

        $this->wpdb->update($fullTable, $data, $where);

        return (int) $this->wpdb->rows_affected;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $table, array $where): int
    {
        $fullTable = $this->getTableName($table);

        $this->wpdb->delete($fullTable, $where);

        return (int) $this->wpdb->rows_affected;
    }

    /**
     * @inheritDoc
     */
    public function getPrefix(): string
    {
        return $this->wpdb->prefix . 'bookando_';
    }

    /**
     * @inheritDoc
     */
    public function getTableName(string $table): string
    {
        return $this->getPrefix() . $table;
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): bool
    {
        return $this->wpdb->query('START TRANSACTION') !== false;
    }

    /**
     * @inheritDoc
     */
    public function commit(): bool
    {
        return $this->wpdb->query('COMMIT') !== false;
    }

    /**
     * @inheritDoc
     */
    public function rollback(): bool
    {
        return $this->wpdb->query('ROLLBACK') !== false;
    }

    /**
     * @inheritDoc
     */
    public function escape($value): string
    {
        return $this->wpdb->_real_escape($value);
    }

    /**
     * Get raw $wpdb instance (for WordPress-specific operations)
     *
     * @return \wpdb
     */
    public function getWpdb()
    {
        return $this->wpdb;
    }
}
