<script setup lang="ts">
/**
 * RoomsTab — Raeume-Uebersicht
 *
 * Kartenbasierte Darstellung aller Raeume.
 * Filter nach Standort und Status.
 * CRUD ueber ResourceModal.
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useResourcesStore } from '@/stores/resources';
import type { Room } from '@/stores/resources';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { CARD_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const { isMobile } = useBreakpoint();
const store = useResourcesStore();

const emit = defineEmits<{
  (e: 'edit', room: Room): void;
  (e: 'create'): void;
}>();

const search = computed({
  get: () => store.filters.search,
  set: (val: string) => store.setFilters({ search: val }),
});

const locationFilter = computed({
  get: () => store.filters.locationId,
  set: (val: string) => store.setFilters({ locationId: val }),
});

const statusFilter = computed({
  get: () => store.filters.status,
  set: (val: string) => store.setFilters({ status: val }),
});

const locationFilterOptions = computed(() => [
  { value: '', label: t('resources.locations') + ' (' + t('common.filter') + ')' },
  ...store.locationOptions,
]);

const statusFilterOptions = [
  { value: '', label: 'Status (' + t('common.filter') + ')' },
  { value: 'AVAILABLE', label: t('resources.available') },
  { value: 'IN_USE', label: t('resources.inUse') },
  { value: 'CLOSED', label: t('resources.closed') },
  { value: 'MAINTENANCE', label: t('resources.maintenance') },
];

async function handleDelete(room: Room) {
  if (await store.deleteRoom(room.id)) {
    toast.success(t('common.deletedSuccessfully'));
  }
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'AVAILABLE': return 'bg-emerald-500';
    case 'IN_USE': return 'bg-blue-500';
    case 'CLOSED': return 'bg-red-500';
    case 'MAINTENANCE': return 'bg-amber-500';
    default: return 'bg-slate-400';
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'AVAILABLE': return t('resources.available');
    case 'IN_USE': return t('resources.inUse');
    case 'CLOSED': return t('resources.closed');
    case 'MAINTENANCE': return t('resources.maintenance');
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
        class="p-5"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ room.name }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ room.locationName }}</p>
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
        <div v-if="room.features.length > 0" class="flex flex-wrap gap-1.5 mb-4">
          <span
            v-for="feature in room.features"
            :key="feature"
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600"
          >
            {{ feature }}
          </span>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
          <BButton variant="ghost" size="sm" @click="emit('edit', room)">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ t('common.edit') }}
          </BButton>
          <BButton variant="ghost" size="sm" @click="handleDelete(room)">
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
