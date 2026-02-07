<script setup lang="ts">
/**
 * ActivityFeed -- Recent activity list widget
 *
 * Displays a scrollable list of recent actions.
 * Matches reference ListWidget pattern: user, action, time.
 * TODO: Replace mock data with API call
 */
import { useI18n } from '@/composables/useI18n';

defineProps<{
  title: string;
}>();

const { t } = useI18n();

// Mock data matching reference (TODO: API)
const activities = [
  { id: 1, user: 'Alice Freeman', action: t('dashboard.actionBooked'), time: t('dashboard.time2min') },
  { id: 2, user: 'Bob Smith', action: `${t('dashboard.actionPaid')} #INV-002`, time: t('dashboard.time1hour') },
  { id: 3, user: t('dashboard.newPartner'), action: t('dashboard.actionConnection'), time: t('dashboard.time3hours') },
  { id: 4, user: t('dashboard.system'), action: t('dashboard.actionBackup'), time: t('dashboard.time5hours') },
];
</script>

<template>
  <div class="h-full flex flex-col">
    <h3 class="text-base font-semibold text-slate-800 mb-4">{{ title }}</h3>
    <div class="flex-1 overflow-y-auto pr-2 space-y-3">
      <div
        v-for="item in activities"
        :key="item.id"
        class="border-b border-slate-100 last:border-0 pb-3 last:pb-0"
      >
        <div class="text-sm">
          <p class="font-medium text-slate-800">{{ item.user }}</p>
          <p class="text-slate-500 text-xs">{{ item.action }}</p>
          <p class="text-slate-400 text-[10px] mt-1">{{ item.time }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
