<script setup lang="ts">
/**
 * EmployeeDetailPage — Mitarbeiter-Dashboard
 *
 * 5 Tabs: Übersicht, Buchungen, Rechnungen, Timeline, DSGVO
 * Foundation-konform: Tenant-first, Money als Integer Minor Units, Event-basierte Kommunikation
 *
 * Zusätzlich: Profil-Sidebar mit Kontaktdaten, HR-Daten, Qualifikationen
 */
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import { useAppStore } from '@/stores/app';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import { useEmployeesStore, AVAILABLE_SERVICES } from '@/stores/employees';
import type { Employee } from '@/stores/employees';
import EmployeeModal from './components/EmployeeModal.vue';
import type { EmployeeFormData } from '@/stores/employees';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const designStore = useDesignStore();
const store = useEmployeesStore();

const employeeId = computed(() => route.params.id as string);
const employee = computed(() => store.getEmployeeById(employeeId.value));

// Edit modal
const showEditModal = ref(false);

function handleSaveEdit(data: EmployeeFormData) {
  store.updateEmployee(employeeId.value, data);
  showEditModal.value = false;
}

// Tabs: Übersicht, Buchungen, Rechnungen, Timeline, DSGVO
const detailTabs = computed<Tab[]>(() => [
  { id: 'overview', label: t('customers.overview') },
  { id: 'bookings', label: t('customers.bookings') },
  { id: 'invoices', label: t('customers.invoices') },
  { id: 'timeline', label: t('customers.timeline') },
  { id: 'gdpr', label: t('customers.gdpr') },
]);
const activeTab = ref('overview');

// Status badge mapping
const statusBadgeVariant: Record<string, string> = {
  ACTIVE: 'success',
  VACATION: 'warning',
  SICK_LEAVE: 'danger',
  PAUSE: 'default',
  TERMINATED: 'danger',
};

function getStatusLabel(status: string): string {
  const map: Record<string, string> = {
    active: 'employees.status.active',
    vacation: 'employees.status.vacation',
    sickleave: 'employees.status.sickLeave',
    sick_leave: 'employees.status.sickLeave',
    pause: 'employees.status.pause',
    terminated: 'employees.status.terminated',
  };
  const key = map[status.toLowerCase().replace('_', '')] || `employees.status.${status.toLowerCase()}`;
  return t(key);
}

function getRoleLabel(role: string): string {
  return t(`employees.role.${role.toLowerCase()}`);
}

function getInitials(emp: Employee): string {
  return `${emp.firstName[0]}${emp.lastName[0]}`;
}

const assignedServices = computed(() => {
  if (!employee.value) return [];
  return AVAILABLE_SERVICES.filter(s => employee.value!.assignedServiceIds.includes(s.id));
});

const vacationBalance = computed(() => {
  if (!employee.value) return 0;
  return employee.value.vacationDaysTotal - employee.value.vacationDaysUsed;
});

function formatPrice(minorUnits: number): string {
  return appStore.formatPrice(minorUnits);
}

function formatSalary(minorUnits: number): string {
  return new Intl.NumberFormat('de-CH', { style: 'currency', currency: 'CHF' }).format(minorUnits / 100);
}

// Mock stats
const mockStats = computed(() => ({
  bookingsThisMonth: 47,
  hoursThisMonth: 156,
  revenueMinor: 892000,
  qualifications: assignedServices.value.length,
}));

// Mock bookings (performed by this employee)
const mockBookings = [
  { id: 'apt-1', date: '2026-02-10', time: '09:00', customer: 'Max Muster', service: 'Herrenhaarschnitt', duration: 45, priceMinor: 6500, status: 'CONFIRMED' },
  { id: 'apt-2', date: '2026-02-10', time: '10:00', customer: 'Anna Bieri', service: 'Damenhaarschnitt', duration: 60, priceMinor: 8500, status: 'CONFIRMED' },
  { id: 'apt-3', date: '2026-02-09', time: '14:30', customer: 'Peter Huber', service: 'Bartpflege', duration: 30, priceMinor: 3500, status: 'COMPLETED' },
  { id: 'apt-4', date: '2026-02-09', time: '11:00', customer: 'Maria Fischer', service: 'Komplett-Paket', duration: 90, priceMinor: 12000, status: 'COMPLETED' },
  { id: 'apt-5', date: '2026-02-08', time: '09:30', customer: 'Thomas Meier', service: 'Färben', duration: 120, priceMinor: 18000, status: 'NO_SHOW' },
];

// Mock invoices (generated from this employee's work)
const mockInvoices = [
  { id: 'inv-1', number: 'INV-2026-0055', date: '2026-02-09', dueDate: '2026-03-09', totalMinor: 15500, status: 'SENT' },
  { id: 'inv-2', number: 'INV-2026-0048', date: '2026-02-02', dueDate: '2026-03-02', totalMinor: 34500, status: 'PAID' },
  { id: 'inv-3', number: 'INV-2026-0039', date: '2026-01-26', dueDate: '2026-02-26', totalMinor: 28000, status: 'PAID' },
];

// Mock timeline
const mockTimeline = [
  { id: 'evt-1', type: 'booking', date: '2026-02-10 09:00', description: 'Termin bestätigt: Max Muster — Herrenhaarschnitt', icon: 'calendar' },
  { id: 'evt-2', type: 'booking', date: '2026-02-09 14:30', description: 'Termin abgeschlossen: Peter Huber — Bartpflege', icon: 'check' },
  { id: 'evt-3', type: 'invoice', date: '2026-02-09 17:00', description: 'Rechnung INV-2026-0055 erstellt (CHF 155.00)', icon: 'document' },
  { id: 'evt-4', type: 'noshow', date: '2026-02-08 09:30', description: 'Nicht erschienen: Thomas Meier — Färben', icon: 'x' },
  { id: 'evt-5', type: 'absence', date: '2026-01-15', description: 'Ferien beantragt: 15.03. — 21.03.2026 (5 Tage)', icon: 'vacation' },
  { id: 'evt-6', type: 'hr', date: '2025-06-01', description: 'Mitarbeiter eingestellt', icon: 'user-plus' },
];

// Mock schedule
const mockSchedule = [
  { day: t('employees.detail.monday'), start: '08:00', end: '17:00', break: '12:00-13:00' },
  { day: t('employees.detail.tuesday'), start: '08:00', end: '17:00', break: '12:00-13:00' },
  { day: t('employees.detail.wednesday'), start: '08:00', end: '17:00', break: '12:00-13:00' },
  { day: t('employees.detail.thursday'), start: '10:00', end: '19:00', break: '14:00-15:00' },
  { day: t('employees.detail.friday'), start: '08:00', end: '16:00', break: '12:00-12:30' },
  { day: t('employees.detail.saturday'), start: '', end: '', break: '' },
  { day: t('employees.detail.sunday'), start: '', end: '', break: '' },
];

// Mock absences
const mockAbsences = [
  { id: 'abs-1', type: 'vacation', from: '2025-12-23', to: '2026-01-02', days: 7, status: 'approved' },
  { id: 'abs-2', type: 'sick', from: '2025-11-05', to: '2025-11-06', days: 2, status: 'approved' },
  { id: 'abs-3', type: 'vacation', from: '2026-03-15', to: '2026-03-21', days: 5, status: 'pending' },
];

function getStatusColor(status: string): string {
  const map: Record<string, string> = {
    CONFIRMED: 'bg-blue-100 text-blue-700',
    COMPLETED: 'bg-emerald-100 text-emerald-700',
    CANCELLED: 'bg-red-100 text-red-700',
    NO_SHOW: 'bg-amber-100 text-amber-700',
    PENDING: 'bg-slate-100 text-slate-600',
  };
  return map[status] || 'bg-slate-100 text-slate-600';
}

function getInvoiceStatusColor(status: string): string {
  const map: Record<string, string> = {
    DRAFT: 'bg-slate-100 text-slate-600',
    SENT: 'bg-blue-100 text-blue-700',
    PAID: 'bg-emerald-100 text-emerald-700',
    OVERDUE: 'bg-red-100 text-red-700',
    CANCELLED: 'bg-slate-100 text-slate-500',
  };
  return map[status] || 'bg-slate-100 text-slate-600';
}

function getTimelineIcon(icon: string): string {
  const map: Record<string, string> = {
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    x: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    vacation: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z',
    'user-plus': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
  };
  return map[icon] || map.check;
}

function getTimelineColor(type: string): string {
  const map: Record<string, string> = {
    booking: 'bg-blue-100 text-blue-600',
    invoice: 'bg-purple-100 text-purple-600',
    noshow: 'bg-red-100 text-red-600',
    absence: 'bg-amber-100 text-amber-600',
    hr: 'bg-slate-100 text-slate-600',
  };
  return map[type] || 'bg-slate-100 text-slate-600';
}
</script>

<template>
  <ModuleLayout
    module-name="employees"
    :title="employee ? `${employee.firstName} ${employee.lastName}` : t('employees.detail.notFound')"
    :subtitle="employee?.position || ''"
    :tabs="detailTabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <button
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors"
          @click="router.push('/employees')"
        >
          {{ t('employees.detail.backToList') }}
        </button>
        <button
          v-if="employee"
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
          @click="showEditModal = true"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          {{ t('employees.action.edit') }}
        </button>
      </div>
    </template>

    <!-- Not Found -->
    <div v-if="!employee" class="flex items-center justify-center py-16">
      <div class="text-center text-slate-400">
        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <h3 class="text-lg font-semibold text-slate-600">{{ t('employees.detail.notFound') }}</h3>
        <p class="text-sm text-slate-400 mt-1">{{ t('employees.detail.notFoundDescription') }}</p>
        <BButton variant="secondary" class="mt-4" @click="router.push('/employees')">
          {{ t('employees.detail.backToList') }}
        </BButton>
      </div>
    </div>

    <!-- Detail Content -->
    <template v-else>
      <!-- ==================== ÜBERSICHT ==================== -->
      <div v-if="activeTab === 'overview'" class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- LEFT: Profile Card -->
          <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <div class="h-16 bg-gradient-to-r from-slate-700 to-slate-900" />
              <div class="px-6 pb-6 -mt-8 text-center">
                <div class="w-16 h-16 rounded-full bg-white text-slate-700 border-4 border-white shadow-lg flex items-center justify-center font-bold text-xl mx-auto">
                  {{ getInitials(employee) }}
                </div>
                <h2 class="mt-3 text-lg font-bold text-slate-900">{{ employee.firstName }} {{ employee.lastName }}</h2>
                <p class="text-sm text-slate-500">{{ employee.position }}</p>
                <div class="flex items-center justify-center gap-2 mt-2">
                  <BBadge :status="employee.status" dot>{{ getStatusLabel(employee.status) }}</BBadge>
                  <BBadge variant="outline">{{ getRoleLabel(employee.role) }}</BBadge>
                </div>

                <div class="mt-6 space-y-3 text-left">
                  <div class="flex items-center gap-3 text-sm">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="text-slate-700 truncate">{{ employee.email }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span class="text-slate-700">{{ employee.phone }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="text-slate-700">{{ employee.department }}</span>
                  </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-200 text-left">
                  <div class="flex items-start gap-3 text-sm">
                    <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div class="text-slate-700">
                      <p>{{ employee.street }}</p>
                      <p>{{ employee.zip }} {{ employee.city }}</p>
                    </div>
                  </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-200">
                  <p class="text-xs text-slate-400">{{ t('employees.detail.employeeSince') }} {{ employee.hireDate }}</p>
                </div>
              </div>
            </div>

            <!-- Assigned Services -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
              <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ t('employees.detail.assignedServices') }}</h3>
              <div v-if="assignedServices.length > 0" class="flex flex-wrap gap-2">
                <BBadge v-for="svc in assignedServices" :key="svc.id" variant="default">{{ svc.name }}</BBadge>
              </div>
              <p v-else class="text-sm text-slate-400">{{ t('employees.detail.noServices') }}</p>
            </div>

            <!-- Mobile Edit -->
            <div class="lg:hidden">
              <BButton variant="primary" class="w-full" @click="showEditModal = true">
                {{ t('employees.action.edit') }}
              </BButton>
            </div>
          </div>

          <!-- RIGHT: Stats + HR + Schedule -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Quick Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-brand-600">{{ mockStats.bookingsThisMonth }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ t('employees.detail.statBookings') }}</p>
              </div>
              <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600">{{ mockStats.hoursThisMonth }}h</p>
                <p class="text-xs text-slate-500 mt-1">{{ t('employees.detail.statHours') }}</p>
              </div>
              <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-purple-600">{{ vacationBalance }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ t('employees.detail.statVacation') }}</p>
              </div>
              <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-amber-600">{{ formatPrice(mockStats.revenueMinor) }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ t('customers.totalRevenue') }}</p>
              </div>
            </div>

            <!-- HR Summary -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
              <div class="px-5 py-3 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">{{ t('employees.hrData') }}</h3>
              </div>
              <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                  <span class="text-sm text-slate-500">{{ t('employees.field.salary') }}</span>
                  <span class="text-sm font-medium text-slate-900">{{ formatSalary(employee.salaryMinor) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                  <span class="text-sm text-slate-500">{{ t('employees.field.employmentPercent') }}</span>
                  <span class="text-sm font-medium text-slate-900">{{ employee.employmentPercent }}%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                  <span class="text-sm text-slate-500">{{ t('employees.field.vacationDays') }}</span>
                  <span class="text-sm font-medium text-slate-900">{{ employee.vacationDaysUsed }} / {{ employee.vacationDaysTotal }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                  <span class="text-sm text-slate-500">{{ t('employees.field.role') }}</span>
                  <span class="text-sm font-medium text-slate-900">{{ getRoleLabel(employee.role) }}</span>
                </div>
              </div>
            </div>

            <!-- Weekly Schedule -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
              <div class="px-5 py-3 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">{{ t('employees.detail.weeklySchedule') }}</h3>
              </div>
              <div class="p-5">
                <div class="grid grid-cols-7 gap-2">
                  <div
                    v-for="slot in mockSchedule"
                    :key="slot.day"
                    :class="[
                      'p-3 rounded-lg text-center text-xs',
                      slot.start ? 'bg-blue-50 border border-blue-100' : 'bg-slate-50 border border-slate-100',
                    ]"
                  >
                    <p class="font-semibold text-slate-700 mb-1">{{ slot.day.slice(0, 2) }}</p>
                    <p v-if="slot.start" class="text-slate-600">{{ slot.start }}</p>
                    <p v-if="slot.end" class="text-slate-600">{{ slot.end }}</p>
                    <p v-if="!slot.start" class="text-slate-400">—</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Absences -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
              <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">{{ t('employees.detail.absences') }}</h3>
                <BButton variant="secondary" class="!text-xs !px-3 !py-1.5">{{ t('employees.detail.requestAbsence') }}</BButton>
              </div>
              <div class="p-5">
                <!-- Vacation Balance -->
                <div class="p-4 bg-slate-50 rounded-lg mb-4">
                  <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-slate-600">{{ t('employees.detail.vacationBalance') }}</span>
                    <span class="text-sm font-semibold text-slate-900">{{ vacationBalance }} {{ t('employees.detail.daysRemaining') }}</span>
                  </div>
                  <div class="w-full bg-slate-200 rounded-full h-2">
                    <div
                      class="bg-emerald-500 h-2 rounded-full transition-all"
                      :style="{ width: `${(employee.vacationDaysUsed / employee.vacationDaysTotal) * 100}%` }"
                    />
                  </div>
                </div>
                <div class="space-y-2">
                  <div
                    v-for="absence in mockAbsences"
                    :key="absence.id"
                    class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg"
                  >
                    <div class="flex items-center gap-3">
                      <BBadge :variant="absence.type === 'vacation' ? 'warning' : 'danger'">
                        {{ absence.type === 'vacation' ? t('employees.detail.absenceVacation') : t('employees.detail.absenceSick') }}
                      </BBadge>
                      <span class="text-sm text-slate-700">{{ absence.from }} — {{ absence.to }} ({{ absence.days }}d)</span>
                    </div>
                    <BBadge :variant="absence.status === 'approved' ? 'success' : 'warning'">
                      {{ absence.status === 'approved' ? t('employees.detail.approved') : t('employees.detail.pending') }}
                    </BBadge>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== BUCHUNGEN ==================== -->
      <div v-else-if="activeTab === 'bookings'" class="p-6">
        <!-- Desktop Table -->
        <div class="hidden md:block">
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.selectDate') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.selectTime') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.selectCustomer') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.selectService') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.duration') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.price') }}</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="b in mockBookings" :key="b.id" class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm text-slate-900">{{ b.date }}</td>
                  <td class="px-4 py-3 text-sm text-slate-600">{{ b.time }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ b.customer }}</td>
                  <td class="px-4 py-3 text-sm text-slate-600">{{ b.service }}</td>
                  <td class="px-4 py-3 text-sm text-slate-600">{{ b.duration }} min</td>
                  <td class="px-4 py-3 text-sm text-slate-900 text-right font-medium">{{ formatPrice(b.priceMinor) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getStatusColor(b.status)]">{{ b.status }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-3">
          <div v-for="b in mockBookings" :key="b.id" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-slate-900">{{ b.customer }}</span>
              <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getStatusColor(b.status)]">{{ b.status }}</span>
            </div>
            <p class="text-xs text-slate-500">{{ b.date }} · {{ b.time }} · {{ b.service }} · {{ b.duration }} min</p>
            <p class="text-sm font-medium text-slate-900 mt-2">{{ formatPrice(b.priceMinor) }}</p>
          </div>
        </div>
      </div>

      <!-- ==================== RECHNUNGEN ==================== -->
      <div v-else-if="activeTab === 'invoices'" class="p-6">
        <!-- Desktop Table -->
        <div class="hidden md:block">
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('finance.invoiceNumber') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('finance.issueDate') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('finance.dueDate') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('finance.total') }}</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="inv in mockInvoices" :key="inv.id" class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ inv.number }}</td>
                  <td class="px-4 py-3 text-sm text-slate-600">{{ inv.date }}</td>
                  <td class="px-4 py-3 text-sm text-slate-600">{{ inv.dueDate }}</td>
                  <td class="px-4 py-3 text-sm text-slate-900 text-right font-medium">{{ formatPrice(inv.totalMinor) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getInvoiceStatusColor(inv.status)]">
                      {{ t(`finance.status.${inv.status.toLowerCase()}`) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-3">
          <div v-for="inv in mockInvoices" :key="inv.id" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-slate-900">{{ inv.number }}</span>
              <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getInvoiceStatusColor(inv.status)]">
                {{ t(`finance.status.${inv.status.toLowerCase()}`) }}
              </span>
            </div>
            <div class="text-xs text-slate-500">
              <p>{{ t('finance.issueDate') }}: {{ inv.date }}</p>
              <p>{{ t('finance.dueDate') }}: {{ inv.dueDate }}</p>
            </div>
            <p class="text-sm font-bold text-slate-900 mt-2">{{ formatPrice(inv.totalMinor) }}</p>
          </div>
        </div>
      </div>

      <!-- ==================== TIMELINE ==================== -->
      <div v-else-if="activeTab === 'timeline'" class="p-6">
        <div class="relative">
          <div class="absolute left-5 top-0 bottom-0 w-px bg-slate-200" />
          <div class="space-y-6">
            <div v-for="evt in mockTimeline" :key="evt.id" class="relative flex items-start gap-4 pl-12">
              <div :class="['absolute left-2 w-7 h-7 rounded-full flex items-center justify-center shrink-0', getTimelineColor(evt.type)]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTimelineIcon(evt.icon)" />
                </svg>
              </div>
              <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex-1 min-w-0">
                <p class="text-sm text-slate-700">{{ evt.description }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ evt.date }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== DSGVO ==================== -->
      <div v-else-if="activeTab === 'gdpr'" class="p-6 max-w-3xl space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
          <div class="px-5 py-3 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">{{ t('customers.gdpr') }} — {{ t('employees.title') }}</h3>
          </div>
          <div class="p-5 space-y-4">
            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
              <div>
                <p class="text-sm font-medium text-blue-900">Art. 15 — {{ t('customers.gdprExport') }}</p>
                <p class="text-xs text-blue-600 mt-0.5">{{ t('customers.gdprExportDesc') }}</p>
              </div>
              <BButton variant="secondary" class="!text-xs shrink-0">{{ t('customers.gdprExport') }}</BButton>
            </div>
            <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
              <div>
                <p class="text-sm font-medium text-red-900">Art. 17 — {{ t('customers.gdprDelete') }}</p>
                <p class="text-xs text-red-600 mt-0.5">{{ t('customers.gdprDeleteDesc') }}</p>
              </div>
              <BButton variant="danger" class="!text-xs shrink-0">{{ t('customers.gdprDelete') }}</BButton>
            </div>
          </div>
        </div>

        <!-- Dossier / Document Upload -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
          <div class="px-5 py-3 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">{{ t('employees.dossier') }}</h3>
          </div>
          <div class="p-5 flex flex-col items-center justify-center py-12 text-center">
            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm text-slate-500">{{ t('employees.detail.dossierEmpty') || 'Noch keine Dokumente vorhanden' }}</p>
            <BButton variant="secondary" class="mt-3 !text-xs">{{ t('employees.detail.uploadDocument') }}</BButton>
          </div>
        </div>
      </div>
    </template>

    <!-- Edit Modal -->
    <EmployeeModal
      v-if="employee"
      v-model="showEditModal"
      :employee="employee"
      @save="handleSaveEdit"
    />
  </ModuleLayout>
</template>
