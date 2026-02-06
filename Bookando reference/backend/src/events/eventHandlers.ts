import { PrismaClient } from '@prisma/client';
import { bookingEvents } from '../services/BookingService';
import { invoiceEvents } from '../services/InvoiceService';
import { invoiceService } from '../services/InvoiceService';
import { NotificationService, NotificationEvent } from '../services/NotificationService';
import { CalendarIntegrationService } from '../services/CalendarIntegrationService';

const prisma = new PrismaClient();
const notificationService = new NotificationService(prisma);
const calendarService = new CalendarIntegrationService(prisma);

/**
 * Event-Driven Architecture for Module Integration
 *
 * This file sets up event listeners that enable cross-module functionality:
 * - Booking confirmed → Auto-generate invoice
 * - Invoice paid → Update booking payment status
 * - Booking cancelled → Cancel associated invoice
 */

export function initializeEventHandlers() {
  console.log('🎯 Initializing event handlers...');

  // ============================================================================
  // BOOKING EVENTS
  // ============================================================================

  /**
   * When a booking is confirmed, automatically generate an invoice
   */
  bookingEvents.on('booking:confirmed', async (data: { booking: any; organizationId: string }) => {
    try {
      console.log(`📋 Booking confirmed: ${data.booking.bookingNumber}`);
      console.log(`💰 Auto-generating invoice for booking ${data.booking.id}...`);

      // Create scoped Prisma client for the organization
      const scopedPrisma = prisma.$extends({
        query: {
          $allModels: {
            async create({ args, query }: any) {
              args.data = { ...args.data, organizationId: data.organizationId };
              return query(args);
            },
          },
        },
      });

      // Generate invoice from booking
      const invoice = await invoiceService.createFromBooking(
        data.booking.id,
        scopedPrisma,
        data.organizationId
      );

      console.log(`✅ Invoice ${invoice.invoiceNumber} created successfully for booking ${data.booking.bookingNumber}`);

      // Send booking confirmation notification
      try {
        await notificationService.send(
          data.organizationId,
          NotificationEvent.BOOKING_CONFIRMED,
          data.booking,
          {
            email: data.booking.customer?.email,
            phone: data.booking.customer?.phone,
          },
          data.booking.service?.category
        );
        console.log(`📧 Booking confirmation notification sent`);
      } catch (notifError: any) {
        console.error(`⚠️ Failed to send booking confirmation notification:`, notifError.message);
      }

      // Create calendar event for assigned employee
      try {
        if (data.booking.employeeId) {
          await calendarService.createEvent(data.booking.id);
          console.log(`📅 Calendar event created for employee`);
        }
      } catch (calError: any) {
        console.error(`⚠️ Failed to create calendar event:`, calError.message);
      }
    } catch (error: any) {
      console.error(`❌ Failed to create invoice for booking ${data.booking.id}:`, error.message);
    }
  });

  /**
   * When a booking is cancelled, cancel the associated invoice
   */
  bookingEvents.on('booking:cancelled', async (data: { booking: any; organizationId: string; reason?: string }) => {
    try {
      console.log(`🚫 Booking cancelled: ${data.booking.bookingNumber}`);

      if (data.booking.invoiceId) {
        console.log(`📄 Cancelling associated invoice ${data.booking.invoiceId}...`);

        // Create scoped Prisma client
        const scopedPrisma = prisma.$extends({
          query: {
            $allModels: {
              async create({ args, query }: any) {
                args.data = { ...args.data, organizationId: data.organizationId };
                return query(args);
              },
            },
          },
        });

        const reason = data.reason || 'Booking cancelled';
        await invoiceService.cancel(data.booking.invoiceId, reason, scopedPrisma);

        console.log(`✅ Invoice cancelled successfully`);
      }

      // Send booking cancellation notification
      try {
        await notificationService.send(
          data.organizationId,
          NotificationEvent.BOOKING_CANCELLED,
          data.booking,
          {
            email: data.booking.customer?.email,
            phone: data.booking.customer?.phone,
          },
          data.booking.service?.category
        );
        console.log(`📧 Booking cancellation notification sent`);
      } catch (notifError: any) {
        console.error(`⚠️ Failed to send booking cancellation notification:`, notifError.message);
      }

      // Delete calendar event for employee
      try {
        await calendarService.deleteEvent(data.booking.id);
        console.log(`📅 Calendar event deleted`);
      } catch (calError: any) {
        console.error(`⚠️ Failed to delete calendar event:`, calError.message);
      }
    } catch (error: any) {
      console.error(`❌ Failed to cancel invoice for booking ${data.booking.id}:`, error.message);
    }
  });

  /**
   * When a booking is completed, log for analytics
   */
  bookingEvents.on('booking:completed', async (data: { booking: any; organizationId: string }) => {
    try {
      console.log(`✅ Booking completed: ${data.booking.bookingNumber}`);
      // Here you could:
      // - Send customer satisfaction survey
      // - Update analytics
      // - Trigger review request
      // - Update customer engagement score
    } catch (error: any) {
      console.error(`❌ Error handling completed booking ${data.booking.id}:`, error.message);
    }
  });

  /**
   * When a booking is updated, update calendar event
   */
  bookingEvents.on('booking:updated', async (data: { booking: any; organizationId: string }) => {
    try {
      console.log(`📝 Booking updated: ${data.booking.bookingNumber}`);

      // Update calendar event
      try {
        await calendarService.updateEvent(data.booking.id);
        console.log(`📅 Calendar event updated`);
      } catch (calError: any) {
        console.error(`⚠️ Failed to update calendar event:`, calError.message);
      }
    } catch (error: any) {
      console.error(`❌ Error handling updated booking ${data.booking.id}:`, error.message);
    }
  });

  /**
   * When a booking is created, send notifications
   */
  bookingEvents.on('booking:created', async (data: { booking: any; organizationId: string }) => {
    try {
      console.log(`📅 New booking created: ${data.booking.bookingNumber}`);
      // Here you could:
      // - Send confirmation email to customer
      // - Send notification to assigned employee
      // - Add to calendar
      // - Send SMS reminder
    } catch (error: any) {
      console.error(`❌ Error handling new booking ${data.booking.id}:`, error.message);
    }
  });

  // ============================================================================
  // INVOICE EVENTS
  // ============================================================================

  /**
   * When an invoice is sent, send notification to customer
   */
  invoiceEvents.on('invoice:sent', async (data: { invoice: any; organizationId: string }) => {
    try {
      console.log(`📧 Invoice sent: ${data.invoice.invoiceNumber}`);

      // Send invoice notification
      try {
        await notificationService.send(
          data.organizationId,
          NotificationEvent.INVOICE_SENT,
          data.invoice,
          {
            email: data.invoice.customer?.email,
          }
        );
        console.log(`📧 Invoice notification sent`);
      } catch (notifError: any) {
        console.error(`⚠️ Failed to send invoice notification:`, notifError.message);
      }
    } catch (error: any) {
      console.error(`❌ Error handling sent invoice ${data.invoice.id}:`, error.message);
    }
  });

  /**
   * When an invoice is paid, update booking and send receipt
   */
  invoiceEvents.on('invoice:paid', async (data: { invoice: any; organizationId: string }) => {
    try {
      console.log(`💳 Invoice paid: ${data.invoice.invoiceNumber}`);

      // Send payment confirmation notification
      try {
        await notificationService.send(
          data.organizationId,
          NotificationEvent.INVOICE_PAID,
          data.invoice,
          {
            email: data.invoice.customer?.email,
          }
        );
        console.log(`📧 Payment confirmation notification sent`);
      } catch (notifError: any) {
        console.error(`⚠️ Failed to send payment confirmation notification:`, notifError.message);
      }
    } catch (error: any) {
      console.error(`❌ Error handling paid invoice ${data.invoice.id}:`, error.message);
    }
  });

  /**
   * When an invoice is overdue, send reminder
   */
  invoiceEvents.on('invoice:overdue', async (data: { invoice: any; organizationId: string }) => {
    try {
      console.log(`⚠️ Invoice overdue: ${data.invoice.invoiceNumber}`);

      // Send overdue invoice notification
      try {
        await notificationService.send(
          data.organizationId,
          NotificationEvent.INVOICE_OVERDUE,
          data.invoice,
          {
            email: data.invoice.customer?.email,
          }
        );
        console.log(`📧 Overdue invoice notification sent`);
      } catch (notifError: any) {
        console.error(`⚠️ Failed to send overdue invoice notification:`, notifError.message);
      }
    } catch (error: any) {
      console.error(`❌ Error handling overdue invoice ${data.invoice.id}:`, error.message);
    }
  });

  /**
   * When an invoice is created, log for analytics
   */
  invoiceEvents.on('invoice:created', async (data: { invoice: any; organizationId: string; autoGenerated?: boolean }) => {
    try {
      const type = data.autoGenerated ? 'auto-generated' : 'manual';
      console.log(`📄 Invoice created (${type}): ${data.invoice.invoiceNumber}`);
      // Here you could:
      // - Update revenue analytics
      // - Log in audit trail
      // - Notify accounting team
    } catch (error: any) {
      console.error(`❌ Error handling new invoice ${data.invoice.id}:`, error.message);
    }
  });

  /**
   * When an invoice is cancelled, log and notify
   */
  invoiceEvents.on('invoice:cancelled', async (data: { invoice: any; organizationId: string; reason?: string }) => {
    try {
      console.log(`🚫 Invoice cancelled: ${data.invoice.invoiceNumber}`);
      console.log(`   Reason: ${data.reason || 'Not specified'}`);
      // Here you could:
      // - Send cancellation confirmation
      // - Update accounting records
      // - Log in audit trail
    } catch (error: any) {
      console.error(`❌ Error handling cancelled invoice ${data.invoice.id}:`, error.message);
    }
  });

  console.log('✅ Event handlers initialized successfully');
}

/**
 * Cleanup event listeners (call on server shutdown)
 */
export function cleanupEventHandlers() {
  bookingEvents.removeAllListeners();
  invoiceEvents.removeAllListeners();
  console.log('🧹 Event handlers cleaned up');
}

/**
 * Export services for use in other modules
 */
export { notificationService, calendarService };
