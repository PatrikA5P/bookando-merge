<template>
  <div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.finance.payroll') }}</h3>
      <button class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700">{{ $t('mod.finance.process_payroll') }}</button>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold text-slate-500 uppercase">
            <th class="p-4">{{ $t('mod.finance.employee') }}</th>
            <th class="p-4">{{ $t('mod.finance.hours_worked') }}</th>
            <th class="p-4">{{ $t('mod.finance.gross_salary') }}</th>
            <th class="p-4">{{ $t('mod.finance.deductions') }}</th>
            <th class="p-4">{{ $t('mod.finance.net_salary') }}</th>
            <th class="p-4">{{ $t('mod.finance.status') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr v-for="emp in mockPayroll" :key="emp.id" class="hover:bg-slate-50">
            <td class="p-4 font-medium text-slate-800">{{ emp.name }}</td>
            <td class="p-4 text-slate-600">{{ emp.hours }}h</td>
            <td class="p-4 text-slate-800">CHF {{ emp.gross }}</td>
            <td class="p-4 text-rose-600">-CHF {{ emp.deductions }}</td>
            <td class="p-4 font-bold text-emerald-600">CHF {{ emp.net }}</td>
            <td class="p-4"><span class="px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">{{ emp.status }}</span></td>
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

const mockPayroll = ref([
  { id: 1, name: 'Sarah Jenkins', hours: 160, gross: '5,200', deductions: '1,040', net: '4,160', status: 'Processed' },
  { id: 2, name: 'Mike Ross', hours: 160, gross: '4,800', deductions: '960', net: '3,840', status: 'Processed' },
  { id: 3, name: 'Jessica Pearson', hours: 160, gross: '6,000', deductions: '1,200', net: '4,800', status: 'Processed' }
])
</script>
