/**
 * API Client - Zentrale HTTP-Client Konfiguration
 *
 * Dieser Service stellt einen konfigurierten HTTP-Client bereit
 * mit automatischem Token-Management und Fehlerbehandlung.
 */

// Safe access to environment variables
const getApiBaseUrl = (): string => {
  try {
    if (typeof import.meta !== 'undefined' && import.meta.env?.VITE_API_URL) {
      return import.meta.env.VITE_API_URL;
    }
  } catch (e) {
    console.warn('import.meta.env not available, using default API URL');
  }
  return 'http://localhost:3001/api';
};

const API_BASE_URL = getApiBaseUrl();

interface RequestConfig extends RequestInit {
  params?: Record<string, string | number | boolean>;
}

class ApiClient {
  private baseURL: string;
  private defaultHeaders: HeadersInit;

  constructor(baseURL: string) {
    this.baseURL = baseURL;
    this.defaultHeaders = {
      'Content-Type': 'application/json',
    };
  }

  /**
   * Setzt den Auth-Token für alle folgenden Requests
   */
  setAuthToken(token: string | null) {
    if (token) {
      this.defaultHeaders = {
        ...this.defaultHeaders,
        'Authorization': `Bearer ${token}`,
      };
    } else {
      const { Authorization, ...rest } = this.defaultHeaders as any;
      this.defaultHeaders = rest;
    }
  }

  /**
   * Setzt die Organization ID für Multi-Tenancy
   */
  setOrganizationId(orgId: string | null) {
    if (orgId) {
      this.defaultHeaders = {
        ...this.defaultHeaders,
        'x-organization-id': orgId,
      };
    } else {
      const { 'x-organization-id': removed, ...rest } = this.defaultHeaders as any;
      this.defaultHeaders = rest;
    }
  }

  /**
   * Baut die vollständige URL mit Query-Parametern
   */
  private buildUrl(endpoint: string, params?: Record<string, any>): string {
    const url = new URL(`${this.baseURL}${endpoint}`);

    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          url.searchParams.append(key, String(value));
        }
      });
    }

    return url.toString();
  }

  /**
   * Behandelt API-Fehler und wirft strukturierte Fehler
   */
  private async handleResponse<T>(response: Response): Promise<T> {
    if (!response.ok) {
      let errorMessage = `HTTP ${response.status}: ${response.statusText}`;

      try {
        const errorData = await response.json();
        errorMessage = errorData.error || errorData.message || errorMessage;
      } catch {
        // Fehler beim Parsen der Error-Response, nutze Standard-Message
      }

      // Bei 401: Token ungültig, User ausloggen
      if (response.status === 401) {
        try {
          if (typeof localStorage !== 'undefined') {
            localStorage.removeItem('authToken');
            localStorage.removeItem('refreshToken');
          }
          if (typeof window !== 'undefined') {
            window.location.href = '/login';
          }
        } catch (e) {
          console.error('Error handling 401 logout:', e);
        }
      }

      throw new Error(errorMessage);
    }

    // Leere Responses (204 No Content)
    if (response.status === 204) {
      return {} as T;
    }

    return response.json();
  }

  /**
   * GET Request
   */
  async get<T>(endpoint: string, config?: RequestConfig): Promise<T> {
    const url = this.buildUrl(endpoint, config?.params);

    const response = await fetch(url, {
      method: 'GET',
      headers: { ...this.defaultHeaders, ...config?.headers },
      ...config,
    });

    return this.handleResponse<T>(response);
  }

  /**
   * POST Request
   */
  async post<T>(endpoint: string, data?: any, config?: RequestConfig): Promise<T> {
    const url = this.buildUrl(endpoint, config?.params);

    const response = await fetch(url, {
      method: 'POST',
      headers: { ...this.defaultHeaders, ...config?.headers },
      body: data ? JSON.stringify(data) : undefined,
      ...config,
    });

    return this.handleResponse<T>(response);
  }

  /**
   * PUT Request
   */
  async put<T>(endpoint: string, data?: any, config?: RequestConfig): Promise<T> {
    const url = this.buildUrl(endpoint, config?.params);

    const response = await fetch(url, {
      method: 'PUT',
      headers: { ...this.defaultHeaders, ...config?.headers },
      body: data ? JSON.stringify(data) : undefined,
      ...config,
    });

    return this.handleResponse<T>(response);
  }

  /**
   * PATCH Request
   */
  async patch<T>(endpoint: string, data?: any, config?: RequestConfig): Promise<T> {
    const url = this.buildUrl(endpoint, config?.params);

    const response = await fetch(url, {
      method: 'PATCH',
      headers: { ...this.defaultHeaders, ...config?.headers },
      body: data ? JSON.stringify(data) : undefined,
      ...config,
    });

    return this.handleResponse<T>(response);
  }

  /**
   * DELETE Request
   */
  async delete<T>(endpoint: string, config?: RequestConfig): Promise<T> {
    const url = this.buildUrl(endpoint, config?.params);

    const response = await fetch(url, {
      method: 'DELETE',
      headers: { ...this.defaultHeaders, ...config?.headers },
      ...config,
    });

    return this.handleResponse<T>(response);
  }
}

// Singleton Instance
export const apiClient = new ApiClient(API_BASE_URL);

// Initialisiere Token aus localStorage beim Start (safe access)
try {
  if (typeof localStorage !== 'undefined') {
    const token = localStorage.getItem('authToken');
    const orgId = localStorage.getItem('organizationId');

    if (token) {
      apiClient.setAuthToken(token);
    }
    if (orgId) {
      apiClient.setOrganizationId(orgId);
    }
  }
} catch (e) {
  console.warn('localStorage not available, skipping token initialization');
}

export default apiClient;
