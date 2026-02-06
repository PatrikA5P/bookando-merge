<script setup lang="ts">
/**
 * AbsencesTab — Abwesenheiten
 *
 * Features:
 * - Antragsformular via BModal: Typ, Start/Ende, Tageberechnung, Notizen
 * - Kalender-artige Übersicht (wer ist wann abwesend)
 * - BTable mit Anträgen (Pending / Approved / Rejected)
 * - Genehmigen/Ablehnen-Buttons für Manager
 * - Ferientage-Saldo-Karten pro Mitarbeiter (Total, Verwendet, Verbleibend)
 */
import { ref, computed, watch } from 'vue';
import { CARD_STYLES, BADGE_STYLES, TABLE_STYLES, GRID_STYLES, BUTTON_STYLES, INPUT_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useWorkdayStore, type AbsenceType, type AbsenceStatus } from '@/stores/workday';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BModal from '@/components/ui/BModal.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTable from '@/components/ui/BTable.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const store = useWorkdayStore();

// Filter
const statusFilter = ref<AbsenceStatus | 'ALL'>('ALL');

// New request modal
const showNewRequest = ref(false);
const newRequest = ref({
  employeeId: 'emp-1',
  type: 'VACATION' as AbsenceType,
  startDate: '',
  endDate: '',
  notes: '',
});

// Computed days between dates
const calculatedDays = computed(() => {
  if (!newRequest.value.startDate || !newRequest.value.endDate) return 0;
  const start = new Date(newRequest.value.startDate);
  const end = new Date(newRequest.value.endDate);
  if (end < start) return 0;
  // Count weekdays only
  let days = 0;
  const current = new Date(start);
  while (current <= end) {
    const dayOfWeek = current.getDay();
    if (dayOfWeek !== 0 && dayOfWeek !== 6) days++;
    current.setDate(current.getDate() + 1);
  }
  return days;
});

// Absence type options
const absenceTypeOptions = computed(() => [
  { value: 'VACATION', label: t('workday.absenceTypes.vacation') },
  { value: 'SICK', label: t('workday.absenceTypes.sick') },
  { value: 'PERSONAL', label: t('workday.absenceTypes.personal') },
  { value: 'TRAINING', label: t('workday.absenceTypes.training') },
  { value: 'MATERNITY', label: t('workday.absenceTypes.maternity') },
  { value: 'MILITARY', label: t('workday.absenceTypes.military') },
]);

// Employee options for the form
const employeeOptions = computed(() =>
  store.EMPLOYEES.map(e => ({ value: e.id, label: e.name })),
);

// Filtered absences
const filteredAbsences = computed(() => {
  let list = [...store.absenceRequests];
  if (statusFilter.value !== 'ALL') {
    list = list.filter(a => a.status === statusFilter.value);
  }
  return list.sort((a, b) => new Date(b.startDate).getTime() - new Date(a.startDate).getTime());
});

// Table columns
const tableColumns = computed(() => {
  const cols: { key: string; label: string; width?: string; align?: string }[] = [
    { key: 'employeeName', label: t('employees.title'), width: '150px' },
    { key: 'type', label: 'Typ', width: '120px' },
    { key: 'startDate', label: 'Von', width: '100px' },
  ];
  if (!isMobile.value) {
    cols.push({ key: 'endDate', label: 'Bis', width: '100px' });
    cols.push({ key: 'days', label: 'Tage', width: '60px', align: 'center' });
  }
  cols.push(
    { key: 'status', label: 'Status', width: '100px' },
    { key: 'actions', label: '', width: '120px' },
  );
  return cols;
});

// Table data
const tableData = computed(() =>
  filteredAbsences.value.map(a => ({
    id: a.id,
    employeeName: a.employeeName,
    type: a.type,
    startDate: formatDateShort(a.startDate),
    endDate: formatDateShort(a.endDate),
    days: a.days,
    status: a.status,
    notes: a.notes,
    _raw: a,
  })),
);

// Badge variant mapping
function getStatusBadge(status: AbsenceStatus): 'warning' | 'success' | 'danger' {
  switch (status) {
    case 'PENDING': return 'warning';
    case 'APPROVED': return 'success';
    case 'REJECTED': return 'danger';
  }
}

function getStatusLabel(status: AbsenceStatus): string {
  switch (status) {
    case 'PENDING': return t('common.pending');
    case 'APPROVED': return t('common.approved');
    case 'REJECTED': return t('common.rejected');
  }
}

function getTypeLabel(type: AbsenceType): string {
  const map: Record<AbsenceType, string> = {
    VACATION: 'workday.absenceTypes.vacation',
    SICK: 'workday.absenceTypes.sick',
    PERSONAL: 'workday.absenceTypes.personal',
    TRAINING: 'workday.absenceTypes.training',
    MATERNITY: 'workday.absenceTypes.maternity',
    MILITARY: 'workday.absenceTypes.military',
  };
  return t(map[type]);
}

function getTypeBadgeVariant(type: AbsenceType): 'info' | 'danger' | 'default' | 'purple' | 'brand' | 'warning' {
  switch (type) {
    case 'VACATION': return 'info';
    case 'SICK': return 'danger';
    case 'PERSONAL': return 'default';
    case 'TRAINING': return 'purple';
    case 'MATERNITY': return 'brand';
    case 'MILITARY': return 'warning';
  }
}

function formatDateShort(dateStr: string): string {
  const d = new Date(dateStr);
  return `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}.${d.getFullYear()}`;
}

function openNewRequest() {
  newRequest.value = {
    employeeId: store.currentEmployeeId,
    type: 'VACATION',
    startDate: '',
    endDate: '',
    notes: '',
  };
  showNewRequest.value = true;
}

function submitRequest() {
  if (!newRequest.value.startDate || !newRequest.value.endDate || calculatedDays.value === 0) {
    toast.warning(t('common.required'));
    return;
  }

  const emp = store.EMPLOYEES.find(e => e.id === newRequest.value.employeeId);
  store.addAbsenceRequest({
    employeeId: newRequest.value.employeeId,
    employeeName: emp?.name || '',
    type: newRequest.value.type,
    startDate: newRequest.value.startDate,
    endDate: newRequest.value.endDate,
    days: calculatedDays.value,
    notes: newRequest.value.notes,
  });

  showNewRequest.value = false;
  toast.success(t('common.savedSuccessfully'));
}

function handleApprove(id: string) {
  store.approveAbsence(id);
  toast.success(t('workday.approve'));
}

function handleReject(id: string) {
  store.rejectAbsence(id);
  toast.info(t('workday.reject'));
}
</script>

<template>
  <div class="space-y-6">
    <!-- Vacation Balance Cards -->
    <div>
      <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ t('workday.vacationDays') }}</h3>
      <div :class="GRID_STYLES.cols4Dense">
        <div
          v-for="bal in store.vacationBalances"
          :key="bal.employeeId"
          :class="[CARD_STYLES.base, 'p-4']"
        >
          <div class="flex items-center gap-3 mb-3">
            <div
              class="w-9 h-9 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-semibold text-xs uppercase"
            >
              {{ bal.employeeName.split(' ').map(n => n[0]).join('') }}
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ bal.employeeName }}</p>
            </div>
          </div>

          <!-- Balance bar -->
          <div class="space-y-2">
            <div class="flex justify-between text-xs">
              <span class="text-slate-500">{{ t('workday.used') }}: {{ bal.usedDays }}d</span>
              <span class="font-medium text-slate-700">{{ t('workday.remaining') }}: {{ bal.remainingDays }}d</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2.5">
              <div
                class="h-2.5 rounded-full transition-all duration-500"
                :class="bal.remainingDays <= 3 ? 'bg-red-500' : bal.remainingDays <= 8 ? 'bg-amber-500' : 'bg-emerald-500'"
                :style="{ width: `${(bal.usedDays / bal.totalDays) * 100}%` }"
              />
            </div>
            <p class="text-[10px] text-slate-400 text-right">
              Total: {{ bal.totalDays }} {{ t('workday.vacationDays') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Absence Calendar (simplified timeline) -->
    <div :class="CARD_STYLES.base">
      <div :class="[CARD_STYLES.headerCompact, 'flex items-center justify-between']">
        <h3 class="text-sm font-semibold text-slate-900">{{ t('workday.absences') }}</h3>
        <div class="flex items-center gap-2">
          <!-- Status filter tabs -->
          <div class="flex gap-1 bg-slate-100 p-1 rounded-lg">
            <button
              v-for="filter in (['ALL', 'PENDING', 'APPROVED', 'REJECTED'] as const)"
              :key="filter"
              :class="[
                'px-2.5 py-1 text-xs font-medium rounded-md transition-colors',
                statusFilter === filter
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700',
              ]"
              @click="statusFilter = filter"
            >
              <template v-if="filter === 'ALL'">Alle</template>
              <template v-else-if="filter === 'PENDING'">{{ t('common.pending') }}</template>
              <template v-else-if="filter === 'APPROVED'">{{ t('common.approved') }}</template>
              <template v-else>{{ t('common.rejected') }}</template>
            </button>
          </div>
          <BButton variant="primary" size="sm" @click="openNewRequest">
            <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ t('workday.request') }}
          </BButton>
        </div>
      </div>

      <!-- Table -->
      <div v-if="filteredAbsences.length > 0" :class="TABLE_STYLES.scrollContainer">
        <table :class="TABLE_STYLES.table">
          <thead :class="TABLE_STYLES.thead">
            <tr>
              <th :class="TABLE_STYLES.th">{{ t('employees.title') }}</th>
              <th :class="TABLE_STYLES.th">Typ</th>
              <th :class="TABLE_STYLES.th">Von</th>
              <th v-if="!isMobile" :class="TABLE_STYLES.th">Bis</th>
              <th v-if="!isMobile" :class="[TABLE_STYLES.th, 'text-center']">Tage</th>
              <th :class="TABLE_STYLES.th">Status</th>
              <th :class="TABLE_STYLES.th" />
            </tr>
          </thead>
          <tbody :class="TABLE_STYLES.tbody">
            <tr
              v-for="absence in filteredAbsences"
              :key="absence.id"
              :class="TABLE_STYLES.tr"
            >
              <td :class="TABLE_STYLES.tdBold">
                <div class="flex items-center gap-2">
                  <div
                    class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-semibold text-[10px] uppercase"
                  >
                    {{ absence.employeeName.split(' ').map(n => n[0]).join('') }}
                  </div>
                  <span>{{ absence.employeeName }}</span>
                </div>
              </td>
              <td :class="TABLE_STYLES.td">
                <BBadge :variant="getTypeBadgeVariant(absence.type)">
                  {{ getTypeLabel(absence.type) }}
                </BBadge>
              </td>
              <td :class="TABLE_STYLES.td">{{ formatDateShort(absence.startDate) }}</td>
              <td v-if="!isMobile" :class="TABLE_STYLES.td">{{ formatDateShort(absence.endDate) }}</td>
              <td v-if="!isMobile" :class="[TABLE_STYLES.td, 'text-center font-medium']">{{ absence.days }}</td>
              <td :class="TABLE_STYLES.td">
                <BBadge :variant="getStatusBadge(absence.status)">
                  {{ getStatusLabel(absence.status) }}
                </BBadge>
              </td>
              <td :class="TABLE_STYLES.td">
                <div v-if="absence.status === 'PENDING'" class="flex gap-1">
                  <button
                    :class="[BUTTON_STYLES.icon, 'text-emerald-600 hover:bg-emerald-50']"
                    :title="t('workday.approve')"
                    @click="handleApprove(absence.id)"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                  <button
                    :class="[BUTTON_STYLES.icon, 'text-red-600 hover:bg-red-50']"
                    :title="t('workday.reject')"
                    @click="handleReject(absence.id)"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <span v-else class="text-xs text-slate-400">
                  {{ absence.notes }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty state -->
      <div v-else class="p-6">
        <BEmptyState
          :title="t('common.noResults')"
          :description="t('common.noResultsMessage')"
          icon="calendar"
        />
      </div>
    </div>

    <!-- New Request Modal -->
    <BModal
      v-model="showNewRequest"
      :title="t('workday.request')"
      size="md"
    >
      <div class="space-y-4">
        <BSelect
          v-model="newRequest.employeeId"
          :label="t('employees.title')"
          :options="employeeOptions"
          required
        />

        <BSelect
          v-model="newRequest.type"
          :label="'Typ'"
          :options="absenceTypeOptions"
          required
        />

        <div :class="isMobile ? 'space-y-4' : 'grid grid-cols-2 gap-4'">
          <BInput
            v-model="newRequest.startDate"
            type="date"
            label="Von"
            required
          />
          <BInput
            v-model="newRequest.endDate"
            type="date"
            label="Bis"
            required
          />
        </div>

        <!-- Calculated days -->
        <div
          v-if="calculatedDays > 0"
          class="flex items-center justify-between p-3 bg-amber-50 border border-amber-200 rounded-lg"
        >
          <span class="text-sm text-amber-800">Arbeitstage:</span>
          <span class="text-lg font-bold text-amber-900">{{ calculatedDays }}</span>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">
            {{ t('appointments.notes') }}
          </label>
          <textarea
            v-model="newRequest.notes"
            :class="INPUT_STYLES.textarea"
            rows="3"
            :placeholder="t('appointments.notes')"
          />
        </div>

        <!-- Vacation balance info for selected employee -->
        <div
          v-if="newRequest.type === 'VACATION'"
          class="text-xs text-slate-500 p-3 bg-slate-50 rounded-lg"
        >
          <template v-for="bal in store.vacationBalances" :key="bal.employeeId">
            <div v-if="bal.employeeId === newRequest.employeeId">
              {{ t('workday.remaining') }}: <strong>{{ bal.remainingDays }} {{ t('workday.vacationDays') }}</strong>
              ({{ t('workday.used') }}: {{ bal.usedDays }} / Total: {{ bal.totalDays }})
            </div>
          </template>
        </div>
      </div>

      <template #footer>
        <BButton variant="secondary" @click="showNewRequest = false">
          {{ t('common.cancel') }}
        </BButton>
        <BButton
          variant="primary"
          :disabled="!newRequest.startDate || !newRequest.endDate || calculatedDays === 0"
          @click="submitRequest"
        >
          {{ t('workday.request') }}
        </BButton>
      </template>
    </BModal>
  </div>
</template>
