<script setup lang="ts">
/**
 * AppointmentsPage — Termine-Modul Container
 *
 * Wrapper page using ModuleLayout with two tabs (Calendar / List).
 * Provides:
 * - Gradient hero header via ModuleLayout
 * - Tab routing between calendar and list views
 * - Desktop: primary "New Appointment" button in header
 * - Mobile: FAB for creating new appointment
 * - Shared modal state lifted here so both views can trigger it
 */
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import AppointmentModal from './components/AppointmentModal.vue';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import { useAppointmentsStore } from '@/stores/appointments';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();
const designStore = useDesignStore();
const store = useAppointmentsStore();

// Load data on mount
onMounted(() => {
  store.fetchAll();
});

// Tabs
const tabs = computed<Tab[]>(() => [
  { id: 'calendar', label: t('appointments.calendar'), icon: 'calendar' },
  { id: 'list', label: t('appointments.list'), icon: 'list' },
]);

const activeTab = computed(() => {
  const child = route.name as string | undefined;
  if (child?.includes('list')) return 'list';
  return 'calendar';
});

function onTabChange(tabId: string) {
  if (tabId === 'list') {
    router.push({ name: 'appointments-list' });
  } else {
    router.push({ name: 'appointments-calendar' });
  }
}

// Shared modal state
const showCreateModal = ref(false);

function openCreateModal() {
  showCreateModal.value = true;
}

function onAppointmentCreated() {
  showCreateModal.value = false;
}
</script>

<template>
  <ModuleLayout
    module-name="appointments"
    :title="t('appointments.title')"
    :subtitle="t('appointments.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="true"
    :fab-label="t('appointments.newAppointment')"
    @tab-change="onTabChange"
    @fab-click="openCreateModal"
  >
    <!-- Hero icon -->
    <template #hero-icon>
      <svg class="w-6 h-6 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </template>

    <!-- Hero watermark -->
    <template #hero-watermark>
      <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </template>

    <!-- Tab icons -->
    <template #tab-icon-calendar>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </template>

    <template #tab-icon-list>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
      </svg>
    </template>

    <!-- Desktop primary action button -->
    <template #header-actions>
      <button
        class="px-4 py-2.5 text-sm font-bold rounded-xl bg-brand-600 text-white hover:bg-brand-700 transition-colors hidden md:inline-flex items-center gap-2 shadow-sm"
        @click="openCreateModal"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('appointments.newAppointment') }}
      </button>
    </template>

    <!-- Content: child route view -->
    <router-view @open-create="openCreateModal" />

    <!-- Create Modal (shared across views) -->
    <AppointmentModal
      v-model="showCreateModal"
      @created="onAppointmentCreated"
    />
  </ModuleLayout>
</template>
