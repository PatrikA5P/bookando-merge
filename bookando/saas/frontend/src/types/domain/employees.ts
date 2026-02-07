/**
 * Employees Domain Types
 *
 * Mitarbeiterdaten, Verfuegbarkeit, Kalender-Integration.
 */

// ============================================================================
// ENUMS
// ============================================================================

export type EmployeeStatus = 'ACTIVE' | 'ON_LEAVE' | 'INACTIVE';

export type ShiftType = 'EARLY' | 'LATE' | 'NIGHT' | 'OFF';

export type TimeEntryType = 'WORK' | 'BREAK' | 'MEETING' | 'TRAVEL';

export type TimeEntryStatus = 'PENDING' | 'APPROVED' | 'REJECTED';

export type CalendarProvider = 'GOOGLE' | 'MICROSOFT' | 'APPLE';

export type SyncDirection = 'READ_ONLY' | 'WRITE_ONLY' | 'TWO_WAY';

// ============================================================================
// EMPLOYEE
// ============================================================================

export interface Employee {
  id: string;
  organizationId: string;

  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  gender: 'MALE' | 'FEMALE' | 'OTHER';
  birthday?: string;
  description?: string;
  avatar?: string;

  /** Position & Abteilung */
  position: string;
  department?: string;
  roleId?: string;

  /** Arbeitsdaten */
  hireDate: string;
  exitDate?: string;
  workloadPercentage: number; // 0-100
  hourlyRateCents?: number;

  /** Skills & Qualifikationen */
  skills: string[];
  qualifications: string[];
  assignedServiceIds: string[];

  /** Zugang */
  hubPassword?: string;
  badgeId?: string;

  /** Adresse */
  street?: string;
  zip?: string;
  city?: string;
  country?: string;

  /** Status */
  status: EmployeeStatus;

  notes?: string;

  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// TIME TRACKING
// ============================================================================

export interface TimeEntry {
  id: string;
  employeeId: string;
  date: string;
  startTime: string;
  endTime?: string;
  type: TimeEntryType;
  status: TimeEntryStatus;
  notes?: string;
}

export interface Shift {
  id: string;
  employeeId: string;
  date: string;
  shiftType: ShiftType;
  startTime: string;
  endTime: string;
}

// ============================================================================
// CALENDAR CONNECTION
// ============================================================================

export interface CalendarConnection {
  id: string;
  employeeId: string;
  provider: CalendarProvider;
  isActive: boolean;
  email: string;
  calendarId: string;
  syncDirection: SyncDirection;
  autoCreateEvents: boolean;
  autoUpdateEvents: boolean;
  autoDeleteEvents: boolean;
  lastSyncedAt?: string;
}

// ============================================================================
// FORM DATA
// ============================================================================

export type EmployeeFormData = Omit<Employee, 'id' | 'organizationId' | 'createdAt' | 'updatedAt'>;
