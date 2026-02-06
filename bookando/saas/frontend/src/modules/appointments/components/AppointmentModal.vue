<script setup lang="ts">
/**
 * AppointmentModal — Multi-Step Buchungs-Assistent
 *
 * 5-Schritte-Wizard:
 * 1. Dienstleistung waehlen
 * 2. Mitarbeiter waehlen
 * 3. Datum & Zeit waehlen
 * 4. Kunde waehlen (oder schnell erstellen)
 * 5. Zusammenfassung & Bestaetigung
 */
import { ref, computed, watch } from 'vue';
import {
  BUTTON_STYLES,
  CARD_STYLES,
  INPUT_STYLES,
  GRID_STYLES,
  MODAL_STYLES,
  BADGE_STYLES,
  AVATAR_STYLES,
  LABEL_STYLES,
} from '@/design';
import BModal from '@/components/ui/BModal.vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useAppStore } from '@/stores/app';
import { useAppointmentsStore } from '@/stores/appointments';
import type { Service, Employee, Customer } from '@/stores/appointments';

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'created'): void;
}>();

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const appStore = useAppStore();
const store = useAppointmentsStore();

// Wizard State
const currentStep = ref(1);
const totalSteps = 5;

// Form State
const selectedServiceId = ref('');
const selectedEmployeeId = ref('');
const selectedDate = ref('');
const selectedTime = ref('');
const selectedCustomerId = ref('');
const notes = ref('');
const customerSearch = ref('');
const isQuickCreate = ref(false);
const quickCustomerName = ref('');
const quickCustomerEmail = ref('');
const quickCustomerPhone = ref('');

// Step Labels
const stepLabels = computed(() => [
  t('appointments.selectService'),
  t('appointments.selectEmployee'),
  t('appointments.selectDate'),
  t('appointments.selectCustomer'),
  t('common.confirm'),
]);

// Selected Items
const selectedService = computed(() =>
  store.services.find(s => s.id === selectedServiceId.value)
);

const selectedEmployee = computed(() =>
  store.employees.find(e => e.id === selectedEmployeeId.value)
);

const selectedCustomer = computed(() =>
  store.customers.find(c => c.id === selectedCustomerId.value)
);

// Available Employees (filtered by service)
const availableEmployees = computed(() => {
  if (!selectedServiceId.value) return store.employees;
  return store.getEmployeesForService(selectedServiceId.value);
});

// Available Time Slots
const availableSlots = computed(() => {
  if (!selectedDate.value || !selectedEmployeeId.value || !selectedService.value) return [];
  return store.getAvailableTimeSlots(
    selectedDate.value,
    selectedEmployeeId.value,
    selectedService.value.duration,
  );
});

// Filtered Customers
const filteredCustomers = computed(() => {
  if (!customerSearch.value) return store.customers;
  const q = customerSearch.value.toLowerCase();
  return store.customers.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.email.toLowerCase().includes(q) ||
    c.phone.includes(q)
  );
});

// Computed End Time
const computedEndTime = computed(() => {
  if (!selectedTime.value || !selectedService.value) return '';
  const [hours, mins] = selectedTime.value.split(':').map(Number);
  const totalMinutes = hours * 60 + mins + selectedService.value.duration;
  return `${String(Math.floor(totalMinutes / 60)).padStart(2, '0')}:${String(totalMinutes % 60).padStart(2, '0')}`;
});

// Step Validation
const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1: return !!selectedServiceId.value;
    case 2: return !!selectedEmployeeId.value;
    case 3: return !!selectedDate.value && !!selectedTime.value;
    case 4: return !!selectedCustomerId.value || (isQuickCreate.value && !!quickCustomerName.value);
    case 5: return true;
    default: return false;
  }
});

// Today's date string for min-date
const todayString = computed(() => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
});

// Service Categories grouped
const servicesByCategory = computed(() => {
  const groups: Record<string, Service[]> = {};
  for (const svc of store.services) {
    if (!groups[svc.category]) groups[svc.category] = [];
    groups[svc.category].push(svc);
  }
  return groups;
});

// Navigation
function nextStep() {
  if (canProceed.value && currentStep.value < totalSteps) {
    currentStep.value++;
  }
}

function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
}

function goToStep(step: number) {
  if (step <= currentStep.value) {
    currentStep.value = step;
  }
}

// Quick-create customer
function quickCreateCustomer() {
  if (!quickCustomerName.value) return;
  const newCustomer: Customer = {
    id: `cust-${Date.now()}`,
    name: quickCustomerName.value,
    email: quickCustomerEmail.value,
    phone: quickCustomerPhone.value,
  };
  store.customers.push(newCustomer);
  selectedCustomerId.value = newCustomer.id;
  isQuickCreate.value = false;
}

// Confirm & Create
function confirmBooking() {
  const service = selectedService.value;
  const employee = selectedEmployee.value;
  const customer = selectedCustomer.value;

  if (!service || !employee || !customer || !selectedDate.value || !selectedTime.value) return;

  store.createAppointment({
    customerId: customer.id,
    customerName: customer.name,
    employeeId: employee.id,
    employeeName: employee.name,
    serviceId: service.id,
    serviceName: service.name,
    date: selectedDate.value,
    startTime: selectedTime.value,
    endTime: computedEndTime.value,
    duration: service.duration,
    status: 'PENDING',
    priceMinor: service.priceMinor,
    currency: 'CHF',
    notes: notes.value,
    locationId: 'loc-1',
    roomId: 'room-1',
  });

  emit('created');
  close();
}

function close() {
  emit('update:modelValue', false);
  resetForm();
}

function resetForm() {
  currentStep.value = 1;
  selectedServiceId.value = '';
  selectedEmployeeId.value = '';
  selectedDate.value = '';
  selectedTime.value = '';
  selectedCustomerId.value = '';
  notes.value = '';
  customerSearch.value = '';
  isQuickCreate.value = false;
  quickCustomerName.value = '';
  quickCustomerEmail.value = '';
  quickCustomerPhone.value = '';
}

// Reset when modal opens
watch(() => props.modelValue, (open) => {
  if (open) resetForm();
});

function getEmployeeInitials(name: string): string {
  const parts = name.split(' ');
  if (parts.length >= 2) return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
  return parts[0]?.[0]?.toUpperCase() || '?';
}

function formatDateDisplay(dateStr: string): string {
  if (!dateStr) return '';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('de-CH', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}
</script>

<template>
  <BModal
    :model-value="modelValue"
    :title="t('appointments.newAppointment')"
    size="xl"
    :close-on-overlay="false"
    @update:model-value="$emit('update:modelValue', $event)"
    @close="close"
  >
    <div class="min-h-[400px] flex flex-col">
      <!-- Step Indicator -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
          <template v-for="(label, index) in stepLabels" :key="index">
            <button
              :class="[
                'flex items-center gap-1.5 text-xs font-medium transition-colors',
                (index + 1) <= currentStep ? 'text-brand-600' : 'text-slate-400',
                (index + 1) < currentStep ? 'cursor-pointer' : 'cursor-default',
              ]"
              @click="goToStep(index + 1)"
            >
              <span
                :class="[
                  'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                  (index + 1) === currentStep
                    ? 'bg-brand-600 text-white'
                    : (index + 1) < currentStep
                      ? 'bg-brand-100 text-brand-700'
                      : 'bg-slate-100 text-slate-400',
                ]"
              >
                <svg
                  v-if="(index + 1) < currentStep"
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                <span v-else>{{ index + 1 }}</span>
              </span>
              <span class="hidden sm:inline">{{ label }}</span>
            </button>
            <div
              v-if="index < stepLabels.length - 1"
              :class="[
                'flex-1 h-0.5 mx-2 rounded transition-colors',
                (index + 1) < currentStep ? 'bg-brand-300' : 'bg-slate-200',
              ]"
            />
          </template>
        </div>
      </div>

      <!-- Step Content -->
      <div class="flex-1">
        <!-- Step 1: Select Service -->
        <div v-if="currentStep === 1">
          <h3 class="text-base font-semibold text-slate-900 mb-4">{{ t('appointments.selectService') }}</h3>
          <template v-for="(services, category) in servicesByCategory" :key="category">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 mt-4 first:mt-0">
              {{ category }}
            </h4>
            <div :class="isMobile ? GRID_STYLES.cols1 : GRID_STYLES.cols2Dense">
              <button
                v-for="service in services"
                :key="service.id"
                :class="[
                  'text-left p-4 rounded-xl border-2 transition-all duration-200',
                  selectedServiceId === service.id
                    ? 'border-brand-500 bg-brand-50 shadow-sm'
                    : 'border-slate-200 hover:border-brand-200 hover:bg-slate-50',
                ]"
                @click="selectedServiceId = service.id"
              >
                <div class="flex items-start justify-between">
                  <div>
                    <h5 class="text-sm font-semibold text-slate-900">{{ service.name }}</h5>
                    <p class="text-xs text-slate-500 mt-0.5">{{ service.description }}</p>
                  </div>
                  <div
                    v-if="selectedServiceId === service.id"
                    class="w-5 h-5 rounded-full bg-brand-600 flex items-center justify-center flex-shrink-0 ml-2"
                  >
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                  <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ service.duration }} min
                  </span>
                  <span class="font-medium text-slate-700">{{ appStore.formatPrice(service.priceMinor) }}</span>
                </div>
              </button>
            </div>
          </template>
        </div>

        <!-- Step 2: Select Employee -->
        <div v-if="currentStep === 2">
          <h3 class="text-base font-semibold text-slate-900 mb-4">{{ t('appointments.selectEmployee') }}</h3>
          <div :class="isMobile ? GRID_STYLES.cols1 : GRID_STYLES.cols2Dense">
            <button
              v-for="employee in availableEmployees"
              :key="employee.id"
              :class="[
                'text-left p-4 rounded-xl border-2 transition-all duration-200 flex items-center gap-4',
                selectedEmployeeId === employee.id
                  ? 'border-brand-500 bg-brand-50 shadow-sm'
                  : 'border-slate-200 hover:border-brand-200 hover:bg-slate-50',
              ]"
              @click="selectedEmployeeId = employee.id"
            >
              <div
                :class="[
                  AVATAR_STYLES.initials.lg,
                  selectedEmployeeId === employee.id
                    ? 'bg-brand-200 text-brand-800'
                    : 'bg-slate-200 text-slate-600',
                ]"
              >
                {{ getEmployeeInitials(employee.name) }}
              </div>
              <div class="flex-1 min-w-0">
                <h5 class="text-sm font-semibold text-slate-900">{{ employee.name }}</h5>
                <p class="text-xs text-slate-500">{{ employee.position }}</p>
              </div>
              <div
                v-if="selectedEmployeeId === employee.id"
                class="w-5 h-5 rounded-full bg-brand-600 flex items-center justify-center flex-shrink-0"
              >
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </button>
          </div>
        </div>

        <!-- Step 3: Select Date & Time -->
        <div v-if="currentStep === 3">
          <h3 class="text-base font-semibold text-slate-900 mb-4">{{ t('appointments.selectDate') }}</h3>
          <div :class="isMobile ? 'space-y-4' : 'grid grid-cols-2 gap-6'">
            <!-- Date Picker -->
            <div>
              <label :class="LABEL_STYLES.required">{{ t('appointments.selectDate') }}</label>
              <input
                v-model="selectedDate"
                type="date"
                :min="todayString"
                :class="INPUT_STYLES.base"
              />
            </div>

            <!-- Time Slots -->
            <div>
              <label :class="LABEL_STYLES.required">{{ t('appointments.selectTime') }}</label>
              <div v-if="!selectedDate" class="text-sm text-slate-400 py-3">
                {{ t('appointments.selectDate') }}...
              </div>
              <div v-else-if="availableSlots.length === 0" class="text-sm text-slate-400 py-3">
                {{ t('common.noResults') }}
              </div>
              <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-2 max-h-[240px] overflow-y-auto mt-1">
                <button
                  v-for="slot in availableSlots"
                  :key="slot"
                  :class="[
                    'px-3 py-2 text-sm font-medium rounded-lg border transition-all',
                    selectedTime === slot
                      ? 'border-brand-500 bg-brand-50 text-brand-700'
                      : 'border-slate-200 text-slate-700 hover:border-brand-200 hover:bg-slate-50',
                  ]"
                  @click="selectedTime = slot"
                >
                  {{ slot }}
                </button>
              </div>
            </div>
          </div>

          <!-- Selected Summary -->
          <div v-if="selectedDate && selectedTime" class="mt-4 p-3 bg-brand-50 rounded-lg border border-brand-200">
            <div class="flex items-center gap-2 text-sm text-brand-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="font-medium">
                {{ formatDateDisplay(selectedDate) }}, {{ selectedTime }} - {{ computedEndTime }}
              </span>
            </div>
          </div>
        </div>

        <!-- Step 4: Select Customer -->
        <div v-if="currentStep === 4">
          <h3 class="text-base font-semibold text-slate-900 mb-4">{{ t('appointments.selectCustomer') }}</h3>

          <div v-if="!isQuickCreate">
            <BSearchBar
              v-model="customerSearch"
              :placeholder="t('customers.searchPlaceholder')"
              class="mb-4"
            />

            <div class="space-y-2 max-h-[280px] overflow-y-auto">
              <button
                v-for="customer in filteredCustomers"
                :key="customer.id"
                :class="[
                  'w-full text-left p-3 rounded-lg border-2 transition-all flex items-center gap-3',
                  selectedCustomerId === customer.id
                    ? 'border-brand-500 bg-brand-50'
                    : 'border-slate-200 hover:border-brand-200 hover:bg-slate-50',
                ]"
                @click="selectedCustomerId = customer.id"
              >
                <div
                  :class="[
                    AVATAR_STYLES.initials.sm,
                    selectedCustomerId === customer.id
                      ? 'bg-brand-200 text-brand-800'
                      : 'bg-emerald-100 text-emerald-700',
                  ]"
                >
                  {{ customer.name.split(' ').map((p: string) => p[0]).join('').toUpperCase().slice(0, 2) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-slate-900">{{ customer.name }}</div>
                  <div class="text-xs text-slate-500">{{ customer.email }} &middot; {{ customer.phone }}</div>
                </div>
                <svg
                  v-if="selectedCustomerId === customer.id"
                  class="w-5 h-5 text-brand-600 flex-shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>

            <button
              :class="[BUTTON_STYLES.ghost, 'w-full mt-3 flex items-center justify-center gap-2']"
              @click="isQuickCreate = true"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ t('customers.newCustomer') }}
            </button>
          </div>

          <!-- Quick Create Customer -->
          <div v-else class="space-y-4">
            <div class="flex items-center gap-2 mb-2">
              <button :class="BUTTON_STYLES.icon" @click="isQuickCreate = false">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <h4 class="text-sm font-semibold text-slate-900">{{ t('customers.newCustomer') }}</h4>
            </div>
            <BInput
              v-model="quickCustomerName"
              :label="t('customers.firstName') + ' & ' + t('customers.lastName')"
              :placeholder="'z.B. Maria Schneider'"
              required
            />
            <BInput
              v-model="quickCustomerEmail"
              type="email"
              :label="t('customers.email')"
              :placeholder="'email@beispiel.ch'"
            />
            <BInput
              v-model="quickCustomerPhone"
              type="tel"
              :label="t('customers.phone')"
              :placeholder="'+41 79 123 45 67'"
            />
            <BButton
              variant="primary"
              :disabled="!quickCustomerName"
              @click="quickCreateCustomer"
            >
              {{ t('common.create') }}
            </BButton>
          </div>
        </div>

        <!-- Step 5: Confirmation -->
        <div v-if="currentStep === 5">
          <h3 class="text-base font-semibold text-slate-900 mb-4">{{ t('common.confirm') }}</h3>

          <div :class="[CARD_STYLES.base, 'divide-y divide-slate-200']">
            <!-- Service -->
            <div class="p-4 flex items-center justify-between">
              <div>
                <div class="text-xs text-slate-500 uppercase tracking-wider">{{ t('appointments.selectService') }}</div>
                <div class="text-sm font-semibold text-slate-900 mt-0.5">{{ selectedService?.name }}</div>
                <div class="text-xs text-slate-500">{{ selectedService?.duration }} min</div>
              </div>
              <button class="text-xs text-brand-600 hover:text-brand-700 font-medium" @click="goToStep(1)">
                {{ t('common.edit') }}
              </button>
            </div>

            <!-- Employee -->
            <div class="p-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div :class="[AVATAR_STYLES.initials.sm, 'bg-brand-100 text-brand-700']">
                  {{ selectedEmployee ? getEmployeeInitials(selectedEmployee.name) : '' }}
                </div>
                <div>
                  <div class="text-xs text-slate-500 uppercase tracking-wider">{{ t('appointments.selectEmployee') }}</div>
                  <div class="text-sm font-semibold text-slate-900 mt-0.5">{{ selectedEmployee?.name }}</div>
                </div>
              </div>
              <button class="text-xs text-brand-600 hover:text-brand-700 font-medium" @click="goToStep(2)">
                {{ t('common.edit') }}
              </button>
            </div>

            <!-- Date & Time -->
            <div class="p-4 flex items-center justify-between">
              <div>
                <div class="text-xs text-slate-500 uppercase tracking-wider">{{ t('appointments.selectDate') }} & {{ t('appointments.selectTime') }}</div>
                <div class="text-sm font-semibold text-slate-900 mt-0.5">
                  {{ formatDateDisplay(selectedDate) }}
                </div>
                <div class="text-xs text-slate-500">{{ selectedTime }} - {{ computedEndTime }}</div>
              </div>
              <button class="text-xs text-brand-600 hover:text-brand-700 font-medium" @click="goToStep(3)">
                {{ t('common.edit') }}
              </button>
            </div>

            <!-- Customer -->
            <div class="p-4 flex items-center justify-between">
              <div>
                <div class="text-xs text-slate-500 uppercase tracking-wider">{{ t('appointments.selectCustomer') }}</div>
                <div class="text-sm font-semibold text-slate-900 mt-0.5">{{ selectedCustomer?.name }}</div>
                <div class="text-xs text-slate-500">{{ selectedCustomer?.email }}</div>
              </div>
              <button class="text-xs text-brand-600 hover:text-brand-700 font-medium" @click="goToStep(4)">
                {{ t('common.edit') }}
              </button>
            </div>

            <!-- Price -->
            <div class="p-4 bg-slate-50">
              <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-slate-600">{{ t('appointments.price') }}</div>
                <div class="text-lg font-bold text-slate-900">
                  {{ selectedService ? appStore.formatPrice(selectedService.priceMinor) : '' }}
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="mt-4">
            <label :class="LABEL_STYLES.base">{{ t('appointments.notes') }}</label>
            <textarea
              v-model="notes"
              :class="INPUT_STYLES.textarea"
              :placeholder="t('appointments.notes') + '...'"
              rows="2"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <div class="flex items-center justify-between w-full">
        <BButton
          v-if="currentStep > 1"
          variant="ghost"
          @click="prevStep"
        >
          <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          {{ t('common.back') }}
        </BButton>
        <div v-else />

        <div class="flex items-center gap-3">
          <BButton variant="secondary" @click="close">
            {{ t('common.cancel') }}
          </BButton>
          <BButton
            v-if="currentStep < totalSteps"
            variant="primary"
            :disabled="!canProceed"
            @click="nextStep"
          >
            {{ t('common.next') }}
            <svg class="w-4 h-4 ml-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </BButton>
          <BButton
            v-else
            variant="primary"
            @click="confirmBooking"
          >
            <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ t('common.confirm') }}
          </BButton>
        </div>
      </div>
    </template>
  </BModal>
</template>
