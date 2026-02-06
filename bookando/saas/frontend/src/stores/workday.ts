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
// MOCK DATA
// ============================================================================

const today = new Date();
const monday = getMonday(today);

function dayStr(offset: number): string {
  const d = new Date(monday);
  d.setDate(d.getDate() + offset);
  return formatDate(d);
}

const EMPLOYEES = [
  { id: 'emp-1', name: 'Anna Müller' },
  { id: 'emp-2', name: 'Beat Keller' },
  { id: 'emp-3', name: 'Claudia Brunner' },
  { id: 'emp-4', name: 'Daniel Schmid' },
];

function createMockTimeEntries(): TimeEntry[] {
  const entries: TimeEntry[] = [];
  // Anna Müller — full week
  const annaSchedule = [
    { day: 0, clockIn: '08:00', clockOut: '17:00', breakMinutes: 60 },
    { day: 1, clockIn: '07:30', clockOut: '16:30', breakMinutes: 60 },
    { day: 2, clockIn: '08:00', clockOut: '17:30', breakMinutes: 45 },
    { day: 3, clockIn: '08:15', clockOut: '17:15', breakMinutes: 60 },
    { day: 4, clockIn: '08:00', clockOut: '16:00', breakMinutes: 30 },
  ];
  annaSchedule.forEach((s, i) => {
    entries.push({
      id: `te-anna-${i}`,
      employeeId: 'emp-1',
      employeeName: 'Anna Müller',
      date: dayStr(s.day),
      clockIn: s.clockIn,
      clockOut: s.clockOut,
      breakMinutes: s.breakMinutes,
      type: 'REGULAR',
      notes: '',
      status: i < 3 ? 'APPROVED' : 'SUBMITTED',
    });
  });

  // Beat Keller — partial week, some overtime
  const beatSchedule = [
    { day: 0, clockIn: '07:00', clockOut: '18:00', breakMinutes: 30, type: 'OVERTIME' as TimeEntryType },
    { day: 1, clockIn: '07:00', clockOut: '17:30', breakMinutes: 45, type: 'REGULAR' as TimeEntryType },
    { day: 2, clockIn: '08:00', clockOut: '17:00', breakMinutes: 60, type: 'REGULAR' as TimeEntryType },
    { day: 3, clockIn: '07:30', clockOut: '16:30', breakMinutes: 60, type: 'REGULAR' as TimeEntryType },
  ];
  beatSchedule.forEach((s, i) => {
    entries.push({
      id: `te-beat-${i}`,
      employeeId: 'emp-2',
      employeeName: 'Beat Keller',
      date: dayStr(s.day),
      clockIn: s.clockIn,
      clockOut: s.clockOut,
      breakMinutes: s.breakMinutes,
      type: s.type,
      notes: i === 0 ? 'Projektdeadline' : '',
      status: 'OPEN',
    });
  });

  // Claudia Brunner — 3 days, short break violation
  const claudiaSchedule = [
    { day: 0, clockIn: '09:00', clockOut: '17:30', breakMinutes: 15 },
    { day: 1, clockIn: '09:00', clockOut: '18:00', breakMinutes: 30 },
    { day: 2, clockIn: '09:00', clockOut: '17:00', breakMinutes: 30 },
  ];
  claudiaSchedule.forEach((s, i) => {
    entries.push({
      id: `te-claudia-${i}`,
      employeeId: 'emp-3',
      employeeName: 'Claudia Brunner',
      date: dayStr(s.day),
      clockIn: s.clockIn,
      clockOut: s.clockOut,
      breakMinutes: s.breakMinutes,
      type: 'REGULAR',
      notes: '',
      status: 'SUBMITTED',
    });
  });

  // Daniel Schmid — sick on Mon, rest normal
  entries.push({
    id: 'te-daniel-0',
    employeeId: 'emp-4',
    employeeName: 'Daniel Schmid',
    date: dayStr(0),
    clockIn: '00:00',
    clockOut: '00:00',
    breakMinutes: 0,
    type: 'SICK',
    notes: 'Arztzeugnis vorhanden',
    status: 'APPROVED',
  });
  const danielSchedule = [
    { day: 1, clockIn: '08:00', clockOut: '16:30', breakMinutes: 30 },
    { day: 2, clockIn: '08:00', clockOut: '17:00', breakMinutes: 45 },
    { day: 3, clockIn: '08:30', clockOut: '17:30', breakMinutes: 60 },
  ];
  danielSchedule.forEach((s, i) => {
    entries.push({
      id: `te-daniel-${i + 1}`,
      employeeId: 'emp-4',
      employeeName: 'Daniel Schmid',
      date: dayStr(s.day),
      clockIn: s.clockIn,
      clockOut: s.clockOut,
      breakMinutes: s.breakMinutes,
      type: 'REGULAR',
      notes: '',
      status: 'OPEN',
    });
  });

  return entries;
}

function createMockShifts(): ShiftEntry[] {
  const shifts: ShiftEntry[] = [];
  const shiftDefs: Record<ShiftType, { start: string; end: string }> = {
    EARLY: { start: '06:00', end: '14:00' },
    LATE: { start: '14:00', end: '22:00' },
    NIGHT: { start: '22:00', end: '06:00' },
    OFF: { start: '', end: '' },
  };

  // Week schedule per employee
  const schedule: Record<string, ShiftType[]> = {
    'emp-1': ['EARLY', 'EARLY', 'LATE', 'LATE', 'EARLY', 'OFF', 'OFF'],
    'emp-2': ['LATE', 'LATE', 'EARLY', 'EARLY', 'LATE', 'OFF', 'OFF'],
    'emp-3': ['EARLY', 'LATE', 'EARLY', 'LATE', 'OFF', 'EARLY', 'OFF'],
    'emp-4': ['OFF', 'EARLY', 'EARLY', 'LATE', 'LATE', 'OFF', 'OFF'],
  };

  EMPLOYEES.forEach(emp => {
    const empSchedule = schedule[emp.id] || [];
    empSchedule.forEach((shiftType, dayIndex) => {
      const def = shiftDefs[shiftType];
      shifts.push({
        id: `shift-${emp.id}-${dayIndex}`,
        employeeId: emp.id,
        employeeName: emp.name,
        date: dayStr(dayIndex),
        shiftType,
        startTime: def.start,
        endTime: def.end,
      });
    });
  });

  return shifts;
}

function createMockAbsences(): AbsenceRequest[] {
  return [
    {
      id: 'abs-1',
      employeeId: 'emp-1',
      employeeName: 'Anna Müller',
      type: 'VACATION',
      startDate: '2026-03-02',
      endDate: '2026-03-06',
      days: 5,
      status: 'APPROVED',
      notes: 'Skiferien',
    },
    {
      id: 'abs-2',
      employeeId: 'emp-2',
      employeeName: 'Beat Keller',
      type: 'TRAINING',
      startDate: '2026-02-16',
      endDate: '2026-02-17',
      days: 2,
      status: 'APPROVED',
      notes: 'Vue.js Advanced Workshop',
    },
    {
      id: 'abs-3',
      employeeId: 'emp-3',
      employeeName: 'Claudia Brunner',
      type: 'VACATION',
      startDate: '2026-04-13',
      endDate: '2026-04-24',
      days: 10,
      status: 'PENDING',
      notes: 'Osterferien',
    },
    {
      id: 'abs-4',
      employeeId: 'emp-4',
      employeeName: 'Daniel Schmid',
      type: 'SICK',
      startDate: dayStr(0),
      endDate: dayStr(0),
      days: 1,
      status: 'APPROVED',
      notes: 'Arztzeugnis vorhanden',
    },
    {
      id: 'abs-5',
      employeeId: 'emp-1',
      employeeName: 'Anna Müller',
      type: 'PERSONAL',
      startDate: '2026-02-20',
      endDate: '2026-02-20',
      days: 1,
      status: 'PENDING',
      notes: 'Umzug',
    },
    {
      id: 'abs-6',
      employeeId: 'emp-2',
      employeeName: 'Beat Keller',
      type: 'MILITARY',
      startDate: '2026-06-01',
      endDate: '2026-06-19',
      days: 15,
      status: 'APPROVED',
      notes: 'WK',
    },
  ];
}

// ============================================================================
// STORE
// ============================================================================

export const useWorkdayStore = defineStore('workday', () => {
  // State
  const timeEntries = ref<TimeEntry[]>(createMockTimeEntries());
  const shiftEntries = ref<ShiftEntry[]>(createMockShifts());
  const absenceRequests = ref<AbsenceRequest[]>(createMockAbsences());

  // Clock state for the current user (emp-1 by default)
  const currentEmployeeId = ref('emp-1');
  const clockState = ref<ClockState>('NOT_STARTED');
  const clockStartTime = ref<Date | null>(null);
  const breakStartTime = ref<Date | null>(null);
  const totalBreakSeconds = ref(0);

  // Current week reference
  const currentWeekStart = ref(getMonday(new Date()));

  // Vacation balances
  const vacationBalances = ref<VacationBalance[]>([
    { employeeId: 'emp-1', employeeName: 'Anna Müller', totalDays: 25, usedDays: 6, remainingDays: 19 },
    { employeeId: 'emp-2', employeeName: 'Beat Keller', totalDays: 25, usedDays: 17, remainingDays: 8 },
    { employeeId: 'emp-3', employeeName: 'Claudia Brunner', totalDays: 25, usedDays: 10, remainingDays: 15 },
    { employeeId: 'emp-4', employeeName: 'Daniel Schmid', totalDays: 20, usedDays: 3, remainingDays: 17 },
  ]);

  // ----------------------------------------------------------
  // COMPUTED
  // ----------------------------------------------------------

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
    for (const emp of EMPLOYEES) {
      const v = validateWeeklyMax(emp.id);
      if (v) violations.push(v);
    }
    return violations;
  });

  // ----------------------------------------------------------
  // ACTIONS
  // ----------------------------------------------------------

  function clockIn() {
    clockState.value = 'WORKING';
    clockStartTime.value = new Date();
    totalBreakSeconds.value = 0;
    breakStartTime.value = null;
  }

  function clockOut() {
    if (!clockStartTime.value) return;
    const now = new Date();
    const breakMins = Math.round(totalBreakSeconds.value / 60);
    const clockInStr = `${String(clockStartTime.value.getHours()).padStart(2, '0')}:${String(clockStartTime.value.getMinutes()).padStart(2, '0')}`;
    const clockOutStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    const newEntry: TimeEntry = {
      id: `te-new-${Date.now()}`,
      employeeId: currentEmployeeId.value,
      employeeName: EMPLOYEES.find(e => e.id === currentEmployeeId.value)?.name || '',
      date: formatDate(now),
      clockIn: clockInStr,
      clockOut: clockOutStr,
      breakMinutes: breakMins,
      type: 'REGULAR',
      notes: '',
      status: 'OPEN',
    };

    timeEntries.value.push(newEntry);
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

  function addTimeEntry(entry: Omit<TimeEntry, 'id'>) {
    timeEntries.value.push({
      ...entry,
      id: `te-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    });
  }

  function updateTimeEntry(id: string, updates: Partial<TimeEntry>) {
    const idx = timeEntries.value.findIndex(e => e.id === id);
    if (idx >= 0) {
      timeEntries.value[idx] = { ...timeEntries.value[idx], ...updates };
    }
  }

  function deleteTimeEntry(id: string) {
    timeEntries.value = timeEntries.value.filter(e => e.id !== id);
  }

  function updateShift(id: string, shiftType: ShiftType) {
    const idx = shiftEntries.value.findIndex(s => s.id === id);
    if (idx >= 0) {
      const shiftDefs: Record<ShiftType, { start: string; end: string }> = {
        EARLY: { start: '06:00', end: '14:00' },
        LATE: { start: '14:00', end: '22:00' },
        NIGHT: { start: '22:00', end: '06:00' },
        OFF: { start: '', end: '' },
      };
      const def = shiftDefs[shiftType];
      shiftEntries.value[idx] = {
        ...shiftEntries.value[idx],
        shiftType,
        startTime: def.start,
        endTime: def.end,
      };
    }
  }

  function addAbsenceRequest(request: Omit<AbsenceRequest, 'id' | 'status'>) {
    absenceRequests.value.push({
      ...request,
      id: `abs-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
      status: 'PENDING',
    });
  }

  function approveAbsence(id: string) {
    const idx = absenceRequests.value.findIndex(a => a.id === id);
    if (idx >= 0) {
      absenceRequests.value[idx] = { ...absenceRequests.value[idx], status: 'APPROVED' };
    }
  }

  function rejectAbsence(id: string) {
    const idx = absenceRequests.value.findIndex(a => a.id === id);
    if (idx >= 0) {
      absenceRequests.value[idx] = { ...absenceRequests.value[idx], status: 'REJECTED' };
    }
  }

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

    // Actions
    clockIn,
    clockOut,
    startBreak,
    endBreak,
    addTimeEntry,
    updateTimeEntry,
    deleteTimeEntry,
    updateShift,
    addAbsenceRequest,
    approveAbsence,
    rejectAbsence,
    navigateWeek,
    goToCurrentWeek,

    // Helpers (exposed for components)
    calcHours,
    getWeekDates,
    EMPLOYEES,
  };
});
