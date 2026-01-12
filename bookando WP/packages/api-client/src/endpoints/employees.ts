/**
 * @bookando/api-client - Employees Endpoints
 */

import type { Employee, EmployeeStatus, ListResponse, PaginationParams, SortParams } from '@bookando/types';
import type { BookandoApiClient } from '../client';

export interface EmployeesListParams extends PaginationParams, SortParams {
  status?: EmployeeStatus;
  department?: string;
  search?: string;
  [key: string]: unknown;
}

export function createEmployeesEndpoints(client: BookandoApiClient) {
  return {
    list: (params?: EmployeesListParams) =>
      client.get<ListResponse<Employee>>('/employees', params),

    get: (id: string) =>
      client.get<Employee>(`/employees/${id}`),

    create: (data: Omit<Employee, 'id' | 'tenantId' | 'createdAt' | 'updatedAt'>) =>
      client.post<Employee>('/employees', data),

    update: (id: string, data: Partial<Employee>) =>
      client.put<Employee>(`/employees/${id}`, data),

    delete: (id: string) =>
      client.delete<void>(`/employees/${id}`),

    // Calendar integrations
    listCalendars: (id: string) =>
      client.get(`/employees/${id}/calendars`),

    startOauth: (id: string, provider: 'google' | 'outlook', mode: 'ro' | 'wb' = 'ro') =>
      client.post(`/employees/${id}/calendar/connections/oauth/start`, { provider, mode }),

    connectIcs: (id: string, url: string, name?: string) =>
      client.post(`/employees/${id}/calendar/connections/ics`, { url, name }),

    disconnectIcs: (id: string, connectionId: number) =>
      client.delete(`/employees/${id}/calendar/connections/ics`, { params: { connection_id: connectionId } }),

    replaceCalendars: (id: string, calendars: any[]) =>
      client.put(`/employees/${id}/calendars`, { calendars }),

    sendInvite: (id: string, payload: any) =>
      client.post(`/employees/${id}/calendar/invite`, payload),
  };
}
