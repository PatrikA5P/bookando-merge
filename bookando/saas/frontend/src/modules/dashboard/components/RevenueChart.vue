<script setup lang="ts">
/**
 * RevenueChart — Umsatz-Diagramm Widget
 */
import { CARD_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';

defineProps<{
  dateRange: string;
  title?: string;
}>();

const { t } = useI18n();

// TODO: ApexCharts Integration mit echten Daten
</script>

<template>
  <div :class="CARD_STYLES.base">
    <div :class="CARD_STYLES.header">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-slate-900">{{ title || t('dashboard.revenue') }}</h3>
        <span class="text-sm text-slate-500">{{ dateRange }}</span>
      </div>
    </div>
    <div :class="CARD_STYLES.body" class="h-64 flex items-center justify-center">
      <!-- Placeholder für ApexCharts -->
      <div class="text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
        </svg>
        <p class="text-sm">{{ t('dashboard.revenueChart') }}</p>
        <p class="text-xs mt-1">{{ t('dashboard.chartPlaceholder') }}</p>
      </div>
    </div>
  </div>
</template>
