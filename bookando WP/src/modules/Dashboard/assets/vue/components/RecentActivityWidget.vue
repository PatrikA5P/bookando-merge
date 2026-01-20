<template>
  <div class="h-full flex flex-col">
    <h3 class="text-base font-semibold text-slate-800 mb-4">{{ $t('dashboard.recent_activity') }}</h3>
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

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()

interface Activity {
  id: number
  user: string
  action: string
  time: string
}

const activities = ref<Activity[]>([
  { id: 1, user: 'Alice Freeman', action: $t('dashboard.action_booked'), time: '2 ' + $t('dashboard.mins_ago') },
  { id: 2, user: 'Bob Smith', action: $t('dashboard.action_paid') + ' #INV-002', time: '1 ' + $t('dashboard.hour_ago') },
  { id: 3, user: $t('dashboard.new_partner'), action: $t('dashboard.action_connection'), time: '3 ' + $t('dashboard.hours_ago') },
  { id: 4, user: 'System', action: $t('dashboard.action_backup'), time: '5 ' + $t('dashboard.hours_ago') }
])
</script>
