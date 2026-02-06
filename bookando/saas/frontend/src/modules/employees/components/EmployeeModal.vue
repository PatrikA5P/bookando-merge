<script setup lang="ts">
/**
 * EmployeeModal — Mitarbeiter erstellen / bearbeiten
 *
 * 4-Tab-Formular:
 *   1. Profil (Name, E-Mail, Telefon, Position, Abteilung, Bio)
 *   2. Adresse (Strasse, PLZ, Ort, Land)
 *   3. HR-Daten (Eintrittsdatum, Austrittsdatum, Rolle, Gehalt, Ferien)
 *   4. Services (Checkbox-Liste zuweisbarer Dienstleistungen)
 *
 * Verwendet das Design-System konsequent via @/design Imports
 * und die UI-Basiskomponenten BInput, BSelect, BTextarea, BModal, BButton.
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import BModal from '@/components/ui/BModal.vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import { TAB_STYLES, INPUT_STYLES, MODULE_DESIGNS } from '@/design';
import {
  type Employee,
  type EmployeeFormData,
  type EmployeeStatus,
  type EmployeeRole,
  AVAILABLE_SERVICES,
  DEPARTMENTS,
} from '@/stores/employees';

const { t } = useI18n();

const props = defineProps<{
  modelValue: boolean;
  employee?: Employee | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'save', data: EmployeeFormData): void;
  (e: 'close'): void;
}>();

// Tab state
type TabId = 'profile' | 'address' | 'hr' | 'services';
const activeTab = ref<TabId>('profile');

const tabs: { id: TabId; labelKey: string }[] = [
  { id: 'profile', labelKey: 'employees.modal.tabProfile' },
  { id: 'address', labelKey: 'employees.modal.tabAddress' },
  { id: 'hr', labelKey: 'employees.modal.tabHR' },
  { id: 'services', labelKey: 'employees.modal.tabServices' },
];

const employeesDesign = MODULE_DESIGNS.employees;

// Form data
const form = ref<EmployeeFormData>(getEmptyForm());
const errors = ref<Partial<Record<keyof EmployeeFormData, string>>>({});

function getEmptyForm(): EmployeeFormData {
  return {
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    position: '',
    department: '',
    status: 'ACTIVE',
    role: 'EMPLOYEE',
    hireDate: new Date().toISOString().split('T')[0],
    exitDate: undefined,
    avatar: '',
    bio: '',
    street: '',
    zip: '',
    city: '',
    country: 'CH',
    salaryMinor: 0,
    vacationDaysTotal: 25,
    vacationDaysUsed: 0,
    employmentPercent: 100,
    socialSecurityNumber: '',
    assignedServiceIds: [],
  };
}

// Reset form when modal opens or employee changes
watch(() => [props.modelValue, props.employee], () => {
  if (props.modelValue) {
    activeTab.value = 'profile';
    errors.value = {};
    if (props.employee) {
      form.value = {
        firstName: props.employee.firstName,
        lastName: props.employee.lastName,
        email: props.employee.email,
        phone: props.employee.phone,
        position: props.employee.position,
        department: props.employee.department,
        status: props.employee.status,
        role: props.employee.role,
        hireDate: props.employee.hireDate,
        exitDate: props.employee.exitDate,
        avatar: props.employee.avatar || '',
        bio: props.employee.bio || '',
        street: props.employee.street,
        zip: props.employee.zip,
        city: props.employee.city,
        country: props.employee.country,
        salaryMinor: props.employee.salaryMinor,
        vacationDaysTotal: props.employee.vacationDaysTotal,
        vacationDaysUsed: props.employee.vacationDaysUsed,
        employmentPercent: props.employee.employmentPercent,
        socialSecurityNumber: props.employee.socialSecurityNumber || '',
        assignedServiceIds: [...props.employee.assignedServiceIds],
      };
    } else {
      form.value = getEmptyForm();
    }
  }
}, { immediate: true });

// Computed for display
const isEditing = computed(() => !!props.employee);
const modalTitle = computed(() =>
  isEditing.value
    ? t('employees.modal.titleEdit')
    : t('employees.modal.titleCreate')
);

// Salary displayed in CHF (convert from minor)
const salaryDisplay = computed({
  get: () => form.value.salaryMinor / 100,
  set: (val: number) => { form.value.salaryMinor = Math.round(val * 100); },
});

// Options
const statusOptions = [
  { value: 'ACTIVE', label: t('employees.status.active') },
  { value: 'VACATION', label: t('employees.status.vacation') },
  { value: 'SICK_LEAVE', label: t('employees.status.sickLeave') },
  { value: 'PAUSE', label: t('employees.status.pause') },
  { value: 'TERMINATED', label: t('employees.status.terminated') },
];

const roleOptions = [
  { value: 'ADMIN', label: t('employees.role.admin') },
  { value: 'MANAGER', label: t('employees.role.manager') },
  { value: 'EMPLOYEE', label: t('employees.role.employee') },
  { value: 'TRAINEE', label: t('employees.role.trainee') },
];

const departmentOptions = DEPARTMENTS.map(d => ({ value: d, label: d }));

const countryOptions = [
  { value: 'CH', label: t('employees.country.ch') },
  { value: 'DE', label: t('employees.country.de') },
  { value: 'AT', label: t('employees.country.at') },
  { value: 'FR', label: t('employees.country.fr') },
  { value: 'IT', label: t('employees.country.it') },
  { value: 'LI', label: t('employees.country.li') },
];

// Services grouped by category
const servicesByCategory = computed(() => {
  const grouped: Record<string, typeof AVAILABLE_SERVICES> = {};
  for (const svc of AVAILABLE_SERVICES) {
    if (!grouped[svc.category]) grouped[svc.category] = [];
    grouped[svc.category].push(svc);
  }
  return grouped;
});

function isServiceAssigned(serviceId: string): boolean {
  return form.value.assignedServiceIds.includes(serviceId);
}

function toggleService(serviceId: string) {
  const idx = form.value.assignedServiceIds.indexOf(serviceId);
  if (idx >= 0) {
    form.value.assignedServiceIds.splice(idx, 1);
  } else {
    form.value.assignedServiceIds.push(serviceId);
  }
}

// Validation
function validate(): boolean {
  const errs: Partial<Record<keyof EmployeeFormData, string>> = {};

  if (!form.value.firstName.trim()) {
    errs.firstName = t('employees.validation.firstNameRequired');
  }
  if (!form.value.lastName.trim()) {
    errs.lastName = t('employees.validation.lastNameRequired');
  }
  if (!form.value.email.trim()) {
    errs.email = t('employees.validation.emailRequired');
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errs.email = t('employees.validation.emailInvalid');
  }
  if (!form.value.position.trim()) {
    errs.position = t('employees.validation.positionRequired');
  }
  if (!form.value.department) {
    errs.department = t('employees.validation.departmentRequired');
  }
  if (!form.value.hireDate) {
    errs.hireDate = t('employees.validation.hireDateRequired');
  }

  errors.value = errs;

  // Navigate to first tab with errors
  if (errs.firstName || errs.lastName || errs.email || errs.position || errs.department) {
    activeTab.value = 'profile';
  } else if (errs.hireDate) {
    activeTab.value = 'hr';
  }

  return Object.keys(errs).length === 0;
}

function handleSave() {
  if (!validate()) return;
  emit('save', { ...form.value });
}

function handleClose() {
  emit('update:modelValue', false);
  emit('close');
}
</script>

<template>
  <BModal
    :model-value="modelValue"
    :title="modalTitle"
    size="xl"
    @update:model-value="$emit('update:modelValue', $event)"
    @close="handleClose"
  >
    <!-- Tab Navigation -->
    <div class="mb-6 border-b border-slate-200 -mx-6 -mt-2 px-6">
      <nav :class="TAB_STYLES.container" role="tablist">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          role="tab"
          :aria-selected="activeTab === tab.id"
          :class="[
            activeTab === tab.id
              ? `${TAB_STYLES.tabActive} ${employeesDesign.activeText} border-b-2 border-current`
              : TAB_STYLES.tab,
          ]"
          @click="activeTab = tab.id"
        >
          {{ t(tab.labelKey) }}
        </button>
      </nav>
    </div>

    <!-- Tab 1: Profile -->
    <div v-show="activeTab === 'profile'" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BInput
          v-model="form.firstName"
          :label="t('employees.field.firstName')"
          :placeholder="t('employees.placeholder.firstName')"
          :error="errors.firstName"
          required
        />
        <BInput
          v-model="form.lastName"
          :label="t('employees.field.lastName')"
          :placeholder="t('employees.placeholder.lastName')"
          :error="errors.lastName"
          required
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BInput
          v-model="form.email"
          type="email"
          :label="t('employees.field.email')"
          :placeholder="t('employees.placeholder.email')"
          :error="errors.email"
          required
        />
        <BInput
          v-model="form.phone"
          type="tel"
          :label="t('employees.field.phone')"
          :placeholder="t('employees.placeholder.phone')"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BInput
          v-model="form.position"
          :label="t('employees.field.position')"
          :placeholder="t('employees.placeholder.position')"
          :error="errors.position"
          required
        />
        <BSelect
          v-model="form.department"
          :label="t('employees.field.department')"
          :options="departmentOptions"
          :placeholder="t('employees.placeholder.department')"
          :error="errors.department"
          required
        />
      </div>

      <BTextarea
        v-model="form.bio"
        :label="t('employees.field.bio')"
        :placeholder="t('employees.placeholder.bio')"
        :rows="3"
      />
    </div>

    <!-- Tab 2: Address -->
    <div v-show="activeTab === 'address'" class="space-y-4">
      <BInput
        v-model="form.street"
        :label="t('employees.field.street')"
        :placeholder="t('employees.placeholder.street')"
      />

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <BInput
          v-model="form.zip"
          :label="t('employees.field.zip')"
          :placeholder="t('employees.placeholder.zip')"
        />
        <div class="sm:col-span-2">
          <BInput
            v-model="form.city"
            :label="t('employees.field.city')"
            :placeholder="t('employees.placeholder.city')"
          />
        </div>
      </div>

      <BSelect
        v-model="form.country"
        :label="t('employees.field.country')"
        :options="countryOptions"
      />
    </div>

    <!-- Tab 3: HR Data -->
    <div v-show="activeTab === 'hr'" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BInput
          v-model="form.hireDate"
          type="date"
          :label="t('employees.field.hireDate')"
          :error="errors.hireDate"
          required
        />
        <BInput
          v-model="form.exitDate"
          type="date"
          :label="t('employees.field.exitDate')"
          :hint="t('employees.hint.exitDate')"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BSelect
          v-model="form.role"
          :label="t('employees.field.role')"
          :options="roleOptions"
        />
        <BSelect
          v-model="(form.status as string)"
          :label="t('employees.field.status')"
          :options="statusOptions"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BInput
          v-model="salaryDisplay"
          type="number"
          :label="t('employees.field.salary')"
          :placeholder="t('employees.placeholder.salary')"
          :hint="t('employees.hint.salary')"
        />
        <BInput
          v-model="form.employmentPercent"
          type="number"
          :label="t('employees.field.employmentPercent')"
          :hint="t('employees.hint.employmentPercent')"
        />
        <BInput
          v-model="form.vacationDaysTotal"
          type="number"
          :label="t('employees.field.vacationDays')"
          :hint="t('employees.hint.vacationDays')"
        />
      </div>

      <BInput
        v-model="form.socialSecurityNumber"
        :label="t('employees.field.socialSecurityNumber')"
        :placeholder="t('employees.placeholder.socialSecurityNumber')"
        :hint="t('employees.hint.socialSecurityNumber')"
      />
    </div>

    <!-- Tab 4: Services -->
    <div v-show="activeTab === 'services'" class="space-y-6">
      <p class="text-sm text-slate-500">
        {{ t('employees.modal.servicesDescription') }}
      </p>

      <div
        v-for="(services, category) in servicesByCategory"
        :key="category"
        class="space-y-2"
      >
        <h4 class="text-sm font-semibold text-slate-700">{{ category }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <label
            v-for="svc in services"
            :key="svc.id"
            :class="[
              'flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200',
              isServiceAssigned(svc.id)
                ? 'bg-slate-100 border-slate-300'
                : 'bg-white border-slate-200 hover:border-slate-300',
            ]"
          >
            <input
              type="checkbox"
              :checked="isServiceAssigned(svc.id)"
              :class="INPUT_STYLES.checkbox"
              @change="toggleService(svc.id)"
            />
            <span class="text-sm text-slate-700">{{ svc.name }}</span>
          </label>
        </div>
      </div>

      <div v-if="form.assignedServiceIds.length > 0" class="pt-4 border-t border-slate-200">
        <p class="text-xs text-slate-500">
          {{ t('employees.modal.servicesAssigned', { count: form.assignedServiceIds.length }) }}
        </p>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <BButton variant="secondary" @click="handleClose">
        {{ t('common.cancel') }}
      </BButton>
      <BButton variant="primary" @click="handleSave">
        {{ isEditing ? t('common.save') : t('employees.action.create') }}
      </BButton>
    </template>
  </BModal>
</template>
