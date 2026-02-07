<script setup lang="ts">
/**
 * EmployeesPage — Mitarbeiter-Uebersicht
 *
 * Kartenbasierte Uebersicht aller Mitarbeiter mit:
 * - ModuleLayout OHNE Tabs (Hero links + Toolbar rechts)
 * - Responsive Card-Grid (1/2/3/4 Spalten)
 * - Rich Cards mit Banner, Initialen-Avatar, Status-Badge
 * - Search im Header, New-Button Desktop, FAB Mobile
 * - Mock-Daten mit Schweizer Namen
 *
 * Verwendet:
 * - useDesignStore fuer reaktives Design
 * - useEmployeesStore fuer State-Management
 * - useI18n fuer alle Strings
 */
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import { useEmployeesStore } from '@/stores/employees';
import type { Employee } from '@/stores/employees';
import EmployeeModal from './components/EmployeeModal.vue';
import type { EmployeeFormData } from '@/stores/employees';
import {
  CARD_STYLES,
  BADGE_STYLES,
  BUTTON_STYLES,
  EMPTY_STATE_STYLES,
} from '@/design';

const { t } = useI18n();
const router = useRouter();
const store = useEmployeesStore();
const designStore = useDesignStore();
const design = computed(() => designStore.getModuleDesign('employees'));

// ============================================================================
// MOCK DATA — Schweizer Namen
// ============================================================================

const MOCK_EMPLOYEES: Employee[] = [
  {
    id: 'EMP-001',
    firstName: 'Lisa',
    lastName: 'Weber',
    email: 'lisa.weber@salon.ch',
    phone: '+41 79 234 56 78',
    position: 'Senior Friseurin',
    department: 'Haarstyling',
    status: 'ACTIVE',
    role: 'MANAGER',
    hireDate: '2019-03-15',
    bio: 'Spezialisiert auf Balayage und kreative Farbkonzepte. 12 Jahre Erfahrung.',
    street: 'Bahnhofstrasse 42',
    zip: '8001',
    city: 'Zuerich',
    country: 'CH',
    salaryMinor: 680000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 8,
    employmentPercent: 100,
    socialSecurityNumber: '756.1234.5678.90',
    assignedServiceIds: ['svc-001', 'svc-003', 'svc-004', 'svc-012'],
    createdAt: '2019-03-15T08:00:00Z',
    updatedAt: '2025-12-01T10:30:00Z',
  },
  {
    id: 'EMP-002',
    firstName: 'Marco',
    lastName: 'Rossi',
    email: 'marco.rossi@salon.ch',
    phone: '+41 78 345 67 89',
    position: 'Barbier',
    department: 'Barbershop',
    status: 'ACTIVE',
    role: 'EMPLOYEE',
    hireDate: '2021-06-01',
    bio: 'Barber mit Leidenschaft fuer klassische Herren-Haarschnitte und Bartpflege.',
    street: 'Via Nassa 15',
    zip: '6900',
    city: 'Lugano',
    country: 'CH',
    salaryMinor: 560000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 12,
    employmentPercent: 100,
    socialSecurityNumber: '756.2345.6789.01',
    assignedServiceIds: ['svc-002', 'svc-005', 'svc-006'],
    createdAt: '2021-06-01T08:00:00Z',
    updatedAt: '2025-11-15T14:00:00Z',
  },
  {
    id: 'EMP-003',
    firstName: 'Sophie',
    lastName: 'Dubois',
    email: 'sophie.dubois@salon.ch',
    phone: '+41 76 456 78 90',
    position: 'Kosmetikerin',
    department: 'Kosmetik',
    status: 'VACATION',
    role: 'EMPLOYEE',
    hireDate: '2022-01-10',
    bio: 'Diplomierte Kosmetikerin mit Schwerpunkt Gesichtsbehandlungen und Hautpflege.',
    street: 'Rue du Rhone 28',
    zip: '1204',
    city: 'Geneve',
    country: 'CH',
    salaryMinor: 520000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 18,
    employmentPercent: 80,
    socialSecurityNumber: '756.3456.7890.12',
    assignedServiceIds: ['svc-007', 'svc-008', 'svc-009'],
    createdAt: '2022-01-10T08:00:00Z',
    updatedAt: '2025-12-10T09:00:00Z',
  },
  {
    id: 'EMP-004',
    firstName: 'Thomas',
    lastName: 'Mueller',
    email: 'thomas.mueller@salon.ch',
    phone: '+41 79 567 89 01',
    position: 'Wellness-Therapeut',
    department: 'Wellness',
    status: 'ACTIVE',
    role: 'EMPLOYEE',
    hireDate: '2020-09-01',
    bio: 'Ausgebildeter Masseur mit Zertifizierung in Hot-Stone und klassischer Massage.',
    street: 'Kramgasse 7',
    zip: '3011',
    city: 'Bern',
    country: 'CH',
    salaryMinor: 580000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 5,
    employmentPercent: 100,
    socialSecurityNumber: '756.4567.8901.23',
    assignedServiceIds: ['svc-010', 'svc-011'],
    createdAt: '2020-09-01T08:00:00Z',
    updatedAt: '2025-11-28T16:00:00Z',
  },
  {
    id: 'EMP-005',
    firstName: 'Elena',
    lastName: 'Keller',
    email: 'elena.keller@salon.ch',
    phone: '+41 77 678 90 12',
    position: 'Empfangsleiterin',
    department: 'Empfang',
    status: 'ACTIVE',
    role: 'EMPLOYEE',
    hireDate: '2023-02-15',
    bio: 'Verantwortlich fuer Terminkoordination, Kundenkommunikation und Empfang.',
    street: 'Marktgasse 20',
    zip: '4051',
    city: 'Basel',
    country: 'CH',
    salaryMinor: 480000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 3,
    employmentPercent: 80,
    socialSecurityNumber: '756.5678.9012.34',
    assignedServiceIds: [],
    createdAt: '2023-02-15T08:00:00Z',
    updatedAt: '2025-12-05T11:00:00Z',
  },
  {
    id: 'EMP-006',
    firstName: 'Luca',
    lastName: 'Bernasconi',
    email: 'luca.bernasconi@salon.ch',
    phone: '+41 78 789 01 23',
    position: 'Lernender Coiffeur',
    department: 'Haarstyling',
    status: 'ACTIVE',
    role: 'TRAINEE',
    hireDate: '2025-08-01',
    bio: 'Im 1. Lehrjahr, lernt Grundtechniken des Haarschneidens und Styling.',
    street: 'Pilatusstrasse 5',
    zip: '6003',
    city: 'Luzern',
    country: 'CH',
    salaryMinor: 85000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 0,
    employmentPercent: 100,
    socialSecurityNumber: '756.6789.0123.45',
    assignedServiceIds: ['svc-002'],
    createdAt: '2025-08-01T08:00:00Z',
    updatedAt: '2025-12-01T08:00:00Z',
  },
  {
    id: 'EMP-007',
    firstName: 'Anna',
    lastName: 'Schneider',
    email: 'anna.schneider@salon.ch',
    phone: '+41 76 890 12 34',
    position: 'Friseurin',
    department: 'Haarstyling',
    status: 'SICK_LEAVE',
    role: 'EMPLOYEE',
    hireDate: '2021-11-01',
    bio: 'Erfahrene Friseurin mit Fokus auf Hochsteckfrisuren und Braut-Styling.',
    street: 'Obere Bahnhofstrasse 10',
    zip: '9500',
    city: 'Wil',
    country: 'CH',
    salaryMinor: 540000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 10,
    employmentPercent: 100,
    socialSecurityNumber: '756.7890.1234.56',
    assignedServiceIds: ['svc-001', 'svc-004', 'svc-012'],
    createdAt: '2021-11-01T08:00:00Z',
    updatedAt: '2026-01-15T09:00:00Z',
  },
  {
    id: 'EMP-008',
    firstName: 'Pierre',
    lastName: 'Favre',
    email: 'pierre.favre@salon.ch',
    phone: '+41 79 901 23 45',
    position: 'Geschaeftsfuehrer',
    department: 'Management',
    status: 'ACTIVE',
    role: 'ADMIN',
    hireDate: '2018-01-01',
    bio: 'Gruender und Geschaeftsfuehrer. 20 Jahre Erfahrung in der Beauty-Branche.',
    street: 'Avenue de la Gare 3',
    zip: '1003',
    city: 'Lausanne',
    country: 'CH',
    salaryMinor: 950000,
    vacationDaysTotal: 30,
    vacationDaysUsed: 15,
    employmentPercent: 100,
    socialSecurityNumber: '756.8901.2345.67',
    assignedServiceIds: [],
    createdAt: '2018-01-01T08:00:00Z',
    updatedAt: '2025-12-20T17:00:00Z',
  },
  {
    id: 'EMP-009',
    firstName: 'Chiara',
    lastName: 'Bianchi',
    email: 'chiara.bianchi@salon.ch',
    phone: '+41 77 012 34 56',
    position: 'Nageldesignerin',
    department: 'Kosmetik',
    status: 'PAUSE',
    role: 'EMPLOYEE',
    hireDate: '2022-07-01',
    bio: 'Spezialisiert auf Gel- und Acrylnaegel, Nail Art und Manikuere.',
    street: 'Seestrasse 45',
    zip: '8800',
    city: 'Thalwil',
    country: 'CH',
    salaryMinor: 460000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 6,
    employmentPercent: 60,
    socialSecurityNumber: '756.9012.3456.78',
    assignedServiceIds: ['svc-008', 'svc-009'],
    createdAt: '2022-07-01T08:00:00Z',
    updatedAt: '2025-10-01T08:00:00Z',
  },
  {
    id: 'EMP-010',
    firstName: 'Nina',
    lastName: 'Huber',
    email: 'nina.huber@salon.ch',
    phone: '+41 78 123 45 67',
    position: 'Coloristin',
    department: 'Haarstyling',
    status: 'TERMINATED',
    role: 'EMPLOYEE',
    hireDate: '2020-04-01',
    exitDate: '2025-09-30',
    bio: 'Ehemalige Coloristin. Spezialisiert auf Folienstraehnen und Ombre-Techniken.',
    street: 'Freiestrasse 12',
    zip: '8032',
    city: 'Zuerich',
    country: 'CH',
    salaryMinor: 560000,
    vacationDaysTotal: 25,
    vacationDaysUsed: 25,
    employmentPercent: 100,
    socialSecurityNumber: '756.0123.4567.89',
    assignedServiceIds: ['svc-001', 'svc-003', 'svc-004'],
    createdAt: '2020-04-01T08:00:00Z',
    updatedAt: '2025-09-30T17:00:00Z',
  },
];

// ============================================================================
// STATE
// ============================================================================

const showCreateModal = ref(false);

// Seed mock data on mount if store is empty
onMounted(() => {
  if (store.employees.length === 0) {
    store.employees.push(...MOCK_EMPLOYEES);
  }
});

// ============================================================================
// COMPUTED
// ============================================================================

const filteredEmployees = computed(() => store.filteredEmployees);

// Status configuration
const statusConfig: Record<string, { class: string; label: string }> = {
  ACTIVE: { class: 'bg-emerald-500 text-white', label: t('employees.status.active') },
  VACATION: { class: 'bg-amber-500 text-white', label: t('employees.status.vacation') },
  SICK_LEAVE: { class: 'bg-red-500 text-white', label: t('employees.status.sickLeave') },
  PAUSE: { class: 'bg-slate-400 text-white', label: t('employees.status.pause') },
  TERMINATED: { class: 'bg-slate-600 text-white', label: t('employees.status.terminated') },
};

const roleConfig: Record<string, { class: string; label: string }> = {
  ADMIN: { class: 'bg-indigo-50 border-indigo-100 text-indigo-700', label: t('employees.role.admin') },
  MANAGER: { class: 'bg-purple-50 border-purple-100 text-purple-700', label: t('employees.role.manager') },
  EMPLOYEE: { class: 'bg-slate-50 border-slate-200 text-slate-600', label: t('employees.role.employee') },
  TRAINEE: { class: 'bg-sky-50 border-sky-100 text-sky-700', label: t('employees.role.trainee') },
};

// ============================================================================
// METHODS
// ============================================================================

function getInitials(emp: Employee): string {
  return `${emp.firstName[0] || ''}${emp.lastName[0] || ''}`;
}

function openCreateModal() {
  showCreateModal.value = true;
}

function handleCreate(data: EmployeeFormData) {
  store.createEmployee(data);
  showCreateModal.value = false;
}

function handleCardClick(emp: Employee) {
  router.push(`/employees/${emp.id}`);
}

function handleSearch(query: string) {
  store.setFilters({ search: query });
}

function handleFabClick() {
  openCreateModal();
}
</script>

<template>
  <ModuleLayout
    module-name="employees"
    :title="t('employees.title')"
    :subtitle="t('employees.subtitle', { count: store.employeeCount })"
    :show-fab="true"
    :fab-label="t('employees.fabLabel')"
    :search-placeholder="t('employees.searchPlaceholder')"
    @search="handleSearch"
    @fab-click="handleFabClick"
  >
    <!-- Hero Icon -->
    <template #hero-icon>
      <svg class="w-6 h-6 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
    </template>

    <!-- Hero Watermark -->
    <template #hero-watermark>
      <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </template>

    <!-- Desktop Primary Action: New Employee Button -->
    <template #primary-action>
      <button
        :class="BUTTON_STYLES.primary"
        class="!inline-flex items-center gap-2"
        @click="openCreateModal"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span class="hidden sm:inline">{{ t('employees.action.new') }}</span>
      </button>
    </template>

    <!-- Header Actions: Export Button -->
    <template #header-actions>
      <button
        :class="BUTTON_STYLES.secondary"
        class="!inline-flex items-center gap-2 !px-3 !py-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span class="hidden md:inline">{{ t('common.export') }}</span>
      </button>
    </template>

    <!-- Filter Content -->
    <template #filter-content>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('employees.field.status') }}</label>
          <select
            :value="store.filters.status"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
            @change="store.setFilters({ status: ($event.target as HTMLSelectElement).value as any })"
          >
            <option value="">{{ t('common.filter') }}</option>
            <option value="ACTIVE">{{ t('employees.status.active') }}</option>
            <option value="VACATION">{{ t('employees.status.vacation') }}</option>
            <option value="SICK_LEAVE">{{ t('employees.status.sickLeave') }}</option>
            <option value="PAUSE">{{ t('employees.status.pause') }}</option>
            <option value="TERMINATED">{{ t('employees.status.terminated') }}</option>
          </select>
        </div>
        <!-- Department Filter -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('employees.field.department') }}</label>
          <select
            :value="store.filters.department"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
            @change="store.setFilters({ department: ($event.target as HTMLSelectElement).value })"
          >
            <option value="">{{ t('common.filter') }}</option>
            <option value="Haarstyling">Haarstyling</option>
            <option value="Barbershop">Barbershop</option>
            <option value="Kosmetik">Kosmetik</option>
            <option value="Wellness">Wellness</option>
            <option value="Empfang">Empfang</option>
            <option value="Management">Management</option>
          </select>
        </div>
        <!-- Role Filter -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('employees.field.role') }}</label>
          <select
            :value="store.filters.role"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
            @change="store.setFilters({ role: ($event.target as HTMLSelectElement).value as any })"
          >
            <option value="">{{ t('common.filter') }}</option>
            <option value="ADMIN">{{ t('employees.role.admin') }}</option>
            <option value="MANAGER">{{ t('employees.role.manager') }}</option>
            <option value="EMPLOYEE">{{ t('employees.role.employee') }}</option>
            <option value="TRAINEE">{{ t('employees.role.trainee') }}</option>
          </select>
        </div>
      </div>
    </template>

    <!-- ================================================================== -->
    <!-- EMPLOYEE CARD GRID                                                 -->
    <!-- ================================================================== -->
    <div class="p-4 sm:p-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
        <div
          v-for="emp in filteredEmployees"
          :key="emp.id"
          :class="CARD_STYLES.gridItem"
          @click="handleCardClick(emp)"
        >
          <!-- Card Banner -->
          <div class="h-20 sm:h-24 bg-gradient-to-r from-slate-800 to-slate-900 relative">
            <!-- Status Badge (top-right on banner) -->
            <div class="absolute top-3 right-3">
              <span
                :class="[
                  'px-2 py-0.5 text-[10px] font-bold uppercase rounded-full shadow-sm border border-white/10',
                  statusConfig[emp.status]?.class || 'bg-slate-500 text-white',
                ]"
              >
                {{ statusConfig[emp.status]?.label || emp.status }}
              </span>
            </div>
          </div>

          <!-- Card Content -->
          <div class="px-4 sm:px-5 flex-1 flex flex-col relative">
            <!-- Avatar — overlapping the banner -->
            <div class="-mt-10 mb-2 flex justify-between items-end">
              <div class="w-20 h-20 rounded-xl border-4 border-white shadow-md bg-white overflow-hidden flex items-center justify-center">
                <div
                  v-if="!emp.avatar"
                  :class="[
                    'w-full h-full flex items-center justify-center text-2xl font-bold',
                    design.iconBg, design.iconText,
                  ]"
                >
                  {{ getInitials(emp) }}
                </div>
                <img
                  v-else
                  :src="emp.avatar"
                  :alt="`${emp.firstName} ${emp.lastName}`"
                  class="w-full h-full object-cover"
                />
              </div>
              <!-- Edit icon hint -->
              <button
                class="mb-1 p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
                @click.stop="router.push(`/employees/${emp.id}`)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>

            <!-- Name & Position -->
            <div class="mb-4">
              <h3 class="font-bold text-base text-slate-900 truncate">
                {{ emp.firstName }} {{ emp.lastName }}
              </h3>
              <p class="text-sm font-medium text-brand-600 truncate">
                {{ emp.position }}
              </p>

              <!-- Contact Info -->
              <div class="mt-3 space-y-2">
                <div class="flex items-center gap-2.5 text-sm text-slate-600">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <span class="truncate">{{ emp.email }}</span>
                </div>
                <div class="flex items-center gap-2.5 text-sm text-slate-600">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <span class="truncate">{{ emp.department }}</span>
                </div>
                <div v-if="emp.phone" class="flex items-center gap-2.5 text-sm text-slate-600">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <span class="truncate">{{ emp.phone }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Footer -->
          <div class="px-4 sm:px-5 py-3 bg-slate-50 border-t border-slate-100 flex justify-between items-center mt-auto">
            <div class="flex flex-col">
              <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                {{ t('employees.detail.employeeSince') }}
              </span>
              <span class="text-xs font-medium text-slate-700">{{ emp.hireDate }}</span>
            </div>
            <div
              v-if="emp.role"
              :class="[
                'flex items-center gap-1.5 px-2 py-0.5 rounded border text-xs font-bold',
                roleConfig[emp.role]?.class || 'bg-slate-50 border-slate-200 text-slate-600',
              ]"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              {{ roleConfig[emp.role]?.label || emp.role }}
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredEmployees.length === 0" :class="EMPTY_STATE_STYLES.container">
        <svg :class="EMPTY_STATE_STYLES.icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <h3 :class="EMPTY_STATE_STYLES.title">{{ t('employees.emptyTitle') }}</h3>
        <p :class="EMPTY_STATE_STYLES.description">{{ t('employees.emptyDescription') }}</p>
        <div :class="EMPTY_STATE_STYLES.action">
          <button :class="BUTTON_STYLES.primary" @click="openCreateModal">
            {{ t('employees.action.create') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <EmployeeModal
      v-model="showCreateModal"
      :employee="null"
      @save="handleCreate"
    />
  </ModuleLayout>
</template>
