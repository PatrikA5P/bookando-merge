/**
 * Automation Rules Domain Types
 *
 * SOLL-Architektur gemaess MODUL_ANALYSE.md Abschnitt 2.9 / 5.2
 *
 * Event-basierte Automationen:
 * Trigger (z.B. BOOKING_CONFIRMED) + Bedingung → Action (z.B. ASSIGN_TRAINING_CARD)
 */

// ============================================================================
// TRIGGER EVENTS
// ============================================================================

export type TriggerEvent =
  | 'BOOKING_CREATED'
  | 'BOOKING_CONFIRMED'
  | 'BOOKING_PAID'
  | 'FIRST_SESSION_OF_TYPE'
  | 'SESSION_COMPLETED'
  | 'COURSE_COMPLETED'
  | 'BOOKING_CANCELLED';

// ============================================================================
// ACTION TYPES
// ============================================================================

export type ActionType =
  | 'ASSIGN_TRAINING_CARD'
  | 'ASSIGN_ONLINE_COURSE'
  | 'GRANT_BADGE'
  | 'SEND_NOTIFICATION'
  | 'ENROLL_IN_COURSE';

// ============================================================================
// AUTOMATION RULE
// ============================================================================

export interface AutomationRule {
  id: string;
  organizationId: string;

  name: string;
  description?: string;
  active: boolean;

  /** Trigger */
  triggerEvent: TriggerEvent;

  /** Trigger-Bedingung */
  triggerOfferId?: string;
  triggerOfferTitle?: string;
  triggerCategoryId?: string;
  triggerCategoryName?: string;
  triggerCondition?: TriggerCondition;

  /** Action */
  actionType: ActionType;
  actionConfig: ActionConfig;

  /** Optionen */
  allowDuplicate: boolean;
  priority: number;

  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// TRIGGER CONDITIONS (erweiterte Bedingungen)
// ============================================================================

export interface TriggerCondition {
  /** z.B. sessionNumber = 1 fuer "erste Fahrstunde" */
  sessionNumber?: number;

  /** z.B. minBookingCount = 3 fuer "nach 3 Buchungen" */
  minBookingCount?: number;

  /** z.B. customerTag = 'VIP' */
  customerTag?: string;
}

// ============================================================================
// ACTION CONFIGS (typ-spezifisch)
// ============================================================================

export interface AssignTrainingCardConfig {
  trainingCardTemplateId: string;
  trainingCardTemplateName?: string;
}

export interface AssignOnlineCourseConfig {
  onlineCourseOfferId: string;
  onlineCourseOfferTitle?: string;
  accessDurationDays?: number;
}

export interface GrantBadgeConfig {
  badgeId: string;
  badgeName?: string;
}

export interface SendNotificationConfig {
  channel: 'EMAIL' | 'SMS' | 'PUSH';
  templateId?: string;
  subject?: string;
  message?: string;
}

export interface EnrollInCourseConfig {
  academyCourseId: string;
  academyCourseTitle?: string;
}

export type ActionConfig =
  | AssignTrainingCardConfig
  | AssignOnlineCourseConfig
  | GrantBadgeConfig
  | SendNotificationConfig
  | EnrollInCourseConfig;

// ============================================================================
// TRIGGER EVENT LABELS (fuer UI)
// ============================================================================

export const TRIGGER_EVENT_LABELS: Record<TriggerEvent, string> = {
  BOOKING_CREATED: 'Bei Buchung erstellt',
  BOOKING_CONFIRMED: 'Bei Buchung bestaetigt',
  BOOKING_PAID: 'Bei Zahlung',
  FIRST_SESSION_OF_TYPE: 'Bei erster Session einer Kategorie',
  SESSION_COMPLETED: 'Bei Session abgeschlossen',
  COURSE_COMPLETED: 'Bei Kurs abgeschlossen',
  BOOKING_CANCELLED: 'Bei Stornierung',
};

export const ACTION_TYPE_LABELS: Record<ActionType, string> = {
  ASSIGN_TRAINING_CARD: 'Ausbildungskarte zuweisen',
  ASSIGN_ONLINE_COURSE: 'Onlinekurs freischalten',
  GRANT_BADGE: 'Badge vergeben',
  SEND_NOTIFICATION: 'Benachrichtigung senden',
  ENROLL_IN_COURSE: 'In Kurs einschreiben',
};

// ============================================================================
// FORM DATA
// ============================================================================

export type AutomationRuleFormData = Omit<AutomationRule, 'id' | 'organizationId' | 'createdAt' | 'updatedAt' | 'triggerOfferTitle' | 'triggerCategoryName'>;
