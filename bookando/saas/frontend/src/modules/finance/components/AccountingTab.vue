<script setup lang="ts">
/**
 * AccountingTab — Buchhaltung (GeBüV-konform)
 *
 * Features:
 * - Kontenplan (Chart of Accounts) als Accordion
 *   Gruppen: Aktiven, Passiven, Ertrag, Aufwand
 * - Journal-Einträge Tabelle (BTable)
 * - Bilanzzusammenfassung als Stat-Cards
 */
import { ref, computed } from 'vue';
import BTable from '@/components/ui/BTable.vue';
import BBadge from '@/components/ui/BBadge.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useAppStore } from '@/stores/app';
import { useFinanceStore } from '@/stores/finance';
import { CARD_STYLES, GRID_STYLES, TABLE_STYLES, BADGE_STYLES, BUTTON_STYLES } from '@/design';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const appStore = useAppStore();
const financeStore = useFinanceStore();

// Accordion state
const openSections = ref<string[]>(['assets']);

function toggleSection(section: string) {
  const idx = openSections.value.indexOf(section);
  if (idx !== -1) {
    openSections.value.splice(idx, 1);
  } else {
    openSections.value.push(section);
  }
}

function isSectionOpen(section: string): boolean {
  return openSections.value.includes(section);
}

// Account groups
const accountGroups = computed(() => [
  {
    id: 'assets',
    label: t('finance.accounting.assets'),
    accounts: financeStore.assetAccounts,
    totalMinor: financeStore.totalAssetsMinor,
    color: 'text-blue-600',
    bgColor: 'bg-blue-50',
    icon: 'building',
  },
  {
    id: 'liabilities',
    label: t('finance.accounting.liabilities'),
    accounts: financeStore.liabilityAccounts,
    totalMinor: financeStore.totalLiabilitiesMinor,
    color: 'text-red-600',
    bgColor: 'bg-red-50',
    icon: 'scale',
  },
  {
    id: 'revenue',
    label: t('finance.accounting.revenue'),
    accounts: financeStore.revenueAccounts,
    totalMinor: financeStore.totalRevenueMinor,
    color: 'text-emerald-600',
    bgColor: 'bg-emerald-50',
    icon: 'trending-up',
  },
  {
    id: 'expenses',
    label: t('finance.accounting.expenses'),
    accounts: financeStore.expenseAccounts,
    totalMinor: financeStore.totalExpensesMinor,
    color: 'text-amber-600',
    bgColor: 'bg-amber-50',
    icon: 'receipt',
  },
]);

// Journal table
const journalColumns = [
  { key: 'date', label: t('finance.accounting.date'), sortable: true },
  { key: 'description', label: t('finance.accounting.description') },
  { key: 'account', label: t('finance.accounting.debitAccount') },
  { key: 'contraAccount', label: t('finance.accounting.creditAccount') },
  { key: 'amount', label: t('finance.accounting.amount'), align: 'right' as const },
];

const journalData = computed(() =>
  financeStore.journalEntries.map(entry => ({
    ...entry,
    amount: entry.debitMinor > 0 ? entry.debitMinor : entry.creditMinor,
  }))
);

// Balance sheet summary
const balanceSummary = computed(() => [
  {
    label: t('finance.accounting.totalAssets'),
    value: financeStore.totalAssetsMinor,
    color: 'text-blue-600',
    bgColor: 'bg-blue-50',
  },
  {
    label: t('finance.accounting.totalLiabilities'),
    value: financeStore.totalLiabilitiesMinor,
    color: 'text-red-600',
    bgColor: 'bg-red-50',
  },
  {
    label: t('finance.accounting.totalRevenue'),
    value: financeStore.totalRevenueMinor,
    color: 'text-emerald-600',
    bgColor: 'bg-emerald-50',
  },
  {
    label: t('finance.accounting.totalExpenses'),
    value: financeStore.totalExpensesMinor,
    color: 'text-amber-600',
    bgColor: 'bg-amber-50',
  },
]);
</script>

<template>
  <div class="space-y-6">
    <!-- Balance Sheet Summary Cards -->
    <div :class="GRID_STYLES.cols4">
      <div
        v-for="item in balanceSummary"
        :key="item.label"
        :class="CARD_STYLES.stat"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ item.label }}</span>
          <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', item.bgColor]">
            <svg :class="['w-4 h-4', item.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-xl font-bold text-slate-900">{{ appStore.formatPrice(item.value) }}</p>
      </div>
    </div>

    <!-- Chart of Accounts (Accordion) -->
    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-sm font-semibold text-slate-900">{{ t('finance.accounting.chartOfAccounts') }}</h3>
      </div>
      <div class="divide-y divide-slate-200">
        <div v-for="group in accountGroups" :key="group.id">
          <!-- Accordion Header -->
          <button
            class="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition-colors text-left"
            @click="toggleSection(group.id)"
          >
            <div class="flex items-center gap-3">
              <div :class="['w-8 h-8 rounded-lg flex items-center justify-center shrink-0', group.bgColor]">
                <!-- Building -->
                <svg v-if="group.icon === 'building'" :class="['w-4 h-4', group.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <!-- Scale -->
                <svg v-else-if="group.icon === 'scale'" :class="['w-4 h-4', group.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                </svg>
                <!-- Trending Up -->
                <svg v-else-if="group.icon === 'trending-up'" :class="['w-4 h-4', group.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <!-- Receipt -->
                <svg v-else-if="group.icon === 'receipt'" :class="['w-4 h-4', group.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                </svg>
              </div>
              <div>
                <span class="text-sm font-medium text-slate-900">{{ group.label }}</span>
                <span class="text-xs text-slate-500 ml-2">({{ group.accounts.length }} {{ t('finance.accounting.accounts') }})</span>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-sm font-semibold text-slate-900">{{ appStore.formatPrice(group.totalMinor) }}</span>
              <svg
                :class="['w-4 h-4 text-slate-400 transition-transform duration-200', isSectionOpen(group.id) ? 'rotate-180' : '']"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </button>

          <!-- Accordion Content -->
          <div v-if="isSectionOpen(group.id)" class="bg-slate-50 border-t border-slate-100">
            <div
              v-for="account in group.accounts"
              :key="account.number"
              class="flex items-center justify-between px-4 py-3 pl-16 border-b border-slate-100 last:border-b-0"
            >
              <div class="flex items-center gap-3">
                <span class="text-xs font-mono text-slate-400 w-10">{{ account.number }}</span>
                <span class="text-sm text-slate-700">{{ account.name }}</span>
              </div>
              <span class="text-sm font-medium text-slate-900">{{ appStore.formatPrice(account.balanceMinor) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Journal Entries -->
    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-sm font-semibold text-slate-900">{{ t('finance.accounting.journal') }}</h3>
      </div>

      <!-- Mobile: Cards -->
      <div v-if="isMobile" class="divide-y divide-slate-100">
        <div
          v-for="entry in financeStore.journalEntries"
          :key="entry.id"
          class="p-4"
        >
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs text-slate-500">{{ appStore.formatDate(entry.date) }}</span>
            <span class="text-xs font-mono text-slate-400">{{ entry.reference }}</span>
          </div>
          <p class="text-sm text-slate-900 mb-2">{{ entry.description }}</p>
          <div class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
              <span class="text-slate-500">{{ t('finance.accounting.debit') }}:</span>
              <span class="font-mono text-slate-700">{{ entry.account }}</span>
              <span class="text-slate-400">/</span>
              <span class="text-slate-500">{{ t('finance.accounting.credit') }}:</span>
              <span class="font-mono text-slate-700">{{ entry.contraAccount }}</span>
            </div>
            <span class="font-semibold text-slate-900">
              {{ appStore.formatPrice(entry.debitMinor > 0 ? entry.debitMinor : entry.creditMinor) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Desktop: Table -->
      <div v-else>
        <BTable
          :columns="journalColumns"
          :data="journalData as Record<string, unknown>[]"
          :total="journalData.length"
          :per-page="25"
          :empty-title="t('finance.accounting.noEntries')"
          :empty-message="t('finance.accounting.noEntriesDescription')"
        >
          <template #cell-date="{ row }">
            <span class="text-sm text-slate-500">{{ appStore.formatDate(String((row as Record<string, unknown>).date)) }}</span>
          </template>

          <template #cell-description="{ row }">
            <div>
              <span class="text-sm text-slate-900">{{ (row as Record<string, unknown>).description }}</span>
              <span class="text-xs text-slate-400 ml-2 font-mono">{{ (row as Record<string, unknown>).reference }}</span>
            </div>
          </template>

          <template #cell-account="{ row }">
            <span class="text-sm font-mono text-slate-700">{{ (row as Record<string, unknown>).account }}</span>
          </template>

          <template #cell-contraAccount="{ row }">
            <span class="text-sm font-mono text-slate-700">{{ (row as Record<string, unknown>).contraAccount }}</span>
          </template>

          <template #cell-amount="{ row }">
            <span class="text-sm font-semibold text-slate-900">
              {{ appStore.formatPrice(Number((row as Record<string, unknown>).amount)) }}
            </span>
          </template>
        </BTable>
      </div>
    </div>
  </div>
</template>
