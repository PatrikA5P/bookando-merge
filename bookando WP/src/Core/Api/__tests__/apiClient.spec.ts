import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

type FetchCall = Parameters<typeof fetch>

declare global {
  interface Window {
    BOOKANDO_VARS?: {
      nonce?: string
      rest_url?: string
    }
  }
}

const jsonResponse = (value: unknown) => ({
  ok: true,
  status: 200,
  json: async () => value,
})

describe('apiClient', () => {
  const fetchSpy = vi.fn<FetchCall, Promise<ReturnType<typeof jsonResponse>>>()
  let apiGet: typeof import('../apiClient').apiGet
  let apiPost: typeof import('../apiClient').apiPost
  let apiDelete: typeof import('../apiClient').apiDelete
  let EmployeesApi: typeof import('../apiClient').EmployeesApi

  beforeEach(async () => {
    fetchSpy.mockResolvedValue(jsonResponse({ success: true }) as any)
    vi.stubGlobal('fetch', fetchSpy as unknown as typeof fetch)
    window.BOOKANDO_VARS = {
      nonce: 'secure-nonce',
      rest_url: '/wp-json/bookando/v1/employees',
    }

    vi.resetModules()
    const module = await import('../apiClient')
    apiGet = module.apiGet
    apiPost = module.apiPost
    apiDelete = module.apiDelete
    EmployeesApi = module.EmployeesApi
  })

  afterEach(() => {
    fetchSpy.mockReset()
    vi.unstubAllGlobals()
  })

  it('performs GET requests with nonce header and query params', async () => {
    await apiGet('/wp-json/bookando/v1/test', { foo: 'bar', baz: '1' })

    expect(fetchSpy).toHaveBeenCalledWith(
      '/wp-json/bookando/v1/test?foo=bar&baz=1',
      expect.objectContaining({
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': 'secure-nonce' },
      })
    )
  })

  it('performs POST requests with json body and nonce header', async () => {
    await apiPost('/endpoint', { foo: 'bar' })

    expect(fetchSpy).toHaveBeenCalledWith(
      '/endpoint',
      expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Content-Type': 'application/json',
          'X-WP-Nonce': 'secure-nonce',
        }),
        body: JSON.stringify({ foo: 'bar' }),
      })
    )
  })

  it('throws when fetch indicates failure', async () => {
    fetchSpy.mockResolvedValueOnce({
      ok: false,
      status: 500,
      json: async () => ({}),
    } as any)

    await expect(apiDelete('/bad')).rejects.toThrow('API-Error: 500')
  })

  it('sends correct payload for Employees API helpers', async () => {
    fetchSpy.mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({}),
    } as any)

    await EmployeesApi.startOauth(7, 'google', 'wb')
    await EmployeesApi.disconnectIcs(7, 99)
    await EmployeesApi.replaceCalendars(7, [{ calendar: 'google', calendar_id: 'abc', mode: 'ro' }])

    expect(fetchSpy).toHaveBeenNthCalledWith(
      1,
      '/wp-json/bookando/v1/employees/7/calendar/connections/oauth/start',
      expect.objectContaining({
        method: 'POST',
        body: JSON.stringify({ provider: 'google', mode: 'wb' }),
      })
    )

    expect(fetchSpy).toHaveBeenNthCalledWith(
      2,
      '/wp-json/bookando/v1/employees/7/calendar/connections/ics?connection_id=99',
      expect.objectContaining({ method: 'DELETE' })
    )

    expect(fetchSpy).toHaveBeenNthCalledWith(
      3,
      '/wp-json/bookando/v1/employees/7/calendars',
      expect.objectContaining({
        method: 'PUT',
        body: JSON.stringify({ calendars: [{ calendar: 'google', calendar_id: 'abc', mode: 'ro' }] }),
      })
    )
  })
})
