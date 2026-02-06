<script setup lang="ts">
/**
 * ListView — Tabellarische Termin-Uebersicht
 *
 * Features:
 * - BTable mit Spalten: Zeit, Service, Kunde, Mitarbeiter, Status, Preis, Aktionen
 * - Gruppiert nach Datum mit Datum-Headern
 * - Sortierung, Paginierung
 * - Status-Badges mit Punkt-Indikator
 * - Responsive: Auf Mobile Karten-Layout statt Tabelle
 */
import { ref, computed, watch } from 'vue';
import {
  BUTTON_STYLES,
  CARD_STYLES,
  TABLE_STYLES,
  BADGE_STYLES,
  INPUT_STYLES,
  GRID_STYLES,
  MODAL_STYLES,
  TAB_STYLES,
} from '@/design';
import BTable from '@/components/ui/BTable.vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BModal from '@/components/ui/BModal.vue';
import AppointmentCard from '../components/AppointmentCard.vue';
import AppointmentModal from '../components/AppointmentModal.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useAppStore } from '@/stores/app';
import { useAppointmentsStore } from '@/stores/appointments';
import type { Appointment, AppointmentStatus } from '@/stores/appointments';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const appStore = useAppStore();
const store = useAppointmentsStore();

// Local state
const searchQuery = ref('');
const statusFilter = ref<AppointmentStatus | ''>('');
const employeeFilter = ref('');
const sortBy = ref('date');
const sortDir = ref<'asc' | 'desc'>('asc');
const currentPage = ref(1);
const perPage = ref(25);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedAppointment = ref<Appointment | null>(null);

// Status filter options
const statusOptions = computed(() => [
  { value: '', label: t('common.filter') + ': ' + t('common.select') },
  { value: 'PENDING', label: t('common.pending') },
  { value: 'CONFIRMED', label: t('appointments.confirmed') },
  { value: 'COMPLETED', label: t('common.completed') },
  { value: 'CANCELLED', label: t('common.cancelled') },
  { value: 'NO_SHOW', label: t('appointments.noShow') },
]);

// Employee filter options
const employeeOptions = computed(() => [
  { value: '', label: t('appointments.selectEmployee') },
  ...store.employees.map(e => ({ value: e.id, label: e.name })),
]);

// Apply filters to store
watch([searchQuery, statusFilter, employeeFilter], () => {
  store.setFilters({
    search: searchQuery.value,
    status: statusFilter.value,
    employeeId: employeeFilter.value,
  });
  currentPage.value = 1;
});

// Sorted and paginated data
const sortedAppointments = computed(() => {
  const data = [...store.filteredAppointments];

  data.sort((a, b) => {
    let comparison = 0;
    switch (sortBy.value) {
      case 'date':
        comparison = a.date.localeCompare(b.date) || a.startTime.localeCompare(b.startTime);
        break;
      case 'customerName':
        comparison = a.customerName.localeCompare(b.customerName);
        break;
      case 'employeeName':
        comparison = a.employeeName.localeCompare(b.employeeName);
        break;
      case 'serviceName':
        comparison = a.serviceName.localeCompare(b.serviceName);
        break;
      case 'status':
        comparison = a.status.localeCompare(b.status);
        break;
      case 'priceMinor':
        comparison = a.priceMinor - b.priceMinor;
        break;
      default:
        comparison = a.date.localeCompare(b.date);
    }
    return sortDir.value === 'asc' ? comparison : -comparison;
  });

  return data;
});

const totalItems = computed(() => sortedAppointments.value.length);

const paginatedAppointments = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return sortedAppointments.value.slice(start, start + perPage.value);
});

// Group by date for display
const groupedAppointments = computed(() => {
  const groups: { date: string; label: string; appointments: Appointment[] }[] = [];
  let currentGroupDate = '';

  for (const apt of paginatedAppointments.value) {
    if (apt.date !== currentGroupDate) {
      currentGroupDate = apt.date;
      const d = new Date(apt.date + 'T00:00:00');
      const today = new Date();
      const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
      const tomorrow = new Date(today);
      tomorrow.setDate(tomorrow.getDate() + 1);
      const tomorrowStr = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`;

      let label: string;
      if (apt.date === todayStr) {
        label = t('common.today');
      } else if (apt.date === tomorrowStr) {
        label = t('common.tomorrow');
      } else {
        label = d.toLocaleDateString('de-CH', {
          weekday: 'long',
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        });
      }
      groups.push({ date: apt.date, label, appointments: [] });
    }
    groups[groups.length - 1].appointments.push(apt);
  }

  return groups;
});

// Table columns
const columns = computed(() => [
  { key: 'time', label: t('appointments.selectTime'), sortable: false, width: '100px' },
  { key: 'serviceName', label: t('appointments.selectService'), sortable: true },
  { key: 'customerName', label: t('appointments.selectCustomer'), sortable: true },
  { key: 'employeeName', label: t('appointments.selectEmployee'), sortable: true },
  { key: 'status', label: 'Status', sortable: true, width: '130px' },
  { key: 'priceMinor', label: t('appointments.price'), sortable: true, align: 'right' as const, width: '100px' },
  { key: 'actions', label: '', sortable: false, width: '60px' },
]);

// Table data (flattened for BTable)
const tableData = computed(() =>
  paginatedAppointments.value.map(apt => ({
    ...apt,
    time: `${apt.startTime} - ${apt.endTime}`,
  }))
);

function getStatusLabel(status: string): string {
  const map: Record<string, string> = {
    PENDING: t('common.pending'),
    CONFIRMED: t('appointments.confirmed'),
    COMPLETED: t('common.completed'),
    CANCELLED: t('common.cancelled'),
    NO_SHOW: t('appointments.noShow'),
  };
  return map[status] || status;
}

function getStatusKey(status: string): string {
  const map: Record<string, string> = {
    PENDING: 'pending',
    CONFIRMED: 'confirmed',
    COMPLETED: 'completed',
    CANCELLED: 'cancelled',
    NO_SHOW: 'noShow',
  };
  return map[status] || 'inactive';
}

function onSort(column: string) {
  if (sortBy.value === column) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = column;
    sortDir.value = 'asc';
  }
}

function onPageChange(page: number) {
  currentPage.value = page;
}

function onRowClick(row: Record<string, unknown>) {
  selectedAppointment.value = row as unknown as Appointment;
  showDetailModal.value = true;
}

function onAppointmentClick(apt: Appointment) {
  selectedAppointment.value = apt;
  showDetailModal.value = true;
}

function resetFilters() {
  searchQuery.value = '';
  statusFilter.value = '';
  employeeFilter.value = '';
  store.resetFilters();
}
</script>

<template>
  <div>
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-4">
      <BSearchBar
        v-model="searchQuery"
        :placeholder="t('common.search') + '...'"
        class="flex-1 max-w-md"
      />

      <div class="flex items-center gap-2 flex-wrap">
        <BSelect
          v-model="statusFilter"
          :options="statusOptions"
          class="w-40"
        />
        <BSelect
          v-model="employeeFilter"
          :options="employeeOptions"
          class="w-44"
        />
        <button
          v-if="searchQuery || statusFilter || employeeFilter"
          :class="BUTTON_STYLES.ghost"
          @click="resetFilters"
        >
          {{ t('common.reset') }}
        </button>
      </div>
    </div>

    <!-- Mobile: Card Layout grouped by date -->
    <div v-if="isMobile">
      <BEmptyState
        v-if="sortedAppointments.length === 0"
        :title="t('common.noResults')"
        :description="t('common.noResultsMessage')"
        icon="calendar"
        :action-label="t('appointments.newAppointment')"
        @action="showCreateModal = true"
      />

      <div v-else class="space-y-6">
        <div v-for="group in groupedAppointments" :key="group.date">
          <!-- Date Header -->
          <div class="flex items-center gap-3 mb-3">
            <h3 class="text-sm font-bold text-slate-900">{{ group.label }}</h3>
            <BBadge variant="default">{{ group.appointments.length }}</BBadge>
            <div class="flex-1 h-px bg-slate-200" />
          </div>
          <!-- Cards -->
          <div class="space-y-2">
            <AppointmentCard
              v-for="apt in group.appointments"
              :key="apt.id"
              :appointment="apt"
              @click="onAppointmentClick"
            />
          </div>
        </div>
      </div>

      <!-- Mobile Pagination -->
      <div
        v-if="totalItems > perPage"
        class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200"
      >
        <span class="text-sm text-slate-500">
          {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, totalItems) }} {{ t('common.of') }} {{ totalItems }}
        </span>
        <div class="flex items-center gap-1">
          <BButton
            variant="secondary"
            size="sm"
            :disabled="currentPage <= 1"
            @click="currentPage--"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </BButton>
          <span class="px-3 text-sm font-medium text-slate-700">{{ currentPage }}</span>
          <BButton
            variant="secondary"
            size="sm"
            :disabled="currentPage * perPage >= totalItems"
            @click="currentPage++"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </BButton>
        </div>
      </div>
    </div>

    <!-- Desktop: Table with date group headers -->
    <div v-else>
      <div v-if="sortedAppointments.length === 0">
        <BEmptyState
          :title="t('common.noResults')"
          :description="t('common.noResultsMessage')"
          icon="calendar"
          :action-label="t('appointments.newAppointment')"
          @action="showCreateModal = true"
        />
      </div>

      <div v-else class="space-y-4">
        <template v-for="group in groupedAppointments" :key="group.date">
          <!-- Date Group Header -->
          <div class="flex items-center gap-3">
            <h3 class="text-sm font-bold text-slate-900">{{ group.label }}</h3>
            <BBadge variant="default">{{ group.appointments.length }}</BBadge>
            <div class="flex-1 h-px bg-slate-200" />
          </div>

          <!-- Table for this date group -->
          <div :class="TABLE_STYLES.container">
            <div :class="TABLE_STYLES.scrollContainer">
              <table :class="TABLE_STYLES.table">
                <thead :class="TABLE_STYLES.thead">
                  <tr>
                    <th
                      v-for="col in columns"
                      :key="col.key"
                      :class="[
                        col.sortable ? TABLE_STYLES.thSortable : TABLE_STYLES.th,
                        col.align === 'right' ? 'text-right' : 'text-left',
                      ]"
                      :style="col.width ? { width: col.width } : undefined"
                      @click="col.sortable && onSort(col.key)"
                    >
                      <span class="flex items-center gap-1">
                        {{ col.label }}
                        <svg
                          v-if="col.sortable"
                          class="w-3 h-3 opacity-50"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            v-if="sortBy === col.key && sortDir === 'asc'"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 15l7-7 7 7"
                          />
                          <path
                            v-else-if="sortBy === col.key && sortDir === 'desc'"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                          />
                          <path
                            v-else
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                          />
                        </svg>
                      </span>
                    </th>
                  </tr>
                </thead>
                <tbody :class="TABLE_STYLES.tbody">
                  <tr
                    v-for="apt in group.appointments"
                    :key="apt.id"
                    :class="TABLE_STYLES.trClickable"
                    @click="onAppointmentClick(apt)"
                  >
                    <!-- Time -->
                    <td :class="TABLE_STYLES.tdBold">
                      {{ apt.startTime }} - {{ apt.endTime }}
                    </td>
                    <!-- Service -->
                    <td :class="TABLE_STYLES.td">
                      <div class="text-sm font-medium text-slate-900">{{ apt.serviceName }}</div>
                      <div class="text-xs text-slate-500">{{ apt.duration }} min</div>
                    </td>
                    <!-- Customer -->
                    <td :class="TABLE_STYLES.td">
                      {{ apt.customerName }}
                    </td>
                    <!-- Employee -->
                    <td :class="TABLE_STYLES.td">
                      {{ apt.employeeName }}
                    </td>
                    <!-- Status -->
                    <td :class="TABLE_STYLES.td">
                      <BBadge :status="getStatusKey(apt.status)" dot>
                        {{ getStatusLabel(apt.status) }}
                      </BBadge>
                    </td>
                    <!-- Price -->
                    <td :class="[TABLE_STYLES.td, 'text-right']">
                      <span class="font-medium">{{ appStore.formatPrice(apt.priceMinor) }}</span>
                    </td>
                    <!-- Actions -->
                    <td :class="TABLE_STYLES.td" @click.stop>
                      <button :class="BUTTON_STYLES.icon" @click="onAppointmentClick(apt)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <!-- Pagination -->
        <div
          v-if="totalItems > perPage"
          :class="[TABLE_STYLES.pagination, CARD_STYLES.base, 'mt-2']"
        >
          <span :class="TABLE_STYLES.paginationInfo">
            {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, totalItems) }} {{ t('common.of') }} {{ totalItems }}
          </span>
          <div :class="TABLE_STYLES.paginationButtons">
            <button
              :class="[TABLE_STYLES.paginationButton, TABLE_STYLES.paginationInactive]"
              :disabled="currentPage <= 1"
              @click="currentPage--"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              :class="[
                TABLE_STYLES.paginationButton,
                TABLE_STYLES.paginationActive,
              ]"
            >
              {{ currentPage }}
            </button>
            <button
              :class="[TABLE_STYLES.paginationButton, TABLE_STYLES.paginationInactive]"
              :disabled="currentPage * perPage >= totalItems"
              @click="currentPage++"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <BModal
      v-model="showDetailModal"
      :title="selectedAppointment?.serviceName || ''"
      size="md"
      @close="selectedAppointment = null"
    >
      <div v-if="selectedAppointment" class="space-y-4">
        <!-- Status -->
        <div class="flex items-center justify-between">
          <BBadge :status="getStatusKey(selectedAppointment.status)" dot>
            {{ getStatusLabel(selectedAppointment.status) }}
          </BBadge>
          <span class="text-lg font-bold text-slate-900">
            {{ appStore.formatPrice(selectedAppointment.priceMinor) }}
          </span>
        </div>

        <!-- Details -->
        <div :class="[CARD_STYLES.flat, 'divide-y divide-slate-100']">
          <div class="p-3 flex items-center gap-3">
            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <div class="text-sm font-medium text-slate-900">
                {{ selectedAppointment.startTime }} - {{ selectedAppointment.endTime }}
              </div>
              <div class="text-xs text-slate-500">{{ selectedAppointment.duration }} min</div>
            </div>
          </div>
          <div class="p-3 flex items-center gap-3">
            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <div class="text-sm text-slate-900">
              {{ new Date(selectedAppointment.date + 'T00:00:00').toLocaleDateString('de-CH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
            </div>
          </div>
          <div class="p-3 flex items-center gap-3">
            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <div class="text-sm text-slate-900">{{ selectedAppointment.customerName }}</div>
          </div>
          <div class="p-3 flex items-center gap-3">
            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <div class="text-sm text-slate-900">{{ selectedAppointment.employeeName }}</div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="selectedAppointment.notes" class="bg-slate-50 rounded-lg p-3">
          <div class="text-xs font-medium text-slate-500 mb-1">{{ t('appointments.notes') }}</div>
          <div class="text-sm text-slate-700">{{ selectedAppointment.notes }}</div>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center gap-2">
          <BButton
            v-if="selectedAppointment?.status === 'PENDING'"
            variant="primary"
            @click="store.updateStatus(selectedAppointment!.id, 'CONFIRMED'); showDetailModal = false"
          >
            {{ t('common.confirm') }}
          </BButton>
          <BButton
            v-if="selectedAppointment?.status !== 'CANCELLED' && selectedAppointment?.status !== 'COMPLETED'"
            variant="danger"
            @click="store.updateStatus(selectedAppointment!.id, 'CANCELLED'); showDetailModal = false"
          >
            {{ t('common.cancel') }}
          </BButton>
          <BButton variant="secondary" @click="showDetailModal = false">
            {{ t('common.close') }}
          </BButton>
        </div>
      </template>
    </BModal>

    <!-- Create Modal -->
    <AppointmentModal
      v-model="showCreateModal"
      @created="showCreateModal = false"
    />
  </div>
</template>
