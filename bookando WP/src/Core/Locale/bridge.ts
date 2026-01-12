// src/Core/Locale/bridge.ts
import type { I18n } from 'vue-i18n'
import { setLocale as setDayJsLocale, getLang } from './index'
import { normalizeLocale } from '@core/Design/data/language-mapping'

type BootOpts = { available?: readonly string[]; fallback?: string }
type LangEventDetail = { lang: string; i18n: string } // raw + normalized

/** Aktuelle "raw" Locale aus Bridge/Browser ziehen. */
function readRawLocale(): string {
  return String(
    (window as any).BOOKANDO_VARS?.lang ||
    (window as any).BOOKANDO_VARS?.wp_locale ||
    navigator.language ||
    'de'
  )
}

/** Bestimme zweistellige i18n-Locale inkl. Verfügbarkeitsprüfung. */
function resolveI18nLocale(raw: string, opts?: BootOpts): string {
  const code = normalizeLocale(raw) // → 'de' | 'en' | 'fr' | 'it' …
  if (opts?.available?.length) {
    return opts.available.includes(code) ? code : (opts.fallback ?? 'de')
  }
  return code
}

/** Einmaliger Boot: liest Bridge, setzt <html lang>, synced Dayjs. */
export function bootLocaleFromBridge(opts?: BootOpts) {
  const raw = readRawLocale()
  const i18nLocale = resolveI18nLocale(raw, opts)
  document.documentElement.setAttribute('lang', i18nLocale)
  setDayJsLocale(i18nLocale)
  return { rawLocale: raw, i18nLocale }
}

/** Hilfs-Setter für <html lang>. */
export function setHTMLLang(code: string) {
  document.documentElement.setAttribute('lang', code)
}

/** Global anwenden (Settings → alle SPAs). */
export function applyGlobalLocale(raw: string) {
  const nextRaw = String(raw || '').trim() || 'de'
  const nextI18n = normalizeLocale(nextRaw)

  // <html lang>, Dayjs sofort syncen
  setHTMLLang(nextI18n)
  setDayJsLocale(nextI18n)

  // Bridge-Variable aktualisieren
  ;(window as any).BOOKANDO_VARS = (window as any).BOOKANDO_VARS || {}
  ;(window as any).BOOKANDO_VARS.lang = nextRaw

  // Einheitliches Broadcast-Event (raw + normalized)
  const detail: LangEventDetail = { lang: nextRaw, i18n: nextI18n }
  window.dispatchEvent(new CustomEvent('bookando:lang-changed', { detail }))
}

/**
 * I18n-agnostischer Listener (bevorzugt in neuen Modulen).
 * Gibt eine Dispose-Funktion zurück.
 */
export function onLangChange(cb: (raw: string, i18n: string) => void) {
  const handler = (_e: any) => {
    const raw = String(_e?.detail?.lang || '').trim()
    const i18n = String(_e?.detail?.i18n || normalizeLocale(raw))
    if (!raw) return
    cb(raw, i18n)
  }
  window.addEventListener('bookando:lang-changed', handler)
  return () => window.removeEventListener('bookando:lang-changed', handler)
}

/**
 * Bequemer Wrapper für bestehende SPAs mit vue-i18n-Instanz.
 * (Backward-compat zu deinem bisherigen Code.)
 */
export function initLocaleBridge(i18n: I18n) {
  const dispose = onLangChange((_raw, i18nCode) => {
    if (i18n.global.locale.value !== i18nCode) i18n.global.locale.value = i18nCode
    setDayJsLocale(i18nCode)
    setHTMLLang(i18nCode)
  })
  // initial Dayjs-Sync (falls SPA später mounted)
  setDayJsLocale(i18n.global.locale.value || getLang())
  return dispose
}
