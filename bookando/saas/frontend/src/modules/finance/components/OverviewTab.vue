<script setup lang="ts">
/**
 * OverviewTab — Finance KPI Dashboard
 *
 * Zeigt 4 KPI-Karten, Umsatz-Chart-Platzhalter und
 * die 5 neuesten Rechnungen als Mini-Tabelle.
 */
import { computed } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useAppStore } from '@/stores/app';
import { useFinanceStore } from '@/stores/finance';
import { CARD_STYLES, GRID_STYLES, TABLE_STYLES, BADGE_STYLES } from '@/design';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const appStore = useAppStore();
const financeStore = useFinanceStore();

const kpiCards = computed(() => [
  {
    id: 'revenue',
    label: t('finance.overview.revenue'),
    value: appStore.formatPrice(financeStore.revenueThisMonthMinor),
    icon: 'trending-up',
    color: 'text-emerald-600',
    bgColor: 'bg-emerald-50',
  },
  {
    id: 'outstanding',
    label: t('finance.overview.outstanding'),
    value: appStore.formatPrice(financeStore.outstandingMinor),
    icon: 'clock',
    color: 'text-blue-600',
    bgColor: 'bg-blue-50',
  },
  {
    id: 'overdue',
    label: t('finance.overview.overdue'),
    value: String(financeStore.overdueCount),
    icon: 'alert',
    color: 'text-red-600',
    bgColor: 'bg-red-50',
  },
  {
    id: 'profit',
    label: t('finance.overview.profit'),
    value: appStore.formatPrice(financeStore.profitThisMonthMinor),
    icon: 'chart',
    color: 'text-purple-600',
    bgColor: 'bg-purple-50',
  },
]);

function getStatusKey(status: string): string {
  return status.toLowerCase();
}
</script>

<template>
  <div class="space-y-6">
    <!-- KPI Stat Cards -->
    <div :class="GRID_STYLES.cols4">
      <div
        v-for="kpi in kpiCards"
        :key="kpi.id"
        :class="CARD_STYLES.stat"
      >
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">
            {{ kpi.label }}
          </span>
          <div :class="['w-9 h-9 rounded-lg flex items-center justify-center', kpi.bgColor]">
            <!-- Trending Up -->
            <svg v-if="kpi.icon === 'trending-up'" :class="['w-5 h-5', kpi.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <!-- Clock -->
            <svg v-else-if="kpi.icon === 'clock'" :class="['w-5 h-5', kpi.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <!-- Alert -->
            <svg v-else-if="kpi.icon === 'alert'" :class="['w-5 h-5', kpi.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <!-- Chart -->
            <svg v-else-if="kpi.icon === 'chart'" :class="['w-5 h-5', kpi.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-slate-900">{{ kpi.value }}</p>
        <p class="text-xs text-slate-400 mt-1">
          {{ kpi.id === 'overdue' ? t('finance.overview.invoicesOverdue') : t('finance.overview.thisMonth') }}
        </p>
      </div>
    </div>

    <!-- Revenue Chart Placeholder -->
    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-sm font-semibold text-slate-900">{{ t('finance.overview.revenueChart') }}</h3>
      </div>
      <div :class="CARD_STYLES.body">
        <div class="h-48 flex items-center justify-center bg-slate-50 rounded-lg border border-dashed border-slate-200">
          <div class="text-center">
            <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-sm text-slate-400">{{ t('finance.overview.chartPlaceholder') }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Invoices Mini-Table -->
    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-sm font-semibold text-slate-900">{{ t('finance.overview.recentInvoices') }}</h3>
      </div>

      <!-- Mobile: Cards -->
      <div v-if="isMobile" class="divide-y divide-slate-100">
        <div
          v-for="inv in financeStore.latestInvoices"
          :key="inv.id"
          class="p-4 flex items-center justify-between"
        >
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-900 truncate">{{ inv.customerName }}</p>
            <p class="text-xs text-slate-500">{{ inv.number }} &middot; {{ appStore.formatDate(inv.issueDate) }}</p>
          </div>
          <div class="flex items-center gap-3 ml-3">
            <span class="text-sm font-semibold text-slate-900 whitespace-nowrap">{{ appStore.formatPrice(inv.totalMinor) }}</span>
            <BBadge :status="getStatusKey(inv.status)" dot>
              {{ t(`finance.status.${inv.status}`) }}
            </BBadge>
          </div>
        </div>
      </div>

      <!-- Desktop: Table -->
      <div v-else :class="TABLE_STYLES.scrollContainer">
        <table :class="TABLE_STYLES.table">
          <thead :class="TABLE_STYLES.thead">
            <tr>
              <th :class="TABLE_STYLES.th">{{ t('finance.invoices.number') }}</th>
              <th :class="TABLE_STYLES.th">{{ t('finance.invoices.customer') }}</th>
              <th :class="TABLE_STYLES.th">{{ t('finance.invoices.date') }}</th>
              <th :class="[TABLE_STYLES.th, 'text-right']">{{ t('finance.invoices.total') }}</th>
              <th :class="TABLE_STYLES.th">{{ t('finance.invoices.status') }}</th>
            </tr>
          </thead>
          <tbody :class="TABLE_STYLES.tbody">
            <tr
              v-for="inv in financeStore.latestInvoices"
              :key="inv.id"
              :class="TABLE_STYLES.tr"
            >
              <td :class="TABLE_STYLES.tdBold">{{ inv.number }}</td>
              <td :class="TABLE_STYLES.td">{{ inv.customerName }}</td>
              <td :class="TABLE_STYLES.tdMuted">{{ appStore.formatDate(inv.issueDate) }}</td>
              <td :class="[TABLE_STYLES.tdBold, 'text-right']">{{ appStore.formatPrice(inv.totalMinor) }}</td>
              <td :class="TABLE_STYLES.td">
                <BBadge :status="getStatusKey(inv.status)" dot>
                  {{ t(`finance.status.${inv.status}`) }}
                </BBadge>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
