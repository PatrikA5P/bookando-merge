import httpBase from '@assets/http'
import type { Customer } from '../models/CustomersModel'
import { normalizeCountryCode } from '@core/Util/formatters'

// ⚙️ Modul-Client: /wp-json/bookando/v1/customers/...
const http = httpBase.module('customers')

// --- interne Helfer ---
const v = (x: unknown) => (x === '' ? null : x)

function normalizeBirthdate(input: unknown): string | null {
  if (input == null || input === '') return null
  if (input instanceof Date && !isNaN(input.getTime())) {
    const y = input.getFullYear()
    const m = String(input.getMonth() + 1).padStart(2, '0')
    const d = String(input.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
  }
  const s = String(input).trim()
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s
  const m = s.match(/^(\d{2})\.(\d{2})\.(\d{4})$/)
  return m ? `${m[3]}-${m[2]}-${m[1]}` : null
}

/** Customer API payload interface */
export interface CustomerPayload {
  first_name?: string | null
  last_name?: string | null
  email?: string | null
  phone?: string | null
  address?: string | null
  address_2?: string | null
  zip?: string | null
  city?: string | null
  country?: string | null
  gender?: string | null
  birthdate?: string | null
  language?: string | null
  note?: string | null
  description?: string | null
  avatar_url?: string | null
  timezone?: string | null
  external_id?: string | null
  status?: string | null
}

/** Whitelist-Builder für API-Calls (POST/PUT) */
export function toPayload(c: Customer): CustomerPayload {
  return {
    first_name: v(c.first_name),
    last_name:  v(c.last_name),
    email:      v(c.email),
    phone:      v(c.phone),
    address:    v(c.address),
    address_2:  v(c.address_2),
    zip:        v(c.zip),
    city:       v(c.city),
    country:    v(normalizeCountryCode(c.country)),
    gender:     v(c.gender || null),
    birthdate:  v(normalizeBirthdate(c.birthdate)),
    language:   v(c.language || 'de'),
    note:       v(c.note),
    description: v(c.description),
    avatar_url: v(c.avatar_url),
    timezone:   v(c.timezone),
    external_id: v(c.external_id),
    status:     v(c.status || 'active'),
  }
}

/** API query parameters for fetching customers */
export interface CustomersQuery {
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

// ⬇️ Default: auch soft-deleted anzeigen, Hard-Deleted NIE (macht der Server).
export async function getCustomers(params: CustomersQuery = {}): Promise<Customer[]> {
  // Limit auf 10000 setzen um ALLE Kunden zu laden (Backend-Pagination später implementieren)
  const defaults: CustomersQuery = {
    include_deleted: 'soft',
    limit: 10000  // Lade alle Kunden auf einmal (Client-side Pagination)
  }
  // GET /wp-json/bookando/v1/customers (not customers/customers!)
  const res = await http.get<Customer[] | ApiListResponse<Customer>>('', { ...defaults, ...params })
  // Response Interceptor unwrappt automatisch Response::ok() Format
  // Ergebnis: { data: [...], total: 142, limit: 10000, offset: 0 }
  if (Array.isArray(res.data)) {
    return res.data
  }
  const payload = res.data as ApiListResponse<Customer> & { items?: Customer[] }
  return payload?.data ?? payload?.items ?? []
}

/** API response for single customer */
interface CustomerResponse {
  id: number
  [key: string]: unknown
}

/** API response for delete operations */
interface DeleteResponse {
  deleted: boolean
  id: number
  hard?: boolean
}

export async function getCustomer(id: number): Promise<Customer> {
  // GET /wp-json/bookando/v1/customers/{id}
  const res = await http.get<Customer>(`${id}`)
  // Response Interceptor unwrappt automatisch
  const data = res.data as Customer
  return {
    ...data,
    country: normalizeCountryCode(data.country), // 'CH' | null
  }
}

export async function createCustomer(data: Customer): Promise<CustomerResponse> {
  const res = await http.post<CustomerResponse>('', toPayload(data))
  return res.data
}

export async function updateCustomer(id: number, data: Customer): Promise<CustomerResponse> {
  const res = await http.put<CustomerResponse>(`${id}`, toPayload(data))
  return res.data
}

export async function deleteCustomer(id: number, opts: { hard?: boolean } = {}): Promise<DeleteResponse> {
  const query = opts.hard ? { hard: 1 } : undefined
  const res = await http.del<DeleteResponse>(`${id}`, query)
  return res.data
}
