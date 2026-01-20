<template>
  <div class="p-6 space-y-6">
    <!--Header with Actions -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.finance.invoice_management') }}</h3>
      <button class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 flex items-center gap-2">
        <PlusIcon :size="18" /> {{ $t('mod.finance.new_invoice') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-4 bg-white p-4 rounded-xl border border-slate-200">
      <select class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
        <option>{{ $t('mod.finance.all_statuses') }}</option>
        <option>{{ $t('mod.finance.draft') }}</option>
        <option>{{ $t('mod.finance.sent') }}</option>
        <option>{{ $t('mod.finance.paid') }}</option>
        <option>{{ $t('mod.finance.overdue') }}</option>
      </select>
      <input type="search" :placeholder="$t('mod.finance.search_invoices')" class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm" />
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold text-slate-500 uppercase">
            <th class="p-4">{{ $t('mod.finance.invoice_no') }}</th>
            <th class="p-4">{{ $t('mod.finance.customer') }}</th>
            <th class="p-4">{{ $t('mod.finance.date') }}</th>
            <th class="p-4">{{ $t('mod.finance.amount') }}</th>
            <th class="p-4">{{ $t('mod.finance.status') }}</th>
            <th class="p-4 text-right">{{ $t('mod.finance.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr v-for="inv in mockInvoices" :key="inv.id" class="hover:bg-slate-50">
            <td class="p-4 font-mono font-medium text-slate-800">#{{ inv.number }}</td>
            <td class="p-4 text-slate-700">{{ inv.customer }}</td>
            <td class="p-4 text-slate-600">{{ inv.date }}</td>
            <td class="p-4 font-bold text-slate-800">CHF {{ inv.amount }}</td>
            <td class="p-4">
              <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusClass(inv.status)]">
                {{ inv.status }}
              </span>
            </td>
            <td class="p-4 text-right">
              <button class="text-brand-600 hover:text-brand-700 text-sm font-medium">{{ $t('mod.finance.view') }}</button>
            </td>
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

const mockInvoices = ref([
  { id: 1, number: '2024-001', customer: 'Maria Schmidt', date: '2024-01-15', amount: '850.00', status: 'Paid' },
  { id: 2, number: '2024-002', customer: 'Thomas Weber', date: '2024-01-18', amount: '1,250.00', status: 'Sent' },
  { id: 3, number: '2024-003', customer: 'Julia Meier', date: '2024-01-20', amount: '650.00', status: 'Overdue' },
  { id: 4, number: '2024-004', customer: 'Andreas Fischer', date: '2024-01-22', amount: '2,100.00', status: 'Draft' }
])

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Paid': return 'bg-emerald-100 text-emerald-700'
    case 'Sent': return 'bg-blue-100 text-blue-700'
    case 'Overdue': return 'bg-rose-100 text-rose-700'
    case 'Draft': return 'bg-slate-100 text-slate-700'
    default: return 'bg-slate-100 text-slate-700'
  }
}
</script>
