import { describe, expect, it } from 'vitest'

import { mergeI18nMessages } from './messages'

describe('mergeI18nMessages', () => {
  const core = {
    de: {
      mod: {
        shared: {
          title: 'Titel',
          nested: { label: 'Core' },
        },
      },
    },
    en: {
      mod: {
        shared: {
          title: 'Title',
          nested: { label: 'Core' },
        },
      },
    },
  }

  it('deep merges local messages into core ones per language', () => {
    const locals = {
      './i18n.local.ts': {
        messages: {
          de: { mod: { shared: { nested: { helper: 'Hinweis' } } } },
          en: { mod: { shared: { nested: { helper: 'Hint' }, caption: 'Offers' } } },
        },
      },
    }

    const merged = mergeI18nMessages(core, locals)

    expect(merged.de.mod.shared.nested).toEqual({ label: 'Core', helper: 'Hinweis' })
    expect(merged.en.mod.shared.nested).toEqual({ label: 'Core', helper: 'Hint' })
    expect(merged.en.mod.shared.caption).toBe('Offers')
  })

  it('returns a merged clone and keeps the original core messages untouched', () => {
    const localMessages = {
      './i18n.local.ts': {
        messages: {
          de: { mod: { shared: { title: 'Lokaler Titel' } } },
        },
      },
    }

    const merged = mergeI18nMessages(core, localMessages)

    expect(merged.de.mod.shared.title).toBe('Lokaler Titel')
    expect(core.de.mod.shared.title).toBe('Titel')
  })

  it('supports modules that only expose a default export', () => {
    const locals = {
      './i18n.local.ts': {
        default: {
          messages: {
            it: { mod: { shared: { title: 'Titolo' } } },
          },
        },
      },
    }

    const merged = mergeI18nMessages(core, locals)

    expect(merged.it.mod.shared.title).toBe('Titolo')
    expect(Object.keys(merged)).toEqual(['de', 'en', 'it'])
  })

  it('returns core messages when no locals are provided', () => {
    const merged = mergeI18nMessages(core)
    expect(merged).toEqual(core)
    expect(merged).not.toBe(core)
  })
})
