<script setup lang="ts">
/**
 * Finanzen-Modul — Swiss-compliant Finance Management
 *
 * GeBüV-konforme Buchhaltung, QR-Bill Rechnungen,
 * Swissdec Lohnbuchhaltung, MwSt-Verwaltung.
 *
 * Tabs: Übersicht, Rechnungen, Buchhaltung, MwSt, Lohnbuchhaltung, Gutscheine
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import OverviewTab from './components/OverviewTab.vue';
import InvoicesTab from './components/InvoicesTab.vue';
import AccountingTab from './components/AccountingTab.vue';
import PayrollTab from './components/PayrollTab.vue';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import { useFinanceStore } from '@/stores/finance';
import { BUTTON_STYLES } from '@/design';

const { t } = useI18n();
const designStore = useDesignStore();
const financeStore = useFinanceStore();

const activeTab = ref('overview');

const tabs = computed<Tab[]>(() => [
  { id: 'overview', label: t('finance.tabs.overview') },
  { id: 'invoices', label: t('finance.tabs.invoices'), badge: financeStore.overdueCount > 0 ? financeStore.overdueCount : undefined },
  { id: 'accounting', label: t('finance.tabs.accounting') },
  { id: 'vat', label: t('finance.tabs.vat') },
  { id: 'payroll', label: t('finance.tabs.payroll') },
  { id: 'vouchers', label: t('finance.tabs.vouchers') },
]);
</script>

<template>
  <ModuleLayout
    module-name="finance"
    :title="t('finance.title')"
    :subtitle="t('finance.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="activeTab === 'invoices'"
    :fab-label="t('finance.newInvoice')"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <button
          :class="BUTTON_STYLES.secondary"
          class="!bg-white/20 !border-white/30 !text-white text-xs hidden sm:inline-flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span>{{ t('finance.export') }}</span>
        </button>
      </div>
    </template>

    <!-- Übersicht -->
    <OverviewTab v-if="activeTab === 'overview'" />

    <!-- Rechnungen -->
    <InvoicesTab v-else-if="activeTab === 'invoices'" />

    <!-- Buchhaltung -->
    <AccountingTab v-else-if="activeTab === 'accounting'" />

    <!-- MwSt -->
    <div v-else-if="activeTab === 'vat'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ t('finance.vat.title') }}</h3>
          <p class="text-sm text-slate-500 mt-1">{{ t('finance.vat.placeholder') }}</p>
          <p class="text-xs text-slate-400 mt-2">{{ t('finance.vat.description') }}</p>
        </div>
      </div>
    </div>

    <!-- Lohnbuchhaltung -->
    <PayrollTab v-else-if="activeTab === 'payroll'" />

    <!-- Gutscheine -->
    <div v-else-if="activeTab === 'vouchers'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ t('finance.vouchers.title') }}</h3>
          <p class="text-sm text-slate-500 mt-1">{{ t('finance.vouchers.placeholder') }}</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
