<script setup lang="ts">
/**
 * PayrollTab — Lohnbuchhaltung (Swissdec ELM 5.0/5.5)
 *
 * Features:
 * - Mitarbeiter-Lohnübersicht (BTable)
 * - Monatlich/Jährlich Toggle
 * - Swissdec-Meldestatus pro Mitarbeiter (BBadge)
 * - Sozialversicherungs-Zusammenfassung als Cards
 * - Button: Swissdec-Meldung erstellen
 */
import { ref, computed } from 'vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BTable from '@/components/ui/BTable.vue';
import BToggle from '@/components/ui/BToggle.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useFinanceStore } from '@/stores/finance';
import type { SalaryDeclaration } from '@/stores/finance';
import { CARD_STYLES, GRID_STYLES, TABLE_STYLES, BADGE_STYLES, BUTTON_STYLES } from '@/design';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const appStore = useAppStore();
const financeStore = useFinanceStore();

// Monthly/Yearly toggle
const isYearly = ref(false);

// Salary data (with yearly calculation)
const salaryData = computed(() =>
  financeStore.salaryDeclarations.map(s => ({
    ...s,
    displayGross: isYearly.value ? s.grossMinor * 12 : s.grossMinor,
    displayAhv: isYearly.value ? s.ahvMinor * 12 : s.ahvMinor,
    displayAlv: isYearly.value ? s.alvMinor * 12 : s.alvMinor,
    displayBvg: isYearly.value ? s.bvgMinor * 12 : s.bvgMinor,
    displayNbu: isYearly.value ? s.nbuMinor * 12 : s.nbuMinor,
    displayTax: isYearly.value ? s.taxMinor * 12 : s.taxMinor,
    displayNet: isYearly.value ? s.netMinor * 12 : s.netMinor,
  }))
);

// Table columns
const columns = computed(() => [
  { key: 'employeeName', label: t('finance.payroll.employee'), sortable: true },
  { key: 'displayGross', label: t('finance.payroll.gross'), align: 'right' as const },
  { key: 'displayAhv', label: 'AHV/IV/EO', align: 'right' as const },
  { key: 'displayAlv', label: 'ALV', align: 'right' as const },
  { key: 'displayBvg', label: 'BVG', align: 'right' as const },
  { key: 'displayNbu', label: 'NBU', align: 'right' as const },
  { key: 'displayTax', label: t('finance.payroll.tax'), align: 'right' as const },
  { key: 'displayNet', label: t('finance.payroll.net'), align: 'right' as const },
  { key: 'status', label: 'Status' },
]);

// Social insurance summary cards
const socialInsuranceCards = computed(() => {
  const multiplier = isYearly.value ? 12 : 1;
  const totalAhv = financeStore.salaryDeclarations.reduce((sum, s) => sum + s.ahvMinor, 0) * multiplier;
  const totalAlv = financeStore.salaryDeclarations.reduce((sum, s) => sum + s.alvMinor, 0) * multiplier;
  const totalBvg = financeStore.salaryDeclarations.reduce((sum, s) => sum + s.bvgMinor, 0) * multiplier;
  const totalNbu = financeStore.salaryDeclarations.reduce((sum, s) => sum + s.nbuMinor, 0) * multiplier;

  return [
    { label: 'AHV/IV/EO', value: totalAhv, color: 'text-blue-600', bgColor: 'bg-blue-50', rate: '5.3%' },
    { label: 'ALV', value: totalAlv, color: 'text-indigo-600', bgColor: 'bg-indigo-50', rate: '1.1%' },
    { label: 'BVG', value: totalBvg, color: 'text-purple-600', bgColor: 'bg-purple-50', rate: '~5.0%' },
    { label: 'NBU', value: totalNbu, color: 'text-amber-600', bgColor: 'bg-amber-50', rate: '~0.7%' },
  ];
});

// Total row
const totalGrossMinor = computed(() => {
  const multiplier = isYearly.value ? 12 : 1;
  return financeStore.totalSalaryGrossMinor * multiplier;
});

const totalNetMinor = computed(() => {
  const multiplier = isYearly.value ? 12 : 1;
  return financeStore.totalSalaryNetMinor * multiplier;
});

function getStatusKey(status: string): string {
  const map: Record<string, string> = {
    DRAFT: 'draft',
    SUBMITTED: 'pending',
    CONFIRMED: 'confirmed',
  };
  return map[status] || 'draft';
}

function handleSubmitDeclaration(salary: SalaryDeclaration) {
  financeStore.submitSalaryDeclaration(salary.id);
  toast.success(t('finance.payroll.submittedSuccess'));
}

function handleCreateSwissdec() {
  toast.info(t('finance.payroll.swissdecCreating'));
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header with Toggle -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">{{ t('finance.payroll.title') }}</h3>
        <p class="text-xs text-slate-500 mt-0.5">{{ t('finance.payroll.swissdecCompliant') }}</p>
      </div>
      <div class="flex items-center gap-4">
        <BToggle
          v-model="isYearly"
          :label="t('finance.payroll.yearlyView')"
        />
        <BButton variant="primary" @click="handleCreateSwissdec">
          <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          {{ t('finance.payroll.createSwissdec') }}
        </BButton>
      </div>
    </div>

    <!-- Social Insurance Summary Cards -->
    <div :class="GRID_STYLES.cols4">
      <div
        v-for="card in socialInsuranceCards"
        :key="card.label"
        :class="CARD_STYLES.stat"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ card.label }}</span>
          <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', card.bgColor]">
            <svg :class="['w-4 h-4', card.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
        </div>
        <p class="text-xl font-bold text-slate-900">{{ appStore.formatPrice(card.value) }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ t('finance.payroll.rate') }}: {{ card.rate }}</p>
      </div>
    </div>

    <!-- Salary Overview -->
    <!-- Mobile: Cards -->
    <div v-if="isMobile" class="space-y-3">
      <div
        v-for="salary in salaryData"
        :key="salary.id"
        :class="CARD_STYLES.listItem"
      >
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="text-sm font-semibold text-slate-900">{{ salary.employeeName }}</p>
            <p class="text-xs text-slate-500">
              {{ salary.month }}/{{ salary.year }}
            </p>
          </div>
          <BBadge :status="getStatusKey(salary.status)" dot>
            {{ t(`finance.payroll.status.${salary.status}`) }}
          </BBadge>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs">
          <div>
            <span class="text-slate-500">{{ t('finance.payroll.gross') }}:</span>
            <span class="ml-1 font-medium text-slate-900">{{ appStore.formatPrice(salary.displayGross) }}</span>
          </div>
          <div>
            <span class="text-slate-500">{{ t('finance.payroll.net') }}:</span>
            <span class="ml-1 font-bold text-emerald-600">{{ appStore.formatPrice(salary.displayNet) }}</span>
          </div>
          <div>
            <span class="text-slate-500">AHV:</span>
            <span class="ml-1 text-slate-700">{{ appStore.formatPrice(salary.displayAhv) }}</span>
          </div>
          <div>
            <span class="text-slate-500">ALV:</span>
            <span class="ml-1 text-slate-700">{{ appStore.formatPrice(salary.displayAlv) }}</span>
          </div>
          <div>
            <span class="text-slate-500">BVG:</span>
            <span class="ml-1 text-slate-700">{{ appStore.formatPrice(salary.displayBvg) }}</span>
          </div>
          <div>
            <span class="text-slate-500">NBU:</span>
            <span class="ml-1 text-slate-700">{{ appStore.formatPrice(salary.displayNbu) }}</span>
          </div>
        </div>

        <div v-if="salary.status === 'DRAFT'" class="mt-3 pt-2 border-t border-slate-100">
          <button
            :class="BUTTON_STYLES.ghost"
            class="!px-3 !py-1.5 text-xs"
            @click="handleSubmitDeclaration(salary)"
          >
            {{ t('finance.payroll.submitDeclaration') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Desktop: Table -->
    <BTable
      v-else
      :columns="columns"
      :data="salaryData as Record<string, unknown>[]"
      :total="salaryData.length"
      :per-page="25"
      :empty-title="t('finance.payroll.emptyTitle')"
      :empty-message="t('finance.payroll.emptyDescription')"
    >
      <template #cell-employeeName="{ row }">
        <div>
          <span class="text-sm font-medium text-slate-900">{{ (row as Record<string, unknown>).employeeName }}</span>
          <span class="text-xs text-slate-400 ml-2">{{ (row as Record<string, unknown>).month }}/{{ (row as Record<string, unknown>).year }}</span>
        </div>
      </template>

      <template #cell-displayGross="{ row }">
        <span class="text-sm font-medium text-slate-900">{{ appStore.formatPrice(Number((row as Record<string, unknown>).displayGross)) }}</span>
      </template>

      <template #cell-displayAhv="{ row }">
        <span class="text-sm text-slate-700">{{ appStore.formatPrice(Number((row as Record<string, unknown>).displayAhv)) }}</span>
      </template>

      <template #cell-displayAlv="{ row }">
        <span class="text-sm text-slate-700">{{ appStore.formatPrice(Number((row as Record<string, unknown>).displayAlv)) }}</span>
      </template>

      <template #cell-displayBvg="{ row }">
        <span class="text-sm text-slate-700">{{ appStore.formatPrice(Number((row as Record<string, unknown>).displayBvg)) }}</span>
      </template>

      <template #cell-displayNbu="{ row }">
        <span class="text-sm text-slate-700">{{ appStore.formatPrice(Number((row as Record<string, unknown>).displayNbu)) }}</span>
      </template>

      <template #cell-displayTax="{ row }">
        <span class="text-sm text-slate-700">{{ appStore.formatPrice(Number((row as Record<string, unknown>).displayTax)) }}</span>
      </template>

      <template #cell-displayNet="{ row }">
        <span class="text-sm font-bold text-emerald-600">{{ appStore.formatPrice(Number((row as Record<string, unknown>).displayNet)) }}</span>
      </template>

      <template #cell-status="{ row }">
        <div class="flex items-center gap-2">
          <BBadge :status="getStatusKey(String((row as Record<string, unknown>).status))" dot>
            {{ t(`finance.payroll.status.${(row as Record<string, unknown>).status}`) }}
          </BBadge>
          <button
            v-if="(row as Record<string, unknown>).status === 'DRAFT'"
            :class="BUTTON_STYLES.icon"
            class="!p-1"
            :title="t('finance.payroll.submitDeclaration')"
            @click.stop="handleSubmitDeclaration(row as unknown as SalaryDeclaration)"
          >
            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </div>
      </template>
    </BTable>

    <!-- Totals Footer -->
    <div :class="CARD_STYLES.base">
      <div class="p-4 flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold text-slate-900">{{ t('finance.payroll.totalPayroll') }}</p>
          <p class="text-xs text-slate-500">
            {{ salaryData.length }} {{ t('finance.payroll.employees') }} &middot;
            {{ isYearly ? t('finance.payroll.yearly') : t('finance.payroll.monthly') }}
          </p>
        </div>
        <div class="flex items-center gap-6">
          <div class="text-right">
            <p class="text-xs text-slate-500">{{ t('finance.payroll.gross') }}</p>
            <p class="text-sm font-bold text-slate-900">{{ appStore.formatPrice(totalGrossMinor) }}</p>
          </div>
          <div class="text-right">
            <p class="text-xs text-slate-500">{{ t('finance.payroll.net') }}</p>
            <p class="text-sm font-bold text-emerald-600">{{ appStore.formatPrice(totalNetMinor) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
