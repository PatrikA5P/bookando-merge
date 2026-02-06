<script setup lang="ts">
/**
 * Bookando — Haupt-Applikationskomponente
 *
 * Initialisiert:
 * - Vue Router
 * - Pinia Stores
 * - Toast-Container
 * - Global Error Boundary
 */
import { useToast } from '@/composables/useToast';
import { TOAST_STYLES } from '@/design';

const { toasts, remove } = useToast();
</script>

<template>
  <router-view />

  <!-- Toast Container -->
  <Teleport to="body">
    <div :class="TOAST_STYLES.container">
      <TransitionGroup
        enter-active-class="duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-4 scale-95"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[TOAST_STYLES.base, TOAST_STYLES[toast.type]]"
        >
          <!-- Icon -->
          <svg v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else-if="toast.type === 'error'" class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <svg v-else-if="toast.type === 'warning'" class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <svg v-else class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>

          <!-- Message -->
          <p class="text-sm font-medium flex-1">{{ toast.message }}</p>

          <!-- Action -->
          <button
            v-if="toast.action"
            class="text-sm font-bold underline shrink-0"
            @click="toast.action.onClick(); remove(toast.id)"
          >
            {{ toast.action.label }}
          </button>

          <!-- Close -->
          <button
            class="p-1 rounded hover:bg-black/5 shrink-0"
            aria-label="Schliessen"
            @click="remove(toast.id)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style>
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';

/* Scrollbar-Styling */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Smooth Scroll */
html {
  scroll-behavior: smooth;
}

/* Body Base */
body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
