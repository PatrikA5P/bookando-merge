/**
 * API Types - Generated from Prisma Schema
 * These types match the backend database schema exactly
 * Use these for API communication
 */

// ============================================
// ENUMS (synced with Prisma)
// ============================================

export enum UserStatus {
  ACTIVE = 'ACTIVE',
  INACTIVE = 'INACTIVE',
  SUSPENDED = 'SUSPENDED'
}

export enum CustomerStatus {
  ACTIVE = 'ACTIVE',
  INACTIVE = 'INACTIVE',
  ARCHIVED = 'ARCHIVED'
}

export enum EmployeeStatus {
  ACTIVE = 'ACTIVE',
  ON_LEAVE = 'ON_LEAVE',
  INACTIVE = 'INACTIVE'
}

export enum TimeEntryType {
  WORK = 'WORK',
  BREAK = 'BREAK',
  SICK = 'SICK',
  VACATION = 'VACATION'
}

export enum TimeEntryStatus {
  PENDING = 'PENDING',
  APPROVED = 'APPROVED',
  REJECTED = 'REJECTED'
}

export enum ShiftType {
  EARLY = 'EARLY',
  LATE = 'LATE',
  NIGHT = 'NIGHT',
  OFF = 'OFF'
}

export enum AbsenceType {
  VACATION = 'VACATION',
  SICK = 'SICK',
  PERSONAL = 'PERSONAL',
  OTHER = 'OTHER'
}

export enum AbsenceStatus {
  PENDING = 'PENDING',
  APPROVED = 'APPROVED',
  REJECTED = 'REJECTED'
}

export enum CourseType {
  ONLINE = 'ONLINE',
  IN_PERSON = 'IN_PERSON',
  BLENDED = 'BLENDED'
}

export enum CourseVisibility {
  PUBLIC = 'PUBLIC',
  PRIVATE = 'PRIVATE',
  INTERNAL = 'INTERNAL'
}

export enum SessionStatus {
  SCHEDULED = 'SCHEDULED',
  FULL = 'FULL',
  CANCELLED = 'CANCELLED',
  COMPLETED = 'COMPLETED'
}

export enum ServiceType {
  SERVICE = 'SERVICE',
  ONLINE_COURSE = 'ONLINE_COURSE',
  EVENT = 'EVENT'
}

export enum ExtraPriceType {
  FIXED = 'FIXED',
  PERCENTAGE = 'PERCENTAGE'
}

export enum PricingRuleType {
  EARLY_BIRD = 'EARLY_BIRD',
  LAST_MINUTE = 'LAST_MINUTE',
  SEASONAL = 'SEASONAL',
  DEMAND = 'DEMAND',
  HISTORY = 'HISTORY'
}

export enum BookingStatus {
  PENDING = 'PENDING',
  CONFIRMED = 'CONFIRMED',
  PAID = 'PAID',
  COMPLETED = 'COMPLETED',
  CANCELLED = 'CANCELLED',
  NO_SHOW = 'NO_SHOW'
}

export enum PaymentStatus {
  UNPAID = 'UNPAID',
  PARTIAL = 'PARTIAL',
  PAID = 'PAID',
  REFUNDED = 'REFUNDED'
}

export enum InvoiceStatus {
  DRAFT = 'DRAFT',
  SENT = 'SENT',
  PAID = 'PAID',
  OVERDUE = 'OVERDUE',
  CANCELLED = 'CANCELLED',
  PARTIAL = 'PARTIAL'
}

export enum LicenseTier {
  STARTER = 'STARTER',
  PROFESSIONAL = 'PROFESSIONAL',
  ENTERPRISE = 'ENTERPRISE'
}

export enum LicenseStatus {
  ACTIVE = 'ACTIVE',
  TRIAL = 'TRIAL',
  SUSPENDED = 'SUSPENDED',
  CANCELLED = 'CANCELLED',
  EXPIRED = 'EXPIRED'
}

export enum BillingCycle {
  MONTHLY = 'MONTHLY',
  YEARLY = 'YEARLY'
}

export enum DevicePlatform {
  IOS = 'IOS',
  ANDROID = 'ANDROID',
  WEB = 'WEB'
}

// ============================================
// API TYPES (synced with Prisma models)
// ============================================

export interface Organization {
  id: string;
  name: string;
  subdomain?: string;
  email: string;
  phone?: string;
  address?: string;
  city?: string;
  zip?: string;
  country: string;
  language: string;
  timezone: string;
  currency: string;
  licenseId?: string;
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  organizationId: string;
  roleId: string;
  status: UserStatus;
  lastLogin?: Date | string;
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface Role {
  id: string;
  name: string;
  permissions: ModulePermissions; // JSON
}

export interface ModulePermissions {
  [moduleName: string]: {
    read: boolean;
    write: boolean;
    delete: boolean;
  };
}

export interface Customer {
  id: string;
  organizationId: string;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  address?: string;
  zip?: string;
  city?: string;
  country?: string;
  birthday?: string;
  gender?: string;
  customFields?: any; // JSON
  status: CustomerStatus;
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface Employee {
  id: string;
  organizationId: string;
  userId: string;
  position: string;
  department?: string;
  hireDate: string;
  exitDate?: string;
  skills: string[];
  qualifications: string[];
  status: EmployeeStatus;
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface TimeEntry {
  id: string;
  employeeId: string;
  date: string;
  startTime: string;
  endTime?: string;
  type: TimeEntryType;
  status: TimeEntryStatus;
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface Shift {
  id: string;
  employeeId: string;
  date: string;
  type: ShiftType;
  startTime: string;
  endTime: string;
  createdAt: Date | string;
}

export interface Absence {
  id: string;
  employeeId: string;
  type: AbsenceType;
  startDate: string;
  endDate: string;
  reason?: string;
  status: AbsenceStatus;
  requestedAt: Date | string;
}

export interface Course {
  id: string;
  organizationId: string;
  title: string;
  description?: string;
  coverImage?: string;
  type: CourseType;
  visibility: CourseVisibility;
  category: string;
  curriculum: any; // JSON - Array of Topics
  certificate: boolean;
  certificateTemplate?: string;
  duration?: number;
  difficulty?: string;
  tags: string[];
  published: boolean;
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface CourseSession {
  id: string;
  courseId: string;
  date: string;
  startTime: string;
  endTime: string;
  instructorId?: string;
  locationId?: string;
  roomId?: string;
  maxParticipants: number;
  currentEnrollment: number;
  status: SessionStatus;
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface Enrollment {
  id: string;
  customerId: string;
  courseId: string;
  progress: any; // JSON
  completed: boolean;
  completedAt?: Date | string;
  certificateIssued: boolean;
  certificateUrl?: string;
  enrolledAt: Date | string;
}

export interface EducationCard {
  id: string;
  title: string;
  description?: string;
  chapters: any; // JSON
  gradingConfig: any; // JSON
  automation: any; // JSON
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface Service {
  id: string;
  organizationId: string;
  title: string;
  description?: string;
  type: ServiceType;
  price: number;
  currency: string;
  duration?: number;
  capacity?: number;
  minNoticeHours?: number;
  maxAdvanceDays?: number;
  dynamicPricing: boolean;
  pricingRuleId?: string;
  linkedCourseId?: string;
  linkedEducationCardId?: string;
  formTemplateId?: string;
  categoryId?: string;
  active: boolean;
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface Category {
  id: string;
  name: string;
  description?: string;
  color?: string;
  image?: string;
}

export interface ServiceExtra {
  id: string;
  serviceId: string;
  name: string;
  description?: string;
  price: number;
  priceType: ExtraPriceType;
}

export interface PricingRule {
  id: string;
  name: string;
  type: PricingRuleType;
  config: any; // JSON
}

export interface FormTemplate {
  id: string;
  name: string;
  description?: string;
  elements: any[]; // JSON
  active: boolean;
}

export interface Booking {
  id: string;
  bookingNumber: string;
  organizationId: string;
  customerId: string;
  serviceId: string;
  sessionId?: string;
  scheduledDate: string;
  scheduledTime: string;
  basePrice: number;
  appliedPricing?: any; // JSON
  extras: any[]; // JSON
  totalPrice: number;
  employeeId?: string;
  resourceAllocation?: any; // JSON
  formResponses: any[]; // JSON
  status: BookingStatus;
  paymentStatus: PaymentStatus;
  invoiceId?: string;
  createdAt: Date | string;
  confirmedAt?: Date | string;
  paidAt?: Date | string;
  completedAt?: Date | string;
  cancelledAt?: Date | string;
}

export interface Invoice {
  id: string;
  invoiceNumber: string;
  organizationId: string;
  customerId: string;
  amount: number;
  vatRate?: number;
  vatAmount?: number;
  totalAmount: number;
  currency: string;
  status: InvoiceStatus;
  dueDate: string;
  paidAt?: Date | string;
  dunningLevel: number;
  lastReminderAt?: Date | string;
  items: any[]; // JSON
  createdAt: Date | string;
  updatedAt: Date | string;
}

export interface Location {
  id: string;
  organizationId: string;
  name: string;
  address?: string;
  city?: string;
  zip?: string;
  country?: string;
}

export interface Room {
  id: string;
  locationId: string;
  name: string;
  capacity: number;
  equipment: string[];
}

export interface License {
  id: string;
  tier: LicenseTier;
  status: LicenseStatus;
  platforms: any; // JSON
  enabledModules: any; // JSON
  limits: any; // JSON
  features: any; // JSON
  validFrom: Date | string;
  validUntil: Date | string;
  billingCycle: BillingCycle;
  price: number;
  currency: string;
  createdAt: Date | string;
  updatedAt: Date | string;
}

// ============================================
// API REQUEST/RESPONSE TYPES
// ============================================

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  user: User & {
    organization: Organization;
    role: Role;
  };
  token: string;
  refreshToken: string;
}

export interface CreateCustomerRequest {
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  address?: string;
  zip?: string;
  city?: string;
  country?: string;
  birthday?: string;
  gender?: string;
  customFields?: any;
}

export interface CreateBookingRequest {
  customerId?: string;
  customerEmail?: string;
  serviceId: string;
  courseSessionId?: string;
  scheduledDate: string;
  scheduledTime: string;
  formResponses: any[];
  extras: { extraId: string; quantity: number }[];
}

export interface CreateBookingResponse {
  booking: Booking;
  invoice: Invoice;
}

// ============================================
// HELPER TYPES
// ============================================

export interface ApiError {
  error: string;
  field?: string;
  code?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  pageSize: number;
  totalPages: number;
}

export interface ApiResponse<T> {
  data?: T;
  error?: ApiError;
  success: boolean;
}
