/**
 * BOOKANDO SHARED API TYPES
 *
 * Gemeinsame TypeScript-Typen die sowohl vom SaaS-Frontend
 * als auch vom WordPress-Plugin verwendet werden.
 *
 * Diese Typen spiegeln die Domain-Objekte der Software Foundation
 * und definieren die API-Verträge zwischen Frontend und Backend.
 */

// ============================================================================
// ENUMS
// ============================================================================

export enum AppointmentStatus {
  PENDING = 'PENDING',
  CONFIRMED = 'CONFIRMED',
  COMPLETED = 'COMPLETED',
  CANCELLED = 'CANCELLED',
  NO_SHOW = 'NO_SHOW',
}

export enum CustomerStatus {
  ACTIVE = 'ACTIVE',
  BLOCKED = 'BLOCKED',
  DELETED = 'DELETED',
}

export enum EmployeeStatus {
  ACTIVE = 'ACTIVE',
  VACATION = 'VACATION',
  SICK_LEAVE = 'SICK_LEAVE',
  PAUSE = 'PAUSE',
  TERMINATED = 'TERMINATED',
}

export enum InvoiceStatus {
  DRAFT = 'DRAFT',
  SENT = 'SENT',
  PAID = 'PAID',
  OVERDUE = 'OVERDUE',
  CANCELLED = 'CANCELLED',
}

export enum ServiceType {
  SERVICE = 'SERVICE',
  EVENT = 'EVENT',
  ONLINE_COURSE = 'ONLINE_COURSE',
}

export enum PaymentMethod {
  CASH = 'CASH',
  CARD = 'CARD',
  INVOICE = 'INVOICE',
  ONLINE = 'ONLINE',
  TWINT = 'TWINT',
}

export enum DossierType {
  PERSONNEL = 'PERSONNEL',
  ORDER = 'ORDER',
  COURSE_DATA = 'COURSE_DATA',
  APPLICATION = 'APPLICATION',
  CONTRACT = 'CONTRACT',
  CORRESPONDENCE = 'CORRESPONDENCE',
  FINANCIAL = 'FINANCIAL',
  TIME_TRACKING = 'TIME_TRACKING',
  MEDICAL = 'MEDICAL',
}

// ============================================================================
// BASE TYPES
// ============================================================================

export interface BaseEntity {
  id: string;
  createdAt: string;
  updatedAt: string;
}

export interface TenantEntity extends BaseEntity {
  tenantId: number;
}

// ============================================================================
// DOMAIN ENTITIES
// ============================================================================

export interface Customer extends TenantEntity {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  status: CustomerStatus;
  street?: string;
  zip?: string;
  city?: string;
  country?: string;
  birthday?: string;
  gender?: string;
  notes?: string;
  customFields?: Record<string, string>;
  tags?: string[];
}

export interface Employee extends TenantEntity {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  status: EmployeeStatus;
  position: string;
  department: string;
  hireDate: string;
  exitDate?: string;
  role: string;
  avatar?: string;
  assignedServiceIds: string[];
}

export interface Appointment extends TenantEntity {
  customerId: string;
  employeeId: string;
  serviceId: string;
  locationId?: string;
  roomId?: string;
  date: string;
  startTime: string;
  endTime: string;
  duration: number;
  status: AppointmentStatus;
  priceMinor: number;
  currency: string;
  notes?: string;
  dynamicPricingApplied?: boolean;
}

export interface ServiceItem extends TenantEntity {
  title: string;
  description: string;
  type: ServiceType;
  categoryId?: string;
  priceMinor: number;
  salePriceMinor?: number;
  currency: string;
  duration: number;
  active: boolean;
  image?: string;
  tags?: string[];
  pricingRuleId?: string;
}

export interface Invoice extends TenantEntity {
  number: string;
  customerId: string;
  status: InvoiceStatus;
  issueDate: string;
  dueDate: string;
  totalMinor: number;
  taxMinor: number;
  currency: string;
  lineItems: InvoiceLineItem[];
  paymentMethod?: PaymentMethod;
  dunningLevel: number;
  qrReference?: string;
}

export interface InvoiceLineItem {
  id: string;
  description: string;
  quantity: number;
  unitPriceMinor: number;
  totalMinor: number;
  vatRatePercent: number;
}

// ============================================================================
// API RESPONSE TYPES
// ============================================================================

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    page: number;
    perPage: number;
    total: number;
    totalPages: number;
  };
}

export interface ValidationError {
  field: string;
  message: string;
  rule: string;
}

export interface ApiErrorResponse {
  message: string;
  errors?: ValidationError[];
  status: number;
}
