<script setup lang="ts">
/**
 * Ressourcen-Modul — Standorte, Räume & Equipment
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import LocationsTab from './components/LocationsTab.vue';
import RoomsTab from './components/RoomsTab.vue';
import EquipmentTab from './components/EquipmentTab.vue';
import { useI18n } from '@/composables/useI18n';
import { useResourcesStore } from '@/stores/resources';

const { t } = useI18n();
const store = useResourcesStore();

const activeTab = ref('locations');

const tabs = computed<Tab[]>(() => [
  { id: 'locations', label: t('resources.locations'), badge: store.locations?.length },
  { id: 'rooms', label: t('resources.rooms'), badge: store.rooms?.length },
  { id: 'equipment', label: t('resources.equipment'), badge: store.equipment?.length },
]);
</script>

<template>
  <ModuleLayout
    module-name="resources"
    :title="t('resources.title')"
    :subtitle="t('resources.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="true"
    :fab-label="t('resources.newResource')"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <button
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('resources.newResource') }}
      </button>
    </template>

    <!-- Standorte -->
    <LocationsTab v-if="activeTab === 'locations'" />

    <!-- Räume -->
    <RoomsTab v-else-if="activeTab === 'rooms'" />

    <!-- Equipment -->
    <EquipmentTab v-else-if="activeTab === 'equipment'" />
  </ModuleLayout>
</template>
