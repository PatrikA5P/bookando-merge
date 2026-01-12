import { describe, expect, it } from 'vitest'

import { messages as coreMessages } from '@core/Design/i18n'

const supportedLanguages = Object.keys(coreMessages)
const moduleI18nFiles = import.meta.glob('./*/assets/vue/i18n.local.ts', { eager: true })

type Messages = Record<string, any>

type ModuleMessages = {
  messages?: Messages
  default?: { messages?: Messages }
}

function extractMessages(entry: ModuleMessages): Messages | undefined {
  if (entry?.messages) return entry.messages
  if (entry?.default?.messages) return entry.default.messages
  return undefined
}

function collectLeafKeys(source: any, prefix = ''): string[] {
  if (!source || typeof source !== 'object' || Array.isArray(source)) {
    return prefix ? [prefix] : []
  }

  return Object.keys(source)
    .flatMap((key) => {
      const nextPrefix = prefix ? `${prefix}.${key}` : key
      return collectLeafKeys(source[key], nextPrefix)
    })
    .sort()
}

describe('module i18n coverage', () => {
  it('provides all configured languages for every module', () => {
    for (const [path, rawModule] of Object.entries(moduleI18nFiles)) {
      const messages = extractMessages(rawModule as ModuleMessages)
      expect(messages, `${path} must export a messages object`).toBeTruthy()

      const base = messages?.de
      expect(base, `${path} must define German (de) translations as baseline`).toBeTruthy()

      const baselineKeys = collectLeafKeys(base)
      expect(baselineKeys.length, `${path} should expose at least one translation key`).toBeGreaterThan(0)

      for (const lang of supportedLanguages) {
        const target = messages?.[lang]
        expect(target, `${path} missing translations for language "${lang}"`).toBeTruthy()
        const targetKeys = collectLeafKeys(target)
        expect(targetKeys, `${path} translations for ${lang} must match baseline keys`).toEqual(baselineKeys)
      }
    }
  })
})
