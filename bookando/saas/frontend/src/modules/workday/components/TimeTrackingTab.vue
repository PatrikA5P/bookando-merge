<script setup lang="ts">
/**
 * TimeTrackingTab — Zeiterfassung
 *
 * Features:
 * - Wochenübersicht: Mo-So mit Stunden pro Tag, Pausen, Nettostunden
 * - Team-Übersicht: Karten pro Mitarbeiter mit Status
 * - ArG-Compliance-Warnungen (rote Badges bei Verstössen)
 * - Statistik-Karten: Total Stunden, Pausenzeit, Überstunden, Saldo
 * - Navigation zwischen Wochen
 */
import { computed } from 'vue';
import { CARD_STYLES, BADGE_STYLES, TABLE_STYLES, GRID_STYLES, BUTTON_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useWorkdayStore } from '@/stores/workday';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BTable from '@/components/ui/BTable.vue';

const { t } = useI18n();
const { isMobile, isDesktop } = useBreakpoint();
const store = useWorkdayStore();

const WEEKDAY_LABELS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

const weekLabel = computed(() => {
  const dates = store.weekDates;
  if (dates.length < 7) return '';
  const start = new Date(dates[0]);
  const end = new Date(dates[6]);
  const fmt = (d: Date) => `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}`;
  return `${fmt(start)} - ${fmt(end)}.${end.getFullYear()}`;
});

// Statistics
const statsCards = computed(() => [
  {
    label: t('workday.totalHours'),
    value: `${store.weeklyHours.toFixed(1)}h`,
    icon: 'clock',
    color: 'text-amber-600',
    bg: 'bg-amber-50',
  },
  {
    label: t('workday.breakDuration'),
    value: `${store.weeklyBreakMinutes}min`,
    icon: 'pause',
    color: 'text-blue-600',
    bg: 'bg-blue-50',
  },
  {
    label: t('workday.overtime'),
    value: `${store.overtimeHours.toFixed(1)}h`,
    icon: 'trending-up',
    color: store.overtimeHours > 0 ? 'text-red-600' : 'text-emerald-600',
    bg: store.overtimeHours > 0 ? 'bg-red-50' : 'bg-emerald-50',
  },
  {
    label: t('workday.balance'),
    value: `${(store.weeklyHours - 42.5).toFixed(1)}h`,
    icon: 'scale',
    color: store.weeklyHours - 42.5 >= 0 ? 'text-emerald-600' : 'text-amber-600',
    bg: store.weeklyHours - 42.5 >= 0 ? 'bg-emerald-50' : 'bg-amber-50',
  },
]);

// ArG violations for current week
const violations = computed(() => store.argViolations);

// Team status
const teamStatus = computed(() => {
  return store.EMPLOYEES.map(emp => {
    const todayEntry = store.todayEntries.find(e => e.employeeId === emp.id);
    const weeklyHours = store.weeklyHoursPerEmployee[emp.id] || 0;
    const empViolations = violations.value.filter(v => v.entryId.includes(emp.id) || v.entryId === emp.id);

    let status: 'working' | 'break' | 'not-started' | 'finished' | 'sick' = 'not-started';
    let statusLabel = t('workday.clockIn');
    let statusBadge: 'default' | 'success' | 'warning' | 'danger' | 'info' = 'default';

    if (todayEntry?.type === 'SICK') {
      status = 'sick';
      statusLabel = t('workday.absenceTypes.sick');
      statusBadge = 'danger';
    } else if (todayEntry?.clockOut) {
      status = 'finished';
      statusLabel = t('common.completed');
      statusBadge = 'info';
    } else if (todayEntry?.clockIn && todayEntry.clockIn !== '00:00') {
      status = 'working';
      statusLabel = t('common.active');
      statusBadge = 'success';
    }

    return {
      ...emp,
      status,
      statusLabel,
      statusBadge,
      weeklyHours,
      todayEntry,
      hasViolations: empViolations.length > 0,
      violationCount: empViolations.length,
    };
  });
});

// Table data for weekly overview
const weekTableColumns = computed(() => {
  const cols: { key: string; label: string; width?: string; align?: string }[] = [
    { key: 'day', label: t('common.today'), width: '100px' },
  ];
  if (!isMobile.value) {
    cols.push(
      { key: 'clockIn', label: t('workday.clockIn'), width: '90px' },
      { key: 'clockOut', label: t('workday.clockOut'), width: '90px' },
    );
  }
  cols.push(
    { key: 'breakMinutes', label: t('workday.breakDuration'), width: '90px', align: 'center' },
    { key: 'netHours', label: t('workday.netHours'), width: '90px', align: 'right' },
  );
  return cols;
});

const weekTableData = computed(() => {
  return store.dailyHours.map((day, index) => {
    const entry = store.weekEntries.find(
      e => e.date === day.date && e.employeeId === store.currentEmployeeId,
    );
    return {
      id: day.date,
      day: `${WEEKDAY_LABELS[index]}, ${new Date(day.date).getDate()}.${new Date(day.date).getMonth() + 1}.`,
      clockIn: entry?.clockIn || '—',
      clockOut: entry?.clockOut || '—',
      breakMinutes: day.breakMinutes > 0 ? `${day.breakMinutes}min` : '—',
      netHours: day.netHours > 0 ? `${day.netHours.toFixed(1)}h` : '—',
      _raw: day,
    };
  });
});
</script>

<template>
  <div class="space-y-6">
    <!-- Statistics Cards -->
    <div :class="GRID_STYLES.cols4Dense">
      <div
        v-for="stat in statsCards"
        :key="stat.label"
        :class="CARD_STYLES.stat"
      >
        <div class="flex items-center gap-3">
          <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', stat.bg]">
            <!-- Clock icon -->
            <svg v-if="stat.icon === 'clock'" :class="['w-5 h-5', stat.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <!-- Pause icon -->
            <svg v-else-if="stat.icon === 'pause'" :class="['w-5 h-5', stat.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <!-- Trending up icon -->
            <svg v-else-if="stat.icon === 'trending-up'" :class="['w-5 h-5', stat.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <!-- Scale icon -->
            <svg v-else :class="['w-5 h-5', stat.color]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
            </svg>
          </div>
          <div>
            <p class="text-xs text-slate-500">{{ stat.label }}</p>
            <p class="text-lg font-bold text-slate-900">{{ stat.value }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ArG Violations -->
    <div
      v-if="violations.length > 0"
      :class="[CARD_STYLES.base, 'border-red-200 bg-red-50 p-4']"
    >
      <div class="flex items-center gap-2 mb-3">
        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <h3 class="text-sm font-bold text-red-800">ArG-Compliance</h3>
        <BBadge variant="danger">{{ violations.length }}</BBadge>
      </div>
      <ul class="space-y-1">
        <li
          v-for="v in violations"
          :key="v.entryId + v.type"
          class="flex items-start gap-2 text-sm text-red-700"
        >
          <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span>{{ v.message }}</span>
        </li>
      </ul>
    </div>

    <!-- Week navigation + Table -->
    <div :class="CARD_STYLES.base">
      <div :class="[CARD_STYLES.headerCompact, 'flex items-center justify-between']">
        <h3 class="text-sm font-semibold text-slate-900">{{ t('workday.weeklyOverview') }}</h3>
        <div class="flex items-center gap-2">
          <BButton variant="ghost" size="sm" @click="store.navigateWeek('prev')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </BButton>
          <button
            class="text-xs font-medium text-slate-600 hover:text-slate-900 px-2 py-1 rounded hover:bg-slate-100 transition-colors"
            @click="store.goToCurrentWeek()"
          >
            {{ weekLabel }}
          </button>
          <BButton variant="ghost" size="sm" @click="store.navigateWeek('next')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </BButton>
        </div>
      </div>

      <!-- Weekly hours table -->
      <div :class="TABLE_STYLES.scrollContainer">
        <table :class="TABLE_STYLES.table">
          <thead :class="TABLE_STYLES.thead">
            <tr>
              <th :class="TABLE_STYLES.th">{{ t('common.today') }}</th>
              <th v-if="!isMobile" :class="TABLE_STYLES.th">{{ t('workday.clockIn') }}</th>
              <th v-if="!isMobile" :class="TABLE_STYLES.th">{{ t('workday.clockOut') }}</th>
              <th :class="[TABLE_STYLES.th, 'text-center']">{{ t('workday.breakDuration') }}</th>
              <th :class="[TABLE_STYLES.th, 'text-right']">{{ t('workday.netHours') }}</th>
            </tr>
          </thead>
          <tbody :class="TABLE_STYLES.tbody">
            <tr
              v-for="(row, index) in weekTableData"
              :key="row.id"
              :class="[
                TABLE_STYLES.tr,
                row.id === store.todayStr ? 'bg-amber-50/50' : '',
              ]"
            >
              <td :class="TABLE_STYLES.tdBold">
                <span class="flex items-center gap-2">
                  {{ row.day }}
                  <BBadge v-if="row.id === store.todayStr" variant="warning">
                    {{ t('common.today') }}
                  </BBadge>
                </span>
              </td>
              <td v-if="!isMobile" :class="TABLE_STYLES.td">{{ row.clockIn }}</td>
              <td v-if="!isMobile" :class="TABLE_STYLES.td">{{ row.clockOut }}</td>
              <td :class="[TABLE_STYLES.td, 'text-center']">{{ row.breakMinutes }}</td>
              <td :class="[TABLE_STYLES.td, 'text-right font-medium']">{{ row.netHours }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bg-slate-50 border-t-2 border-slate-200">
              <td :class="TABLE_STYLES.tdBold" :colspan="isMobile ? 1 : 3">Total</td>
              <td :class="[TABLE_STYLES.td, 'text-center font-medium']">
                {{ store.weeklyBreakMinutes }}min
              </td>
              <td :class="[TABLE_STYLES.td, 'text-right font-bold text-amber-700']">
                {{ store.weeklyHours.toFixed(1) }}h
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Team Overview -->
    <div>
      <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ t('workday.teamOverview') }}</h3>
      <div :class="GRID_STYLES.cols2Dense">
        <div
          v-for="member in teamStatus"
          :key="member.id"
          :class="[CARD_STYLES.base, 'p-4']"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <!-- Avatar initials -->
              <div
                class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-semibold text-sm uppercase"
              >
                {{ member.name.split(' ').map(n => n[0]).join('') }}
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-900">{{ member.name }}</p>
                <div class="flex items-center gap-2 mt-0.5">
                  <BBadge :variant="member.statusBadge">
                    {{ member.statusLabel }}
                  </BBadge>
                  <BBadge
                    v-if="member.hasViolations"
                    variant="danger"
                  >
                    {{ member.violationCount }} ArG
                  </BBadge>
                </div>
              </div>
            </div>
            <div class="text-right">
              <p class="text-lg font-bold text-slate-900">{{ member.weeklyHours.toFixed(1) }}h</p>
              <p class="text-xs text-slate-500">{{ t('common.thisWeek') }}</p>
            </div>
          </div>

          <!-- Progress bar for weekly hours -->
          <div class="mt-3">
            <div class="flex justify-between text-xs text-slate-500 mb-1">
              <span>{{ t('workday.weeklyOverview') }}</span>
              <span>{{ member.weeklyHours.toFixed(1) }} / 42.5h</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2">
              <div
                class="h-2 rounded-full transition-all duration-500"
                :class="member.weeklyHours > 45 ? 'bg-red-500' : member.weeklyHours > 42.5 ? 'bg-amber-500' : 'bg-emerald-500'"
                :style="{ width: `${Math.min(100, (member.weeklyHours / 45) * 100)}%` }"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
