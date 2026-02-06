/**
 * BOOKANDO API CLIENT
 *
 * Zentraler HTTP-Client für alle API-Aufrufe.
 *
 * Features:
 * - Sanctum SPA cookie-based auth (primary)
 * - Bearer token fallback (for API testing)
 * - CSRF token handling with automatic retry on 419
 * - Multi-Tenancy Header (x-tenant-id)
 * - TypeScript Generics für typsichere Responses
 */

export interface ApiError {
  status: number;
  message: string;
  errors?: Record<string, string[]>;
}

export interface ApiResponse<T> {
  data: T;
  meta?: {
    page?: number;
    perPage?: number;
    total?: number;
    totalPages?: number;
  };
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    page: number;
    perPage: number;
    total: number;
    totalPages: number;
  };
}

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

// CSRF state
let csrfInitialized = false;

function getCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'));
  return match ? decodeURIComponent(match[2]) : null;
}

async function ensureCsrf(): Promise<void> {
  if (csrfInitialized) return;
  await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
  csrfInitialized = true;
}

class ApiClient {
  private baseUrl: string;
  private getAccessToken: (() => string | null) | null = null;
  private getTenantId: (() => number | null) | null = null;
  private onUnauthorized: (() => void) | null = null;

  constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  /**
   * Konfiguriert den Client mit Auth- und Tenant-Funktionen.
   */
  configure(options: {
    getAccessToken: () => string | null;
    getTenantId: () => number | null;
    onUnauthorized: () => void;
  }) {
    this.getAccessToken = options.getAccessToken;
    this.getTenantId = options.getTenantId;
    this.onUnauthorized = options.onUnauthorized;
  }

  private buildUrl(path: string, params?: Record<string, string | number | boolean | undefined>): string {
    const url = new URL(`${this.baseUrl}${path}`, window.location.origin);

    if (params) {
      for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null) {
          url.searchParams.set(key, String(value));
        }
      }
    }

    return url.toString();
  }

  private async request<T>(method: string, path: string, options?: {
    body?: unknown;
    params?: Record<string, string | number | boolean | undefined>;
  }): Promise<T> {
    // For mutating requests, ensure CSRF cookie
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      await ensureCsrf();
    }

    const url = this.buildUrl(path, options?.params);
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };

    // Add XSRF token from cookie
    const xsrfToken = getCookie('XSRF-TOKEN');
    if (xsrfToken) {
      headers['X-XSRF-TOKEN'] = xsrfToken;
    }

    // Fallback: Bearer token if configured
    if (this.getAccessToken) {
      const token = this.getAccessToken();
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
    }

    // Tenant header
    if (this.getTenantId) {
      const tenantId = this.getTenantId();
      if (tenantId) {
        headers['X-Tenant-Id'] = String(tenantId);
      }
    }

    const fetchOptions: RequestInit = {
      method,
      headers,
      credentials: 'include', // CRITICAL for Sanctum cookies
    };

    if (options?.body !== undefined) {
      fetchOptions.body = JSON.stringify(options.body);
    }

    const response = await fetch(url, fetchOptions);

    if (response.status === 419) {
      // CSRF token expired - retry once
      csrfInitialized = false;
      await ensureCsrf();
      const retryHeaders = { ...headers };
      retryHeaders['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN') || '';
      const retryResponse = await fetch(url, { ...fetchOptions, headers: retryHeaders });
      if (!retryResponse.ok) {
        let errorData;
        try { errorData = await retryResponse.json(); } catch { errorData = {}; }
        throw {
          status: retryResponse.status,
          message: errorData.message || `HTTP ${retryResponse.status}`,
          errors: errorData.errors,
        } as ApiError;
      }
      if (retryResponse.status === 204) return undefined as T;
      return retryResponse.json();
    }

    if (response.status === 401 && this.onUnauthorized) {
      this.onUnauthorized();
      throw { status: 401, message: 'Unauthorized' } as ApiError;
    }

    if (!response.ok) {
      let errorData;
      try { errorData = await response.json(); } catch { errorData = {}; }
      throw {
        status: response.status,
        message: errorData.message || `HTTP ${response.status}`,
        errors: errorData.errors,
      } as ApiError;
    }

    if (response.status === 204) return undefined as T;
    return response.json();
  }

  async get<T>(path: string, params?: Record<string, string | number | boolean | undefined>): Promise<T> {
    return this.request<T>('GET', path, { params });
  }

  async post<T>(path: string, body?: unknown): Promise<T> {
    return this.request<T>('POST', path, { body });
  }

  async put<T>(path: string, body?: unknown): Promise<T> {
    return this.request<T>('PUT', path, { body });
  }

  async patch<T>(path: string, body?: unknown): Promise<T> {
    return this.request<T>('PATCH', path, { body });
  }

  async delete<T = void>(path: string): Promise<T> {
    return this.request<T>('DELETE', path);
  }
}

export const api = new ApiClient(API_BASE_URL);
export default api;
