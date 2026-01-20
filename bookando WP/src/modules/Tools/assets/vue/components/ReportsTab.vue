<template>
  <div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.tools.report_generation') }}</h3>
      <button class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 flex items-center gap-2">
        <DownloadIcon :size="18" /> {{ $t('mod.tools.generate_report') }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="report in reportTypes" :key="report.id" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow cursor-pointer">
        <component :is="report.icon" :size="24" class="text-brand-600 mb-3" />
        <h4 class="font-bold text-slate-800 mb-2">{{ report.title }}</h4>
        <p class="text-sm text-slate-600 mb-4">{{ report.description }}</p>
        <button class="text-brand-600 text-sm font-medium hover:underline">{{ $t('mod.tools.generate') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Download as DownloadIcon, FileText, TrendingUp, Users, Calendar, DollarSign, Clock } from 'lucide-vue-next'

const { t: $t } = useI18n()

const reportTypes = ref([
  { id: 1, title: $t('mod.tools.sales_report'), description: $t('mod.tools.sales_desc'), icon: TrendingUp },
  { id: 2, title: $t('mod.tools.customer_report'), description: $t('mod.tools.customer_desc'), icon: Users },
  { id: 3, title: $t('mod.tools.appointment_report'), description: $t('mod.tools.appointment_desc'), icon: Calendar },
  { id: 4, title: $t('mod.tools.financial_report'), description: $t('mod.tools.financial_desc'), icon: DollarSign },
  { id: 5, title: $t('mod.tools.time_tracking_report'), description: $t('mod.tools.time_tracking_desc'), icon: Clock },
  { id: 6, title: $t('mod.tools.custom_report'), description: $t('mod.tools.custom_desc'), icon: FileText }
])
</script>
