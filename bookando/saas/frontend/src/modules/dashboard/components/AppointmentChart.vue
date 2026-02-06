<script setup lang="ts">
/**
 * AppointmentChart — Termin-Diagramm Widget
 */
import { CARD_STYLES } from '@/design';

defineProps<{
  dateRange: string;
}>();
</script>

<template>
  <div :class="CARD_STYLES.base">
    <div :class="CARD_STYLES.header">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-slate-900">Termine</h3>
        <span class="text-sm text-slate-500">{{ dateRange }}</span>
      </div>
    </div>
    <div :class="CARD_STYLES.body" class="h-64 flex items-center justify-center">
      <div class="text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <p class="text-sm">Termin-Chart</p>
        <p class="text-xs mt-1">Wird mit API-Daten geladen</p>
      </div>
    </div>
  </div>
</template>
