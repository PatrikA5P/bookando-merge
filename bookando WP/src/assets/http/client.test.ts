import { afterEach, describe, expect, it, vi } from 'vitest'
import type { AxiosResponse, InternalAxiosRequestConfig } from 'axios'

const createMockResponse = (config: InternalAxiosRequestConfig): AxiosResponse<unknown> => ({
  data: {},
  status: 200,
  statusText: 'OK',
  headers: {} as AxiosResponse['headers'],
  config,
})

describe('assets/http/client runtime', () => {
  afterEach(() => {
    delete window.BOOKANDO_VARS
    delete window.wpApiSettings
    vi.resetModules()
  })

  it('uses explicit rest_url_base when provided', async () => {
    window.BOOKANDO_VARS = {
      rest_root: 'https://demo.test/wp-json/',
      rest_url_base: 'https://demo.test/wp-json/bookando/v1',
      rest_nonce: 'nonce-123',
      lang: 'de',
      origin: 'https://demo.test',
    }

    const clientModule = await import('./client')

    expect(clientModule.restClient.defaults.baseURL).toBe('https://demo.test/wp-json/bookando/v1/')
    expect(clientModule.rootClient.defaults.baseURL).toBe('https://demo.test/wp-json/')
  })

  it('derives rest_url_base from rest_url when the bridge omits it', async () => {
    window.BOOKANDO_VARS = {
      rest_root: 'https://fallback.test/wp-json/',
      rest_url: 'https://fallback.test/wp-json/bookando/v1/employees',
    }

    const clientModule = await import('./client')

    expect(clientModule.restClient.defaults.baseURL).toBe('https://fallback.test/wp-json/bookando/v1/')
    expect(clientModule.rootClient.defaults.baseURL).toBe('https://fallback.test/wp-json/')
  })

  it('prefers the WP nonce even if a bearer token is configured', async () => {
    window.BOOKANDO_VARS = {
      rest_root: 'https://secure.test/wp-json/',
      rest_url_base: 'https://secure.test/wp-json/bookando/v1',
      rest_nonce: 'nonce-777',
    }

    const clientModule = await import('./client')

    clientModule.setToken('broken-token')

    await clientModule.restClient.get('/employees', {
      adapter: async (config) => {
        expect(config.baseURL).toBe('https://secure.test/wp-json/bookando/v1/')
        expect((config.headers as Record<string, string>)['X-WP-Nonce']).toBe('nonce-777')
        expect((config.headers as Record<string, string>)['Authorization']).toBeUndefined()
        return createMockResponse(config)
      },
    })
  })
})
