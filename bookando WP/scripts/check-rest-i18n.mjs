#!/usr/bin/env node
import { glob } from 'glob'
import { readFile } from 'node:fs/promises'

const files = [
  ...(await glob('src/modules/*/Api/*.php', { windowsPathsNoEscape: true })),
  ...(await glob('src/modules/*/RestHandler.php', { windowsPathsNoEscape: true })),
]

const patterns = [
  {
    label: 'REST parameter description',
    regex: /'description'\s*=>\s*'([^']*[A-Za-z][^']*)'/g,
  },
  {
    label: 'REST parameter description',
    regex: /"description"\s*=>\s*"([^"]*[A-Za-z][^"]*)"/g,
  },
  {
    label: 'REST response message',
    regex: /'message'\s*=>\s*'([^']*[A-Za-z][^']*)'/g,
  },
  {
    label: 'REST response message',
    regex: /"message"\s*=>\s*"([^"]*[A-Za-z][^"]*)"/g,
  },
  {
    label: 'WP_Error message',
    regex: /new\s+WP_Error\([^,]+,\s*'([^']*[A-Za-z][^']*)'/g,
  },
  {
    label: 'WP_Error message',
    regex: /new\s+WP_Error\([^,]+,\s*"([^"]*[A-Za-z][^"]*)"/g,
  },
]

const results = []

for (const file of files) {
  const source = await readFile(file, 'utf8')
  for (const { regex, label } of patterns) {
    for (const match of source.matchAll(regex)) {
      const value = match[1]
      if (!value || /^\s*$/.test(value)) continue
      const index = match.index ?? 0
      // allow translations that already call __()/_x()
      if (/__\(|_x\(/.test(value)) continue
      const line = source.slice(0, index).split('\n').length
      results.push(`${file}:${line} – ${label} should use __()/_x(): "${value.trim()}"`)
    }
  }
}

if (results.length) {
  console.error('\nUntranslated REST strings detected:')
  for (const entry of results) {
    console.error(`  • ${entry}`)
  }
  console.error('\nPlease wrap user-facing strings in __()/ _x() with the correct text domain.')
  process.exit(1)
}

console.log('REST i18n check passed.')
