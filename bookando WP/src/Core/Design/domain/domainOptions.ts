// @core/Design/domain/domainOptions.ts
import { messages } from '@core/Design/i18n'
import { getCountries } from '@core/Design/data/countries-optimized'
import { getLanguages } from '@core/Design/data/languages'
import { getGenders  } from '@core/Design/data/genders'

export type Option = {
  value: string
  label: string
  // ➕ neu:
  code?: string
  flag?: string
  dial_code?: string
  disabled?: boolean
}

export function countryOptions(locale: string): Option[] {
  return getCountries(locale).map(c => ({
    // 🔑 ISO 2 immer doppelt führen: code + value
    code:  c.code,
    value: c.code,
    label: c.label ?? c.name,
    flag:  c.flag,
    dial_code: c.dial_code
  }))
}

export function languageOptions(locale: string) {
  const SUPPORTED = Object.keys(messages) // Quelle der Wahrheit
  return getLanguages(SUPPORTED, locale).map(l => ({
    code: l.code, value: l.code, label: l.label, flag: l.flag
  }))
}

export function genderOptions(locale: string): Option[] {
  // UI-/DB-Code m|f|d|n
  const map: Record<string, string> = { male: 'm', female: 'f', other: 'd', none: 'n' }
  return getGenders(locale).map(g => {
    const code = map[g.value] ?? g.value
    return {
      code,          // konsistent, falls du irgendwo ({{opt.code}}) renderst
      value: code,   // was ans v-model/Backend geht
      label: g.label // Label kommt derzeit aus genders.ts (Variante A)
    }
  })
}
