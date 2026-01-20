<template>
  <div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.finance.accounting') }}</h3>
      <button class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 flex items-center gap-2">
        <PlusIcon :size="18" /> {{ $t('mod.finance.new_entry') }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.assets') }}</div>
        <div class="text-2xl font-bold text-slate-800">CHF 125,400</div>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.liabilities') }}</div>
        <div class="text-2xl font-bold text-slate-800">CHF 45,200</div>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.equity') }}</div>
        <div class="text-2xl font-bold text-emerald-600">CHF 80,200</div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
      <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.finance.recent_entries') }}</h4>
      <div class="space-y-2">
        <div v-for="i in 8" :key="i" class="flex justify-between items-center p-3 hover:bg-slate-50 rounded-lg border border-slate-100">
          <div>
            <div class="font-medium text-sm text-slate-800">{{ $t('mod.finance.transaction') }} #{{ i }}</div>
            <div class="text-xs text-slate-500">{{ $t('mod.finance.account') }}: 1020</div>
          </div>
          <div class="text-right">
            <div class="font-bold text-sm text-slate-800">CHF {{ (Math.random() * 1000).toFixed(2) }}</div>
            <div class="text-xs text-slate-500">{{ new Date().toLocaleDateString('de-CH') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Plus as PlusIcon } from 'lucide-vue-next'
const { t: $t } = useI18n()
</script>
