<script setup lang="ts">
/**
 * AlertCenter -- Infocenter/notification widget
 *
 * Displays active alerts with dismiss (acknowledge) functionality.
 * Shows "all clear" empty state when no alerts remain.
 * Matches reference InfocenterWidget exactly.
 * TODO: Replace mock data with API call via Foundation NotificationPort
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

interface Alert {
  id: string;
  title: string;
  message: string;
  acknowledged: boolean;
  timestamp: string;
}

// Mock data (TODO: API)
const alerts = ref<Alert[]>([
  {
    id: '1',
    title: t('dashboard.alertSwissdecTitle'),
    message: t('dashboard.alertSwissdecMessage'),
    acknowledged: false,
    timestamp: t('dashboard.time1hour'),
  },
  {
    id: '2',
    title: t('dashboard.alertOvertimeTitle'),
    message: t('dashboard.alertOvertimeMessage'),
    acknowledged: false,
    timestamp: t('dashboard.time3hours'),
  },
  {
    id: '3',
    title: t('dashboard.alertInvoicesTitle'),
    message: t('dashboard.alertInvoicesMessage'),
    acknowledged: false,
    timestamp: t('dashboard.timeYesterday'),
  },
]);

const activeAlerts = computed(() => alerts.value.filter(a => !a.acknowledged));

function acknowledgeAlert(id: string) {
  const alert = alerts.value.find(a => a.id === id);
  if (alert) alert.acknowledged = true;
}
</script>

<template>
  <!-- All clear state -->
  <div v-if="activeAlerts.length === 0" class="h-full flex flex-col items-center justify-center text-center p-4">
    <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mb-3">
      <!-- Check icon -->
      <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
    </div>
    <h3 class="text-base font-semibold text-slate-800">{{ t('dashboard.allClear') }}</h3>
    <p class="text-xs text-slate-500">{{ t('dashboard.noAlerts') }}</p>
  </div>

  <!-- Active alerts -->
  <div v-else class="h-full flex flex-col">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
        <!-- Bell icon -->
        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        {{ t('dashboard.infocenter') }}
      </h3>
      <span class="text-xs font-bold bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">
        {{ activeAlerts.length }} {{ t('dashboard.new') }}
      </span>
    </div>
    <div class="flex-1 overflow-y-auto pr-2 space-y-3">
      <div
        v-for="alert in activeAlerts"
        :key="alert.id"
        class="p-3 bg-rose-50 border border-rose-100 rounded-lg"
      >
        <div class="flex justify-between items-start mb-1">
          <div class="flex items-center gap-2 text-sm font-bold text-rose-800">
            <!-- AlertTriangle icon -->
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            {{ alert.title }}
          </div>
          <button
            class="text-rose-400 hover:text-rose-700 transition-colors"
            :title="t('dashboard.acknowledge')"
            @click="acknowledgeAlert(alert.id)"
          >
            <!-- Check icon -->
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </button>
        </div>
        <p class="text-xs text-rose-700 mb-2 leading-relaxed">
          {{ alert.message }}
        </p>
        <div class="text-[10px] text-rose-400 text-right">
          {{ alert.timestamp }}
        </div>
      </div>
    </div>
  </div>
</template>
