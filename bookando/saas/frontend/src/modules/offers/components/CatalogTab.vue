<script setup lang="ts">
/**
 * CatalogTab — Angebotskatalog-Grid
 *
 * Zeigt alle Angebote (Service, Event, OnlineCourse) als Karten-Grid
 * mit Such-, Filter- und Sortierfunktion.
 *
 * Nutzt OfferFormPanel (BFormPanel SlideIn) statt ServiceModal (BModal Overlay).
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useOffersStore, isServiceOffer } from '@/stores/offers';
import type { Offer, OfferType } from '@/stores/offers';
import { formatMoney } from '@/utils/money';
import { CARD_STYLES, BUTTON_STYLES } from '@/design';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BButton from '@/components/ui/BButton.vue';
import OfferFormPanel from './OfferFormPanel.vue';

const { t } = useI18n();
const toast = useToast();
const store = useOffersStore();

// Filters & Sort
const searchQuery = ref('');
const categoryFilter = ref('');
const typeFilter = ref<'' | OfferType>('');
const sortBy = ref<'name' | 'price' | 'duration'>('name');
const showPanel = ref(false);
const editingOffer = ref<Offer | null>(null);

const categoryOptions = computed(() => [
  { value: '', label: t('offers.categories') + ' — ' + t('common.all') },
  ...store.categories.map(c => ({ value: c.id, label: c.name })),
]);

const typeOptions = [
  { value: '', label: 'Alle Typen' },
  { value: 'SERVICE', label: 'Dienstleistung' },
  { value: 'EVENT', label: 'Kurs / Event' },
  { value: 'ONLINE_COURSE', label: 'Onlinekurs' },
];

const sortOptions = [
  { value: 'name', label: 'Name' },
  { value: 'price', label: 'Preis' },
  { value: 'duration', label: 'Dauer' },
];

const filteredOffers = computed(() => {
  let result = [...store.offers];

  // Search
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(o =>
      o.title.toLowerCase().includes(q) ||
      o.description.toLowerCase().includes(q) ||
      o.tags.some(tag => tag.toLowerCase().includes(q))
    );
  }

  // Category filter
  if (categoryFilter.value) {
    result = result.filter(o => o.categoryId === categoryFilter.value);
  }

  // Type filter
  if (typeFilter.value) {
    result = result.filter(o => o.offerType === typeFilter.value);
  }

  // Sort
  result.sort((a, b) => {
    if (sortBy.value === 'name') return a.title.localeCompare(b.title);
    if (sortBy.value === 'price') return a.priceCents - b.priceCents;
    // Duration: nur fuer ServiceOffer sinnvoll
    const durA = isServiceOffer(a) ? a.serviceConfig.durationMinutes : 0;
    const durB = isServiceOffer(b) ? b.serviceConfig.durationMinutes : 0;
    return durA - durB;
  });

  return result;
});

const activeCount = computed(() => filteredOffers.value.filter(o => o.status === 'ACTIVE').length);
const inactiveCount = computed(() => filteredOffers.value.filter(o => o.status !== 'ACTIVE').length);

function getTypeBadgeVariant(type: OfferType): 'info' | 'brand' | 'purple' {
  switch (type) {
    case 'SERVICE': return 'info';
    case 'EVENT': return 'brand';
    case 'ONLINE_COURSE': return 'purple';
  }
}

function getTypeLabel(type: OfferType): string {
  switch (type) {
    case 'SERVICE': return t('offers.serviceTypes.service');
    case 'EVENT': return t('offers.serviceTypes.event');
    case 'ONLINE_COURSE': return t('offers.serviceTypes.onlineCourse');
  }
}

function getDuration(offer: Offer): number | undefined {
  if (isServiceOffer(offer)) return offer.serviceConfig.durationMinutes;
  return undefined;
}

function getStatusBadgeVariant(status: string): 'success' | 'default' | 'warning' | 'danger' {
  switch (status) {
    case 'ACTIVE': return 'success';
    case 'DRAFT': return 'default';
    case 'PAUSED': return 'warning';
    case 'ARCHIVED': return 'danger';
    default: return 'default';
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'ACTIVE': return t('common.active');
    case 'DRAFT': return 'Entwurf';
    case 'PAUSED': return 'Pausiert';
    case 'ARCHIVED': return 'Archiviert';
    default: return status;
  }
}

function onToggleStatus(offer: Offer) {
  const newStatus = offer.status === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';
  store.setOfferStatus(offer.id, newStatus);
  toast.success(`${offer.title} ${newStatus === 'ACTIVE' ? 'aktiviert' : 'pausiert'}`);
}

function onEditOffer(offer: Offer) {
  editingOffer.value = offer;
  showPanel.value = true;
}

function openCreatePanel() {
  editingOffer.value = null;
  showPanel.value = true;
}

function onPanelClose() {
  showPanel.value = false;
  editingOffer.value = null;
}

// Expose fuer OffersPage FAB
defineExpose({ openCreatePanel });
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
      <BButton variant="primary" @click="openCreatePanel">
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
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="Typ"
        />
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
      v-if="filteredOffers.length === 0"
      :title="t('offers.noServices')"
      :description="t('offers.noServicesDesc')"
      icon="inbox"
      :action-label="t('offers.newService')"
      @action="openCreatePanel"
    />

    <!-- Offer Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="offer in filteredOffers"
        :key="offer.id"
        :class="CARD_STYLES.gridItem"
      >
        <!-- Bild-Platzhalter -->
        <div
          class="h-40 bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center relative"
          :class="{ 'opacity-60': offer.status !== 'ACTIVE' }"
        >
          <svg class="w-12 h-12 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <!-- Sale badge -->
          <div
            v-if="offer.salePriceCents"
            class="absolute top-3 left-3"
          >
            <BBadge variant="danger">{{ t('offers.salePrice') }}</BBadge>
          </div>
          <!-- Type badge -->
          <div class="absolute top-3 right-3">
            <BBadge :variant="getTypeBadgeVariant(offer.offerType)">
              {{ getTypeLabel(offer.offerType) }}
            </BBadge>
          </div>
        </div>

        <!-- Inhalt -->
        <div class="p-4 flex-1 flex flex-col">
          <div class="flex items-start justify-between gap-2 mb-1">
            <h3 class="text-sm font-semibold text-slate-900 line-clamp-1">{{ offer.title }}</h3>
            <BBadge :variant="getStatusBadgeVariant(offer.status)" size="sm">
              {{ getStatusLabel(offer.status) }}
            </BBadge>
          </div>

          <p class="text-xs text-slate-500 mb-3 line-clamp-2">{{ offer.description }}</p>

          <!-- Tags -->
          <div v-if="offer.tags.length" class="flex flex-wrap gap-1 mb-3">
            <span
              v-for="tag in offer.tags.slice(0, 3)"
              :key="tag"
              class="px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full"
            >
              {{ tag }}
            </span>
            <span
              v-if="offer.tags.length > 3"
              class="px-2 py-0.5 text-xs bg-slate-100 text-slate-500 rounded-full"
            >
              +{{ offer.tags.length - 3 }}
            </span>
          </div>

          <div class="mt-auto">
            <!-- Preis & Dauer -->
            <div class="flex items-center justify-between mb-3">
              <div>
                <span v-if="offer.salePriceCents" class="text-xs text-slate-400 line-through mr-2">
                  {{ formatMoney(offer.priceCents, offer.currency) }}
                </span>
                <span class="text-base font-bold text-slate-900">
                  {{ formatMoney(offer.salePriceCents || offer.priceCents, offer.currency) }}
                </span>
              </div>
              <span v-if="getDuration(offer)" class="text-xs text-slate-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ getDuration(offer) }} min
              </span>
            </div>

            <!-- Kategorie -->
            <div class="text-xs text-slate-400 mb-3">{{ offer.categoryName }}</div>

            <!-- Aktionen -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <button
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                :class="offer.status === 'ACTIVE' ? 'bg-brand-600' : 'bg-slate-200'"
                @click="onToggleStatus(offer)"
              >
                <span
                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                  :class="offer.status === 'ACTIVE' ? 'translate-x-5' : 'translate-x-0'"
                />
              </button>
              <button
                :class="BUTTON_STYLES.ghost"
                class="!px-3 !py-1.5 !text-xs"
                @click="onEditOffer(offer)"
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

    <!-- OfferFormPanel (SlideIn Gold Standard) -->
    <OfferFormPanel
      :model-value="showPanel"
      :offer="editingOffer"
      @update:model-value="onPanelClose"
      @saved="onPanelClose"
    />
  </div>
</template>
