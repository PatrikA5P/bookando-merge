// src/Core/Design/data/genders.ts
import { messages } from '@core/Design/i18n'

export type GenderOption = { value: 'male'|'female'|'other'|'none'; label: string }
const ORDER: GenderOption['value'][] = ['male','female','other','none']

function dictFor(lang: string) {
  const m: any = (messages as any)[lang] || (messages as any).de || (messages as any).en || {}
  const g   = m.core?.genders || m.genders || {}
  const fb  = ((messages as any).en || {}).core?.genders || ((messages as any).en || {}).genders || {}
  return { ...fb, ...g }
}

export function getGenders(lang = 'de'): GenderOption[] {
  const labels = dictFor(lang)
  return ORDER.filter(k => labels[k]).map(k => ({ value: k, label: String(labels[k]) }))
}
