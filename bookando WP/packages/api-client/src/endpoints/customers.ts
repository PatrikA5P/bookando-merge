/**
 * @bookando/api-client - Customers Endpoints
 */

import type { Customer, CustomerStatus, ListResponse, PaginationParams, SortParams } from '@bookando/types';
import type { BookandoApiClient } from '../client';

export interface CustomersListParams extends PaginationParams, SortParams {
  status?: CustomerStatus;
  search?: string;
  [key: string]: unknown;
}

export function createCustomersEndpoints(client: BookandoApiClient) {
  return {
    list: (params?: CustomersListParams) =>
      client.get<ListResponse<Customer>>('/customers', params),

    get: (id: string) =>
      client.get<Customer>(`/customers/${id}`),

    create: (data: Omit<Customer, 'id' | 'tenantId' | 'createdAt' | 'updatedAt'>) =>
      client.post<Customer>('/customers', data),

    update: (id: string, data: Partial<Customer>) =>
      client.put<Customer>(`/customers/${id}`, data),

    delete: (id: string) =>
      client.delete<void>(`/customers/${id}`),

    block: (id: string) =>
      client.post<Customer>(`/customers/${id}/block`),

    activate: (id: string) =>
      client.post<Customer>(`/customers/${id}/activate`),

    export: (params?: CustomersListParams) =>
      client.get<Blob>('/customers/export', params),
  };
}
