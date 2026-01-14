import httpBase from '@assets/http'
import type {
  TimelineGroup,
  LookupResponse,
  CreateAppointmentPayload,
  UpdateAppointmentPayload,
  AssignEventPayload
} from '../models/timeline'

const http = httpBase.module('appointments')

/** API response wrapper for timeline */
interface TimelineResponse {
  data: TimelineGroup[]
}

/** API response for create/update appointment operations */
interface AppointmentResponse {
  id: number
  created?: boolean
  updated?: boolean
  [key: string]: unknown
}

/** API response for assign operations */
interface AssignResponse {
  success: boolean
  event_id?: number
  customer_id?: number
  [key: string]: unknown
}

export async function fetchTimeline(params: { from?: string; to?: string } = {}): Promise<TimelineGroup[]> {
  const { data } = await http.get<TimelineGroup[] | TimelineResponse>('timeline', params)
  return Array.isArray(data) ? data : ((data as TimelineResponse)?.data ?? [])
}

export async function fetchLookups(params: { search?: string; limit?: number } = {}): Promise<LookupResponse> {
  const { data } = await http.get<LookupResponse>('lookups', params)
  return data as LookupResponse
}

export async function createAppointment(payload: CreateAppointmentPayload): Promise<AppointmentResponse> {
  const { data } = await http.post<AppointmentResponse>('appointments', payload)
  return data
}

export async function updateAppointment(id: number, payload: UpdateAppointmentPayload): Promise<AppointmentResponse> {
  const { data } = await http.put<AppointmentResponse>(`appointments/${id}`, payload)
  return data
}

export async function assignCustomerToEvent(payload: AssignEventPayload): Promise<AssignResponse> {
  const { data } = await http.post<AssignResponse>('assign', payload)
  return data
}
