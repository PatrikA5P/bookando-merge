// src/core/Composables/useTimeRanges.ts
export type TimeRange = { start: string; end: string }

/** "HH:mm" -> Minuten ab 00:00; bei Ungültig NaN */
function toMin(hhmm: string): number {
  const m = /^(\d{2}):(\d{2})/.exec(hhmm || '')
  if (!m) return NaN
  return Number(m[1]) * 60 + Number(m[2])
}

/** Sekunden ignorieren; liefert [A,B] falls A<B, sonst null */
function normalizeRange(a: string, b: string): [string, string] | null {
  const A = (a || '').trim(), B = (b || '').trim()
  if (!A || !B) return null
  const cut = (s: string) => (/^\d{2}:\d{2}:\d{2}$/.test(s) ? s.slice(0, 5) : s)
  const sA = cut(A), sB = cut(B)
  return sA < sB ? [sA, sB] : null
}

/** Entfernt leere/ungültige Bereiche und normalisiert auf HH:mm */
function sanitizeTimes(list: TimeRange[]): TimeRange[] {
  const out: TimeRange[] = []
  for (const r of list || []) {
    const v = normalizeRange(r.start, r.end)
    if (v) out.push({ start: v[0], end: v[1] })
  }
  return out
}

function rangesOverlap(a: [number, number], b: [number, number]) {
  return a[0] < b[1] && b[0] < a[1]
}
function rangeContains(outer: [number, number], inner: [number, number]) {
  return outer[0] <= inner[0] && inner[1] <= outer[1]
}

/** Hilfsfunktion: TimeRange[] -> Minuten-Paare */
function toPairs(arr: TimeRange[]): [number, number][] {
  return (arr || []).map(r => [toMin(r.start), toMin(r.end)] as [number, number])
}

export function useTimeRanges() {
  return {
    toMin,
    normalizeRange,
    sanitizeTimes,
    rangesOverlap,
    rangeContains,
    toPairs
  }
}
