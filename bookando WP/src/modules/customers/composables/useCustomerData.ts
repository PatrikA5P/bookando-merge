// src/modules/customers/composables/useCustomerData.ts
import { reactive } from 'vue'

/* =============================================================================
 *  Public Types (Form/VM)
 * ============================================================================= */
export type Gender = '' | 'm' | 'f' | 'd' | 'n'
export type Status = 'active' | 'blocked' | 'deleted'

export type CustomerFormBase = {
  id?: number | string
  first_name: string
  last_name: string
  email?: string
  phone?: string
  address?: string
  zip?: string
  city?: string
  country?: any
  gender?: Gender
  birthdate?: string      // YYYY-MM-DD
  language?: string       // i18n code
  note?: string
  avatar_url?: any        // string | WP image object
  deleted_at?: string | null
  status?: Status
  description?: string
  // system
  created_at?: string
  updated_at?: string
}

export type CustomerStats = {
  total_appointments?: number
  last_appointment?: string
  next_appointment?: string
}

export type CustomerVM = {
  form: CustomerFormBase
  stats: CustomerStats
  meta?: Record<string, any>
}

/* =============================================================================
 *  NORMALIZER: API → VM
 * ============================================================================= */
export function normalizeCustomerFromApi(api: any): CustomerVM {
  const form: CustomerFormBase = {
    id: api?.id ?? undefined,
    first_name: String(api?.first_name ?? ''),
    last_name:  String(api?.last_name  ?? ''),
    email:      api?.email ? String(api.email) : '',
    phone:      api?.phone ? String(api.phone) : '',
    address:    String(api?.address ?? ''),
    zip:        String(api?.zip ?? ''),
    city:       String(api?.city ?? ''),
    country:    api?.country ?? null,
    gender:     (api?.gender ?? '') as Gender,
    birthdate:  String(api?.birthdate ?? ''),
    language:   String(api?.language ?? 'de'),
    note:       String(api?.note ?? ''),
    avatar_url: api?.avatar_url ?? '',
    deleted_at: api?.deleted_at ?? null,
    status:     (api?.status ?? 'active') as Status,
    description:String(api?.description ?? ''),
    created_at: api?.created_at ?? undefined,
    updated_at: api?.updated_at ?? undefined,
  }

  const stats: CustomerStats = {
    total_appointments: Number(api?.total_appointments ?? 0) || 0,
    last_appointment:   api?.last_appointment ? String(api.last_appointment) : '',
    next_appointment:   api?.next_appointment ? String(api.next_appointment) : '',
  }

  const meta = (api && typeof api.meta === 'object') ? api.meta as Record<string,any> : undefined
  return { form, stats, meta }
}

/* =============================================================================
 *  SERIALIZER: VM/Form → API payload
 * ============================================================================= */
export function serializeCustomerForSave(input: CustomerVM | CustomerFormBase) {
  const f = (input as any).form ? (input as CustomerVM).form : (input as CustomerFormBase)
  return {
    id: f.id ?? undefined,
    first_name: f.first_name?.trim() ?? '',
    last_name:  f.last_name?.trim() ?? '',
    email:      f.email?.trim() ?? '',
    phone:      f.phone?.trim() ?? '',
    address:    f.address?.trim() ?? '',
    zip:        f.zip?.trim() ?? '',
    city:       f.city?.trim() ?? '',
    country:    f.country ?? null,
    gender:     f.gender ?? '',
    birthdate:  f.birthdate ?? '',
    language:   f.language ?? 'de',
    note:       f.note ?? '',
    avatar_url: f.avatar_url ?? '',
    status:     f.status ?? 'active',
    deleted_at: f.deleted_at ?? null,
    description:f.description ?? '',
  }
}

/* =============================================================================
 *  DISPLAY helpers
 * ============================================================================= */
export type DataField = { key: string; label: string }

/** Einheitliche Feldliste für die Info-Panels (Desktop & Mobile) */
export function buildCustomerDataFields(t: (k:string)=>string, module = 'customers'): DataField[] {
  return [
    { key: 'id',         label: fieldLabelCompat(t, 'id', module) },
    { key: 'last_name',  label: fieldLabelCompat(t, 'last_name', module) },
    { key: 'first_name', label: fieldLabelCompat(t, 'first_name', module) },
    { key: 'email',      label: fieldLabelCompat(t, 'email', module) },
    { key: 'phone',      label: fieldLabelCompat(t, 'phone', module) },
    { key: 'address',    label: fieldLabelCompat(t, 'address', module) },
    { key: 'zip',        label: fieldLabelCompat(t, 'zip', module) },
    { key: 'city',       label: fieldLabelCompat(t, 'city', module) },
    { key: 'country',    label: fieldLabelCompat(t, 'country', module) },
    { key: 'language',   label: fieldLabelCompat(t, 'language', module) },
    { key: 'status',     label: fieldLabelCompat(t, 'status', module) },
    { key: 'gender',     label: fieldLabelCompat(t, 'gender', module) },
    { key: 'birthdate',  label: fieldLabelCompat(t, 'birthdate', module) },
    { key: 'created_at', label: fieldLabelCompat(t, 'created_at', module) },
    { key: 'updated_at', label: fieldLabelCompat(t, 'updated_at', module) },
  ]
}

function fieldLabelCompat(t:(k:string)=>string, field: string, module='customers'){
  try {
    return (t as any)(`fields.${module}.${field}`) || (t as any)(`fields.${field}`) || field
  } catch {
    return field
  }
}

/* =============================================================================
 *  DETAILS-CACHE (optional Lazy-Load)
 * ============================================================================= */
export type LoaderFn = (id:number) => Promise<any>
const _detailsCache = reactive<Record<number, any>>({})
const _loading      = reactive<Record<number, boolean>>({})

export function useCustomerDetailsCache(loadFn: LoaderFn){
  async function ensure(id:number){
    if (_detailsCache[id] || _loading[id]) return
    _loading[id] = true
    try { _detailsCache[id] = await loadFn(id) }
    finally { _loading[id] = false }
  }
  function getVM(id:number): CustomerVM | null {
    const api = _detailsCache[id]
    return api ? normalizeCustomerFromApi(api) : null
  }
  function getRaw(id:number){ return _detailsCache[id] || null }
  function isLoading(id:number){ return !!_loading[id] }
  function invalidate(id?:number){ id ? delete _detailsCache[id] : Object.keys(_detailsCache).forEach(k=>delete _detailsCache[+k]) }
  return { ensure, getVM, getRaw, isLoading, invalidate }
}

/* =============================================================================
 *  Small UI helpers – zentral, um Duplikate zu vermeiden
 * ============================================================================= */
export function initials(obj: { first_name?: string; last_name?: string }){
  return ((obj.first_name?.[0] || '') + (obj.last_name?.[0] || '')).toUpperCase()
}
export function normalizePhone(p: string | number){ return String(p ?? '').replace(/\s+/g, '') }
export function statusClass(val?: string){
  return val === 'active' ? 'active' : val === 'blocked' ? 'inactive' : 'deleted'
}

/** WP image object or string → usable src */
export function avatarSrcFromAny(value: any): string {
  if (!value) return ''
  if (typeof value === 'string') return value.trim() || ''
  if (typeof value === 'object'){
    if (typeof value.url === 'string' && value.url.trim()) return value.url
    if (typeof value.src === 'string' && value.src.trim()) return value.src
    const s = (value as any).sizes
    if (s){
      if (typeof s.thumbnail === 'string' && s.thumbnail.trim()) return s.thumbnail
      if (typeof s.medium === 'string' && s.medium.trim()) return s.medium
      if (typeof s.full === 'string' && s.full.trim()) return s.full
    }
  }
  return ''
}
