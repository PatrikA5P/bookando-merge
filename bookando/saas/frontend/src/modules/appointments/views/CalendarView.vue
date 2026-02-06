<script setup lang="ts">
/**
 * Kalenderansicht — Termine
 *
 * Platzhalter für die Kalenderansicht mit Tages-, Wochen- und Monatsansicht.
 * TODO: FullCalendar-Integration, Drag & Drop, Termin-Erstellung per Klick
 */
import { ref } from 'vue';

const viewMode = ref<'day' | 'week' | 'month'>('week');
const currentDate = ref(new Date());

const viewLabels: Record<string, string> = {
  day: 'Tag',
  week: 'Woche',
  month: 'Monat',
};

function formatDate(date: Date): string {
  return date.toLocaleDateString('de-CH', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}
</script>

<template>
  <div>
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
      <div class="flex items-center gap-2">
        <button
          class="p-2 rounded-lg hover:bg-slate-100 transition-colors"
          @click="currentDate = new Date(currentDate.getTime() - 7 * 86400000)"
        >
          <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h2 class="text-sm font-medium text-slate-900">{{ formatDate(currentDate) }}</h2>
        <button
          class="p-2 rounded-lg hover:bg-slate-100 transition-colors"
          @click="currentDate = new Date(currentDate.getTime() + 7 * 86400000)"
        >
          <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        <button
          class="ml-2 px-3 py-1.5 text-xs font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors"
          @click="currentDate = new Date()"
        >
          Heute
        </button>
      </div>

      <!-- View Mode Switcher -->
      <div class="flex bg-slate-100 rounded-lg p-0.5">
        <button
          v-for="mode in (['day', 'week', 'month'] as const)"
          :key="mode"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md transition-all',
            viewMode === mode
              ? 'bg-white text-slate-900 shadow-sm'
              : 'text-slate-500 hover:text-slate-700',
          ]"
          @click="viewMode = mode"
        >
          {{ viewLabels[mode] }}
        </button>
      </div>
    </div>

    <!-- Kalender-Platzhalter -->
    <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[400px] flex items-center justify-center">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto bg-brand-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900">Kalenderansicht — {{ viewLabels[viewMode] }}</h3>
        <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
        <p class="text-xs text-slate-400 mt-2">
          FullCalendar-Integration mit Drag &amp; Drop und Terminerstellung per Klick
        </p>
      </div>
    </div>
  </div>
</template>
