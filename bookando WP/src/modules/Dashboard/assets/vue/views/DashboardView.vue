<template>
  <div class="space-y-6 p-6">
    <!-- Header -->
    <div class="flex justify-between items-end">
      <div>
        <h2 class="text-lg font-semibold text-slate-800">{{ $t('mod.dashboard.overview') }}</h2>
        <p class="text-sm text-slate-500">{{ $t('mod.dashboard.overview_subtitle') }}</p>
      </div>
      <div class="flex gap-2">
        <button
          @click="isCustomizing = !isCustomizing"
          class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="isCustomizing ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
        >
          <component :is="isCustomizing ? XIcon : SettingsIcon" :size="16" />
          {{ isCustomizing ? $t('common.done') : $t('common.customize') }}
        </button>
      </div>
    </div>

    <!-- Simple placeholder widget grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-sm font-medium text-slate-500 mb-1">Revenue</div>
        <h3 class="text-2xl font-bold text-slate-900">CHF 45,231</h3>
        <div class="mt-2 text-xs text-emerald-600">+20.1% vs last month</div>
      </div>

      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-sm font-medium text-slate-500 mb-1">Customers</div>
        <h3 class="text-2xl font-bold text-slate-900">2,345</h3>
        <div class="mt-2 text-xs text-emerald-600">+15.3% vs last month</div>
      </div>

      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-sm font-medium text-slate-500 mb-1">Appointments</div>
        <h3 class="text-2xl font-bold text-slate-900">452</h3>
        <div class="mt-2 text-xs text-rose-600">-4.5% vs last month</div>
      </div>

      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="text-sm font-medium text-slate-500 mb-1">Avg Time</div>
        <h3 class="text-2xl font-bold text-slate-900">58m</h3>
        <div class="mt-2 text-xs text-emerald-600">+1.2% vs last month</div>
      </div>
    </div>

    <!-- Placeholder message -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
      <p class="text-sm text-blue-800">
        📊 Dashboard is now available! Full widget customization coming soon.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Settings as SettingsIcon, X as XIcon } from 'lucide-vue-next'

const { t: $t } = useI18n()
const isCustomizing = ref(false)
</script>
