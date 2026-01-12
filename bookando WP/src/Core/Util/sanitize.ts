// src/Core/Util/sanitize.ts
export const asString = (value: unknown): string => `${value ?? ''}`

export function safeStartsWith(value: unknown, prefix: string): boolean {
  const s = asString(value).toLowerCase()
  return s.startsWith(prefix.toLowerCase())
}

export function normalizeCountry(value: unknown): string | null {
  const raw = asString((value as any)?.code ?? value).trim().toUpperCase()
  return /^[A-Z]{2}$/.test(raw) ? raw : null
}

// Bleib bei Unterstrich-Form, z.B. "de_CH"
export function normalizeLanguage(value: unknown): string | null {
  const raw = asString((value as any)?.value ?? value).trim()
  if (!raw) return null
  const parts = raw.replace('-', '_').split('_')
  if (parts[0]) parts[0] = parts[0].toLowerCase()
  if (parts[1]?.length === 2) parts[1] = parts[1].toUpperCase()
  return parts.join('_')
}
