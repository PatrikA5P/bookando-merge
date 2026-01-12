// src/Core/Util/formatters.ts

import dayjs from 'dayjs'
import localizedFormat from 'dayjs/plugin/localizedFormat'
import relativeTime from 'dayjs/plugin/relativeTime'
import weekOfYear from 'dayjs/plugin/weekOfYear'
import isoWeek from 'dayjs/plugin/isoWeek'
import quarterOfYear from 'dayjs/plugin/quarterOfYear'
import updateLocale from 'dayjs/plugin/updateLocale'
import duration from 'dayjs/plugin/duration'

import { getCountries } from '@core/Design/data/countries'
import { getGenders }  from '@core/Design/data/genders'
import { getLanguages } from '@core/Design/data/languages'
import { asString } from '@core/Util/sanitize'
import { getLang, getDateFmt, getTimeFmt, getDateTimeFmt } from '@core/Locale'
import { messages } from '@core/Design/i18n'

import 'dayjs/locale/de'
import 'dayjs/locale/en'

import 'dayjs/locale/fr'
import 'dayjs/locale/it'
import 'dayjs/locale/es'

dayjs.extend(localizedFormat)
dayjs.extend(relativeTime)
dayjs.extend(weekOfYear)
dayjs.extend(isoWeek)
dayjs.extend(quarterOfYear)
dayjs.extend(updateLocale)
dayjs.extend(duration)

/** dayjs möchte i.d.R. die Basissprache (de, fr, it, en, es) */
const dayjsLocale = (loc: string) => (loc ? loc.split(/[-_]/)[0] : 'en')

// ---------- Defaults (live aus @core/Locale holen) ----------
const DEFAULT_RANGE_SEP = ' – '

/* ---------- Country ---------- */
export function countryFlag(code: string | undefined, locale: string = getLang()): string {
  const raw = asString((code as any)?.code ?? code)
  if (!raw) return ''
  const upper = raw.toUpperCase()
  const entry = getCountries(locale).find((c: any) => c.code === upper)
  return entry?.flag || (upper.length === 2
    ? String.fromCodePoint(...upper.split('').map(c => 127397 + c.charCodeAt(0)))
    : '')
}

export function countryLabel(code: string, locale: string = getLang()): string {
  const raw = asString((code as any)?.code ?? code)
  if (!raw) return '–'
  const upper = raw.toUpperCase()
  const entry = getCountries(locale).find((c: any) => c.code === upper)
  return entry?.label || entry?.name || upper
}

//Robust: akzeptiert "CH" oder { code:"CH" } und gibt ISO-2 oder null
export function normalizeCountryCode(
  input?: string | { code?: string; value?: string; iso2?: string } | null
): string | null {
  // akzeptiere string oder Objekt mit code/value/iso2
  const rawCandidate =
    input && typeof input === 'object'
      ? (input as any).code ?? (input as any).value ?? (input as any).iso2
      : input

  const raw = asString(rawCandidate)
  if (!raw) return null
  const upper = raw.trim().toUpperCase()
  return /^[A-Z]{2}$/.test(upper) ? upper : null
}

/* ---------- Gender ---------- */
function normalizeGenderCode(input?: string): string {
  const k = String(input ?? '').trim().toLowerCase()
  const map: Record<string,string> = {
    m:'male', male:'male', man:'male',
    f:'female', female:'female', woman:'female', w:'female',
    d:'other', x:'other', diverse:'other', divers:'other', other:'other', o:'other',
    n:'none', none:'none', unknown:'none', u:'none', '':'none'
  }
  return map[k] ?? k
}

export function genderLabel(code?: string, locale: string = getLang()): string {
  const normalized = normalizeGenderCode(code)
  const genders = getGenders(locale)
  return genders.find((g: any) => g.value === normalized)?.label || code || '–'
}


/* ---------- Language ---------- */
export function languageLabel(code: string, locale: string = getLang()): string {
  const langs = getLanguages([code], locale)
  return langs[0]?.label || code || '–'
}
export function languageFlag(code: string, locale: string = getLang()): string {
  const langs = getLanguages([code], locale)
  return langs[0]?.flag || ''
}

/* ---------- Status (kleine Map als Beispiel) ---------- */
export function statusLabel(val: string, locale: string = getLang()): string {
  const m: any = (messages as any)[locale] || (messages as any).de || (messages as any).en || {}
  const dict = m.core?.status || m.status || {}
  const fb   = ((messages as any).en || {}).core?.status || ((messages as any).en || {}).status || {}
  return dict[val] || fb[val] || val || '–'
}

/* ---------- Date / Time / Datetime ---------- */
export function formatDate(
  val: string | Date | undefined,
  locale: string = getLang(),
  displayFormat?: string
): string {
  if (!val) return '–'
  const d = typeof val === 'string' ? new Date(val) : val
  if (isNaN(d.getTime())) return '–'
  const fmt = displayFormat ?? getDateFmt()
  try { return dayjs(d).locale(dayjsLocale(locale)).format(fmt) }
  catch { return d.toLocaleDateString(locale) }
}

export function formatTime(
  val: string | Date | undefined,
  locale: string = getLang(),
  displayFormat?: string
): string {
  if (!val) return '–'
  const d = typeof val === 'string' ? new Date(val) : val
  if (isNaN(d.getTime())) return '–'
  const fmt = displayFormat ?? getTimeFmt()
  try { return dayjs(d).locale(dayjsLocale(locale)).format(fmt) }
  catch { return d.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' }) }
}

export function formatDatetime(
  val: string | Date | undefined,
  locale: string = getLang(),
  displayFormat?: string
): string {
  if (!val) return '–'
  const d = typeof val === 'string' ? new Date(val) : val
  if (isNaN(d.getTime())) return '–'
  const fmt = displayFormat ?? getDateTimeFmt()
  try { return dayjs(d).locale(dayjsLocale(locale)).format(fmt) }
  catch { return d.toLocaleString(locale) }
}

/* ---------- Ranges ---------- */
export function formatDateRange(start?: string|Date, end?: string|Date, locale: string = getLang(), displayFormat?: string, sep = DEFAULT_RANGE_SEP): string {
  if (!start && !end) return '–'
  const fmt = displayFormat ?? getDateFmt()
  const s = start ? formatDate(start, locale, fmt) : '…'
  const e = end ? formatDate(end, locale, fmt) : '…'
  return `${s}${sep}${e}`
}

export function formatTimeRange(start?: string|Date, end?: string|Date, locale: string = getLang(), displayFormat?: string, sep = DEFAULT_RANGE_SEP): string {
  if (!start && !end) return '–'
  const fmt = displayFormat ?? getTimeFmt()
  const s = start ? formatTime(start, locale, fmt) : '…'
  const e = end ? formatTime(end, locale, fmt) : '…'
  return `${s}${sep}${e}`
}

export function formatDatetimeRange(start?: string|Date, end?: string|Date, locale: string = getLang(), displayFormat?: string, sep = DEFAULT_RANGE_SEP): string {
  if (!start && !end) return '–'
  const fmt = displayFormat ?? getDateTimeFmt()
  const s = start ? formatDatetime(start, locale, fmt) : '…'
  const e = end ? formatDatetime(end, locale, fmt) : '…'
  return `${s}${sep}${e}`
}

/* ---------- Kalenderwoche, Quartal, Relativzeit ---------- */
export function formatWeek(date: string | Date | undefined, locale: string = getLang()): string {
  if (!date) return '–'
  try { const d = dayjs(date).locale(dayjsLocale(locale)); return `KW ${d.isoWeek()} / ${d.year()}` }
  catch { return '–' }
}
export function formatQuarter(date: string | Date | undefined, locale: string = getLang()): string {
  if (!date) return '–'
  try { const d = dayjs(date).locale(dayjsLocale(locale)); return `Q${d.quarter()} / ${d.year()}` }
  catch { return '–' }
}
export function formatDuration(
  start: string | Date | undefined,
  end: string | Date | undefined,
  opts?: { asWords?: boolean; locale?: string }
): string {
  if (!start || !end) return '–'
  const lang = opts?.locale ?? getLang()
  const s = dayjs(start); const e = dayjs(end); const ms = Math.abs(e.diff(s))
  if (opts?.asWords) return dayjs.duration(ms).locale(dayjsLocale(lang)).humanize()
  const h = Math.floor(ms / (1000 * 60 * 60)); const m = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60))
  return `${h > 0 ? h + ':' : ''}${m.toString().padStart(2, '0')}`
}
export function formatRelative(
  date: string | Date | undefined,
  base: string | Date = new Date(),
  locale: string = getLang()
): string {
  if (!date) return '–'
  try { return dayjs(date).locale(dayjsLocale(locale)).from(dayjs(base)) }
  catch { return '–' }
}

/* ---------- Phone ---------- */
export function formatPhone(phone: string | undefined): string {
  if (!phone) return '–'
  return phone.replace(/[^\d+]/g, '').replace(/(\+\d{2})(\d{2,3})(\d{3})(\d{2})(\d{2})$/, '$1 $2 $3 $4 $5')
}

/* ---------- Listen ---------- */
export function formatDateList(
  dates: Array<string | Date>,
  locale: string = getLang(),
  displayFormat?: string,
  sep = ', '
): string {
  const fmt = displayFormat ?? getDateFmt()
  return dates && dates.length ? dates.map((d) => formatDate(d, locale, fmt)).join(sep) : '–'
}

export function formatTimeList(
  times: Array<string | Date>,
  locale: string = getLang(),
  displayFormat?: string,
  sep = ', '
): string {
  const fmt = displayFormat ?? getTimeFmt()
  return times && times.length ? times.map((t) => formatTime(t, locale, fmt)).join(sep) : '–'
}

export function phoneFlag(phone: string | undefined, locale: string = getLang()): string {
  const p = asString(phone)
  if (!p) return ''
  const countries = getCountries(locale)
  const match = countries
    .slice()
    .sort((a, b) => (b.dial_code.length - a.dial_code.length))
    .find(c => p.startsWith(asString(c.dial_code)))
  return match?.flag || ''
}
