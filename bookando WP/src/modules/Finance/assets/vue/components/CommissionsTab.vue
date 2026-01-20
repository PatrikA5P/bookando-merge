<template>
  <div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.finance.commission_tracking') }}</h3>
      <button class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700">{{ $t('mod.finance.calculate_commissions') }}</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.total_commissions') }}</div>
        <div class="text-2xl font-bold text-slate-800">CHF 8,450</div>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.paid_out') }}</div>
        <div class="text-2xl font-bold text-emerald-600">CHF 6,200</div>
      </div>
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $t('mod.finance.pending_payout') }}</div>
        <div class="text-2xl font-bold text-amber-600">CHF 2,250</div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold text-slate-500 uppercase">
            <th class="p-4">{{ $t('mod.finance.employee') }}</th>
            <th class="p-4">{{ $t('mod.finance.sales_amount') }}</th>
            <th class="p-4">{{ $t('mod.finance.rate') }}</th>
            <th class="p-4">{{ $t('mod.finance.commission_earned') }}</th>
            <th class="p-4">{{ $t('mod.finance.status') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr v-for="c in mockCommissions" :key="c.id" class="hover:bg-slate-50">
            <td class="p-4 font-medium text-slate-800">{{ c.employee }}</td>
            <td class="p-4 text-slate-600">CHF {{ c.sales }}</td>
            <td class="p-4 text-slate-600">{{ c.rate }}%</td>
            <td class="p-4 font-bold text-emerald-600">CHF {{ c.commission }}</td>
            <td class="p-4"><span :class="['px-2 py-1 rounded-full text-xs font-medium', c.status === 'Paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">{{ c.status }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
const { t: $t } = useI18n()

const mockCommissions = ref([
  { id: 1, employee: 'Sarah Jenkins', sales: '10,500', rate: 15, commission: '1,575', status: 'Paid' },
  { id: 2, employee: 'Mike Ross', sales: '8,200', rate: 12, commission: '984', status: 'Pending' },
  { id: 3, employee: 'Jessica Pearson', sales: '15,600', rate: 18, commission: '2,808', status: 'Paid' }
])
</script>
