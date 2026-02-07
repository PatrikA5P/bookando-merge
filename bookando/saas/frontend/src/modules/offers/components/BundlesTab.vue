<script setup lang="ts">
/**
 * BundlesTab — Paket-Verwaltung
 *
 * Karten-Grid mit Paket-Uebersicht, Erstellen und Bearbeiten
 * von Bundles mit Offer-Auswahl und automatischer Preisberechnung.
 *
 * GOLD STANDARD: BFormPanel (SlideIn) statt BModal (Overlay).
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useOffersStore } from '@/stores/offers';
import type { Bundle, Offer } from '@/stores/offers';
import { formatMoney } from '@/utils/money';
import { CARD_STYLES, BUTTON_STYLES, LABEL_STYLES } from '@/design';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';

const { t } = useI18n();
const toast = useToast();
const store = useOffersStore();

const showPanel = ref(false);
const editingBundle = ref<Bundle | null>(null);
const saving = ref(false);

// Form
const form = ref({
  title: '',
  description: '',
  offerIds: [] as string[],
  active: true,
});

const errors = ref<Record<string, string>>({});

const isEditing = computed(() => !!editingBundle.value);

const selectedOffersTotal = computed(() => {
  return form.value.offerIds.reduce((sum, id) => {
    const offer = store.getOfferById(id);
    return sum + (offer ? offer.priceCents : 0);
  }, 0);
});

const bundleSavings = computed(() => {
  const total = selectedOffersTotal.value;
  return Math.round(total * 0.1);
});

const bundlePrice = computed(() => {
  return selectedOffersTotal.value - bundleSavings.value;
});

const dirty = computed(() => form.value.title !== '' || form.value.offerIds.length > 0);

function getOffersForBundle(bundle: Bundle): Offer[] {
  return bundle.offerIds
    .map(id => store.getOfferById(id))
    .filter((o): o is Offer => !!o);
}

function onToggleActive(bundle: Bundle) {
  store.updateBundle(bundle.id, { active: !bundle.active });
  const label = !bundle.active ? 'aktiviert' : 'deaktiviert';
  toast.success(`${bundle.title} ${label}`);
}

function onCreateBundle() {
  editingBundle.value = null;
  form.value = { title: '', description: '', offerIds: [], active: true };
  errors.value = {};
  showPanel.value = true;
}

function onEditBundle(bundle: Bundle) {
  editingBundle.value = bundle;
  form.value = {
    title: bundle.title,
    description: bundle.description,
    offerIds: [...bundle.offerIds],
    active: bundle.active,
  };
  errors.value = {};
  showPanel.value = true;
}

function onDeleteBundle(bundle: Bundle) {
  store.deleteBundle(bundle.id);
  toast.success(`${bundle.title} geloescht`);
}

function toggleOffer(offerId: string) {
  const idx = form.value.offerIds.indexOf(offerId);
  if (idx === -1) {
    form.value.offerIds.push(offerId);
  } else {
    form.value.offerIds.splice(idx, 1);
  }
}

function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!form.value.title.trim()) {
    errs.title = t('common.required');
  }
  if (form.value.offerIds.length < 2) {
    errs.offers = 'Min. 2 Angebote';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

async function onSave() {
  if (!validate()) return;

  saving.value = true;
  try {
    if (editingBundle.value) {
      await store.updateBundle(editingBundle.value.id, {
        title: form.value.title,
        description: form.value.description,
        offerIds: form.value.offerIds,
        totalPriceCents: bundlePrice.value,
        savingsCents: bundleSavings.value,
        active: form.value.active,
      });
    } else {
      await store.addBundle({
        title: form.value.title,
        description: form.value.description,
        offerIds: form.value.offerIds,
        totalPriceCents: bundlePrice.value,
        savingsCents: bundleSavings.value,
        active: form.value.active,
      });
    }
    toast.success(t('common.saved'));
    showPanel.value = false;
  } catch {
    toast.error('Fehler beim Speichern');
  } finally {
    saving.value = false;
  }
}

function onPanelClose() {
  showPanel.value = false;
  editingBundle.value = null;
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
      description="Erstellen Sie Ihr erstes Paket mit kombinierten Angeboten."
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
              {{ t('offers.savings') }}: {{ formatMoney(bundle.savingsCents) }}
            </BBadge>
          </div>
        </div>

        <!-- Inhalt -->
        <div class="p-4 flex-1 flex flex-col">
          <h3 class="text-sm font-semibold text-slate-900 mb-1">{{ bundle.title }}</h3>
          <p class="text-xs text-slate-500 mb-3 line-clamp-2">{{ bundle.description }}</p>

          <!-- Enthaltene Offers -->
          <div class="space-y-1.5 mb-3">
            <div
              v-for="offer in getOffersForBundle(bundle)"
              :key="offer.id"
              class="flex items-center gap-2 text-xs text-slate-600"
            >
              <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ offer.title }}</span>
              <span class="text-slate-400 ml-auto">{{ formatMoney(offer.priceCents) }}</span>
            </div>
          </div>

          <div class="mt-auto">
            <!-- Preis -->
            <div class="flex items-baseline gap-2 mb-3">
              <span class="text-base font-bold text-slate-900">
                {{ formatMoney(bundle.totalPriceCents) }}
              </span>
              <span class="text-xs text-slate-400 line-through">
                {{ formatMoney(bundle.totalPriceCents + bundle.savingsCents) }}
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

    <!-- Bundle FormPanel (SlideIn Gold Standard) -->
    <BFormPanel
      :model-value="showPanel"
      :title="isEditing ? 'Paket bearbeiten: ' + editingBundle?.title : 'Neues Paket'"
      :mode="isEditing ? 'edit' : 'create'"
      size="md"
      :saving="saving"
      :dirty="dirty"
      @update:model-value="onPanelClose"
      @save="onSave"
      @cancel="onPanelClose"
    >
      <BFormSection title="Grunddaten" :columns="1" divided>
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
      </BFormSection>

      <BFormSection title="Enthaltene Angebote" :columns="1" divided>
        <p v-if="errors.offers" :class="LABEL_STYLES.error" class="mb-2">{{ errors.offers }}</p>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          <label
            v-for="offer in store.activeOffers"
            :key="offer.id"
            class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-blue-200 hover:bg-blue-50/50 cursor-pointer transition-all"
            :class="{ 'border-blue-300 bg-blue-50': form.offerIds.includes(offer.id) }"
          >
            <input
              type="checkbox"
              :checked="form.offerIds.includes(offer.id)"
              class="rounded border-slate-300 text-brand-600"
              @change="toggleOffer(offer.id)"
            />
            <div class="flex-1 min-w-0">
              <span class="text-sm font-medium text-slate-900">{{ offer.title }}</span>
            </div>
            <span class="text-sm font-medium text-slate-700">{{ formatMoney(offer.priceCents) }}</span>
          </label>
        </div>
      </BFormSection>

      <!-- Preis-Uebersicht -->
      <div v-if="form.offerIds.length > 0" :class="CARD_STYLES.ghost" class="p-4 mx-0 mt-4">
        <h4 class="text-sm font-medium text-slate-700 mb-2">{{ t('common.preview') }}</h4>
        <div class="space-y-1 text-sm">
          <div class="flex justify-between text-slate-600">
            <span>{{ form.offerIds.length }} Angebote</span>
            <span>{{ formatMoney(selectedOffersTotal) }}</span>
          </div>
          <div class="flex justify-between text-emerald-600 font-medium">
            <span>{{ t('offers.savings') }} (10%)</span>
            <span>-{{ formatMoney(bundleSavings) }}</span>
          </div>
          <div class="flex justify-between text-slate-900 font-bold pt-1 border-t border-slate-200">
            <span>Total</span>
            <span>{{ formatMoney(bundlePrice) }}</span>
          </div>
        </div>
      </div>

      <BFormSection title="Status" :columns="1">
        <BToggle
          v-model="form.active"
          :label="t('common.active')"
        />
      </BFormSection>

      <!-- Delete button in footer-left -->
      <template v-if="isEditing" #footer-left>
        <button
          class="px-4 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
          @click="() => { if (editingBundle) { onDeleteBundle(editingBundle); showPanel = false; } }"
        >
          Loeschen
        </button>
      </template>
    </BFormPanel>
  </div>
</template>
