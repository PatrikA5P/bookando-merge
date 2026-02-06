<script setup lang="ts">
/**
 * LocationsTab — Standorte-Uebersicht
 *
 * Kartenbasierte Darstellung aller Standorte.
 * CRUD ueber ResourceModal, Status-Badges, Kontaktdaten.
 */
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useResourcesStore } from '@/stores/resources';
import type { Location } from '@/stores/resources';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { CARD_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const { isMobile } = useBreakpoint();
const store = useResourcesStore();

const emit = defineEmits<{
  (e: 'edit', location: Location): void;
  (e: 'create'): void;
}>();

const search = computed({
  get: () => store.filters.search,
  set: (val: string) => store.setFilters({ search: val }),
});

async function handleDelete(location: Location) {
  if (await store.deleteLocation(location.id)) {
    toast.success(t('common.deletedSuccessfully'));
  }
}

function getStatusVariant(status: string): 'success' | 'danger' {
  return status === 'OPEN' ? 'success' : 'danger';
}

function getStatusLabel(status: string): string {
  return status === 'OPEN' ? t('resources.available') : t('resources.closed');
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

    <!-- Location Cards -->
    <div
      v-if="store.filteredLocations.length > 0"
      :class="GRID_STYLES.cols2"
    >
      <div
        v-for="location in store.filteredLocations"
        :key="location.id"
        :class="CARD_STYLES.hover"
        class="p-5"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-slate-900">{{ location.name }}</h3>
              <p class="text-xs text-slate-500 mt-0.5">{{ location.address }}, {{ location.zip }} {{ location.city }}</p>
            </div>
          </div>
          <BBadge :variant="getStatusVariant(location.status)">
            {{ getStatusLabel(location.status) }}
          </BBadge>
        </div>

        <!-- Details -->
        <div class="space-y-2 mb-4">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>{{ location.roomCount }} {{ t('resources.roomCount') }}</span>
          </div>
          <div v-if="location.phone" class="flex items-center gap-2 text-xs text-slate-500">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            <span>{{ location.phone }}</span>
          </div>
          <div v-if="location.email" class="flex items-center gap-2 text-xs text-slate-500">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span>{{ location.email }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
          <BButton variant="ghost" size="sm" @click="emit('edit', location)">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ t('common.edit') }}
          </BButton>
          <BButton variant="ghost" size="sm" @click="handleDelete(location)">
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
      :title="t('resources.noLocations')"
      :description="t('resources.noLocationsDesc')"
      icon="folder"
      :action-label="t('resources.newLocation')"
      @action="emit('create')"
    />
  </div>
</template>
