// src/modules/employees/composables/useEmployeeData.ts
import { reactive, ref } from 'vue'

/* =============================================================================
 *  Public Types (Form/VM-kompatibel)
 * ============================================================================= */
export type SDTime = { id?: number; start: string; end: string }

export type DayCombo = {
  id?: number
  serviceIds: number[]
  locationIds: number[]
  work: SDTime[]
  breaks: SDTime[]
}
export type WorkDay = { key: DayKey; label: string; combos: DayCombo[] }
export type DayKey = 'mon'|'tue'|'wed'|'thu'|'fri'|'sat'|'sun'

export type UIDayOff = {
  id: number
  title: string
  note?: string
  dateStart: string // YYYY-MM-DD
  dateEnd: string   // YYYY-MM-DD
  repeatYearly: boolean
}

export type SDSpecialCombo = { id?: number; serviceIds:number[]; locationIds:number[]; work:SDTime[]; breaks:SDTime[] }
export type SDSpecialDayCard = { id:number; dateStart:string|null; dateEnd:string|null; items:SDSpecialCombo[] }

/** Minimaler Basisteil für Stammdaten */
export type EmployeeFormBase = {
  id?: number | string
  first_name: string
  last_name: string
  email?: string
  phone?: string
  address?: string
  address_2?: string
  zip?: string
  city?: string
  country?: any
  gender?: '' | 'm' | 'f' | 'd' | 'n'
  birthdate?: string
  language?: string
  timezone?: string
  work_locations?: any[]
  badge?: any
  employee_area_password?: string
  description?: string
  note?: string
  avatar_url?: string
  deleted_at?: string | null
  status?: 'active' | 'blocked' | 'deleted'
}

/** Vollständiges, normalisiertes ViewModel */
export type EmployeeVM = {
  form: EmployeeFormBase
  workingDays: WorkDay[]                 // Combos je Wochentag (für Form)
  daysOff: UIDayOff[]                    // UI-Shape (Form)
  specialDayCards: SDSpecialDayCard[]    // Cards + Combos (Form)
  assignedServicesFlat: Array<{ id:number; name:string }>
  serviceGroups?: ServiceGroup[]         // optional, falls du Gruppierung brauchst
  calendars: any[]
}

export type ServiceItem = {
  id: number
  name: string
  enabled: boolean
  minCapacity: number
  maxCapacity: number
  price: string
}
export type ServiceGroup = { id: number; name: string; enabled: boolean; services: ServiceItem[] }

/* =============================================================================
 *  Shared constants / helpers
 * ============================================================================= */
const DAYS: DayKey[] = ['mon','tue','wed','thu','fri','sat','sun']
const DAY_LABEL_FALLBACK: Record<DayKey, string> = {
  mon: 'Montag', tue: 'Dienstag', wed: 'Mittwoch', thu: 'Donnerstag', fri: 'Freitag', sat: 'Samstag', sun: 'Sonntag'
}
const dayIdToKey = (id:number): DayKey => DAYS[Math.max(1, Math.min(7, id)) - 1]
const normTime = (s: string) => String(s || '').slice(0, 5)
const asIds = (arr: any): number[] =>
  (Array.isArray(arr) ? arr : [])
    .map((entry:any) => (typeof entry === 'object' ? Number(entry?.id) : Number(entry)))
    .filter((n:number) => Number.isFinite(n))
    .sort((a,b)=>a-b)

/* =============================================================================
 *  NORMALIZER: API → VM
 * ============================================================================= */

export function normalizeEmployeeFromApi(api: any, opts?: {
  dayLabels?: Partial<Record<DayKey, string>>
}): EmployeeVM {
  const form: EmployeeFormBase = { ...(api || {}) }

  // --- Working Days ---
  const workingDays: WorkDay[] = DAYS.map(k => ({ key: k, label: (opts?.dayLabels?.[k] ?? DAY_LABEL_FALLBACK[k]), combos: [] }))
  if (Array.isArray(api?.workday_sets) && api.workday_sets.length) {
    const byDay = new Map<number, DayCombo[]>()
    for (const s of api.workday_sets) {
      const dayId = Number(s.week_day_id ?? 0)
      const combo: DayCombo = {
        id: Number(s.id ?? 0) || undefined,
        serviceIds:  s.services?.length ? asIds(s.services) : (s.service_id  != null ? [Number(s.service_id)]  : []),
        locationIds: s.locations?.length ? asIds(s.locations): (s.location_id != null ? [Number(s.location_id)] : []),
        work:   [],
        breaks: []
      }
      for (const it of (s.intervals || [])) {
        const start = normTime(it.start_time)
        const end   = normTime(it.end_time)
        if (!start || !end || start >= end) continue
        ;(Number(it.is_break) ? combo.breaks : combo.work).push({ id: Number(it.id ?? 0) || undefined, start, end })
      }
      const arr = byDay.get(dayId) || []
      arr.push(combo)
      byDay.set(dayId, arr)
    }
    for (const [dayId, combos] of byDay) {
      const key = dayIdToKey(dayId)
      const wd = workingDays.find(d => d.key === key)!
      wd.combos = sortCombosByFirstStart(combos)
    }
  } else if (Array.isArray(api?.working_hours)) {
    // Legacy: eine Combo pro Tag mit work/breaks
    const byDay = new Map<number, DayCombo>()
    for (const row of api.working_hours) {
      const dayId = Number(row.week_day_id || 0)
      const start = normTime(row.start_time)
      const end   = normTime(row.end_time)
      if (!start || !end || start >= end) continue
      const combo = byDay.get(dayId) || { id: undefined, serviceIds:[], locationIds:[], work:[], breaks:[] }
      ;(Number(row.is_break) ? combo.breaks : combo.work).push({ start, end })
      byDay.set(dayId, combo)
    }
    for (const [dayId, combo] of byDay) {
      const key = dayIdToKey(dayId)
      const wd = workingDays.find(d => d.key === key)!
      wd.combos = [combo]
    }
  }

  // --- Days Off ---
  const daysOff: UIDayOff[] = Array.isArray(api?.days_off)
    ? api.days_off.map((it:any) => ({
        id: Number(it.id || 0),
        title: String(it.name || ''),
        note: String(it.note || ''),
        dateStart: String(it.start_date || ''),
        dateEnd:   String(it.end_date || it.start_date || ''),
        repeatYearly: !!Number(it.repeat_yearly ?? 0),
      }))
    : []

  // --- Special Days → Cards + Combos ---
  let specialDayCards: SDSpecialDayCard[] = []
  if (Array.isArray(api?.special_day_sets) && api.special_day_sets.length) {
    specialDayCards = buildCardsFromSpecialDaySets(api.special_day_sets)
  } else if (Array.isArray(api?.special_days) && api.special_days.length) {
    specialDayCards = buildCardsFromFlatSpecialDays(api.special_days)
  }

  // --- Services ---
  const { groups, flat } = normalizeAssignedServices(api?.assigned_services ?? api?.services ?? [])

  // --- Calendars ---
  const calendars = Array.isArray(api?.calendars) ? api.calendars : []

  return {
    form,
    workingDays,
    daysOff,
    specialDayCards,
    assignedServicesFlat: flat,
    serviceGroups: groups,
    calendars
  }
}

/* =============================================================================
 *  SERIALIZER: VM → API-Payloads
 * ============================================================================= */

export function serializeDaysOffForApi(items: UIDayOff[]) {
  return items.map(d => ({
    id: d.id || undefined,
    name: d.title?.trim() || null,
    note: d.note || null,
    repeat_yearly: d.repeatYearly ? 1 : 0,
    start_date: d.dateStart,
    end_date: d.dateEnd || d.dateStart,
  }))
}

/** Flache Liste pro Combo (wie dein Form-Submit) – gut für dein bestehendes Save */
export function serializeSpecialDaysFromCards(cards: SDSpecialDayCard[]) {
  const out: Array<{
    id?: number
    start: string
    end: string
    serviceIds: number[]
    locationIds: number[]
    work: SDTime[]
    breaks: SDTime[]
  }> = []
  for (const c of cards) {
    const start = c.dateStart || ''
    const end   = c.dateEnd   || c.dateStart || ''
    if (!start) continue
    for (const it of c.items) {
      out.push({
        id: (it as any).id ?? undefined,
        start, end,
        serviceIds:  [...(it.serviceIds  || [])],
        locationIds: [...(it.locationIds || [])],
        work:   (it.work   || []).map(w => ({ ...w })),
        breaks: (it.breaks || []).map(b => ({ ...b })),
      })
    }
  }
  return out
}

/* =============================================================================
 *  DISPLAY-BUILDER (für Tabellen/Accordion)
 * ============================================================================= */

export type DisplayCombo = { serviceIds:number[]; locationIds:number[]; work:SDTime[]; breaks:SDTime[] }
export type DisplayDay = { key:string; label:string; combos:DisplayCombo[] }

export function buildWorkingDaysDisplay(vm: EmployeeVM, dayLabels?: Partial<Record<DayKey,string>>): DisplayDay[] {
  const labels = { ...DAY_LABEL_FALLBACK, ...(dayLabels || {}) }
  const days: DisplayDay[] = []
  for (const d of vm.workingDays) {
    if (!d.combos?.length) continue
    days.push({ key: d.key, label: labels[d.key], combos: sortCombosByFirstStart(d.combos) })
  }
  return days
}

export type SDCardDisplay = { dateStart:string|null; dateEnd:string|null; items:DisplayCombo[] }
export function buildSpecialDaysDisplay(vm: EmployeeVM): SDCardDisplay[] {
  // 1:1 Darstellung der Cards
  return vm.specialDayCards.map(c => ({
    dateStart: c.dateStart,
    dateEnd:   c.dateEnd ?? c.dateStart,
    items: c.items.map(it => ({
      serviceIds: [...it.serviceIds],
      locationIds: [...it.locationIds],
      work: [...(it.work || [])],
      breaks: [...(it.breaks || [])],
    }))
  }))
}

/* =============================================================================
 *  DETAILS-CACHE (für Tables/Mobile)
 * ============================================================================= */

type LoaderFn = (id:number)=>Promise<any>
const _detailsCache = reactive<Record<number, any>>({})
const _loading = reactive<Record<number, boolean>>({})

/** Einfache Cache-Hülle. Du gibst deine getEmployee(id) rein. */
export function useEmployeeDetailsCache(loadFn: LoaderFn) {
  async function ensure(id:number) {
    if (_detailsCache[id] || _loading[id]) return
    _loading[id] = true
    try {
      _detailsCache[id] = await loadFn(id)
    } finally {
      _loading[id] = false
    }
  }
  function getVM(id:number, opts?: { dayLabels?: Partial<Record<DayKey,string>> }): EmployeeVM | null {
    const api = _detailsCache[id]
    return api ? normalizeEmployeeFromApi(api, opts) : null
  }
  function getRaw(id:number){ return _detailsCache[id] || null }
  function isLoading(id:number){ return !!_loading[id] }
  function invalidate(id?:number){ id ? delete _detailsCache[id] : Object.keys(_detailsCache).forEach(k=>delete _detailsCache[+k]) }
  return { ensure, getVM, getRaw, isLoading, invalidate }
}

/* =============================================================================
 *  Internal helpers
 * ============================================================================= */

function sortCombosByFirstStart<T extends { work?: SDTime[] }>(arr: T[]): T[] {
  const first = (c:T) => {
    const xs = (c.work || []).map(w => w.start)
    return xs.length ? xs.sort()[0] : '99:99'
  }
  return [...arr].sort((a,b)=> first(a).localeCompare(first(b)))
}

function buildCardsFromSpecialDaySets(sets: any[]): SDSpecialDayCard[] {
  const groups = new Map<string, SDSpecialDayCard>() // key = start__end
  for (const s of (sets || [])) {
    const ds = String(s.start_date || '')
    const de = String(s.end_date || ds)
    const key = `${ds}__${de}`
    let card = groups.get(key)
    if (!card) {
      card = { id: 0, dateStart: ds || null, dateEnd: de || ds || null, items: [] }
      groups.set(key, card)
    }
    const work: SDTime[] = []
    const breaks: SDTime[] = []
    for (const it of (s.intervals || [])) {
      const st = normTime(it.start_time)
      const en = normTime(it.end_time)
      if (!st || !en || st >= en) continue
      ;(Number(it.is_break) ? breaks : work).push({ id: Number(it.id ?? 0) || undefined, start: st, end: en })
    }
    card.items.push({
      id: Number(s.id ?? 0) || undefined,
      serviceIds:  s.services?.length ? asIds(s.services) : (s.service_id  != null ? [Number(s.service_id)]  : []),
      locationIds: s.locations?.length ? asIds(s.locations): (s.location_id != null ? [Number(s.location_id)] : []),
      work, breaks
    })
  }
  const out = Array.from(groups.values())
  out.forEach(c => c.items = sortCombosByFirstStart(c.items))
  out.sort((a,b)=> (a.dateStart||'').localeCompare(b.dateStart||''))
  out.forEach((c,i)=> (c.id = i+1))
  return out
}

function buildCardsFromFlatSpecialDays(rows: any[]): SDSpecialDayCard[] {
  // Gruppiert flache Einträge nach Datum & Service/Location-Set
  const byRange = new Map<string, SDSpecialDayCard>()
  for (const r of (rows || [])) {
    const ds = String(r.start_date || '')
    const de = String(r.end_date || ds)
    const key = `${ds}__${de}`
    let card = byRange.get(key)
    if (!card) {
      card = { id: 0, dateStart: ds || null, dateEnd: de || ds || null, items: [] }
      byRange.set(key, card)
    }
    const services = asIds(r.services)
    const locations = asIds(r.locations)
    const st = normTime(r.start_time)
    const en = normTime(r.end_time)
    if (!st || !en || st >= en) continue

    let combo = card.items.find(c =>
      JSON.stringify(c.serviceIds)===JSON.stringify(services) &&
      JSON.stringify(c.locationIds)===JSON.stringify(locations)
    )
    if (!combo) {
      combo = { serviceIds: services, locationIds: locations, work: [], breaks: [] }
      card.items.push(combo)
    }
    combo.work.push({ start: st, end: en })
  }
  const out = Array.from(byRange.values())
  out.forEach(c => {
    c.items.forEach(it => it.work.sort((a,b)=> a.start.localeCompare(b.start)))
    c.items = sortCombosByFirstStart(c.items)
  })
  out.sort((a,b)=> (a.dateStart||'').localeCompare(b.dateStart||''))
  out.forEach((c,i)=> (c.id = i+1))
  return out
}

function normalizeAssignedServices(input: any): { groups?: ServiceGroup[]; flat: Array<{id:number; name:string}> } {
  const normalizeItem = (raw: any): ServiceItem => ({
    id: Number(raw.id ?? raw.service_id ?? 0),
    name: String(raw.name ?? raw.service_name ?? '').trim(),
    enabled: !!Number(raw.enabled ?? raw.active ?? 1),
    minCapacity: Number(raw.min_capacity ?? raw.minCapacity ?? 1) || 1,
    maxCapacity: Number(raw.max_capacity ?? raw.maxCapacity ?? 1) || 1,
    price: String(raw.price ?? raw.price_text ?? '').trim(),
  })
  const groupsOut: ServiceGroup[] = []
  const flat: Array<{id:number; name:string}> = []

  if (Array.isArray(input)) {
    const looksGrouped = input.some((g:any)=> Array.isArray(g?.services))
    if (looksGrouped) {
      for (const g of input) {
        const items = (Array.isArray(g.services)? g.services : []).map(normalizeItem)
        groupsOut.push({
          id: Number(g.id ?? 0),
          name: String(g.name ?? ''),
          enabled: !!Number(g.enabled ?? 1),
          services: items
        })
        flat.push(...items.map(i => ({ id:i.id, name:i.name })))
      }
    } else {
      // flache Liste → gruppieren nach group_id/name
      type FlatRow = { group_id?: number|string; group_name?: string }
      const groups = new Map<string, { id: number; name: string; enabled: boolean; services: any[] }>()
      for (const r of input as FlatRow[]) {
        const key = String((r as any).group_id ?? (r as any).group_name ?? '0')
        const entry = groups.get(key) ?? {
          id: Number((r as any).group_id ?? 0),
          name: String((r as any).group_name ?? ((r as any).group_id ? `Gruppe ${(r as any).group_id}` : 'Allgemein')),
          enabled: !!Number((r as any).group_enabled ?? (r as any).enabled ?? 1),
          services: []
        }
        entry.services.push(r)
        groups.set(key, entry)
      }
      for (const g of groups.values()) {
        const items = g.services.map(normalizeItem)
        groupsOut.push({ id:g.id, name:g.name, enabled:g.enabled, services: items })
        flat.push(...items.map(i => ({ id:i.id, name:i.name })))
      }
    }
  } else if (input && typeof input === 'object') {
    for (const g of Object.values<any>(input)) {
      const items = (Array.isArray(g.services)? g.services : []).map(normalizeItem)
      groupsOut.push({
        id: Number(g.id ?? 0),
        name: String(g.name ?? ''),
        enabled: !!Number(g.enabled ?? 1),
        services: items
      })
      flat.push(...items.map(i => ({ id:i.id, name:i.name })))
    }
  }

  // Ordnung
  groupsOut.sort((a,b)=> a.name.localeCompare(b.name, 'de'))
  for (const g of groupsOut) g.services.sort((a,b)=> a.name.localeCompare(b.name, 'de'))

  // Dedupe flat
  const seen = new Set<number>()
  const flatUnique = flat.filter(s => (seen.has(s.id) ? false : (seen.add(s.id), true)))
  return { groups: groupsOut.length ? groupsOut : undefined, flat: flatUnique }
}

/* =============================================================================
 *  UI Helper Functions
 * ============================================================================= */

export function initials(item: any): string {
  return ((item?.first_name?.[0] || '') + (item?.last_name?.[0] || '')).toUpperCase()
}

export function statusClass(val?: string): string {
  return val === 'active' ? 'active' : val === 'blocked' ? 'blocked' : val === 'deleted' ? 'deleted' : 'inactive'
}

export function normalizePhone(phone: string | number): string {
  return String(phone ?? '').replace(/\s+/g, '')
}

export function formatWorkLocations(locs: any): string {
  if (!locs) return '–'
  if (Array.isArray(locs)) {
    return locs.map((l: any) => (typeof l === 'object' ? l.name || l.label || String(l.id || '') : String(l))).filter(Boolean).join(', ') || '–'
  }
  if (typeof locs === 'string') return locs
  if (typeof locs === 'object' && locs.name) return locs.name
  return '–'
}
