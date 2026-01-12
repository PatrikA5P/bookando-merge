<?php

declare(strict_types=1);

namespace Bookando\Core\Contracts;

/**
 * Customer repository contract.
 *
 * Defines the interface for customer data persistence,
 * enabling different storage implementations (WordPress, PostgreSQL, etc.).
 *
 * All methods enforce tenant isolation to ensure data privacy.
 */
interface CustomerRepositoryInterface
{
    /**
     * Finds a customer by ID within a tenant.
     *
     * @param int $id Customer ID
     * @param int $tenantId Tenant ID for isolation
     * @return array|null Customer data or null if not found
     */
    public function findById(int $id, int $tenantId): ?array;

    /**
     * Creates a new customer.
     *
     * @param array $data Customer data
     * @param int $tenantId Tenant ID
     * @return int The new customer ID
     */
    public function create(array $data, int $tenantId): int;

    /**
     * Updates an existing customer.
     *
     * @param int $id Customer ID
     * @param array $data Updated customer data
     * @param int $tenantId Tenant ID for isolation
     * @return bool True on success, false on failure
     */
    public function update(int $id, array $data, int $tenantId): bool;

    /**
     * Deletes a customer (soft or hard delete).
     *
     * @param int $id Customer ID
     * @param bool $hard If true, permanently delete; if false, soft delete
     * @param int $tenantId Tenant ID for isolation
     * @return bool True on success, false on failure
     */
    public function delete(int $id, bool $hard, int $tenantId): bool;

    /**
     * Lists customers with optional filtering and pagination.
     *
     * @param array $filters Filtering options (search, limit, offset, etc.)
     * @param int $tenantId Tenant ID for isolation
     * @return array{items: array, total: int} Customer list with total count
     */
    public function list(array $filters, int $tenantId): array;
}
