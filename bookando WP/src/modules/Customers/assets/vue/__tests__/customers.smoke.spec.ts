import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

describe('Customers SPA entry', () => {
  beforeEach(() => {
    vi.resetModules()
    document.body.innerHTML = '<div id="bookando-customers-root"></div>'
    ;(window as any).BOOKANDO_VARS = {
      slug: 'customers',
      lang: 'de',
      license_features: ['export_csv', 'user_roles'],
      module_actions: {
        allowed: ['soft_delete', 'hard_delete', 'block', 'activate', 'export'],
        features: {
          export: ['export_csv'],
        },
      },
    }
    if (typeof window.matchMedia !== 'function') {
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockImplementation(query => ({
          matches: false,
          media: query,
          onchange: null,
          addListener: vi.fn(),
          removeListener: vi.fn(),
          addEventListener: vi.fn(),
          removeEventListener: vi.fn(),
          dispatchEvent: vi.fn(),
        })),
      })
    }
    if (typeof window.ResizeObserver !== 'function') {
      class ResizeObserverStub {
        constructor(_callback: ResizeObserverCallback) {}
        observe() {}
        unobserve() {}
        disconnect() {}
      }
      Object.defineProperty(window, 'ResizeObserver', {
        writable: true,
        value: ResizeObserverStub,
      })
    }
  })

  afterEach(() => {
    document.body.innerHTML = ''
    delete (window as any).BOOKANDO_VARS
  })

  it('mounts the customers admin layout', async () => {
    await import('../main')
    const root = document.querySelector('#bookando-customers-root')
    expect(root?.querySelector('.bookando-page')).not.toBeNull()
  }, 15000)
})
