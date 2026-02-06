/**
 * Auth Store — Authentifizierung & Session
 *
 * Pinia Store für:
 * - Login/Logout
 * - JWT Token Management
 * - Session Refresh
 * - User Profile
 *
 * Verbesserung gegenüber Referenz:
 * - Pinia statt monolithischem Context
 * - Automatisches Token-Refresh
 * - Sicheres Token-Handling (kein localStorage für Access-Token)
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

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
      // TODO: API-Call implementieren
      // const response = await api.post('/auth/login', { email, password });
      // accessToken.value = response.data.accessToken;
      // user.value = response.data.user;
      throw new Error('Not implemented');
    } finally {
      isLoading.value = false;
    }
  }

  async function logout(): Promise<void> {
    // TODO: API-Call zum Invalidieren des Refresh-Tokens
    accessToken.value = null;
    user.value = null;
  }

  async function refreshSession(): Promise<void> {
    try {
      // TODO: Refresh via httpOnly Cookie
      // const response = await api.post('/auth/refresh');
      // accessToken.value = response.data.accessToken;
      // user.value = response.data.user;
    } catch {
      await logout();
    }
  }

  function setAuth(token: string, userData: User) {
    accessToken.value = token;
    user.value = userData;
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
  };
});
