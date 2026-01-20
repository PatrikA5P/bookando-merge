<template>
  <div class="space-y-6 p-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-slate-500">{{ $t('mod.finance.total_revenue') }}</span>
          <TrendingUpIcon :size="20" class="text-emerald-600" />
        </div>
        <div class="text-3xl font-bold text-slate-800">CHF 45,230</div>
        <div class="text-xs text-emerald-600 mt-1">+12.5% {{ $t('mod.finance.vs_last_month') }}</div>
      </div>

      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-slate-500">{{ $t('mod.finance.outstanding') }}</span>
          <AlertCircleIcon :size="20" class="text-amber-600" />
        </div>
        <div class="text-3xl font-bold text-slate-800">CHF 8,450</div>
        <div class="text-xs text-slate-500 mt-1">12 {{ $t('mod.finance.invoices') }}</div>
      </div>

      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-slate-500">{{ $t('mod.finance.paid_invoices') }}</span>
          <CheckCircleIcon :size="20" class="text-emerald-600" />
        </div>
        <div class="text-3xl font-bold text-slate-800">CHF 36,780</div>
        <div class="text-xs text-slate-500 mt-1">48 {{ $t('mod.finance.invoices') }}</div>
      </div>

      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-slate-500">{{ $t('mod.finance.expenses') }}</span>
          <TrendingDownIcon :size="20" class="text-rose-600" />
        </div>
        <div class="text-3xl font-bold text-slate-800">CHF 12,340</div>
        <div class="text-xs text-rose-600 mt-1">+5.2% {{ $t('mod.finance.vs_last_month') }}</div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Revenue Chart -->
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h3 class="font-bold text-slate-800 mb-4">{{ $t('mod.finance.revenue_trend') }}</h3>
        <div class="h-64 flex items-end justify-around gap-2">
          <div v-for="(val, i) in [65, 75, 82, 90, 78, 85, 95]" :key="i" class="flex-1 bg-emerald-500 rounded-t" :style="`height: ${val}%`"></div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-slate-500">
          <span>{{ $t('mod.finance.mon') }}</span>
          <span>{{ $t('mod.finance.tue') }}</span>
          <span>{{ $t('mod.finance.wed') }}</span>
          <span>{{ $t('mod.finance.thu') }}</span>
          <span>{{ $t('mod.finance.fri') }}</span>
          <span>{{ $t('mod.finance.sat') }}</span>
          <span>{{ $t('mod.finance.sun') }}</span>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h3 class="font-bold text-slate-800 mb-4">{{ $t('mod.finance.recent_transactions') }}</h3>
        <div class="space-y-3">
          <div v-for="i in 5" :key="i" class="flex items-center justify-between p-3 hover:bg-slate-50 rounded-lg transition-colors">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                <ArrowDownIcon :size="14" />
              </div>
              <div>
                <div class="font-medium text-sm text-slate-800">{{ $t('mod.finance.invoice') }} #{{ 1000 + i }}</div>
                <div class="text-xs text-slate-500">2 {{ $t('mod.finance.days_ago') }}</div>
              </div>
            </div>
            <div class="text-sm font-bold text-emerald-600">+CHF 850</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { TrendingUp as TrendingUpIcon, TrendingDown as TrendingDownIcon, AlertCircle as AlertCircleIcon, CheckCircle as CheckCircleIcon, ArrowDown as ArrowDownIcon } from 'lucide-vue-next'

const { t: $t } = useI18n()
</script>
