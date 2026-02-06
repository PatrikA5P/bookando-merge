/**
 * Workday Store — Zeiterfassung, Schichtplan & Abwesenheiten
 *
 * ArG-konform (Schweizer Arbeitsgesetz):
 * - Pausenregeln: >5.5h → 15min, >7h → 30min, >9h → 60min
 * - Maximale Wochenarbeitszeit: 45h (Industrie/Büro)
 * - Nachtarbeitsverbot 23:00-06:00 (Ausnahmen möglich)
 *
 * Stores: TimeEntries, ShiftEntries, AbsenceRequests
 * Computed: todayEntries, weeklyHours, overtimeHours, ArG-Validierung
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';

// ============================================================================
// TYPES
// ============================================================================

export type TimeEntryType = 'REGULAR' | 'OVERTIME' | 'HOLIDAY' | 'SICK';
export type TimeEntryStatus = 'OPEN' | 'SUBMITTED' | 'APPROVED';
export type ShiftType = 'EARLY' | 'LATE' | 'NIGHT' | 'OFF';
export type AbsenceType = 'VACATION' | 'SICK' | 'PERSONAL' | 'TRAINING' | 'MATERNITY' | 'MILITARY';
export type AbsenceStatus = 'PENDING' | 'APPROVED' | 'REJECTED';
export type ClockState = 'NOT_STARTED' | 'WORKING' | 'ON_BREAK';

export interface TimeEntry {
  id: string;
  employeeId: string;
  employeeName: string;
  date: string;
  clockIn: string;
  clockOut: string | null;
  breakMinutes: number;
  type: TimeEntryType;
  notes: string;
  status: TimeEntryStatus;
}

export interface ShiftEntry {
  id: string;
  employeeId: string;
  employeeName: string;
  date: string;
  shiftType: ShiftType;
  startTime: string;
  endTime: string;
}

export interface AbsenceRequest {
  id: string;
  employeeId: string;
  employeeName: string;
  type: AbsenceType;
  startDate: string;
  endDate: string;
  days: number;
  status: AbsenceStatus;
  notes: string;
}

export interface ArGViolation {
  type: 'BREAK_MISSING' | 'BREAK_TOO_SHORT' | 'WEEKLY_HOURS_EXCEEDED' | 'NIGHT_WORK';
  message: string;
  entryId: string;
  severity: 'warning' | 'error';
}

export interface VacationBalance {
  employeeId: string;
  employeeName: string;
  totalDays: number;
  usedDays: number;
  remainingDays: number;
}

// ============================================================================
// HELPERS
// ============================================================================

function getMonday(date: Date): Date {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  d.setDate(diff);
  d.setHours(0, 0, 0, 0);
  return d;
}

function formatDate(date: Date): string {
  return date.toISOString().split('T')[0];
}

function getWeekDates(referenceDate: Date): string[] {
  const monday = getMonday(referenceDate);
  const dates: string[] = [];
  for (let i = 0; i < 7; i++) {
    const d = new Date(monday);
    d.setDate(d.getDate() + i);
    dates.push(formatDate(d));
  }
  return dates;
}

function parseTime(timeStr: string): number {
  const [h, m] = timeStr.split(':').map(Number);
  return h * 60 + m;
}

function calcHours(clockIn: string, clockOut: string | null, breakMinutes: number): number {
  if (!clockOut) return 0;
  const inMinutes = parseTime(clockIn);
  const outMinutes = parseTime(clockOut);
  const worked = outMinutes - inMinutes - breakMinutes;
  return Math.max(0, worked / 60);
}

// ============================================================================
// STORE
// ============================================================================

export const useWorkdayStore = defineStore('workday', () => {
  // State — initialized empty, populated via API
  const timeEntries = ref<TimeEntry[]>([]);
  const shiftEntries = ref<ShiftEntry[]>([]);
  const absenceRequests = ref<AbsenceRequest[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Clock state for the current user (emp-1 by default)
  const currentEmployeeId = ref('emp-1');
  const clockState = ref<ClockState>('NOT_STARTED');
  const clockStartTime = ref<Date | null>(null);
  const breakStartTime = ref<Date | null>(null);
  const totalBreakSeconds = ref(0);

  // Current week reference
  const currentWeekStart = ref(getMonday(new Date()));

  // ----------------------------------------------------------
  // COMPUTED
  // ----------------------------------------------------------

  /** Derive unique employees from all loaded data sources */
  const EMPLOYEES = computed(() => {
    const map = new Map<string, string>();
    for (const e of timeEntries.value) {
      if (!map.has(e.employeeId)) map.set(e.employeeId, e.employeeName);
    }
    for (const s of shiftEntries.value) {
      if (!map.has(s.employeeId)) map.set(s.employeeId, s.employeeName);
    }
    for (const a of absenceRequests.value) {
      if (!map.has(a.employeeId)) map.set(a.employeeId, a.employeeName);
    }
    return Array.from(map.entries()).map(([id, name]) => ({ id, name }));
  });

  /** Vacation balances computed from absence data (default 25 days/year Swiss standard) */
  const vacationBalances = computed<VacationBalance[]>(() => {
    const DEFAULT_VACATION_DAYS = 25;
    const balanceMap = new Map<string, VacationBalance>();

    for (const emp of EMPLOYEES.value) {
      balanceMap.set(emp.id, {
        employeeId: emp.id,
        employeeName: emp.name,
        totalDays: DEFAULT_VACATION_DAYS,
        usedDays: 0,
        remainingDays: DEFAULT_VACATION_DAYS,
      });
    }

    for (const absence of absenceRequests.value) {
      if (absence.type === 'VACATION' && absence.status !== 'REJECTED') {
        const bal = balanceMap.get(absence.employeeId);
        if (bal) {
          bal.usedDays += absence.days;
          bal.remainingDays = bal.totalDays - bal.usedDays;
        }
      }
    }

    return Array.from(balanceMap.values());
  });

  const todayStr = computed(() => formatDate(new Date()));

  const todayEntries = computed(() =>
    timeEntries.value.filter(e => e.date === todayStr.value),
  );

  const weekDates = computed(() => getWeekDates(currentWeekStart.value));

  const weekEntries = computed(() =>
    timeEntries.value.filter(e => weekDates.value.includes(e.date)),
  );

  /** Total worked hours this week for all employees */
  const weeklyHoursPerEmployee = computed(() => {
    const result: Record<string, number> = {};
    for (const entry of weekEntries.value) {
      if (entry.type === 'SICK' || entry.type === 'HOLIDAY') continue;
      const hours = calcHours(entry.clockIn, entry.clockOut, entry.breakMinutes);
      result[entry.employeeId] = (result[entry.employeeId] || 0) + hours;
    }
    return result;
  });

  /** Weekly hours for current employee */
  const weeklyHours = computed(() =>
    weeklyHoursPerEmployee.value[currentEmployeeId.value] || 0,
  );

  /** Overtime (weekly hours > 42.5 regular, difference up to 45 max) */
  const overtimeHours = computed(() => {
    const regular = 42.5; // Standard 5-day week at 8.5h
    return Math.max(0, weeklyHours.value - regular);
  });

  /** Total break minutes this week for current employee */
  const weeklyBreakMinutes = computed(() => {
    return weekEntries.value
      .filter(e => e.employeeId === currentEmployeeId.value)
      .reduce((sum, e) => sum + e.breakMinutes, 0);
  });

  /** Daily hours breakdown for current employee */
  const dailyHours = computed(() => {
    return weekDates.value.map(date => {
      const entries = timeEntries.value.filter(
        e => e.date === date && e.employeeId === currentEmployeeId.value,
      );
      const totalHours = entries.reduce(
        (sum, e) => sum + calcHours(e.clockIn, e.clockOut, e.breakMinutes),
        0,
      );
      const totalBreak = entries.reduce((sum, e) => sum + e.breakMinutes, 0);
      const grossHours = entries.reduce(
        (sum, e) => sum + calcHours(e.clockIn, e.clockOut, 0),
        0,
      );
      return { date, grossHours, breakMinutes: totalBreak, netHours: totalHours };
    });
  });

  // ----------------------------------------------------------
  // ARG VALIDATION
  // ----------------------------------------------------------

  /**
   * Swiss ArG break requirements:
   * - >5.5h worked → min 15min break
   * - >7h worked   → min 30min break
   * - >9h worked   → min 60min break
   */
  function validateArGBreaks(entry: TimeEntry): ArGViolation | null {
    if (!entry.clockOut || entry.type === 'SICK' || entry.type === 'HOLIDAY') return null;
    const grossMinutes = parseTime(entry.clockOut) - parseTime(entry.clockIn);
    const grossHours = grossMinutes / 60;

    let requiredBreak = 0;
    if (grossHours > 9) requiredBreak = 60;
    else if (grossHours > 7) requiredBreak = 30;
    else if (grossHours > 5.5) requiredBreak = 15;

    if (requiredBreak > 0 && entry.breakMinutes < requiredBreak) {
      return {
        type: entry.breakMinutes === 0 ? 'BREAK_MISSING' : 'BREAK_TOO_SHORT',
        message: `ArG-Verstoss: ${grossHours.toFixed(1)}h Arbeitszeit erfordert mind. ${requiredBreak}min Pause (eingetragen: ${entry.breakMinutes}min)`,
        entryId: entry.id,
        severity: 'error',
      };
    }
    return null;
  }

  /**
   * Max 45h/week for industry/office workers (ArG Art. 9)
   */
  function validateWeeklyMax(employeeId: string): ArGViolation | null {
    const hours = weeklyHoursPerEmployee.value[employeeId] || 0;
    if (hours > 45) {
      return {
        type: 'WEEKLY_HOURS_EXCEEDED',
        message: `ArG-Verstoss: ${hours.toFixed(1)}h/Woche überschreitet das Maximum von 45h`,
        entryId: employeeId,
        severity: 'error',
      };
    }
    return null;
  }

  const argViolations = computed((): ArGViolation[] => {
    const violations: ArGViolation[] = [];
    // Check break rules for all entries in the current week
    for (const entry of weekEntries.value) {
      const v = validateArGBreaks(entry);
      if (v) violations.push(v);
    }
    // Check weekly max for each employee
    for (const emp of EMPLOYEES.value) {
      const v = validateWeeklyMax(emp.id);
      if (v) violations.push(v);
    }
    return violations;
  });

  // ----------------------------------------------------------
  // API FETCH ACTIONS
  // ----------------------------------------------------------

  async function fetchTimeEntries() {
    const response = await api.get<{ data: TimeEntry[] }>('/v1/time-entries', { per_page: 100 });
    timeEntries.value = response.data;
  }

  async function fetchShifts() {
    const response = await api.get<{ data: ShiftEntry[] }>('/v1/shifts', { per_page: 100 });
    shiftEntries.value = response.data;
  }

  async function fetchAbsences() {
    const response = await api.get<{ data: AbsenceRequest[] }>('/v1/absences', { per_page: 100 });
    absenceRequests.value = response.data;
  }

  async function fetchAll() {
    loading.value = true;
    error.value = null;
    try {
      await Promise.all([fetchTimeEntries(), fetchShifts(), fetchAbsences()]);
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load workday data';
      throw e;
    } finally {
      loading.value = false;
    }
  }

  // ----------------------------------------------------------
  // CLOCK ACTIONS (local state management)
  // ----------------------------------------------------------

  function clockIn() {
    clockState.value = 'WORKING';
    clockStartTime.value = new Date();
    totalBreakSeconds.value = 0;
    breakStartTime.value = null;
  }

  async function clockOut() {
    if (!clockStartTime.value) return;
    const now = new Date();
    const breakMins = Math.round(totalBreakSeconds.value / 60);
    const clockInStr = `${String(clockStartTime.value.getHours()).padStart(2, '0')}:${String(clockStartTime.value.getMinutes()).padStart(2, '0')}`;
    const clockOutStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    const payload = {
      employeeId: currentEmployeeId.value,
      date: formatDate(now),
      clockIn: clockInStr,
      clockOut: clockOutStr,
      breakMinutes: breakMins,
      type: 'REGULAR' as TimeEntryType,
      notes: '',
      status: 'OPEN' as TimeEntryStatus,
    };

    const response = await api.post<{ data: TimeEntry }>('/v1/time-entries', payload);
    timeEntries.value.push(response.data);

    clockState.value = 'NOT_STARTED';
    clockStartTime.value = null;
    breakStartTime.value = null;
    totalBreakSeconds.value = 0;
  }

  function startBreak() {
    if (clockState.value !== 'WORKING') return;
    clockState.value = 'ON_BREAK';
    breakStartTime.value = new Date();
  }

  function endBreak() {
    if (clockState.value !== 'ON_BREAK' || !breakStartTime.value) return;
    const elapsed = (Date.now() - breakStartTime.value.getTime()) / 1000;
    totalBreakSeconds.value += elapsed;
    breakStartTime.value = null;
    clockState.value = 'WORKING';
  }

  // ----------------------------------------------------------
  // TIME ENTRY CRUD ACTIONS
  // ----------------------------------------------------------

  async function addTimeEntry(entry: Omit<TimeEntry, 'id'>) {
    const response = await api.post<{ data: TimeEntry }>('/v1/time-entries', entry);
    timeEntries.value.push(response.data);
  }

  async function updateTimeEntry(id: string, updates: Partial<TimeEntry>) {
    const response = await api.put<{ data: TimeEntry }>(`/v1/time-entries/${id}`, updates);
    const idx = timeEntries.value.findIndex(e => e.id === id);
    if (idx >= 0) {
      timeEntries.value[idx] = response.data;
    }
  }

  async function deleteTimeEntry(id: string) {
    await api.delete(`/v1/time-entries/${id}`);
    timeEntries.value = timeEntries.value.filter(e => e.id !== id);
  }

  // ----------------------------------------------------------
  // SHIFT ACTIONS
  // ----------------------------------------------------------

  async function updateShift(id: string, shiftType: ShiftType) {
    const shiftDefs: Record<ShiftType, { start: string; end: string }> = {
      EARLY: { start: '06:00', end: '14:00' },
      LATE: { start: '14:00', end: '22:00' },
      NIGHT: { start: '22:00', end: '06:00' },
      OFF: { start: '', end: '' },
    };
    const def = shiftDefs[shiftType];
    const response = await api.put<{ data: ShiftEntry }>(`/v1/shifts/${id}`, {
      shiftType,
      startTime: def.start,
      endTime: def.end,
    });
    const idx = shiftEntries.value.findIndex(s => s.id === id);
    if (idx >= 0) {
      shiftEntries.value[idx] = response.data;
    }
  }

  // ----------------------------------------------------------
  // ABSENCE ACTIONS
  // ----------------------------------------------------------

  async function addAbsenceRequest(request: Omit<AbsenceRequest, 'id' | 'status'>) {
    const response = await api.post<{ data: AbsenceRequest }>('/v1/absences', {
      ...request,
      status: 'PENDING',
    });
    absenceRequests.value.push(response.data);
  }

  async function approveAbsence(id: string) {
    const response = await api.patch<{ data: AbsenceRequest }>(`/v1/absences/${id}`, {
      status: 'APPROVED',
    });
    const idx = absenceRequests.value.findIndex(a => a.id === id);
    if (idx >= 0) {
      absenceRequests.value[idx] = response.data;
    }
  }

  async function rejectAbsence(id: string) {
    const response = await api.patch<{ data: AbsenceRequest }>(`/v1/absences/${id}`, {
      status: 'REJECTED',
    });
    const idx = absenceRequests.value.findIndex(a => a.id === id);
    if (idx >= 0) {
      absenceRequests.value[idx] = response.data;
    }
  }

  // ----------------------------------------------------------
  // NAVIGATION ACTIONS
  // ----------------------------------------------------------

  function navigateWeek(direction: 'prev' | 'next') {
    const d = new Date(currentWeekStart.value);
    d.setDate(d.getDate() + (direction === 'next' ? 7 : -7));
    currentWeekStart.value = d;
  }

  function goToCurrentWeek() {
    currentWeekStart.value = getMonday(new Date());
  }

  return {
    // State
    timeEntries,
    shiftEntries,
    absenceRequests,
    currentEmployeeId,
    clockState,
    clockStartTime,
    breakStartTime,
    totalBreakSeconds,
    currentWeekStart,
    vacationBalances,
    loading,
    error,

    // Computed
    todayStr,
    todayEntries,
    weekDates,
    weekEntries,
    weeklyHoursPerEmployee,
    weeklyHours,
    overtimeHours,
    weeklyBreakMinutes,
    dailyHours,
    argViolations,

    // Actions — API fetches
    fetchTimeEntries,
    fetchShifts,
    fetchAbsences,
    fetchAll,

    // Actions — Clock
    clockIn,
    clockOut,
    startBreak,
    endBreak,

    // Actions — CRUD
    addTimeEntry,
    updateTimeEntry,
    deleteTimeEntry,
    updateShift,
    addAbsenceRequest,
    approveAbsence,
    rejectAbsence,

    // Actions — Navigation
    navigateWeek,
    goToCurrentWeek,

    // Helpers (exposed for components)
    calcHours,
    getWeekDates,
    EMPLOYEES,
  };
});
