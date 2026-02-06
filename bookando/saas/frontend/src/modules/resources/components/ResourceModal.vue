<script setup lang="ts">
/**
 * ResourceModal — Ressource erstellen / bearbeiten
 *
 * Dynamisches Formular basierend auf Ressourcen-Typ:
 * - Location: Name, Adresse, PLZ, Stadt, Land, Telefon, E-Mail
 * - Room: Name, Standort, Kapazitaet, Ausstattung, Status
 * - Equipment: Name, Kategorie, Anzahl, Zustand, Standort
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useResourcesStore, EQUIPMENT_CATEGORIES } from '@/stores/resources';
import type { Location, Room, Equipment } from '@/stores/resources';
import BModal from '@/components/ui/BModal.vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import { LABEL_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const store = useResourcesStore();

const props = defineProps<{
  modelValue: boolean;
  type: 'location' | 'room' | 'equipment';
  item?: Record<string, unknown> | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'save', data: Record<string, unknown>): void;
}>();

// Form data
const locationForm = ref({
  name: '',
  address: '',
  zip: '',
  city: '',
  country: 'CH',
  phone: '',
  email: '',
  status: 'OPEN' as const,
});

const roomForm = ref({
  name: '',
  locationId: '',
  locationName: '',
  capacity: 4,
  status: 'AVAILABLE' as const,
  features: [] as string[],
});

const equipmentForm = ref({
  name: '',
  category: '',
  total: 1,
  available: 1,
  condition: 'GOOD' as const,
  locationId: '',
  locationName: '',
});

const newFeature = ref('');
const errors = ref<Record<string, string>>({});

const isEditing = computed(() => !!props.item);

const modalTitle = computed(() => {
  if (isEditing.value) {
    if (props.type === 'location') return t('resources.locations') + ' ' + t('common.edit').toLowerCase();
    if (props.type === 'room') return t('resources.rooms') + ' ' + t('common.edit').toLowerCase();
    return t('resources.equipment') + ' ' + t('common.edit').toLowerCase();
  }
  if (props.type === 'location') return t('resources.newLocation');
  if (props.type === 'room') return t('resources.newRoom');
  return t('resources.newEquipment');
});

// Options
const countryOptions = [
  { value: 'CH', label: 'Schweiz' },
  { value: 'DE', label: 'Deutschland' },
  { value: 'AT', label: 'Oesterreich' },
  { value: 'FR', label: 'Frankreich' },
  { value: 'IT', label: 'Italien' },
  { value: 'LI', label: 'Liechtenstein' },
];

const locationStatusOptions = [
  { value: 'OPEN', label: t('resources.available') },
  { value: 'CLOSED', label: t('resources.closed') },
];

const roomStatusOptions = [
  { value: 'AVAILABLE', label: t('resources.available') },
  { value: 'IN_USE', label: t('resources.inUse') },
  { value: 'CLOSED', label: t('resources.closed') },
  { value: 'MAINTENANCE', label: t('resources.maintenance') },
];

const conditionOptions = [
  { value: 'GOOD', label: t('resources.condition.good') },
  { value: 'FAIR', label: t('resources.condition.fair') },
  { value: 'POOR', label: t('resources.condition.poor') },
];

const categoryOptions = EQUIPMENT_CATEGORIES.map(c => ({ value: c, label: c }));

// Reset form when modal opens
watch(() => [props.modelValue, props.item, props.type], () => {
  if (props.modelValue) {
    errors.value = {};
    newFeature.value = '';

    if (props.item && isEditing.value) {
      if (props.type === 'location') {
        const loc = props.item as unknown as Location;
        locationForm.value = {
          name: loc.name,
          address: loc.address,
          zip: loc.zip,
          city: loc.city,
          country: loc.country,
          phone: loc.phone || '',
          email: loc.email || '',
          status: loc.status,
        };
      } else if (props.type === 'room') {
        const room = props.item as unknown as Room;
        roomForm.value = {
          name: room.name,
          locationId: room.locationId,
          locationName: room.locationName,
          capacity: room.capacity,
          status: room.status,
          features: [...room.features],
        };
      } else {
        const eq = props.item as unknown as Equipment;
        equipmentForm.value = {
          name: eq.name,
          category: eq.category,
          total: eq.total,
          available: eq.available,
          condition: eq.condition,
          locationId: eq.locationId || '',
          locationName: eq.locationName || '',
        };
      }
    } else {
      locationForm.value = { name: '', address: '', zip: '', city: '', country: 'CH', phone: '', email: '', status: 'OPEN' };
      roomForm.value = { name: '', locationId: '', locationName: '', capacity: 4, status: 'AVAILABLE', features: [] };
      equipmentForm.value = { name: '', category: '', total: 1, available: 1, condition: 'GOOD', locationId: '', locationName: '' };
    }
  }
}, { immediate: true });

function addFeature() {
  const tag = newFeature.value.trim();
  if (tag && !roomForm.value.features.includes(tag)) {
    roomForm.value.features.push(tag);
  }
  newFeature.value = '';
}

function removeFeature(index: number) {
  roomForm.value.features.splice(index, 1);
}

function handleFeatureKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter') {
    e.preventDefault();
    addFeature();
  }
}

function onLocationSelect(locationId: string) {
  const loc = store.getLocationById(locationId);
  if (props.type === 'room') {
    roomForm.value.locationId = locationId;
    roomForm.value.locationName = loc?.name || '';
  } else {
    equipmentForm.value.locationId = locationId;
    equipmentForm.value.locationName = loc?.name || '';
  }
}

function validate(): boolean {
  const errs: Record<string, string> = {};

  if (props.type === 'location') {
    if (!locationForm.value.name.trim()) errs.name = t('common.required');
    if (!locationForm.value.address.trim()) errs.address = t('common.required');
    if (!locationForm.value.city.trim()) errs.city = t('common.required');
    if (!locationForm.value.zip.trim()) errs.zip = t('common.required');
  } else if (props.type === 'room') {
    if (!roomForm.value.name.trim()) errs.name = t('common.required');
    if (!roomForm.value.locationId) errs.locationId = t('common.required');
    if (roomForm.value.capacity < 1) errs.capacity = t('common.required');
  } else {
    if (!equipmentForm.value.name.trim()) errs.name = t('common.required');
    if (!equipmentForm.value.category) errs.category = t('common.required');
    if (equipmentForm.value.total < 1) errs.total = t('common.required');
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
}

function handleSave() {
  if (!validate()) return;

  let data: Record<string, unknown>;
  if (props.type === 'location') {
    data = { ...locationForm.value };
  } else if (props.type === 'room') {
    data = { ...roomForm.value };
  } else {
    data = { ...equipmentForm.value };
  }

  emit('save', data);
  toast.success(t('common.savedSuccessfully'));
}

function handleClose() {
  emit('update:modelValue', false);
}
</script>

<template>
  <BModal
    :model-value="modelValue"
    :title="modalTitle"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
    @close="handleClose"
  >
    <!-- Location Form -->
    <div v-if="type === 'location'" class="space-y-4">
      <BInput
        v-model="locationForm.name"
        :label="t('resources.locations') + ' Name'"
        :placeholder="t('resources.locations') + ' Name'"
        :error="errors.name"
        required
      />

      <BInput
        v-model="locationForm.address"
        :label="t('resources.address')"
        :placeholder="t('resources.address')"
        :error="errors.address"
        required
      />

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <BInput
          v-model="locationForm.zip"
          label="PLZ"
          placeholder="8001"
          :error="errors.zip"
          required
        />
        <div class="sm:col-span-2">
          <BInput
            v-model="locationForm.city"
            label="Stadt"
            placeholder="Zuerich"
            :error="errors.city"
            required
          />
        </div>
      </div>

      <BSelect
        v-model="locationForm.country"
        label="Land"
        :options="countryOptions"
      />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BInput
          v-model="locationForm.phone"
          type="tel"
          label="Telefon"
          placeholder="+41 44 123 45 67"
        />
        <BInput
          v-model="locationForm.email"
          type="email"
          label="E-Mail"
          placeholder="standort@bookando.ch"
        />
      </div>

      <BSelect
        v-model="locationForm.status"
        label="Status"
        :options="locationStatusOptions"
      />
    </div>

    <!-- Room Form -->
    <div v-else-if="type === 'room'" class="space-y-4">
      <BInput
        v-model="roomForm.name"
        label="Raumname"
        placeholder="z.B. Salon A"
        :error="errors.name"
        required
      />

      <BSelect
        v-model="roomForm.locationId"
        :label="t('resources.locations')"
        :options="store.locationOptions"
        :placeholder="t('common.select')"
        :error="errors.locationId"
        required
        @update:model-value="onLocationSelect"
      />

      <BInput
        v-model="roomForm.capacity"
        type="number"
        :label="t('resources.capacity')"
        placeholder="4"
        :error="errors.capacity"
        required
      />

      <BSelect
        v-model="roomForm.status"
        label="Status"
        :options="roomStatusOptions"
      />

      <!-- Features tag input -->
      <div>
        <label :class="LABEL_STYLES.base">{{ t('resources.features') }}</label>
        <div class="flex gap-2">
          <input
            v-model="newFeature"
            type="text"
            class="flex-1 px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200"
            placeholder="z.B. Klimaanlage"
            @keydown="handleFeatureKeydown"
          />
          <BButton variant="secondary" @click="addFeature">
            {{ t('common.add') }}
          </BButton>
        </div>
        <div v-if="roomForm.features.length > 0" class="flex flex-wrap gap-2 mt-3">
          <span
            v-for="(feature, idx) in roomForm.features"
            :key="idx"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700"
          >
            {{ feature }}
            <button
              type="button"
              class="ml-0.5 hover:text-cyan-900 transition-colors"
              @click="removeFeature(idx)"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- Equipment Form -->
    <div v-else class="space-y-4">
      <BInput
        v-model="equipmentForm.name"
        label="Name"
        placeholder="z.B. Haartrockner Dyson"
        :error="errors.name"
        required
      />

      <BSelect
        v-model="equipmentForm.category"
        label="Kategorie"
        :options="categoryOptions"
        :placeholder="t('common.select')"
        :error="errors.category"
        required
      />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BInput
          v-model="equipmentForm.total"
          type="number"
          label="Gesamtanzahl"
          placeholder="1"
          :error="errors.total"
          required
        />
        <BInput
          v-model="equipmentForm.available"
          type="number"
          :label="t('resources.available')"
          placeholder="1"
        />
      </div>

      <BSelect
        v-model="equipmentForm.condition"
        label="Zustand"
        :options="conditionOptions"
      />

      <BSelect
        v-model="equipmentForm.locationId"
        :label="t('resources.locations')"
        :options="[{ value: '', label: '-- ' + t('common.optional') + ' --' }, ...store.locationOptions]"
        @update:model-value="onLocationSelect"
      />
    </div>

    <!-- Footer -->
    <template #footer>
      <BButton variant="secondary" @click="handleClose">
        {{ t('common.cancel') }}
      </BButton>
      <BButton variant="primary" @click="handleSave">
        {{ isEditing ? t('common.save') : t('common.create') }}
      </BButton>
    </template>
  </BModal>
</template>
