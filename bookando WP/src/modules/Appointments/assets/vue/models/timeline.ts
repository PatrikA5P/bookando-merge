export type AppointmentStatus = 'pending' | 'approved' | 'confirmed' | 'cancelled' | 'noshow'

export interface AppointmentTimelineItem {
  type: 'appointment'
  id: number
  status: AppointmentStatus
  start_utc: string
  end_utc: string
  start_local: string | null
  end_local: string | null
  service: {
    id: number | null
    name: string | null
  }
  customer: {
    id: number | null
    name: string | null
    email: string | null
  }
  event: {
    id: number | null
    name: string | null
    type: string | null
  }
  persons: number
  meta: Record<string, any>
}

export interface EventTimelineItem {
  type: 'event'
  event_id: number
  period_id: number | null
  name: string
  event_type: string
  status: string
  start_utc: string | null
  end_utc: string | null
  start_local: string | null
  end_local: string | null
  participants: number
  capacity: number | null
  price: number | null
}

export type TimelineItem = AppointmentTimelineItem | EventTimelineItem

export interface TimelineGroup {
  date: string
  label: string
  items: TimelineItem[]
}

export interface LookupCustomer {
  id: number
  name: string
  email?: string
  phone?: string
}

export interface LookupService {
  id: number
  name: string
  status?: string
}

export interface LookupEvent {
  event_id: number
  period_id: number | null
  name: string
  type: string
  status: string
  start_local: string | null
  end_local: string | null
}

export interface LookupResponse {
  customers: LookupCustomer[]
  services: LookupService[]
  events: LookupEvent[]
}

export interface CreateAppointmentPayload {
  customer_id: number
  service_id: number
  starts_at: string
  ends_at?: string
  status?: AppointmentStatus
  persons?: number
  price?: number
  employee_id?: number | null
  location_id?: number | null
  event_id?: number | null
  meta?: Record<string, any>
}

export interface UpdateAppointmentPayload {
  customer_id?: number
  service_id?: number
  employee_id?: number | null
  location_id?: number | null
  event_id?: number | null
  starts_at?: string
  ends_at?: string
  status?: AppointmentStatus
  price?: number
  persons?: number
  meta?: Record<string, any>
}

export interface AssignEventPayload {
  event_id: number
  period_id?: number | null
  customer_id: number
  service_id?: number | null
  status?: AppointmentStatus
  starts_at?: string
  ends_at?: string
  persons?: number
  employee_id?: number | null
  location_id?: number | null
  meta?: Record<string, any>
}
