<script setup lang="ts">
/**
 * Angebote-Modul — Vollstaendige Verwaltung
 *
 * Dienstleistungskatalog, Pakete, Gutscheine, Dynamic Pricing,
 * Tags, Extras, Kategorien und Buchungsformulare.
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import { useOffersStore } from '@/stores/offers';
import { BUTTON_STYLES } from '@/design';
import CatalogTab from './components/CatalogTab.vue';
import BundlesTab from './components/BundlesTab.vue';
import VouchersTab from './components/VouchersTab.vue';
import DynamicPricingTab from './components/DynamicPricingTab.vue';
import CategoriesTab from './components/CategoriesTab.vue';

const { t } = useI18n();
const designStore = useDesignStore();
const store = useOffersStore();

const tabs = computed<Tab[]>(() => [
  { id: 'catalog', label: t('offers.catalog'), badge: store.services.length },
  { id: 'bundles', label: t('offers.bundles'), badge: store.bundles.length },
  { id: 'vouchers', label: t('offers.vouchers'), badge: store.vouchers.length },
  { id: 'dynamic-pricing', label: t('offers.dynamicPricing'), badge: store.pricingRules.length },
  { id: 'tags', label: t('offers.tags') },
  { id: 'extras', label: t('offers.extras') },
  { id: 'categories', label: t('offers.categories'), badge: store.categories.length },
  { id: 'booking-forms', label: t('offers.bookingForms') },
]);

const activeTab = ref('catalog');

const showNewServiceModal = ref(false);

function onFabClick() {
  if (activeTab.value === 'catalog') {
    showNewServiceModal.value = true;
  }
}
</script>

<template>
  <ModuleLayout
    module-name="offers"
    :title="t('offers.title')"
    :subtitle="t('offers.catalog')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="activeTab === 'catalog' || activeTab === 'bundles' || activeTab === 'vouchers'"
    :fab-label="t('offers.newService')"
    @tab-change="(id: string) => activeTab = id"
    @fab-click="onFabClick"
  >
    <template #header-actions>
      <button
        :class="[
          'px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5',
        ]"
        @click="showNewServiceModal = true"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('offers.newService') }}
      </button>
    </template>

    <!-- Katalog -->
    <CatalogTab v-if="activeTab === 'catalog'" />

    <!-- Pakete -->
    <BundlesTab v-else-if="activeTab === 'bundles'" />

    <!-- Gutscheine -->
    <VouchersTab v-else-if="activeTab === 'vouchers'" />

    <!-- Dynamic Pricing -->
    <DynamicPricingTab v-else-if="activeTab === 'dynamic-pricing'" />

    <!-- Kategorien -->
    <CategoriesTab v-else-if="activeTab === 'categories'" />

    <!-- Tags (Platzhalter) -->
    <div v-else-if="activeTab === 'tags'" class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900">{{ t('offers.tags') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('offers.tags') }} — Coming soon</p>
      </div>
    </div>

    <!-- Extras (Platzhalter) -->
    <div v-else-if="activeTab === 'extras'" class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900">{{ t('offers.extras') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('offers.extras') }} — Coming soon</p>
      </div>
    </div>

    <!-- Buchungsformulare (Platzhalter) -->
    <div v-else class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900">{{ t('offers.bookingForms') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ t('offers.bookingForms') }} — Coming soon</p>
      </div>
    </div>
  </ModuleLayout>
</template>
