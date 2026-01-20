<template>
  <div class="h-full flex flex-col">
    <!-- No alerts state -->
    <div v-if="activeAlerts.length === 0" class="h-full flex flex-col items-center justify-center text-center p-4">
      <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mb-3">
        <CheckIcon :size="24" class="text-emerald-500" />
      </div>
      <h3 class="text-base font-semibold text-slate-800">{{ $t('dashboard.all_clear') }}</h3>
      <p class="text-xs text-slate-500">{{ $t('dashboard.no_alerts') }}</p>
    </div>

    <!-- Alerts present -->
    <template v-else>
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
          <BellIcon :size="16" class="text-rose-500" /> {{ $t('dashboard.infocenter') }}
        </h3>
        <span class="text-xs font-bold bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">
          {{ activeAlerts.length }} {{ $t('dashboard.new') }}
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
              <AlertTriangleIcon :size="14" /> {{ alert.title }}
            </div>
            <button
              @click="acknowledgeAlert(alert.id)"
              class="text-rose-400 hover:text-rose-700"
              :title="$t('dashboard.acknowledge')"
            >
              <CheckIcon :size="14" />
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
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bell as BellIcon, Check as CheckIcon, AlertTriangle as AlertTriangleIcon } from 'lucide-vue-next'

const { t: $t } = useI18n()

interface Alert {
  id: number
  title: string
  message: string
  timestamp: string
  acknowledged: boolean
}

const alerts = ref<Alert[]>([
  {
    id: 1,
    title: $t('dashboard.alert_payment_failed'),
    message: $t('dashboard.alert_payment_failed_msg'),
    timestamp: '2 ' + $t('dashboard.mins_ago'),
    acknowledged: false
  },
  {
    id: 2,
    title: $t('dashboard.alert_low_stock'),
    message: $t('dashboard.alert_low_stock_msg'),
    timestamp: '1 ' + $t('dashboard.hour_ago'),
    acknowledged: false
  },
  {
    id: 3,
    title: $t('dashboard.alert_maintenance'),
    message: $t('dashboard.alert_maintenance_msg'),
    timestamp: '3 ' + $t('dashboard.hours_ago'),
    acknowledged: false
  }
])

const activeAlerts = computed(() => alerts.value.filter(a => !a.acknowledged))

const acknowledgeAlert = (id: number) => {
  const alert = alerts.value.find(a => a.id === id)
  if (alert) {
    alert.acknowledged = true
  }
}
</script>
