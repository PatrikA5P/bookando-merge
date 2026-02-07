/**
 * Customers Domain Types
 *
 * SOLL-Architektur gemaess MODUL_ANALYSE.md
 *
 * Kundendaten, Kommunikationshistorie, Benachrichtigungspraeferenzen.
 */

// ============================================================================
// ENUMS
// ============================================================================

export type CustomerStatus = 'ACTIVE' | 'BLOCKED' | 'DELETED';

export type Gender = 'MALE' | 'FEMALE' | 'OTHER' | 'PREFER_NOT_TO_SAY';

export type TimelineEventType =
  | 'BOOKING'
  | 'INVOICE'
  | 'PAYMENT'
  | 'NOTE'
  | 'NO_SHOW'
  | 'CREATED'
  | 'STATUS_CHANGE'
  | 'TRAINING_CARD_ASSIGNED'
  | 'COURSE_ENROLLED'
  | 'BADGE_AWARDED'
  | 'NOTIFICATION_SENT';

// ============================================================================
// CUSTOMER
// ============================================================================

export interface Customer {
  id: string;
  organizationId: string;

  firstName: string;
  lastName: string;
  email: string;
  phone: string;

  /** Adresse */
  street?: string;
  zip?: string;
  city?: string;
  country?: string;

  /** Persoenliche Daten */
  birthday?: string;
  gender?: Gender;
  language?: string; // de, en, fr, it
  nationality?: string;

  /** Status */
  status: CustomerStatus;

  /** Freitext */
  notes?: string;
  tags: string[];

  /** Dynamische Felder */
  customData: Record<string, string>;

  /** Schweizer Fahrschul-spezifisch */
  driverLicenseCategories?: string[];
  learnerPermitNumber?: string;

  /** Badges */
  earnedBadgeIds: string[];

  /** Statistik (berechnet) */
  totalBookings?: number;
  totalRevenueCents?: number;
  openInvoicesCents?: number;
  noShowCount?: number;

  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// TIMELINE EVENT
// ============================================================================

export interface TimelineEvent {
  id: string;
  customerId: string;
  eventType: TimelineEventType;
  title: string;
  description?: string;
  metadata?: Record<string, unknown>;
  createdAt: string;
  createdBy?: string;
}

// ============================================================================
// COMMUNICATION HISTORY
// ============================================================================

export type CommunicationChannel = 'EMAIL' | 'SMS' | 'PUSH' | 'IN_APP';
export type CommunicationStatus = 'SENT' | 'DELIVERED' | 'FAILED' | 'OPENED' | 'CLICKED';

export interface CommunicationRecord {
  id: string;
  customerId: string;
  channel: CommunicationChannel;
  subject: string;
  body?: string;
  status: CommunicationStatus;
  sentAt: string;
  deliveredAt?: string;
  openedAt?: string;
}

// ============================================================================
// NOTIFICATION PREFERENCES
// ============================================================================

export interface NotificationPreferences {
  customerId: string;
  emailEnabled: boolean;
  smsEnabled: boolean;
  pushEnabled: boolean;

  /** Detailliert: welche Arten von Benachrichtigungen */
  bookingConfirmation: boolean;
  bookingReminder: boolean;
  bookingCancellation: boolean;
  invoiceSent: boolean;
  paymentReminder: boolean;
  marketingPromotions: boolean;
  courseUpdates: boolean;
  trainingCardUpdates: boolean;
}

// ============================================================================
// GDPR / CONSENT
// ============================================================================

export type ConsentType = 'DATA_PROCESSING' | 'EMAIL_MARKETING' | 'SMS_MARKETING' | 'THIRD_PARTY_SHARING';

export interface ConsentRecord {
  id: string;
  customerId: string;
  consentType: ConsentType;
  granted: boolean;
  grantedAt?: string;
  revokedAt?: string;
  ipAddress?: string;
}

// ============================================================================
// FORM DATA
// ============================================================================

export type CustomerFormData = Omit<Customer, 'id' | 'organizationId' | 'createdAt' | 'updatedAt' | 'totalBookings' | 'totalRevenueCents' | 'openInvoicesCents' | 'noShowCount' | 'earnedBadgeIds'>;
