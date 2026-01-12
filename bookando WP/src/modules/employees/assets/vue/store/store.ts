// src/modules/employees/assets/vue/store/store.ts
import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Employee } from '../api/EmployeesApi'
import {
  getEmployees,
  getEmployee,
  createEmployee,
  updateEmployee,
  deleteEmployee as apiDeleteEmployee,
  replaceCalendarsStandalone,
  fetchWorkdaySetsStandalone,
  saveWorkdaySetsStandalone,
  fetchSpecialDaySetsStandalone,
  saveDaysOffMerge,
  saveSpecialDaySetsMerge,
  invalidateSetsCache,
} from '../api/EmployeesApi'

/* =============================================================================
   View-Model Typen (UI ⇄ Store)
   ============================================================================= */
export type TimeRangeVM  = { id?: number; start: string; end: string }
export type DayComboVM   = { id: number; serviceIds: number[]; locationIds: number[]; work: TimeRangeVM[]; breaks: TimeRangeVM[] }
export type WorkDayVM    = { key: string; label: string; combos: DayComboVM[] }

export type DayOffVM     = { id: number; title: string; note?: string; start: string; end: string; repeatYearly?: boolean }
export type SpecialDayVM = {
  id: number
  start: string
  end: string
  serviceIds: number[]
  locationIds: number[]
  work: TimeRangeVM[]
  breaks: TimeRangeVM[]
}

export interface EmployeeFormVM {
  form: Partial<Employee>
  workingDays: WorkDayVM[]
  workingDaysDirty?: boolean
  daysOff: DayOffVM[]
  daysOffDirty: boolean
  specialDays: SpecialDayVM[]
  specialDaysDirty?: boolean
  calendars?: any[]
  formDirty?: boolean
}

/* =============================================================================
   Konstanten / Mappings
   ============================================================================= */
const DAY_KEY_TO_ID: Record<string, number> = { mon:1, tue:2, wed:3, thu:4, fri:5, sat:6, sun:7 }
const DAY_ID_TO_KEY: Record<number, string> = { 1:'mon', 2:'tue', 3:'wed', 4:'thu', 5:'fri', 6:'sat', 7:'sun' }
const DAY_LABELS = ['Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag']

/* =============================================================================
   Utils
   ============================================================================= */
function normalizeTimeHHmm(_v: string): string {
  const s = (_v || '').trim()
  if (!s) return ''
  if (/^\d{2}:\d{2}:\d{2}$/.test(s)) return s.slice(0, 5)
  if (/^\d{2}:\d{2}$/.test(s)) return s
  return s
}

/* =============================================================================
   Mapper: API → VM
   ============================================================================= */

function buildCalendarsPayload(list: any[] | undefined) {
  const arr = Array.isArray(list) ? list : []
  return arr.map((c: any) => {
    const provider = String(c.provider ?? c.calendar ?? '').toLowerCase().trim()
    const base = {
      provider,
      name: (c.name ?? null) as string | null,
      access: (c.access === 'rw' ? 'rw' : 'ro') as 'ro'|'rw',
      is_busy_source: (c.is_busy_source ? 1 : 0) as 0|1,
      is_default_write: (c.is_default_write ? 1 : 0) as 0|1,
    }

    if (provider === 'ics' || provider === 'apple' || provider === 'icloud') {
      // ICS erwartet URL im Feld "url" (webcal -> https wird serverseitig gefixt)
      const url = String(c.url ?? c.calendar_id ?? '').trim()
      return { ...base, provider: 'ics', url }
    }

    // OAuth-Provider: calendar_id muss gesetzt sein
    return {
      ...base,
      provider: (provider === 'outlook' ? 'microsoft' : provider),
      calendar_id: String(c.calendar_id ?? '').trim(),
    }
  }).filter(it => it.provider && (it.provider === 'ics' ? it.url : it.calendar_id))
}

function buildVMFromWorkdaySets(sets: any[]): WorkDayVM[] {
  const byDay: Record<number, DayComboVM[]> = { 1:[],2:[],3:[],4:[],5:[],6:[],7:[] }
  const sortMap = new Map<number, number>()
  for (const s of (sets || [])) {
    if (s?.id != null) sortMap.set(Number(s.id), Number(s.sort ?? Number.MAX_SAFE_INTEGER))
  }
  (sets || []).forEach((s: any) => {
    const d = Number(s.week_day_id)
    if (d < 1 || d > 7) return
    const work: TimeRangeVM[] = []
    const breaks: TimeRangeVM[] = []
    for (const it of (s.intervals || [])) {
      const st = normalizeTimeHHmm(it.start_time)
      const en = normalizeTimeHHmm(it.end_time)
      if (!st || !en || st >= en) continue
      ;(Number(it.is_break) === 1 ? breaks : work).push({ start: st, end: en })
    }
    byDay[d].push({
      id: Number(s.id ?? 0),
      serviceIds: Array.isArray(s.services)  ? s.services.map(Number)  : [],
      locationIds: Array.isArray(s.locations)? s.locations.map(Number) : [],
      work, breaks,
    })
  })

  return [1,2,3,4,5,6,7].map((dayId, idx) => ({
    key: DAY_ID_TO_KEY[dayId],
    label: DAY_LABELS[idx],
    combos: byDay[dayId].sort((a, b) => {
      const sa = sortMap.get(a.id) ?? Number.MAX_SAFE_INTEGER
      const sb = sortMap.get(b.id) ?? Number.MAX_SAFE_INTEGER
      return sa !== sb ? sa - sb : (a.id || 0) - (b.id || 0)
    })
  }))
}

function buildDaysOffVMFromApi(list: any[]): DayOffVM[] {
  return (list || []).map((d: any) => ({
    id: Number(d.id ?? 0),
    title: String(d.name || ''),             // ← title aus Name
    note:  String(d.note || ''),             // (Backend muss note liefern)
    start: String(d.start_date || ''),
    end:   String(d.end_date   || ''),
    repeatYearly: !!Number(d.repeat_yearly ?? 0), // (Backend muss repeat_yearly liefern)
  }))
}

function buildSpecialDaysVMFromApi(list: any[]): SpecialDayVM[] {
  type Row = {
    id?: number; start_date?: string; end_date?: string
    start_time?: string; end_time?: string
    locations?: number[]; services?: number[]
    repeat?: string
  }

  const keyOf = (r: Row) => {
    const sLoc = JSON.stringify([...(r.locations||[])].map(Number).sort((a,b)=>a-b))
    const sSvc = JSON.stringify([...(r.services ||[])].map(Number).sort((a,b)=>a-b))
    const sd   = String(r.start_date || '')
    const ed   = String(r.end_date   || r.start_date || '')
    const rep  = String(r.repeat || 'none')
    return `${sd}|${ed}|${sLoc}|${sSvc}|${rep}`
  }

  const groups = new Map<string, Row[]>()
  for (const raw of (list || []) as Row[]) {
    const k = keyOf(raw)
    if (!groups.has(k)) groups.set(k, [])
    groups.get(k)!.push(raw)
  }

  const out: SpecialDayVM[] = []
  for (const [k, rows] of groups) {
    if (!rows.length) continue
    const first = rows[0]
    const start = String(first.start_date || '')
    const end   = String(first.end_date   || first.start_date || '')
    const locationIds = [...(first.locations || [])].map(Number).sort((a,b)=>a-b)
    const serviceIds  = [...(first.services  || [])].map(Number).sort((a,b)=>a-b)

    // Alle work-Intervalle sammeln & sortieren
    const work = rows
      .map(r => ({ start: normHHmm(r.start_time), end: normHHmm(r.end_time) }))
      .filter(r => r.start && r.end && r.start < r.end)
      .sort((a, b) => a.start.localeCompare(b.start))

    // Breaks als Lücken zwischen aufeinander folgenden work-Blöcken ableiten
    const breaks: TimeRangeVM[] = []
    for (let i = 0; i < work.length - 1; i++) {
      const gapStart = work[i].end
      const gapEnd   = work[i+1].start
      if (gapStart < gapEnd) breaks.push({ start: gapStart, end: gapEnd })
    }

    out.push({
      id: Number(first.id ?? 0),
      start, end,
      locationIds, serviceIds,
      work, breaks
    })
  }

  return out

  function normHHmm(v?: string): string {
    const s = String(v || '').trim()
    if (!s) return ''
    if (/^\d{2}:\d{2}:\d{2}$/.test(s)) return s.slice(0, 5)
    if (/^\d{2}:\d{2}$/.test(s))       return s
    return ''
  }
}

/* =============================================================================
   Mapper: VM → API
   ============================================================================= */
function buildWorkdaySetsFromVM(workingDays: WorkDayVM[]) {
  const sets: any[] = []
  for (const day of workingDays) {
    const dayId = DAY_KEY_TO_ID[day.key] ?? 0
    if (dayId < 1 || dayId > 7) continue
    day.combos.forEach((c, idx) => {
      const intervals: any[] = []
      c.work.forEach(w => {
        const st = normalizeTimeHHmm(w.start); const en = normalizeTimeHHmm(w.end)
        if (st && en && st < en) intervals.push({
          id: (w as any).id ? Number((w as any).id) : undefined,
          start_time: st, end_time: en, is_break: 0
        })
      })
      c.breaks.forEach(b => {
        const st = normalizeTimeHHmm(b.start); const en = normalizeTimeHHmm(b.end)
        if (st && en && st < en) intervals.push({
          id: (b as any).id ? Number((b as any).id) : undefined,
          start_time: st, end_time: en, is_break: 1
        })
      })
      if (intervals.length) {
        sets.push({
          id: c.id || undefined,
          week_day_id: dayId,
          services:  c.serviceIds || [],
          locations: c.locationIds || [],
          label: null,
          sort: idx,
          intervals,
        })
      }
    })
  }
  return sets
}

function serializeDaysOffFromVM(list: DayOffVM[]) {
  return (list || [])
    .filter(d => !!d.start)
    .map(d => ({
      id: d.id || undefined,
      name: d.title ?? null,
      note: d.note ?? null,
      start_date: d.start,
      end_date:   d.end || d.start,
      repeat_yearly: d.repeatYearly ? 1 : 0,
    }))
}

function buildSpecialDaysFromVM(list: SpecialDayVM[]) {
  const out: any[] = []
  for (const sd of (list || [])) {
    const base = {
      start_date: sd.start,
      end_date:   sd.end || sd.start,
      locations:  sd.locationIds || [],
      services:   sd.serviceIds  || [],
      repeat:     'none', // falls du künftig Wiederholungen brauchst
    }
    const blocks = (sd.work || [])
      .map(w => ({ start_time: norm(w.start), end_time: norm(w.end) }))
      .filter(w => w.start_time && w.end_time && w.start_time < w.end_time)

    if (!blocks.length) {
      // Optional: ganz-tägig ohne Zeiten speichern → einfach ohne start_time/end_time schicken
      out.push(base)
    } else {
      for (const b of blocks) {
        out.push({ ...base, ...b })
      }
    }
  }
  return out

  function norm(v?: string): string {
    const s = String(v || '').trim()
    if (!s) return ''
    if (/^\d{2}:\d{2}$/.test(s))       return s
    if (/^\d{2}:\d{2}:\d{2}$/.test(s)) return s.slice(0, 5)
    return ''
  }
}

function buildSpecialDaySetsFromVM(list: SpecialDayVM[]) {
  type Key = string
  const byRange = new Map<Key, SpecialDayVM[]>()

  for (const row of (list || [])) {
    const start = (row.start || '').trim()
    const end   = (row.end   || row.start || '').trim()
    if (!start) continue
    const k: Key = `${start}|${end}`
    ;(byRange.get(k) || byRange.set(k, []).get(k)!).push(row)
  }

  const toMin = (s:string) => {
    const [h,m] = (s||'').split(':').map(Number)
    return (isFinite(h)&&isFinite(m)) ? h*60+m : Infinity
  }

  const out: any[] = []
  for (const [key, rows] of byRange) {
    rows.sort((a,b) => {
      const aw = Math.min(...(a.work||[]).map(w=>toMin(w.start)))
      const bw = Math.min(...(b.work||[]).map(w=>toMin(w.start)))
      return aw - bw
    })

    rows.forEach((r, idx) => {
      const ids = (a?: any[]) =>
        Array.from(new Set((a || []).map((n:any) => Number(n)).filter(Number.isFinite))).sort((a,b)=>a-b)

      const intervals = [
        ...(r.work   || []).map(w => ({
          id: (w as any).id ? Number((w as any).id) : undefined,
          start_time: normalizeTimeHHmm(w.start),
          end_time:   normalizeTimeHHmm(w.end),
          is_break: 0
        })),
        ...(r.breaks || []).map(b => ({
          id: (b as any).id ? Number((b as any).id) : undefined,
          start_time: normalizeTimeHHmm(b.start),
          end_time:   normalizeTimeHHmm(b.end),
          is_break: 1
        })),
      ].filter(it => it.start_time && it.end_time && it.start_time < it.end_time)

      out.push({
        id: (r.id ? Number(r.id) : undefined),     // ← Set-ID erhalten
        start_date: key.split('|')[0],
        end_date:   key.split('|')[1],
        services:   ids(r.serviceIds),
        locations:  ids(r.locationIds),
        sort: idx,
        label: null,
        intervals
      })
    })
  }
  return out
}

function canonSet(s: any) {
  // Kanonische Form für Vergleiche (Zeiten → HH:mm, Arrays sortiert)
  const norm = (t: string) => /^\d{2}:\d{2}/.test(t) ? t.slice(0, 5) : ''

  const ints = (s.intervals || [])
    .map((it: any) => ({
      id: it.id ? Number(it.id) : undefined,
      start_time: norm(String(it.start_time || '')),
      end_time:   norm(String(it.end_time   || '')),
      is_break:   Number(it.is_break) ? 1 : 0,
    }))
    .filter((it: any) => it.start_time && it.end_time && it.start_time < it.end_time)
    .sort((a: any, b: any) =>
      (a.is_break - b.is_break) ||
      a.start_time.localeCompare(b.start_time) ||
      a.end_time.localeCompare(b.end_time)
    )

  const arr = (a?: any[]) =>
    Array.from(new Set((a || []).map(Number).filter(Boolean))).sort((x, y) => x - y)

  return {
    id: s.id ? Number(s.id) : undefined,
    week_day_id: Number(s.week_day_id),
    label: s.label ?? null,
    sort: s.sort != null ? Number(s.sort) : undefined,
    services:  arr(s.services),
    locations: arr(s.locations),
    intervals: ints,
  }
}

function diffWorkdaySets(prevRaw: any[], nextRaw: any[]) {
  const prev = (prevRaw||[]).map(canonSet)
  const next = (nextRaw||[]).map(canonSet)

  const byIdPrev = new Map<number, any>()
  prev.forEach(s => { if (s.id) byIdPrev.set(s.id, s) })

  const upsert: any[] = []
  const nextIds = new Set<number>()
  for (const s of next) {
    if (!s.id) { upsert.push(s); continue }
    nextIds.add(s.id)
    const old = byIdPrev.get(s.id)
    if (!old) { upsert.push(s); continue }

    const changed =
      s.week_day_id !== old.week_day_id ||
      (s.label ?? null) !== (old.label ?? null) ||
      (s.sort ?? null)  !== (old.sort ?? null)  ||
      JSON.stringify(s.services)  !== JSON.stringify(old.services) ||
      JSON.stringify(s.locations) !== JSON.stringify(old.locations) ||
      JSON.stringify(s.intervals) !== JSON.stringify(old.intervals)

    if (changed) upsert.push(s)
  }

  const delete_ids: number[] = []
  for (const o of prev) {
    if (o.id && !nextIds.has(o.id)) delete_ids.push(o.id)
  }
  return { upsert, delete_ids }
}


/* =============================================================================
   Store
   ============================================================================= */
export const useEmployeesStore = defineStore('employees', () => {
  const items   = ref<Employee[]>([])
  const loading = ref(false)
  const error   = ref<string | null>(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      const response = await getEmployees()
      items.value = Array.isArray(response) ? response : (response as any).data || []
    } catch (err: any) {
      error.value = err?.message || 'Fehler beim Laden der Mitarbeitenden.'
      items.value = []
    } finally {
      loading.value = false
    }
  }

  // Detail **immer** mit Relationen laden – und klar loggen, was ankommt
  async function fetchById(id: number): Promise<Employee | null> {
    try {
      const emp = await getEmployee(id)
      const setCount = emp.workday_sets?.length ?? 0
      console.log('[employees] fetchById:', id, 'workday_sets:', setCount)
      if (!setCount) {
        console.warn('[employees] Achtung: workday_sets ist leer – Backend hat keine Relationen geliefert.')
      }
      return emp
    } catch (error) {
      console.error('[employees] fetchById error', error)
      return null
    }
  }

  async function save(employee: Employee | Partial<Employee>) {
    loading.value = true
    error.value = null
    try {
      if ((employee as Employee).id) {
        await updateEmployee(Number((employee as Employee).id), employee)
      } else {
        await createEmployee(employee)
      }
      await load()
      return true
    } catch (err: any) {
      error.value = err?.message || 'Fehler beim Speichern.'
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Speichern aus Formular-VM.
   * - CREATE: sendet Collections, wenn vorhanden
   * - UPDATE: sendet Collections nur bei Dirty (falls Flag fehlt → sicherer Fallback = true)
   */
  async function saveFromForm(vm: EmployeeFormVM) {
    loading.value = true
    error.value = null
    try {
      // Datum normalisieren (dd.MM.yyyy -> yyyy-MM-dd)
      if (vm.form.birthdate && /^\d{2}\.\d{2}\.\d{4}$/.test(String(vm.form.birthdate))) {
        const [d, m, y] = String(vm.form.birthdate).split('.')
        vm.form.birthdate = `${y}-${m}-${d}`
      }

      // Basis-Payload (nur Core-Felder; Collections extra behandeln)
      const payload: Partial<Employee> = { ...vm.form }

      // ViewModels -> API-Form
      const sets           = buildWorkdaySetsFromVM(vm.workingDays || [])
      const daysOff = (vm as any).daysOffApi ?? serializeDaysOffFromVM(vm.daysOff || [])
      const specialDaySets = buildSpecialDaySetsFromVM(vm.specialDays || [])
      const calendars      = Array.isArray(vm.calendars) ? vm.calendars : undefined

      const isCreate = !vm.form.id
      const asBool = (value: any) =>
        typeof value === 'boolean'
          ? value
          : !!(value && typeof value === 'object' && 'value' in value && value.value === true)

      const wdDirty    = asBool(vm.workingDaysDirty)
      const sdDirty    = asBool(vm.specialDaysDirty)
      const doDaysOff  = !!vm.daysOffDirty         // bewusst eigenes Flag
      const doCalendars = calendars !== undefined  // auch leere Arrays zulassen (explizit löschen)
      const formDirty  = asBool(vm.formDirty)

      if (isCreate) {
        // CREATE darf Collections direkt mitsenden (Server legt IDs an)
        if (sets.length)           (payload as any).workday_sets      = sets
        if (daysOff.length)        (payload as any).days_off          = daysOff
        if (specialDaySets.length) (payload as any).special_day_sets  = specialDaySets
        if (doCalendars)           (payload as any).calendars         = calendars

        await createEmployee(payload)
      } else {
        const idNum = Number(vm.form.id)

        // 1) Core-Felder (ohne Collections) aktualisieren – nur wenn nötig
        if (formDirty) {
          const core: Partial<Employee> = { ...payload }
          delete (core as any).workday_sets
          delete (core as any).special_day_sets
          delete (core as any).days_off
          delete (core as any).calendars
          await updateEmployee(idNum, core)
        }

        // 2) Workday-Sets: diff & MERGE
        if (wdDirty) {
          const prev = await fetchWorkdaySetsStandalone(idNum)
          const { upsert, delete_ids } = diffWorkdaySets(prev, sets)
          if (upsert.length || (delete_ids && delete_ids.length)) {
            await saveWorkdaySetsStandalone(idNum, { upsert, delete_ids })
          }
        }

        // 3) Special-Day-Sets: MERGE gegen vorherigen Stand
        if (sdDirty) {
          const prevSets = await fetchSpecialDaySetsStandalone(idNum)
          await saveSpecialDaySetsMerge(idNum, prevSets as any, specialDaySets as any)
        }

        // 4) Days Off – MERGE (Upsert + gezielte Löschungen)
        if (doDaysOff) {
          // 1) Vorherigen Stand holen (IDs für Diff)
          const prevEmp = await getEmployee(idNum, { with: ['days_off'] })
          const prev = Array.isArray(prevEmp.days_off) ? prevEmp.days_off : []

          // 2) Nächsten Stand (aus VM) mit IDs serialisieren
          const next = serializeDaysOffFromVM(vm.daysOff || []) as any[]

          // 3) delete_ids = alle alten IDs, die im next nicht mehr vorkommen
          const prevIds = new Set(prev.map(d => Number(d.id)).filter(Boolean))
          const nextIds = new Set(next.map(d => Number(d.id)).filter(Boolean))
          const delete_ids = [...prevIds].filter(id => !nextIds.has(id)) as number[]

          // 4) MERGE callen (neue ohne id → INSERT, mit id → UPDATE, delete_ids → DELETE)
          await saveDaysOffMerge(idNum, next as any, delete_ids)
        }

        // 5) Calendars – explizit ersetzen, wenn gesetzt (auch leeres Array = löschen)
        if (doCalendars) {
          const calPayload = buildCalendarsPayload(calendars as any[])
          await replaceCalendarsStandalone(idNum, calPayload)
        }

        // 6) Cache invalidieren (Sets/Special)
        invalidateSetsCache(idNum)
      }

      await load()
      return true
    } catch (err: any) {
      error.value = err?.message || 'Fehler beim Speichern.'
      return false
    } finally {
      loading.value = false
    }
  }


  async function remove(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiDeleteEmployee(id)
      await load()
      return true
    } catch (err: any) {
      error.value = err?.message || 'Fehler beim Loeschen.'
      return false
    } finally {
      loading.value = false
    }
  }

  /* =============================================================================
     Persistenz: Filter / Tabellen-Settings
     ============================================================================= */
  const ACTIVE_FILTER_FIELDS_KEY = 'bookando_employees_active_filter_fields'
  const ACTIVE_FILTERS_KEY       = 'bookando_employees_active_filters'
  const activeFilterFields = ref<string[]>(loadActiveFilterFields())
  const activeFilters      = ref<any>(loadActiveFilters())

  function loadActiveFilterFields(): string[] {
    try {
      const raw = localStorage.getItem(ACTIVE_FILTER_FIELDS_KEY)
      const parsed = JSON.parse(raw || '[]')
      return Array.isArray(parsed) ? parsed : []
    } catch { return [] }
  }
  function setActiveFilterFields(fields: string[]) {
    activeFilterFields.value = [...fields]
    try { localStorage.setItem(ACTIVE_FILTER_FIELDS_KEY, JSON.stringify(activeFilterFields.value)) } catch {}
  }

  function loadActiveFilters(): any {
    try {
      const raw = localStorage.getItem(ACTIVE_FILTERS_KEY)
      const parsed = JSON.parse(raw || '{}')
      return typeof parsed === 'object' && parsed !== null ? parsed : {}
    } catch { return {} }
  }
  function setActiveFilters(val: any) {
    activeFilters.value = val
    try { localStorage.setItem(ACTIVE_FILTERS_KEY, JSON.stringify(val)) } catch {}
  }

  const VISIBLE_COLUMNS_KEY = 'bookando_employees_visible_columns'
  const visibleColumns = ref<string[]>(loadVisibleColumns())
  function loadVisibleColumns(): string[] {
    try {
      const raw = localStorage.getItem(VISIBLE_COLUMNS_KEY)
      const parsed = JSON.parse(raw || '[]')
      return Array.isArray(parsed) ? parsed.filter(k => typeof k === 'string') : []
    } catch { return [] }
  }
  function setVisibleColumns(cols: string[]) {
    visibleColumns.value = cols
    try { localStorage.setItem(VISIBLE_COLUMNS_KEY, JSON.stringify(cols)) }
    catch (error) { console.warn('Konnte sichtbare Spalten nicht speichern:', error) }
  }

  const COL_WIDTHS_KEY = 'bookando_employees_col_widths'
  const colWidths = ref<{ [key: string]: number }>(loadColWidths())
  function loadColWidths(): { [key: string]: number } {
    try {
      const raw = localStorage.getItem(COL_WIDTHS_KEY)
      const parsed = JSON.parse(raw || '{}')
      return typeof parsed === 'object' && parsed !== null ? parsed : {}
    } catch { return {} }
  }
  function setColWidths(widths: { [key: string]: number }) {
    colWidths.value = { ...widths }
    try { localStorage.setItem(COL_WIDTHS_KEY, JSON.stringify(colWidths.value)) }
    catch (error) { console.warn('Konnte Spaltenbreiten nicht speichern:', error) }
  }

  function resetColumnSettings() {
    try {
      localStorage.removeItem(VISIBLE_COLUMNS_KEY)
      localStorage.removeItem(COL_WIDTHS_KEY)
      visibleColumns.value = []
      colWidths.value = {}
    } catch (error) {
      console.warn('Konnte Spalteneinstellungen nicht zurücksetzen:', error)
    }
  }

  // Sidebar-Breite speichern
  const SIDEBAR_WIDTH_KEY = 'bookando_employees_sidebar_width'
  const sidebarWidth = ref<number>(loadSidebarWidth())

  function loadSidebarWidth(): number {
    try {
      const raw = localStorage.getItem(SIDEBAR_WIDTH_KEY)
      const parsed = parseInt(raw || '360', 10)
      return parsed >= 280 && parsed <= 600 ? parsed : 360
    } catch {
      return 360
    }
  }

  function setSidebarWidth(width: number) {
    const clamped = Math.max(280, Math.min(600, width))
    sidebarWidth.value = clamped
    try {
      localStorage.setItem(SIDEBAR_WIDTH_KEY, String(clamped))
    } catch (error) {
      console.warn('Konnte Sidebar-Breite nicht speichern:', error)
    }
  }

  return {
    items, loading, error,
    load, fetchById, save, saveFromForm, remove,
    activeFilterFields, setActiveFilterFields,
    activeFilters, setActiveFilters,
    visibleColumns, setVisibleColumns,
    colWidths, setColWidths,
    resetColumnSettings,
    sidebarWidth,
    setSidebarWidth
  }
})
