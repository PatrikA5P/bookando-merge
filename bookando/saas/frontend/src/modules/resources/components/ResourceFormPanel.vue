<script setup lang="ts">
/**
 * ResourceFormPanel — Gold Standard SlideIn fuer Ressourcen
 *
 * Ersetzt ResourceModal.vue (BModal → BFormPanel).
 * Dynamisches Formular nach ResourceType:
 * - LOCATION: Name, Adresse, PLZ, Stadt, Land, Telefon, E-Mail
 * - ROOM: Name, Standort (parent), Kapazitaet, Ausstattung
 * - VEHICLE: Name, Kennzeichen, Marke, Modell, Kategorie, Farbe
 * - EQUIPMENT: Name, Kategorie, Gesamtanzahl, Zustand
 *
 * Gemeinsame Felder: Status, Sichtbarkeit, Beschreibung
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import {
  useResourcesStore,
  EQUIPMENT_CATEGORIES,
  VEHICLE_CATEGORIES,
  RESOURCE_STATUS_LABELS,
} from '@/stores/resources';
import type {
  Resource,
  ResourceType,
  ResourceStatus,
  ResourceVisibility,
  LocationProperties,
  RoomProperties,
  VehicleProperties,
  EquipmentProperties,
} from '@/stores/resources';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BButton from '@/components/ui/BButton.vue';
import BConfirmDialog from '@/components/ui/BConfirmDialog.vue';

const { t } = useI18n();
const toast = useToast();
const store = useResourcesStore();

const props = defineProps<{
  modelValue: boolean;
  resourceType: ResourceType;
  resource?: Resource | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved', resource: Resource): void;
  (e: 'deleted', id: string): void;
}>();

// ── Form State ───────────────────────────────────────────────────────────
const saving = ref(false);
const dirty = ref(false);
const showDeleteConfirm = ref(false);

const isEditing = computed(() => !!props.resource);
const mode = computed(() => isEditing.value ? 'edit' : 'create');

const panelTitle = computed(() => {
  const typeLabel = {
    LOCATION: 'Standort',
    ROOM: 'Raum',
    VEHICLE: 'Fahrzeug',
    EQUIPMENT: 'Equipment',
  }[props.resourceType];
  return isEditing.value
    ? `${typeLabel} bearbeiten`
    : `${typeLabel} erfassen`;
});

// ── Common Fields ────────────────────────────────────────────────────────
const name = ref('');
const description = ref('');
const status = ref<ResourceStatus>('ACTIVE');
const visibility = ref<ResourceVisibility>('ADMIN_ONLY');
const capacity = ref(1);
const parentId = ref('');
const errors = ref<Record<string, string>>({});

// ── Location-specific Fields ─────────────────────────────────────────────
const address = ref('');
const city = ref('');
const zip = ref('');
const country = ref('CH');
const phone = ref('');
const email = ref('');

// ── Room-specific Fields ─────────────────────────────────────────────────
const features = ref<string[]>([]);
const floor = ref('');
const newFeature = ref('');

// ── Vehicle-specific Fields ──────────────────────────────────────────────
const licensePlate = ref('');
const brand = ref('');
const vehicleModel = ref('');
const vehicleCategory = ref('');
const color = ref('');

// ── Equipment-specific Fields ────────────────────────────────────────────
const equipmentCategory = ref('');
const condition = ref<'GOOD' | 'FAIR' | 'POOR'>('GOOD');
const totalUnits = ref(1);
const availableUnits = ref(1);
const serialNumber = ref('');

// ── Options ──────────────────────────────────────────────────────────────
const statusOptions = [
  { value: 'ACTIVE', label: RESOURCE_STATUS_LABELS.ACTIVE },
  { value: 'MAINTENANCE', label: RESOURCE_STATUS_LABELS.MAINTENANCE },
  { value: 'RETIRED', label: RESOURCE_STATUS_LABELS.RETIRED },
];

const visibilityOptions = [
  { value: 'ADMIN_ONLY', label: 'Nur Admin' },
  { value: 'EMPLOYEE', label: 'Mitarbeiter sichtbar' },
  { value: 'CUSTOMER_VISIBLE', label: 'Kunden sichtbar' },
  { value: 'CUSTOMER_BOOKABLE', label: 'Kunden buchbar' },
];

const countryOptions = [
  { value: 'CH', label: 'Schweiz' },
  { value: 'DE', label: 'Deutschland' },
  { value: 'AT', label: 'Oesterreich' },
  { value: 'FR', label: 'Frankreich' },
  { value: 'IT', label: 'Italien' },
  { value: 'LI', label: 'Liechtenstein' },
];

const conditionOptions = [
  { value: 'GOOD', label: t('resources.condition.good') },
  { value: 'FAIR', label: t('resources.condition.fair') },
  { value: 'POOR', label: t('resources.condition.poor') },
];

const categoryOptions = EQUIPMENT_CATEGORIES.map(c => ({ value: c, label: c }));
const vehicleCategoryOptions = VEHICLE_CATEGORIES.map(c => ({ value: c, label: c }));

const locationSelectOptions = computed(() => [
  { value: '', label: '-- Standort waehlen --' },
  ...store.locationOptions,
]);

// ── Watch for dirty state ────────────────────────────────────────────────
watch([name, description, status, visibility, capacity, parentId, address, city, zip, country, phone, email, features, floor, licensePlate, brand, vehicleModel, vehicleCategory, color, equipmentCategory, condition, totalUnits, availableUnits, serialNumber], () => {
  dirty.value = true;
}, { deep: true });

// ── Reset form when panel opens or resource changes ──────────────────────
watch(() => [props.modelValue, props.resource, props.resourceType], () => {
  if (props.modelValue) {
    errors.value = {};
    dirty.value = false;

    if (props.resource && isEditing.value) {
      // Populate from existing resource
      name.value = props.resource.name;
      description.value = props.resource.description || '';
      status.value = props.resource.status;
      visibility.value = props.resource.visibility;
      capacity.value = props.resource.capacity;
      parentId.value = props.resource.parentId || '';

      const p = props.resource.properties;

      if (props.resourceType === 'LOCATION') {
        const lp = p as LocationProperties;
        address.value = lp.address || '';
        city.value = lp.city || '';
        zip.value = lp.zip || '';
        country.value = lp.country || 'CH';
        phone.value = lp.phone || '';
        email.value = lp.email || '';
      } else if (props.resourceType === 'ROOM') {
        const rp = p as RoomProperties;
        features.value = [...(rp.features || [])];
        floor.value = rp.floor || '';
      } else if (props.resourceType === 'VEHICLE') {
        const vp = p as VehicleProperties;
        licensePlate.value = vp.licensePlate || '';
        brand.value = vp.brand || '';
        vehicleModel.value = vp.model || '';
        vehicleCategory.value = vp.category || '';
        color.value = vp.color || '';
      } else if (props.resourceType === 'EQUIPMENT') {
        const ep = p as EquipmentProperties;
        equipmentCategory.value = ep.category || '';
        condition.value = ep.condition || 'GOOD';
        totalUnits.value = ep.totalUnits || 1;
        availableUnits.value = ep.availableUnits || 1;
        serialNumber.value = ep.serialNumber || '';
      }
    } else {
      // Reset to defaults
      name.value = '';
      description.value = '';
      status.value = 'ACTIVE';
      visibility.value = 'ADMIN_ONLY';
      capacity.value = props.resourceType === 'ROOM' ? 4 : 1;
      parentId.value = '';

      address.value = '';
      city.value = '';
      zip.value = '';
      country.value = 'CH';
      phone.value = '';
      email.value = '';

      features.value = [];
      floor.value = '';
      newFeature.value = '';

      licensePlate.value = '';
      brand.value = '';
      vehicleModel.value = '';
      vehicleCategory.value = '';
      color.value = '';

      equipmentCategory.value = '';
      condition.value = 'GOOD';
      totalUnits.value = 1;
      availableUnits.value = 1;
      serialNumber.value = '';
    }

    // Reset dirty after population
    setTimeout(() => { dirty.value = false; }, 0);
  }
}, { immediate: true });

// ── Feature Tag Input ────────────────────────────────────────────────────
function addFeature() {
  const tag = newFeature.value.trim();
  if (tag && !features.value.includes(tag)) {
    features.value.push(tag);
  }
  newFeature.value = '';
}

function removeFeature(index: number) {
  features.value.splice(index, 1);
}

function handleFeatureKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter') {
    e.preventDefault();
    addFeature();
  }
}

// ── Validation ───────────────────────────────────────────────────────────
function validate(): boolean {
  const errs: Record<string, string> = {};

  if (!name.value.trim()) errs.name = t('common.required');

  if (props.resourceType === 'LOCATION') {
    if (!address.value.trim()) errs.address = t('common.required');
    if (!city.value.trim()) errs.city = t('common.required');
    if (!zip.value.trim()) errs.zip = t('common.required');
  } else if (props.resourceType === 'ROOM') {
    if (!parentId.value) errs.parentId = t('common.required');
    if (capacity.value < 1) errs.capacity = t('common.required');
  } else if (props.resourceType === 'EQUIPMENT') {
    if (!equipmentCategory.value) errs.equipmentCategory = t('common.required');
    if (totalUnits.value < 1) errs.totalUnits = t('common.required');
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
}

// ── Build Properties ─────────────────────────────────────────────────────
function buildProperties(): Record<string, unknown> {
  switch (props.resourceType) {
    case 'LOCATION':
      return {
        address: address.value,
        city: city.value,
        zip: zip.value,
        country: country.value,
        phone: phone.value || undefined,
        email: email.value || undefined,
      } satisfies LocationProperties;
    case 'ROOM':
      return {
        features: features.value,
        floor: floor.value || undefined,
      } satisfies RoomProperties;
    case 'VEHICLE':
      return {
        licensePlate: licensePlate.value || undefined,
        brand: brand.value || undefined,
        model: vehicleModel.value || undefined,
        category: vehicleCategory.value || undefined,
        color: color.value || undefined,
      } satisfies VehicleProperties;
    case 'EQUIPMENT':
      return {
        category: equipmentCategory.value,
        condition: condition.value,
        totalUnits: totalUnits.value,
        availableUnits: availableUnits.value,
        serialNumber: serialNumber.value || undefined,
      } satisfies EquipmentProperties;
    default:
      return {};
  }
}

// ── Save ─────────────────────────────────────────────────────────────────
async function handleSave() {
  if (!validate()) return;

  saving.value = true;
  try {
    const parentLoc = parentId.value ? store.getLocationById(parentId.value) : undefined;

    const payload = {
      resourceType: props.resourceType,
      name: name.value.trim(),
      description: description.value.trim() || undefined,
      capacity: capacity.value,
      parentId: parentId.value || undefined,
      properties: buildProperties(),
      visibility: visibility.value,
      status: status.value,
    };

    let saved: Resource;
    if (isEditing.value && props.resource) {
      saved = await store.updateResource(props.resource.id, payload);
    } else {
      saved = await store.createResource(payload as any);
    }

    toast.success(t('common.savedSuccessfully'));
    dirty.value = false;
    emit('saved', saved);
    emit('update:modelValue', false);
  } catch {
    toast.error(t('common.errorOccurred'));
  } finally {
    saving.value = false;
  }
}

// ── Delete ───────────────────────────────────────────────────────────────
async function handleDelete() {
  if (!props.resource) return;
  try {
    await store.deleteResource(props.resource.id);
    toast.success(t('common.deletedSuccessfully'));
    emit('deleted', props.resource.id);
    emit('update:modelValue', false);
  } catch {
    toast.error(t('common.errorOccurred'));
  }
  showDeleteConfirm.value = false;
}

function handleCancel() {
  emit('update:modelValue', false);
}
</script>

<template>
  <BFormPanel
    :model-value="modelValue"
    :title="panelTitle"
    :mode="mode"
    size="lg"
    :saving="saving"
    :dirty="dirty"
    @update:model-value="$emit('update:modelValue', $event)"
    @save="handleSave"
    @cancel="handleCancel"
  >
    <!-- ────────────── LOCATION FORM ────────────── -->
    <template v-if="resourceType === 'LOCATION'">
      <BFormSection title="Standort-Details" :columns="1">
        <BInput
          v-model="name"
          label="Name"
          placeholder="z.B. Hauptstandort Zuerich"
          :error="errors.name"
          required
        />
        <BInput
          v-model="address"
          label="Adresse"
          placeholder="Bahnhofstrasse 1"
          :error="errors.address"
          required
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BInput
          v-model="zip"
          label="PLZ"
          placeholder="8001"
          :error="errors.zip"
          required
        />
        <BInput
          v-model="city"
          label="Stadt"
          placeholder="Zuerich"
          :error="errors.city"
          required
        />
      </BFormSection>

      <BFormSection :columns="1">
        <BSelect
          v-model="country"
          label="Land"
          :options="countryOptions"
        />
      </BFormSection>

      <BFormSection title="Kontakt" :columns="2">
        <BInput
          v-model="phone"
          type="tel"
          label="Telefon"
          placeholder="+41 44 123 45 67"
        />
        <BInput
          v-model="email"
          type="email"
          label="E-Mail"
          placeholder="standort@bookando.ch"
        />
      </BFormSection>
    </template>

    <!-- ────────────── ROOM FORM ────────────── -->
    <template v-else-if="resourceType === 'ROOM'">
      <BFormSection title="Raum-Details" :columns="1">
        <BInput
          v-model="name"
          label="Raumname"
          placeholder="z.B. Salon A"
          :error="errors.name"
          required
        />
        <BSelect
          v-model="parentId"
          label="Standort"
          :options="locationSelectOptions"
          :error="errors.parentId"
          required
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BInput
          v-model.number="capacity"
          type="number"
          label="Kapazitaet (Personen)"
          placeholder="4"
          :error="errors.capacity"
          required
        />
        <BInput
          v-model="floor"
          label="Stockwerk"
          placeholder="z.B. EG, 1. OG"
        />
      </BFormSection>

      <BFormSection title="Ausstattung" :columns="1">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Merkmale</label>
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
          <div v-if="features.length > 0" class="flex flex-wrap gap-2 mt-3">
            <span
              v-for="(feature, idx) in features"
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
      </BFormSection>
    </template>

    <!-- ────────────── VEHICLE FORM ────────────── -->
    <template v-else-if="resourceType === 'VEHICLE'">
      <BFormSection title="Fahrzeug-Details" :columns="1">
        <BInput
          v-model="name"
          label="Bezeichnung"
          placeholder="z.B. Fahrschulauto VW Golf"
          :error="errors.name"
          required
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BInput
          v-model="licensePlate"
          label="Kennzeichen"
          placeholder="ZH 123 456"
        />
        <BSelect
          v-model="vehicleCategory"
          label="Kategorie"
          :options="[{ value: '', label: '-- Waehlen --' }, ...vehicleCategoryOptions]"
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BInput
          v-model="brand"
          label="Marke"
          placeholder="z.B. VW"
        />
        <BInput
          v-model="vehicleModel"
          label="Modell"
          placeholder="z.B. Golf 8"
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BInput
          v-model="color"
          label="Farbe"
          placeholder="z.B. Silber"
        />
        <BSelect
          v-model="parentId"
          label="Standort (optional)"
          :options="locationSelectOptions"
        />
      </BFormSection>
    </template>

    <!-- ────────────── EQUIPMENT FORM ────────────── -->
    <template v-else-if="resourceType === 'EQUIPMENT'">
      <BFormSection title="Equipment-Details" :columns="1">
        <BInput
          v-model="name"
          label="Name"
          placeholder="z.B. Haartrockner Dyson"
          :error="errors.name"
          required
        />
        <BSelect
          v-model="equipmentCategory"
          label="Kategorie"
          :options="[{ value: '', label: '-- Waehlen --' }, ...categoryOptions]"
          :error="errors.equipmentCategory"
          required
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BInput
          v-model.number="totalUnits"
          type="number"
          label="Gesamtanzahl"
          placeholder="1"
          :error="errors.totalUnits"
          required
        />
        <BInput
          v-model.number="availableUnits"
          type="number"
          label="Verfuegbar"
          placeholder="1"
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BSelect
          v-model="condition"
          label="Zustand"
          :options="conditionOptions"
        />
        <BInput
          v-model="serialNumber"
          label="Seriennummer"
          placeholder="Optional"
        />
      </BFormSection>

      <BFormSection :columns="1">
        <BSelect
          v-model="parentId"
          label="Standort (optional)"
          :options="locationSelectOptions"
        />
      </BFormSection>
    </template>

    <!-- ────────────── GEMEINSAME FELDER ────────────── -->
    <BFormSection title="Allgemein" :columns="2" divided>
      <BSelect
        v-model="status"
        label="Status"
        :options="statusOptions"
      />
      <BSelect
        v-model="visibility"
        label="Sichtbarkeit"
        :options="visibilityOptions"
      />
    </BFormSection>

    <BFormSection :columns="1">
      <BTextarea
        v-model="description"
        label="Beschreibung"
        placeholder="Optionale Beschreibung..."
        :rows="3"
      />
    </BFormSection>

    <!-- ────────────── FOOTER: DELETE BUTTON ────────────── -->
    <template v-if="isEditing" #footer-left>
      <BButton
        variant="ghost"
        class="text-red-600 hover:text-red-700 hover:bg-red-50"
        @click="showDeleteConfirm = true"
      >
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        {{ t('common.delete') }}
      </BButton>
    </template>
  </BFormPanel>

  <!-- Delete Confirmation -->
  <BConfirmDialog
    v-model="showDeleteConfirm"
    :title="t('common.confirmDelete')"
    :message="`${name} wirklich loeschen? Diese Aktion kann nicht rueckgaengig gemacht werden.`"
    confirm-variant="danger"
    :confirm-label="t('common.delete')"
    @confirm="handleDelete"
  />
</template>
