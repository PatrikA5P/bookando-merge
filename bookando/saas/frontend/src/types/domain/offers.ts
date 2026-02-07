/**
 * Offers Domain Types
 *
 * SOLL-Architektur gemaess MODUL_ANALYSE.md Abschnitt 2.2
 *
 * Offer (Basis) + Subtypen:
 * - ServiceOffer: Einzeltermine, Kunde waehlt Slot
 * - EventOffer: Fixe Termine, Gruppen, Serien
 * - OnlineCourseOffer: 24/7, self-paced, verknuepft mit AcademyCourse
 *
 * Alle Preise in Minor Units (Rappen/Cents) als Integer.
 */

// ============================================================================
// ENUMS
// ============================================================================

export type OfferType = 'SERVICE' | 'EVENT' | 'ONLINE_COURSE';

export type OfferStatus = 'DRAFT' | 'ACTIVE' | 'PAUSED' | 'ARCHIVED';

export type OfferVisibility = 'PUBLIC' | 'UNLISTED' | 'PRIVATE';

export type EventStructure = 'SINGLE' | 'SERIES_ALL' | 'SERIES_DROP_IN';

export type AssignmentStrategy =
  | 'ROUND_ROBIN'
  | 'AVAILABILITY'
  | 'PRIORITY'
  | 'SAME_EMPLOYEE'
  | 'WORKLOAD_BALANCE';

export type PaymentOption = 'ON_SITE' | 'CREDIT_CARD' | 'PAYPAL' | 'INVOICE' | 'INSURANCE';

export type IntegrationType = 'NONE' | 'ZOOM' | 'GOOGLE_MEET' | 'MS_TEAMS';

export type PricingRuleType = 'EARLY_BIRD' | 'LAST_MINUTE' | 'SEASONAL' | 'DEMAND' | 'AI';

export type VoucherCategory = 'PROMOTION' | 'GIFT_CARD';

export type VoucherDiscountType = 'PERCENTAGE' | 'FIXED';

// ============================================================================
// BASE OFFER
// ============================================================================

/** Felder die alle Offer-Typen gemeinsam haben */
export interface OfferBase {
  id: string;
  organizationId: string;
  offerType: OfferType;

  // Identifikation
  title: string;
  slug?: string;
  description: string;
  productCode?: string;
  externalProductCode?: string;

  // Kategorisierung
  categoryId: string;
  categoryName?: string;
  tags: string[];

  // Pricing (Integer Minor Units!)
  priceCents: number;
  currency: string;
  salePriceCents?: number;
  vatRate?: number;
  pricingRuleId?: string;
  paymentOptions: PaymentOption[];

  // Medien
  coverImageUrl?: string;
  galleryUrls: string[];

  // Status & Sichtbarkeit
  status: OfferStatus;
  visibility: OfferVisibility;

  // Booking-Defaults
  defaultBookingStatus: 'CONFIRMED' | 'PENDING';
  formTemplateId?: string;

  // Extras
  extraIds: string[];
  customerSelectableExtraIds: string[];

  // Timestamps
  createdAt: string;
  updatedAt: string;
  publishedAt?: string;
}

// ============================================================================
// SERVICE CONFIG (1:1 zu OfferBase WHERE offerType = 'SERVICE')
// ============================================================================

export interface ServiceConfig {
  durationMinutes: number;
  bufferBeforeMin: number;
  bufferAfterMin: number;

  // Slot-Regeln
  slotIntervalMin: number;
  bookingWindowDaysAhead: number;
  minNoticeHours: number;
  cancelNoticeHours: number;
  rescheduleNoticeHours: number;

  // Kapazitaet
  maxParticipants: number;
  allowGroupBooking: boolean;
  maxGroupSize?: number;

  // Zuweisung
  assignmentStrategy: AssignmentStrategy;

  // Wiederholung
  isRecurring: boolean;
  recurrenceRule?: string; // iCal RRULE

  // Ressourcen
  resourceRequirements: OfferResourceRequirement[];
}

export interface ServiceOffer extends OfferBase {
  offerType: 'SERVICE';
  serviceConfig: ServiceConfig;
}

// ============================================================================
// EVENT CONFIG (1:1 zu OfferBase WHERE offerType = 'EVENT')
// ============================================================================

export interface EventConfig {
  eventStructure: EventStructure;
  maxParticipants?: number;
  minParticipants?: number;

  // Booking-Fenster
  bookingOpensAt?: string;
  bookingClosesAt?: string;
  bookingOpensImmediately: boolean;
  bookingClosesOnStart: boolean;

  // Auto-Cancel
  autoCancelBelowMin: boolean;
  autoCancelHoursBefore: number;

  // Waitlist
  waitlistEnabled: boolean;
  waitlistCapacity?: number;

  // Ressourcen
  resourceRequirements: OfferResourceRequirement[];
}

export interface EventOffer extends OfferBase {
  offerType: 'EVENT';
  eventConfig: EventConfig;
}

// ============================================================================
// ONLINE COURSE CONFIG (1:1 zu OfferBase WHERE offerType = 'ONLINE_COURSE')
// ============================================================================

export interface OnlineCourseConfig {
  academyCourseId?: string;
  maxParticipants?: number; // null = unbegrenzt
  accessDurationDays?: number; // null = unbegrenzt

  // Video-Integration
  integrationType: IntegrationType;
  integrationConfig?: Record<string, unknown>;
}

export interface OnlineCourseOffer extends OfferBase {
  offerType: 'ONLINE_COURSE';
  onlineCourseConfig: OnlineCourseConfig;
}

// ============================================================================
// UNION TYPE
// ============================================================================

export type Offer = ServiceOffer | EventOffer | OnlineCourseOffer;

// ============================================================================
// RESOURCE REQUIREMENTS (n:m Offer → Resource)
// ============================================================================

export interface OfferResourceRequirement {
  id: string;
  offerId: string;
  resourceId?: string;
  resourceType?: string; // Falls "irgendein Raum" statt spezifischer Resource
  resourceName?: string;

  isRequired: boolean;
  isCustomerSelectable: boolean;
  quantity: number;
}

// ============================================================================
// CATEGORIES & TAGS
// ============================================================================

export interface OfferCategory {
  id: string;
  name: string;
  description: string;
  image?: string;
  color?: string;
  parentId?: string;
  sortOrder: number;
  serviceCount: number;
}

export interface OfferTag {
  id: string;
  name: string;
  color: string;
}

// ============================================================================
// EXTRAS
// ============================================================================

export type ExtraPriceType = 'FIXED' | 'PERCENTAGE';

export interface OfferExtra {
  id: string;
  name: string;
  description?: string;
  priceCents: number;
  priceType: ExtraPriceType;
  active: boolean;
}

// ============================================================================
// BUNDLES
// ============================================================================

export interface Bundle {
  id: string;
  title: string;
  description: string;
  offerIds: string[];
  totalPriceCents: number;
  savingsCents: number;
  active: boolean;
  image?: string;
}

// ============================================================================
// VOUCHERS
// ============================================================================

export interface Voucher {
  id: string;
  title: string;
  code: string;
  category: VoucherCategory;
  discountType: VoucherDiscountType;
  discountValue: number; // Prozent oder Rappen je nach discountType
  maxUses: number;
  maxUsesPerCustomer?: number;
  usedCount: number;
  expiresAt?: string;
  active: boolean;
  minOrderCents?: number;

  // Gift Card spezifisch
  allowCustomAmount?: boolean;
  minCustomAmountCents?: number;
  maxCustomAmountCents?: number;
  fixedValueCents?: number;
  currentBalanceCents?: number;
}

// ============================================================================
// PRICING RULES
// ============================================================================

export interface PricingRuleConditions {
  daysBeforeMin?: number;
  daysBeforeMax?: number;
  dateRange?: { start: string; end: string };
  timeRange?: { start: string; end: string };
  occupancyThresholdPercent?: number;
}

export interface PricingRule {
  id: string;
  name: string;
  type: PricingRuleType;
  discountPercent: number;
  conditions: PricingRuleConditions;
  active: boolean;
  linkedOfferCount?: number;
}

// ============================================================================
// FORM TEMPLATES
// ============================================================================

export type FormElementType =
  | 'text'
  | 'number'
  | 'date'
  | 'select'
  | 'multiselect'
  | 'radio'
  | 'textarea'
  | 'checkbox'
  | 'file';

export type FormElementWidth = 'full' | 'half' | 'third';

export interface FormElement {
  id: string;
  label: string;
  type: FormElementType;
  width: FormElementWidth;
  required: boolean;
  options?: string[];
  placeholder?: string;
  infoText?: string;
}

export interface FormTemplate {
  id: string;
  name: string;
  description?: string;
  elements: FormElement[];
  active: boolean;
}

// ============================================================================
// TYPE GUARDS
// ============================================================================

export function isServiceOffer(offer: Offer): offer is ServiceOffer {
  return offer.offerType === 'SERVICE';
}

export function isEventOffer(offer: Offer): offer is EventOffer {
  return offer.offerType === 'EVENT';
}

export function isOnlineCourseOffer(offer: Offer): offer is OnlineCourseOffer {
  return offer.offerType === 'ONLINE_COURSE';
}

// ============================================================================
// FORM DATA (fuer Create/Update ohne id & timestamps)
// ============================================================================

export type ServiceOfferFormData = Omit<ServiceOffer, 'id' | 'organizationId' | 'createdAt' | 'updatedAt' | 'publishedAt' | 'categoryName'>;
export type EventOfferFormData = Omit<EventOffer, 'id' | 'organizationId' | 'createdAt' | 'updatedAt' | 'publishedAt' | 'categoryName'>;
export type OnlineCourseOfferFormData = Omit<OnlineCourseOffer, 'id' | 'organizationId' | 'createdAt' | 'updatedAt' | 'publishedAt' | 'categoryName'>;
