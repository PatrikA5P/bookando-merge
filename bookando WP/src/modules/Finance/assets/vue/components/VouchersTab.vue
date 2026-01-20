<template>
  <div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.finance.voucher_management') }}</h3>
      <button class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 flex items-center gap-2">
        <PlusIcon :size="18" /> {{ $t('mod.finance.create_voucher') }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.active_vouchers') }}</div>
        <div class="text-2xl font-bold text-slate-800">24</div>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.redeemed') }}</div>
        <div class="text-2xl font-bold text-emerald-600">156</div>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.total_value') }}</div>
        <div class="text-2xl font-bold text-slate-800">CHF 12,500</div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold text-slate-500 uppercase">
            <th class="p-4">{{ $t('mod.finance.code') }}</th>
            <th class="p-4">{{ $t('mod.finance.recipient') }}</th>
            <th class="p-4">{{ $t('mod.finance.value') }}</th>
            <th class="p-4">{{ $t('mod.finance.valid_until') }}</th>
            <th class="p-4">{{ $t('mod.finance.status') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr v-for="v in mockVouchers" :key="v.id" class="hover:bg-slate-50">
            <td class="p-4 font-mono font-medium text-slate-800">{{ v.code }}</td>
            <td class="p-4 text-slate-700">{{ v.recipient }}</td>
            <td class="p-4 font-bold text-slate-800">CHF {{ v.value }}</td>
            <td class="p-4 text-slate-600">{{ v.validUntil }}</td>
            <td class="p-4"><span :class="['px-2 py-1 rounded-full text-xs font-medium', v.status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700']">{{ v.status }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Plus as PlusIcon } from 'lucide-vue-next'
const { t: $t } = useI18n()

const mockVouchers = ref([
  { id: 1, code: 'VOUCHER2024-001', recipient: 'Maria Schmidt', value: '100', validUntil: '31.12.2024', status: 'Active' },
  { id: 2, code: 'VOUCHER2024-002', recipient: 'Thomas Weber', value: '200', validUntil: '30.06.2024', status: 'Active' },
  { id: 3, code: 'VOUCHER2024-003', recipient: 'Julia Meier', value: '50', validUntil: '15.03.2024', status: 'Redeemed' }
])
</script>
