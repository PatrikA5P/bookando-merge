<script setup lang="ts">
/**
 * UpcomingList -- Upcoming appointments list widget
 *
 * Displays upcoming appointments with client name, type, and time badge.
 * Matches reference ListWidget pattern for "list-upcoming".
 * TODO: Replace mock data with API call
 */
import { useI18n } from '@/composables/useI18n';

defineProps<{
  title: string;
}>();

const { t } = useI18n();

// Mock data matching reference (TODO: API)
const upcomingAppointments = [
  { id: 1, client: 'Sarah Connor', time: t('dashboard.time1400Today'), type: t('dashboard.typeConsultation') },
  { id: 2, client: 'John Wick', time: t('dashboard.time1630Today'), type: t('dashboard.typeEmergency') },
  { id: 3, client: 'Ellen Ripley', time: t('dashboard.time0900Tomorrow'), type: t('dashboard.typeFollowUp') },
];
</script>

<template>
  <div class="h-full flex flex-col">
    <h3 class="text-base font-semibold text-slate-800 mb-4">{{ title }}</h3>
    <div class="flex-1 overflow-y-auto pr-2 space-y-3">
      <div
        v-for="item in upcomingAppointments"
        :key="item.id"
        class="border-b border-slate-100 last:border-0 pb-3 last:pb-0"
      >
        <div class="flex justify-between items-center text-sm">
          <div>
            <p class="font-medium text-slate-800">{{ item.client }}</p>
            <p class="text-slate-500 text-xs">{{ item.type }}</p>
          </div>
          <div class="text-right bg-slate-100 px-2 py-1 rounded text-xs font-medium text-slate-600">
            {{ item.time }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
