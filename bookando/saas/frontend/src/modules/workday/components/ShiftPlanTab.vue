<script setup lang="ts">
/**
 * ShiftPlanTab — Schichtplan
 *
 * Features:
 * - 7-Tage-Grid (Mo-So Spalten)
 * - Zeilen pro Mitarbeiter
 * - Farbcodierte Schichtzellen (Früh=Amber, Spät=Blau, Nacht=Indigo, Frei=Slate)
 * - Klick auf Zelle zum Ändern der Schicht
 * - Zusammenfassungszeile mit Abdeckung pro Tag
 */
import { ref, computed } from 'vue';
import { CARD_STYLES, TABLE_STYLES, BADGE_STYLES, BUTTON_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useWorkdayStore, type ShiftType } from '@/stores/workday';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BModal from '@/components/ui/BModal.vue';
import BSelect from '@/components/ui/BSelect.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const store = useWorkdayStore();

const WEEKDAY_LABELS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
const WEEKDAY_LABELS_LONG = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

// Shift type colors and labels
const SHIFT_CONFIG: Record<ShiftType, { bg: string; text: string; border: string; label: string; time: string }> = {
  EARLY: {
    bg: 'bg-amber-100',
    text: 'text-amber-800',
    border: 'border-amber-200',
    label: 'workday.shiftTypes.early',
    time: '06:00-14:00',
  },
  LATE: {
    bg: 'bg-blue-100',
    text: 'text-blue-800',
    border: 'border-blue-200',
    label: 'workday.shiftTypes.late',
    time: '14:00-22:00',
  },
  NIGHT: {
    bg: 'bg-indigo-100',
    text: 'text-indigo-800',
    border: 'border-indigo-200',
    label: 'workday.shiftTypes.night',
    time: '22:00-06:00',
  },
  OFF: {
    bg: 'bg-slate-50',
    text: 'text-slate-400',
    border: 'border-slate-200',
    label: 'workday.shiftTypes.off',
    time: '',
  },
};

// Edit modal state
const showEditModal = ref(false);
const editingShiftId = ref<string | null>(null);
const editingShiftType = ref<ShiftType>('EARLY');
const editingEmployeeName = ref('');
const editingDayLabel = ref('');

// Computed: shift grid organized by employee x day
const shiftGrid = computed(() => {
  return store.EMPLOYEES.map(emp => {
    const shifts = store.weekDates.map(date => {
      return store.shiftEntries.find(
        s => s.employeeId === emp.id && s.date === date,
      ) || null;
    });
    return { employee: emp, shifts };
  });
});

// Coverage summary per day
const coveragePerDay = computed(() => {
  return store.weekDates.map((date, dayIndex) => {
    const shifts = store.shiftEntries.filter(s => s.date === date && s.shiftType !== 'OFF');
    const early = shifts.filter(s => s.shiftType === 'EARLY').length;
    const late = shifts.filter(s => s.shiftType === 'LATE').length;
    const night = shifts.filter(s => s.shiftType === 'NIGHT').length;
    return { date, total: shifts.length, early, late, night };
  });
});

const shiftOptions = [
  { value: 'EARLY', label: '' },
  { value: 'LATE', label: '' },
  { value: 'NIGHT', label: '' },
  { value: 'OFF', label: '' },
];

// Update labels reactively
const shiftSelectOptions = computed(() => [
  { value: 'EARLY', label: t('workday.shiftTypes.early') + ' (06:00-14:00)' },
  { value: 'LATE', label: t('workday.shiftTypes.late') + ' (14:00-22:00)' },
  { value: 'NIGHT', label: t('workday.shiftTypes.night') + ' (22:00-06:00)' },
  { value: 'OFF', label: t('workday.shiftTypes.off') },
]);

function openEditShift(shiftId: string | null, employeeName: string, dayIndex: number, currentType: ShiftType) {
  if (!shiftId) return;
  editingShiftId.value = shiftId;
  editingShiftType.value = currentType;
  editingEmployeeName.value = employeeName;
  editingDayLabel.value = WEEKDAY_LABELS_LONG[dayIndex];
  showEditModal.value = true;
}

function saveShift() {
  if (!editingShiftId.value) return;
  store.updateShift(editingShiftId.value, editingShiftType.value);
  showEditModal.value = false;
  toast.success(t('common.savedSuccessfully'));
}

function getDateLabel(dateStr: string): string {
  const d = new Date(dateStr);
  return `${d.getDate()}.${d.getMonth() + 1}.`;
}
</script>

<template>
  <div class="space-y-6">
    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-3">
      <span class="text-xs font-medium text-slate-500 mr-1">Legende:</span>
      <span
        v-for="(config, type) in SHIFT_CONFIG"
        :key="type"
        :class="[
          'inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs font-medium border',
          config.bg,
          config.text,
          config.border,
        ]"
      >
        <span class="w-2 h-2 rounded-full" :class="config.bg.replace('100', '500').replace('50', '400')" />
        {{ t(config.label) }}
        <span v-if="config.time" class="text-[10px] opacity-70">{{ config.time }}</span>
      </span>
    </div>

    <!-- Shift Grid -->
    <div :class="CARD_STYLES.base">
      <div :class="TABLE_STYLES.scrollContainer">
        <table :class="TABLE_STYLES.table">
          <thead :class="TABLE_STYLES.thead">
            <tr>
              <th :class="[TABLE_STYLES.th, 'min-w-[140px]']">
                {{ t('employees.title') }}
              </th>
              <th
                v-for="(date, index) in store.weekDates"
                :key="date"
                :class="[
                  TABLE_STYLES.th,
                  'text-center min-w-[100px]',
                  date === store.todayStr ? 'bg-amber-50' : '',
                ]"
              >
                <div>{{ WEEKDAY_LABELS[index] }}</div>
                <div class="text-[10px] font-normal text-slate-400">{{ getDateLabel(date) }}</div>
              </th>
            </tr>
          </thead>
          <tbody :class="TABLE_STYLES.tbody">
            <tr
              v-for="row in shiftGrid"
              :key="row.employee.id"
              :class="TABLE_STYLES.tr"
            >
              <!-- Employee name -->
              <td :class="TABLE_STYLES.tdBold">
                <div class="flex items-center gap-2">
                  <div
                    class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-semibold text-xs uppercase"
                  >
                    {{ row.employee.name.split(' ').map((n: string) => n[0]).join('') }}
                  </div>
                  <span class="text-sm">{{ row.employee.name }}</span>
                </div>
              </td>
              <!-- Shift cells -->
              <td
                v-for="(shift, dayIndex) in row.shifts"
                :key="dayIndex"
                :class="[
                  'p-2 text-center',
                  store.weekDates[dayIndex] === store.todayStr ? 'bg-amber-50/50' : '',
                ]"
              >
                <button
                  v-if="shift"
                  :class="[
                    'w-full px-2 py-2 rounded-lg border text-xs font-medium transition-all duration-200 cursor-pointer',
                    'hover:shadow-md hover:scale-105',
                    SHIFT_CONFIG[shift.shiftType].bg,
                    SHIFT_CONFIG[shift.shiftType].text,
                    SHIFT_CONFIG[shift.shiftType].border,
                  ]"
                  @click="openEditShift(shift.id, row.employee.name, dayIndex, shift.shiftType)"
                >
                  <div>{{ t(SHIFT_CONFIG[shift.shiftType].label) }}</div>
                  <div v-if="SHIFT_CONFIG[shift.shiftType].time" class="text-[10px] opacity-70 mt-0.5">
                    {{ SHIFT_CONFIG[shift.shiftType].time }}
                  </div>
                </button>
                <span v-else class="text-xs text-slate-300">&mdash;</span>
              </td>
            </tr>
          </tbody>

          <!-- Coverage summary -->
          <tfoot>
            <tr class="bg-slate-50 border-t-2 border-slate-200">
              <td :class="[TABLE_STYLES.tdBold, 'text-xs']">
                <svg class="w-4 h-4 inline mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Abdeckung
              </td>
              <td
                v-for="(cov, index) in coveragePerDay"
                :key="index"
                class="p-2 text-center"
              >
                <div class="text-sm font-semibold" :class="cov.total >= 2 ? 'text-emerald-700' : cov.total === 1 ? 'text-amber-700' : 'text-red-600'">
                  {{ cov.total }} / {{ store.EMPLOYEES.length }}
                </div>
                <div v-if="cov.total > 0" class="flex justify-center gap-1 mt-1">
                  <span v-if="cov.early" class="w-4 h-1.5 rounded-full bg-amber-400" :title="`${cov.early} ${t('workday.shiftTypes.early')}`" />
                  <span v-if="cov.late" class="w-4 h-1.5 rounded-full bg-blue-400" :title="`${cov.late} ${t('workday.shiftTypes.late')}`" />
                  <span v-if="cov.night" class="w-4 h-1.5 rounded-full bg-indigo-400" :title="`${cov.night} ${t('workday.shiftTypes.night')}`" />
                </div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Edit Shift Modal -->
    <BModal
      v-model="showEditModal"
      :title="`${t('workday.shifts')} — ${editingEmployeeName}`"
      size="sm"
    >
      <div class="space-y-4">
        <p class="text-sm text-slate-600">
          {{ editingDayLabel }}
        </p>
        <BSelect
          v-model="editingShiftType"
          :label="t('workday.shifts')"
          :options="shiftSelectOptions"
        />

        <!-- Shift preview -->
        <div
          v-if="editingShiftType !== 'OFF'"
          :class="[
            'p-3 rounded-lg border text-center',
            SHIFT_CONFIG[editingShiftType].bg,
            SHIFT_CONFIG[editingShiftType].text,
            SHIFT_CONFIG[editingShiftType].border,
          ]"
        >
          <p class="text-sm font-semibold">{{ t(SHIFT_CONFIG[editingShiftType].label) }}</p>
          <p class="text-xs mt-1">{{ SHIFT_CONFIG[editingShiftType].time }}</p>
        </div>
      </div>

      <template #footer>
        <BButton variant="secondary" @click="showEditModal = false">
          {{ t('common.cancel') }}
        </BButton>
        <BButton variant="primary" @click="saveShift">
          {{ t('common.save') }}
        </BButton>
      </template>
    </BModal>
  </div>
</template>
