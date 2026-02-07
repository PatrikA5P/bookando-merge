<script setup lang="ts">
/**
 * RoomsTab — Raeume-Uebersicht
 *
 * Kartenbasierte Darstellung aller Raeume.
 * Nutzt das einheitliche Resource-Modell mit resourceType 'ROOM'.
 * Filter nach Standort (parentId) und Status.
 * CRUD ueber ResourceFormPanel (SlideIn).
 */
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useResourcesStore, RESOURCE_STATUS_COLORS } from '@/stores/resources';
import type { Resource, RoomProperties } from '@/stores/resources';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
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

const locationFilter = computed({
  get: () => store.filters.parentId,
  set: (val: string) => store.setFilters({ parentId: val }),
});

const statusFilter = computed({
  get: () => store.filters.status,
  set: (val: string) => store.setFilters({ status: val as any }),
});

const locationFilterOptions = computed(() => [
  { value: '', label: t('resources.locations') + ' (' + t('common.filter') + ')' },
  ...store.locationOptions,
]);

const statusFilterOptions = [
  { value: '', label: 'Status (' + t('common.filter') + ')' },
  { value: 'ACTIVE', label: t('resources.available') },
  { value: 'MAINTENANCE', label: t('resources.maintenance') },
  { value: 'RETIRED', label: t('resources.closed') },
];

function getProps(resource: Resource): RoomProperties {
  return resource.properties as RoomProperties;
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
</script>

<template>
  <div>
    <!-- Search & Filters -->
    <div class="mb-6 flex flex-col sm:flex-row gap-3">
      <div class="flex-1 max-w-md">
        <BSearchBar
          v-model="search"
          :placeholder="t('common.search') + '...'"
        />
      </div>
      <div class="flex gap-3">
        <BSelect
          v-model="locationFilter"
          :options="locationFilterOptions"
          class="min-w-[180px]"
        />
        <BSelect
          v-model="statusFilter"
          :options="statusFilterOptions"
          class="min-w-[160px]"
        />
      </div>
    </div>

    <!-- Room Cards -->
    <div
      v-if="store.filteredRooms.length > 0"
      :class="GRID_STYLES.cols3"
    >
      <div
        v-for="room in store.filteredRooms"
        :key="room.id"
        :class="CARD_STYLES.hover"
        class="p-5 cursor-pointer"
        @click="emit('edit', room)"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ room.name }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ room.parentName || '—' }}</p>
          </div>
          <div class="flex items-center gap-1.5">
            <span :class="['w-2 h-2 rounded-full', getStatusColor(room.status)]" />
            <span class="text-xs text-slate-600">{{ getStatusLabel(room.status) }}</span>
          </div>
        </div>

        <!-- Capacity -->
        <div class="flex items-center gap-2 mb-3">
          <div class="flex items-center gap-1.5 text-sm text-slate-700">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="font-medium">{{ room.capacity }}</span>
            <span class="text-slate-400">{{ t('resources.capacity') }}</span>
          </div>
        </div>

        <!-- Features -->
        <div v-if="getProps(room).features?.length > 0" class="flex flex-wrap gap-1.5 mb-4">
          <span
            v-for="feature in getProps(room).features"
            :key="feature"
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600"
          >
            {{ feature }}
          </span>
        </div>

        <!-- Floor info -->
        <div v-if="getProps(room).floor" class="text-xs text-slate-500 mb-4">
          Stockwerk: {{ getProps(room).floor }}
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
          <BButton variant="ghost" size="sm" @click.stop="emit('edit', room)">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ t('common.edit') }}
          </BButton>
          <BButton variant="ghost" size="sm" @click.stop="handleDelete(room)">
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
      :title="t('common.noResults')"
      :description="t('common.noResultsMessage')"
      icon="inbox"
      :action-label="t('resources.newRoom')"
      @action="emit('create')"
    />
  </div>
</template>
