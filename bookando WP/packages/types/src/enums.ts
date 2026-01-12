/**
 * @bookando/types - Enums
 * Shared enumerations for the Bookando platform
 */

export enum ModuleName {
  DASHBOARD = 'Dashboard',
  CUSTOMERS = 'Customers',
  EMPLOYEES = 'Employees',
  APPOINTMENTS = 'Appointments',
  FINANCE = 'Finance',
  OFFERS = 'Offers',
  ACADEMY = 'Academy',
  RESOURCES = 'Resources',
  WORKDAY = 'Workday',
  PARTNER_HUB = 'Partner Hub',
  TOOLS = 'Tools',
  SETTINGS = 'Settings'
}

export enum CustomerStatus {
  ACTIVE = 'Active',
  BLOCKED = 'Blocked',
  DELETED = 'Deleted'
}

export enum EmployeeStatus {
  ACTIVE = 'Active',
  VACATION = 'Vacation',
  SICK_LEAVE = 'Sick Leave',
  PAUSE = 'Pause',
  TERMINATED = 'Terminated'
}

export enum InvoiceStatus {
  DRAFT = 'Draft',
  SENT = 'Sent',
  PAID = 'Paid',
  OVERDUE = 'Overdue',
  CANCELLED = 'Cancelled',
  PARTIAL = 'Partial'
}

export enum DunningLevel {
  NONE = 0,
  REMINDER_1 = 1,
  REMINDER_2 = 2,
  COLLECTION = 3
}

export enum CourseType {
  ONLINE = 'Online Course',
  IN_PERSON = 'In-Person Class',
  BLENDED = 'Blended Learning'
}

export enum CourseVisibility {
  PUBLIC = 'Public',
  PRIVATE = 'Private',
  INTERNAL = 'Internal Only'
}

export enum DifficultyLevel {
  BEGINNER = 'Beginner',
  INTERMEDIATE = 'Intermediate',
  ADVANCED = 'Advanced'
}

export enum QuestionType {
  SINGLE_CHOICE = 'Single Choice',
  MULTIPLE_CHOICE = 'Multiple Choice',
  TRUE_FALSE = 'True/False',
  SLIDER = 'Slider',
  PIN_ANSWER = 'Pin Answer',
  ESSAY = 'Essay/Open',
  FILL_BLANKS = 'Fill in Blanks',
  SHORT_ANSWER = 'Short Answer',
  MATCHING = 'Matching',
  IMAGE_ANSWER = 'Image Answer',
  SORTING = 'Sorting',
  PUZZLE = 'Puzzle'
}

export type AppointmentStatus = 'Confirmed' | 'Pending' | 'Cancelled' | 'Completed' | 'No-Show';
export type TimeEntryType = 'Work' | 'Break' | 'Meeting' | 'Travel';
export type ShiftType = 'Early' | 'Late' | 'Night' | 'FullDay' | 'Off';
export type AbsenceType = 'Vacation' | 'Sick' | 'NBU' | 'BU' | 'Maternity' | 'Unpaid' | 'Other';
export type OfferType = 'Service' | 'Online Course' | 'Event';
export type VoucherCategory = 'Promotion' | 'GiftCard';
export type VoucherType = 'Percentage' | 'Fixed';
export type PaymentOption = 'On Site' | 'Credit Card' | 'Paypal' | 'Invoice' | 'Insurance';
export type EventStructure = 'Single' | 'Series_All' | 'Series_DropIn';
export type PricingStrategyType = 'EarlyBird' | 'LastMinute' | 'Occupancy' | 'Demand' | 'Season' | 'History';

export type VatTaxType =
  | 'Bezugsteuer MWST Investitionen, uebriger Betriebsaufwand'
  | 'Bezugsteuer MWST Material, Waren, Dienstleistungen, Energie'
  | 'Vorsteuer MWST Investitionen, uebriger Betriebsaufwand'
  | 'Vorsteuer MWST Material, Waren, Dienstleistungen, Energie'
  | 'Zollsteuer MWST Investitionen, uebriger Betriebsaufwand'
  | 'Zollsteuer MWST Material, Waren, Dienstleistungen, Energie'
  | 'Geschuldete MWST (Umsatzsteuer)'
  | 'Optierte geschuldete MWST (Umsatzsteuer)'
  | 'Nichtentgelt'
  | 'Nicht steuerpflichtiger Umsatz'
  | 'Saldosteuersatz'
  | 'Optierter Saldosteuersatz';
