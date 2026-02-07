/**
 * Sessions Store — Terminplanung & Verfuegbarkeit
 *
 * Gemaess MODUL_ANALYSE.md Abschnitt 2.3:
 * - Sessions sind einzelne Durchfuehrungen von Offers
 * - EmployeeAvailability: Regeln + Bloecke
 * - CalendarBlock: Abwesenheiten mit Genehmigungsworkflow
 * - AvailableSlot: Berechnete verfuegbare Zeitfenster
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';
import type {
  Session,
  SessionStatus,
  EmployeeAvailabilityRule,
  CalendarBlock,
  CalendarBlockType,
  AbsenceApprovalStatus,
  AvailableSlot,
  SessionFormData,
  AvailabilityRuleFormData,
  CalendarBlockFormData,
} from '@/types/domain/sessions';

// Re-export domain types for backward compatibility
export type {
  Session,
  SessionStatus,
  EmployeeAvailabilityRule,
  CalendarBlock,
  CalendarBlockType,
  AbsenceApprovalStatus,
  AvailableSlot,
  SessionFormData,
  AvailabilityRuleFormData,
  CalendarBlockFormData,
};

// ============================================================================
// CONSTANTS
// ============================================================================

export const SESSION_STATUS_LABELS: Record<SessionStatus, string> = {
  SCHEDULED: 'Geplant',
  CONFIRMED: 'Bestaetigt',
  IN_PROGRESS: 'Laufend',
  COMPLETED: 'Abgeschlossen',
  CANCELLED: 'Abgesagt',
};

export const SESSION_STATUS_COLORS: Record<SessionStatus, string> = {
  SCHEDULED: 'warning',
  CONFIRMED: 'success',
  IN_PROGRESS: 'info',
  COMPLETED: 'brand',
  CANCELLED: 'default',
};

export const CALENDAR_BLOCK_TYPE_LABELS: Record<CalendarBlockType, string> = {
  ABSENCE: 'Abwesenheit',
  VACATION: 'Ferien',
  SICK: 'Krankheit',
  PERSONAL: 'Persoenlich',
  EXTERNAL_BUSY: 'Externer Termin',
  MANUAL_BLOCK: 'Manuell gesperrt',
};

export const APPROVAL_STATUS_LABELS: Record<AbsenceApprovalStatus, string> = {
  PENDING: 'Ausstehend',
  APPROVED: 'Genehmigt',
  REJECTED: 'Abgelehnt',
};

export const APPROVAL_STATUS_COLORS: Record<AbsenceApprovalStatus, string> = {
  PENDING: 'warning',
  APPROVED: 'success',
  REJECTED: 'danger',
};

export const DAY_OF_WEEK_LABELS: Record<number, string> = {
  0: 'Montag',
  1: 'Dienstag',
  2: 'Mittwoch',
  3: 'Donnerstag',
  4: 'Freitag',
  5: 'Samstag',
  6: 'Sonntag',
};

// ============================================================================
// FILTERS
// ============================================================================

export interface SessionFilters {
  search: string;
  status: SessionStatus | '';
  instructorId: string;
  dateFrom: string;
  dateTo: string;
}

// ============================================================================
// STORE
// ============================================================================

export const useSessionsStore = defineStore('sessions', () => {
  // ── State ──────────────────────────────────────────────────────────────
  const sessions = ref<Session[]>([]);
  const availabilityRules = ref<EmployeeAvailabilityRule[]>([]);
  const calendarBlocks = ref<CalendarBlock[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<SessionFilters>({
    search: '',
    status: '',
    instructorId: '',
    dateFrom: '',
    dateTo: '',
  });

  // ── Filtered Views ─────────────────────────────────────────────────────
  const filteredSessions = computed(() => {
    let result = sessions.value;

    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(s =>
        (s.title || '').toLowerCase().includes(q) ||
        (s.offerTitle || '').toLowerCase().includes(q) ||
        (s.instructorName || '').toLowerCase().includes(q)
      );
    }

    if (filters.value.status) {
      result = result.filter(s => s.status === filters.value.status);
    }

    if (filters.value.instructorId) {
      result = result.filter(s => s.instructorId === filters.value.instructorId);
    }

    if (filters.value.dateFrom) {
      const from = new Date(filters.value.dateFrom).getTime();
      result = result.filter(s => new Date(s.startsAt).getTime() >= from);
    }

    if (filters.value.dateTo) {
      const to = new Date(filters.value.dateTo).getTime();
      result = result.filter(s => new Date(s.startsAt).getTime() <= to);
    }

    return result;
  });

  // ── Status-filtered Collections ────────────────────────────────────────
  const scheduledSessions = computed(() =>
    sessions.value.filter(s => s.status === 'SCHEDULED')
  );

  const confirmedSessions = computed(() =>
    sessions.value.filter(s => s.status === 'CONFIRMED')
  );

  const inProgressSessions = computed(() =>
    sessions.value.filter(s => s.status === 'IN_PROGRESS')
  );

  const completedSessions = computed(() =>
    sessions.value.filter(s => s.status === 'COMPLETED')
  );

  // ── Counts & Time-based Views ──────────────────────────────────────────
  const sessionCount = computed(() => sessions.value.length);

  const todaySessions = computed(() => {
    const todayStr = new Date().toISOString().slice(0, 10);
    return sessions.value.filter(s => s.startsAt.slice(0, 10) === todayStr);
  });

  const upcomingSessions = computed(() => {
    const now = new Date().getTime();
    return sessions.value
      .filter(s => new Date(s.startsAt).getTime() > now && s.status !== 'CANCELLED')
      .sort((a, b) => new Date(a.startsAt).getTime() - new Date(b.startsAt).getTime());
  });

  // ── Calendar Block Views ───────────────────────────────────────────────
  const pendingApprovals = computed(() =>
    calendarBlocks.value.filter(b => b.approvalStatus === 'PENDING')
  );

  // ── Fetch Actions ──────────────────────────────────────────────────────
  async function fetchSessions(params?: Record<string, string | number | boolean | undefined>): Promise<void> {
    try {
      const response = await api.get<{ data: Session[] }>('/v1/sessions', params);
      sessions.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Sessions konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAvailabilityRules(employeeId?: string): Promise<void> {
    try {
      const params = employeeId ? { employeeId } : undefined;
      const response = await api.get<{ data: EmployeeAvailabilityRule[] }>('/v1/availability-rules', params);
      availabilityRules.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Verfuegbarkeitsregeln konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchCalendarBlocks(employeeId?: string): Promise<void> {
    try {
      const params = employeeId ? { employeeId } : undefined;
      const response = await api.get<{ data: CalendarBlock[] }>('/v1/calendar-blocks', params);
      calendarBlocks.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Kalenderblockierungen konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await Promise.all([
        fetchSessions(),
        fetchAvailabilityRules(),
        fetchCalendarBlocks(),
      ]);
    } catch {
      // individual fetchers already set error.value
    } finally {
      loading.value = false;
    }
  }

  // ── Lookups ────────────────────────────────────────────────────────────
  function getSessionById(id: string): Session | undefined {
    return sessions.value.find(s => s.id === id);
  }

  function getSessionsByOffer(offerId: string): Session[] {
    return sessions.value.filter(s => s.offerId === offerId);
  }

  function getRulesForEmployee(employeeId: string): EmployeeAvailabilityRule[] {
    return availabilityRules.value.filter(r => r.employeeId === employeeId);
  }

  function getBlocksForEmployee(employeeId: string): CalendarBlock[] {
    return calendarBlocks.value.filter(b => b.employeeId === employeeId);
  }

  // ── Available Slots (API-basiert) ──────────────────────────────────────
  async function fetchAvailableSlots(params: {
    offerId: string;
    dateFrom: string;
    dateTo: string;
    employeeId?: string;
  }): Promise<AvailableSlot[]> {
    try {
      const response = await api.get<{ data: AvailableSlot[] }>('/v1/available-slots', params);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Verfuegbare Slots konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  // ── CRUD: Sessions ────────────────────────────────────────────────────
  async function createSession(data: SessionFormData): Promise<Session> {
    try {
      const response = await api.post<{ data: Session }>('/v1/sessions', data);
      sessions.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Session konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateSession(id: string, data: Partial<SessionFormData>): Promise<Session> {
    try {
      const response = await api.put<{ data: Session }>(`/v1/sessions/${id}`, data);
      const index = sessions.value.findIndex(s => s.id === id);
      if (index !== -1) {
        sessions.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Session konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function updateSessionStatus(id: string, status: SessionStatus): Promise<Session> {
    try {
      const response = await api.patch<{ data: Session }>(`/v1/sessions/${id}/status`, { status });
      const index = sessions.value.findIndex(s => s.id === id);
      if (index !== -1) {
        sessions.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Session-Status konnte nicht geaendert werden';
      error.value = message;
      throw err;
    }
  }

  async function cancelSession(id: string): Promise<Session> {
    return updateSessionStatus(id, 'CANCELLED');
  }

  // ── CRUD: Availability Rules ──────────────────────────────────────────
  async function createAvailabilityRule(data: AvailabilityRuleFormData): Promise<EmployeeAvailabilityRule> {
    try {
      const response = await api.post<{ data: EmployeeAvailabilityRule }>('/v1/availability-rules', data);
      availabilityRules.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Regel konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteAvailabilityRule(id: string): Promise<void> {
    try {
      await api.delete(`/v1/availability-rules/${id}`);
      availabilityRules.value = availabilityRules.value.filter(r => r.id !== id);
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Regel konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  // ── CRUD: Calendar Blocks ─────────────────────────────────────────────
  async function createCalendarBlock(data: CalendarBlockFormData): Promise<CalendarBlock> {
    try {
      const response = await api.post<{ data: CalendarBlock }>('/v1/calendar-blocks', data);
      calendarBlocks.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Blockierung konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function approveCalendarBlock(id: string): Promise<CalendarBlock> {
    try {
      const response = await api.patch<{ data: CalendarBlock }>(`/v1/calendar-blocks/${id}/approve`, {});
      const index = calendarBlocks.value.findIndex(b => b.id === id);
      if (index !== -1) {
        calendarBlocks.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Genehmigung fehlgeschlagen';
      error.value = message;
      throw err;
    }
  }

  async function rejectCalendarBlock(id: string): Promise<CalendarBlock> {
    try {
      const response = await api.patch<{ data: CalendarBlock }>(`/v1/calendar-blocks/${id}/reject`, {});
      const index = calendarBlocks.value.findIndex(b => b.id === id);
      if (index !== -1) {
        calendarBlocks.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Ablehnung fehlgeschlagen';
      error.value = message;
      throw err;
    }
  }

  async function deleteCalendarBlock(id: string): Promise<void> {
    try {
      await api.delete(`/v1/calendar-blocks/${id}`);
      calendarBlocks.value = calendarBlocks.value.filter(b => b.id !== id);
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Blockierung konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  // ── Filter Actions ─────────────────────────────────────────────────────
  function setFilters(newFilters: Partial<SessionFilters>) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function resetFilters() {
    filters.value = { search: '', status: '', instructorId: '', dateFrom: '', dateTo: '' };
  }

  return {
    // State
    sessions,
    availabilityRules,
    calendarBlocks,
    loading,
    error,
    filters,

    // Filtered views
    filteredSessions,

    // Status-filtered collections
    scheduledSessions,
    confirmedSessions,
    inProgressSessions,
    completedSessions,

    // Counts & time-based
    sessionCount,
    todaySessions,
    upcomingSessions,

    // Calendar block views
    pendingApprovals,

    // Fetch
    fetchSessions,
    fetchAvailabilityRules,
    fetchCalendarBlocks,
    fetchAll,

    // Lookups
    getSessionById,
    getSessionsByOffer,
    getRulesForEmployee,
    getBlocksForEmployee,

    // Available Slots
    fetchAvailableSlots,

    // Session CRUD
    createSession,
    updateSession,
    updateSessionStatus,
    cancelSession,

    // Availability Rules
    createAvailabilityRule,
    deleteAvailabilityRule,

    // Calendar Blocks
    createCalendarBlock,
    approveCalendarBlock,
    rejectCalendarBlock,
    deleteCalendarBlock,

    // Filters
    setFilters,
    resetFilters,
  };
});
