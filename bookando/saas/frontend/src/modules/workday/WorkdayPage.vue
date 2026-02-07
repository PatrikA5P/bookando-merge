<script setup lang="ts">
/**
 * WorkdayPage — ArG-konforme Arbeitszeiterfassung
 *
 * Tabs: Zeiterfassung, Schichtplan, Abwesenheiten, Ueberstunden
 * Swiss ArG (Arbeitsgesetz) Art. 46 compliant time tracking,
 * shift management, absence requests, and overtime overview.
 *
 * Features:
 * - ModuleLayout with vertical tabs (desktop) / horizontal pills (mobile)
 * - Tab icon slots with inline SVGs
 * - ClockWidget in header-actions area
 * - Overtime tab with summary cards + history placeholder
 * - All text via useI18n t()
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import TimeTrackingTab from './components/TimeTrackingTab.vue';
import ShiftPlanTab from './components/ShiftPlanTab.vue';
import AbsencesTab from './components/AbsencesTab.vue';
import ClockWidget from './components/ClockWidget.vue';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import { useWorkdayStore } from '@/stores/workday';
import { CARD_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const designStore = useDesignStore();
const store = useWorkdayStore();

const design = computed(() => designStore.getModuleDesign('workday'));

const activeTab = ref('timetracking');

const tabs = computed<Tab[]>(() => [
  { id: 'timetracking', label: t('workday.timeTracking') },
  { id: 'shifts', label: t('workday.shifts') },
  { id: 'absences', label: t('workday.absences') },
  { id: 'overtime', label: t('workday.overtime') },
]);

// Overtime summary cards
const overtimeCards = computed(() => [
  {
    label: t('workday.overtime'),
    value: `${store.overtimeHours.toFixed(1)}h`,
    sublabel: t('workday.weeklyOverview'),
    color: 'text-amber-600',
    bg: 'bg-amber-50',
    border: 'border-amber-200',
  },
  {
    label: t('workday.totalHours'),
    value: `${store.weeklyHours.toFixed(1)}h`,
    sublabel: '/ 42.5h',
    color: 'text-blue-600',
    bg: 'bg-blue-50',
    border: 'border-blue-200',
  },
  {
    label: t('workday.balance'),
    value: `${(store.weeklyHours - 42.5).toFixed(1)}h`,
    sublabel: store.weeklyHours >= 42.5 ? t('common.positive') : t('common.negative'),
    color: store.weeklyHours >= 42.5 ? 'text-emerald-600' : 'text-red-600',
    bg: store.weeklyHours >= 42.5 ? 'bg-emerald-50' : 'bg-red-50',
    border: store.weeklyHours >= 42.5 ? 'border-emerald-200' : 'border-red-200',
  },
]);

// Mock overtime history entries
const overtimeHistory = computed(() => {
  return store.weekDates.map((date, idx) => {
    const dailyData = store.dailyHours[idx];
    const overtime = dailyData ? Math.max(0, dailyData.netHours - 8.5) : 0;
    const d = new Date(date);
    const dayLabels = ['workday.weekdays.sun', 'workday.weekdays.mon', 'workday.weekdays.tue', 'workday.weekdays.wed', 'workday.weekdays.thu', 'workday.weekdays.fri', 'workday.weekdays.sat'];
    return {
      date,
      dayLabel: t(dayLabels[d.getDay()] || 'common.unknown'),
      netHours: dailyData?.netHours.toFixed(1) || '0.0',
      overtime: overtime.toFixed(1),
      hasOvertime: overtime > 0,
    };
  });
});
</script>

<template>
  <ModuleLayout
    module-name="workday"
    :title="t('workday.title')"
    :subtitle="t('workday.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <!-- Hero Icon -->
    <template #hero-icon>
      <svg class="w-6 h-6 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </template>

    <!-- Tab Icons -->
    <template #tab-icon-timetracking>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </template>
    <template #tab-icon-shifts>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </template>
    <template #tab-icon-absences>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </template>
    <template #tab-icon-overtime>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
      </svg>
    </template>

    <!-- Header Actions: ClockWidget -->
    <template #header-actions>
      <ClockWidget />
    </template>

    <!-- Zeiterfassung -->
    <TimeTrackingTab v-if="activeTab === 'timetracking'" />

    <!-- Schichtplan -->
    <ShiftPlanTab v-else-if="activeTab === 'shifts'" />

    <!-- Abwesenheiten -->
    <AbsencesTab v-else-if="activeTab === 'absences'" />

    <!-- Ueberstunden -->
    <div v-else-if="activeTab === 'overtime'" class="space-y-6 p-6">
      <!-- Summary Cards -->
      <div :class="GRID_STYLES.cols4Dense">
        <div
          v-for="card in overtimeCards"
          :key="card.label"
          :class="[CARD_STYLES.base, 'p-5']"
        >
          <div class="flex items-center gap-3">
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', card.bg]">
              <svg :class="['w-5 h-5', card.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <div>
              <p class="text-xs text-slate-500">{{ card.label }}</p>
              <p class="text-xl font-bold text-slate-900">{{ card.value }}</p>
              <p class="text-[10px] text-slate-400">{{ card.sublabel }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ArG Compliance Note -->
      <div :class="[CARD_STYLES.base, 'p-4 border-blue-200 bg-blue-50']">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <h4 class="text-sm font-semibold text-blue-800">{{ t('workday.argCompliance') }}</h4>
            <p class="text-xs text-blue-700 mt-1">{{ t('workday.argOvertimeNote') }}</p>
          </div>
        </div>
      </div>

      <!-- Overtime History Table -->
      <div :class="CARD_STYLES.base">
        <div :class="[CARD_STYLES.headerCompact, 'flex items-center justify-between']">
          <h3 class="text-sm font-semibold text-slate-900">{{ t('workday.overtimeHistory') }}</h3>
          <span class="text-xs text-slate-500">{{ t('workday.weeklyOverview') }}</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="p-3 text-xs font-semibold text-slate-500 uppercase">{{ t('common.day') }}</th>
                <th class="p-3 text-xs font-semibold text-slate-500 uppercase text-right">{{ t('workday.netHours') }}</th>
                <th class="p-3 text-xs font-semibold text-slate-500 uppercase text-right">{{ t('workday.overtime') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="entry in overtimeHistory"
                :key="entry.date"
                :class="entry.date === store.todayStr ? 'bg-amber-50/50' : ''"
                class="hover:bg-slate-50"
              >
                <td class="p-3 text-sm font-medium text-slate-700">
                  {{ entry.dayLabel }}
                  <span class="text-xs text-slate-400 ml-1">{{ entry.date }}</span>
                </td>
                <td class="p-3 text-sm text-slate-600 text-right font-mono">{{ entry.netHours }}h</td>
                <td class="p-3 text-sm text-right font-mono">
                  <span
                    :class="entry.hasOvertime ? 'text-amber-600 font-semibold' : 'text-slate-400'"
                  >
                    {{ entry.hasOvertime ? '+' : '' }}{{ entry.overtime }}h
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-slate-50 border-t-2 border-slate-200">
                <td class="p-3 text-sm font-bold text-slate-900">Total</td>
                <td class="p-3 text-sm font-bold text-slate-900 text-right font-mono">{{ store.weeklyHours.toFixed(1) }}h</td>
                <td class="p-3 text-sm font-bold text-right font-mono" :class="store.overtimeHours > 0 ? 'text-amber-600' : 'text-slate-400'">
                  {{ store.overtimeHours > 0 ? '+' : '' }}{{ store.overtimeHours.toFixed(1) }}h
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
