// scripts/generate-countries.mjs
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const inputFile = path.resolve(__dirname, '../node_modules/intl-tel-input/build/js/data.js')
const outputFile = path.resolve(__dirname, '../src/Core/Design/data/countries.ts')

// Unterstützte Locales laut Intl API
const supportedLocales = Intl.DisplayNames.supportedLocalesOf([
  'af', 'am', 'ar', 'az', 'be', 'bg', 'bn', 'bs', 'ca', 'cs', 'cy', 'da', 'de',
  'el', 'en', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fo', 'fr', 'ga', 'gd', 'gl',
  'gu', 'haw', 'he', 'hi', 'hr', 'ht', 'hu', 'hy', 'id', 'is', 'it', 'ja', 'jv',
  'ka', 'kk', 'km', 'kn', 'ko', 'ku', 'ky', 'la', 'lb', 'lo', 'lt', 'lv', 'mg',
  'mi', 'mk', 'ml', 'mn', 'mr', 'ms', 'mt', 'nb', 'ne', 'nl', 'nn', 'no', 'ny',
  'or', 'pa', 'pl', 'ps', 'pt', 'qu', 'ro', 'ru', 'rw', 'si', 'sk', 'sl', 'sm',
  'sn', 'so', 'sq', 'sr', 'st', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'tk',
  'tl', 'tr', 'tt', 'uk', 'ur', 'uz', 'vi', 'xh', 'yi', 'zh', 'zu'
])

function isoToFlag(code) {
  return code.toUpperCase().split('').map(c => String.fromCodePoint(127397 + c.charCodeAt())).join('')
}

function getLocalizedNames(code) {
  const names = {}
  for (const locale of supportedLocales) {
    try {
      const name = new Intl.DisplayNames([locale], { type: 'region' }).of(code)
      if (name) names[locale] = name
    } catch {
      // Fallback wird automatisch übernommen
    }
  }
  return names
}

const js = fs.readFileSync(inputFile, 'utf-8')
const match = js.match(/var rawCountryData\s*=\s*(\[[\s\S]+?\]);/)

if (!match) {
  console.error('❌ rawCountryData nicht gefunden!')
  process.exit(1)
}

let raw = []
try {
  raw = eval(match[1])
} catch (e) {
  console.error('❌ Fehler beim Parsen:', e)
  process.exit(1)
}

const result = raw.map(([iso2, dial]) => ({
  code: iso2.toUpperCase(),
  dial_code: `+${dial}`,
  flag: isoToFlag(iso2),
  names: getLocalizedNames(iso2.toUpperCase())
}))

const output = `// 🌍 Generated with Intl.DisplayNames – ${supportedLocales.length}+ locales
export function getCountries(lang = 'en') {
  return ${JSON.stringify(result, null, 2)}.map(c => {
    const localized = c.names[lang] || c.names['en'] || c.code
    return {
      code: c.code,
      dial_code: c.dial_code,
      flag: c.flag,
      name: localized,
      label: localized
    }
  })
}
`

fs.mkdirSync(path.dirname(outputFile), { recursive: true })
fs.writeFileSync(outputFile, output, 'utf-8')

console.log(`✅ ${result.length} Länder mit ${supportedLocales.length}+ Sprachen gespeichert unter ${outputFile}`)
