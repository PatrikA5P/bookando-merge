/**
 * @bookando/types - Offers Models
 * Types for services, bundles, vouchers, and dynamic pricing
 */

import type { BaseEntity } from './base';
import type {
  OfferType,
  VoucherCategory,
  VoucherType,
  PaymentOption,
  EventStructure,
  PricingStrategyType
} from './enums';

export interface OfferTag extends BaseEntity {
  name: string;
  color: string;
}

export interface OfferExtra extends BaseEntity {
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
  startTime?: string;
  endTime?: string;
  adjustmentValue?: number;
  adjustmentType?: 'Percentage' | 'FixedAmount';
  labelType?: 'Holiday' | 'SpecialDay';
  dayConfigs?: Record<string, DaySchedule>;
  name?: string;
}

export interface PricingTier {
  id: string;
  conditionValue: number;
  conditionUnit?: 'Months' | 'Weeks' | 'Days' | 'Hours' | 'Percent' | 'Bookings';
  adjustmentValue: number;
  adjustmentType: 'Percentage' | 'FixedAmount';
  limitValue?: number;
  limitMetric?: 'CapacityPercent' | 'BookingsCount';
  occupancyLimit?: number;
  occupancyTrigger?: number;
}

export interface DynamicPricingRule extends BaseEntity {
  name: string;
  type: PricingStrategyType;
  active: boolean;
  roundingValue: number;
  roundingMethod?: 'Nearest' | 'Up' | 'Down';
  priceEnding?: 'None' | '.99' | '.95' | '.49';
  maxIncreasePercent?: number;
  maxDecreasePercent?: number;
  aggressiveness?: 'Mild' | 'Neutral' | 'Aggressive';
  tiers?: PricingTier[];
  seasonalRules?: SeasonalRule[];
  demandConfig?: {
    velocityThreshold: number;
    lookbackHours: number;
    priceIncreasePercent: number;
    cooldownHours: number;
  };
}

export interface ServiceItem extends BaseEntity {
  title: string;
  description: string;
  category: string;
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
  minNotice?: {
    value: number;
    unit: 'minutes' | 'hours' | 'days';
    type: 'booking' | 'cancel' | 'reschedule';
  }[];
  customerLimits?: {
    count: number;
    period: 'day' | 'month' | 'year';
  };
  minParticipants?: number;
  allowGroupBooking?: boolean;
  maxGroupSize?: number;
  allowMultipleBookings?: boolean;
  waitlistEnabled?: boolean;
  waitlistCapacity?: number;
  gallery?: string[];
  lessons?: number;
}

export interface BundleItem extends BaseEntity {
  title: string;
  items: string[];
  price: number;
  originalPrice: number;
  savings: number;
  image: string;
  active: boolean;
}

export interface VoucherItem extends BaseEntity {
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
