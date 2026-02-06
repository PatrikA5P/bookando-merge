<script setup lang="ts">
/**
 * ActivityFeed — Letzte Aktivitäten Widget
 */
import { CARD_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';

defineProps<{
  title?: string;
}>();

const { t } = useI18n();

// Mock-Daten (TODO: API)
const activities = [
  { id: '1', text: 'Neuer Kunde registriert: Max Muster', time: 'Vor 5 Min.', type: 'customer' },
  { id: '2', text: 'Termin bestätigt: Yoga Kurs, 14:00', time: 'Vor 12 Min.', type: 'appointment' },
  { id: '3', text: 'Rechnung #INV-2026-00042 bezahlt', time: 'Vor 25 Min.', type: 'finance' },
  { id: '4', text: 'Mitarbeiter Anna hat eingecheckt', time: 'Vor 1 Std.', type: 'workday' },
  { id: '5', text: 'Neuer Kurs "Pilates Basis" erstellt', time: 'Vor 2 Std.', type: 'academy' },
];

const typeColors: Record<string, string> = {
  customer: 'bg-emerald-100 text-emerald-600',
  appointment: 'bg-brand-100 text-brand-600',
  finance: 'bg-purple-100 text-purple-600',
  workday: 'bg-amber-100 text-amber-600',
  academy: 'bg-rose-100 text-rose-600',
};
</script>

<template>
  <div :class="CARD_STYLES.base">
    <div :class="CARD_STYLES.headerCompact">
      <h3 class="text-base font-semibold text-slate-900">{{ title || t('dashboard.recentActivity') }}</h3>
    </div>
    <div class="divide-y divide-slate-100">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="px-4 py-3 flex items-start gap-3 hover:bg-slate-50 transition-colors"
      >
        <div :class="['w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5', typeColors[activity.type] || 'bg-slate-100 text-slate-600']">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm text-slate-700">{{ activity.text }}</p>
          <p class="text-xs text-slate-400 mt-0.5">{{ activity.time }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
