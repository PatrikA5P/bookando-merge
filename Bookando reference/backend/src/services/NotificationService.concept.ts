/**
 * NOTIFICATION SYSTEM - Konzept & Architektur
 *
 * Automatische Kommunikation via:
 * - Email (SMTP, SendGrid, AWS SES)
 * - SMS (Twilio, Nexmo)
 * - WhatsApp (WhatsApp Business API, Twilio)
 * - App-Notification (Push Notifications via Firebase, OneSignal)
 *
 * TRIGGER EVENTS:
 * 1. Buchungsbestätigung (booking:confirmed)
 * 2. Buchungsänderungen (booking:updated)
 * 3. Stornierungen (booking:cancelled)
 * 4. Erinnerungen (24h vor Termin, 1h vor Termin)
 * 5. Aftersale (Nach abgeschlossenem Kurs - Bewertung anfragen)
 * 6. Geburtstag (Happy Birthday Nachricht)
 * 7. Rechnung versandt (invoice:sent)
 * 8. Zahlung erhalten (invoice:paid)
 * 9. Mahnung (invoice:overdue)
 */

import { EventEmitter } from 'events';

export const notificationEvents = new EventEmitter();

/**
 * Notification Channels
 */
export enum NotificationChannel {
  EMAIL = 'EMAIL',
  SMS = 'SMS',
  WHATSAPP = 'WHATSAPP',
  PUSH = 'PUSH', // App Push Notifications
}

/**
 * Notification Event Types
 */
export enum NotificationEvent {
  // Booking Events
  BOOKING_CONFIRMED = 'BOOKING_CONFIRMED',
  BOOKING_UPDATED = 'BOOKING_UPDATED',
  BOOKING_CANCELLED = 'BOOKING_CANCELLED',
  BOOKING_REMINDER_24H = 'BOOKING_REMINDER_24H',
  BOOKING_REMINDER_1H = 'BOOKING_REMINDER_1H',

  // Invoice Events
  INVOICE_SENT = 'INVOICE_SENT',
  INVOICE_PAID = 'INVOICE_PAID',
  INVOICE_OVERDUE = 'INVOICE_OVERDUE',

  // Customer Events
  CUSTOMER_BIRTHDAY = 'CUSTOMER_BIRTHDAY',
  CUSTOMER_WELCOME = 'CUSTOMER_WELCOME',

  // Aftersale
  COURSE_COMPLETED_REVIEW = 'COURSE_COMPLETED_REVIEW',
}

/**
 * Notification Template
 * Templates stored in database with placeholders
 */
interface NotificationTemplate {
  id: string;
  organizationId: string;
  event: NotificationEvent;
  channel: NotificationChannel;
  subject?: string; // For email
  body: string; // Template with {{placeholders}}
  isActive: boolean;

  // Category/Service Filters
  serviceCategories?: string[]; // Apply to specific categories
  serviceIds?: string[]; // Apply to specific services
}

/**
 * Notification Settings per Organization
 */
interface NotificationSettings {
  organizationId: string;

  // Channel Configuration
  emailProvider?: 'SMTP' | 'SENDGRID' | 'AWS_SES';
  emailConfig?: any; // Provider-specific config

  smsProvider?: 'TWILIO' | 'NEXMO';
  smsConfig?: any;

  whatsappProvider?: 'WHATSAPP_BUSINESS' | 'TWILIO';
  whatsappConfig?: any;

  pushProvider?: 'FIREBASE' | 'ONESIGNAL';
  pushConfig?: any;

  // Event-specific settings
  enabledEvents: {
    [key in NotificationEvent]?: {
      enabled: boolean;
      channels: NotificationChannel[];
      delayMinutes?: number; // For reminders
    };
  };
}

/**
 * Notification Service (To Be Implemented)
 *
 * Responsibilities:
 * 1. Listen to booking/invoice events
 * 2. Check organization notification settings
 * 3. Load appropriate template
 * 4. Replace placeholders with actual data
 * 5. Send via configured channel(s)
 * 6. Log notification (audit trail)
 * 7. Handle failures and retries
 */
export class NotificationService {
  /**
   * Send notification for a specific event
   */
  async send(
    organizationId: string,
    event: NotificationEvent,
    data: any,
    recipientContact: {
      email?: string;
      phone?: string;
      pushToken?: string;
    }
  ): Promise<void> {
    // TODO: Implement
    // 1. Load organization notification settings
    // 2. Check if event is enabled
    // 3. Get configured channels for this event
    // 4. Load template for each channel
    // 5. Replace placeholders ({{customerName}}, {{bookingDate}}, etc.)
    // 6. Send via each channel
    // 7. Log result
  }

  /**
   * Send Email
   */
  private async sendEmail(to: string, subject: string, body: string, config: any): Promise<void> {
    // TODO: Implement with configured provider
    // - SMTP: nodemailer
    // - SendGrid: @sendgrid/mail
    // - AWS SES: aws-sdk
  }

  /**
   * Send SMS
   */
  private async sendSMS(to: string, message: string, config: any): Promise<void> {
    // TODO: Implement with configured provider
    // - Twilio: twilio SDK
    // - Nexmo: nexmo SDK
  }

  /**
   * Send WhatsApp Message
   */
  private async sendWhatsApp(to: string, message: string, config: any): Promise<void> {
    // TODO: Implement
    // - WhatsApp Business API
    // - Twilio WhatsApp
  }

  /**
   * Send Push Notification
   */
  private async sendPush(token: string, title: string, body: string, config: any): Promise<void> {
    // TODO: Implement
    // - Firebase Cloud Messaging
    // - OneSignal
  }

  /**
   * Schedule reminder notifications
   * Called by cron job or background worker
   */
  async scheduleReminders(): Promise<void> {
    // TODO: Implement
    // 1. Find bookings in next 24 hours
    // 2. Check if 24h reminder sent
    // 3. Send if not
    // 4. Find bookings in next 1 hour
    // 5. Send 1h reminder
  }

  /**
   * Send birthday notifications
   * Called daily by cron job
   */
  async sendBirthdayGreetings(): Promise<void> {
    // TODO: Implement
    // 1. Find customers with birthday today
    // 2. Send greeting via configured channels
  }
}

/**
 * Integration with existing event system
 *
 * In eventHandlers.ts, add:
 *
 * bookingEvents.on('booking:confirmed', async (data) => {
 *   await notificationService.send(
 *     data.organizationId,
 *     NotificationEvent.BOOKING_CONFIRMED,
 *     data.booking,
 *     {
 *       email: data.booking.customer.email,
 *       phone: data.booking.customer.phone,
 *     }
 *   );
 * });
 *
 * invoiceEvents.on('invoice:sent', async (data) => {
 *   await notificationService.send(
 *     data.organizationId,
 *     NotificationEvent.INVOICE_SENT,
 *     data.invoice,
 *     {
 *       email: data.invoice.customer.email,
 *     }
 *   );
 * });
 */

/**
 * DATABASE SCHEMA ADDITIONS NEEDED:
 *
 * model NotificationTemplate {
 *   id                String               @id @default(cuid())
 *   organizationId    String
 *   organization      Organization         @relation(fields: [organizationId], references: [id])
 *   event             NotificationEvent
 *   channel           NotificationChannel
 *   subject           String?
 *   body              String               @db.Text
 *   isActive          Boolean              @default(true)
 *   serviceCategories String[]
 *   serviceIds        String[]
 *   createdAt         DateTime             @default(now())
 *   updatedAt         DateTime             @updatedAt
 * }
 *
 * model NotificationLog {
 *   id             String              @id @default(cuid())
 *   organizationId String
 *   event          NotificationEvent
 *   channel        NotificationChannel
 *   recipient      String
 *   subject        String?
 *   body           String              @db.Text
 *   status         String              // SENT, FAILED, PENDING
 *   errorMessage   String?
 *   sentAt         DateTime?
 *   createdAt      DateTime            @default(now())
 * }
 *
 * model NotificationSettings {
 *   id              String       @id @default(cuid())
 *   organizationId  String       @unique
 *   organization    Organization @relation(fields: [organizationId], references: [id])
 *   emailProvider   String?
 *   emailConfig     Json?
 *   smsProvider     String?
 *   smsConfig       Json?
 *   whatsappProvider String?
 *   whatsappConfig  Json?
 *   pushProvider    String?
 *   pushConfig      Json?
 *   enabledEvents   Json         // Map of event -> settings
 *   createdAt       DateTime     @default(now())
 *   updatedAt       DateTime     @updatedAt
 * }
 */

export const notificationService = new NotificationService();
