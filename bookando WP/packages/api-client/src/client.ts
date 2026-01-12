/**
 * @bookando/api-client - Core Client
 * Unified API client for all Bookando platforms
 */

import type { ListResponse, PaginationParams, SortParams, ApiError } from '@bookando/types';

const DEFAULT_TIMEOUT = 10_000;
const DEFAULT_RETRIES = 0;
const DEFAULT_RETRY_DELAY = 250;

type ApiErrorKind = 'http' | 'timeout' | 'network' | 'unknown';

export interface ApiErrorDetails {
  message: string;
  url: string;
  attempt: number;
  status?: number;
  statusText?: string;
  body?: unknown;
  cause?: unknown;
  kind: ApiErrorKind;
}

export class BookandoApiError extends Error {
  readonly url: string;
  readonly attempt: number;
  readonly status?: number;
  readonly statusText?: string;
  readonly body?: unknown;
  readonly cause?: unknown;
  readonly kind: ApiErrorKind;

  constructor(details: ApiErrorDetails) {
    super(details.message);
    this.name = 'BookandoApiError';
    this.url = details.url;
    this.attempt = details.attempt;
    this.status = details.status;
    this.statusText = details.statusText;
    this.body = details.body;
    this.cause = details.cause;
    this.kind = details.kind;
  }

  toJSON() {
    const { name, message, url, attempt, status, statusText, body, kind } = this;
    return { name, message, url, attempt, status, statusText, body, kind };
  }
}

export type RetryDelay = number | ((attempt: number) => number);

export interface ApiRequestOptions<TBody = unknown> {
  params?: Record<string, unknown>;
  headers?: HeadersInit;
  timeout?: number;
  retries?: number;
  retryDelay?: RetryDelay;
  signal?: AbortSignal;
  credentials?: RequestCredentials;
  data?: TBody;
}

export interface RequestOptions<TBody = unknown> extends ApiRequestOptions<TBody> {
  method?: string;
}

export interface ApiClientConfig {
  baseUrl: string;
  auth?: {
    token?: string;
    nonce?: string;
    apiKey?: string;
  };
  tenantId?: number;
  defaultTimeout?: number;
  defaultRetries?: number;
  onError?: (error: BookandoApiError) => void;
}

function isBodyInit(value: unknown): value is BodyInit {
  return value instanceof Blob || value instanceof FormData || value instanceof URLSearchParams || typeof value === 'string';
}

function normalizeBody(data: unknown, method: string, headers: Headers) {
  if (method === 'GET' || data === undefined || data === null) return undefined;
  if (isBodyInit(data)) return data;
  if (typeof data === 'object') {
    if (!headers.has('Content-Type')) headers.set('Content-Type', 'application/json');
    return JSON.stringify(data);
  }
  return String(data);
}

function appendParams(url: string, params?: Record<string, unknown>) {
  if (!params) return url;
  const searchParams = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null) continue;
    const values = Array.isArray(value) ? value : [value];
    for (const item of values) {
      searchParams.append(key, String(item));
    }
  }
  const query = searchParams.toString();
  if (!query) return url;
  return url.includes('?') ? `${url}&${query}` : `${url}?${query}`;
}

function mergeSignals(signal: AbortSignal | undefined, controller: AbortController) {
  if (!signal) return;
  if (signal.aborted) {
    controller.abort(signal.reason);
    return;
  }
  const abort = () => controller.abort(signal.reason);
  signal.addEventListener('abort', abort, { once: true });
  controller.signal.addEventListener('abort', () => signal.removeEventListener('abort', abort), { once: true });
}

async function delay(ms: number) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function createApiError(kind: ApiErrorKind, message: string, url: string, attempt: number, options: Partial<ApiErrorDetails>) {
  return new BookandoApiError({
    kind,
    message,
    url,
    attempt,
    status: options.status,
    statusText: options.statusText,
    body: options.body,
    cause: options.cause,
  });
}

async function parseBody<T>(response: Response): Promise<T> {
  if (response.status === 204) return undefined as T;
  const contentType = response.headers.get('content-type') || '';
  if (contentType.includes('application/json')) {
    return await response.json() as T;
  }
  const text = await response.text();
  return text as unknown as T;
}

export class BookandoApiClient {
  private config: ApiClientConfig;

  constructor(config: ApiClientConfig) {
    this.config = {
      defaultTimeout: DEFAULT_TIMEOUT,
      defaultRetries: DEFAULT_RETRIES,
      ...config,
    };
  }

  private buildUrl(endpoint: string, params?: Record<string, unknown>): string {
    const needsBase = !/^https?:\/\//i.test(endpoint);
    const base = this.config.baseUrl.replace(/\/+$/, '');
    let url = endpoint;

    if (needsBase) {
      if (endpoint.startsWith('/')) {
        url = `${base}${endpoint}`;
      } else {
        url = `${base}/${endpoint}`;
      }
    }

    // Auto-inject tenant_id if configured
    const finalParams = {
      ...params,
      ...(this.config.tenantId ? { tenant_id: this.config.tenantId } : {}),
    };

    return appendParams(url, finalParams);
  }

  private getHeaders(additional?: HeadersInit): Headers {
    const headers = new Headers(additional);

    // WordPress nonce
    if (this.config.auth?.nonce && !headers.has('X-WP-Nonce')) {
      headers.set('X-WP-Nonce', this.config.auth.nonce);
    }

    // JWT Token
    if (this.config.auth?.token && !headers.has('Authorization')) {
      headers.set('Authorization', `Bearer ${this.config.auth.token}`);
    }

    // API Key
    if (this.config.auth?.apiKey && !headers.has('X-API-Key')) {
      headers.set('X-API-Key', this.config.auth.apiKey);
    }

    return headers;
  }

  async request<T = unknown>(endpoint: string, options: RequestOptions = {}): Promise<T> {
    const {
      method = 'GET',
      params,
      headers: incomingHeaders,
      timeout = this.config.defaultTimeout,
      retries = this.config.defaultRetries,
      retryDelay = DEFAULT_RETRY_DELAY,
      signal,
      credentials = 'same-origin',
      data,
    } = options;

    const headers = this.getHeaders(incomingHeaders);
    const url = this.buildUrl(endpoint, params);
    let attempt = 0;
    let lastError: BookandoApiError | undefined;

    while (attempt <= (retries || 0)) {
      const controller = new AbortController();
      mergeSignals(signal, controller);
      const timer = setTimeout(() => controller.abort(new DOMException('Timeout', 'AbortError')), timeout || DEFAULT_TIMEOUT);

      try {
        const response = await fetch(url, {
          method,
          headers,
          credentials,
          body: normalizeBody(data, method, headers),
          signal: controller.signal,
        });

        clearTimeout(timer);

        if (!response.ok) {
          const body = await parseBody(response).catch(() => undefined);
          throw createApiError('http', `Request failed with status ${response.status}`, url, attempt, {
            status: response.status,
            statusText: response.statusText,
            body,
          });
        }

        return await parseBody<T>(response);
      } catch (error) {
        clearTimeout(timer);
        const isAbort = error instanceof DOMException && error.name === 'AbortError';
        const kind: ApiErrorKind = isAbort ? 'timeout' : error instanceof BookandoApiError ? error.kind : 'network';
        const normalized = error instanceof BookandoApiError
          ? error
          : createApiError(kind, error instanceof Error ? error.message : 'Unknown error', url, attempt, { cause: error });

        lastError = normalized;

        if (attempt >= (retries || 0)) {
          if (this.config.onError) {
            this.config.onError(normalized);
          }
          throw normalized;
        }

        const delayMs = typeof retryDelay === 'function' ? retryDelay(attempt + 1) : retryDelay;
        if (delayMs > 0) await delay(delayMs);
        attempt += 1;
      }
    }

    if (lastError) throw lastError;
    throw createApiError('unknown', 'Unknown error', url, attempt, {});
  }

  // Convenience methods
  get<T = unknown>(endpoint: string, params?: Record<string, unknown>, options?: ApiRequestOptions): Promise<T> {
    const mergedParams = { ...(options?.params ?? {}), ...(params ?? {}) };
    const nextOptions = { ...options, params: Object.keys(mergedParams).length ? mergedParams : undefined };
    return this.request<T>(endpoint, { ...nextOptions, method: 'GET' });
  }

  post<T = unknown>(endpoint: string, data?: unknown, options?: ApiRequestOptions): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: 'POST', data });
  }

  put<T = unknown>(endpoint: string, data?: unknown, options?: ApiRequestOptions): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: 'PUT', data });
  }

  patch<T = unknown>(endpoint: string, data?: unknown, options?: ApiRequestOptions): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: 'PATCH', data });
  }

  delete<T = unknown>(endpoint: string, options?: ApiRequestOptions): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: 'DELETE' });
  }

  // Update tenant ID at runtime
  setTenantId(tenantId: number) {
    this.config.tenantId = tenantId;
  }

  // Update auth at runtime
  setAuth(auth: ApiClientConfig['auth']) {
    this.config.auth = { ...this.config.auth, ...auth };
  }
}
