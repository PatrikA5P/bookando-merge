<script setup lang="ts">
/**
 * ReportsTab -- Berichte generieren und herunterladen
 *
 * Features:
 * - Datumsbereich-Auswahl (Monat, Quartal, Jahr, Benutzerdefiniert)
 * - Berichtskarten mit Mini-Chart-Platzhalter
 * - PDF-Download und Generierung
 * - Benutzerdefinierter Report Builder (Platzhalter)
 */
import { ref, computed } from 'vue';
import BButton from '@/components/ui/BButton.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BInput from '@/components/ui/BInput.vue';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useI18n } from '@/composables/useI18n';
import { BUTTON_STYLES, CARD_STYLES, BADGE_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const { isMobile } = useBreakpoint();

// Date range
const dateRange = ref('this-month');
const customFrom = ref('');
const customTo = ref('');

const dateRangeOptions = [
  { value: 'this-month', label: t('tools.reports.thisMonth') || 'Dieser Monat' },
  { value: 'last-month', label: t('tools.reports.lastMonth') || 'Letzter Monat' },
  { value: 'this-quarter', label: t('tools.reports.thisQuarter') || 'Dieses Quartal' },
  { value: 'this-year', label: t('tools.reports.thisYear') || 'Dieses Jahr' },
  { value: 'custom', label: t('tools.reports.custom') || 'Benutzerdefiniert' },
];

const showCustomRange = computed(() => dateRange.value === 'custom');

interface Report {
  id: string;
  title: string;
  description: string;
  icon: string;
  color: string;
  lastGenerated?: string;
}

const reports = ref<Report[]>([
  {
    id: 'customer-growth',
    title: t('tools.reports.customerGrowth') || 'Kundenentwicklung',
    description: t('tools.reports.customerGrowthDesc') || 'Neukunden, Abwanderung und Wachstumsrate im Zeitverlauf',
    icon: 'users',
    color: 'emerald',
    lastGenerated: '2025-06-01',
  },
  {
    id: 'revenue-analysis',
    title: t('tools.reports.revenueAnalysis') || 'Umsatzanalyse',
    description: t('tools.reports.revenueAnalysisDesc') || 'Umsatz nach Dienstleistung, Mitarbeiter und Zeitraum',
    icon: 'currency',
    color: 'purple',
    lastGenerated: '2025-05-28',
  },
  {
    id: 'appointment-load',
    title: t('tools.reports.appointmentLoad') || 'Terminauslastung',
    description: t('tools.reports.appointmentLoadDesc') || 'Auslastung nach Tageszeit, Wochentag und Mitarbeiter',
    icon: 'calendar',
    color: 'blue',
    lastGenerated: '2025-06-02',
  },
  {
    id: 'employee-performance',
    title: t('tools.reports.employeePerformance') || 'Mitarbeiter-Performance',
    description: t('tools.reports.employeePerformanceDesc') || 'Umsatz, Auslastung und Kundenzufriedenheit pro Mitarbeiter',
    icon: 'chart',
    color: 'amber',
  },
  {
    id: 'commission-report',
    title: t('tools.reports.commissionReport') || 'Kommissionsreport',
    description: t('tools.reports.commissionReportDesc') || 'Provisionen, Bonuszahlungen und Abrechnungsdetails',
    icon: 'banknote',
    color: 'rose',
    lastGenerated: '2025-05-15',
  },
]);

const generatingId = ref<string | null>(null);

function generateReport(reportId: string) {
  generatingId.value = reportId;
  setTimeout(() => {
    generatingId.value = null;
    const report = reports.value.find(r => r.id === reportId);
    if (report) {
      report.lastGenerated = new Date().toISOString().split('T')[0];
    }
    toast.success(t('tools.reports.generated') || 'Bericht wurde generiert');
  }, 2000);
}

function downloadPdf(reportId: string) {
  toast.info(t('tools.reports.downloading') || 'PDF wird heruntergeladen...');
}
</script>

<template>
  <div class="space-y-6">
    <!-- Date Range Selector -->
    <div :class="CARD_STYLES.base" class="p-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
        <div class="w-full sm:w-56">
          <BSelect
            v-model="dateRange"
            :options="dateRangeOptions"
            :label="t('tools.reports.dateRange') || 'Zeitraum'"
          />
        </div>
        <template v-if="showCustomRange">
          <div class="w-full sm:w-44">
            <BInput
              v-model="customFrom"
              type="date"
              :label="t('tools.reports.from') || 'Von'"
            />
          </div>
          <div class="w-full sm:w-44">
            <BInput
              v-model="customTo"
              type="date"
              :label="t('tools.reports.to') || 'Bis'"
            />
          </div>
        </template>
      </div>
    </div>

    <!-- Report Cards Grid -->
    <div :class="GRID_STYLES.cols3">
      <div
        v-for="report in reports"
        :key="report.id"
        :class="CARD_STYLES.base"
        class="flex flex-col"
      >
        <div class="p-5 flex-1">
          <!-- Icon & Title -->
          <div class="flex items-start gap-3 mb-3">
            <div
              :class="[
                'w-10 h-10 rounded-lg flex items-center justify-center shrink-0',
                report.color === 'emerald' ? 'bg-emerald-100 text-emerald-600' : '',
                report.color === 'purple' ? 'bg-purple-100 text-purple-600' : '',
                report.color === 'blue' ? 'bg-blue-100 text-blue-600' : '',
                report.color === 'amber' ? 'bg-amber-100 text-amber-600' : '',
                report.color === 'rose' ? 'bg-rose-100 text-rose-600' : '',
              ]"
            >
              <!-- Users icon -->
              <svg v-if="report.icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <!-- Currency icon -->
              <svg v-else-if="report.icon === 'currency'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <!-- Calendar icon -->
              <svg v-else-if="report.icon === 'calendar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <!-- Chart icon -->
              <svg v-else-if="report.icon === 'chart'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <!-- Banknote icon -->
              <svg v-else-if="report.icon === 'banknote'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="text-sm font-semibold text-slate-900">{{ report.title }}</h3>
              <p class="text-xs text-slate-500 mt-0.5">{{ report.description }}</p>
            </div>
          </div>

          <!-- Mini Chart Placeholder -->
          <div class="h-16 bg-slate-50 rounded-lg border border-slate-100 flex items-end justify-between px-3 pb-2 mb-3">
            <div
              v-for="i in 7"
              :key="i"
              :class="[
                'w-3 rounded-sm',
                report.color === 'emerald' ? 'bg-emerald-300' : '',
                report.color === 'purple' ? 'bg-purple-300' : '',
                report.color === 'blue' ? 'bg-blue-300' : '',
                report.color === 'amber' ? 'bg-amber-300' : '',
                report.color === 'rose' ? 'bg-rose-300' : '',
              ]"
              :style="{ height: `${20 + Math.random() * 80}%` }"
            />
          </div>

          <!-- Last generated info -->
          <div v-if="report.lastGenerated" class="text-xs text-slate-400">
            {{ t('tools.reports.lastGenerated') || 'Zuletzt generiert' }}: {{ report.lastGenerated }}
          </div>
        </div>

        <!-- Actions -->
        <div class="px-5 pb-4 flex items-center gap-2">
          <BButton
            variant="primary"
            size="sm"
            :loading="generatingId === report.id"
            class="flex-1 !text-xs"
            @click="generateReport(report.id)"
          >
            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ t('tools.reports.generate') || 'Generieren' }}
          </BButton>
          <BButton
            variant="secondary"
            size="sm"
            class="!text-xs"
            :disabled="!report.lastGenerated"
            @click="downloadPdf(report.id)"
          >
            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            PDF
          </BButton>
        </div>
      </div>

      <!-- Custom Report Builder Placeholder -->
      <div :class="CARD_STYLES.empty" class="min-h-[280px]">
        <svg class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
        </svg>
        <h3 class="text-sm font-semibold">{{ t('tools.reports.customBuilder') || 'Eigenen Report erstellen' }}</h3>
        <p class="text-xs mt-1">{{ t('tools.reports.customBuilderDesc') || 'Erstellen Sie benutzerdefinierte Berichte mit Ihren Kennzahlen' }}</p>
      </div>
    </div>
  </div>
</template>
