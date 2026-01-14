import { formatDate, formatTimeRange } from '@core/Util/formatters'

import type { AvailabilitySlot } from '../api/ResourcesApi'

export function formatAvailabilitySlot(slot: AvailabilitySlot, locale?: string | null): string {
  const lang = locale ?? undefined
  const dateLabel = formatDate(slot.date ?? undefined, lang)
  const startValue = combineDateAndTime(slot.date, slot.start)
  const endValue = combineDateAndTime(slot.date, slot.end)
  const timeLabel = formatTimeRange(startValue, endValue, lang)
  const capacityLabel = slot.capacity != null ? `${slot.capacity}` : '–'
  const notesLabel = slot.notes ? ` · ${slot.notes}` : ''

  return `${dateLabel} · ${timeLabel} · ${capacityLabel}${notesLabel}`
}

function combineDateAndTime(date: string | null, time: string | null): string | undefined {
  if (!time) return undefined
  const normalizedTime = normalizeTime(time)
  if (!normalizedTime) return undefined

  const safeDate = normalizeDate(date)
  return `${safeDate}T${normalizedTime}`
}

function normalizeTime(time: string): string | null {
  const trimmed = (time || '').trim()
  if (!trimmed) return null
  const match = /^([0-2]\d):([0-5]\d)(?::([0-5]\d))?$/.exec(trimmed)
  if (!match) return trimmed
  const hours = match[1]
  const minutes = match[2]
  return `${hours}:${minutes}`
}

function normalizeDate(date: string | null): string {
  const raw = (date || '').trim()
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw
  if (/^\d{4}-\d{2}-\d{2}T/.test(raw)) return raw.slice(0, 10)
  return '1970-01-01'
}
