/**
 * Booking Domain Types
 *
 * SOLL-Architektur gemaess MODUL_ANALYSE.md Abschnitt 2.6
 *
 * Booking mit Statusmaschine:
 * PENDING → CONFIRMED → PAID → COMPLETED / NO_SHOW
 *                                    ↘ CANCELLED (von jedem nicht-finalen Status)
 *
 * Alle Preise in Minor Units (Rappen/Cents).
 */

// ============================================================================
// ENUMS
// ============================================================================

export type BookingStatus = 'PENDING' | 'CONFIRMED' | 'PAID' | 'COMPLETED' | 'NO_SHOW' | 'CANCELLED';

export type PaymentStatus = 'PENDING' | 'PAID' | 'PARTIALLY_PAID' | 'REFUNDED';

// ============================================================================
// STATUS MACHINE — Erlaubte Uebergaenge
// ============================================================================

export const BOOKING_STATUS_TRANSITIONS: Record<BookingStatus, BookingStatus[]> = {
  PENDING: ['CONFIRMED', 'CANCELLED'],
  CONFIRMED: ['PAID', 'CANCELLED'],
  PAID: ['COMPLETED', 'NO_SHOW', 'CANCELLED'],
  COMPLETED: [],
  NO_SHOW: [],
  CANCELLED: [],
};

export const FINAL_BOOKING_STATUSES: BookingStatus[] = ['COMPLETED', 'NO_SHOW', 'CANCELLED'];

/**
 * Prueft ob ein Status-Uebergang erlaubt ist.
 */
export function isTransitionAllowed(from: BookingStatus, to: BookingStatus): boolean {
  return BOOKING_STATUS_TRANSITIONS[from].includes(to);
}

/**
 * Gibt alle moeglichen naechsten Status zurueck.
 */
export function getNextStatuses(current: BookingStatus): BookingStatus[] {
  return BOOKING_STATUS_TRANSITIONS[current];
}

/**
 * Prueft ob ein Status final ist (keine weiteren Uebergaenge moeglich).
 */
export function isFinalStatus(status: BookingStatus): boolean {
  return FINAL_BOOKING_STATUSES.includes(status);
}

// ============================================================================
// BOOKING
// ============================================================================

export interface Booking {
  id: string;
  bookingNumber: string;
  organizationId: string;

  /** Verknuepfungen */
  offerId: string;
  offerTitle?: string;
  offerType?: string;
  sessionId?: string;
  customerId: string;
  customerName?: string;
  employeeId?: string;
  employeeName?: string;

  /** Zeitpunkt */
  scheduledAt: string; // ISO DateTime (NICHT separate date/time Strings)
  durationMinutes: number;

  /** Teilnehmer */
  participantCount: number;
  participants?: BookingParticipant[];

  /** Pricing (Integer Minor Units!) */
  basePriceCents: number;
  extrasTotalCents: number;
  discountCents: number;
  totalPriceCents: number;
  currency: string;

  /** Applied Pricing (falls Dynamic Pricing) */
  appliedPricingLabel?: string;

  /** Status */
  status: BookingStatus;
  paymentStatus: PaymentStatus;

  /** Extras */
  extras: BookingExtra[];

  /** Ressourcen */
  resourceReservations: BookingResourceReservation[];

  /** Formular-Antworten */
  formResponses: Record<string, unknown>;

  /** Storno */
  cancelledAt?: string;
  cancelReason?: string;

  /** Timestamps */
  createdAt: string;
  confirmedAt?: string;
  paidAt?: string;
  completedAt?: string;
  updatedAt: string;
}

// ============================================================================
// BOOKING PARTICIPANT
// ============================================================================

export interface BookingParticipant {
  id: string;
  bookingId: string;
  customerId?: string;
  name: string;
  email?: string;
  phone?: string;
}

// ============================================================================
// BOOKING EXTRAS
// ============================================================================

export interface BookingExtra {
  id: string;
  extraId: string;
  name: string;
  priceCents: number;
  quantity: number;
}

// ============================================================================
// BOOKING RESOURCE RESERVATION (typisiert, NICHT JSON blob)
// ============================================================================

export interface BookingResourceReservation {
  id: string;
  resourceId: string;
  resourceName: string;
  resourceType: string;
  startsAt: string;
  endsAt: string;
  status: 'TENTATIVE' | 'CONFIRMED' | 'RELEASED';
}

// ============================================================================
// FORM DATA
// ============================================================================

export interface BookingFormData {
  offerId: string;
  sessionId?: string;
  customerId: string;
  scheduledAt: string;
  durationMinutes: number;
  participantCount: number;
  extras: { extraId: string; quantity: number }[];
  resourceSelections: { resourceId: string }[];
  formResponses: Record<string, unknown>;
}

// ============================================================================
// STATUS DISPLAY HELPERS
// ============================================================================

export const BOOKING_STATUS_LABELS: Record<BookingStatus, string> = {
  PENDING: 'Ausstehend',
  CONFIRMED: 'Bestaetigt',
  PAID: 'Bezahlt',
  COMPLETED: 'Abgeschlossen',
  NO_SHOW: 'Nicht erschienen',
  CANCELLED: 'Storniert',
};

export const BOOKING_STATUS_COLORS: Record<BookingStatus, string> = {
  PENDING: 'warning',
  CONFIRMED: 'success',
  PAID: 'info',
  COMPLETED: 'brand',
  NO_SHOW: 'danger',
  CANCELLED: 'default',
};

export const PAYMENT_STATUS_LABELS: Record<PaymentStatus, string> = {
  PENDING: 'Ausstehend',
  PAID: 'Bezahlt',
  PARTIALLY_PAID: 'Teilbezahlt',
  REFUNDED: 'Erstattet',
};
