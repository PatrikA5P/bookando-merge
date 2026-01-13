<?php

declare(strict_types=1);

namespace Bookando\Core\Adapter;

/**
 * Database Adapter Interface
 *
 * Platform-agnostic database interface to decouple business logic
 * from WordPress-specific database implementation.
 *
 * This enables the codebase to run as:
 * - WordPress Plugin (using $wpdb)
 * - Standalone SaaS (using PDO/Doctrine)
 * - Docker/Cloud deployment
 *
 * @package Bookando\Core\Adapter
 */
interface DatabaseAdapter
{
    /**
     * Execute a raw SQL query with parameters
     *
     * @param string $sql SQL query with placeholders
     * @param array<mixed> $params Parameters to bind
     * @return array<array<string,mixed>> Result rows
     */
    public function query(string $sql, array $params = []): array;

    /**
     * Execute a query and return single row
     *
     * @param string $sql SQL query
     * @param array<mixed> $params Parameters
     * @return array<string,mixed>|null Single row or null
     */
    public function queryRow(string $sql, array $params = []): ?array;

    /**
     * Execute a query and return single value
     *
     * @param string $sql SQL query
     * @param array<mixed> $params Parameters
     * @return mixed Single value or null
     */
    public function queryValue(string $sql, array $params = []);

    /**
     * Insert a row into a table
     *
     * @param string $table Table name (without prefix)
     * @param array<string,mixed> $data Column => Value pairs
     * @return int Last insert ID
     */
    public function insert(string $table, array $data): int;

    /**
     * Update rows in a table
     *
     * @param string $table Table name (without prefix)
     * @param array<string,mixed> $data Column => Value pairs to update
     * @param array<string,mixed> $where WHERE conditions
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $where): int;

    /**
     * Delete rows from a table
     *
     * @param string $table Table name (without prefix)
     * @param array<string,mixed> $where WHERE conditions
     * @return int Number of affected rows
     */
    public function delete(string $table, array $where): int;

    /**
     * Get table prefix (e.g., 'wp_bookando_')
     *
     * @return string Table prefix
     */
    public function getPrefix(): string;

    /**
     * Get full table name with prefix
     *
     * @param string $table Table name without prefix
     * @return string Full table name
     */
    public function getTableName(string $table): string;

    /**
     * Begin a database transaction
     *
     * @return bool Success
     */
    public function beginTransaction(): bool;

    /**
     * Commit a database transaction
     *
     * @return bool Success
     */
    public function commit(): bool;

    /**
     * Rollback a database transaction
     *
     * @return bool Success
     */
    public function rollback(): bool;

    /**
     * Escape a value for SQL (prevents SQL injection)
     *
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    public function escape($value): string;
}
