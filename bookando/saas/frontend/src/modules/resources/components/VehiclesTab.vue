<script setup lang="ts">
/**
 * VehiclesTab — Fahrzeuge-Uebersicht
 *
 * Kartenbasierte Darstellung aller Fahrzeuge.
 * Nutzt das einheitliche Resource-Modell mit resourceType 'VEHICLE'.
 * Schweizer Fahrschul-Kontext: Fahrschulautos, Motorraeder etc.
 * CRUD ueber ResourceFormPanel (SlideIn).
 */
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useResourcesStore, RESOURCE_STATUS_COLORS } from '@/stores/resources';
import type { Resource, VehicleProperties } from '@/stores/resources';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { CARD_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const store = useResourcesStore();

const emit = defineEmits<{
  (e: 'edit', resource: Resource): void;
  (e: 'create'): void;
}>();

const search = computed({
  get: () => store.filters.search,
  set: (val: string) => store.setFilters({ search: val }),
});

function getProps(resource: Resource): VehicleProperties {
  return resource.properties as VehicleProperties;
}

async function handleDelete(resource: Resource) {
  if (await store.deleteResource(resource.id)) {
    toast.success(t('common.deletedSuccessfully'));
  }
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'ACTIVE': return 'bg-emerald-500';
    case 'MAINTENANCE': return 'bg-amber-500';
    case 'RETIRED': return 'bg-red-500';
    default: return 'bg-slate-400';
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'ACTIVE': return t('resources.available');
    case 'MAINTENANCE': return t('resources.maintenance');
    case 'RETIRED': return t('resources.closed');
    default: return status;
  }
}

function getVehicleSubtitle(resource: Resource): string {
  const p = getProps(resource);
  const parts: string[] = [];
  if (p.brand) parts.push(p.brand);
  if (p.model) parts.push(p.model);
  if (p.color) parts.push(p.color);
  return parts.join(' · ') || '—';
}
</script>

<template>
  <div>
    <!-- Search -->
    <div class="mb-6 max-w-md">
      <BSearchBar
        v-model="search"
        :placeholder="t('common.search') + '...'"
      />
    </div>

    <!-- Vehicle Cards -->
    <div
      v-if="store.filteredVehicles.length > 0"
      :class="GRID_STYLES.cols3"
    >
      <div
        v-for="vehicle in store.filteredVehicles"
        :key="vehicle.id"
        :class="CARD_STYLES.hover"
        class="p-5 cursor-pointer"
        @click="emit('edit', vehicle)"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-slate-900">{{ vehicle.name }}</h3>
              <p class="text-xs text-slate-500 mt-0.5">{{ getVehicleSubtitle(vehicle) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-1.5">
            <span :class="['w-2 h-2 rounded-full', getStatusColor(vehicle.status)]" />
            <span class="text-xs text-slate-600">{{ getStatusLabel(vehicle.status) }}</span>
          </div>
        </div>

        <!-- Details -->
        <div class="space-y-2 mb-4">
          <div v-if="getProps(vehicle).licensePlate" class="flex items-center gap-2 text-xs text-slate-600">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <span class="font-mono font-medium">{{ getProps(vehicle).licensePlate }}</span>
          </div>
          <div v-if="getProps(vehicle).category" class="flex items-center gap-2 text-xs text-slate-500">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <span>{{ getProps(vehicle).category }}</span>
          </div>
          <div v-if="vehicle.parentName" class="flex items-center gap-2 text-xs text-slate-500">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ vehicle.parentName }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
          <BButton variant="ghost" size="sm" @click.stop="emit('edit', vehicle)">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ t('common.edit') }}
          </BButton>
          <BButton variant="ghost" size="sm" @click.stop="handleDelete(vehicle)">
            <svg class="w-4 h-4 mr-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            {{ t('common.delete') }}
          </BButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BEmptyState
      v-else
      title="Keine Fahrzeuge"
      description="Erfassen Sie Ihr erstes Fahrzeug fuer die Ressourcenplanung."
      icon="inbox"
      action-label="Fahrzeug erfassen"
      @action="emit('create')"
    />
  </div>
</template>
