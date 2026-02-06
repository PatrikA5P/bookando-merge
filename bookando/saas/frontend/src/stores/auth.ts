/**
 * Auth Store — Authentifizierung & Session
 *
 * Pinia Store fuer:
 * - Cookie-based Sanctum SPA auth (primary)
 * - Bootstrap: checks existing session on app startup
 * - Login/Logout
 * - Bearer token fallback for API testing
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
  const isLoading = ref(false);
  const isReady = ref(false); // true after initial bootstrap attempt

  const isAuthenticated = computed(() => !!user.value);
  const fullName = computed(() => user.value ? `${user.value.firstName} ${user.value.lastName}` : '');
  const initials = computed(() => {
    if (!user.value) return '';
    return `${user.value.firstName?.[0] || ''}${user.value.lastName?.[0] || ''}`.toUpperCase();
  });

  // Called on app startup - checks if user has active session
  async function bootstrap(): Promise<void> {
    try {
      const response = await api.get<{ data: User }>('/v1/auth/me');
      user.value = response.data;
    } catch {
      user.value = null;
    } finally {
      isReady.value = true;
    }
  }

  async function login(email: string, password: string): Promise<void> {
    isLoading.value = true;
    try {
      const response = await api.post<{ data: { accessToken: string; user: User } }>('/v1/auth/login', { email, password });
      user.value = response.data.user;
      // Configure api client with token as fallback
      api.configure({
        getAccessToken: () => response.data.accessToken,
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
    } catch {
      // Ignore logout errors
    }
    user.value = null;
  }

  async function refreshSession(): Promise<void> {
    try {
      const response = await api.get<{ data: User }>('/v1/auth/me');
      user.value = response.data;
    } catch {
      user.value = null;
    }
  }

  return {
    user,
    isLoading,
    isReady,
    isAuthenticated,
    fullName,
    initials,
    bootstrap,
    login,
    logout,
    refreshSession,
  };
});
