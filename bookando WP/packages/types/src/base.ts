/**
 * @bookando/types - Base Types
 * Base interfaces and types for Multi-Tenant support
 */

/**
 * Base interface for all tenant-scoped entities
 * All models that require tenant isolation should extend this
 */
export interface TenantScoped {
  tenantId: number;
}

/**
 * Base interface for all entities with timestamps
 */
export interface Timestamped {
  createdAt?: string; // ISO 8601
  updatedAt?: string; // ISO 8601
}

/**
 * Base interface for soft-deletable entities
 */
export interface SoftDeletable {
  deletedAt?: string | null; // ISO 8601
}

/**
 * Complete base entity with all common fields
 */
export interface BaseEntity extends TenantScoped, Timestamped, SoftDeletable {
  id: string;
}

/**
 * Custom field for extensible data
 */
export interface CustomField {
  key: string;
  value: string;
}

/**
 * Pagination parameters
 */
export interface PaginationParams {
  page?: number;
  perPage?: number;
  offset?: number;
  limit?: number;
}

/**
 * Sorting parameters
 */
export interface SortParams {
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
}

/**
 * Standard list response with pagination
 */
export interface ListResponse<T> {
  data: T[];
  meta: {
    total: number;
    page: number;
    perPage: number;
    totalPages: number;
  };
}

/**
 * API Error response
 */
export interface ApiError {
  code: string;
  message: string;
  details?: Record<string, unknown>;
}
