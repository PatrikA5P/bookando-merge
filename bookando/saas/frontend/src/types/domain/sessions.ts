/**
 * Sessions & Scheduling Domain Types
 *
 * SOLL-Architektur gemaess MODUL_ANALYSE.md Abschnitt 2.3
 *
 * Sessions sind einzelne Durchfuehrungen von Offers:
 * - Service: on-demand generiert bei Buchung
 * - Event: manuell/Serie vom Planer erstellt
 * - Online Course: optional fuer Live-Sessions
 *
 * EmployeeAvailability: Regeln + Bloecke fuer Mitarbeiterverfuegbarkeit
 */

// ============================================================================
// ENUMS
// ============================================================================

export type SessionStatus = 'SCHEDULED' | 'CONFIRMED' | 'IN_PROGRESS' | 'COMPLETED' | 'CANCELLED';

export type CalendarBlockType =
  | 'ABSENCE'
  | 'VACATION'
  | 'SICK'
  | 'PERSONAL'
  | 'EXTERNAL_BUSY'
  | 'MANUAL_BLOCK';

export type AbsenceApprovalStatus = 'PENDING' | 'APPROVED' | 'REJECTED';

// ============================================================================
// SESSION
// ============================================================================

export interface Session {
  id: string;
  offerId: string;
  offerTitle?: string;

  /** Zeitpunkt */
  startsAt: string;
  endsAt: string;

  /** Zuweisung */
  instructorId?: string;
  instructorName?: string;

  /** Kapazitaet */
  maxParticipants?: number;
  currentEnrollment: number;

  /** Status */
  status: SessionStatus;

  /** Verknuepfung Academy */
  linkedLessonId?: string;
  linkedLessonTitle?: string;

  /** Metadaten */
  title?: string;
  notes?: string;

  /** Ressourcen-Reservierungen fuer diese Session */
  resourceReservationIds?: string[];

  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// EMPLOYEE AVAILABILITY RULES (regelmaessig)
// ============================================================================

export interface EmployeeAvailabilityRule {
  id: string;
  employeeId: string;

  /** Wochentag: 0=Mo, 6=So */
  dayOfWeek: number;
  startTime: string; // HH:mm
  endTime: string;   // HH:mm

  /** Gueltigkeitszeitraum */
  validFrom: string;
  validUntil?: string;
}

// ============================================================================
// CALENDAR BLOCKS (einmalige Sperren)
// ============================================================================

export interface CalendarBlock {
  id: string;
  employeeId: string;

  startsAt: string;
  endsAt: string;

  blockType: CalendarBlockType;
  reason?: string;
  approvalStatus: AbsenceApprovalStatus;

  /** Sync mit externem Kalender */
  externalCalendarId?: string;
  externalEventId?: string;

  createdAt: string;
}

// ============================================================================
// AVAILABLE SLOT (Berechnetes Ergebnis der Verfuegbarkeits-Engine)
// ============================================================================

export interface AvailableSlot {
  startsAt: string;
  endsAt: string;
  employeeId: string;
  employeeName?: string;

  /** Verfuegbare Ressourcen fuer diesen Slot */
  availableResources: SlotResource[];
}

export interface SlotResource {
  resourceId: string;
  resourceName: string;
  resourceType: string;
}

// ============================================================================
// FORM DATA
// ============================================================================

export type SessionFormData = Omit<Session, 'id' | 'createdAt' | 'updatedAt' | 'offerTitle' | 'instructorName' | 'linkedLessonTitle' | 'currentEnrollment'>;

export interface AvailabilityRuleFormData {
  employeeId: string;
  dayOfWeek: number;
  startTime: string;
  endTime: string;
  validFrom: string;
  validUntil?: string;
}

export interface CalendarBlockFormData {
  employeeId: string;
  startsAt: string;
  endsAt: string;
  blockType: CalendarBlockType;
  reason?: string;
}
