<script setup lang="ts">
/**
 * Login-Seite — Authentifizierung
 *
 * Einfaches Login-Formular mit E-Mail und Passwort.
 * TODO: OAuth-Provider (Google, Apple), 2FA, Passwort-Reset
 */
import { ref } from 'vue';

const email = ref('');
const password = ref('');
const isLoading = ref(false);
const errorMessage = ref('');

async function handleLogin() {
  errorMessage.value = '';
  if (!email.value || !password.value) {
    errorMessage.value = 'Bitte E-Mail und Passwort eingeben.';
    return;
  }
  isLoading.value = true;
  try {
    // TODO: API-Call an AuthApi.login()
    console.log('Login attempt:', email.value);
  } catch (err) {
    errorMessage.value = 'Anmeldung fehlgeschlagen. Bitte versuchen Sie es erneut.';
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-600 rounded-2xl mb-4">
          <span class="text-white font-bold text-xl">B</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Willkommen bei Bookando</h1>
        <p class="text-sm text-slate-500 mt-1">Melden Sie sich an, um fortzufahren</p>
      </div>

      <!-- Login-Formular -->
      <form
        class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8 space-y-5"
        @submit.prevent="handleLogin"
      >
        <!-- Fehlermeldung -->
        <div
          v-if="errorMessage"
          class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3"
          role="alert"
        >
          {{ errorMessage }}
        </div>

        <!-- E-Mail -->
        <div>
          <label for="login-email" class="block text-sm font-medium text-slate-700 mb-1.5">
            E-Mail-Adresse
          </label>
          <input
            id="login-email"
            v-model="email"
            type="email"
            autocomplete="email"
            required
            placeholder="name@beispiel.ch"
            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
          />
        </div>

        <!-- Passwort -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label for="login-password" class="block text-sm font-medium text-slate-700">
              Passwort
            </label>
            <a href="#" class="text-xs text-brand-600 hover:text-brand-700 font-medium">
              Passwort vergessen?
            </a>
          </div>
          <input
            id="login-password"
            v-model="password"
            type="password"
            autocomplete="current-password"
            required
            placeholder="••••••••"
            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
          />
        </div>

        <!-- Anmelden-Button -->
        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-2.5 px-4 bg-brand-600 hover:bg-brand-700 disabled:bg-brand-400 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
        >
          <span v-if="isLoading" class="inline-flex items-center gap-2">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Wird angemeldet...
          </span>
          <span v-else>Anmelden</span>
        </button>
      </form>

      <!-- Footer -->
      <p class="text-center text-xs text-slate-400 mt-6">
        &copy; {{ new Date().getFullYear() }} Bookando. Alle Rechte vorbehalten.
      </p>
    </div>
  </div>
</template>
