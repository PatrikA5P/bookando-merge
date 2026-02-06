<script setup lang="ts">
/**
 * BundlesTab — Paket-Verwaltung
 *
 * Karten-Grid mit Paket-Uebersicht, Erstellen und Bearbeiten
 * von Bundles mit Service-Auswahl und automatischer Preisberechnung.
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useOffersStore } from '@/stores/offers';
import type { Bundle } from '@/stores/offers';
import { CARD_STYLES, BADGE_STYLES, BUTTON_STYLES, GRID_STYLES, MODAL_STYLES, INPUT_STYLES, LABEL_STYLES } from '@/design';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BModal from '@/components/ui/BModal.vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const appStore = useAppStore();
const store = useOffersStore();

const showModal = ref(false);
const editingBundle = ref<Bundle | null>(null);

// Form
const form = ref({
  title: '',
  description: '',
  serviceIds: [] as string[],
  active: true,
});

const errors = ref<Record<string, string>>({});

const selectedServicesTotal = computed(() => {
  return form.value.serviceIds.reduce((sum, id) => {
    const svc = store.getServiceById(id);
    return sum + (svc ? svc.priceMinor : 0);
  }, 0);
});

const bundleSavings = computed(() => {
  const total = selectedServicesTotal.value;
  // Default savings: 10% off
  return Math.round(total * 0.1);
});

const bundlePrice = computed(() => {
  return selectedServicesTotal.value - bundleSavings.value;
});

function getServicesForBundle(bundle: Bundle) {
  return bundle.serviceIds
    .map(id => store.getServiceById(id))
    .filter(Boolean);
}

function onToggleActive(bundle: Bundle) {
  store.toggleBundleActive(bundle.id);
  const label = bundle.active ? 'aktiviert' : 'deaktiviert';
  toast.success(`${bundle.title} ${label}`);
}

function onCreateBundle() {
  editingBundle.value = null;
  form.value = { title: '', description: '', serviceIds: [], active: true };
  errors.value = {};
  showModal.value = true;
}

function onEditBundle(bundle: Bundle) {
  editingBundle.value = bundle;
  form.value = {
    title: bundle.title,
    description: bundle.description,
    serviceIds: [...bundle.serviceIds],
    active: bundle.active,
  };
  errors.value = {};
  showModal.value = true;
}

function onDeleteBundle(bundle: Bundle) {
  store.deleteBundle(bundle.id);
  toast.success(`${bundle.title} geloescht`);
}

function toggleService(serviceId: string) {
  const idx = form.value.serviceIds.indexOf(serviceId);
  if (idx === -1) {
    form.value.serviceIds.push(serviceId);
  } else {
    form.value.serviceIds.splice(idx, 1);
  }
}

function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!form.value.title.trim()) {
    errs.title = t('common.required');
  }
  if (form.value.serviceIds.length < 2) {
    errs.services = 'Min. 2 Dienstleistungen';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

function onSave() {
  if (!validate()) return;

  if (editingBundle.value) {
    store.updateBundle(editingBundle.value.id, {
      title: form.value.title,
      description: form.value.description,
      serviceIds: form.value.serviceIds,
      totalPriceMinor: bundlePrice.value,
      savingsMinor: bundleSavings.value,
      active: form.value.active,
    });
    toast.success(t('common.saved'));
  } else {
    store.addBundle({
      title: form.value.title,
      description: form.value.description,
      serviceIds: form.value.serviceIds,
      totalPriceMinor: bundlePrice.value,
      savingsMinor: bundleSavings.value,
      active: form.value.active,
    });
    toast.success(t('common.saved'));
  }

  showModal.value = false;
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
      <h2 class="text-lg font-semibold text-slate-900">{{ t('offers.bundles') }}</h2>
      <BButton variant="primary" @click="onCreateBundle">
        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('offers.bundles') }} {{ t('common.create') }}
      </BButton>
    </div>

    <!-- Leerer Zustand -->
    <BEmptyState
      v-if="store.bundles.length === 0"
      title="Keine Pakete"
      description="Erstellen Sie Ihr erstes Paket mit kombinierten Dienstleistungen."
      icon="inbox"
      :action-label="t('common.create')"
      @action="onCreateBundle"
    />

    <!-- Bundle Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="bundle in store.bundles"
        :key="bundle.id"
        :class="CARD_STYLES.gridItem"
      >
        <!-- Bild-Platzhalter -->
        <div class="h-32 bg-gradient-to-br from-indigo-100 to-purple-50 flex items-center justify-center relative">
          <svg class="w-10 h-10 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <!-- Savings badge -->
          <div class="absolute top-3 right-3">
            <BBadge variant="success">
              {{ t('offers.savings') }}: {{ appStore.formatPrice(bundle.savingsMinor) }}
            </BBadge>
          </div>
        </div>

        <!-- Inhalt -->
        <div class="p-4 flex-1 flex flex-col">
          <h3 class="text-sm font-semibold text-slate-900 mb-1">{{ bundle.title }}</h3>
          <p class="text-xs text-slate-500 mb-3 line-clamp-2">{{ bundle.description }}</p>

          <!-- Enthaltene Services -->
          <div class="space-y-1.5 mb-3">
            <div
              v-for="svc in getServicesForBundle(bundle)"
              :key="svc!.id"
              class="flex items-center gap-2 text-xs text-slate-600"
            >
              <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ svc!.title }}</span>
              <span class="text-slate-400 ml-auto">{{ appStore.formatPrice(svc!.priceMinor) }}</span>
            </div>
          </div>

          <div class="mt-auto">
            <!-- Preis -->
            <div class="flex items-baseline gap-2 mb-3">
              <span class="text-base font-bold text-slate-900">
                {{ appStore.formatPrice(bundle.totalPriceMinor) }}
              </span>
              <span class="text-xs text-slate-400 line-through">
                {{ appStore.formatPrice(bundle.totalPriceMinor + bundle.savingsMinor) }}
              </span>
            </div>

            <!-- Aktionen -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <BToggle
                :model-value="bundle.active"
                @update:model-value="onToggleActive(bundle)"
              />
              <div class="flex gap-1">
                <button
                  :class="BUTTON_STYLES.icon"
                  @click="onEditBundle(bundle)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  :class="BUTTON_STYLES.icon"
                  @click="onDeleteBundle(bundle)"
                >
                  <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bundle Modal -->
    <BModal
      :model-value="showModal"
      :title="editingBundle ? t('common.edit') + ': ' + editingBundle.title : t('offers.bundles') + ' ' + t('common.create')"
      size="lg"
      @update:model-value="showModal = false"
      @close="showModal = false"
    >
      <div class="space-y-4">
        <BInput
          v-model="form.title"
          :label="t('common.name')"
          :placeholder="t('offers.bundles') + ' Name'"
          :required="true"
          :error="errors.title"
        />

        <BTextarea
          v-model="form.description"
          :label="t('offers.description')"
          :placeholder="t('offers.description') + '...'"
          :rows="2"
        />

        <!-- Service-Auswahl -->
        <div>
          <label :class="LABEL_STYLES.required">{{ t('offers.catalog') }}</label>
          <p v-if="errors.services" :class="LABEL_STYLES.error" class="mb-2">{{ errors.services }}</p>
          <div class="space-y-2 max-h-64 overflow-y-auto">
            <label
              v-for="svc in store.services.filter(s => s.active)"
              :key="svc.id"
              class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-blue-200 hover:bg-blue-50/50 cursor-pointer transition-all"
              :class="{ 'border-blue-300 bg-blue-50': form.serviceIds.includes(svc.id) }"
            >
              <input
                type="checkbox"
                :checked="form.serviceIds.includes(svc.id)"
                :class="INPUT_STYLES.checkbox"
                @change="toggleService(svc.id)"
              />
              <div class="flex-1 min-w-0">
                <span class="text-sm font-medium text-slate-900">{{ svc.title }}</span>
                <span class="text-xs text-slate-500 ml-2">{{ svc.duration }} min</span>
              </div>
              <span class="text-sm font-medium text-slate-700">{{ appStore.formatPrice(svc.priceMinor) }}</span>
            </label>
          </div>
        </div>

        <!-- Preis-Uebersicht -->
        <div v-if="form.serviceIds.length > 0" :class="CARD_STYLES.ghost" class="p-4">
          <h4 class="text-sm font-medium text-slate-700 mb-2">{{ t('common.preview') }}</h4>
          <div class="space-y-1 text-sm">
            <div class="flex justify-between text-slate-600">
              <span>{{ form.serviceIds.length }} {{ t('offers.catalog') }}</span>
              <span>{{ appStore.formatPrice(selectedServicesTotal) }}</span>
            </div>
            <div class="flex justify-between text-emerald-600 font-medium">
              <span>{{ t('offers.savings') }} (10%)</span>
              <span>-{{ appStore.formatPrice(bundleSavings) }}</span>
            </div>
            <div class="flex justify-between text-slate-900 font-bold pt-1 border-t border-slate-200">
              <span>Total</span>
              <span>{{ appStore.formatPrice(bundlePrice) }}</span>
            </div>
          </div>
        </div>

        <BToggle
          v-model="form.active"
          :label="t('common.active')"
        />
      </div>

      <template #footer>
        <BButton variant="secondary" @click="showModal = false">
          {{ t('common.cancel') }}
        </BButton>
        <BButton variant="primary" @click="onSave">
          {{ t('common.save') }}
        </BButton>
      </template>
    </BModal>
  </div>
</template>
