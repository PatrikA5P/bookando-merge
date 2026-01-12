import { PrismaClient, Invoice, InvoiceStatus } from '@prisma/client';
import { EventEmitter } from 'events';

export const invoiceEvents = new EventEmitter();

interface CreateInvoiceData {
  customerId: string;
  bookingId?: string;
  dueDate: string;
  notes?: string;
  items: InvoiceItemData[];
}

interface InvoiceItemData {
  description: string;
  quantity: number;
  unitPrice: number;
  taxRate: number;
}

interface UpdateInvoiceData {
  dueDate?: string;
  notes?: string;
  status?: InvoiceStatus;
}

export class InvoiceService {
  /**
   * Get all invoices for the organization
   */
  async getAll(
    prisma: any,
    filters?: {
      status?: InvoiceStatus;
      customerId?: string;
      dateFrom?: string;
      dateTo?: string;
    }
  ): Promise<Invoice[]> {
    const where: any = {};

    if (filters?.status) {
      where.status = filters.status;
    }

    if (filters?.customerId) {
      where.customerId = filters.customerId;
    }

    if (filters?.dateFrom || filters?.dateTo) {
      where.issueDate = {};
      if (filters.dateFrom) {
        where.issueDate.gte = filters.dateFrom;
      }
      if (filters.dateTo) {
        where.issueDate.lte = filters.dateTo;
      }
    }

    return await prisma.invoice.findMany({
      where,
      include: {
        customer: {
          select: {
            id: true,
            firstName: true,
            lastName: true,
            email: true,
          },
        },
        booking: {
          select: {
            id: true,
            bookingNumber: true,
            service: {
              select: {
                id: true,
                name: true,
              },
            },
          },
        },
        items: true,
        payments: true,
      },
      orderBy: {
        createdAt: 'desc',
      },
    });
  }

  /**
   * Get invoice by ID
   */
  async getById(id: string, prisma: any): Promise<Invoice | null> {
    return await prisma.invoice.findUnique({
      where: { id },
      include: {
        customer: true,
        booking: {
          include: {
            service: true,
          },
        },
        items: true,
        payments: {
          orderBy: {
            createdAt: 'desc',
          },
        },
      },
    });
  }

  /**
   * Create invoice manually
   */
  async create(data: CreateInvoiceData, prisma: any, organizationId: string): Promise<Invoice> {
    // Verify customer exists
    const customer = await prisma.customer.findUnique({
      where: { id: data.customerId },
    });

    if (!customer) {
      throw new Error('Customer not found');
    }

    // Generate invoice number
    const invoiceNumber = await this.generateInvoiceNumber(prisma);

    // Calculate totals
    const { subtotal, taxAmount, totalAmount } = this.calculateTotals(data.items);

    // Create invoice with items
    const invoice = await prisma.invoice.create({
      data: {
        invoiceNumber,
        customerId: data.customerId,
        bookingId: data.bookingId,
        issueDate: new Date().toISOString(),
        dueDate: data.dueDate,
        subtotal,
        taxAmount,
        totalAmount,
        amountPaid: 0,
        amountDue: totalAmount,
        notes: data.notes,
        status: 'DRAFT',
        organizationId,
        items: {
          create: data.items.map((item, index) => ({
            description: item.description,
            quantity: item.quantity,
            unitPrice: item.unitPrice,
            taxRate: item.taxRate,
            totalPrice: item.quantity * item.unitPrice,
            sortOrder: index,
          })),
        },
      },
      include: {
        customer: true,
        booking: true,
        items: true,
      },
    });

    // Emit event
    invoiceEvents.emit('invoice:created', {
      invoice,
      organizationId,
    });

    return invoice;
  }

  /**
   * Auto-generate invoice from booking (triggered by booking:confirmed event)
   */
  async createFromBooking(bookingId: string, prisma: any, organizationId: string): Promise<Invoice> {
    // Get booking with relations
    const booking = await prisma.booking.findUnique({
      where: { id: bookingId },
      include: {
        customer: true,
        service: true,
        session: {
          include: {
            course: true,
          },
        },
      },
    });

    if (!booking) {
      throw new Error('Booking not found');
    }

    // Check if invoice already exists for this booking
    const existingInvoice = await prisma.invoice.findFirst({
      where: { bookingId },
    });

    if (existingInvoice) {
      console.log(`Invoice already exists for booking ${bookingId}`);
      return existingInvoice;
    }

    // Generate invoice number
    const invoiceNumber = await this.generateInvoiceNumber(prisma);

    // Calculate due date (14 days from now)
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 14);

    // Get organization for tax settings
    const organization = await prisma.organization.findUnique({
      where: { id: organizationId },
    });

    const taxRate = organization?.taxRate || 7.7; // Default Swiss VAT

    // Create invoice items
    const participants = booking.participants || 1;
    const description = booking.session
      ? `${booking.service.name} - ${booking.session.course?.name || 'Course'}`
      : booking.service.name;

    const items: InvoiceItemData[] = [
      {
        description,
        quantity: participants,
        unitPrice: booking.service.price,
        taxRate,
      },
    ];

    // Calculate totals
    const { subtotal, taxAmount, totalAmount } = this.calculateTotals(items);

    // Create invoice
    const invoice = await prisma.invoice.create({
      data: {
        invoiceNumber,
        customerId: booking.customerId,
        bookingId: booking.id,
        issueDate: new Date().toISOString(),
        dueDate: dueDate.toISOString(),
        subtotal,
        taxAmount,
        totalAmount,
        amountPaid: 0,
        amountDue: totalAmount,
        notes: `Auto-generated for booking ${booking.bookingNumber}`,
        status: 'SENT',
        organizationId,
        items: {
          create: items.map((item, index) => ({
            description: item.description,
            quantity: item.quantity,
            unitPrice: item.unitPrice,
            taxRate: item.taxRate,
            totalPrice: item.quantity * item.unitPrice,
            sortOrder: index,
          })),
        },
      },
      include: {
        customer: true,
        booking: true,
        items: true,
      },
    });

    // Update booking with invoice reference
    await prisma.booking.update({
      where: { id: bookingId },
      data: { invoiceId: invoice.id },
    });

    // Emit event
    invoiceEvents.emit('invoice:created', {
      invoice,
      organizationId,
      autoGenerated: true,
    });

    return invoice;
  }

  /**
   * Update invoice
   */
  async update(id: string, data: UpdateInvoiceData, prisma: any): Promise<Invoice> {
    const existing = await prisma.invoice.findUnique({
      where: { id },
    });

    if (!existing) {
      throw new Error('Invoice not found');
    }

    const invoice = await prisma.invoice.update({
      where: { id },
      data: {
        ...data,
        sentAt: data.status === 'SENT' && !existing.sentAt ? new Date() : existing.sentAt,
        paidAt: data.status === 'PAID' && !existing.paidAt ? new Date() : existing.paidAt,
        cancelledAt: data.status === 'CANCELLED' && !existing.cancelledAt ? new Date() : existing.cancelledAt,
      },
      include: {
        customer: true,
        booking: true,
        items: true,
      },
    });

    // Emit status change events
    if (data.status && data.status !== existing.status) {
      invoiceEvents.emit(`invoice:${data.status.toLowerCase()}`, {
        invoice,
        organizationId: existing.organizationId,
      });

      // If invoice is paid, update booking payment status
      if (data.status === 'PAID' && invoice.bookingId) {
        await prisma.booking.update({
          where: { id: invoice.bookingId },
          data: {
            paymentStatus: 'PAID',
            paidAt: new Date(),
          },
        });
      }
    }

    return invoice;
  }

  /**
   * Send invoice to customer
   */
  async send(id: string, prisma: any): Promise<Invoice> {
    return await this.update(id, { status: 'SENT' }, prisma);
  }

  /**
   * Mark invoice as paid
   */
  async markAsPaid(id: string, prisma: any, paymentAmount?: number): Promise<Invoice> {
    const invoice = await prisma.invoice.findUnique({
      where: { id },
    });

    if (!invoice) {
      throw new Error('Invoice not found');
    }

    const amount = paymentAmount || invoice.amountDue;

    // Create payment record
    await prisma.payment.create({
      data: {
        invoiceId: id,
        amount,
        paymentDate: new Date().toISOString(),
        method: 'MANUAL', // Can be extended to support different payment methods
        status: 'COMPLETED',
        organizationId: invoice.organizationId,
      },
    });

    // Update invoice
    const updatedAmountPaid = invoice.amountPaid + amount;
    const updatedAmountDue = invoice.totalAmount - updatedAmountPaid;

    return await prisma.invoice.update({
      where: { id },
      data: {
        amountPaid: updatedAmountPaid,
        amountDue: updatedAmountDue,
        status: updatedAmountDue <= 0 ? 'PAID' : 'PARTIALLY_PAID',
        paidAt: updatedAmountDue <= 0 ? new Date() : null,
      },
      include: {
        customer: true,
        booking: true,
        items: true,
        payments: true,
      },
    });
  }

  /**
   * Cancel invoice
   */
  async cancel(id: string, reason: string, prisma: any): Promise<Invoice> {
    const invoice = await this.update(id, { status: 'CANCELLED', notes: reason }, prisma);

    invoiceEvents.emit('invoice:cancelled', {
      invoice,
      organizationId: invoice.organizationId,
      reason,
    });

    return invoice;
  }

  /**
   * Get invoices for a specific customer
   */
  async getByCustomer(customerId: string, prisma: any): Promise<Invoice[]> {
    return await prisma.invoice.findMany({
      where: { customerId },
      include: {
        booking: {
          include: {
            service: true,
          },
        },
        items: true,
        payments: true,
      },
      orderBy: {
        issueDate: 'desc',
      },
    });
  }

  /**
   * Get overdue invoices
   */
  async getOverdue(prisma: any): Promise<Invoice[]> {
    const today = new Date().toISOString();

    return await prisma.invoice.findMany({
      where: {
        status: {
          in: ['SENT', 'PARTIALLY_PAID'],
        },
        dueDate: {
          lt: today,
        },
      },
      include: {
        customer: true,
        booking: true,
      },
      orderBy: {
        dueDate: 'asc',
      },
    });
  }

  /**
   * Generate unique invoice number
   * Format: INV-YYYY-XXXXX (e.g., INV-2026-00001)
   */
  private async generateInvoiceNumber(prisma: any): Promise<string> {
    const year = new Date().getFullYear();

    // Get count of invoices created this year
    const count = await prisma.invoice.count({
      where: {
        invoiceNumber: {
          startsWith: `INV-${year}`,
        },
      },
    });

    const sequence = (count + 1).toString().padStart(5, '0');
    return `INV-${year}-${sequence}`;
  }

  /**
   * Calculate invoice totals
   */
  private calculateTotals(items: InvoiceItemData[]): {
    subtotal: number;
    taxAmount: number;
    totalAmount: number;
  } {
    const subtotal = items.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0);

    const taxAmount = items.reduce((sum, item) => {
      const itemTotal = item.quantity * item.unitPrice;
      const itemTax = (itemTotal * item.taxRate) / 100;
      return sum + itemTax;
    }, 0);

    const totalAmount = subtotal + taxAmount;

    return {
      subtotal: Math.round(subtotal * 100) / 100,
      taxAmount: Math.round(taxAmount * 100) / 100,
      totalAmount: Math.round(totalAmount * 100) / 100,
    };
  }
}

export const invoiceService = new InvoiceService();
