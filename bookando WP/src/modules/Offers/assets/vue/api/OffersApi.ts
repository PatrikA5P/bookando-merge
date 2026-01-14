import http from '@assets/http'
import type { Offers } from '../models/OffersModel'

const PATH = 'offers'

/** API query parameters for fetching offers */
export interface OffersQuery {
  include_deleted?: 'soft' | 'no' | 'all'
  limit?: number
  offset?: number
  search?: string
  status?: string
  [key: string]: unknown
}

/** API response wrapper */
interface ApiListResponse<T> {
  data: T[]
  total?: number
  limit?: number
  offset?: number
}

/** API response for create/update operations */
export interface OfferResponse {
  id: number
  updated?: boolean
  created?: boolean
  [key: string]: unknown
}

/** API response for delete operations */
interface DeleteResponse {
  deleted: boolean
  id: number
  hard?: boolean
}

/** API response for bulk operations */
interface BulkResponse {
  affected: number
  success: boolean
  ids?: Array<number | string>
}

export async function list(params: OffersQuery = {}): Promise<Offers[]> {
  const { data } = await http.get<Offers[] | ApiListResponse<Offers>>(PATH, params)
  return Array.isArray(data) ? data : ((data as ApiListResponse<Offers>)?.data ?? [])
}

export async function getOne(id: number | string): Promise<Offers> {
  const { data } = await http.get<Offers>(`${PATH}/${id}`)
  return data as Offers
}

export async function create(payload: Partial<Offers>): Promise<OfferResponse> {
  const { data } = await http.post<OfferResponse>(`${PATH}`, payload)
  return data
}

export async function update(id: number | string, payload: Partial<Offers>): Promise<OfferResponse> {
  const { data } = await http.put<OfferResponse>(`${PATH}/${id}`, payload)
  return data
}

export async function remove(id: number | string, options: { hard?: boolean } = {}): Promise<DeleteResponse> {
  const query = options.hard ? { hard: 1 } : undefined
  const { data } = await http.del<DeleteResponse>(`${PATH}/${id}`, query)
  return data
}

export async function bulk(action: string, ids: Array<number | string>): Promise<BulkResponse> {
  const { data } = await http.post<BulkResponse>(`${PATH}/bulk`, { action, ids })
  return data
}
