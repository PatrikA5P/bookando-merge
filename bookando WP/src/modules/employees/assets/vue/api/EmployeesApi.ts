// src/modules/employees/assets/vue/api/EmployeesApi.ts
console.log('[employees:api] LOADED EmployeesApi.ts v3 – standalone endpoints enabled');

import httpBase from '@assets/http'
import type { AxiosResponse } from 'axios'

// ⚙️ Modul-spezifischen HTTP-Client: Basis = /wp-json/bookando/v1/employees/...
const http = httpBase.module('employees')

// ============================================================================
// Types (schlank – Quelle fürs volle Domain-Modell bleibt EmployeesModel.ts)
// ============================================================================

export type ID = number

export interface DayOff {
  id?: ID
  user_id?: ID
  name?: string | null
  note?: string | null
  start_date: string            // 'YYYY-MM-DD'
  start_time?: string | null    // 'HH:mm:ss' | null
  end_date: string              // 'YYYY-MM-DD'
  repeat_yearly?: 0 | 1 | boolean
}

export interface WorkingHour {
  id?: ID
  week_day_id: number                 // 1..7
  location_id?: ID | null
  service_id?: ID | null
  start_time: string                  // 'HH:mm:ss' | 'HH:mm'
  end_time: string
  is_break?: 0 | 1 | boolean          // 1 = Pause
  combo_id?: ID | null                // Gruppierung (Legacy)
}

// ---- Wochen-Sets (Standardarbeitswoche) ------------------------------------

export interface WorkInterval {
  id?: ID
  set_id?: ID
  start_time: string           // 'HH:mm' | 'HH:mm:ss'
  end_time: string
  is_break?: 0 | 1 | boolean
}

export interface WorkdaySet {
  id?: ID
  week_day_id: number
  label?: string | null
  sort?: number
  services?: ID[]
  locations?: ID[]
  intervals: WorkInterval[]
}

// ---- Special-Day-Sets (wie WorkdaySets, aber mit Datumsspanne) --------------

export interface SpecialInterval {
  id?: ID
  set_id?: ID
  start_time: string
  end_time: string
  is_break?: 0 | 1 | boolean
}

export interface SpecialDaySet {
  id?: ID
  start_date: string
  end_date:   string
  label?: string | null
  sort?: number
  services?: ID[]
  locations?: ID[]
  intervals: SpecialInterval[]
}

// ---- Legacy Special Days (flach, jede Zeile = 1 Zeitblock) ------------------

export interface SpecialDay {
  id?: ID
  name?: string | null
  start_date: string
  start_time?: string | null
  end_date?: string | null
  end_time?: string | null
  repeat?: 'none' | 'daily' | 'weekly' | 'monthly' | 'yearly'
  locations?: ID[]
  services?: ID[]
}

export interface CalendarConn {
  id?: ID
  calendar: 'google' | 'microsoft' | 'exchange' | 'icloud' | 'ics'
  calendar_id: string
  token?: string
}

export interface Employee {
  id: ID
  tenant_id?: ID | null
  first_name?: string | null
  last_name?: string | null
  email?: string | null
  phone?: string | null
  address?: string | null
  address_2?: string | null
  zip?: string | null
  city?: string | null
  country?: string | null
  birthdate?: string | null      // 'YYYY-MM-DD'
  gender?: 'm' | 'f' | 'd' | 'n' | null
  language?: string | null
  note?: string | null
  avatar_url?: string | null
  timezone?: string | null
  status?: 'active' | 'blocked' | 'deleted'
  roles?: string[] | null

  // Nested Collections
  working_hours?: WorkingHour[]
  workday_sets?: WorkdaySet[]         // ersetzt working_hours
  special_day_sets?: SpecialDaySet[]  // NEU: Special Days als Sets
  days_off?: DayOff[]
  special_days?: SpecialDay[]         // Legacy: flache Liste
  calendars?: CalendarConn[]

  // Timestamps vom Server
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

// Relationen, die wir im Detail idealerweise mitladen (Backend-abhängig)
export const EMPLOYEE_DETAIL_WITH = [
  'workday_sets',
  'workday_sets.intervals',
  'workday_sets.services',
  'workday_sets.locations',

  'special_day_sets',
  'special_day_sets.intervals',
  'special_day_sets.services',
  'special_day_sets.locations',

  'days_off',
  'calendars',
] as const

type DetailWith = (typeof EMPLOYEE_DETAIL_WITH)[number]

// ============================================================================
// Helpers (Logging, Zeit-Format, Normalisierung)
// ============================================================================

function log(...a: unknown[]) {
  console.log('[employees:api]', ...a)
}

/** HH:mm oder HH:mm:ss → immer HH:mm, sonst '' */
function toHHmm(value: unknown): string {
  const s = String(value ?? '').trim()
  if (!s) return ''
  return /^\d{2}:\d{2}(:\d{2})?$/.test(s) ? s.slice(0, 5) : ''
}

/** normalize 0/1/true/false auf 0|1 */
function normalizeIsBreak(value: unknown): 0 | 1 {
  return (value === 1 || value === '1' || value === true) ? 1 : 0
}

interface IntervalRaw {
  id?: number
  set_id?: number
  start_time?: string
  end_time?: string
  is_break?: number | boolean
}

interface SetWithIntervals {
  id?: number
  intervals?: IntervalRaw[]
}

/** Intervall-Liste für Workday/Special-Sets robust einlesen */
function ensureIntervalsArray(set: SetWithIntervals): WorkInterval[] {
  const list = Array.isArray(set?.intervals) ? set.intervals : []
  return list.map((it: IntervalRaw) => ({
    id:         Number(it.id ?? 0) || undefined,
    set_id:     Number(it.set_id ?? set.id ?? 0) || undefined,
    start_time: toHHmm(it.start_time),
    end_time:   toHHmm(it.end_time),
    is_break:   normalizeIsBreak(it.is_break),
  }))
}
function ensureSDIntervalsArray(set: SetWithIntervals): SpecialInterval[] {
  const list = Array.isArray(set?.intervals) ? set.intervals : []
  return list.map((it: IntervalRaw) => ({
    id:         Number(it.id ?? 0) || undefined,
    set_id:     Number(it.set_id ?? set.id ?? 0) || undefined,
    start_time: toHHmm(it.start_time),
    end_time:   toHHmm(it.end_time),
    is_break:   normalizeIsBreak(it.is_break),
  }))
}

// ============================================================================
// UI-Helfer (Form-VM → API)
// ============================================================================

// UI-Format aus EmployeesForm (gruppiert pro Date-Range + Combo)
type UiSpecialDay = {
  start: string                 // yyyy-MM-dd
  end?: string                  // yyyy-MM-dd
  serviceIds?: number[]
  locationIds?: number[]
  work?: { start: string; end: string }[]
  breaks?: { start: string; end: string }[]
}

function isUiSpecialDayArray(value: unknown): value is UiSpecialDay[] {
  return Array.isArray(value) && value.length > 0 && (
    'start' in value[0] || 'work' in value[0] || 'serviceIds' in value[0] || 'locationIds' in value[0]
  )
}

/**
 * (Legacy) UI-Gruppen → flache Special-Day-Zeilen.
 * Nur „work“ wird persistiert; „breaks“ bleiben derived.
 */
function mapUiSpecialDaysToApiRows(ui: UiSpecialDay[]): SpecialDay[] {
  const out: SpecialDay[] = []
  for (const item of ui) {
    const start_date = String(item.start || '').trim()
    const end_date   = String(item.end || item.start || '').trim()
    const services   = (item.serviceIds  || []).map(Number).filter(Boolean)
    const locations  = (item.locationIds || []).map(Number).filter(Boolean)

    for (const w of (item.work || [])) {
      const st = toHHmm(w.start)
      const et = toHHmm(w.end)
      if (!st || !et || st >= et) continue
      out.push({
        start_date,
        end_date,
        start_time: st,
        end_time:   et,
        services,
        locations,
      })
    }
  }
  return out
}

/**
 * (NEU) UI-Gruppen → Special-Day-Sets.
 * 1 Combo = 1 Set; Intervals = work + breaks; sort = Reihenfolge innerhalb Range.
 */
function mapVmSpecialDaysToSets(ui: UiSpecialDay[]): SpecialDaySet[] {
  const byRange = new Map<string, UiSpecialDay[]>()
  for (const row of (ui || [])) {
    const key = `${row.start}|${row.end || row.start}`
    ;(byRange.get(key) || byRange.set(key, []).get(key)!).push(row)
  }

  const toMin = (s:string) => {
    const [h,m] = (s||'').split(':').map(Number)
    return (isFinite(h)&&isFinite(m)) ? h*60+m : Infinity
  }

  const out: SpecialDaySet[] = []
  for (const [key, list] of byRange) {
    // Kombos stabil sortieren (nach frühestem Work-Beginn)
    list.sort((a,b) => {
      const aw = Math.min(...(a.work||[]).map(w=>toMin(w.start)))
      const bw = Math.min(...(b.work||[]).map(w=>toMin(w.start)))
      return aw - bw
    })

    list.forEach((row, idx) => {
      const service_id  = row.serviceIds?.[0] ?? null
      const location_id = row.locationIds?.[0] ?? null
      const intervals: SpecialInterval[] = [
        ...(row.work   || []).map(w => ({ start_time: toHHmm(w.start), end_time: toHHmm(w.end), is_break: 0 })),
        ...(row.breaks || []).map(b => ({ start_time: toHHmm(b.start), end_time: toHHmm(b.end), is_break: 1 })),
      ].filter(it => it.start_time && it.end_time && it.start_time < it.end_time)

      out.push({
        start_date: key.split('|')[0],
        end_date:   key.split('|')[1],
        services:   row.serviceIds || [],
        locations:  row.locationIds || [],
        sort: idx,
        label: null,
        intervals
      })
    })
  }
  return out
}

// ============================================================================
// Standalone Endpoints – Workday Sets
// ============================================================================

/**
 * GET /{employeeId}/workday-sets
 * Wichtig: http-Basis ist .../employees → also KEIN "employees/" voranstellen.
 */
interface WorkdaySetResponse {
  workday_sets?: WorkdaySet[]
}

interface RawWorkdaySet {
  id?: number
  week_day_id?: number
  label?: string | null
  sort?: number
  services?: number[]
  locations?: number[]
  intervals?: IntervalRaw[]
}

export async function fetchWorkdaySetsStandalone(employeeId: ID): Promise<WorkdaySet[]> {
  const url = `${employeeId}/workday-sets`
  const res = await http.get<WorkdaySet[] | WorkdaySetResponse>(url)
  const raw = Array.isArray((res.data as WorkdaySetResponse)?.workday_sets) ? (res.data as WorkdaySetResponse).workday_sets
            : Array.isArray(res.data) ? res.data : []

  const sets: WorkdaySet[] = (raw || []).map((s: RawWorkdaySet) => ({
    id: Number(s.id ?? 0) || undefined,
    week_day_id: Number(s.week_day_id ?? 0),
    label: s.label ?? null,
    sort: s.sort != null ? Number(s.sort) : undefined,
    services:  Array.isArray(s.services)  ? s.services.map(Number).filter(Boolean)  : [],
    locations: Array.isArray(s.locations) ? s.locations.map(Number).filter(Boolean) : [],
    intervals: ensureIntervalsArray(s),
  }))

  return sets
}

/** POST /{employeeId}/workday-sets – MERGE (Delta) */
export async function saveWorkdaySetsStandalone(
  employeeId: ID,
  payload: { upsert: WorkdaySet[]; delete_ids?: ID[] }
) {
  const url = `${employeeId}/workday-sets`
  const res = await http.post(url, { mode: 'merge', ...payload })
  return res.data
}

// ============================================================================
// Standalone Endpoints – Special-Day Sets
// ============================================================================

interface SpecialDaySetResponse {
  special_day_sets?: SpecialDaySet[]
}

interface RawSpecialDaySet {
  id?: number
  start_date?: string
  date_start?: string
  end_date?: string
  date_end?: string
  label?: string | null
  sort?: number
  services?: number[]
  locations?: number[]
  intervals?: IntervalRaw[]
}

/** GET /{employeeId}/special-day-sets */
export async function fetchSpecialDaySetsStandalone(employeeId: ID): Promise<SpecialDaySet[]> {
  const url = `${employeeId}/special-day-sets`
  const res = await http.get<SpecialDaySet[] | SpecialDaySetResponse>(url)
  const raw = Array.isArray((res.data as SpecialDaySetResponse)?.special_day_sets) ? (res.data as SpecialDaySetResponse).special_day_sets
            : Array.isArray(res.data) ? res.data : []

  return (raw || []).map((s: RawSpecialDaySet) => ({
    id: Number(s.id ?? 0) || undefined,
    start_date: String(s.start_date || s.date_start || ''),
    end_date:   String(s.end_date   || s.date_end   || s.start_date || ''),
    label: s.label ?? null,
    sort:  s.sort != null ? Number(s.sort) : undefined,
    services:  Array.isArray(s.services)  ? s.services.map(Number).filter(Boolean)  : [],
    locations: Array.isArray(s.locations) ? s.locations.map(Number).filter(Boolean) : [],
    intervals: ensureSDIntervalsArray(s),
  }))
}

interface CanonicalInterval {
  id?: number
  start_time: string
  end_time: string
  is_break: 0 | 1
}

interface CanonicalSet {
  id?: number
  start_date: string
  end_date: string
  label: string | null
  sort?: number
  services: number[]
  locations: number[]
  intervals: CanonicalInterval[]
}

/** POST /{employeeId}/special-day-sets – Merge der Sets */
function canonSdSet(s: Partial<SpecialDaySet>): CanonicalSet {
  const norm = (t: string) => /^\d{2}:\d{2}/.test(String(t||'')) ? String(t).slice(0,5) : ''
  const ints = (s.intervals||[])
    .map((it: Partial<SpecialInterval>) =>({
      id: it.id ? Number(it.id) : undefined,
      start_time: norm(it.start_time || ''),
      end_time:   norm(it.end_time || ''),
      is_break:   (Number(it.is_break) ? 1 : 0) as 0 | 1,
    }))
    .filter((it: CanonicalInterval) => it.start_time && it.end_time && it.start_time < it.end_time)
    .sort((a: CanonicalInterval, b: CanonicalInterval) =>
      (a.is_break - b.is_break) || a.start_time.localeCompare(b.start_time) || a.end_time.localeCompare(b.end_time))
  const arr = (a?: number[]) => Array.from(new Set((a||[]).map(Number).filter(Boolean))).sort((x,y)=>x-y)
  return {
    id: s.id ? Number(s.id) : undefined,
    start_date: String(s.start_date||''),
    end_date:   String(s.end_date||s.start_date||''),
    label: s.label ?? null,
    sort: s.sort != null ? Number(s.sort) : undefined,
    services:  arr(s.services),
    locations: arr(s.locations),
    intervals: ints,
  }
}

export async function saveSpecialDaySetsMerge(
  employeeId: ID,
  prevSets: SpecialDaySet[],
  nextSets: SpecialDaySet[]
) {
  const prev = (prevSets||[]).map(canonSdSet)
  const next = (nextSets||[]).map(canonSdSet)

  const byIdPrev = new Map<number, CanonicalSet>()
  prev.forEach(s => { if (s.id) byIdPrev.set(s.id, s) })

  const upsert: CanonicalSet[] = []
  const nextIds = new Set<number>()

  for (const s of next) {
    if (!s.id) { upsert.push(s); continue }
    nextIds.add(s.id)
    const old = byIdPrev.get(s.id)
    if (!old) { upsert.push(s); continue }
    const changed =
      s.start_date !== old.start_date ||
      s.end_date   !== old.end_date   ||
      (s.label ?? null) !== (old.label ?? null) ||
      (s.sort ?? null)  !== (old.sort ?? null)  ||
      JSON.stringify(s.services)  !== JSON.stringify(old.services) ||
      JSON.stringify(s.locations) !== JSON.stringify(old.locations) ||
      JSON.stringify(s.intervals) !== JSON.stringify(old.intervals)
    if (changed) upsert.push(s)
  }

  const delete_ids:number[] = []
  for (const o of prev) {
    if (o.id && !nextIds.has(o.id)) delete_ids.push(o.id)
  }

  const url = `${employeeId}/special-day-sets`
  const res = await http.post(url, { mode: 'merge', upsert, delete_ids })
  return res.data
}


// ============================================================================
// Optional: Kalender separat laden
// ============================================================================

interface CalendarResponse {
  calendars?: CalendarConn[]
}

interface RawCalendar {
  id?: number
  provider?: string
  calendar?: string
  calendar_id?: string
  token?: string
}

async function fetchCalendarsStandalone(employeeId: ID): Promise<CalendarConn[]> {
  const url = `${employeeId}/calendars`
  const res = await http.get<CalendarConn[] | CalendarResponse>(url)

  const raw = Array.isArray((res.data as CalendarResponse)?.calendars)
    ? (res.data as CalendarResponse).calendars
    : Array.isArray(res.data)
      ? res.data
      : []

  const calendars = (raw || []).map((c: RawCalendar) => ({
    id: Number(c.id ?? 0) || undefined,
    calendar: (c.provider || c.calendar) as 'google' | 'microsoft' | 'exchange' | 'icloud' | 'ics',
    calendar_id: String(c.calendar_id ?? ''),
    token: c.token ?? undefined,
  }))

  log('fallback calendars:', { url, count: calendars.length })
  return calendars
}

export async function replaceCalendarsStandalone(
  employeeId: ID,
  calendars: CalendarConn[]
) {
  const url = `${employeeId}/calendars`
  const body = { calendars: calendars || [] }
  const res = await http.put(url, body)
  return res.data // { updated:true }
}

// ============================================================================
// Core Endpoints (List, Detail, CRUD)
// ============================================================================

/** API query parameters for fetching employees */
interface EmployeesQuery {
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

// Liste (inkl. Soft-Deleted optional)
export async function getEmployees(params: EmployeesQuery = {}): Promise<Employee[]> {
  const defaults: EmployeesQuery = { include_deleted: 'soft' }
  // GET /wp-json/bookando/v1/employees/employees
  const res = await http.get<Employee[] | ApiListResponse<Employee>>('employees', { ...defaults, ...params })
  return Array.isArray(res.data) ? (res.data as Employee[]) : ((res.data as ApiListResponse<Employee>)?.data ?? [])
}

/**
 * Detail laden:
 * 1) Primär Detail (optional mit ?with[])
 * 2) Parallel dedizierte /{id}/workday-sets und /{id}/special-days holen
 * 3) Kalender ggf. separat laden
 * 4) Session-Cache für Sets anwenden (Fallback), damit UI sofort responsiv bleibt
 */
export async function getEmployee(
  id: ID,
  opts?: { with?: DetailWith[] }
): Promise<Employee> {
  const detailUrl = `employees/${id}`
  const q = opts?.with?.length ? { 'with[]': opts.with } : undefined
  const [detailRes, wsetsRes, sdsetsRes] = await Promise.allSettled([
    http.get<Employee>(detailUrl, q),
    fetchWorkdaySetsStandalone(id),
    fetchSpecialDaySetsStandalone(id),
  ])

  const emp: Employee = (detailRes.status === 'fulfilled' ? detailRes.value.data : {}) as Employee

  const updatedAt = (detailRes.status === 'fulfilled' && detailRes.value.data?.updated_at) || ''
  const cacheKeyWork   = updatedAt ? `ba:emp:${id}:sets:${updatedAt}`   : `ba:emp:${id}:sets`
  const cacheKeySdSets = updatedAt ? `ba:emp:${id}:sdsets:${updatedAt}` : `ba:emp:${id}:sdsets`

  if (wsetsRes.status === 'fulfilled' && Array.isArray(wsetsRes.value)) {
    emp.workday_sets = wsetsRes.value
    try { sessionStorage.setItem(cacheKeyWork, JSON.stringify(wsetsRes.value)) } catch {}
  } else {
    try { const cached = sessionStorage.getItem(cacheKeyWork); if (cached) emp.workday_sets = JSON.parse(cached) } catch {}
  }

  if (sdsetsRes.status === 'fulfilled' && Array.isArray(sdsetsRes.value)) {
    emp.special_day_sets = sdsetsRes.value
    try { sessionStorage.setItem(cacheKeySdSets, JSON.stringify(sdsetsRes.value)) } catch {}
  } else {
    try { const cached = sessionStorage.getItem(cacheKeySdSets); if (cached) emp.special_day_sets = JSON.parse(cached) } catch {}
  }

  if (!Array.isArray(emp.calendars)) {
    try { emp.calendars = await fetchCalendarsStandalone(id) } catch {}
  }

  return emp as Employee
}

// Erstellen
export async function createEmployee(payload: Partial<Employee>): Promise<{ id: ID }> {
  const res = await http.post<{ id: ID }>('employees', payload)
  return res.data
}

// Aktualisieren (Partial-Update; Full-Replace von Collections nur, wenn Felder gesetzt sind)
export async function updateEmployee(id: ID, payload: Partial<Employee>): Promise<Employee | { updated: true }> {
  const res = await http.put<Employee | { updated: true }>(`employees/${id}`, payload)
  return res.data
}

// Löschen (soft/hard via Query)
export async function deleteEmployee(id: ID, opts: { hard?: boolean } = {}): Promise<{ deleted: boolean; hard: boolean }> {
  const q = opts.hard ? { hard: 1 } : undefined
  const res = await http.del<{ deleted: boolean; hard: boolean }>(`employees/${id}`, q)
  return res.data
}

// Bulk
export async function bulkEmployees(action: 'block' | 'activate' | 'soft_delete' | 'hard_delete', ids: ID[]) {
  const res: AxiosResponse<{ ok: boolean; affected: number }> = await http.post('employees/bulk', { action, ids })
  return res.data
}

// ============================================================================
// Convenience-Helper (kleine Qualitäts-of-Life Abkürzungen)
// ============================================================================

export async function saveDaysOffMerge(
  employeeId: ID,
  upsert: DayOff[],
  delete_ids: ID[] = []
): Promise<{ updated: true; mode: 'merge' }> {
  const url = `${employeeId}/days-off`
  const res = await http.post(url, { mode: 'merge', upsert, delete_ids })
  return res.data
}

export async function replaceDaysOff(employeeId: ID, items: DayOff[]): Promise<{ updated: true }> {
  const url = `${employeeId}/days-off`
  const res = await http.put(url, { days_off: items })
  return res.data
}

export async function addDayOff(employeeId: ID, item: DayOff): Promise<{ updated: true; mode: 'merge' }> {
  return saveDaysOffMerge(employeeId, [item], [])
}

export async function replaceWorkingHours(employeeId: ID, items: WorkingHour[]): Promise<Employee | { updated: true }> {
  return updateEmployee(employeeId, { working_hours: items })
}

export async function replaceWorkdaySets(employeeId: ID, sets: WorkdaySet[]) {
  return updateEmployee(employeeId, { workday_sets: sets })
}

/** Set-basiert: Special-Day-Sets direkt ersetzen */
export async function replaceSpecialDaySets(employeeId: ID, sets: SpecialDaySet[]) {
  return saveSpecialDaySetsStandalone(employeeId, sets)
}

export async function saveSpecialDaySetsStandalone(
  employeeId: ID,
  sets: SpecialDaySet[]
) {
  const url = `${employeeId}/special-day-sets`
  // Backcompat: ohne mode ⇒ Full-Replace wie vom Backend unterstützt
  const res = await http.post(url, { special_day_sets: sets })
  return res.data
}

// ICS verbinden: POST /employees/{id}/calendar/connections/ics
export async function connectIcs(
  employeeId: ID,
  payload: { url: string; name?: string }
) {
  const url = `${employeeId}/calendar/connections/ics`
  const res = await http.post(url, payload)
  return res.data as {
    id: number
    connection_id: number
    provider: 'ics'
    calendar_id: string
    name: string
    is_busy_source: 1 | 0
    is_default_write: 0
    created?: true
    updated?: true
  }
}

// OAuth-Verbindung starten: POST /employees/{id}/calendar/connections/oauth/start
export async function startOauth(
  employeeId: ID,
  provider: 'google' | 'microsoft',
  mode: 'ro' | 'wb' = 'ro'
) {
  const url = `${employeeId}/calendar/connections/oauth/start`
  const res = await http.post(url, { provider, mode })
  return res.data as { auth_url?: string }
}

// ICS trennen: DELETE /employees/{id}/calendar/connections/ics
export async function disconnectIcs(
  employeeId: ID,
  opts: { connection_id?: number; url?: string }
) {
  const url = `${employeeId}/calendar/connections/ics`
  const res = await http.del(url, opts)
  return res.data as { deleted: boolean; deleted_at?: string }
}

// ============================================================================
// Cache-Invalidierung (optional außerhalb nutzbar)
// ============================================================================

/** Session-Cache für Sets eines Mitarbeitenden invalidieren */
export function invalidateSetsCache(employeeId: ID) {
  try {
    const keys = Object.keys(sessionStorage)
    for (const k of keys) {
      if (k === `ba:emp:${employeeId}:sets` || k.startsWith(`ba:emp:${employeeId}:sets:`)) {
        sessionStorage.removeItem(k)
      }
      // neu: specialdays + alt: sdsets (cleanup/backcompat)
      if (
        k === `ba:emp:${employeeId}:specialdays` || k.startsWith(`ba:emp:${employeeId}:specialdays:`) ||
        k === `ba:emp:${employeeId}:sdsets`      || k.startsWith(`ba:emp:${employeeId}:sdsets:`)
      ) {
        sessionStorage.removeItem(k)
      }
    }
  } catch {}
}
