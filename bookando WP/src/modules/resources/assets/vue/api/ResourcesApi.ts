import http from '@assets/http'

export type AvailabilitySlot = {
  id?: string
  date: string | null
  start: string | null
  end: string | null
  capacity: number | null
  notes: string
}

export type ResourceEntry = {
  id?: string
  name: string
  description: string
  capacity: number | null
  tags: string[]
  availability: AvailabilitySlot[]
  created_at?: string
  updated_at?: string
  type: 'locations' | 'rooms' | 'materials'
}

export type ResourcesState = {
  locations: ResourceEntry[]
  rooms: ResourceEntry[]
  materials: ResourceEntry[]
}

const BASE_URL = (window as any).BOOKANDO_VARS?.rest_url || '/wp-json/bookando/v1/resources'

export async function fetchState(): Promise<ResourcesState> {
  const { data } = await http.get<{ data: ResourcesState; meta: any }>(`${BASE_URL}/state`)
  return data.data
}

export async function saveResource(type: ResourceEntry['type'], payload: Partial<ResourceEntry>): Promise<ResourceEntry> {
  const { data } = await http.post<{ data: ResourceEntry; meta: any }>(`${BASE_URL}/${type}`, payload)
  return data.data
}

export async function deleteResource(type: ResourceEntry['type'], id: string): Promise<boolean> {
  const { data } = await http.delete<{ data: { deleted: boolean; id: string }; meta: any }>(`${BASE_URL}/${type}/${id}`)
  return !!data?.data?.deleted
}
