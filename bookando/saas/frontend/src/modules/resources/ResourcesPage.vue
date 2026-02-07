<script setup lang="ts">
/**
 * Ressourcen-Modul — Standorte, Raeume, Fahrzeuge & Equipment
 *
 * Refactored: Einheitliches Resource-Modell mit ResourceType-Discriminator.
 * Alle Formulare als BFormPanel (SlideIn), keine BModal.
 * Neuer Tab: Fahrzeuge (VEHICLE).
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import LocationsTab from './components/LocationsTab.vue';
import RoomsTab from './components/RoomsTab.vue';
import VehiclesTab from './components/VehiclesTab.vue';
import EquipmentTab from './components/EquipmentTab.vue';
import ResourceFormPanel from './components/ResourceFormPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import { useResourcesStore } from '@/stores/resources';
import type { Resource, ResourceType } from '@/stores/resources';

const { t } = useI18n();
const designStore = useDesignStore();
const store = useResourcesStore();

const activeTab = ref('locations');

const tabs = computed<Tab[]>(() => [
  { id: 'locations', label: t('resources.locations'), badge: store.locationCount },
  { id: 'rooms', label: t('resources.rooms'), badge: store.roomCount },
  { id: 'vehicles', label: 'Fahrzeuge', badge: store.vehicleCount },
  { id: 'equipment', label: t('resources.equipment'), badge: store.equipmentCount },
]);

// ── ResourceFormPanel State ──────────────────────────────────────────────
const showFormPanel = ref(false);
const editingResource = ref<Resource | null>(null);
const formResourceType = ref<ResourceType>('LOCATION');

const TAB_TO_TYPE: Record<string, ResourceType> = {
  locations: 'LOCATION',
  rooms: 'ROOM',
  vehicles: 'VEHICLE',
  equipment: 'EQUIPMENT',
};

function handleCreate() {
  formResourceType.value = TAB_TO_TYPE[activeTab.value] || 'LOCATION';
  editingResource.value = null;
  showFormPanel.value = true;
}

function handleEdit(resource: Resource) {
  formResourceType.value = resource.resourceType;
  editingResource.value = resource;
  showFormPanel.value = true;
}

function handleSaved() {
  showFormPanel.value = false;
  editingResource.value = null;
}

function handleDeleted() {
  showFormPanel.value = false;
  editingResource.value = null;
}

// FAB label changes per tab
const fabLabel = computed(() => {
  switch (activeTab.value) {
    case 'locations': return t('resources.newLocation');
    case 'rooms': return t('resources.newRoom');
    case 'vehicles': return 'Neues Fahrzeug';
    case 'equipment': return t('resources.newEquipment');
    default: return t('resources.newResource');
  }
});
</script>

<template>
  <ModuleLayout
    module-name="resources"
    :title="t('resources.title')"
    :subtitle="t('resources.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="true"
    :fab-label="fabLabel"
    @tab-change="(id: string) => activeTab = id"
    @fab-click="handleCreate"
  >
    <template #header-actions>
      <button
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
        @click="handleCreate"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ fabLabel }}
      </button>
    </template>

    <!-- Standorte -->
    <LocationsTab
      v-if="activeTab === 'locations'"
      @edit="handleEdit"
      @create="handleCreate"
    />

    <!-- Raeume -->
    <RoomsTab
      v-else-if="activeTab === 'rooms'"
      @edit="handleEdit"
      @create="handleCreate"
    />

    <!-- Fahrzeuge -->
    <VehiclesTab
      v-else-if="activeTab === 'vehicles'"
      @edit="handleEdit"
      @create="handleCreate"
    />

    <!-- Equipment -->
    <EquipmentTab
      v-else-if="activeTab === 'equipment'"
      @edit="handleEdit"
      @create="handleCreate"
    />
  </ModuleLayout>

  <!-- Resource Form Panel (SlideIn) -->
  <ResourceFormPanel
    v-model="showFormPanel"
    :resource-type="formResourceType"
    :resource="editingResource"
    @saved="handleSaved"
    @deleted="handleDeleted"
  />
</template>
