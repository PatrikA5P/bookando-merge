/**
 * @bookando/api-client - Appointments Endpoints
 */

import type { Appointment, AppointmentStatus, ListResponse, PaginationParams, SortParams } from '@bookando/types';
import type { BookandoApiClient } from '../client';

export interface AppointmentsListParams extends PaginationParams, SortParams {
  status?: AppointmentStatus;
  customerId?: string;
  employeeId?: string;
  serviceId?: string;
  dateFrom?: string;
  dateTo?: string;
  search?: string;
  [key: string]: unknown;
}

export function createAppointmentsEndpoints(client: BookandoApiClient) {
  return {
    list: (params?: AppointmentsListParams) =>
      client.get<ListResponse<Appointment>>('/appointments', params),

    get: (id: string) =>
      client.get<Appointment>(`/appointments/${id}`),

    create: (data: Omit<Appointment, 'id' | 'tenantId' | 'createdAt' | 'updatedAt'>) =>
      client.post<Appointment>('/appointments', data),

    update: (id: string, data: Partial<Appointment>) =>
      client.put<Appointment>(`/appointments/${id}`, data),

    delete: (id: string) =>
      client.delete<void>(`/appointments/${id}`),

    cancel: (id: string, reason?: string) =>
      client.post<Appointment>(`/appointments/${id}/cancel`, { reason }),

    confirm: (id: string) =>
      client.post<Appointment>(`/appointments/${id}/confirm`),

    complete: (id: string) =>
      client.post<Appointment>(`/appointments/${id}/complete`),

    reschedule: (id: string, newDate: string, newStartTime: string) =>
      client.post<Appointment>(`/appointments/${id}/reschedule`, { date: newDate, startTime: newStartTime }),
  };
}
