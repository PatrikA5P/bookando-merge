<script setup lang="ts">
/**
 * Arbeitstag-Modul — ArG-konforme Zeiterfassung
 *
 * Tabs: Dienstplan, Schichtplan, Zeiterfassung, Urlaub, Überstunden
 * ArG Art. 46 konforme Zeiterfassung, Schichtverwaltung, Abwesenheitsanträge.
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import TimeTrackingTab from './components/TimeTrackingTab.vue';
import ShiftPlanTab from './components/ShiftPlanTab.vue';
import AbsencesTab from './components/AbsencesTab.vue';
import ClockWidget from './components/ClockWidget.vue';
import { useI18n } from '@/composables/useI18n';
import { useWorkdayStore } from '@/stores/workday';

const { t } = useI18n();
const store = useWorkdayStore();

const activeTab = ref('timetracking');

const tabs = computed<Tab[]>(() => [
  { id: 'timetracking', label: t('workday.timeTracking') },
  { id: 'shifts', label: t('workday.shifts') },
  { id: 'absences', label: t('workday.absences') },
  { id: 'overtime', label: t('workday.overtime') },
]);
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
    <template #header-actions>
      <ClockWidget />
    </template>

    <!-- Zeiterfassung -->
    <TimeTrackingTab v-if="activeTab === 'timetracking'" />

    <!-- Schichtplan -->
    <ShiftPlanTab v-else-if="activeTab === 'shifts'" />

    <!-- Abwesenheiten -->
    <AbsencesTab v-else-if="activeTab === 'absences'" />

    <!-- Überstunden (Platzhalter) -->
    <div v-else-if="activeTab === 'overtime'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-amber-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ t('workday.overtime') }}</h3>
          <p class="text-sm text-slate-500 mt-1">Coming soon</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
