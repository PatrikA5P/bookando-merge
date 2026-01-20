<template>
  <div class="h-full flex flex-col">
    <h3 class="text-base font-semibold text-slate-800 mb-4">{{ $t('dashboard.upcoming_appointments') }}</h3>
    <div class="flex-1 overflow-y-auto pr-2 space-y-3">
      <div
        v-for="item in appointments"
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

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()

interface Appointment {
  id: number
  client: string
  time: string
  type: string
}

const appointments = ref<Appointment[]>([
  { id: 1, client: 'Sarah Connor', time: '14:00 ' + $t('dashboard.today'), type: $t('dashboard.consultation') },
  { id: 2, client: 'John Wick', time: '16:30 ' + $t('dashboard.today'), type: $t('dashboard.emergency') },
  { id: 3, client: 'Ellen Ripley', time: '09:00 ' + $t('dashboard.tomorrow'), type: $t('dashboard.followup') }
])
</script>
