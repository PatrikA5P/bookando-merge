import { describe, it, expect, vi } from 'vitest'

import {
  buildCustomerDataFields,
  normalizeCustomerFromApi,
  serializeCustomerForSave,
  useCustomerDetailsCache,
  initials,
  statusClass,
  avatarSrcFromAny,
} from './useCustomerData'

describe('useCustomerData helpers', () => {
  it('normalizes API payloads into a customer view model', () => {
    const vm = normalizeCustomerFromApi({
      id: '5',
      first_name: '  Max ',
      email: 'max@example.com',
      total_appointments: '3',
      language: null,
    })

    expect(vm.form.id).toBe('5')
    expect(vm.form.first_name).toBe('  Max ')
    expect(vm.form.language).toBe('de')
    expect(vm.stats.total_appointments).toBe(3)
  })

  it('serializes customer form data with sensible defaults', () => {
    const payload = serializeCustomerForSave({
      form: {
        first_name: ' Anna ',
        last_name: '  Berg  ',
        language: '',
      },
      stats: {},
    })

    expect(payload.first_name).toBe('Anna')
    expect(payload.last_name).toBe('Berg')
    expect(payload.language).toBe('')
    expect(payload.status).toBe('active')
  })

  it('serializes form objects directly', () => {
    const payload = serializeCustomerForSave({
      first_name: ' Lisa ',
      status: 'blocked' as any,
      note: undefined,
    } as any)

    expect(payload.first_name).toBe('Lisa')
    expect(payload.status).toBe('blocked')
    expect(payload.note).toBe('')
  })

  it('caches detail requests and exposes normalized getters', async () => {
    const loader = vi.fn().mockResolvedValue({ id: 9, first_name: 'Eva' })
    const cache = useCustomerDetailsCache(loader)

    await cache.ensure(9)
    await cache.ensure(9)

    expect(loader).toHaveBeenCalledTimes(1)
    expect(cache.isLoading(9)).toBe(false)
    expect(cache.getVM(9)?.form.first_name).toBe('Eva')
    expect(cache.getRaw(9)).toEqual({ id: 9, first_name: 'Eva' })

    cache.invalidate(9)
    expect(cache.getRaw(9)).toBeNull()

    await cache.ensure(9)
    expect(loader).toHaveBeenCalledTimes(2)
    cache.invalidate()
    expect(cache.getRaw(9)).toBeNull()
  })

  it('builds field metadata using translator fallbacks', () => {
    const translator = vi.fn((key: string) => {
      if (key === 'fields.customers.id') {
        return 'Kunden-ID'
      }
      if (key === 'fields.last_name') {
        return 'Nachname'
      }
      return ''
    })

    const fields = buildCustomerDataFields(translator)
    expect(fields[0]).toEqual({ key: 'id', label: 'Kunden-ID' })
    expect(fields.find((f) => f.key === 'last_name')?.label).toBe('Nachname')

    const fallbackFields = buildCustomerDataFields(() => {
      throw new Error('translator missing')
    })
    expect(fallbackFields[0].label).toBe('id')
  })

  it('computes simple string helpers', () => {
    expect(initials({ first_name: 'Max', last_name: 'Muster' })).toBe('MM')
    expect(initials({ first_name: '', last_name: 'Solo' })).toBe('S')

    expect(statusClass('active')).toBe('active')
    expect(statusClass('blocked')).toBe('inactive')
    expect(statusClass('deleted')).toBe('deleted')
    expect(statusClass('other')).toBe('deleted')
  })

  it('resolves avatar sources from various shapes', () => {
    expect(avatarSrcFromAny(' https://example.test/avatar.png ')).toBe('https://example.test/avatar.png')
    expect(
      avatarSrcFromAny({
        sizes: {
          thumbnail: '',
          medium: 'https://example.test/medium.png',
        },
      })
    ).toBe('https://example.test/medium.png')
    expect(
      avatarSrcFromAny({
        url: 'https://example.test/photo.png',
      })
    ).toBe('https://example.test/photo.png')
    expect(avatarSrcFromAny(null)).toBe('')
  })
})
