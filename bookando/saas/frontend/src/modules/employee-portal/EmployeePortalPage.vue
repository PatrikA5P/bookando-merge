<script setup lang="ts">
/**
 * Mitarbeiterpanel — Self-Service fuer Mitarbeiter
 *
 * Tabs: Dashboard, Kalender, Schichten & Zeiterfassung
 * Zeigt dem eingeloggten Mitarbeiter seine Sessions, Verfuegbarkeit und Schichten.
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import EmployeeDashboardTab from './components/EmployeeDashboardTab.vue';
import EmployeeCalendarTab from './components/EmployeeCalendarTab.vue';
import EmployeeShiftsTab from './components/EmployeeShiftsTab.vue';
import { useI18n } from '@/composables/useI18n';
import { useSessionsStore } from '@/stores/sessions';

const { t } = useI18n();
const sessionsStore = useSessionsStore();

const activeTab = ref('dashboard');

const tabs = computed<Tab[]>(() => [
  { id: 'dashboard', label: 'Dashboard', badge: sessionsStore.todaySessions.length },
  { id: 'calendar', label: 'Kalender' },
  { id: 'shifts', label: 'Schichten' },
]);
</script>

<template>
  <ModuleLayout
    module-name="employee-portal"
    title="Mein Arbeitsplatz"
    subtitle="Sessions, Kalender und Zeiterfassung"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <EmployeeDashboardTab v-if="activeTab === 'dashboard'" />
    <EmployeeCalendarTab v-else-if="activeTab === 'calendar'" />
    <EmployeeShiftsTab v-else-if="activeTab === 'shifts'" />
  </ModuleLayout>
</template>
