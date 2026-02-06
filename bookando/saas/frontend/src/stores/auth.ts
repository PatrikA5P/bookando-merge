/**
 * Auth Store — Authentifizierung & Session
 *
 * Pinia Store fuer:
 * - Login/Logout
 * - JWT Token Management
 * - Session Refresh
 * - User Profile
 *
 * Verbesserung gegenueber Referenz:
 * - Pinia statt monolithischem Context
 * - Automatisches Token-Refresh
 * - Sicheres Token-Handling (kein localStorage fuer Access-Token)
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';

export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  role: string;
  avatar?: string;
  tenantId: number;
  organizationName: string;
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const accessToken = ref<string | null>(null);
  const isLoading = ref(false);

  const isAuthenticated = computed(() => !!accessToken.value && !!user.value);
  const fullName = computed(() => user.value ? `${user.value.firstName} ${user.value.lastName}` : '');
  const initials = computed(() => {
    if (!user.value) return '';
    return `${user.value.firstName[0]}${user.value.lastName[0]}`.toUpperCase();
  });

  async function login(email: string, password: string): Promise<void> {
    isLoading.value = true;
    try {
      const response = await api.post<{ data: { accessToken: string; user: User } }>('/v1/auth/login', { email, password });
      accessToken.value = response.data.accessToken;
      user.value = response.data.user;
      // Configure API client with token
      api.configure({
        getAccessToken: () => accessToken.value,
        getTenantId: () => user.value?.tenantId ?? null,
        onUnauthorized: () => logout(),
      });
    } finally {
      isLoading.value = false;
    }
  }

  async function logout(): Promise<void> {
    try {
      await api.post('/v1/auth/logout');
    } catch { /* ignore */ }
    accessToken.value = null;
    user.value = null;
  }

  async function refreshSession(): Promise<void> {
    try {
      const response = await api.get<{ data: User }>('/v1/auth/me');
      user.value = response.data;
    } catch {
      await logout();
    }
  }

  function setAuth(token: string, userData: User) {
    accessToken.value = token;
    user.value = userData;
  }

  /**
   * Initializes the API client configuration on app start.
   * Call this in the app's main setup if a token is already present (e.g., from persistence).
   */
  function initAuth() {
    if (accessToken.value) {
      api.configure({
        getAccessToken: () => accessToken.value,
        getTenantId: () => user.value?.tenantId ?? null,
        onUnauthorized: () => logout(),
      });
    }
  }

  return {
    user,
    accessToken,
    isLoading,
    isAuthenticated,
    fullName,
    initials,
    login,
    logout,
    refreshSession,
    setAuth,
    initAuth,
  };
});
