// src/Core/Design/data/languages.ts

import { normalizeLocale } from './language-mapping'

export type LanguageEntry = {
  code: string
  label: string
  flag: string
}

// -- Fallback-Liste für Labels (wird NUR genutzt, wenn Intl.DisplayNames nicht verfügbar oder nicht ausreichend) --
const fallbackLabels: Record<string, string> = {
  'de': 'Deutsch',
  'de-CH': 'Deutsch (Schweiz)',
  'fr': 'Français',
  'fr-CH': 'Français (Suisse)',
  'it': 'Italiano',
  'it-CH': 'Italiano (Svizzera)',
  'en': 'English',
  'en-US': 'English (USA)',
  'en-GB': 'English (UK)',
  'es': 'Español',
  'pt': 'Português',
  'zh': '中文',
  'ja': '日本語',
  'ru': 'Русский',
  'ar': 'العربية',
  'tr': 'Türkçe'
  // ➕ beliebig erweiterbar
}

// -- Flaggen-Mapping für Spezialfälle --
const flagMap: Record<string, string> = {
  'en': '🇬🇧',        // Standard englisch = GB-Flagge
  'en-GB': '🇬🇧',     // UK explizit
  'en-US': '🇺🇸',
  'en-CA': '🇨🇦',
  'de': '🇩🇪',
  'de-CH': '🇨🇭',
  'fr': '🇫🇷',
  'fr-CH': '🇨🇭',
  'it': '🇮🇹',
  'it-CH': '🇨🇭',
  'es': '🇪🇸',
  'pt': '🇵🇹',
  'zh': '🇨🇳',
  'ja': '🇯🇵',
  'ru': '🇷🇺',
  'ar': '🇸🇦',
  'tr': '🇹🇷'
  // ➕ beliebig erweiterbar
}

// -- Universelle Flaggenfunktion (zuerst Spezialfälle, dann Unicode-Build, dann Fallback) --
function regionToFlag(regionOrCode: string, code: string = ''): string {
  // Mapping für Spezialfälle (z.B. "en", "en-US", ...)
  if (flagMap[code]) return flagMap[code]
  if (flagMap[regionOrCode]) return flagMap[regionOrCode]
  // ISO-Country zu Unicode Flag (nur für 2-stellige Regions)
  if (/^[a-zA-Z]{2}$/.test(regionOrCode)) {
    return regionOrCode
      .toUpperCase()
      .split('')
      .map(c => String.fromCodePoint(127397 + c.charCodeAt()))
      .join('')
  }
  return '🏳️' // neutrales Fallback
}

// -- Bestes Label ermitteln (Intl bevorzugt, sonst statisch) --
function getLabel(code: string, locale: string): string {
  // 1. Versuche per Intl.DisplayNames (wenn Browser/Node das kann)
  try {
    const intlLocale = normalizeLocale(locale)
    if (typeof Intl !== 'undefined' && typeof Intl.DisplayNames === 'function') {
      const display = new Intl.DisplayNames([intlLocale], { type: 'language' })
      const disp = display.of(code.replace('_', '-'))
      if (disp && disp !== code) return disp
    }
  } catch { /* ignore */ }
  // 2. Fallback auf statische Map
  return fallbackLabels[code] || fallbackLabels[code.replace('_', '-')] || code
}

// -- Hauptfunktion: Sprachcode-Liste zu LanguageEntry[] fürs UI --
export function getLanguages(tags: string[], locale = 'de'): LanguageEntry[] {
  const usedLocale = normalizeLocale(locale)
  if (!Array.isArray(tags)) return []

  return tags.map(langCode => {
    const codeNorm = langCode.replace('_', '-')
    const split = codeNorm.split('-')
    const main = split[0]
    const region = split[1] || main

    return {
      code: langCode,
      label: getLabel(langCode, usedLocale),
      flag: regionToFlag(region, langCode)
    }
  }).sort((a, b) => a.label.localeCompare(b.label, usedLocale))
}
