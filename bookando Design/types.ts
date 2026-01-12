
import { LucideIcon } from 'lucide-react';

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
  SETTINGS = 'Settings',
  DESIGN_SYSTEM = 'Design System',
  DESIGN_FRONTEND = 'Design Frontend'
}

export interface NavItem {
  name: ModuleName;
  icon: LucideIcon;
  badge?: number;
}

export enum CustomerStatus {
  ACTIVE = 'Active',
  BLOCKED = 'Blocked',
  DELETED = 'Deleted'
}

export interface CustomField {
  key: string;
  value: string;
}

// Dynamic Data Configuration
export interface FieldDefinition {
  id: string;
  label: string;
  type: 'text' | 'date' | 'number' | 'select';
  options?: string[]; // Comma separated options for select
  required?: boolean;
}

export interface FieldGroup {
  id: string;
  title: string;
  fields: FieldDefinition[];
}

// --- FORM GENERATOR TYPES ---

export type FormElementSource = 'linked' | 'custom';
export type FormElementType = 'text' | 'number' | 'date' | 'select' | 'multiselect' | 'radio' | 'textarea' | 'checkbox' | 'file';
export type FormElementWidth = 'full' | 'half' | 'third';

export interface FormElement {
  id: string; // Unique ID within the form
  label: string;
  type: FormElementType;
  source: FormElementSource;
  width: FormElementWidth;
  
  // If source is 'linked', this points to the FieldDefinition id or standard field key
  linkedFieldId?: string; 
  linkedGroupId?: string; // If null, it's a standard field

  required: boolean;
  options?: string[]; // For custom selects, radios, multiselects
  
  // Info / Help Content
  infoText?: string;
  infoImage?: string;
}

export interface FormTemplate {
  id: string;
  name: string;
  description?: string;
  elements: FormElement[];
  active: boolean;
}

// ---------------------------

export type AppointmentStatus = 'Confirmed' | 'Pending' | 'Cancelled' | 'Completed' | 'No-Show';

export interface Appointment {
  id: string;
  title: string;
  customerId: string;
  customerName: string;
  employeeId: string;
  employeeName: string;
  serviceId: string;
  serviceName: string;
  category: string;
  date: string; // YYYY-MM-DD
  startTime: string; // HH:mm
  endTime: string; // HH:mm
  duration: number; // minutes
  type: 'Service' | 'Course' | 'Event';
  location: string;
  status: AppointmentStatus;
  notes?: string;
  price: number;
  originalPrice?: number; // To show dynamic pricing effect
  dynamicPricingLabel?: string; // e.g. "Early Bird"
}

export interface Booking {
  id: string;
  serviceName: string;
  date: string;
  time: string;
  status: 'Confirmed' | 'Pending' | 'Cancelled' | 'Completed';
  price: number;
}

export interface Customer {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  status: CustomerStatus;
  bookings?: Booking[]; // Deprecated: Use global appointments filter instead
  // Address Fields Split
  street?: string;
  zip?: string;
  city?: string;
  country?: string;
  
  birthday?: string;
  gender?: 'Male' | 'Female' | 'Other' | 'Prefer not to say';
  notes?: string;
  customFields?: CustomField[];
  earnedBadges?: string[]; 
}

export enum EmployeeStatus {
  ACTIVE = 'Active',
  VACATION = 'Vacation',
  SICK_LEAVE = 'Sick Leave',
  PAUSE = 'Pause',
  TERMINATED = 'Terminated'
}

export interface Employee {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  gender: 'Male' | 'Female' | 'Other';
  birthday: string;
  description?: string; 
  avatar?: string;
  position: string;
  department: string;
  role?: string; 
  hireDate: string;
  exitDate?: string;
  status: EmployeeStatus;
  hubPassword?: string; 
  badgeId?: string;
  notes?: string;
  address: string;
  zip: string;
  city: string;
  country: string;
  assignedServices: string[]; 
}

export type TimeEntryType = 'Work' | 'Break' | 'Meeting' | 'Travel';

export interface TimeEntry {
  id: string;
  employeeId: string;
  date: string; 
  startTime: string; 
  endTime?: string; 
  type: TimeEntryType;
  notes?: string;
  status: 'Pending' | 'Approved' | 'Rejected';
}

export type ShiftType = 'Early' | 'Late' | 'Night' | 'FullDay' | 'Off';

export interface WorkShift {
  id: string;
  employeeId: string;
  date: string;
  type: ShiftType;
  startTime: string;
  endTime: string;
  locationId?: string;
  notes?: string;
}

export interface ShiftTemplate {
  id: string;
  name: string; 
  shifts: Record<string, { type: ShiftType; start: string; end: string }>; 
}

export type AbsenceType = 'Vacation' | 'Sick' | 'NBU' | 'BU' | 'Maternity' | 'Unpaid' | 'Other';

export interface AbsenceRequest {
  id: string;
  employeeId: string;
  employeeName: string;
  type: AbsenceType;
  startDate: string;
  endDate: string;
  reason?: string;
  status: 'Pending' | 'Approved' | 'Rejected';
  requestedOn: string;
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

export interface Invoice {
  id: string;
  client: string; 
  category: 'Customer' | 'Supplier'; 
  amount: number;
  currency?: string;
  date: string;
  dueDate: string;
  status: InvoiceStatus;
  type: 'Invoice' | 'Credit Note' | 'Expense';
  dunningLevel: DunningLevel;
  items?: { desc: string; qty: number; price: number }[];
}

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

export interface VatRate {
  id: string;
  code: string; 
  description: string;
  rate: number; 
  type: VatTaxType; 
  formCode?: string; 
  linkedAccountId?: string;
  validFrom: string;
  validTo?: string;
  active: boolean;
}

export interface FiscalYear {
  id: string;
  name: string; 
  startDate: string;
  endDate: string;
  status: 'Open' | 'Closed';
  isCurrent: boolean;
}

export interface InvoiceTemplate {
  id: string;
  name: string;
  isDefault: boolean;
  
  // Visuals
  logoUrl?: string;
  accentColor: string;
  fontFamily: string;

  // Layout
  addressWindowPosition: 'Left' | 'Right';
  
  // Content
  senderLine: string; // Small line above address
  senderBlock: string; // Full address block (top right usually)
  
  introText: string;
  outroText: string;
  
  // Footer
  footerColumn1: string; // e.g. Bank Details
  footerColumn2: string; // e.g. Registry / VAT
  footerColumn3: string; // e.g. Contact
}

export interface CompanySettings {
  name: string;
  email: string;
  phone: string;
  address: string;
  zip: string;
  city: string;
  country: string;
  logoUrl?: string;
  vatId?: string;
  commercialRegisterId?: string;
  bankName?: string;
  iban?: string;
  qrIban?: string; // Swiss QR-Bill Specific
  besrId?: string; // Legacy BESR-ID (Optional)
  qrReferenceType?: 'QR' | 'SCOR' | 'NON'; // QR-Reference, Creditor Reference, or None
  bic?: string;
}

export interface Account {
  id: string;
  code: string; 
  name: string; 
  group?: string; 
  type: 'Asset' | 'Liability' | 'Equity' | 'Revenue' | 'Expense';
  defaultTaxRateId?: string;
  balance: number;
}

export interface PayrollRun {
  id: string;
  employeeId: string;
  employeeName: string;
  period: string;
  grossSalary: number;
  deductions: number;
  netSalary: number;
  status: 'Draft' | 'Approved' | 'Paid';
}

export interface SoldVoucher {
  id: string;
  code: string;
  purchaseDate: string;
  purchaser: string;
  originalAmount: number;
  usedAmount: number;
  remainingBalance: number;
  status: 'Active' | 'Redeemed' | 'Expired';
}

export interface Partner {
  id: string;
  companyName: string;
  type: 'Reseller' | 'Service Provider';
  status: 'Active' | 'Pending' | 'Suspended';
  gdprSigned: boolean;
  revenueShare: number;
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

export interface Question {
  id: string;
  text: string;
  type: QuestionType;
  points: number;
  options?: string[]; 
  correctAnswer?: string | string[] | number;
  mediaUrl?: string;
}

export interface QuizSettings {
  allowedAttempts: number; 
  passingScore: number; 
  questionsToShow?: number; 
  shuffleQuestions: boolean;
  layout: 'Single Page' | 'One per page';
  feedbackMode: 'Immediate' | 'End of Quiz';
}

export interface Quiz {
  id: string;
  type: 'quiz';
  title: string;
  summary: string;
  questions: Question[];
  settings: QuizSettings;
}

export interface Lesson {
  id: string;
  type: 'lesson';
  title: string;
  content: string; 
  mediaUrls: string[]; 
  fileAttachments: string[];
}

export interface Topic {
  id: string;
  title: string;
  summary: string;
  items: (Lesson | Quiz)[];
}

export interface CertificateSettings {
  enabled: boolean;
  templateId: string;
  validityMonths?: number;
  showScore: boolean;
  signatureText: string;
}

export interface Badge {
  id: string;
  name: string;
  color: string;
  icon: string; 
  description?: string;
}

export interface Course {
  id: string;
  title: string;
  description: string;
  type: CourseType;
  author: string;
  maxParticipants?: number;
  visibility: CourseVisibility;
  startDate?: string;
  endDate?: string;
  category: string;
  tags: string[];
  difficulty: DifficultyLevel;
  coverImage: string;
  introVideoUrl?: string;
  curriculum: Topic[];
  certificate: CertificateSettings;
  awardedBadgeId?: string; 
  studentsCount: number;
  published: boolean;
}

export interface OfferCategory {
  id: string;
  name: string;
  color: string;
  image?: string;
  description?: string;
}

export interface OfferTag {
  id: string;
  name: string;
  color: string;
}

export interface OfferExtra {
  id: string;
  name: string;
  description?: string;
  price: number;
  priceType: 'Fixed' | 'Percentage';
  currency?: string;
}

export interface EventSession {
    id: string;
    date: string;
    startTime: string;
    endTime: string;
    instructorId: string; 
    locationId: string;
    capacity?: number; 
    title?: string;
    description?: string;
    awardedBadges?: string[]; 
    requiredBadges?: string[]; 
    linkedLessonId?: string;
}

export type PricingStrategyType = 'EarlyBird' | 'LastMinute' | 'Occupancy' | 'Demand' | 'Season' | 'History';

export interface PricingTier {
    id: string;
    conditionValue: number; // e.g. 10 
    conditionUnit?: 'Months' | 'Weeks' | 'Days' | 'Hours' | 'Percent' | 'Bookings'; // Time or Capacity/Velocity
    adjustmentValue: number;
    adjustmentType: 'Percentage' | 'FixedAmount';
    limitValue?: number; 
    limitMetric?: 'CapacityPercent' | 'BookingsCount';
    occupancyLimit?: number; // For EarlyBird: "Until X% Capacity" - Legacy
    occupancyTrigger?: number; // For LastMinute: "From X% Capacity" - Legacy
}

export interface TimeSlot {
    startTime: string;
    endTime: string;
    adjustmentValue: number;
    adjustmentType: 'Percentage' | 'FixedAmount';
}

export interface DaySchedule {
    active: boolean;
    slots: TimeSlot[];
}

export interface SeasonalRule {
    id: string;
    type: 'Range' | 'SpecificDate';
    startDate?: string; 
    endDate?: string; 
    specificDate?: string; 
    
    // For SpecificDate (simple)
    startTime?: string; 
    endTime?: string; 
    adjustmentValue?: number;
    adjustmentType?: 'Percentage' | 'FixedAmount';
    labelType?: 'Holiday' | 'SpecialDay';

    // For Range (complex)
    dayConfigs?: Record<string, DaySchedule>; // Key: 'Mon', 'Tue', etc.

    name?: string; 
}

export interface DynamicPricingRule {
    id: string;
    name: string;
    type: PricingStrategyType;
    active: boolean;
    
    roundingValue: number; 
    roundingMethod?: 'Nearest' | 'Up' | 'Down';
    priceEnding?: 'None' | '.99' | '.95' | '.49'; 
    
    // Safety Limits (Percentage only)
    maxIncreasePercent?: number; // Cap increase (e.g. max +50%)
    maxDecreasePercent?: number; // Floor decrease (e.g. max -30%)

    // AI / Model Settings
    aggressiveness?: 'Mild' | 'Neutral' | 'Aggressive';

    tiers?: PricingTier[]; 
    seasonalRules?: SeasonalRule[]; 

    demandConfig?: {
        velocityThreshold: number; 
        lookbackHours: number; // "Short term past"
        priceIncreasePercent: number; 
        cooldownHours: number; 
    };
}

export interface Location {
  id: string;
  name: string;
  address: string;
  rooms: number;
  status: 'Open' | 'Closed';
}

export interface Room {
  id: string;
  name: string;
  location: string;
  capacity: number;
  features: string[];
  status: 'Available' | 'In Use' | 'Maintenance';
}

export interface Equipment {
  id: string;
  name: string;
  category: string;
  total: number;
  available: number;
  condition: 'Good' | 'Fair' | 'Poor';
}

export interface SystemAlert {
  id: string;
  type: 'warning' | 'info' | 'error';
  title: string;
  message: string;
  timestamp: string;
  acknowledged: boolean;
  relatedCustomerId?: string;
}

export interface ModulePermission {
  read: boolean;
  write: boolean;
  delete: boolean;
}

export interface Role {
  id: string;
  name: string;
  description?: string;
  permissions: Record<string, ModulePermission>; 
}

export type OfferType = 'Service' | 'Online Course' | 'Event';
export type VoucherCategory = 'Promotion' | 'GiftCard';
export type VoucherType = 'Percentage' | 'Fixed';
export type PaymentOption = 'On Site' | 'Credit Card' | 'Paypal' | 'Invoice' | 'Insurance';
export type EventStructure = 'Single' | 'Series_All' | 'Series_DropIn'; 

export interface ServiceItem {
  id: string;
  title: string;
  description: string;
  category: string; // Primary category ID or Name
  categories?: string[]; // Multi-select categories
  tags: string[];
  image: string;
  type: OfferType;
  active: boolean;
  
  productCode?: string;
  externalProductCode?: string;

  price: number;
  currency: string;
  salePrice?: number;
  dynamicPricing?: 'Auto' | 'Manual' | 'Off';
  pricingRuleId?: string;
  paymentOptions?: PaymentOption[];
  
  vatEnabled: boolean;
  vatRateSales?: number;
  vatRatePurchase?: number;

  duration?: number;
  bufferBefore?: number;
  bufferAfter?: number;
  capacity?: number;

  requiredLocations?: string[];
  requiredRooms?: string[];
  requiredEquipment?: string[];
  integration?: 'None' | 'Google Meet' | 'Zoom' | 'Microsoft Teams';

  isRecurring?: boolean;
  eventStructure?: EventStructure;
  sharedCapacity?: boolean;
  sessions?: EventSession[];
  organizerId?: string;
  
  formTemplateId?: string;
  defaultStatus?: 'Confirmed' | 'Pending';
  notifications?: {
      confirmation: string;
      reminder: string;
      reminderEnabled: boolean;
  };

  minNotice?: { value: number, unit: 'minutes'|'hours'|'days', type: 'booking' | 'cancel' | 'reschedule' }[];
  noticeChange?: { value: number, unit: 'minutes'|'hours'|'days' }; // Before changes
  
  customerLimits?: { count: number, period: 'day'|'month'|'year' };
  
  minParticipants?: number;
  allowGroupBooking?: boolean;
  maxGroupSize?: number;
  allowMultipleBookings?: boolean;
  
  waitlistEnabled?: boolean;
  waitlistCapacity?: number;

  allowedExtras?: string[]; // IDs of OfferExtras
  customerSelectableResources?: string[]; // IDs of rooms/resources customers can pick
  customerSelectableExtras?: string[]; // Explicit customer-visible extras selection

  gallery?: string[];
  lessons?: number;
}

export interface BundleItem {
  id: string;
  title: string;
  items: string[];
  price: number;
  originalPrice: number;
  savings: number;
  image: string;
  active: boolean;
}

export interface VoucherItem {
  id: string;
  title: string;
  category: VoucherCategory;
  status: 'Active' | 'Expired' | 'Depleted';
  expiry?: string;
  image?: string;
  code?: string;
  discountType?: VoucherType;
  discountValue?: number;
  uses?: number;
  maxUses?: number | null;
  maxUsesPerCustomer?: number | null;
  allowCustomAmount?: boolean;
  minCustomAmount?: number;
  maxCustomAmount?: number;
  fixedValue?: number;
  currentBalance?: number;
}