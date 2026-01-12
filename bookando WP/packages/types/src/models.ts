/**
 * @bookando/types - Models
 * Shared data models for the Bookando platform with Multi-Tenant support
 */

import type { BaseEntity, CustomField } from './base';
import type {
  CustomerStatus,
  EmployeeStatus,
  AppointmentStatus,
  InvoiceStatus,
  DunningLevel,
  TimeEntryType,
  ShiftType,
  AbsenceType,
  CourseType,
  CourseVisibility,
  DifficultyLevel,
  QuestionType,
  OfferType,
  VoucherCategory,
  VoucherType,
  PaymentOption,
  EventStructure,
  PricingStrategyType,
  VatTaxType
} from './enums';

// ==================== CUSTOMERS ====================

export interface Customer extends BaseEntity {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  status: CustomerStatus;
  address?: string;
  birthday?: string;
  gender?: 'Male' | 'Female' | 'Other' | 'Prefer not to say';
  notes?: string;
  customFields?: CustomField[];
  earnedBadges?: string[];
}

export interface Booking {
  id: string;
  tenantId: number;
  serviceName: string;
  date: string;
  time: string;
  status: 'Confirmed' | 'Pending' | 'Cancelled' | 'Completed';
  price: number;
}

// ==================== EMPLOYEES ====================

export interface Employee extends BaseEntity {
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

export interface TimeEntry extends BaseEntity {
  employeeId: string;
  date: string;
  startTime: string;
  endTime?: string;
  type: TimeEntryType;
  notes?: string;
  status: 'Pending' | 'Approved' | 'Rejected';
}

export interface WorkShift extends BaseEntity {
  employeeId: string;
  date: string;
  type: ShiftType;
  startTime: string;
  endTime: string;
  locationId?: string;
  notes?: string;
}

export interface ShiftTemplate extends BaseEntity {
  name: string;
  shifts: Record<string, { type: ShiftType; start: string; end: string }>;
}

export interface AbsenceRequest extends BaseEntity {
  employeeId: string;
  employeeName: string;
  type: AbsenceType;
  startDate: string;
  endDate: string;
  reason?: string;
  status: 'Pending' | 'Approved' | 'Rejected';
  requestedOn: string;
}

// ==================== APPOINTMENTS ====================

export interface Appointment extends BaseEntity {
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
  originalPrice?: number;
  dynamicPricingLabel?: string;
}

// ==================== FINANCE ====================

export interface Invoice extends BaseEntity {
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

export interface VatRate extends BaseEntity {
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

export interface FiscalYear extends BaseEntity {
  name: string;
  startDate: string;
  endDate: string;
  status: 'Open' | 'Closed';
  isCurrent: boolean;
}

export interface InvoiceTemplate extends BaseEntity {
  name: string;
  isDefault: boolean;
  logoUrl?: string;
  accentColor: string;
  fontFamily: string;
  addressWindowPosition: 'Left' | 'Right';
  senderLine: string;
  senderBlock: string;
  introText: string;
  outroText: string;
  footerColumn1: string;
  footerColumn2: string;
  footerColumn3: string;
}

export interface Account extends BaseEntity {
  code: string;
  name: string;
  group?: string;
  type: 'Asset' | 'Liability' | 'Equity' | 'Revenue' | 'Expense';
  defaultTaxRateId?: string;
  balance: number;
}

export interface PayrollRun extends BaseEntity {
  employeeId: string;
  employeeName: string;
  period: string;
  grossSalary: number;
  deductions: number;
  netSalary: number;
  status: 'Draft' | 'Approved' | 'Paid';
}

export interface SoldVoucher extends BaseEntity {
  code: string;
  purchaseDate: string;
  purchaser: string;
  originalAmount: number;
  usedAmount: number;
  remainingBalance: number;
  status: 'Active' | 'Redeemed' | 'Expired';
}

// ==================== SETTINGS ====================

export interface CompanySettings extends BaseEntity {
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
  qrIban?: string;
  besrId?: string;
  qrReferenceType?: 'QR' | 'SCOR' | 'NON';
  bic?: string;
}

export interface ModulePermission {
  read: boolean;
  write: boolean;
  delete: boolean;
}

export interface Role extends BaseEntity {
  name: string;
  description?: string;
  permissions: Record<string, ModulePermission>;
}

export interface SystemAlert extends BaseEntity {
  type: 'warning' | 'info' | 'error';
  title: string;
  message: string;
  timestamp: string;
  acknowledged: boolean;
  relatedCustomerId?: string;
}

// ==================== PARTNERS ====================

export interface Partner extends BaseEntity {
  companyName: string;
  type: 'Reseller' | 'Service Provider';
  status: 'Active' | 'Pending' | 'Suspended';
  gdprSigned: boolean;
  revenueShare: number;
}

// ==================== RESOURCES ====================

export interface Location extends BaseEntity {
  name: string;
  address: string;
  rooms: number;
  status: 'Open' | 'Closed';
}

export interface Room extends BaseEntity {
  name: string;
  location: string;
  capacity: number;
  features: string[];
  status: 'Available' | 'In Use' | 'Maintenance';
}

export interface Equipment extends BaseEntity {
  name: string;
  category: string;
  total: number;
  available: number;
  condition: 'Good' | 'Fair' | 'Poor';
}
