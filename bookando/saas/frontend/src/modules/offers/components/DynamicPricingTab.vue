<script setup lang="ts">
/**
 * DynamicPricingTab — Preisregeln-Verwaltung
 *
 * Karten-Grid mit dynamischen Preisregeln nach Typ,
 * inkl. Erstellung/Bearbeitung und Bedingungskonfiguration.
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useOffersStore } from '@/stores/offers';
import type { PricingRule, PricingRuleType, PricingRuleConditions } from '@/stores/offers';
import { CARD_STYLES, BADGE_STYLES, BUTTON_STYLES, GRID_STYLES, MODAL_STYLES, INPUT_STYLES, LABEL_STYLES } from '@/design';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BModal from '@/components/ui/BModal.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const store = useOffersStore();

const showModal = ref(false);
const editingRule = ref<PricingRule | null>(null);

// Form
const form = ref({
  name: '',
  type: 'EARLY_BIRD' as PricingRuleType,
  discountPercent: 10,
  conditions: {
    daysBeforeMin: undefined as number | undefined,
    daysBeforeMax: undefined as number | undefined,
    dateRange: undefined as { start: string; end: string } | undefined,
    timeRange: undefined as { start: string; end: string } | undefined,
  } as PricingRuleConditions,
  active: true,
});
const errors = ref<Record<string, string>>({});

// Internal state for date/time conditions
const dateRangeStart = ref('');
const dateRangeEnd = ref('');
const timeRangeStart = ref('08:00');
const timeRangeEnd = ref('10:00');
const daysBeforeMin = ref(0);
const daysBeforeMax = ref(14);

const typeOptions = [
  { value: 'EARLY_BIRD', label: t('offers.earlyBird') },
  { value: 'LAST_MINUTE', label: t('offers.lastMinute') },
  { value: 'SEASONAL', label: t('offers.seasonal') },
  { value: 'DEMAND', label: t('offers.demand') },
  { value: 'AI', label: 'AI-Optimiert' },
];

function getTypeBadgeClass(type: PricingRuleType): string {
  switch (type) {
    case 'EARLY_BIRD':
      return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700';
    case 'LAST_MINUTE':
      return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700';
    case 'SEASONAL':
      return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700';
    case 'DEMAND':
      return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700';
    case 'AI':
      return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-fuchsia-100 text-fuchsia-700';
    default:
      return BADGE_STYLES.default;
  }
}

function getTypeLabel(type: PricingRuleType): string {
  switch (type) {
    case 'EARLY_BIRD': return t('offers.earlyBird');
    case 'LAST_MINUTE': return t('offers.lastMinute');
    case 'SEASONAL': return t('offers.seasonal');
    case 'DEMAND': return t('offers.demand');
    case 'AI': return 'AI-Optimiert';
    default: return type;
  }
}

function getTypeIcon(type: PricingRuleType): string {
  switch (type) {
    case 'EARLY_BIRD': return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
    case 'LAST_MINUTE': return 'M13 10V3L4 14h7v7l9-11h-7z';
    case 'SEASONAL': return 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z';
    case 'DEMAND': return 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6';
    case 'AI': return 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z';
    default: return 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z';
  }
}

function getConditionsSummary(rule: PricingRule): string {
  const parts: string[] = [];
  if (rule.conditions.daysBeforeMin !== undefined || rule.conditions.daysBeforeMax !== undefined) {
    const min = rule.conditions.daysBeforeMin ?? 0;
    const max = rule.conditions.daysBeforeMax ?? '...';
    parts.push(`${min}-${max} Tage vorher`);
  }
  if (rule.conditions.dateRange) {
    parts.push(`${rule.conditions.dateRange.start} - ${rule.conditions.dateRange.end}`);
  }
  if (rule.conditions.timeRange) {
    parts.push(`${rule.conditions.timeRange.start} - ${rule.conditions.timeRange.end}`);
  }
  return parts.length > 0 ? parts.join(' | ') : 'Keine Bedingungen';
}

function getCardGradient(type: PricingRuleType): string {
  switch (type) {
    case 'EARLY_BIRD': return 'from-emerald-100 to-green-50';
    case 'LAST_MINUTE': return 'from-amber-100 to-yellow-50';
    case 'SEASONAL': return 'from-blue-100 to-sky-50';
    case 'DEMAND': return 'from-purple-100 to-violet-50';
    case 'AI': return 'from-fuchsia-100 to-pink-50';
    default: return 'from-slate-100 to-slate-50';
  }
}

function onToggleActive(rule: PricingRule) {
  store.togglePricingRuleActive(rule.id);
  const label = rule.active ? 'aktiviert' : 'deaktiviert';
  toast.success(`${rule.name} ${label}`);
}

function onCreateRule() {
  editingRule.value = null;
  form.value = {
    name: '',
    type: 'EARLY_BIRD',
    discountPercent: 10,
    conditions: {},
    active: true,
  };
  daysBeforeMin.value = 0;
  daysBeforeMax.value = 14;
  dateRangeStart.value = '';
  dateRangeEnd.value = '';
  timeRangeStart.value = '08:00';
  timeRangeEnd.value = '10:00';
  errors.value = {};
  showModal.value = true;
}

function onEditRule(rule: PricingRule) {
  editingRule.value = rule;
  form.value = {
    name: rule.name,
    type: rule.type,
    discountPercent: rule.discountPercent,
    conditions: { ...rule.conditions },
    active: rule.active,
  };
  daysBeforeMin.value = rule.conditions.daysBeforeMin ?? 0;
  daysBeforeMax.value = rule.conditions.daysBeforeMax ?? 14;
  dateRangeStart.value = rule.conditions.dateRange?.start || '';
  dateRangeEnd.value = rule.conditions.dateRange?.end || '';
  timeRangeStart.value = rule.conditions.timeRange?.start || '08:00';
  timeRangeEnd.value = rule.conditions.timeRange?.end || '10:00';
  errors.value = {};
  showModal.value = true;
}

function onDeleteRule(rule: PricingRule) {
  store.deletePricingRule(rule.id);
  toast.success(`${rule.name} geloescht`);
}

const needsDaysBefore = computed(() =>
  form.value.type === 'EARLY_BIRD' || form.value.type === 'LAST_MINUTE'
);

const needsDateRange = computed(() =>
  form.value.type === 'SEASONAL'
);

const needsTimeRange = computed(() =>
  form.value.type === 'DEMAND'
);

function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!form.value.name.trim()) {
    errs.name = t('common.required');
  }
  if (form.value.discountPercent <= 0 || form.value.discountPercent > 100) {
    errs.discount = '1-100%';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

function buildConditions(): PricingRuleConditions {
  const conditions: PricingRuleConditions = {};
  if (needsDaysBefore.value) {
    conditions.daysBeforeMin = daysBeforeMin.value;
    conditions.daysBeforeMax = daysBeforeMax.value;
  }
  if (needsDateRange.value && dateRangeStart.value && dateRangeEnd.value) {
    conditions.dateRange = { start: dateRangeStart.value, end: dateRangeEnd.value };
  }
  if (needsTimeRange.value) {
    conditions.timeRange = { start: timeRangeStart.value, end: timeRangeEnd.value };
  }
  return conditions;
}

function onSave() {
  if (!validate()) return;

  const conditions = buildConditions();

  if (editingRule.value) {
    store.updatePricingRule(editingRule.value.id, {
      name: form.value.name,
      type: form.value.type,
      discountPercent: form.value.discountPercent,
      conditions,
      active: form.value.active,
    });
    toast.success(t('common.saved'));
  } else {
    store.addPricingRule({
      name: form.value.name,
      type: form.value.type,
      discountPercent: form.value.discountPercent,
      conditions,
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
      <div>
        <h2 class="text-lg font-semibold text-slate-900">{{ t('offers.dynamicPricing') }}</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ t('offers.pricingRules') }}</p>
      </div>
      <BButton variant="primary" @click="onCreateRule">
        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('offers.pricingRules') }} {{ t('common.create') }}
      </BButton>
    </div>

    <!-- Leerer Zustand -->
    <BEmptyState
      v-if="store.pricingRules.length === 0"
      title="Keine Preisregeln"
      description="Erstellen Sie Ihre erste dynamische Preisregel."
      icon="chart"
      :action-label="t('common.create')"
      @action="onCreateRule"
    />

    <!-- Pricing Rules Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="rule in store.pricingRules"
        :key="rule.id"
        :class="CARD_STYLES.gridItem"
        :style="{ opacity: rule.active ? 1 : 0.6 }"
      >
        <!-- Typ-Header -->
        <div
          class="h-24 bg-gradient-to-br flex items-center justify-center relative"
          :class="getCardGradient(rule.type)"
        >
          <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getTypeIcon(rule.type)" />
          </svg>
          <div class="absolute top-3 right-3">
            <span :class="getTypeBadgeClass(rule.type)">
              {{ getTypeLabel(rule.type) }}
            </span>
          </div>
          <!-- Discount badge -->
          <div class="absolute bottom-3 left-3">
            <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-white/80 text-slate-900 shadow-sm">
              -{{ rule.discountPercent }}%
            </span>
          </div>
        </div>

        <!-- Inhalt -->
        <div class="p-4 flex-1 flex flex-col">
          <h3 class="text-sm font-semibold text-slate-900 mb-1">{{ rule.name }}</h3>
          <p class="text-xs text-slate-500 mb-3">{{ getConditionsSummary(rule) }}</p>

          <div class="mt-auto">
            <!-- Aktionen -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <BToggle
                :model-value="rule.active"
                @update:model-value="onToggleActive(rule)"
              />
              <div class="flex gap-1">
                <button
                  :class="BUTTON_STYLES.icon"
                  @click="onEditRule(rule)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  :class="BUTTON_STYLES.icon"
                  @click="onDeleteRule(rule)"
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

    <!-- Pricing Rule Modal -->
    <BModal
      :model-value="showModal"
      :title="editingRule ? t('common.edit') + ': ' + editingRule.name : t('offers.pricingRules') + ' ' + t('common.create')"
      size="md"
      @update:model-value="showModal = false"
      @close="showModal = false"
    >
      <div class="space-y-4">
        <BInput
          v-model="form.name"
          :label="t('common.name')"
          :placeholder="t('offers.pricingRules') + ' Name'"
          :required="true"
          :error="errors.name"
        />

        <BSelect
          v-model="form.type"
          label="Typ"
          :options="typeOptions"
          :required="true"
        />

        <BInput
          v-model="form.discountPercent"
          type="number"
          label="Rabatt (%)"
          :required="true"
          :error="errors.discount"
          :hint="'1-100%'"
        />

        <!-- Bedingungen: Tage vor Buchung (Early Bird / Last Minute) -->
        <div v-if="needsDaysBefore" :class="CARD_STYLES.ghost" class="p-4">
          <label :class="LABEL_STYLES.base" class="!mb-3">Buchungszeitraum (Tage vorher)</label>
          <div class="grid grid-cols-2 gap-4">
            <BInput
              v-model="daysBeforeMin"
              type="number"
              label="Min. Tage"
            />
            <BInput
              v-model="daysBeforeMax"
              type="number"
              label="Max. Tage"
            />
          </div>
        </div>

        <!-- Bedingungen: Datumsbereich (Seasonal) -->
        <div v-if="needsDateRange" :class="CARD_STYLES.ghost" class="p-4">
          <label :class="LABEL_STYLES.base" class="!mb-3">Saisonzeitraum</label>
          <div class="grid grid-cols-2 gap-4">
            <BInput
              v-model="dateRangeStart"
              type="date"
              label="Von"
            />
            <BInput
              v-model="dateRangeEnd"
              type="date"
              label="Bis"
            />
          </div>
        </div>

        <!-- Bedingungen: Zeitfenster (Demand) -->
        <div v-if="needsTimeRange" :class="CARD_STYLES.ghost" class="p-4">
          <label :class="LABEL_STYLES.base" class="!mb-3">Zeitfenster</label>
          <div class="grid grid-cols-2 gap-4">
            <BInput
              v-model="timeRangeStart"
              type="time"
              label="Von"
            />
            <BInput
              v-model="timeRangeEnd"
              type="time"
              label="Bis"
            />
          </div>
        </div>

        <!-- AI Hinweis -->
        <div v-if="form.type === 'AI'" :class="CARD_STYLES.ghost" class="p-4">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-fuchsia-100 rounded-lg flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-700">AI-optimierte Preisgestaltung</p>
              <p class="text-xs text-slate-500 mt-0.5">
                Preise werden automatisch basierend auf Nachfrage, Auslastung und historischen Daten angepasst.
                Der Rabatt dient als maximaler Rabatt.
              </p>
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
