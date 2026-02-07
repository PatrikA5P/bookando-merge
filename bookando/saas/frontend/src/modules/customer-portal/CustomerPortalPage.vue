<script setup lang="ts">
/**
 * Kundenportal — Self-Service fuer Kunden
 *
 * Tabs: Dashboard, Buchungen, Ausbildungskarten, Academy
 * Zeigt dem eingeloggten Kunden seine Daten und Buchungen.
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import PortalDashboardTab from './components/PortalDashboardTab.vue';
import PortalBookingsTab from './components/PortalBookingsTab.vue';
import PortalTrainingCardsTab from './components/PortalTrainingCardsTab.vue';
import PortalAcademyTab from './components/PortalAcademyTab.vue';
import { useI18n } from '@/composables/useI18n';
import { useBookingsStore } from '@/stores/bookings';
import { useTrainingCardsStore } from '@/stores/training-cards';
import { useAcademyStore } from '@/stores/academy';

const { t } = useI18n();
const bookingsStore = useBookingsStore();
const trainingCardsStore = useTrainingCardsStore();
const academyStore = useAcademyStore();

const activeTab = ref('dashboard');

const tabs = computed<Tab[]>(() => [
  { id: 'dashboard', label: 'Uebersicht' },
  { id: 'bookings', label: 'Buchungen', badge: bookingsStore.bookingCount },
  { id: 'training-cards', label: 'Ausbildungskarten', badge: trainingCardsStore.assignments.length },
  { id: 'academy', label: 'Academy', badge: academyStore.enrollments.length },
]);
</script>

<template>
  <ModuleLayout
    module-name="customer-portal"
    title="Mein Portal"
    subtitle="Ihre Buchungen, Kurse und Ausbildungskarten"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <PortalDashboardTab v-if="activeTab === 'dashboard'" />
    <PortalBookingsTab v-else-if="activeTab === 'bookings'" />
    <PortalTrainingCardsTab v-else-if="activeTab === 'training-cards'" />
    <PortalAcademyTab v-else-if="activeTab === 'academy'" />
  </ModuleLayout>
</template>
