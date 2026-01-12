/**
 * NOTIFICATION SYSTEM - Full Implementation
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
 * 5. Aftersale (Nach abgeschlossenem Kurs)
 * 6. Geburtstag
 * 7. Rechnung versandt (invoice:sent)
 * 8. Zahlung erhalten (invoice:paid)
 * 9. Mahnung (invoice:overdue)
 */

import { EventEmitter } from 'events';
import { PrismaClient } from '@prisma/client';
import nodemailer from 'nodemailer';

export const notificationEvents = new EventEmitter();

/**
 * Notification Channels
 */
export enum NotificationChannel {
  EMAIL = 'EMAIL',
  SMS = 'SMS',
  WHATSAPP = 'WHATSAPP',
  PUSH = 'PUSH',
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
 * Email Provider Configuration
 */
interface EmailConfig {
  provider: 'SMTP' | 'SENDGRID' | 'AWS_SES';
  from: string;
  // SMTP
  host?: string;
  port?: number;
  secure?: boolean;
  auth?: {
    user: string;
    pass: string;
  };
  // SendGrid
  apiKey?: string;
  // AWS SES
  region?: string;
  accessKeyId?: string;
  secretAccessKey?: string;
}

/**
 * SMS Provider Configuration
 */
interface SMSConfig {
  provider: 'TWILIO' | 'NEXMO';
  from: string;
  // Twilio
  accountSid?: string;
  authToken?: string;
  // Nexmo
  apiKey?: string;
  apiSecret?: string;
}

/**
 * WhatsApp Provider Configuration
 */
interface WhatsAppConfig {
  provider: 'WHATSAPP_BUSINESS' | 'TWILIO';
  from: string;
  // Twilio
  accountSid?: string;
  authToken?: string;
  // WhatsApp Business API
  apiKey?: string;
  phoneNumberId?: string;
}

/**
 * Push Notification Provider Configuration
 */
interface PushConfig {
  provider: 'FIREBASE' | 'ONESIGNAL';
  // Firebase
  serverKey?: string;
  // OneSignal
  appId?: string;
  restApiKey?: string;
}

/**
 * Notification Service
 */
export class NotificationService {
  private prisma: PrismaClient;

  constructor(prisma: PrismaClient) {
    this.prisma = prisma;
  }

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
    },
    serviceCategory?: string,
    serviceId?: string
  ): Promise<void> {
    try {
      // 1. Load organization notification settings
      const settings = await this.prisma.notificationSettings.findUnique({
        where: { organizationId },
      });

      if (!settings || !settings.enabledEvents) {
        console.log(`⚠️ No notification settings found for organization ${organizationId}`);
        return;
      }

      // 2. Check if event is enabled
      const eventSettings = (settings.enabledEvents as any)[event];
      if (!eventSettings || !eventSettings.enabled) {
        console.log(`⚠️ Event ${event} is not enabled for organization ${organizationId}`);
        return;
      }

      // 3. Get configured channels for this event
      const channels = eventSettings.channels || [];

      // 4. Load templates and send for each channel
      for (const channel of channels) {
        try {
          await this.sendViaChannel(
            organizationId,
            event,
            channel,
            data,
            recipientContact,
            settings,
            serviceCategory,
            serviceId
          );
        } catch (error: any) {
          console.error(`❌ Failed to send notification via ${channel}:`, error.message);
          // Continue with other channels even if one fails
        }
      }
    } catch (error: any) {
      console.error(`❌ Failed to send notification for event ${event}:`, error.message);
      throw error;
    }
  }

  /**
   * Send via specific channel
   */
  private async sendViaChannel(
    organizationId: string,
    event: NotificationEvent,
    channel: NotificationChannel,
    data: any,
    recipientContact: {
      email?: string;
      phone?: string;
      pushToken?: string;
    },
    settings: any,
    serviceCategory?: string,
    serviceId?: string
  ): Promise<void> {
    // Load template
    const template = await this.loadTemplate(
      organizationId,
      event,
      channel,
      serviceCategory,
      serviceId
    );

    if (!template) {
      console.log(`⚠️ No template found for ${event} via ${channel}`);
      return;
    }

    // Replace placeholders
    const subject = template.subject ? this.replacePlaceholders(template.subject, data) : undefined;
    const body = this.replacePlaceholders(template.body, data);

    // Send via channel
    let status = 'SENT';
    let errorMessage: string | undefined;
    let recipient = '';

    try {
      switch (channel) {
        case NotificationChannel.EMAIL:
          if (!recipientContact.email) {
            throw new Error('Email address not provided');
          }
          recipient = recipientContact.email;
          await this.sendEmail(recipient, subject || 'Notification', body, settings.emailConfig);
          break;

        case NotificationChannel.SMS:
          if (!recipientContact.phone) {
            throw new Error('Phone number not provided');
          }
          recipient = recipientContact.phone;
          await this.sendSMS(recipient, body, settings.smsConfig);
          break;

        case NotificationChannel.WHATSAPP:
          if (!recipientContact.phone) {
            throw new Error('Phone number not provided');
          }
          recipient = recipientContact.phone;
          await this.sendWhatsApp(recipient, body, settings.whatsappConfig);
          break;

        case NotificationChannel.PUSH:
          if (!recipientContact.pushToken) {
            throw new Error('Push token not provided');
          }
          recipient = recipientContact.pushToken;
          await this.sendPush(recipient, subject || 'Notification', body, settings.pushConfig);
          break;
      }

      console.log(`✅ Notification sent via ${channel} to ${recipient}`);
    } catch (error: any) {
      status = 'FAILED';
      errorMessage = error.message;
      console.error(`❌ Failed to send via ${channel}:`, error.message);
      throw error;
    } finally {
      // Log notification
      await this.logNotification(
        organizationId,
        event,
        channel,
        recipient,
        subject,
        body,
        status,
        errorMessage
      );
    }
  }

  /**
   * Load template for event and channel
   */
  private async loadTemplate(
    organizationId: string,
    event: NotificationEvent,
    channel: NotificationChannel,
    serviceCategory?: string,
    serviceId?: string
  ): Promise<any> {
    // Try to find specific template first (by service or category)
    if (serviceId) {
      const template = await this.prisma.notificationTemplate.findFirst({
        where: {
          organizationId,
          event,
          channel,
          isActive: true,
          serviceIds: { has: serviceId },
        },
      });
      if (template) return template;
    }

    if (serviceCategory) {
      const template = await this.prisma.notificationTemplate.findFirst({
        where: {
          organizationId,
          event,
          channel,
          isActive: true,
          serviceCategories: { has: serviceCategory },
        },
      });
      if (template) return template;
    }

    // Fall back to generic template
    return await this.prisma.notificationTemplate.findFirst({
      where: {
        organizationId,
        event,
        channel,
        isActive: true,
        serviceCategories: { isEmpty: true },
        serviceIds: { isEmpty: true },
      },
    });
  }

  /**
   * Replace placeholders in template
   */
  private replacePlaceholders(template: string, data: any): string {
    let result = template;

    // Common placeholders
    const placeholders: Record<string, any> = {
      customerName: data.customer?.firstName || data.customer?.name || 'Kunde',
      customerFirstName: data.customer?.firstName || 'Kunde',
      customerLastName: data.customer?.lastName || '',
      customerEmail: data.customer?.email || '',
      customerPhone: data.customer?.phone || '',

      bookingNumber: data.bookingNumber || data.booking?.bookingNumber || '',
      bookingDate: data.scheduledDate || data.booking?.scheduledDate || '',
      bookingTime: data.scheduledTime || data.booking?.scheduledTime || '',
      bookingStatus: data.status || data.booking?.status || '',

      serviceName: data.service?.name || data.service?.title || '',
      servicePrice: data.service?.price || data.totalPrice || '',

      employeeName: data.employee?.firstName || data.employee?.name || 'Mitarbeiter',
      employeeEmail: data.employee?.email || '',
      employeePhone: data.employee?.phone || '',

      invoiceNumber: data.invoiceNumber || data.invoice?.invoiceNumber || '',
      invoiceAmount: data.totalAmount || data.invoice?.totalAmount || '',
      invoiceDueDate: data.dueDate || data.invoice?.dueDate || '',

      organizationName: data.organization?.name || '',
      organizationEmail: data.organization?.email || '',
      organizationPhone: data.organization?.phone || '',
    };

    // Replace all placeholders
    for (const [key, value] of Object.entries(placeholders)) {
      const regex = new RegExp(`{{${key}}}`, 'g');
      result = result.replace(regex, String(value));
    }

    return result;
  }

  /**
   * Send Email
   */
  private async sendEmail(to: string, subject: string, body: string, config: any): Promise<void> {
    if (!config) {
      throw new Error('Email configuration not found');
    }

    const emailConfig = config as EmailConfig;

    switch (emailConfig.provider) {
      case 'SMTP':
        return await this.sendEmailViaSMTP(to, subject, body, emailConfig);
      case 'SENDGRID':
        return await this.sendEmailViaSendGrid(to, subject, body, emailConfig);
      case 'AWS_SES':
        return await this.sendEmailViaAWS(to, subject, body, emailConfig);
      default:
        throw new Error(`Unsupported email provider: ${emailConfig.provider}`);
    }
  }

  /**
   * Send Email via SMTP (nodemailer)
   */
  private async sendEmailViaSMTP(
    to: string,
    subject: string,
    body: string,
    config: EmailConfig
  ): Promise<void> {
    const transporter = nodemailer.createTransport({
      host: config.host,
      port: config.port,
      secure: config.secure,
      auth: config.auth,
    });

    await transporter.sendMail({
      from: config.from,
      to,
      subject,
      html: body,
    });
  }

  /**
   * Send Email via SendGrid
   */
  private async sendEmailViaSendGrid(
    to: string,
    subject: string,
    body: string,
    config: EmailConfig
  ): Promise<void> {
    // TODO: Implement SendGrid integration
    // const sgMail = require('@sendgrid/mail');
    // sgMail.setApiKey(config.apiKey);
    // await sgMail.send({
    //   to,
    //   from: config.from,
    //   subject,
    //   html: body,
    // });
    throw new Error('SendGrid integration not yet implemented');
  }

  /**
   * Send Email via AWS SES
   */
  private async sendEmailViaAWS(
    to: string,
    subject: string,
    body: string,
    config: EmailConfig
  ): Promise<void> {
    // TODO: Implement AWS SES integration
    // const AWS = require('aws-sdk');
    // const ses = new AWS.SES({
    //   region: config.region,
    //   accessKeyId: config.accessKeyId,
    //   secretAccessKey: config.secretAccessKey,
    // });
    // await ses.sendEmail({
    //   Source: config.from,
    //   Destination: { ToAddresses: [to] },
    //   Message: {
    //     Subject: { Data: subject },
    //     Body: { Html: { Data: body } },
    //   },
    // }).promise();
    throw new Error('AWS SES integration not yet implemented');
  }

  /**
   * Send SMS
   */
  private async sendSMS(to: string, message: string, config: any): Promise<void> {
    if (!config) {
      throw new Error('SMS configuration not found');
    }

    const smsConfig = config as SMSConfig;

    switch (smsConfig.provider) {
      case 'TWILIO':
        return await this.sendSMSViaTwilio(to, message, smsConfig);
      case 'NEXMO':
        return await this.sendSMSViaNexmo(to, message, smsConfig);
      default:
        throw new Error(`Unsupported SMS provider: ${smsConfig.provider}`);
    }
  }

  /**
   * Send SMS via Twilio
   */
  private async sendSMSViaTwilio(to: string, message: string, config: SMSConfig): Promise<void> {
    // TODO: Implement Twilio integration
    // const twilio = require('twilio');
    // const client = twilio(config.accountSid, config.authToken);
    // await client.messages.create({
    //   body: message,
    //   from: config.from,
    //   to,
    // });
    throw new Error('Twilio SMS integration not yet implemented');
  }

  /**
   * Send SMS via Nexmo
   */
  private async sendSMSViaNexmo(to: string, message: string, config: SMSConfig): Promise<void> {
    // TODO: Implement Nexmo integration
    // const Nexmo = require('nexmo');
    // const nexmo = new Nexmo({
    //   apiKey: config.apiKey,
    //   apiSecret: config.apiSecret,
    // });
    // await nexmo.message.sendSms(config.from, to, message);
    throw new Error('Nexmo SMS integration not yet implemented');
  }

  /**
   * Send WhatsApp Message
   */
  private async sendWhatsApp(to: string, message: string, config: any): Promise<void> {
    if (!config) {
      throw new Error('WhatsApp configuration not found');
    }

    const whatsappConfig = config as WhatsAppConfig;

    switch (whatsappConfig.provider) {
      case 'TWILIO':
        return await this.sendWhatsAppViaTwilio(to, message, whatsappConfig);
      case 'WHATSAPP_BUSINESS':
        return await this.sendWhatsAppViaBusiness(to, message, whatsappConfig);
      default:
        throw new Error(`Unsupported WhatsApp provider: ${whatsappConfig.provider}`);
    }
  }

  /**
   * Send WhatsApp via Twilio
   */
  private async sendWhatsAppViaTwilio(
    to: string,
    message: string,
    config: WhatsAppConfig
  ): Promise<void> {
    // TODO: Implement Twilio WhatsApp integration
    // const twilio = require('twilio');
    // const client = twilio(config.accountSid, config.authToken);
    // await client.messages.create({
    //   body: message,
    //   from: `whatsapp:${config.from}`,
    //   to: `whatsapp:${to}`,
    // });
    throw new Error('Twilio WhatsApp integration not yet implemented');
  }

  /**
   * Send WhatsApp via Business API
   */
  private async sendWhatsAppViaBusiness(
    to: string,
    message: string,
    config: WhatsAppConfig
  ): Promise<void> {
    // TODO: Implement WhatsApp Business API integration
    throw new Error('WhatsApp Business API integration not yet implemented');
  }

  /**
   * Send Push Notification
   */
  private async sendPush(
    token: string,
    title: string,
    body: string,
    config: any
  ): Promise<void> {
    if (!config) {
      throw new Error('Push notification configuration not found');
    }

    const pushConfig = config as PushConfig;

    switch (pushConfig.provider) {
      case 'FIREBASE':
        return await this.sendPushViaFirebase(token, title, body, pushConfig);
      case 'ONESIGNAL':
        return await this.sendPushViaOneSignal(token, title, body, pushConfig);
      default:
        throw new Error(`Unsupported push provider: ${pushConfig.provider}`);
    }
  }

  /**
   * Send Push via Firebase Cloud Messaging
   */
  private async sendPushViaFirebase(
    token: string,
    title: string,
    body: string,
    config: PushConfig
  ): Promise<void> {
    // TODO: Implement Firebase Cloud Messaging integration
    // const admin = require('firebase-admin');
    // await admin.messaging().send({
    //   token,
    //   notification: { title, body },
    // });
    throw new Error('Firebase Cloud Messaging integration not yet implemented');
  }

  /**
   * Send Push via OneSignal
   */
  private async sendPushViaOneSignal(
    token: string,
    title: string,
    body: string,
    config: PushConfig
  ): Promise<void> {
    // TODO: Implement OneSignal integration
    throw new Error('OneSignal integration not yet implemented');
  }

  /**
   * Log notification in database
   */
  private async logNotification(
    organizationId: string,
    event: NotificationEvent,
    channel: NotificationChannel,
    recipient: string,
    subject: string | undefined,
    body: string,
    status: string,
    errorMessage?: string
  ): Promise<void> {
    try {
      await this.prisma.notificationLog.create({
        data: {
          organizationId,
          event,
          channel,
          recipient,
          subject: subject || '',
          body,
          status,
          errorMessage,
          sentAt: status === 'SENT' ? new Date() : null,
        },
      });
    } catch (error: any) {
      console.error('❌ Failed to log notification:', error.message);
    }
  }

  /**
   * Schedule reminder notifications
   * Called by cron job or background worker
   */
  async scheduleReminders(): Promise<void> {
    try {
      const now = new Date();
      const in24Hours = new Date(now.getTime() + 24 * 60 * 60 * 1000);
      const in1Hour = new Date(now.getTime() + 1 * 60 * 60 * 1000);

      // Find bookings for 24h reminders
      const bookings24h = await this.prisma.booking.findMany({
        where: {
          status: 'CONFIRMED',
          scheduledDate: {
            gte: now.toISOString().split('T')[0],
            lte: in24Hours.toISOString().split('T')[0],
          },
          // TODO: Add flag to track if reminder was sent
        },
        include: {
          customer: true,
          service: true,
          employee: true,
          organization: true,
        },
      });

      for (const booking of bookings24h) {
        await this.send(
          booking.organizationId,
          NotificationEvent.BOOKING_REMINDER_24H,
          booking,
          {
            email: booking.customer.email,
            phone: booking.customer.phone,
          }
        );
      }

      // Find bookings for 1h reminders
      const bookings1h = await this.prisma.booking.findMany({
        where: {
          status: 'CONFIRMED',
          scheduledDate: now.toISOString().split('T')[0],
          // TODO: Filter by time (1 hour from now)
          // TODO: Add flag to track if reminder was sent
        },
        include: {
          customer: true,
          service: true,
          employee: true,
          organization: true,
        },
      });

      for (const booking of bookings1h) {
        await this.send(
          booking.organizationId,
          NotificationEvent.BOOKING_REMINDER_1H,
          booking,
          {
            email: booking.customer.email,
            phone: booking.customer.phone,
          }
        );
      }

      console.log(`✅ Processed ${bookings24h.length} 24h reminders and ${bookings1h.length} 1h reminders`);
    } catch (error: any) {
      console.error('❌ Failed to schedule reminders:', error.message);
    }
  }

  /**
   * Send birthday notifications
   * Called daily by cron job
   */
  async sendBirthdayGreetings(): Promise<void> {
    try {
      const today = new Date();
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const day = String(today.getDate()).padStart(2, '0');
      const todayMMDD = `${month}-${day}`;

      // Find customers with birthday today
      const customers = await this.prisma.customer.findMany({
        where: {
          dateOfBirth: {
            contains: todayMMDD,
          },
        },
        include: {
          organization: true,
        },
      });

      for (const customer of customers) {
        await this.send(
          customer.organizationId,
          NotificationEvent.CUSTOMER_BIRTHDAY,
          { customer },
          {
            email: customer.email,
            phone: customer.phone,
          }
        );
      }

      console.log(`✅ Sent ${customers.length} birthday greetings`);
    } catch (error: any) {
      console.error('❌ Failed to send birthday greetings:', error.message);
    }
  }

  /**
   * Create default notification templates for an organization
   */
  async createDefaultTemplates(organizationId: string): Promise<void> {
    const defaultTemplates = [
      // Booking Confirmed - Email
      {
        organizationId,
        event: NotificationEvent.BOOKING_CONFIRMED,
        channel: NotificationChannel.EMAIL,
        subject: 'Buchungsbestätigung - {{bookingNumber}}',
        body: `
          <h2>Hallo {{customerName}},</h2>
          <p>Ihre Buchung wurde erfolgreich bestätigt!</p>

          <h3>Buchungsdetails:</h3>
          <ul>
            <li><strong>Buchungsnummer:</strong> {{bookingNumber}}</li>
            <li><strong>Service:</strong> {{serviceName}}</li>
            <li><strong>Datum:</strong> {{bookingDate}}</li>
            <li><strong>Uhrzeit:</strong> {{bookingTime}}</li>
            <li><strong>Mitarbeiter:</strong> {{employeeName}}</li>
            <li><strong>Preis:</strong> CHF {{servicePrice}}</li>
          </ul>

          <p>Wir freuen uns auf Ihren Besuch!</p>

          <p>Mit freundlichen Grüssen,<br>{{organizationName}}</p>
        `,
        isActive: true,
        serviceCategories: [],
        serviceIds: [],
      },
      // Booking Confirmed - SMS
      {
        organizationId,
        event: NotificationEvent.BOOKING_CONFIRMED,
        channel: NotificationChannel.SMS,
        subject: null,
        body: 'Buchung bestätigt! {{bookingNumber}} am {{bookingDate}} um {{bookingTime}}. {{organizationName}}',
        isActive: true,
        serviceCategories: [],
        serviceIds: [],
      },
      // Booking Cancelled - Email
      {
        organizationId,
        event: NotificationEvent.BOOKING_CANCELLED,
        channel: NotificationChannel.EMAIL,
        subject: 'Buchung storniert - {{bookingNumber}}',
        body: `
          <h2>Hallo {{customerName}},</h2>
          <p>Ihre Buchung <strong>{{bookingNumber}}</strong> wurde storniert.</p>

          <p>Falls Sie Fragen haben, kontaktieren Sie uns gerne unter {{organizationEmail}} oder {{organizationPhone}}.</p>

          <p>Mit freundlichen Grüssen,<br>{{organizationName}}</p>
        `,
        isActive: true,
        serviceCategories: [],
        serviceIds: [],
      },
      // Booking Reminder 24h - Email
      {
        organizationId,
        event: NotificationEvent.BOOKING_REMINDER_24H,
        channel: NotificationChannel.EMAIL,
        subject: 'Erinnerung: Ihr Termin morgen - {{bookingNumber}}',
        body: `
          <h2>Hallo {{customerName}},</h2>
          <p>Dies ist eine Erinnerung an Ihren Termin morgen:</p>

          <ul>
            <li><strong>Service:</strong> {{serviceName}}</li>
            <li><strong>Datum:</strong> {{bookingDate}}</li>
            <li><strong>Uhrzeit:</strong> {{bookingTime}}</li>
            <li><strong>Mitarbeiter:</strong> {{employeeName}}</li>
          </ul>

          <p>Wir freuen uns auf Sie!</p>

          <p>Mit freundlichen Grüssen,<br>{{organizationName}}</p>
        `,
        isActive: true,
        serviceCategories: [],
        serviceIds: [],
      },
      // Invoice Sent - Email
      {
        organizationId,
        event: NotificationEvent.INVOICE_SENT,
        channel: NotificationChannel.EMAIL,
        subject: 'Rechnung {{invoiceNumber}}',
        body: `
          <h2>Hallo {{customerName}},</h2>
          <p>Ihre Rechnung ist bereit.</p>

          <h3>Rechnungsdetails:</h3>
          <ul>
            <li><strong>Rechnungsnummer:</strong> {{invoiceNumber}}</li>
            <li><strong>Betrag:</strong> CHF {{invoiceAmount}}</li>
            <li><strong>Fälligkeitsdatum:</strong> {{invoiceDueDate}}</li>
          </ul>

          <p>Bitte überweisen Sie den Betrag bis zum Fälligkeitsdatum.</p>

          <p>Mit freundlichen Grüssen,<br>{{organizationName}}</p>
        `,
        isActive: true,
        serviceCategories: [],
        serviceIds: [],
      },
      // Invoice Paid - Email
      {
        organizationId,
        event: NotificationEvent.INVOICE_PAID,
        channel: NotificationChannel.EMAIL,
        subject: 'Zahlungsbestätigung - {{invoiceNumber}}',
        body: `
          <h2>Hallo {{customerName}},</h2>
          <p>Vielen Dank! Ihre Zahlung für Rechnung <strong>{{invoiceNumber}}</strong> ist eingegangen.</p>

          <p><strong>Betrag:</strong> CHF {{invoiceAmount}}</p>

          <p>Mit freundlichen Grüssen,<br>{{organizationName}}</p>
        `,
        isActive: true,
        serviceCategories: [],
        serviceIds: [],
      },
      // Customer Birthday - Email
      {
        organizationId,
        event: NotificationEvent.CUSTOMER_BIRTHDAY,
        channel: NotificationChannel.EMAIL,
        subject: 'Happy Birthday {{customerFirstName}}! 🎉',
        body: `
          <h2>Alles Gute zum Geburtstag, {{customerFirstName}}! 🎂</h2>
          <p>Das ganze Team von {{organizationName}} wünscht Ihnen einen wunderschönen Geburtstag!</p>

          <p>Wir freuen uns auf viele weitere gemeinsame Fahrten mit Ihnen.</p>

          <p>Herzliche Grüsse,<br>{{organizationName}}</p>
        `,
        isActive: true,
        serviceCategories: [],
        serviceIds: [],
      },
    ];

    for (const template of defaultTemplates) {
      await this.prisma.notificationTemplate.create({
        data: template as any,
      });
    }

    console.log(`✅ Created ${defaultTemplates.length} default notification templates`);
  }

  /**
   * Create default notification settings for an organization
   */
  async createDefaultSettings(organizationId: string): Promise<void> {
    await this.prisma.notificationSettings.create({
      data: {
        organizationId,
        emailProvider: null,
        emailConfig: null,
        smsProvider: null,
        smsConfig: null,
        whatsappProvider: null,
        whatsappConfig: null,
        pushProvider: null,
        pushConfig: null,
        enabledEvents: {
          [NotificationEvent.BOOKING_CONFIRMED]: {
            enabled: true,
            channels: [NotificationChannel.EMAIL],
          },
          [NotificationEvent.BOOKING_CANCELLED]: {
            enabled: true,
            channels: [NotificationChannel.EMAIL],
          },
          [NotificationEvent.BOOKING_REMINDER_24H]: {
            enabled: true,
            channels: [NotificationChannel.EMAIL],
            delayMinutes: 1440, // 24 hours
          },
          [NotificationEvent.INVOICE_SENT]: {
            enabled: true,
            channels: [NotificationChannel.EMAIL],
          },
          [NotificationEvent.INVOICE_PAID]: {
            enabled: true,
            channels: [NotificationChannel.EMAIL],
          },
        },
      },
    });

    console.log(`✅ Created default notification settings`);
  }
}
