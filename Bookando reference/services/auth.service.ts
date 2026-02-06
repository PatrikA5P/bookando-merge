/**
 * Authentication Service
 *
 * Verwaltet Login, Logout, Token-Refresh und User-Session
 */

import apiClient from './api';

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  refreshToken: string;
  user: {
    id: string;
    email: string;
    firstName: string;
    lastName: string;
    organizationId: string;
    role: {
      id: string;
      name: string;
      permissions: any;
    };
  };
  organization: {
    id: string;
    name: string;
    subdomain: string;
  };
}

export interface RegisterData {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
  organizationName: string;
  subdomain?: string;
}

export interface RefreshTokenResponse {
  token: string;
  refreshToken: string;
}

class AuthService {
  /**
   * Login mit Email und Passwort
   */
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/login', credentials);

    // Speichere Tokens und Organization
    localStorage.setItem('authToken', response.token);
    localStorage.setItem('refreshToken', response.refreshToken);
    localStorage.setItem('organizationId', response.user.organizationId);
    localStorage.setItem('user', JSON.stringify(response.user));

    // Setze Token im API Client
    apiClient.setAuthToken(response.token);
    apiClient.setOrganizationId(response.user.organizationId);

    return response;
  }

  /**
   * Registrierung einer neuen Organisation
   */
  async register(data: RegisterData): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/register', data);

    // Speichere Tokens und Organization
    localStorage.setItem('authToken', response.token);
    localStorage.setItem('refreshToken', response.refreshToken);
    localStorage.setItem('organizationId', response.user.organizationId);
    localStorage.setItem('user', JSON.stringify(response.user));

    // Setze Token im API Client
    apiClient.setAuthToken(response.token);
    apiClient.setOrganizationId(response.user.organizationId);

    return response;
  }

  /**
   * Logout - Entfernt alle lokalen Daten
   */
  async logout(): Promise<void> {
    try {
      // Optional: Backend informieren (wenn Endpoint vorhanden)
      await apiClient.post('/auth/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      // Lokale Daten löschen
      localStorage.removeItem('authToken');
      localStorage.removeItem('refreshToken');
      localStorage.removeItem('organizationId');
      localStorage.removeItem('user');

      // Token aus API Client entfernen
      apiClient.setAuthToken(null);
      apiClient.setOrganizationId(null);
    }
  }

  /**
   * Token erneuern mit Refresh Token
   */
  async refreshToken(): Promise<RefreshTokenResponse> {
    const refreshToken = localStorage.getItem('refreshToken');

    if (!refreshToken) {
      throw new Error('No refresh token available');
    }

    const response = await apiClient.post<RefreshTokenResponse>('/auth/refresh', {
      refreshToken,
    });

    // Update Tokens
    localStorage.setItem('authToken', response.token);
    localStorage.setItem('refreshToken', response.refreshToken);
    apiClient.setAuthToken(response.token);

    return response;
  }

  /**
   * Prüft ob User eingeloggt ist
   */
  isAuthenticated(): boolean {
    const token = localStorage.getItem('authToken');
    return !!token;
  }

  /**
   * Gibt den aktuellen User zurück
   */
  getCurrentUser(): LoginResponse['user'] | null {
    const userStr = localStorage.getItem('user');
    if (!userStr) return null;

    try {
      return JSON.parse(userStr);
    } catch {
      return null;
    }
  }

  /**
   * Gibt die aktuelle Organization ID zurück
   */
  getOrganizationId(): string | null {
    return localStorage.getItem('organizationId');
  }

  /**
   * Passwort zurücksetzen (Request)
   */
  async requestPasswordReset(email: string): Promise<void> {
    await apiClient.post('/auth/password-reset/request', { email });
  }

  /**
   * Passwort zurücksetzen (mit Token)
   */
  async resetPassword(token: string, newPassword: string): Promise<void> {
    await apiClient.post('/auth/password-reset/confirm', {
      token,
      newPassword,
    });
  }
}

export const authService = new AuthService();
export default authService;
