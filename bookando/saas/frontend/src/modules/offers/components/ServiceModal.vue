<script setup lang="ts">
/**
 * ServiceModal — Erstellen/Bearbeiten einer Dienstleistung
 *
 * Tabs: Details, Pricing, Availability, Media
 * Vollstaendige Validierung und reaktive Formularverwaltung.
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useOffersStore } from '@/stores/offers';
import type { ServiceItem, ServiceType } from '@/stores/offers';
import { BUTTON_STYLES, CARD_STYLES, BADGE_STYLES, INPUT_STYLES, MODAL_STYLES, TAB_STYLES, LABEL_STYLES } from '@/design';
import BModal from '@/components/ui/BModal.vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BToggle from '@/components/ui/BToggle.vue';

const props = defineProps<{
  modelValue: boolean;
  service?: ServiceItem | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved'): void;
}>();

const { t } = useI18n();
const toast = useToast();
const appStore = useAppStore();
const store = useOffersStore();

// Modal Tab State
const activeModalTab = ref<'details' | 'pricing' | 'availability' | 'media'>('details');

// Form data
const form = ref({
  title: '',
  description: '',
  type: 'SERVICE' as ServiceType,
  categoryId: '',
  duration: 60,
  tags: [] as string[],
  priceMinor: 0,
  salePriceMinor: undefined as number | undefined,
  pricingRuleId: '' as string | undefined,
  active: true,
  // Availability
  availableDays: [true, true, true, true, true, false, false] as boolean[],
  timeSlots: [{ start: '08:00', end: '18:00' }] as { start: string; end: string }[],
});

// Validation
const errors = ref<Record<string, string>>({});
const tagInput = ref('');

const isEditing = computed(() => !!props.service);

const modalTitle = computed(() =>
  isEditing.value ? t('common.edit') + ': ' + props.service?.title : t('offers.newService')
);

const typeOptions = [
  { value: 'SERVICE', label: t('offers.serviceTypes.service') },
  { value: 'EVENT', label: t('offers.serviceTypes.event') },
  { value: 'ONLINE_COURSE', label: t('offers.serviceTypes.onlineCourse') },
];

const categoryOptions = computed(() =>
  store.categories.map(c => ({ value: c.id, label: c.name }))
);

const pricingRuleOptions = computed(() => [
  { value: '', label: '-- ' + t('offers.pricingRules') + ' --' },
  ...store.pricingRules.filter(r => r.active).map(r => ({
    value: r.id,
    label: `${r.name} (${r.discountPercent}%)`,
  })),
]);

const dayLabels = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

// Preis-Helper: Umrechnung Minor <-> Major fuer Input
const priceDisplay = computed({
  get: () => form.value.priceMinor / 100,
  set: (val: number) => { form.value.priceMinor = Math.round(val * 100); },
});

const salePriceDisplay = computed({
  get: () => (form.value.salePriceMinor ?? 0) / 100,
  set: (val: number) => {
    form.value.salePriceMinor = val > 0 ? Math.round(val * 100) : undefined;
  },
});

// Watch fuer Edit-Mode
watch(() => props.modelValue, (open) => {
  if (open) {
    activeModalTab.value = 'details';
    errors.value = {};
    if (props.service) {
      form.value = {
        title: props.service.title,
        description: props.service.description,
        type: props.service.type,
        categoryId: props.service.categoryId,
        duration: props.service.duration,
        tags: [...props.service.tags],
        priceMinor: props.service.priceMinor,
        salePriceMinor: props.service.salePriceMinor,
        pricingRuleId: props.service.pricingRuleId,
        active: props.service.active,
        availableDays: [true, true, true, true, true, false, false],
        timeSlots: [{ start: '08:00', end: '18:00' }],
      };
    } else {
      form.value = {
        title: '',
        description: '',
        type: 'SERVICE',
        categoryId: store.categories[0]?.id || '',
        duration: 60,
        tags: [],
        priceMinor: 0,
        salePriceMinor: undefined,
        pricingRuleId: undefined,
        active: true,
        availableDays: [true, true, true, true, true, false, false],
        timeSlots: [{ start: '08:00', end: '18:00' }],
      };
    }
  }
});

function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!form.value.title.trim()) {
    errs.title = t('common.required');
  }
  if (form.value.priceMinor <= 0) {
    errs.price = t('offers.price') + ' > 0';
  }
  if (form.value.duration <= 0) {
    errs.duration = t('offers.duration') + ' > 0';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

function addTag() {
  const tag = tagInput.value.trim().toLowerCase();
  if (tag && !form.value.tags.includes(tag)) {
    form.value.tags.push(tag);
  }
  tagInput.value = '';
}

function removeTag(tag: string) {
  form.value.tags = form.value.tags.filter(t => t !== tag);
}

function addTimeSlot() {
  form.value.timeSlots.push({ start: '08:00', end: '18:00' });
}

function removeTimeSlot(index: number) {
  form.value.timeSlots.splice(index, 1);
}

function onSave() {
  if (!validate()) {
    activeModalTab.value = 'details';
    if (errors.value.price || errors.value.duration) {
      if (!errors.value.title) {
        activeModalTab.value = 'pricing';
      }
    }
    return;
  }

  const categoryName = store.getCategoryById(form.value.categoryId)?.name || '';

  if (isEditing.value && props.service) {
    store.updateService(props.service.id, {
      title: form.value.title,
      description: form.value.description,
      type: form.value.type,
      categoryId: form.value.categoryId,
      categoryName,
      duration: form.value.duration,
      tags: form.value.tags,
      priceMinor: form.value.priceMinor,
      salePriceMinor: form.value.salePriceMinor,
      pricingRuleId: form.value.pricingRuleId || undefined,
      active: form.value.active,
    });
    toast.success(t('common.saved'));
  } else {
    store.addService({
      title: form.value.title,
      description: form.value.description,
      type: form.value.type,
      categoryId: form.value.categoryId,
      categoryName,
      priceMinor: form.value.priceMinor,
      salePriceMinor: form.value.salePriceMinor,
      currency: appStore.currency,
      duration: form.value.duration,
      active: form.value.active,
      tags: form.value.tags,
      pricingRuleId: form.value.pricingRuleId || undefined,
    });
    toast.success(t('common.saved'));
  }

  emit('saved');
}

function onClose() {
  emit('update:modelValue', false);
}
</script>

<template>
  <BModal
    :model-value="modelValue"
    :title="modalTitle"
    size="lg"
    @update:model-value="onClose"
    @close="onClose"
  >
    <!-- Modal Tabs -->
    <div class="border-b border-slate-200 -mx-6 -mt-6 px-6 mb-6">
      <nav class="flex gap-1 -mb-px" role="tablist">
        <button
          v-for="tab in (['details', 'pricing', 'availability', 'media'] as const)"
          :key="tab"
          role="tab"
          :aria-selected="activeModalTab === tab"
          :class="[
            'px-4 py-3 text-sm font-medium border-b-2 transition-all duration-200 whitespace-nowrap',
            activeModalTab === tab
              ? 'text-blue-700 border-current'
              : 'text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300',
          ]"
          @click="activeModalTab = tab"
        >
          {{
            tab === 'details' ? t('common.details') :
            tab === 'pricing' ? t('offers.price') :
            tab === 'availability' ? t('offers.availability') :
            t('offers.image')
          }}
        </button>
      </nav>
    </div>

    <!-- Details Tab -->
    <div v-if="activeModalTab === 'details'" class="space-y-4">
      <BInput
        v-model="form.title"
        :label="t('common.name')"
        :placeholder="t('offers.newService')"
        :required="true"
        :error="errors.title"
      />

      <BTextarea
        v-model="form.description"
        :label="t('offers.description')"
        :placeholder="t('offers.description') + '...'"
        :rows="3"
      />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BSelect
          v-model="form.type"
          :label="t('common.type')"
          :options="typeOptions"
          :required="true"
        />
        <BSelect
          v-model="form.categoryId"
          :label="t('offers.categories')"
          :options="categoryOptions"
          :required="true"
        />
      </div>

      <BInput
        v-model="form.duration"
        type="number"
        :label="t('offers.duration') + ' (min)'"
        :error="errors.duration"
        :required="true"
      />

      <!-- Tags -->
      <div>
        <label :class="LABEL_STYLES.base">{{ t('offers.tags') }}</label>
        <div class="flex gap-2 mb-2">
          <input
            v-model="tagInput"
            :class="INPUT_STYLES.base"
            class="flex-1"
            placeholder="Tag..."
            @keydown.enter.prevent="addTag"
          />
          <BButton variant="secondary" @click="addTag">+</BButton>
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

      <!-- Active Toggle -->
      <BToggle
        v-model="form.active"
        :label="t('common.active')"
      />
    </div>

    <!-- Pricing Tab -->
    <div v-else-if="activeModalTab === 'pricing'" class="space-y-4">
      <BInput
        v-model="priceDisplay"
        type="number"
        :label="t('offers.price') + ' (' + appStore.currency + ')'"
        :placeholder="'0.00'"
        :required="true"
        :error="errors.price"
      />

      <BInput
        v-model="salePriceDisplay"
        type="number"
        :label="t('offers.salePrice') + ' (' + appStore.currency + ')'"
        :placeholder="'0.00'"
        :hint="form.salePriceMinor ? appStore.formatPrice(form.priceMinor - (form.salePriceMinor || 0)) + ' ' + t('offers.savings') : ''"
      />

      <BSelect
        v-model="form.pricingRuleId"
        :label="t('offers.pricingRules')"
        :options="pricingRuleOptions"
      />

      <!-- Preis-Vorschau -->
      <div v-if="form.priceMinor > 0" :class="CARD_STYLES.ghost" class="p-4">
        <h4 class="text-sm font-medium text-slate-700 mb-2">{{ t('common.preview') }}</h4>
        <div class="flex items-baseline gap-2">
          <span v-if="form.salePriceMinor" class="text-sm text-slate-400 line-through">
            {{ appStore.formatPrice(form.priceMinor) }}
          </span>
          <span class="text-xl font-bold text-slate-900">
            {{ appStore.formatPrice(form.salePriceMinor || form.priceMinor) }}
          </span>
          <span v-if="form.salePriceMinor" class="text-xs text-emerald-600 font-medium">
            -{{ Math.round(((form.priceMinor - (form.salePriceMinor || 0)) / form.priceMinor) * 100) }}%
          </span>
        </div>
      </div>
    </div>

    <!-- Availability Tab -->
    <div v-else-if="activeModalTab === 'availability'" class="space-y-6">
      <!-- Wochentage -->
      <div>
        <label :class="LABEL_STYLES.base">{{ t('offers.availability') }}</label>
        <div class="flex gap-2 mt-2">
          <button
            v-for="(day, index) in dayLabels"
            :key="day"
            :class="[
              'w-10 h-10 rounded-lg text-sm font-medium transition-all duration-200',
              form.availableDays[index]
                ? 'bg-blue-600 text-white shadow-sm'
                : 'bg-slate-100 text-slate-500 hover:bg-slate-200',
            ]"
            @click="form.availableDays[index] = !form.availableDays[index]"
          >
            {{ day }}
          </button>
        </div>
      </div>

      <!-- Zeitfenster -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <label :class="LABEL_STYLES.base" class="!mb-0">Zeitfenster</label>
          <BButton variant="ghost" @click="addTimeSlot">
            <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Hinzufuegen
          </BButton>
        </div>
        <div class="space-y-2">
          <div
            v-for="(slot, index) in form.timeSlots"
            :key="index"
            class="flex items-center gap-3"
          >
            <BInput
              v-model="slot.start"
              type="time"
              class="flex-1"
            />
            <span class="text-slate-400 text-sm">-</span>
            <BInput
              v-model="slot.end"
              type="time"
              class="flex-1"
            />
            <button
              v-if="form.timeSlots.length > 1"
              :class="BUTTON_STYLES.icon"
              @click="removeTimeSlot(index)"
            >
              <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Media Tab -->
    <div v-else-if="activeModalTab === 'media'" class="space-y-4">
      <!-- Bild-Upload-Platzhalter -->
      <div>
        <label :class="LABEL_STYLES.base">{{ t('offers.image') }}</label>
        <div :class="CARD_STYLES.empty" class="!p-12">
          <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-sm text-slate-500 font-medium">{{ t('offers.image') }} hochladen</p>
          <p class="text-xs text-slate-400 mt-1">JPG, PNG oder WebP, max 5MB</p>
        </div>
      </div>

      <!-- Galerie-Platzhalter -->
      <div>
        <label :class="LABEL_STYLES.base">Galerie</label>
        <div class="grid grid-cols-3 gap-3">
          <div :class="CARD_STYLES.empty" class="!p-6 aspect-square">
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <BButton variant="secondary" @click="onClose">
        {{ t('common.cancel') }}
      </BButton>
      <BButton variant="primary" @click="onSave">
        {{ t('common.save') }}
      </BButton>
    </template>
  </BModal>
</template>
