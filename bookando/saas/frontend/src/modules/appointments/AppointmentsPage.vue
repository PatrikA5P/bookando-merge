<script setup lang="ts">
/**
 * Termine-Modul — Buchungen & Termine
 *
 * Container-Seite mit Router-View für Kalender- und Listenansicht.
 * TODO: Vue Query, Drag & Drop, Calendar-Sync, Zuweisung
 */
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

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
</script>

<template>
  <ModuleLayout
    module-name="appointments"
    :title="t('appointments.title')"
    :subtitle="t('appointments.calendar')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="true"
    :fab-label="t('appointments.newAppointment')"
    @tab-change="onTabChange"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <button
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ t('appointments.newAppointment') }}
        </button>
      </div>
    </template>

    <router-view />
  </ModuleLayout>
</template>
