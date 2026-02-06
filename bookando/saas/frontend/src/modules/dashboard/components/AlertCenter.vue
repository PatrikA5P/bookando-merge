<script setup lang="ts">
/**
 * AlertCenter — Infocenter/Benachrichtigungen Widget
 */
import { ref } from 'vue';
import { CARD_STYLES, BADGE_STYLES } from '@/design';

interface Alert {
  id: string;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'error';
  acknowledged: boolean;
  timestamp: string;
}

// Mock-Daten (TODO: API via Foundation NotificationPort)
const alerts = ref<Alert[]>([
  { id: '1', title: 'Swissdec Update', message: 'ELM 5.3 Quellensteuer-Update verfügbar (ab Jan. 2026 obligatorisch)', type: 'info', acknowledged: false, timestamp: 'Vor 1 Std.' },
  { id: '2', title: 'Überstunden-Warnung', message: '3 Mitarbeiter haben das Überstundenlimit erreicht', type: 'warning', acknowledged: false, timestamp: 'Vor 3 Std.' },
  { id: '3', title: 'Offene Rechnungen', message: '5 Rechnungen sind überfällig (Total: CHF 4,250.00)', type: 'error', acknowledged: false, timestamp: 'Gestern' },
]);

function acknowledge(id: string) {
  const alert = alerts.value.find(a => a.id === id);
  if (alert) alert.acknowledged = true;
}

const typeIcon: Record<string, string> = {
  info: 'text-blue-500',
  warning: 'text-amber-500',
  error: 'text-red-500',
};

const unacknowledgedCount = ref(alerts.value.filter(a => !a.acknowledged).length);
</script>

<template>
  <div :class="CARD_STYLES.base">
    <div :class="CARD_STYLES.headerCompact">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h3 class="text-base font-semibold text-slate-900">Infocenter</h3>
          <span v-if="unacknowledgedCount > 0" :class="BADGE_STYLES.danger">
            {{ unacknowledgedCount }}
          </span>
        </div>
      </div>
    </div>
    <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
      <div
        v-for="alert in alerts"
        :key="alert.id"
        :class="[
          'px-4 py-3 transition-colors',
          alert.acknowledged ? 'opacity-60 bg-slate-50' : 'hover:bg-slate-50',
        ]"
      >
        <div class="flex items-start gap-3">
          <svg :class="['w-5 h-5 shrink-0 mt-0.5', typeIcon[alert.type]]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path v-if="alert.type === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            <path v-else-if="alert.type === 'warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-900">{{ alert.title }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ alert.message }}</p>
            <div class="flex items-center justify-between mt-2">
              <span class="text-xs text-slate-400">{{ alert.timestamp }}</span>
              <button
                v-if="!alert.acknowledged"
                class="text-xs text-brand-600 hover:text-brand-700 font-medium"
                @click="acknowledge(alert.id)"
              >
                Gelesen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
