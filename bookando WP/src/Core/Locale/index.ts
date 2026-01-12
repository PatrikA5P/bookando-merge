// src/Core/Locale/index.ts
import dayjs from 'dayjs'
import localizedFormat from 'dayjs/plugin/localizedFormat'
import relativeTime from 'dayjs/plugin/relativeTime'
import weekOfYear from 'dayjs/plugin/weekOfYear'
import isoWeek from 'dayjs/plugin/isoWeek'
import quarterOfYear from 'dayjs/plugin/quarterOfYear'
import updateLocale from 'dayjs/plugin/updateLocale'
import duration from 'dayjs/plugin/duration'

import 'dayjs/locale/de'
import 'dayjs/locale/en'
import 'dayjs/locale/fr'
import 'dayjs/locale/it'
import 'dayjs/locale/es'

import {
  resolveLang,
  getFormatsFor,
  getSupportedLangs as _getSupportedLangs,
} from '@core/Design/data/language-mapping'

// Plugins aktivieren (du nutzt diese Features in den Formattern)
dayjs.extend(localizedFormat)
dayjs.extend(relativeTime)
dayjs.extend(weekOfYear)
dayjs.extend(isoWeek)
dayjs.extend(quarterOfYear)
dayjs.extend(updateLocale)
dayjs.extend(duration)

// -------------------- interner Zustand --------------------
let _lang: string = resolveLang('de')

// -------------------- API --------------------
/** Einmal beim App-Start aufrufen (z. B. mit i18n.locale) */
export function setLocale(langOrLocale: string) {
  _lang = resolveLang(langOrLocale)
  dayjs.locale(_lang)
}

/** Aktive Sprache (2-letter) */
export function getLang(): string {
  return _lang
}

/** Unterstützte Sprachen (aus i18n → available-languages.ts) */
export function getSupportedLangs(): readonly string[] {
  return _getSupportedLangs()
}

/** Datums-/Zeitformat je aktiver Sprache */
export function getDateFmt(): string {
  return getFormatsFor(_lang).date
}
export function getTimeFmt(): string {
  return getFormatsFor(_lang).time
}
export function getDateTimeFmt(): string {
  const f = getFormatsFor(_lang)
  return `${f.date} ${f.time}`
}

export { mergeI18nMessages } from './messages'
