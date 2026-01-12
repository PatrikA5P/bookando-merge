// src/core/Composables/useDateRanges.ts
export type YMD = string // 'yyyy-MM-dd'

/** -------- Basics -------- */
const pad = (n: number) => String(n).padStart(2, '0')

export const isYMD = (s: unknown): s is YMD =>
  typeof s === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s)

/** Akzeptiert Date | 'yyyy-MM-dd' | 'dd.MM.yyyy'; liefert YMD oder null (bei unvollständig/ungültig) */
export function toYMDStrict(input: unknown): YMD | null {
  if (!input) return null
  if (input instanceof Date && !isNaN(input.getTime())) {
    return `${input.getFullYear()}-${pad(input.getMonth() + 1)}-${pad(input.getDate())}`
  }
  if (typeof input === 'string') {
    if (isYMD(input)) return input
    const m = input.match(/^(\d{2})\.(\d{2})\.(\d{4})$/)
    if (m) return `${m[3]}-${m[2]}-${m[1]}`
  }
  return null
}

export function parseYMD(s: YMD): Date {
  // lokale Mitternacht (wie in deinem Code bisher)
  const [y, m, d] = s.split('-').map(part => parseInt(part, 10))
  return new Date(y, m - 1, d)
}
export function ymd(d: Date): YMD {
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}
export function addDays(d: Date, n: number): Date {
  const x = new Date(d); x.setDate(x.getDate() + n); return x
}

/** -------- Ranges (YMD-Strings) -------- */
export function normalizeRangeYMD(a: unknown, b: unknown): [YMD, YMD] | null {
  const s = toYMDStrict(a)
  const e = toYMDStrict(b)
  if (!s || !e || s > e) return null
  return [s, e]
}

/** Date-basierter Overlap-Check */
export const dateRangesOverlap = (a1: Date, a2: Date, b1: Date, b2: Date) => a1 <= b2 && b1 <= a2

/** YMD-basierter Overlap-Check */
export function ymdRangesOverlap(as: YMD, ae: YMD, bs: YMD, be: YMD): boolean {
  return dateRangesOverlap(parseYMD(as), parseYMD(ae), parseYMD(bs), parseYMD(be))
}

/** Für Sortierungen (YMD) */
export const compareYMDAsc = (a: YMD, b: YMD) => (a < b ? -1 : a > b ? 1 : 0)
export const compareYMDAfter = (a: YMD, b: YMD) => -compareYMDAsc(a, b)

/** Materialisiert eine jährlich wiederholte Periode in ein Jahr (handhabt Jahreswechsel) */
export function materializeYearlyOccurrence(startYMD: YMD, endYMD: YMD, year: number): { start: Date; end: Date } {
  const [, sm, sd] = startYMD.split('-').map(Number)
  const [, em, ed] = endYMD.split('-').map(Number)
  const start = new Date(year, sm - 1, sd)
  let end = new Date(year, em - 1, ed)
  if (end.getTime() < start.getTime()) end = new Date(year + 1, em - 1, ed) // über Jahreswechsel
  return { start, end }
}

/** Nächste jährliche Instanz ab 'from' (inkl. heute) */
export function nextYearlyOccurrenceFrom(startYMD: YMD, endYMD: YMD, from: Date) {
  const y = from.getFullYear()
  const thisYear = materializeYearlyOccurrence(startYMD, endYMD, y)
  if (thisYear.end >= from) return { ...thisYear, year: y }
  const nextYear = materializeYearlyOccurrence(startYMD, endYMD, y + 1)
  return { ...nextYear, year: y + 1 }
}

/** Filter minimal so erweitern, dass der Wert hinein fällt */
export function extendFilterToFitRange(filterStart: YMD, filterEnd: YMD, valueStart: YMD, valueEnd: YMD) {
  return {
    start: valueStart < filterStart ? valueStart : filterStart,
    end:   valueEnd   > filterEnd   ? valueEnd   : filterEnd,
  }
}

/** Format "1. März 2025 – 31. März 2025" */
export function formatRangeLong(as: YMD, ae: YMD, locale = 'de-CH'): string {
  const fmt = new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'long', year: 'numeric' })
  return as === ae ? fmt.format(parseYMD(as)) : `${fmt.format(parseYMD(as))} – ${fmt.format(parseYMD(ae))}`
}

/** Nützlich für v-model: null statt ['',''] */
export function toRangeModel(a?: YMD, b?: YMD): [YMD | null, YMD | null] | null {
  const s = a || null, e = b || null
  return !s && !e ? null : [s, e]
}
