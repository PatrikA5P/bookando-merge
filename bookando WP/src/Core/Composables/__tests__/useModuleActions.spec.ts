import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@core/Api/apiClient', () => ({
  apiPost: vi.fn(async () => ({ ok: true, message: 'ok' })),
}))

import { apiPost } from '@core/Api/apiClient'
import { getBulkActionOptions, useModuleActions } from '../useModuleActions'

describe('useModuleActions registry & gates', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    delete (window as any).BOOKANDO_VARS
  })

  it('returns default bulk actions for registered modules', () => {
    const t = (key: string) => key
    const options = getBulkActionOptions(t, 'customers')
    expect(options.map(option => option.value)).toEqual([
      'soft_delete',
      'hard_delete',
      'block',
      'activate',
      'export',
    ])
  })

  it('uses manifest overrides when provided', () => {
    ;(window as any).BOOKANDO_VARS = {
      slug: 'customers',
      module_actions: {
        allowed: ['activate', 'export'],
      },
    }
    const t = (key: string) => key
    const options = getBulkActionOptions(t, 'customers')
    expect(options.map(option => option.value)).toEqual(['activate', 'export'])
  })

  it('applies dynamic config for modules outside the static registry', () => {
    ;(window as any).BOOKANDO_VARS = {
      slug: 'resources',
      module_actions: {
        allowed: ['export'],
      },
    }
    const t = (key: string) => key
    const options = getBulkActionOptions(t, 'resources')
    expect(options.map(option => option.value)).toEqual(['export'])
  })

  it('blocks actions when required license features are missing', async () => {
    ;(window as any).BOOKANDO_VARS = {
      slug: 'customers',
      module_actions: {
        features: {
          export: ['export_csv'],
        },
      },
      license_features: [],
    }

    const { perform } = useModuleActions('customers')
    const result = await perform('export')

    expect(result).toEqual({ ok: false, message: 'Funktion nicht lizenziert' })
    expect(apiPost).not.toHaveBeenCalled()
  })

  it('allows actions when required license features are present', async () => {
    ;(window as any).BOOKANDO_VARS = {
      slug: 'customers',
      module_actions: {
        features: {
          export: ['export_csv'],
        },
      },
      license_features: ['export_csv'],
    }

    const { perform } = useModuleActions('customers')
    const result = await perform('export')

    expect(result).toEqual({ ok: true, message: 'ok' })
    expect(apiPost).toHaveBeenCalledWith('/wp-json/bookando/v1/customers/bulk', {
      action: 'export',
      ids: [],
      payload: undefined,
    })
  })

  it('applies module specific gates (e.g. employees block requires user_roles)', async () => {
    ;(window as any).BOOKANDO_VARS = {
      slug: 'employees',
      module_actions: {
        features: {
          block: ['user_roles'],
        },
      },
      license_features: ['export_csv'],
    }

    const first = useModuleActions('employees')
    const blocked = await first.perform('block')
    expect(blocked).toEqual({ ok: false, message: 'Funktion nicht lizenziert' })
    expect(apiPost).not.toHaveBeenCalled()

    ;(window as any).BOOKANDO_VARS.license_features = ['export_csv', 'user_roles']

    const second = useModuleActions('employees')
    const allowed = await second.perform('block')
    expect(allowed).toEqual({ ok: true, message: 'ok' })
    expect(apiPost).toHaveBeenLastCalledWith('/wp-json/bookando/v1/employees/bulk', {
      action: 'block',
      ids: [],
      payload: undefined,
    })
  })
})
