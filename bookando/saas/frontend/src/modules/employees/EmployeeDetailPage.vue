<script setup lang="ts">
/**
 * EmployeeDetailPage — Mitarbeiter-Detailansicht
 *
 * Layout:
 * - Links: Profilkarte (Avatar, Name, Status, Kontakt, Adresse)
 * - Rechts: Quick-Stats + Tab-Inhalte
 *
 * Tabs: Uebersicht, Kalender, Abwesenheiten, Dossier, Qualifikationen
 *
 * Verwendet das Design-System konsequent via @/design Imports
 * und die UI-Basiskomponenten BBadge, BButton, BModal.
 */
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '@/composables/useI18n';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import {
  CARD_STYLES,
  BADGE_STYLES,
  AVATAR_STYLES,
  TAB_STYLES,
  TABLE_STYLES,
  MODULE_DESIGNS,
} from '@/design';
import { useEmployeesStore, AVAILABLE_SERVICES } from '@/stores/employees';
import type { Employee } from '@/stores/employees';
import EmployeeModal from './components/EmployeeModal.vue';
import type { EmployeeFormData } from '@/stores/employees';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const store = useEmployeesStore();

const employeeId = computed(() => route.params.id as string);
const employee = computed(() => store.getEmployeeById(employeeId.value));

// Edit modal
const showEditModal = ref(false);

function handleSaveEdit(data: EmployeeFormData) {
  store.updateEmployee(employeeId.value, data);
  showEditModal.value = false;
}

// Tabs
const detailTabs = [
  { id: 'overview', label: t('employees.detail.tabOverview') },
  { id: 'schedule', label: t('employees.detail.tabSchedule') },
  { id: 'absences', label: t('employees.detail.tabAbsences') },
  { id: 'dossier', label: t('employees.detail.tabDossier') },
  { id: 'qualifications', label: t('employees.detail.tabQualifications') },
];
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
  const key = `employees.status.${status.toLowerCase().replace('_', '')}`;
  const result = t(key);
  // Fallback if key not found (returns key itself)
  if (result === key) {
    const map: Record<string, string> = {
      ACTIVE: 'Aktiv',
      VACATION: 'Urlaub',
      SICK_LEAVE: 'Krank',
      PAUSE: 'Pause',
      TERMINATED: 'Ausgetreten',
    };
    return map[status] || status;
  }
  return result;
}

function getRoleLabel(role: string): string {
  const key = `employees.role.${role.toLowerCase()}`;
  const result = t(key);
  if (result === key) {
    const map: Record<string, string> = {
      ADMIN: 'Administrator',
      MANAGER: 'Manager',
      EMPLOYEE: 'Mitarbeiter',
      TRAINEE: 'Lernende/r',
    };
    return map[role] || role;
  }
  return result;
}

// Initials for avatar
function getInitials(emp: Employee): string {
  return `${emp.firstName[0]}${emp.lastName[0]}`;
}

// Assigned services resolved
const assignedServices = computed(() => {
  if (!employee.value) return [];
  return AVAILABLE_SERVICES.filter(s =>
    employee.value!.assignedServiceIds.includes(s.id)
  );
});

// Vacation balance
const vacationBalance = computed(() => {
  if (!employee.value) return 0;
  return employee.value.vacationDaysTotal - employee.value.vacationDaysUsed;
});

// Mock stats
const mockStats = {
  bookingsThisMonth: 47,
  hoursThisMonth: 156,
  revenueThisMonth: 892000, // minor units
};

// Mock absences
const mockAbsences = [
  { id: 'abs-1', type: 'vacation', from: '2025-12-23', to: '2026-01-02', days: 7, status: 'approved' },
  { id: 'abs-2', type: 'sick', from: '2025-11-05', to: '2025-11-06', days: 2, status: 'approved' },
  { id: 'abs-3', type: 'vacation', from: '2026-03-15', to: '2026-03-21', days: 5, status: 'pending' },
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

function formatSalary(minorUnits: number): string {
  return new Intl.NumberFormat('de-CH', {
    style: 'currency',
    currency: 'CHF',
  }).format(minorUnits / 100);
}
</script>

<template>
  <ModuleLayout
    module-name="employees"
    :title="employee ? `${employee.firstName} ${employee.lastName}` : t('employees.detail.notFound')"
    :subtitle="employee?.position || ''"
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
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- LEFT: Profile Card -->
      <div class="lg:col-span-1 space-y-6">
        <div :class="CARD_STYLES.base" class="overflow-hidden">
          <!-- Banner -->
          <div :class="CARD_STYLES.banner" class="from-slate-700 to-slate-900" />

          <!-- Profile Content -->
          <div :class="CARD_STYLES.bannerContent">
            <!-- Avatar -->
            <div
              :class="AVATAR_STYLES.initials['2xl']"
              class="bg-white text-slate-700 border-4 border-white shadow-lg"
            >
              {{ getInitials(employee) }}
            </div>

            <h2 class="mt-3 text-lg font-bold text-slate-900">
              {{ employee.firstName }} {{ employee.lastName }}
            </h2>
            <p class="text-sm text-slate-500">{{ employee.position }}</p>

            <div class="flex items-center gap-2 mt-2">
              <BBadge :status="employee.status" dot>
                {{ getStatusLabel(employee.status) }}
              </BBadge>
              <BBadge variant="outline">
                {{ getRoleLabel(employee.role) }}
              </BBadge>
            </div>

            <!-- Contact Info -->
            <div class="mt-6 space-y-3">
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

            <!-- Address -->
            <div class="mt-4 pt-4 border-t border-slate-200">
              <div class="flex items-start gap-3 text-sm">
                <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div class="text-slate-700">
                  <p>{{ employee.street }}</p>
                  <p>{{ employee.zip }} {{ employee.city }}</p>
                  <p class="text-slate-500">{{ employee.country }}</p>
                </div>
              </div>
            </div>

            <!-- Hire Date -->
            <div class="mt-4 pt-4 border-t border-slate-200">
              <p class="text-xs text-slate-400">
                {{ t('employees.detail.employeeSince') }} {{ employee.hireDate }}
              </p>
              <p v-if="employee.exitDate" class="text-xs text-red-500 mt-1">
                {{ t('employees.detail.exitDate') }}: {{ employee.exitDate }}
              </p>
            </div>
          </div>
        </div>

        <!-- Quick Actions (mobile edit button) -->
        <div class="lg:hidden">
          <BButton variant="primary" class="w-full" @click="showEditModal = true">
            {{ t('employees.action.edit') }}
          </BButton>
        </div>
      </div>

      <!-- RIGHT: Stats + Tabs -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Quick Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div :class="CARD_STYLES.stat" class="!p-4 text-center">
            <p class="text-2xl font-bold text-brand-600">{{ mockStats.bookingsThisMonth }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ t('employees.detail.statBookings') }}</p>
          </div>
          <div :class="CARD_STYLES.stat" class="!p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ mockStats.hoursThisMonth }}h</p>
            <p class="text-xs text-slate-500 mt-1">{{ t('employees.detail.statHours') }}</p>
          </div>
          <div :class="CARD_STYLES.stat" class="!p-4 text-center">
            <p class="text-2xl font-bold text-purple-600">{{ vacationBalance }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ t('employees.detail.statVacation') }}</p>
          </div>
          <div :class="CARD_STYLES.stat" class="!p-4 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ assignedServices.length }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ t('employees.detail.statQualifications') }}</p>
          </div>
        </div>

        <!-- Tab Navigation -->
        <div :class="CARD_STYLES.base" class="overflow-hidden">
          <div class="border-b border-slate-200 px-6 pt-4">
            <nav :class="TAB_STYLES.container" role="tablist" class="-mb-px">
              <button
                v-for="tab in detailTabs"
                :key="tab.id"
                role="tab"
                :aria-selected="activeTab === tab.id"
                :class="[
                  'px-4 py-3 text-sm font-medium border-b-2 transition-all duration-200 whitespace-nowrap',
                  activeTab === tab.id
                    ? 'text-slate-900 border-slate-700'
                    : 'text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300',
                ]"
                @click="activeTab = tab.id"
              >
                {{ tab.label }}
              </button>
            </nav>
          </div>

          <div class="p-6">
            <!-- Overview Tab -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
              <!-- Bio -->
              <div v-if="employee.bio">
                <h3 class="text-sm font-semibold text-slate-900 mb-2">{{ t('employees.detail.bio') }}</h3>
                <p class="text-sm text-slate-600">{{ employee.bio }}</p>
              </div>

              <!-- HR Summary -->
              <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ t('employees.detail.hrSummary') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                    <span class="text-sm font-medium text-slate-900">
                      {{ employee.vacationDaysUsed }} / {{ employee.vacationDaysTotal }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                    <span class="text-sm text-slate-500">{{ t('employees.field.role') }}</span>
                    <span class="text-sm font-medium text-slate-900">{{ getRoleLabel(employee.role) }}</span>
                  </div>
                </div>
              </div>

              <!-- Assigned Services -->
              <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ t('employees.detail.assignedServices') }}</h3>
                <div v-if="assignedServices.length > 0" class="flex flex-wrap gap-2">
                  <BBadge
                    v-for="svc in assignedServices"
                    :key="svc.id"
                    variant="default"
                  >
                    {{ svc.name }}
                  </BBadge>
                </div>
                <p v-else class="text-sm text-slate-400">{{ t('employees.detail.noServices') }}</p>
              </div>
            </div>

            <!-- Schedule Tab -->
            <div v-else-if="activeTab === 'schedule'" class="space-y-4">
              <h3 class="text-sm font-semibold text-slate-900">{{ t('employees.detail.weeklySchedule') }}</h3>

              <!-- Mobile: Cards -->
              <div class="md:hidden space-y-2">
                <div
                  v-for="slot in mockSchedule"
                  :key="slot.day"
                  :class="[
                    'p-3 rounded-lg border',
                    slot.start ? 'bg-white border-slate-200' : 'bg-slate-50 border-slate-100',
                  ]"
                >
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-900">{{ slot.day }}</span>
                    <span v-if="slot.start" class="text-sm text-slate-600">
                      {{ slot.start }} - {{ slot.end }}
                    </span>
                    <BBadge v-else variant="default">{{ t('employees.detail.dayOff') }}</BBadge>
                  </div>
                  <p v-if="slot.break" class="text-xs text-slate-400 mt-1">
                    {{ t('employees.detail.break') }}: {{ slot.break }}
                  </p>
                </div>
              </div>

              <!-- Desktop: Table -->
              <div class="hidden md:block">
                <div :class="TABLE_STYLES.container">
                  <table :class="TABLE_STYLES.table">
                    <thead :class="TABLE_STYLES.thead">
                      <tr>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.day') }}</th>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.start') }}</th>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.end') }}</th>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.break') }}</th>
                      </tr>
                    </thead>
                    <tbody :class="TABLE_STYLES.tbody">
                      <tr
                        v-for="slot in mockSchedule"
                        :key="slot.day"
                        :class="TABLE_STYLES.tr"
                      >
                        <td :class="TABLE_STYLES.tdBold">{{ slot.day }}</td>
                        <td :class="TABLE_STYLES.td">{{ slot.start || '-' }}</td>
                        <td :class="TABLE_STYLES.td">{{ slot.end || '-' }}</td>
                        <td :class="TABLE_STYLES.td">{{ slot.break || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Absences Tab -->
            <div v-else-if="activeTab === 'absences'" class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">{{ t('employees.detail.absences') }}</h3>
                <BButton variant="secondary" class="!text-xs !px-3 !py-1.5">
                  {{ t('employees.detail.requestAbsence') }}
                </BButton>
              </div>

              <!-- Vacation Balance Bar -->
              <div class="p-4 bg-slate-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                  <span class="text-sm text-slate-600">{{ t('employees.detail.vacationBalance') }}</span>
                  <span class="text-sm font-semibold text-slate-900">
                    {{ vacationBalance }} {{ t('employees.detail.daysRemaining') }}
                  </span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2.5">
                  <div
                    class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500"
                    :style="{ width: `${(employee.vacationDaysUsed / employee.vacationDaysTotal) * 100}%` }"
                  />
                </div>
                <p class="text-xs text-slate-400 mt-1">
                  {{ employee.vacationDaysUsed }} {{ t('employees.detail.of') }} {{ employee.vacationDaysTotal }} {{ t('employees.detail.daysUsed') }}
                </p>
              </div>

              <!-- Mobile: Cards -->
              <div class="md:hidden space-y-2">
                <div
                  v-for="absence in mockAbsences"
                  :key="absence.id"
                  class="p-3 bg-white rounded-lg border border-slate-200"
                >
                  <div class="flex justify-between items-center">
                    <BBadge :variant="absence.type === 'vacation' ? 'warning' : 'danger'">
                      {{ absence.type === 'vacation' ? t('employees.detail.absenceVacation') : t('employees.detail.absenceSick') }}
                    </BBadge>
                    <BBadge :variant="absence.status === 'approved' ? 'success' : 'warning'">
                      {{ absence.status === 'approved' ? t('employees.detail.approved') : t('employees.detail.pending') }}
                    </BBadge>
                  </div>
                  <p class="text-sm text-slate-700 mt-2">{{ absence.from }} - {{ absence.to }}</p>
                  <p class="text-xs text-slate-400">{{ absence.days }} {{ t('employees.detail.days') }}</p>
                </div>
              </div>

              <!-- Desktop: Table -->
              <div class="hidden md:block">
                <div :class="TABLE_STYLES.container">
                  <table :class="TABLE_STYLES.table">
                    <thead :class="TABLE_STYLES.thead">
                      <tr>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.absenceType') }}</th>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.from') }}</th>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.to') }}</th>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.days') }}</th>
                        <th :class="TABLE_STYLES.th">{{ t('employees.detail.absenceStatus') }}</th>
                      </tr>
                    </thead>
                    <tbody :class="TABLE_STYLES.tbody">
                      <tr
                        v-for="absence in mockAbsences"
                        :key="absence.id"
                        :class="TABLE_STYLES.tr"
                      >
                        <td :class="TABLE_STYLES.td">
                          <BBadge :variant="absence.type === 'vacation' ? 'warning' : 'danger'">
                            {{ absence.type === 'vacation' ? t('employees.detail.absenceVacation') : t('employees.detail.absenceSick') }}
                          </BBadge>
                        </td>
                        <td :class="TABLE_STYLES.td">{{ absence.from }}</td>
                        <td :class="TABLE_STYLES.td">{{ absence.to }}</td>
                        <td :class="TABLE_STYLES.td">{{ absence.days }}</td>
                        <td :class="TABLE_STYLES.td">
                          <BBadge :variant="absence.status === 'approved' ? 'success' : 'warning'">
                            {{ absence.status === 'approved' ? t('employees.detail.approved') : t('employees.detail.pending') }}
                          </BBadge>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Dossier Tab -->
            <div v-else-if="activeTab === 'dossier'" class="space-y-4">
              <h3 class="text-sm font-semibold text-slate-900">{{ t('employees.detail.dossier') }}</h3>
              <div class="flex flex-col items-center justify-center py-12 text-center">
                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm text-slate-500">{{ t('employees.detail.dossierEmpty') }}</p>
                <BButton variant="secondary" class="mt-3 !text-xs">
                  {{ t('employees.detail.uploadDocument') }}
                </BButton>
              </div>
            </div>

            <!-- Qualifications Tab -->
            <div v-else-if="activeTab === 'qualifications'" class="space-y-4">
              <h3 class="text-sm font-semibold text-slate-900">{{ t('employees.detail.qualifications') }}</h3>

              <!-- Assigned Services as Qualifications -->
              <div v-if="assignedServices.length > 0" class="space-y-2">
                <div
                  v-for="svc in assignedServices"
                  :key="svc.id"
                  class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                      <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div>
                      <p class="text-sm font-medium text-slate-900">{{ svc.name }}</p>
                      <p class="text-xs text-slate-500">{{ svc.category }}</p>
                    </div>
                  </div>
                  <BBadge variant="success">{{ t('employees.detail.qualified') }}</BBadge>
                </div>
              </div>
              <p v-else class="text-sm text-slate-400 text-center py-8">
                {{ t('employees.detail.noQualifications') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <EmployeeModal
      v-if="employee"
      v-model="showEditModal"
      :employee="employee"
      @save="handleSaveEdit"
    />
  </ModuleLayout>
</template>
