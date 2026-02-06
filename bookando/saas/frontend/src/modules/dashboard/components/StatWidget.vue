<script setup lang="ts">
/**
 * StatWidget — KPI-Karte
 *
 * Zeigt eine Kennzahl mit Trend-Indikator.
 */
import { CARD_STYLES } from '@/design';

defineProps<{
  label: string;
  value: string;
  trend: number;
  icon: string;
}>();
</script>

<template>
  <div :class="[CARD_STYLES.stat, 'group']">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-sm font-medium text-slate-500">{{ label }}</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ value }}</p>
      </div>
      <div class="p-2.5 rounded-xl bg-brand-50 text-brand-600 group-hover:bg-brand-100 transition-colors">
        <svg v-if="icon === 'banknote'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <svg v-else-if="icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <svg v-else-if="icon === 'calendar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
    </div>

    <!-- Trend -->
    <div class="mt-3 flex items-center gap-1">
      <span
        :class="[
          'text-sm font-medium',
          trend >= 0 ? 'text-emerald-600' : 'text-red-600',
        ]"
      >
        <svg
          class="w-3.5 h-3.5 inline"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            v-if="trend >= 0"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M7 17l9.2-9.2M17 17V7H7"
          />
          <path
            v-else
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 7l-9.2 9.2M7 7v10h10"
          />
        </svg>
        {{ Math.abs(trend) }}%
      </span>
      <span class="text-xs text-slate-400">vs. Vormonat</span>
    </div>
  </div>
</template>
