<script setup lang="ts">
/**
 * CalendarView — Kalenderansicht fuer Termine
 *
 * Features:
 * - Tag/Woche/Monat-Umschalter
 * - Wochenansicht: 7-Spalten-Grid mit Stunden 07:00-20:00
 * - Tagesansicht: Einzelspalte mit Zeitslots
 * - Monatsansicht: Kalender-Grid mit Terminanzahl pro Tag
 * - Datumsnavigation (zurueck/weiter/heute)
 * - Aktuelle-Zeit-Indikator (rote Linie)
 * - Responsive: Mobile nur Tagesansicht als Karten
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
  BUTTON_STYLES,
  CARD_STYLES,
  BADGE_STYLES,
  TAB_STYLES,
  GRID_STYLES,
} from '@/design';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import AppointmentCard from '../components/AppointmentCard.vue';
import AppointmentModal from '../components/AppointmentModal.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useAppointmentsStore } from '@/stores/appointments';
import type { Appointment } from '@/stores/appointments';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const store = useAppointmentsStore();

// View state
const viewMode = ref<'day' | 'week' | 'month'>('week');
const currentDate = ref(new Date());
const selectedAppointment = ref<Appointment | null>(null);
const showDetailModal = ref(false);
const showCreateModal = ref(false);

// Current time for indicator
const currentTime = ref(new Date());
let timeInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  timeInterval = setInterval(() => {
    currentTime.value = new Date();
  }, 60000);
});

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval);
});

// Force day view on mobile
const effectiveViewMode = computed(() => {
  if (isMobile.value) return 'day';
  return viewMode.value;
});

// Hours for day/week grid
const HOURS = Array.from({ length: 14 }, (_, i) => i + 7); // 07:00 - 20:00

// Date helpers
function toDateStr(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function getWeekStart(date: Date): Date {
  const d = new Date(date);
  const day = d.getDay();
  // Monday as start of week (Swiss convention)
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  d.setDate(diff);
  d.setHours(0, 0, 0, 0);
  return d;
}

function getMonthStart(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}

// Week dates
const weekDates = computed(() => {
  const start = getWeekStart(currentDate.value);
  const dates: Date[] = [];
  for (let i = 0; i < 7; i++) {
    const d = new Date(start);
    d.setDate(d.getDate() + i);
    dates.push(d);
  }
  return dates;
});

// Month calendar grid
const monthGrid = computed(() => {
  const start = getMonthStart(currentDate.value);
  const year = start.getFullYear();
  const month = start.getMonth();

  // First day of month, adjusted for Monday start
  let firstDayOffset = start.getDay() - 1;
  if (firstDayOffset < 0) firstDayOffset = 6;

  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const rows: { date: Date; isCurrentMonth: boolean; dateStr: string; count: number }[][] = [];
  let row: typeof rows[0] = [];

  // Previous month padding
  for (let i = firstDayOffset - 1; i >= 0; i--) {
    const d = new Date(year, month, -i);
    const ds = toDateStr(d);
    row.push({ date: d, isCurrentMonth: false, dateStr: ds, count: store.getAppointmentCountForDate(ds) });
  }

  // Current month
  for (let day = 1; day <= daysInMonth; day++) {
    const d = new Date(year, month, day);
    const ds = toDateStr(d);
    row.push({ date: d, isCurrentMonth: true, dateStr: ds, count: store.getAppointmentCountForDate(ds) });
    if (row.length === 7) {
      rows.push(row);
      row = [];
    }
  }

  // Next month padding
  if (row.length > 0) {
    let nextDay = 1;
    while (row.length < 7) {
      const d = new Date(year, month + 1, nextDay++);
      const ds = toDateStr(d);
      row.push({ date: d, isCurrentMonth: false, dateStr: ds, count: store.getAppointmentCountForDate(ds) });
    }
    rows.push(row);
  }

  return rows;
});

// Navigation
const headerTitle = computed(() => {
  const d = currentDate.value;
  if (effectiveViewMode.value === 'day') {
    return d.toLocaleDateString('de-CH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  }
  if (effectiveViewMode.value === 'week') {
    const start = weekDates.value[0];
    const end = weekDates.value[6];
    const startStr = start.toLocaleDateString('de-CH', { day: 'numeric', month: 'short' });
    const endStr = end.toLocaleDateString('de-CH', { day: 'numeric', month: 'short', year: 'numeric' });
    return `${startStr} - ${endStr}`;
  }
  return d.toLocaleDateString('de-CH', { month: 'long', year: 'numeric' });
});

function navigate(direction: -1 | 1) {
  const d = new Date(currentDate.value);
  if (effectiveViewMode.value === 'day') {
    d.setDate(d.getDate() + direction);
  } else if (effectiveViewMode.value === 'week') {
    d.setDate(d.getDate() + direction * 7);
  } else {
    d.setMonth(d.getMonth() + direction);
  }
  currentDate.value = d;
}

function goToToday() {
  currentDate.value = new Date();
}

function selectDay(date: Date) {
  currentDate.value = date;
  viewMode.value = 'day';
}

// Current time indicator position (percentage from top of grid)
const currentTimePosition = computed(() => {
  const now = currentTime.value;
  const hours = now.getHours();
  const minutes = now.getMinutes();
  const totalMinutes = hours * 60 + minutes;
  const startMinutes = 7 * 60; // 07:00
  const endMinutes = 20 * 60; // 20:00
  const range = endMinutes - startMinutes;
  const position = ((totalMinutes - startMinutes) / range) * 100;
  return Math.max(0, Math.min(100, position));
});

const showTimeIndicator = computed(() => {
  const now = currentTime.value;
  const hours = now.getHours();
  return hours >= 7 && hours < 20;
});

const isToday = computed(() => {
  return toDateStr(currentDate.value) === toDateStr(new Date());
});

// Appointment positioning in grid
function getAppointmentStyle(appointment: Appointment) {
  const [startH, startM] = appointment.startTime.split(':').map(Number);
  const [endH, endM] = appointment.endTime.split(':').map(Number);
  const startMinutes = startH * 60 + startM;
  const endMinutes = endH * 60 + endM;
  const gridStartMinutes = 7 * 60;
  const hourHeight = 60; // px per hour

  const top = ((startMinutes - gridStartMinutes) / 60) * hourHeight;
  const height = Math.max(((endMinutes - startMinutes) / 60) * hourHeight - 2, 20);

  return {
    top: `${top}px`,
    height: `${height}px`,
  };
}

function getStatusColor(status: string): string {
  const map: Record<string, string> = {
    PENDING: 'bg-amber-100 border-amber-300 text-amber-800',
    CONFIRMED: 'bg-emerald-100 border-emerald-300 text-emerald-800',
    COMPLETED: 'bg-blue-100 border-blue-300 text-blue-800',
    CANCELLED: 'bg-slate-100 border-slate-300 text-slate-500',
    NO_SHOW: 'bg-rose-100 border-rose-300 text-rose-800',
  };
  return map[status] || 'bg-slate-100 border-slate-300 text-slate-600';
}

// Day appointments
const dayAppointments = computed(() => {
  return store.getByDate(toDateStr(currentDate.value));
});

// Week day names
const weekDayNames = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

function isTodayDate(date: Date): boolean {
  return toDateStr(date) === toDateStr(new Date());
}

function getAppointmentsForDate(date: Date): Appointment[] {
  return store.getByDate(toDateStr(date));
}

function onAppointmentClick(appointment: Appointment) {
  selectedAppointment.value = appointment;
  showDetailModal.value = true;
}

function onAppointmentCreated() {
  showCreateModal.value = false;
}
</script>

<template>
  <div>
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
      <!-- Navigation -->
      <div class="flex items-center gap-2">
        <button :class="BUTTON_STYLES.icon" @click="navigate(-1)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h2 class="text-sm font-semibold text-slate-900 min-w-[200px] text-center">
          {{ headerTitle }}
        </h2>
        <button :class="BUTTON_STYLES.icon" @click="navigate(1)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        <button
          :class="[
            'ml-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors',
            isToday
              ? 'bg-brand-100 text-brand-700'
              : 'bg-brand-50 text-brand-600 hover:bg-brand-100',
          ]"
          @click="goToToday"
        >
          {{ t('common.today') }}
        </button>
      </div>

      <!-- View Mode Switcher (hidden on mobile) -->
      <div v-if="!isMobile" class="flex bg-slate-100 rounded-lg p-0.5">
        <button
          v-for="mode in (['day', 'week', 'month'] as const)"
          :key="mode"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md transition-all',
            viewMode === mode
              ? 'bg-white text-slate-900 shadow-sm'
              : 'text-slate-500 hover:text-slate-700',
          ]"
          @click="viewMode = mode"
        >
          {{ t(`appointments.${mode}`) }}
        </button>
      </div>
    </div>

    <!-- DAY VIEW -->
    <template v-if="effectiveViewMode === 'day'">
      <!-- Mobile: Card Layout -->
      <div v-if="isMobile" class="space-y-3">
        <BEmptyState
          v-if="dayAppointments.length === 0"
          :title="t('common.noResults')"
          :description="t('common.noResultsMessage')"
          icon="calendar"
          :action-label="t('appointments.newAppointment')"
          @action="showCreateModal = true"
        />
        <AppointmentCard
          v-for="apt in dayAppointments"
          :key="apt.id"
          :appointment="apt"
          @click="onAppointmentClick"
        />
      </div>

      <!-- Desktop: Time Grid -->
      <div v-else :class="[CARD_STYLES.base, 'overflow-hidden']">
        <div class="relative" style="height: 840px;"> <!-- 14 hours * 60px -->
          <!-- Time Grid Lines -->
          <div
            v-for="hour in HOURS"
            :key="hour"
            class="absolute left-0 right-0 border-t border-slate-100"
            :style="{ top: `${(hour - 7) * 60}px` }"
          >
            <span class="absolute -top-2.5 left-2 text-[10px] font-medium text-slate-400 bg-white px-1">
              {{ String(hour).padStart(2, '0') }}:00
            </span>
          </div>

          <!-- Current Time Indicator -->
          <div
            v-if="showTimeIndicator && isToday"
            class="absolute left-0 right-0 z-20 pointer-events-none"
            :style="{ top: `${currentTimePosition}%` }"
          >
            <div class="flex items-center">
              <div class="w-2 h-2 rounded-full bg-red-500 -ml-1" />
              <div class="flex-1 h-[2px] bg-red-500" />
            </div>
          </div>

          <!-- Appointments -->
          <div class="ml-14 mr-2 relative">
            <button
              v-for="apt in dayAppointments"
              :key="apt.id"
              class="absolute left-0 right-0 rounded-lg border px-2 py-1 cursor-pointer transition-shadow hover:shadow-md overflow-hidden text-left"
              :class="getStatusColor(apt.status)"
              :style="getAppointmentStyle(apt)"
              @click="onAppointmentClick(apt)"
            >
              <div class="text-[11px] font-bold truncate">{{ apt.startTime }} {{ apt.serviceName }}</div>
              <div class="text-[10px] opacity-75 truncate">{{ apt.customerName }}</div>
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- WEEK VIEW -->
    <template v-if="effectiveViewMode === 'week'">
      <div :class="[CARD_STYLES.base, 'overflow-hidden']">
        <!-- Week Header -->
        <div class="grid grid-cols-[56px_repeat(7,1fr)] border-b border-slate-200">
          <div class="p-2" />
          <div
            v-for="(date, i) in weekDates"
            :key="i"
            :class="[
              'p-2 text-center border-l border-slate-100',
              isTodayDate(date) ? 'bg-brand-50' : '',
            ]"
          >
            <div class="text-[10px] font-medium text-slate-500 uppercase">{{ weekDayNames[i] }}</div>
            <div
              :class="[
                'text-sm font-bold mt-0.5',
                isTodayDate(date) ? 'text-brand-600' : 'text-slate-900',
              ]"
            >
              {{ date.getDate() }}
            </div>
          </div>
        </div>

        <!-- Week Grid -->
        <div class="relative overflow-y-auto" style="height: 660px;">
          <div class="grid grid-cols-[56px_repeat(7,1fr)]" style="height: 840px;">
            <!-- Time Labels -->
            <div class="relative">
              <div
                v-for="hour in HOURS"
                :key="hour"
                class="absolute left-0 right-0"
                :style="{ top: `${(hour - 7) * 60}px` }"
              >
                <span class="text-[10px] font-medium text-slate-400 px-1 relative -top-2">
                  {{ String(hour).padStart(2, '0') }}:00
                </span>
              </div>
            </div>

            <!-- Day Columns -->
            <div
              v-for="(date, dayIndex) in weekDates"
              :key="dayIndex"
              class="relative border-l border-slate-100"
              :class="isTodayDate(date) ? 'bg-brand-50/30' : ''"
            >
              <!-- Hour grid lines -->
              <div
                v-for="hour in HOURS"
                :key="hour"
                class="absolute left-0 right-0 border-t border-slate-100"
                :style="{ top: `${(hour - 7) * 60}px` }"
              />

              <!-- Appointments -->
              <button
                v-for="apt in getAppointmentsForDate(date)"
                :key="apt.id"
                class="absolute left-0.5 right-0.5 rounded border px-1 py-0.5 cursor-pointer transition-shadow hover:shadow-md overflow-hidden text-left z-10"
                :class="getStatusColor(apt.status)"
                :style="getAppointmentStyle(apt)"
                @click="onAppointmentClick(apt)"
              >
                <div class="text-[10px] font-bold truncate">{{ apt.startTime }}</div>
                <div class="text-[9px] opacity-80 truncate">{{ apt.serviceName }}</div>
                <div class="text-[9px] opacity-60 truncate">{{ apt.customerName }}</div>
              </button>
            </div>
          </div>

          <!-- Current Time Indicator -->
          <div
            v-if="showTimeIndicator"
            class="absolute left-0 right-0 z-20 pointer-events-none"
            :style="{ top: `${currentTimePosition}%` }"
          >
            <div class="flex items-center ml-14">
              <div class="w-2 h-2 rounded-full bg-red-500 -ml-1" />
              <div class="flex-1 h-[2px] bg-red-500" />
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- MONTH VIEW -->
    <template v-if="effectiveViewMode === 'month'">
      <div :class="[CARD_STYLES.base, 'overflow-hidden']">
        <!-- Month Header -->
        <div class="grid grid-cols-7 border-b border-slate-200">
          <div
            v-for="dayName in weekDayNames"
            :key="dayName"
            class="p-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider"
          >
            {{ dayName }}
          </div>
        </div>

        <!-- Month Grid -->
        <div
          v-for="(row, rowIndex) in monthGrid"
          :key="rowIndex"
          class="grid grid-cols-7 border-b border-slate-100 last:border-b-0"
        >
          <button
            v-for="cell in row"
            :key="cell.dateStr"
            :class="[
              'p-2 min-h-[80px] text-left border-r border-slate-100 last:border-r-0 transition-colors hover:bg-slate-50',
              !cell.isCurrentMonth ? 'bg-slate-50/50' : '',
              isTodayDate(cell.date) ? 'bg-brand-50' : '',
            ]"
            @click="selectDay(cell.date)"
          >
            <div class="flex items-center justify-between">
              <span
                :class="[
                  'text-sm font-medium',
                  isTodayDate(cell.date)
                    ? 'text-brand-600 font-bold'
                    : cell.isCurrentMonth
                      ? 'text-slate-900'
                      : 'text-slate-400',
                ]"
              >
                {{ cell.date.getDate() }}
              </span>
              <BBadge
                v-if="cell.count > 0"
                :variant="isTodayDate(cell.date) ? 'brand' : 'default'"
              >
                {{ cell.count }}
              </BBadge>
            </div>
            <!-- Mini appointment list -->
            <div class="mt-1 space-y-0.5">
              <div
                v-for="apt in getAppointmentsForDate(cell.date).slice(0, 2)"
                :key="apt.id"
                :class="[
                  'text-[10px] truncate px-1 py-0.5 rounded',
                  getStatusColor(apt.status),
                ]"
              >
                {{ apt.startTime }} {{ apt.serviceName }}
              </div>
              <div
                v-if="getAppointmentsForDate(cell.date).length > 2"
                class="text-[10px] text-slate-400 px-1"
              >
                +{{ getAppointmentsForDate(cell.date).length - 2 }}
              </div>
            </div>
          </button>
        </div>
      </div>
    </template>

    <!-- Detail Modal for clicked appointment -->
    <BModal
      v-model="showDetailModal"
      :title="selectedAppointment?.serviceName || ''"
      size="md"
      @close="selectedAppointment = null"
    >
      <div v-if="selectedAppointment" class="space-y-4">
        <!-- Status -->
        <div class="flex items-center justify-between">
          <BBadge
            :status="selectedAppointment.status.toLowerCase().replace('_', '')"
            dot
          >
            {{ selectedAppointment.status === 'PENDING' ? t('common.pending') :
               selectedAppointment.status === 'CONFIRMED' ? t('appointments.confirmed') :
               selectedAppointment.status === 'COMPLETED' ? t('common.completed') :
               selectedAppointment.status === 'CANCELLED' ? t('common.cancelled') :
               t('appointments.noShow') }}
          </BBadge>
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
      @created="onAppointmentCreated"
    />
  </div>
</template>
