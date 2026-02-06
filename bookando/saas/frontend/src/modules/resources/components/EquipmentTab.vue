<script setup lang="ts">
/**
 * EquipmentTab — Equipment-Uebersicht
 *
 * Tabellenbasierte Darstellung aller Equipment-Gegenstaende.
 * Verfuegbarkeits-Fortschrittsbalken, Zustands-Badges.
 * CRUD ueber ResourceModal.
 */
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useResourcesStore } from '@/stores/resources';
import type { Equipment } from '@/stores/resources';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BTable from '@/components/ui/BTable.vue';
import type { Column } from '@/components/ui/BTable.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { BADGE_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const { isMobile } = useBreakpoint();
const store = useResourcesStore();

const emit = defineEmits<{
  (e: 'edit', equipment: Equipment): void;
  (e: 'create'): void;
}>();

const search = computed({
  get: () => store.filters.search,
  set: (val: string) => store.setFilters({ search: val }),
});

const columns: Column[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'category', label: 'Kategorie', sortable: true },
  { key: 'availability', label: t('resources.available'), sortable: false },
  { key: 'condition', label: 'Zustand', sortable: true },
  { key: 'locationName', label: t('resources.locations'), sortable: true },
  { key: 'actions', label: '', align: 'right', width: '120px' },
];

const tableData = computed(() =>
  store.filteredEquipment.map(eq => ({
    ...eq,
    availability: `${eq.available}/${eq.total}`,
  })) as unknown as Record<string, unknown>[]
);

function getConditionVariant(condition: string): 'success' | 'warning' | 'danger' {
  switch (condition) {
    case 'GOOD': return 'success';
    case 'FAIR': return 'warning';
    case 'POOR': return 'danger';
    default: return 'warning';
  }
}

function getConditionLabel(condition: string): string {
  switch (condition) {
    case 'GOOD': return t('resources.condition.good');
    case 'FAIR': return t('resources.condition.fair');
    case 'POOR': return t('resources.condition.poor');
    default: return condition;
  }
}

function getAvailabilityPercent(row: Record<string, unknown>): number {
  const available = row.available as number;
  const total = row.total as number;
  if (total === 0) return 0;
  return Math.round((available / total) * 100);
}

function getAvailabilityColor(row: Record<string, unknown>): string {
  const pct = getAvailabilityPercent(row);
  if (pct >= 75) return 'bg-emerald-500';
  if (pct >= 50) return 'bg-amber-500';
  return 'bg-red-500';
}

function handleEdit(row: Record<string, unknown>) {
  const eq = store.getEquipmentById(row.id as string);
  if (eq) emit('edit', eq);
}

function handleDelete(row: Record<string, unknown>) {
  if (store.deleteEquipment(row.id as string)) {
    toast.success(t('common.deletedSuccessfully'));
  }
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

    <!-- Table -->
    <div v-if="store.filteredEquipment.length > 0">
      <BTable
        :columns="columns"
        :data="tableData"
        :total="tableData.length"
        :per-page="25"
        :empty-title="t('common.noResults')"
        :empty-message="t('common.noResultsMessage')"
      >
        <!-- Name cell -->
        <template #cell-name="{ row }">
          <span class="font-medium text-slate-900">{{ row.name }}</span>
        </template>

        <!-- Category cell -->
        <template #cell-category="{ row }">
          <span class="text-slate-600">{{ row.category }}</span>
        </template>

        <!-- Availability cell with progress bar -->
        <template #cell-availability="{ row }">
          <div class="flex items-center gap-3 min-w-[140px]">
            <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
              <div
                :class="['h-full rounded-full transition-all duration-300', getAvailabilityColor(row)]"
                :style="{ width: getAvailabilityPercent(row) + '%' }"
              />
            </div>
            <span class="text-xs text-slate-600 whitespace-nowrap font-medium">
              {{ row.available }}/{{ row.total }}
            </span>
          </div>
        </template>

        <!-- Condition cell -->
        <template #cell-condition="{ row }">
          <BBadge :variant="getConditionVariant(row.condition as string)">
            {{ getConditionLabel(row.condition as string) }}
          </BBadge>
        </template>

        <!-- Location cell -->
        <template #cell-locationName="{ row }">
          <span class="text-slate-600">{{ row.locationName || '—' }}</span>
        </template>

        <!-- Actions cell -->
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button
              class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors"
              :aria-label="t('common.edit')"
              @click.stop="handleEdit(row)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-600 transition-colors"
              :aria-label="t('common.delete')"
              @click.stop="handleDelete(row)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </template>
      </BTable>
    </div>

    <!-- Empty State -->
    <BEmptyState
      v-else
      :title="t('common.noResults')"
      :description="t('common.noResultsMessage')"
      icon="inbox"
      :action-label="t('resources.newEquipment')"
      @action="emit('create')"
    />
  </div>
</template>
