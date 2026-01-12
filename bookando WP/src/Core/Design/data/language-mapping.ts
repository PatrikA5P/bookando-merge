// src/Core/Design/data/language-mapping.ts
/**
 * Sprach-Normierung & Datums-/Zeit-Formate.
 * Quelle der Wahrheit für unterstützte Sprachen: i18n messages.
 */
import { messages } from '@core/Design/i18n'

// -------------------- Helpers --------------------
export function getSupportedLangs(): string[] {
  return Object.keys(messages)
}
export function isSupportedLang(lang: string): boolean {
  return getSupportedLangs().includes((lang || '').slice(0, 2))
}

// -------------------- Locale-Aliase → Basissprache --------------------
/** Keys in lowercase mit '-' statt '_' */
export const LOCALE_ALIASES: Record<string, string> = {
  'de': 'de', 'de-de': 'de', 'de-ch': 'de', 'de-at': 'de', 'de-li': 'de',
  'en': 'en', 'en-gb': 'en', 'en-us': 'en', 'en-au': 'en', 'en-ca': 'en',
  'fr': 'fr', 'fr-fr': 'fr', 'fr-ch': 'fr', 'fr-ca': 'fr', 'fr-be': 'fr',
  'it': 'it', 'it-it': 'it', 'it-ch': 'it',
  'es': 'es', 'es-es': 'es', 'es-mx': 'es', 'es-ar': 'es', 'es-cl': 'es',
}

// -------------------- Datums-/Zeit-Formate pro Sprache --------------------
const DEFAULT_FORMAT = { date: 'DD.MM.YYYY', time: 'HH:mm' }
const BASE_FORMATS: Record<string, { date: string; time: string }> = {
  de: { date: 'DD.MM.YYYY', time: 'HH:mm' },
  en: { date: 'YYYY/MM/DD', time: 'HH:mm' },
  fr: { date: 'DD/MM/YYYY', time: 'HH:mm' },
  it: { date: 'DD/MM/YYYY', time: 'HH:mm' },
  es: { date: 'DD/MM/YYYY', time: 'HH:mm' },
}

export function getFormatsFor(langLike: string): { date: string; time: string } {
  const lang = normalizeLocale(langLike)
  return BASE_FORMATS[lang] || DEFAULT_FORMAT
}

// -------------------- Normalisierung --------------------
export function normalizeLocale(input?: string | null): string {
  const raw = (input || '').toString().trim()
  const canonical = raw.replace('_', '-').toLowerCase()
  // 1) Aliase
  if (canonical && LOCALE_ALIASES[canonical]) return LOCALE_ALIASES[canonical]
  // 2) Basis
  const base = canonical.split('-')[0]
  if (isSupportedLang(base)) return base
  // 3) Fallback: erste vorhandene Sprache oder 'de'
  return getSupportedLangs()[0] || 'de'
}

/** Komfort: Browser/WP/Query → robuste Basissprache */
export function resolveLang(localeLike?: string | null): string {
  return normalizeLocale(localeLike)
}
