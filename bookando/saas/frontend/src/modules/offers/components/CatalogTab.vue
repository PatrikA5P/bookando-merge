<script setup lang="ts">
/**
 * CatalogTab — Dienstleistungskatalog-Grid
 *
 * Zeigt alle Dienstleistungen als Karten-Grid mit Such-,
 * Filter- und Sortierfunktion.
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useOffersStore } from '@/stores/offers';
import type { ServiceItem } from '@/stores/offers';
import { CARD_STYLES, BADGE_STYLES, BUTTON_STYLES, INPUT_STYLES, GRID_STYLES } from '@/design';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BButton from '@/components/ui/BButton.vue';
import ServiceModal from './ServiceModal.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const appStore = useAppStore();
const store = useOffersStore();

// Filters & Sort
const searchQuery = ref('');
const categoryFilter = ref('');
const sortBy = ref<'name' | 'price' | 'duration'>('name');
const showModal = ref(false);
const editingService = ref<ServiceItem | null>(null);

const categoryOptions = computed(() => [
  { value: '', label: t('offers.categories') + ' — ' + t('common.all') },
  ...store.categories.map(c => ({ value: c.id, label: c.name })),
]);

const sortOptions = [
  { value: 'name', label: 'Name' },
  { value: 'price', label: 'Preis' },
  { value: 'duration', label: 'Dauer' },
];

const filteredServices = computed(() => {
  let result = [...store.services];

  // Search
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(s =>
      s.title.toLowerCase().includes(q) ||
      s.description.toLowerCase().includes(q) ||
      s.tags.some(tag => tag.toLowerCase().includes(q))
    );
  }

  // Category filter
  if (categoryFilter.value) {
    result = result.filter(s => s.categoryId === categoryFilter.value);
  }

  // Sort
  result.sort((a, b) => {
    if (sortBy.value === 'name') return a.title.localeCompare(b.title);
    if (sortBy.value === 'price') return a.priceMinor - b.priceMinor;
    return a.duration - b.duration;
  });

  return result;
});

const activeCount = computed(() => filteredServices.value.filter(s => s.active).length);
const inactiveCount = computed(() => filteredServices.value.filter(s => !s.active).length);

function getTypeBadgeVariant(type: string): 'info' | 'brand' | 'purple' {
  switch (type) {
    case 'SERVICE': return 'info';
    case 'EVENT': return 'brand';
    case 'ONLINE_COURSE': return 'purple';
    default: return 'info';
  }
}

function getTypeLabel(type: string): string {
  switch (type) {
    case 'SERVICE': return t('offers.serviceTypes.service');
    case 'EVENT': return t('offers.serviceTypes.event');
    case 'ONLINE_COURSE': return t('offers.serviceTypes.onlineCourse');
    default: return type;
  }
}

function onToggleActive(service: ServiceItem) {
  store.toggleServiceActive(service.id);
  const label = service.active ? 'aktiviert' : 'deaktiviert';
  toast.success(`${service.title} ${label}`);
}

function onEditService(service: ServiceItem) {
  editingService.value = { ...service };
  showModal.value = true;
}

function onCreateService() {
  editingService.value = null;
  showModal.value = true;
}

function onModalClose() {
  showModal.value = false;
  editingService.value = null;
}
</script>

<template>
  <div>
    <!-- Header mit Zaehler und Aktionen -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
      <div class="flex items-center gap-3">
        <h2 class="text-lg font-semibold text-slate-900">{{ t('offers.catalog') }}</h2>
        <div class="flex items-center gap-2">
          <BBadge variant="success" :dot="true">{{ activeCount }} {{ t('common.active') }}</BBadge>
          <BBadge variant="default" :dot="true">{{ inactiveCount }} {{ t('common.inactive') }}</BBadge>
        </div>
      </div>
      <BButton variant="primary" @click="onCreateService">
        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('offers.newService') }}
      </BButton>
    </div>

    <!-- Such- und Filterleiste -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
      <div class="flex-1">
        <BSearchBar
          v-model="searchQuery"
          :placeholder="t('common.search') + '...'"
        />
      </div>
      <div class="flex gap-3">
        <BSelect
          v-model="categoryFilter"
          :options="categoryOptions"
          :placeholder="t('offers.categories')"
        />
        <BSelect
          v-model="sortBy"
          :options="sortOptions"
          placeholder="Sortieren"
        />
      </div>
    </div>

    <!-- Leerer Zustand -->
    <BEmptyState
      v-if="filteredServices.length === 0"
      :title="t('offers.noServices')"
      :description="t('offers.noServicesDesc')"
      icon="inbox"
      :action-label="t('offers.newService')"
      @action="onCreateService"
    />

    <!-- Service Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="service in filteredServices"
        :key="service.id"
        :class="CARD_STYLES.gridItem"
      >
        <!-- Bild-Platzhalter -->
        <div
          class="h-40 bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center relative"
          :class="{ 'opacity-60': !service.active }"
        >
          <svg class="w-12 h-12 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <!-- Sale badge -->
          <div
            v-if="service.salePriceMinor"
            class="absolute top-3 left-3"
          >
            <BBadge variant="danger">{{ t('offers.salePrice') }}</BBadge>
          </div>
          <!-- Type badge -->
          <div class="absolute top-3 right-3">
            <BBadge :variant="getTypeBadgeVariant(service.type)">
              {{ getTypeLabel(service.type) }}
            </BBadge>
          </div>
        </div>

        <!-- Inhalt -->
        <div class="p-4 flex-1 flex flex-col">
          <div class="flex items-start justify-between gap-2 mb-1">
            <h3 class="text-sm font-semibold text-slate-900 line-clamp-1">{{ service.title }}</h3>
          </div>

          <p class="text-xs text-slate-500 mb-3 line-clamp-2">{{ service.description }}</p>

          <!-- Tags -->
          <div v-if="service.tags.length" class="flex flex-wrap gap-1 mb-3">
            <span
              v-for="tag in service.tags.slice(0, 3)"
              :key="tag"
              class="px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full"
            >
              {{ tag }}
            </span>
            <span
              v-if="service.tags.length > 3"
              class="px-2 py-0.5 text-xs bg-slate-100 text-slate-500 rounded-full"
            >
              +{{ service.tags.length - 3 }}
            </span>
          </div>

          <div class="mt-auto">
            <!-- Preis & Dauer -->
            <div class="flex items-center justify-between mb-3">
              <div>
                <span v-if="service.salePriceMinor" class="text-xs text-slate-400 line-through mr-2">
                  {{ appStore.formatPrice(service.priceMinor) }}
                </span>
                <span class="text-base font-bold text-slate-900">
                  {{ appStore.formatPrice(service.salePriceMinor || service.priceMinor) }}
                </span>
              </div>
              <span class="text-xs text-slate-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ service.duration }} min
              </span>
            </div>

            <!-- Kategorie -->
            <div class="text-xs text-slate-400 mb-3">{{ service.categoryName }}</div>

            <!-- Aktionen -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <BToggle
                :model-value="service.active"
                @update:model-value="onToggleActive(service)"
              />
              <button
                :class="BUTTON_STYLES.ghost"
                class="!px-3 !py-1.5 !text-xs"
                @click="onEditService(service)"
              >
                <svg class="w-3.5 h-3.5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                {{ t('common.edit') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ServiceModal -->
    <ServiceModal
      :model-value="showModal"
      :service="editingService"
      @update:model-value="onModalClose"
      @saved="onModalClose"
    />
  </div>
</template>
