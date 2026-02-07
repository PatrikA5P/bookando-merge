<script setup lang="ts">
/**
 * OfferFormPanel — Erstellen/Bearbeiten aller Angebotstypen
 *
 * GOLD STANDARD: Verwendet BFormPanel (SlideIn) statt BModal (Overlay).
 *
 * Workflow:
 * 1. Neues Angebot: Typ-Auswahl → typ-spezifisches Formular
 * 2. Bearbeiten: Formular direkt, Typ gesperrt
 *
 * Typ-spezifische Tabs:
 * - SERVICE: Allgemein, Preise, Verfuegbarkeit, Regeln, Medien
 * - EVENT: Allgemein, Preise, Sessions, Regeln, Medien
 * - ONLINE_COURSE: Allgemein, Preise, Kursinhalt, Medien
 */
import { ref, computed, watch, reactive } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useOffersStore } from '@/stores/offers';
import type { Offer, OfferType, ServiceOffer, EventOffer, OnlineCourseOffer } from '@/types/domain/offers';
import { isServiceOffer, isEventOffer, isOnlineCourseOffer } from '@/types/domain/offers';
import { formatMoney, toMajorUnits, toMinorUnits } from '@/utils/money';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import BMoneyInput from '@/components/ui/BMoneyInput.vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BBadge from '@/components/ui/BBadge.vue';
import { BADGE_STYLES, CARD_STYLES, INPUT_STYLES, LABEL_STYLES } from '@/design';

const props = defineProps<{
  modelValue: boolean;
  offer?: Offer | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved'): void;
}>();

const { t } = useI18n();
const toast = useToast();
const appStore = useAppStore();
const store = useOffersStore();

// ---- State ----
const saving = ref(false);
const selectedType = ref<OfferType | null>(null);
const errors = ref<Record<string, string>>({});
const tagInput = ref('');

const isEditing = computed(() => !!props.offer);
const isTypeSelected = computed(() => isEditing.value || selectedType.value !== null);
const currentType = computed<OfferType | null>(() =>
  isEditing.value ? (props.offer?.offerType ?? null) : selectedType.value
);

// ---- Tabs per Type ----
const tabsForType = computed<string[]>(() => {
  switch (currentType.value) {
    case 'SERVICE':
      return ['Allgemein', 'Preise', 'Verfuegbarkeit', 'Regeln', 'Medien'];
    case 'EVENT':
      return ['Allgemein', 'Preise', 'Sessions', 'Regeln', 'Medien'];
    case 'ONLINE_COURSE':
      return ['Allgemein', 'Preise', 'Kursinhalt', 'Medien'];
    default:
      return [];
  }
});

const panelTitle = computed(() => {
  if (!isTypeSelected.value) return 'Neues Angebot';
  if (isEditing.value) return props.offer?.title || 'Angebot bearbeiten';
  const typeLabels: Record<OfferType, string> = {
    SERVICE: 'Neue Dienstleistung',
    EVENT: 'Neuer Kurs / Event',
    ONLINE_COURSE: 'Neuer Onlinekurs',
  };
  return typeLabels[currentType.value!];
});

// ---- Form Data ----
const form = reactive({
  // Basis
  title: '',
  description: '',
  categoryId: '',
  tags: [] as string[],
  status: 'DRAFT' as Offer['status'],
  visibility: 'PUBLIC' as Offer['visibility'],
  priceCents: 0,
  salePriceCents: undefined as number | undefined,
  currency: 'CHF',
  vatRate: 8.1,
  pricingRuleId: undefined as string | undefined,
  paymentOptions: ['ON_SITE'] as string[],
  formTemplateId: undefined as string | undefined,
  defaultBookingStatus: 'CONFIRMED' as 'CONFIRMED' | 'PENDING',
  extraIds: [] as string[],

  // Service-spezifisch
  durationMinutes: 60,
  bufferBeforeMin: 0,
  bufferAfterMin: 0,
  slotIntervalMin: 30,
  bookingWindowDaysAhead: 30,
  minNoticeHours: 24,
  cancelNoticeHours: 24,
  rescheduleNoticeHours: 24,
  maxParticipants: 1,
  allowGroupBooking: false,
  maxGroupSize: undefined as number | undefined,
  assignmentStrategy: 'WORKLOAD_BALANCE' as string,
  isRecurring: false,

  // Event-spezifisch
  eventStructure: 'SINGLE' as string,
  eventMaxParticipants: undefined as number | undefined,
  eventMinParticipants: undefined as number | undefined,
  bookingOpensImmediately: true,
  bookingClosesOnStart: false,
  autoCancelBelowMin: false,
  waitlistEnabled: false,
  waitlistCapacity: undefined as number | undefined,

  // Online Course spezifisch
  academyCourseId: undefined as string | undefined,
  onlineMaxParticipants: undefined as number | undefined,
  accessDurationDays: undefined as number | undefined,
  integrationType: 'NONE' as string,
});

const dirty = computed(() => {
  // Simplified dirty check — in production use deep comparison
  return form.title !== '' || form.priceCents > 0;
});

// ---- Category & Pricing Rule Options ----
const categoryOptions = computed(() =>
  store.categories.map(c => ({ value: c.id, label: c.name }))
);

const pricingRuleOptions = computed(() => [
  { value: '', label: '-- Keine Preisregel --' },
  ...store.pricingRules.filter(r => r.active).map(r => ({
    value: r.id,
    label: `${r.name} (${r.discountPercent}%)`,
  })),
]);

const assignmentOptions = [
  { value: 'WORKLOAD_BALANCE', label: 'Workload Balance' },
  { value: 'ROUND_ROBIN', label: 'Round Robin' },
  { value: 'SAME_EMPLOYEE', label: 'Gleicher Mitarbeiter' },
  { value: 'PRIORITY', label: 'Prioritaet' },
  { value: 'AVAILABILITY', label: 'Erste Verfuegbarkeit' },
];

const eventStructureOptions = [
  { value: 'SINGLE', label: 'Einzeltermin' },
  { value: 'SERIES_ALL', label: 'Serie (gesamte Buchung)' },
  { value: 'SERIES_DROP_IN', label: 'Serie (Einzelbuchung pro Termin)' },
];

const integrationOptions = [
  { value: 'NONE', label: 'Keine' },
  { value: 'ZOOM', label: 'Zoom' },
  { value: 'GOOGLE_MEET', label: 'Google Meet' },
  { value: 'MS_TEAMS', label: 'Microsoft Teams' },
];

// ---- Init Form on Open / Edit ----
watch(() => props.modelValue, (open) => {
  if (open) {
    errors.value = {};
    if (props.offer) {
      populateFromOffer(props.offer);
    } else {
      resetForm();
      selectedType.value = null;
    }
  }
});

function resetForm() {
  form.title = '';
  form.description = '';
  form.categoryId = store.categories[0]?.id || '';
  form.tags = [];
  form.status = 'DRAFT';
  form.visibility = 'PUBLIC';
  form.priceCents = 0;
  form.salePriceCents = undefined;
  form.currency = appStore.currency;
  form.vatRate = 8.1;
  form.pricingRuleId = undefined;
  form.paymentOptions = ['ON_SITE'];
  form.extraIds = [];
  form.durationMinutes = 60;
  form.bufferBeforeMin = 0;
  form.bufferAfterMin = 0;
  form.slotIntervalMin = 30;
  form.bookingWindowDaysAhead = 30;
  form.minNoticeHours = 24;
  form.cancelNoticeHours = 24;
  form.rescheduleNoticeHours = 24;
  form.maxParticipants = 1;
  form.allowGroupBooking = false;
  form.assignmentStrategy = 'WORKLOAD_BALANCE';
  form.isRecurring = false;
  form.eventStructure = 'SINGLE';
  form.eventMaxParticipants = undefined;
  form.eventMinParticipants = undefined;
  form.bookingOpensImmediately = true;
  form.bookingClosesOnStart = false;
  form.autoCancelBelowMin = false;
  form.waitlistEnabled = false;
  form.academyCourseId = undefined;
  form.onlineMaxParticipants = undefined;
  form.accessDurationDays = undefined;
  form.integrationType = 'NONE';
}

function populateFromOffer(offer: Offer) {
  form.title = offer.title;
  form.description = offer.description;
  form.categoryId = offer.categoryId;
  form.tags = [...offer.tags];
  form.status = offer.status;
  form.visibility = offer.visibility;
  form.priceCents = offer.priceCents;
  form.salePriceCents = offer.salePriceCents;
  form.currency = offer.currency;
  form.vatRate = offer.vatRate ?? 8.1;
  form.pricingRuleId = offer.pricingRuleId;
  form.paymentOptions = [...offer.paymentOptions];
  form.extraIds = [...offer.extraIds];
  form.defaultBookingStatus = offer.defaultBookingStatus;

  if (isServiceOffer(offer)) {
    const c = offer.serviceConfig;
    form.durationMinutes = c.durationMinutes;
    form.bufferBeforeMin = c.bufferBeforeMin;
    form.bufferAfterMin = c.bufferAfterMin;
    form.slotIntervalMin = c.slotIntervalMin;
    form.bookingWindowDaysAhead = c.bookingWindowDaysAhead;
    form.minNoticeHours = c.minNoticeHours;
    form.cancelNoticeHours = c.cancelNoticeHours;
    form.rescheduleNoticeHours = c.rescheduleNoticeHours;
    form.maxParticipants = c.maxParticipants;
    form.allowGroupBooking = c.allowGroupBooking;
    form.maxGroupSize = c.maxGroupSize;
    form.assignmentStrategy = c.assignmentStrategy;
    form.isRecurring = c.isRecurring;
  } else if (isEventOffer(offer)) {
    const c = offer.eventConfig;
    form.eventStructure = c.eventStructure;
    form.eventMaxParticipants = c.maxParticipants;
    form.eventMinParticipants = c.minParticipants;
    form.bookingOpensImmediately = c.bookingOpensImmediately;
    form.bookingClosesOnStart = c.bookingClosesOnStart;
    form.autoCancelBelowMin = c.autoCancelBelowMin;
    form.waitlistEnabled = c.waitlistEnabled;
    form.waitlistCapacity = c.waitlistCapacity;
  } else if (isOnlineCourseOffer(offer)) {
    const c = offer.onlineCourseConfig;
    form.academyCourseId = c.academyCourseId;
    form.onlineMaxParticipants = c.maxParticipants;
    form.accessDurationDays = c.accessDurationDays;
    form.integrationType = c.integrationType;
  }
}

// ---- Tags ----
function addTag() {
  const tag = tagInput.value.trim().toLowerCase();
  if (tag && !form.tags.includes(tag)) {
    form.tags.push(tag);
  }
  tagInput.value = '';
}

function removeTag(tag: string) {
  form.tags = form.tags.filter(t => t !== tag);
}

// ---- Validation ----
function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!form.title.trim()) errs.title = 'Titel ist erforderlich';
  if (!form.categoryId) errs.categoryId = 'Kategorie ist erforderlich';
  if (form.priceCents <= 0) errs.price = 'Preis muss groesser als 0 sein';
  if (currentType.value === 'SERVICE' && form.durationMinutes <= 0) {
    errs.duration = 'Dauer muss groesser als 0 sein';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

// ---- Save ----
async function handleSave() {
  if (!validate() || !currentType.value) return;

  saving.value = true;
  try {
    const baseData = {
      title: form.title,
      description: form.description,
      categoryId: form.categoryId,
      tags: form.tags,
      status: form.status,
      visibility: form.visibility,
      priceCents: form.priceCents,
      salePriceCents: form.salePriceCents,
      currency: form.currency,
      vatRate: form.vatRate,
      pricingRuleId: form.pricingRuleId || undefined,
      paymentOptions: form.paymentOptions,
      defaultBookingStatus: form.defaultBookingStatus,
      formTemplateId: form.formTemplateId,
      extraIds: form.extraIds,
      customerSelectableExtraIds: [],
      coverImageUrl: undefined,
      galleryUrls: [],
    };

    if (isEditing.value && props.offer) {
      await store.updateOffer(props.offer.id, {
        ...baseData,
        offerType: currentType.value,
      } as Partial<Offer>);
    } else {
      await store.addOffer({
        ...baseData,
        offerType: currentType.value,
        ...(currentType.value === 'SERVICE' ? {
          serviceConfig: {
            durationMinutes: form.durationMinutes,
            bufferBeforeMin: form.bufferBeforeMin,
            bufferAfterMin: form.bufferAfterMin,
            slotIntervalMin: form.slotIntervalMin,
            bookingWindowDaysAhead: form.bookingWindowDaysAhead,
            minNoticeHours: form.minNoticeHours,
            cancelNoticeHours: form.cancelNoticeHours,
            rescheduleNoticeHours: form.rescheduleNoticeHours,
            maxParticipants: form.maxParticipants,
            allowGroupBooking: form.allowGroupBooking,
            maxGroupSize: form.maxGroupSize,
            assignmentStrategy: form.assignmentStrategy as any,
            isRecurring: form.isRecurring,
            resourceRequirements: [],
          },
        } : {}),
        ...(currentType.value === 'EVENT' ? {
          eventConfig: {
            eventStructure: form.eventStructure as any,
            maxParticipants: form.eventMaxParticipants,
            minParticipants: form.eventMinParticipants,
            bookingOpensImmediately: form.bookingOpensImmediately,
            bookingClosesOnStart: form.bookingClosesOnStart,
            autoCancelBelowMin: form.autoCancelBelowMin,
            autoCancelHoursBefore: 48,
            waitlistEnabled: form.waitlistEnabled,
            waitlistCapacity: form.waitlistCapacity,
            resourceRequirements: [],
          },
        } : {}),
        ...(currentType.value === 'ONLINE_COURSE' ? {
          onlineCourseConfig: {
            academyCourseId: form.academyCourseId,
            maxParticipants: form.onlineMaxParticipants,
            accessDurationDays: form.accessDurationDays,
            integrationType: form.integrationType as any,
          },
        } : {}),
      } as any);
    }

    toast.success('Angebot gespeichert');
    emit('saved');
    emit('update:modelValue', false);
  } catch (e) {
    toast.error('Fehler beim Speichern');
  } finally {
    saving.value = false;
  }
}

function handleCancel() {
  emit('update:modelValue', false);
}

function selectType(type: OfferType) {
  selectedType.value = type;
}
</script>

<template>
  <!-- Type Selection Screen -->
  <BFormPanel
    v-if="!isTypeSelected"
    :model-value="modelValue"
    title="Neues Angebot"
    subtitle="Waehle den Angebotstyp"
    size="md"
    mode="create"
    save-label="Weiter"
    :disabled="true"
    @update:model-value="emit('update:modelValue', $event)"
    @cancel="handleCancel"
  >
    <div class="space-y-3">
      <!-- Dienstleistung -->
      <button
        class="w-full text-left p-5 rounded-xl border-2 transition-all duration-200 hover:border-blue-400 hover:bg-blue-50/50 border-slate-200 group"
        @click="selectType('SERVICE')"
      >
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 group-hover:bg-blue-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Dienstleistung</h3>
            <p class="text-xs text-slate-500 mt-0.5">Einzeltermine mit Slot-Auswahl. Kunden waehlen Datum und Uhrzeit aus verfuegbaren Zeiten.</p>
            <p class="text-xs text-slate-400 mt-1">z.B. Fahrstunde, Massage, Beratung</p>
          </div>
        </div>
      </button>

      <!-- Kurs / Event -->
      <button
        class="w-full text-left p-5 rounded-xl border-2 transition-all duration-200 hover:border-purple-400 hover:bg-purple-50/50 border-slate-200 group"
        @click="selectType('EVENT')"
      >
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0 group-hover:bg-purple-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Kurs / Event</h3>
            <p class="text-xs text-slate-500 mt-0.5">Fixe Termine mit Teilnehmerlimit. Ein- oder mehrtaegig, als Einzeltermin oder Serie.</p>
            <p class="text-xs text-slate-400 mt-1">z.B. VKU, Motorradgrundkurs, Nothelferkurs</p>
          </div>
        </div>
      </button>

      <!-- Onlinekurs -->
      <button
        class="w-full text-left p-5 rounded-xl border-2 transition-all duration-200 hover:border-amber-400 hover:bg-amber-50/50 border-slate-200 group"
        @click="selectType('ONLINE_COURSE')"
      >
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 group-hover:bg-amber-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-900">Onlinekurs</h3>
            <p class="text-xs text-slate-500 mt-0.5">24/7 verfuegbar, self-paced. Verknuepft mit Academy-Kursinhalten.</p>
            <p class="text-xs text-slate-400 mt-1">z.B. Theorie-Vorbereitung, E-Learning Module</p>
          </div>
        </div>
      </button>
    </div>
  </BFormPanel>

  <!-- Offer Form (type selected) -->
  <BFormPanel
    v-else
    :model-value="modelValue"
    :title="panelTitle"
    :subtitle="isEditing ? 'Angebot bearbeiten' : undefined"
    :tabs="tabsForType"
    :saving="saving"
    :dirty="dirty"
    :mode="isEditing ? 'edit' : 'create'"
    size="lg"
    @update:model-value="emit('update:modelValue', $event)"
    @save="handleSave"
    @cancel="handleCancel"
  >
    <!-- ================================================================ -->
    <!-- TAB 0: ALLGEMEIN (alle Typen) -->
    <!-- ================================================================ -->
    <template #tab-0>
      <BFormSection title="Grunddaten" :columns="1" divided>
        <BInput
          v-model="form.title"
          label="Titel"
          placeholder="z.B. Motorrad-Fahrstunde (50 Min)"
          :required="true"
          :error="errors.title"
        />
        <BTextarea
          v-model="form.description"
          label="Beschreibung"
          placeholder="Beschreibe das Angebot..."
          :rows="3"
        />
      </BFormSection>

      <BFormSection title="Kategorisierung" :columns="2" divided>
        <BSelect
          v-model="form.categoryId"
          label="Kategorie"
          :options="categoryOptions"
          :required="true"
          :error="errors.categoryId"
        />
        <div>
          <label :class="LABEL_STYLES.base">Tags</label>
          <div class="flex gap-2 mb-2">
            <input
              v-model="tagInput"
              :class="INPUT_STYLES.base"
              class="flex-1"
              placeholder="Tag hinzufuegen..."
              @keydown.enter.prevent="addTag"
            />
            <button
              class="px-3 py-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 text-sm font-medium transition-colors"
              @click="addTag"
            >+</button>
          </div>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="tag in form.tags"
              :key="tag"
              :class="BADGE_STYLES.default"
              class="cursor-pointer hover:bg-slate-200 transition-colors"
              @click="removeTag(tag)"
            >
              {{ tag }}
              <svg class="w-3 h-3 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </span>
          </div>
        </div>
      </BFormSection>

      <BFormSection title="Status" :columns="2">
        <BSelect
          v-model="form.status"
          label="Status"
          :options="[
            { value: 'DRAFT', label: 'Entwurf' },
            { value: 'ACTIVE', label: 'Aktiv' },
            { value: 'PAUSED', label: 'Pausiert' },
            { value: 'ARCHIVED', label: 'Archiviert' },
          ]"
        />
        <BSelect
          v-model="form.visibility"
          label="Sichtbarkeit"
          :options="[
            { value: 'PUBLIC', label: 'Oeffentlich' },
            { value: 'UNLISTED', label: 'Nicht gelistet (nur per Link)' },
            { value: 'PRIVATE', label: 'Privat (nur Admin)' },
          ]"
        />
      </BFormSection>
    </template>

    <!-- ================================================================ -->
    <!-- TAB 1: PREISE (alle Typen) -->
    <!-- ================================================================ -->
    <template #tab-1>
      <BFormSection title="Preiskonfiguration" :columns="2" divided>
        <BMoneyInput
          v-model="form.priceCents"
          label="Preis"
          :currency="form.currency"
          :error="errors.price"
          :required="true"
        />
        <BMoneyInput
          v-model="form.salePriceCents!"
          label="Aktionspreis"
          :currency="form.currency"
          hint="Optionaler reduzierter Preis"
        />
      </BFormSection>

      <BFormSection title="Steuer & Zahlungsoptionen" :columns="2" divided>
        <BInput
          v-model="form.vatRate"
          type="number"
          label="MwSt-Satz (%)"
          placeholder="8.10"
          hint="Schweizer Normalsatz: 8.1%"
        />
        <BSelect
          v-model="form.currency"
          label="Waehrung"
          :options="[
            { value: 'CHF', label: 'CHF (Schweizer Franken)' },
            { value: 'EUR', label: 'EUR (Euro)' },
          ]"
        />
      </BFormSection>

      <BFormSection title="Dynamic Pricing" :columns="1">
        <BSelect
          v-model="form.pricingRuleId"
          label="Preisregel"
          :options="pricingRuleOptions"
        />
      </BFormSection>

      <!-- Preis-Vorschau -->
      <div v-if="form.priceCents > 0" :class="CARD_STYLES.ghost" class="p-4 mt-4">
        <h4 class="text-sm font-medium text-slate-700 mb-2">Vorschau</h4>
        <div class="flex items-baseline gap-2">
          <span v-if="form.salePriceCents" class="text-sm text-slate-400 line-through">
            {{ formatMoney(form.priceCents, form.currency) }}
          </span>
          <span class="text-xl font-bold text-slate-900">
            {{ formatMoney(form.salePriceCents || form.priceCents, form.currency) }}
          </span>
          <span v-if="form.salePriceCents && form.priceCents > 0" class="text-xs text-emerald-600 font-medium">
            -{{ Math.round(((form.priceCents - (form.salePriceCents || 0)) / form.priceCents) * 100) }}%
          </span>
        </div>
      </div>
    </template>

    <!-- ================================================================ -->
    <!-- TAB 2: TYP-SPEZIFISCH -->
    <!-- ================================================================ -->
    <template #tab-2>
      <!-- SERVICE: Verfuegbarkeit -->
      <template v-if="currentType === 'SERVICE'">
        <BFormSection title="Dauer & Puffer" :columns="3" divided>
          <BInput v-model="form.durationMinutes" type="number" label="Dauer (Min)" :required="true" :error="errors.duration" />
          <BInput v-model="form.bufferBeforeMin" type="number" label="Puffer vorher (Min)" hint="Vorbereitungszeit" />
          <BInput v-model="form.bufferAfterMin" type="number" label="Puffer nachher (Min)" hint="Nachbereitungszeit" />
        </BFormSection>

        <BFormSection title="Slot-Konfiguration" :columns="2" divided>
          <BInput v-model="form.slotIntervalMin" type="number" label="Slot-Intervall (Min)" hint="Alle X Minuten ein Slot" />
          <BInput v-model="form.bookingWindowDaysAhead" type="number" label="Buchungsfenster (Tage)" hint="Wie weit im Voraus buchbar" />
        </BFormSection>

        <BFormSection title="Fristen" :columns="3" divided>
          <BInput v-model="form.minNoticeHours" type="number" label="Min. Vorlauf Buchung (Std)" />
          <BInput v-model="form.cancelNoticeHours" type="number" label="Stornofrist (Std)" />
          <BInput v-model="form.rescheduleNoticeHours" type="number" label="Umbuchungsfrist (Std)" />
        </BFormSection>

        <BFormSection title="Kapazitaet & Zuweisung" :columns="2">
          <BInput v-model="form.maxParticipants" type="number" label="Max. Teilnehmer" />
          <BSelect v-model="form.assignmentStrategy" label="Zuweisungsstrategie" :options="assignmentOptions" />
          <BToggle v-model="form.allowGroupBooking" label="Gruppenbuchung erlauben" />
          <BToggle v-model="form.isRecurring" label="Wiederkehrende Buchung" />
        </BFormSection>
      </template>

      <!-- EVENT: Sessions -->
      <template v-else-if="currentType === 'EVENT'">
        <BFormSection title="Event-Struktur" :columns="2" divided>
          <BSelect v-model="form.eventStructure" label="Struktur" :options="eventStructureOptions" />
          <BInput v-model="form.eventMaxParticipants" type="number" label="Max. Teilnehmer" />
          <BInput v-model="form.eventMinParticipants" type="number" label="Min. Teilnehmer" hint="Fuer Auto-Cancel wenn unterschritten" />
        </BFormSection>

        <BFormSection title="Buchungsfenster" :columns="1" divided>
          <BToggle v-model="form.bookingOpensImmediately" label="Buchung sofort geoeffnet" />
          <BToggle v-model="form.bookingClosesOnStart" label="Buchung schliesst bei Kursbeginn" />
          <BToggle v-model="form.autoCancelBelowMin" label="Auto-Cancel bei zu wenig Teilnehmern" />
        </BFormSection>

        <BFormSection title="Warteliste" :columns="2">
          <BToggle v-model="form.waitlistEnabled" label="Warteliste aktivieren" />
          <BInput v-if="form.waitlistEnabled" v-model="form.waitlistCapacity" type="number" label="Wartelisten-Kapazitaet" />
        </BFormSection>

        <!-- Session-Management Platzhalter -->
        <div :class="CARD_STYLES.ghost" class="p-6 mt-4 text-center">
          <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-sm font-medium text-slate-600">Sessions verwalten</p>
          <p class="text-xs text-slate-400 mt-1">Nach dem Speichern koennen Sessions/Termine hinzugefuegt werden.</p>
        </div>
      </template>

      <!-- ONLINE COURSE: Kursinhalt -->
      <template v-else-if="currentType === 'ONLINE_COURSE'">
        <BFormSection title="Verknuepfter Academy-Kurs" :columns="1" divided>
          <BSelect
            v-model="form.academyCourseId"
            label="Academy-Kurs"
            :options="[{ value: '', label: '-- Kurs waehlen --' }]"
            hint="Verknuepfe dieses Angebot mit einem Academy-Kurs fuer die Lerninhalte."
          />
        </BFormSection>

        <BFormSection title="Zugang" :columns="2" divided>
          <BInput v-model="form.onlineMaxParticipants" type="number" label="Max. Teilnehmer" hint="Leer = unbegrenzt" />
          <BInput v-model="form.accessDurationDays" type="number" label="Zugang (Tage)" hint="Leer = unbegrenzt" />
        </BFormSection>

        <BFormSection title="Video-Integration" :columns="1">
          <BSelect v-model="form.integrationType" label="Integration" :options="integrationOptions" />
        </BFormSection>
      </template>
    </template>

    <!-- ================================================================ -->
    <!-- TAB 3: REGELN (Service/Event) bzw. MEDIEN (Online Course) -->
    <!-- ================================================================ -->
    <template #tab-3>
      <template v-if="currentType === 'ONLINE_COURSE'">
        <!-- Medien fuer Online Course (Tab 3 = letzter Tab) -->
        <BFormSection title="Coverbild" :columns="1">
          <div :class="CARD_STYLES.empty" class="!p-12">
            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-sm text-slate-500 font-medium">Bild hochladen</p>
            <p class="text-xs text-slate-400 mt-1">JPG, PNG oder WebP, max 5MB</p>
          </div>
        </BFormSection>
      </template>

      <template v-else>
        <!-- Regeln fuer Service/Event -->
        <BFormSection title="Buchungsprozess" :columns="2" divided>
          <BSelect
            v-model="form.defaultBookingStatus"
            label="Standard-Status nach Buchung"
            :options="[
              { value: 'CONFIRMED', label: 'Automatisch bestaetigt' },
              { value: 'PENDING', label: 'Muss bestaetigt werden' },
            ]"
          />
          <BSelect
            v-model="form.formTemplateId"
            label="Buchungsformular"
            :options="[{ value: '', label: '-- Standard --' }]"
          />
        </BFormSection>

        <BFormSection title="Extras & Zusatzoptionen" :columns="1">
          <div v-if="store.activeExtras.length === 0" class="text-sm text-slate-400">
            Keine Extras vorhanden. Erstelle Extras im Tab "Extras".
          </div>
          <div v-else class="space-y-2">
            <label
              v-for="extra in store.activeExtras"
              :key="extra.id"
              class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors"
            >
              <input
                type="checkbox"
                :checked="form.extraIds.includes(extra.id)"
                class="rounded border-slate-300 text-brand-600"
                @change="form.extraIds.includes(extra.id)
                  ? form.extraIds = form.extraIds.filter(id => id !== extra.id)
                  : form.extraIds.push(extra.id)"
              />
              <div class="flex-1">
                <span class="text-sm font-medium text-slate-800">{{ extra.name }}</span>
                <span v-if="extra.description" class="text-xs text-slate-500 block">{{ extra.description }}</span>
              </div>
              <span class="text-sm font-medium text-slate-600">
                {{ formatMoney(extra.priceCents) }}
              </span>
            </label>
          </div>
        </BFormSection>
      </template>
    </template>

    <!-- ================================================================ -->
    <!-- TAB 4: MEDIEN (Service/Event — letzter Tab) -->
    <!-- ================================================================ -->
    <template #tab-4>
      <BFormSection title="Coverbild" :columns="1" divided>
        <div :class="CARD_STYLES.empty" class="!p-12">
          <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-sm text-slate-500 font-medium">Bild hochladen</p>
          <p class="text-xs text-slate-400 mt-1">JPG, PNG oder WebP, max 5MB</p>
        </div>
      </BFormSection>

      <BFormSection title="Galerie" :columns="1">
        <div class="grid grid-cols-3 gap-3">
          <div :class="CARD_STYLES.empty" class="!p-6 aspect-square">
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
        </div>
      </BFormSection>
    </template>

    <!-- Footer Left: Loeschen-Button bei Bearbeiten -->
    <template v-if="isEditing" #footer-left>
      <button
        class="px-4 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
        @click="() => { if (props.offer) store.deleteOffer(props.offer.id); emit('update:modelValue', false); }"
      >
        Loeschen
      </button>
    </template>
  </BFormPanel>
</template>
