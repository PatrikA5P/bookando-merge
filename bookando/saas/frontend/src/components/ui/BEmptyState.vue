<script setup lang="ts">
/**
 * BEmptyState — Einheitlicher Leer-Zustand
 *
 * Aus IMPROVEMENTS.md: Leere Listen ohne Erklärung → jetzt mit
 * Illustration, Titel, Beschreibung und optionaler Aktion.
 */
import { EMPTY_STATE_STYLES, BUTTON_STYLES } from '@/design';

defineProps<{
  title: string;
  description?: string;
  icon?: 'inbox' | 'search' | 'users' | 'calendar' | 'folder' | 'chart';
  actionLabel?: string;
}>();

const emit = defineEmits<{
  (e: 'action'): void;
}>();
</script>

<template>
  <div :class="EMPTY_STATE_STYLES.container">
    <div :class="EMPTY_STATE_STYLES.icon">
      <!-- Inbox (Default) -->
      <svg v-if="!icon || icon === 'inbox'" class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
      </svg>
      <svg v-else-if="icon === 'search'" class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <svg v-else-if="icon === 'users'" class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
      <svg v-else-if="icon === 'calendar'" class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <svg v-else-if="icon === 'folder'" class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
      </svg>
      <svg v-else-if="icon === 'chart'" class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
    </div>
    <h3 :class="EMPTY_STATE_STYLES.title">{{ title }}</h3>
    <p v-if="description" :class="EMPTY_STATE_STYLES.description">{{ description }}</p>
    <div v-if="actionLabel" :class="EMPTY_STATE_STYLES.action">
      <button :class="BUTTON_STYLES.primary" @click="emit('action')">
        <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ actionLabel }}
      </button>
    </div>
  </div>
</template>
