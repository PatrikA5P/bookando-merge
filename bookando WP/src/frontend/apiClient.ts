const DEFAULT_TIMEOUT = 10_000
const DEFAULT_RETRIES = 0
const DEFAULT_RETRY_DELAY = 250
const DEFAULT_BASE = '/wp-json/bookando/v1/employees'

const missingConfig = new Set<string>()

type BookandoVars = NonNullable<Window['BOOKANDO_VARS']> | undefined

type ApiErrorKind = 'http' | 'timeout' | 'network' | 'unknown'

export interface ApiErrorDetails {
  message: string
  url: string
  attempt: number
  status?: number
  statusText?: string
  body?: unknown
  cause?: unknown
  kind: ApiErrorKind
}

export class ApiError extends Error {
  readonly url: string
  readonly attempt: number
  readonly status?: number
  readonly statusText?: string
  readonly body?: unknown
  readonly cause?: unknown
  readonly kind: ApiErrorKind

  constructor(details: ApiErrorDetails) {
    super(details.message)
    this.name = 'ApiError'
    this.url = details.url
    this.attempt = details.attempt
    this.status = details.status
    this.statusText = details.statusText
    this.body = details.body
    this.cause = details.cause
    this.kind = details.kind
  }

  toJSON() {
    const { name, message, url, attempt, status, statusText, body, kind } = this
    return { name, message, url, attempt, status, statusText, body, kind }
  }
}

export type RetryDelay = number | ((attempt: number) => number)

export interface ApiRequestOptions<TBody = unknown> {
  params?: Record<string, unknown>
  headers?: HeadersInit
  timeout?: number
  retries?: number
  retryDelay?: RetryDelay
  signal?: AbortSignal
  credentials?: RequestCredentials
  data?: TBody
}

export interface RequestOptions<TBody = unknown> extends ApiRequestOptions<TBody> {
  method?: string
}

function logMissingConfig(key: string, vars: BookandoVars) {
  if (missingConfig.has(key)) return
  missingConfig.add(key)
  const payload = { key, vars: vars ?? null }
  console.error('[apiClient] Missing configuration', payload)
  window.Sentry?.captureMessage?.(`BOOKANDO_VARS missing ${key}`, { extra: payload })
}

function resolveBase(): string {
  const vars = window.BOOKANDO_VARS
  if (!vars || !vars.rest_url) {
    logMissingConfig('rest_url', vars)
  }
  const base = vars?.rest_url ?? DEFAULT_BASE
  return base.replace(/\/+$/, '')
}

function ensureNonce(headers: Headers) {
  const nonce = window.BOOKANDO_VARS?.nonce
  if (nonce && !headers.has('X-WP-Nonce')) {
    headers.set('X-WP-Nonce', nonce)
  }
}

function isBodyInit(value: unknown): value is BodyInit {
  return value instanceof Blob || value instanceof FormData || value instanceof URLSearchParams || typeof value === 'string'
}

function normalizeBody(data: unknown, method: string, headers: Headers) {
  if (method === 'GET' || data === undefined || data === null) return undefined
  if (isBodyInit(data)) return data
  if (typeof data === 'object') {
    if (!headers.has('Content-Type')) headers.set('Content-Type', 'application/json')
    return JSON.stringify(data)
  }
  return String(data)
}

function appendParams(url: string, params?: Record<string, unknown>) {
  if (!params) return url
  const searchParams = new URLSearchParams()
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null) continue
    const values = Array.isArray(value) ? value : [value]
    for (const item of values) {
      searchParams.append(key, String(item))
    }
  }
  const query = searchParams.toString()
  if (!query) return url
  return url.includes('?') ? `${url}&${query}` : `${url}?${query}`
}

function buildUrl(endpoint: string, params?: Record<string, unknown>) {
  const needsBase = !/^https?:\/\//i.test(endpoint)
  const base = needsBase ? resolveBase() : ''
  let url = endpoint
  if (needsBase) {
    if (endpoint.startsWith('/')) {
      url = `${base}${endpoint}`
    } else {
      url = `${base}/${endpoint}`
    }
  }
  return appendParams(url, params)
}

function mergeSignals(signal: AbortSignal | undefined, controller: AbortController) {
  if (!signal) return
  if (signal.aborted) {
    controller.abort(signal.reason)
    return
  }
  const abort = () => controller.abort(signal.reason)
  signal.addEventListener('abort', abort, { once: true })
  controller.signal.addEventListener('abort', () => signal.removeEventListener('abort', abort), { once: true })
}

async function delay(ms: number) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

function createApiError(kind: ApiErrorKind, message: string, url: string, attempt: number, options: Partial<ApiErrorDetails>) {
  const error = new ApiError({
    kind,
    message,
    url,
    attempt,
    status: options.status,
    statusText: options.statusText,
    body: options.body,
    cause: options.cause,
  })
  return error
}

async function parseBody<T>(response: Response): Promise<T> {
  if (response.status === 204) return undefined as T
  const contentType = response.headers.get('content-type') || ''
  if (contentType.includes('application/json')) {
    return await response.json() as T
  }
  const text = await response.text()
  return text as unknown as T
}

export async function request<T = unknown>(endpoint: string, options: RequestOptions = {}) {
  const {
    method = 'GET',
    params,
    headers: incomingHeaders,
    timeout = DEFAULT_TIMEOUT,
    retries = DEFAULT_RETRIES,
    retryDelay = DEFAULT_RETRY_DELAY,
    signal,
    credentials = 'same-origin',
    data,
  } = options

  const headers = new Headers(incomingHeaders)
  ensureNonce(headers)
  const url = buildUrl(endpoint, params)
  let attempt = 0
  let lastError: ApiError | undefined

  while (attempt <= retries) {
    const controller = new AbortController()
    mergeSignals(signal, controller)
    const timer = setTimeout(() => controller.abort(new DOMException('Timeout', 'AbortError')), timeout)

    try {
      const response = await fetch(url, {
        method,
        headers,
        credentials,
        body: normalizeBody(data, method, headers),
        signal: controller.signal,
      })

      clearTimeout(timer)

      if (!response.ok) {
        const body = await parseBody(response).catch(() => undefined)
        throw createApiError('http', `Request failed with status ${response.status}`, url, attempt, {
          status: response.status,
          statusText: response.statusText,
          body,
        })
      }

      return await parseBody<T>(response)
    } catch (error) {
      clearTimeout(timer)
      const isAbort = error instanceof DOMException && error.name === 'AbortError'
      const kind: ApiErrorKind = isAbort ? 'timeout' : error instanceof ApiError ? error.kind : 'network'
      const normalized = error instanceof ApiError
        ? error
        : createApiError(kind, error instanceof Error ? error.message : 'Unknown error', url, attempt, { cause: error })

      lastError = normalized

      if (attempt >= retries) {
        window.Sentry?.captureException?.(normalized, { tags: { module: 'apiClient' } })
        throw normalized
      }

      const delayMs = typeof retryDelay === 'function' ? retryDelay(attempt + 1) : retryDelay
      if (delayMs > 0) await delay(delayMs)
      attempt += 1
    }
  }

  if (lastError) throw lastError
  throw createApiError('unknown', 'Unknown error', url, attempt, {})
}

export function apiGet<T = unknown>(endpoint: string, params?: Record<string, unknown>, options?: ApiRequestOptions) {
  const mergedParams = { ...(options?.params ?? {}), ...(params ?? {}) }
  const nextOptions = { ...options, params: Object.keys(mergedParams).length ? mergedParams : undefined }
  return request<T>(endpoint, { ...nextOptions, method: 'GET' })
}

export function apiPost<T = unknown>(endpoint: string, data?: unknown, options?: ApiRequestOptions) {
  return request<T>(endpoint, { ...options, method: 'POST', data })
}

export function apiPut<T = unknown>(endpoint: string, data?: unknown, options?: ApiRequestOptions) {
  return request<T>(endpoint, { ...options, method: 'PUT', data })
}

export function apiDelete<T = unknown>(endpoint: string, options?: ApiRequestOptions) {
  return request<T>(endpoint, { ...options, method: 'DELETE' })
}

export type EmpCalInput = {
  calendar: 'google' | 'outlook' | 'exchange' | 'apple'
  calendar_id: string
  mode: 'ro' | 'wb'
  connection_id?: number
}

function withBase(path: string) {
  return path.startsWith('/') ? path : `/${path}`
}

export const EmployeesApi = {
  listCalendars: (userId: number) => apiGet(withBase(`${userId}/calendars`)),
  startOauth: (userId: number, provider: 'google' | 'outlook', mode: 'ro' | 'wb' = 'ro') =>
    apiPost(withBase(`${userId}/calendar/connections/oauth/start`), { provider, mode }),
  connectIcs: (userId: number, url: string, name?: string) =>
    apiPost(withBase(`${userId}/calendar/connections/ics`), { url, name }),
  disconnectIcs: (userId: number, connectionId: number) =>
    apiDelete(withBase(`${userId}/calendar/connections/ics`), { params: { connection_id: connectionId } }),
  replaceCalendars: (userId: number, calendars: EmpCalInput[]) =>
    apiPut(withBase(`${userId}/calendars`), { calendars }),
  sendInvite: (userId: number, payload: any) =>
    apiPost(withBase(`${userId}/calendar/invite`), payload),
}
