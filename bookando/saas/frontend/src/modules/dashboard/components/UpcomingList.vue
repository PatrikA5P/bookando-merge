<script setup lang="ts">
/**
 * UpcomingList — Kommende Termine Widget
 */
import { CARD_STYLES, BADGE_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';

defineProps<{
  title?: string;
}>();

const { t } = useI18n();

// Mock-Daten (TODO: API)
const upcoming = [
  { id: '1', title: 'Yoga Kurs', customer: 'Anna Müller', time: '14:00 – 15:00', employee: 'Lisa B.', status: 'confirmed' },
  { id: '2', title: 'Massage Klassisch', customer: 'Peter Schmidt', time: '15:30 – 16:30', employee: 'Marco R.', status: 'confirmed' },
  { id: '3', title: 'Personal Training', customer: 'Sarah Weber', time: '16:00 – 17:00', employee: 'Tom K.', status: 'pending' },
  { id: '4', title: 'Pilates Intro', customer: 'Julia Keller', time: '17:30 – 18:30', employee: 'Lisa B.', status: 'confirmed' },
];
</script>

<template>
  <div :class="CARD_STYLES.base">
    <div :class="CARD_STYLES.headerCompact">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-slate-900">{{ title || t('dashboard.upcomingAppointments') }}</h3>
        <span class="text-xs text-slate-500">{{ t('dashboard.today') }}</span>
      </div>
    </div>
    <div class="divide-y divide-slate-100">
      <div
        v-for="item in upcoming"
        :key="item.id"
        class="px-4 py-3 flex items-center justify-between hover:bg-slate-50 transition-colors cursor-pointer"
      >
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
          <p class="text-xs text-slate-500 mt-0.5">{{ item.customer }} · {{ item.employee }}</p>
        </div>
        <div class="text-right shrink-0 ml-4">
          <p class="text-sm font-medium text-slate-700">{{ item.time }}</p>
          <span
            :class="item.status === 'confirmed' ? BADGE_STYLES.success : BADGE_STYLES.warning"
            class="mt-1 inline-block"
          >
            {{ item.status === 'confirmed' ? t('dashboard.confirmed') : t('dashboard.pendingStatus') }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
