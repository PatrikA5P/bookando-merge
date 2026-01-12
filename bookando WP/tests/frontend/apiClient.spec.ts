import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { ApiError, apiGet, request } from '../../src/frontend/apiClient'

describe('apiClient', () => {
  const originalFetch = global.fetch
  const originalConsoleError = console.error

  beforeEach(() => {
    vi.restoreAllMocks()
    console.error = vi.fn()
  })

  afterEach(() => {
    vi.useRealTimers()
    delete (window as any).BOOKANDO_VARS
    window.Sentry = undefined
    console.error = originalConsoleError
    if (originalFetch) {
      global.fetch = originalFetch
    }
  })

  it('retries requests when fetch rejects and eventually succeeds', async () => {
    ;(window as any).BOOKANDO_VARS = { rest_url: 'https://api.example.test/base', nonce: 'abc' }

    const fetchMock = vi.fn()
      .mockRejectedValueOnce(new TypeError('Network down'))
      .mockResolvedValueOnce(new Response(JSON.stringify({ ok: true }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }))

    vi.stubGlobal('fetch', fetchMock)

    const result = await request<{ ok: boolean }>('/resource', { retries: 1 })

    expect(fetchMock).toHaveBeenCalledTimes(2)
    expect(fetchMock.mock.calls[0][0]).toBe('https://api.example.test/base/resource')
    expect(result).toEqual({ ok: true })
  })

  it('wraps http errors in ApiError with normalized payload', async () => {
    ;(window as any).BOOKANDO_VARS = { rest_url: 'https://api.example.test/base', nonce: 'abc' }

    const response = new Response(JSON.stringify({ message: 'Kaputt' }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' },
      statusText: 'Server kaputt',
    })

    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(response))

    await expect(request('/broken'))
      .rejects
      .toMatchObject<ApiError>({
        kind: 'http',
        status: 500,
        statusText: 'Server kaputt',
        body: { message: 'Kaputt' },
      })
  })

  it('aborts on timeout and reports a timeout ApiError', async () => {
    ;(window as any).BOOKANDO_VARS = { rest_url: 'https://api.example.test/base', nonce: 'abc' }

    vi.useFakeTimers()

    vi.stubGlobal('fetch', vi.fn((_input, init: RequestInit = {}) => {
      return new Promise((_resolve, reject) => {
        init.signal?.addEventListener('abort', () => {
          reject(new DOMException('Timed out', 'AbortError'))
        })
      })
    }))

    const requestPromise = request('/slow', { timeout: 5 })
    const expectation = expect(requestPromise).rejects.toMatchObject({ kind: 'timeout' })

    await vi.advanceTimersByTimeAsync(5)
    await expectation
  })

  it('logs missing rest_url configuration and uses fallback base', async () => {
    ;(window as any).BOOKANDO_VARS = { nonce: 'abc' }

    const fetchMock = vi.fn().mockResolvedValue(new Response(JSON.stringify({ ok: true }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    }))

    vi.stubGlobal('fetch', fetchMock)

    await apiGet('test')

    expect(console.error).toHaveBeenCalledWith('[apiClient] Missing configuration', expect.objectContaining({ key: 'rest_url' }))
    expect(fetchMock).toHaveBeenCalledWith('/wp-json/bookando/v1/employees/test', expect.any(Object))
  })
})
