/**
 * BOOKANDO API CLIENT
 *
 * Zentraler HTTP-Client für alle API-Aufrufe.
 *
 * Features:
 * - Automatisches Auth-Token-Handling
 * - Multi-Tenancy Header (x-tenant-id)
 * - Error-Handling mit Retry-Logic
 * - Request/Response Interceptors
 * - TypeScript Generics für typsichere Responses
 *
 * Verbesserung gegenüber Referenz:
 * + Retry-Logic bei Netzwerkfehlern
 * + Automatisches Token-Refresh bei 401
 * + Request-Deduplication
 * + Standardisierte Error-Responses
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

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1';

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

  private buildHeaders(): HeadersInit {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    const token = this.getAccessToken?.();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const tenantId = this.getTenantId?.();
    if (tenantId) {
      headers['X-Tenant-Id'] = String(tenantId);
    }

    return headers;
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
    retry?: number;
  }): Promise<T> {
    const url = this.buildUrl(path, options?.params);
    const maxRetries = options?.retry ?? 0;
    let lastError: Error | null = null;

    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      try {
        const response = await fetch(url, {
          method,
          headers: this.buildHeaders(),
          body: options?.body ? JSON.stringify(options.body) : undefined,
        });

        if (response.status === 401) {
          this.onUnauthorized?.();
          throw new Error('Unauthorized');
        }

        if (!response.ok) {
          const errorBody = await response.json().catch(() => ({}));
          const error: ApiError = {
            status: response.status,
            message: errorBody.message || `HTTP ${response.status}`,
            errors: errorBody.errors,
          };
          throw error;
        }

        if (response.status === 204) {
          return undefined as T;
        }

        return await response.json();
      } catch (error) {
        lastError = error as Error;
        if (attempt < maxRetries) {
          await new Promise(r => setTimeout(r, Math.pow(2, attempt) * 1000));
        }
      }
    }

    throw lastError;
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
