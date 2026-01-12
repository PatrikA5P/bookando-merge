import { describe, expect, it, vi, afterEach } from 'vitest'
import { onLangChange } from './bridge'

describe('onLangChange', () => {
  let dispose: (() => void) | undefined

  afterEach(() => {
    dispose?.()
    dispose = undefined
  })

  it('invokes callback with provided raw and i18n values from the event', () => {
    const callback = vi.fn()
    dispose = onLangChange(callback)

    const detail = { lang: ' de-CH ', i18n: 'de' }
    window.dispatchEvent(new CustomEvent('bookando:lang-changed', { detail }))

    expect(callback).toHaveBeenCalledWith('de-CH', 'de')
  })

  it('normalizes i18n locale when detail.i18n is missing', () => {
    const callback = vi.fn()
    dispose = onLangChange(callback)

    const detail = { lang: 'fr-CA' }
    window.dispatchEvent(new CustomEvent('bookando:lang-changed', { detail }))

    expect(callback).toHaveBeenCalledWith('fr-CA', 'fr')
  })
})
