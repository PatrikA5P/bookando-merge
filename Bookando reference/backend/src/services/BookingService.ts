import { PrismaClient, Booking, BookingStatus, PaymentStatus } from '@prisma/client';
import { EventEmitter } from 'events';

// Event emitter for cross-module integration
export const bookingEvents = new EventEmitter();

// Assignment strategies for employee allocation
export enum AssignmentStrategy {
  ROUND_ROBIN = 'ROUND_ROBIN', // Distribute evenly across all employees
  AVAILABILITY = 'AVAILABILITY', // Maximize bookings, assign to any available
  PRIORITY = 'PRIORITY', // Assign based on employee priority/ranking
  SAME_EMPLOYEE = 'SAME_EMPLOYEE', // Try to keep same employee for customer (rebooking preference)
  WORKLOAD_BALANCE = 'WORKLOAD_BALANCE', // Balance based on current workload
}

interface AssignmentOptions {
  strategy?: AssignmentStrategy;
  customerId?: string; // For SAME_EMPLOYEE strategy
  priorityEmployeeIds?: string[]; // For PRIORITY strategy
}

interface CreateBookingData {
  customerId: string;
  serviceId: string;
  sessionId?: string;
  scheduledDate: string;
  scheduledTime: string;
  participants?: number;
  notes?: string;
  employeeId?: string; // Optional manual assignment
}

interface UpdateBookingData {
  scheduledDate?: string;
  scheduledTime?: string;
  participants?: number;
  notes?: string;
  employeeId?: string;
  status?: BookingStatus;
  paymentStatus?: PaymentStatus;
}

export class BookingService {
  /**
   * Get all bookings for the organization
   * Supports filtering by status, customer, date range
   */
  async getAll(
    prisma: any,
    filters?: {
      status?: BookingStatus;
      customerId?: string;
      serviceId?: string;
      dateFrom?: string;
      dateTo?: string;
    }
  ): Promise<Booking[]> {
    const where: any = {};

    if (filters?.status) {
      where.status = filters.status;
    }

    if (filters?.customerId) {
      where.customerId = filters.customerId;
    }

    if (filters?.serviceId) {
      where.serviceId = filters.serviceId;
    }

    if (filters?.dateFrom || filters?.dateTo) {
      where.scheduledDate = {};
      if (filters.dateFrom) {
        where.scheduledDate.gte = filters.dateFrom;
      }
      if (filters.dateTo) {
        where.scheduledDate.lte = filters.dateTo;
      }
    }

    return await prisma.booking.findMany({
      where,
      include: {
        customer: {
          select: {
            id: true,
            firstName: true,
            lastName: true,
            email: true,
            phone: true,
          },
        },
        service: {
          select: {
            id: true,
            name: true,
            price: true,
            duration: true,
          },
        },
        employee: {
          select: {
            id: true,
            firstName: true,
            lastName: true,
            email: true,
          },
        },
        session: {
          select: {
            id: true,
            startDate: true,
            endDate: true,
            location: {
              select: {
                id: true,
                name: true,
                address: true,
              },
            },
          },
        },
        invoice: {
          select: {
            id: true,
            invoiceNumber: true,
            totalAmount: true,
            status: true,
          },
        },
      },
      orderBy: {
        createdAt: 'desc',
      },
    });
  }

  /**
   * Get booking by ID
   */
  async getById(id: string, prisma: any): Promise<Booking | null> {
    return await prisma.booking.findUnique({
      where: { id },
      include: {
        customer: true,
        service: true,
        employee: true,
        session: {
          include: {
            location: true,
            course: true,
          },
        },
        invoice: true,
      },
    });
  }

  /**
   * Create a new booking
   * Workflow: PENDING → auto-assign employee → calculate price
   */
  async create(data: CreateBookingData, prisma: any, organizationId: string): Promise<Booking> {
    // 1. Get service to calculate price
    const service = await prisma.service.findUnique({
      where: { id: data.serviceId },
    });

    if (!service) {
      throw new Error('Service not found');
    }

    // 2. Verify customer exists
    const customer = await prisma.customer.findUnique({
      where: { id: data.customerId },
    });

    if (!customer) {
      throw new Error('Customer not found');
    }

    // 3. If sessionId provided, verify it exists
    if (data.sessionId) {
      const session = await prisma.courseSession.findUnique({
        where: { id: data.sessionId },
      });

      if (!session) {
        throw new Error('Course session not found');
      }

      // Check if session has available spots
      const existingBookings = await prisma.booking.count({
        where: {
          sessionId: data.sessionId,
          status: {
            in: ['PENDING', 'CONFIRMED', 'PAID'],
          },
        },
      });

      if (session.maxParticipants && existingBookings >= session.maxParticipants) {
        throw new Error('Course session is fully booked');
      }
    }

    // 4. Generate unique booking number
    const bookingNumber = await this.generateBookingNumber(prisma);

    // 5. Calculate total price
    const participants = data.participants || 1;
    const totalPrice = service.price * participants;

    // 6. Auto-assign employee if not provided (with strategy support)
    let employeeId = data.employeeId;
    if (!employeeId) {
      employeeId = await this.autoAssignEmployee(
        data.serviceId,
        data.scheduledDate,
        data.scheduledTime,
        service.duration,
        prisma,
        organizationId,
        {
          customerId: data.customerId, // For SAME_EMPLOYEE strategy
          // Strategy can be configured in Service or passed via API later
        }
      );
    }

    // 7. Create booking
    const booking = await prisma.booking.create({
      data: {
        bookingNumber,
        customerId: data.customerId,
        serviceId: data.serviceId,
        sessionId: data.sessionId,
        scheduledDate: data.scheduledDate,
        scheduledTime: data.scheduledTime,
        totalPrice,
        participants,
        notes: data.notes,
        employeeId,
        status: 'PENDING',
        paymentStatus: 'PENDING',
        organizationId,
      },
      include: {
        customer: true,
        service: true,
        employee: true,
        session: true,
      },
    });

    // 8. Emit event for other modules
    bookingEvents.emit('booking:created', {
      booking,
      organizationId,
    });

    return booking;
  }

  /**
   * Update booking
   */
  async update(id: string, data: UpdateBookingData, prisma: any): Promise<Booking> {
    const existing = await prisma.booking.findUnique({
      where: { id },
    });

    if (!existing) {
      throw new Error('Booking not found');
    }

    // If status changed to CONFIRMED, emit event for invoice generation
    const statusChanged = data.status && data.status !== existing.status;

    const booking = await prisma.booking.update({
      where: { id },
      data: {
        ...data,
        confirmedAt: data.status === 'CONFIRMED' && !existing.confirmedAt ? new Date() : existing.confirmedAt,
        paidAt: data.paymentStatus === 'PAID' && !existing.paidAt ? new Date() : existing.paidAt,
        completedAt: data.status === 'COMPLETED' && !existing.completedAt ? new Date() : existing.completedAt,
        cancelledAt: data.status === 'CANCELLED' && !existing.cancelledAt ? new Date() : existing.cancelledAt,
      },
      include: {
        customer: true,
        service: true,
        employee: true,
        invoice: true,
      },
    });

    // Emit events for workflow transitions
    if (statusChanged) {
      if (data.status === 'CONFIRMED') {
        bookingEvents.emit('booking:confirmed', {
          booking,
          organizationId: existing.organizationId,
        });
      } else if (data.status === 'CANCELLED') {
        bookingEvents.emit('booking:cancelled', {
          booking,
          organizationId: existing.organizationId,
        });
      } else if (data.status === 'COMPLETED') {
        bookingEvents.emit('booking:completed', {
          booking,
          organizationId: existing.organizationId,
        });
      }
    }

    return booking;
  }

  /**
   * Cancel booking (soft delete)
   */
  async cancel(id: string, reason: string, prisma: any): Promise<Booking> {
    const booking = await prisma.booking.update({
      where: { id },
      data: {
        status: 'CANCELLED',
        cancelledAt: new Date(),
        notes: reason,
      },
      include: {
        customer: true,
        service: true,
        invoice: true,
      },
    });

    // Emit cancellation event
    bookingEvents.emit('booking:cancelled', {
      booking,
      organizationId: booking.organizationId,
      reason,
    });

    return booking;
  }

  /**
   * Confirm booking and trigger invoice generation
   */
  async confirm(id: string, prisma: any): Promise<Booking> {
    return await this.update(id, { status: 'CONFIRMED' }, prisma);
  }

  /**
   * Mark booking as paid
   */
  async markAsPaid(id: string, prisma: any): Promise<Booking> {
    return await this.update(id, { paymentStatus: 'PAID' }, prisma);
  }

  /**
   * Mark booking as completed
   */
  async complete(id: string, prisma: any): Promise<Booking> {
    return await this.update(id, { status: 'COMPLETED' }, prisma);
  }

  /**
   * Get bookings for a specific customer
   */
  async getByCustomer(customerId: string, prisma: any): Promise<Booking[]> {
    return await prisma.booking.findMany({
      where: { customerId },
      include: {
        service: true,
        employee: true,
        session: {
          include: {
            location: true,
          },
        },
        invoice: true,
      },
      orderBy: {
        scheduledDate: 'desc',
      },
    });
  }

  /**
   * Get bookings for a specific employee
   */
  async getByEmployee(employeeId: string, prisma: any): Promise<Booking[]> {
    return await prisma.booking.findMany({
      where: { employeeId },
      include: {
        customer: true,
        service: true,
        session: {
          include: {
            location: true,
          },
        },
      },
      orderBy: {
        scheduledDate: 'asc',
      },
    });
  }

  /**
   * Generate unique booking number
   * Format: BKG-XXXXXX (e.g., BKG-A7K9M2)
   * Uses Base36 encoding (0-9, A-Z) for compact, non-sequential appearance
   * Combines timestamp + daily counter for uniqueness
   */
  private async generateBookingNumber(prisma: any): Promise<string> {
    const today = new Date();
    const dateStr = today.toISOString().split('T')[0]; // YYYY-MM-DD

    // Get count of bookings created today
    const count = await prisma.booking.count({
      where: {
        createdAt: {
          gte: new Date(dateStr + 'T00:00:00.000Z'),
          lt: new Date(new Date(dateStr).getTime() + 24 * 60 * 60 * 1000),
        },
      },
    });

    // Create unique number: YYMMDD + daily counter
    const year = today.getFullYear() % 100; // Last 2 digits of year (26)
    const month = today.getMonth() + 1; // 1-12
    const day = today.getDate(); // 1-31
    const counter = count + 1;

    // Combine into single number: YYMMDDCCC
    // Example: 26 01 11 001 = 26011001
    const numberStr = `${year.toString().padStart(2, '0')}${month.toString().padStart(2, '0')}${day
      .toString()
      .padStart(2, '0')}${counter.toString().padStart(3, '0')}`;
    const number = parseInt(numberStr);

    // Convert to Base36 (0-9, A-Z) for compact representation
    // Add offset to avoid too simple numbers
    const base36 = (number + 100000).toString(36).toUpperCase();

    // Pad to 6 characters for consistency
    const code = base36.padStart(6, '0');

    return `BKG-${code}`;
  }

  /**
   * Auto-assign employee based on configurable strategy
   * Supports: Round Robin, Availability, Priority, Same Employee, Workload Balance
   */
  private async autoAssignEmployee(
    serviceId: string,
    scheduledDate: string,
    scheduledTime: string,
    serviceDuration: number,
    prisma: any,
    organizationId: string,
    options: AssignmentOptions = {}
  ): Promise<string | undefined> {
    // Get service to check strategy and settings
    const service = await prisma.service.findUnique({
      where: { id: serviceId },
    });

    if (!service) {
      return undefined;
    }

    // Determine strategy (service override > options > default)
    const strategy = service.assignmentStrategy || options.strategy || AssignmentStrategy.WORKLOAD_BALANCE;

    // Calculate end time for overlap checking
    const [hours, minutes] = scheduledTime.split(':').map(Number);
    const startMinutes = hours * 60 + minutes;
    const endMinutes = startMinutes + serviceDuration;

    // Get all active employees
    const employees = await prisma.employee.findMany({
      where: {
        organizationId,
        status: 'ACTIVE',
      },
      include: {
        bookings: {
          where: {
            scheduledDate,
            status: {
              in: ['PENDING', 'CONFIRMED', 'PAID'],
            },
          },
          include: {
            service: {
              select: {
                duration: true,
              },
            },
          },
        },
      },
    });

    if (employees.length === 0) {
      return undefined;
    }

    // Filter out employees with time conflicts
    const availableEmployees = employees.filter((employee) => {
      return !employee.bookings.some((booking) => {
        const bookingStart = booking.scheduledTime;
        const [bHours, bMinutes] = bookingStart.split(':').map(Number);
        const bookingStartMinutes = bHours * 60 + bMinutes;
        const bookingEndMinutes = bookingStartMinutes + (booking.service?.duration || 60);

        // Check for time overlap
        const hasOverlap =
          (startMinutes >= bookingStartMinutes && startMinutes < bookingEndMinutes) ||
          (endMinutes > bookingStartMinutes && endMinutes <= bookingEndMinutes) ||
          (startMinutes <= bookingStartMinutes && endMinutes >= bookingEndMinutes);

        return hasOverlap;
      });
    });

    if (availableEmployees.length === 0) {
      return undefined;
    }

    // Apply assignment strategy
    switch (strategy) {
      case AssignmentStrategy.SAME_EMPLOYEE:
        return await this.assignSameEmployee(availableEmployees, options.customerId, prisma);

      case AssignmentStrategy.ROUND_ROBIN:
        return await this.assignRoundRobin(availableEmployees, organizationId, scheduledDate, prisma);

      case AssignmentStrategy.PRIORITY:
        return await this.assignByPriority(availableEmployees, options.priorityEmployeeIds);

      case AssignmentStrategy.AVAILABILITY:
        // Just return first available (maximize bookings)
        return availableEmployees[0].id;

      case AssignmentStrategy.WORKLOAD_BALANCE:
      default:
        // Balance based on current workload
        return await this.assignByWorkload(availableEmployees, scheduledDate, prisma);
    }
  }

  /**
   * SAME_EMPLOYEE: Try to assign the same employee the customer had before
   */
  private async assignSameEmployee(
    availableEmployees: any[],
    customerId: string | undefined,
    prisma: any
  ): Promise<string | undefined> {
    if (!customerId) {
      // Fallback to workload balance if no customer ID
      return availableEmployees.sort((a, b) => a.bookings.length - b.bookings.length)[0]?.id;
    }

    // Find customer's most recent booking
    const previousBooking = await prisma.booking.findFirst({
      where: {
        customerId,
        status: {
          in: ['COMPLETED', 'PAID'],
        },
      },
      orderBy: {
        createdAt: 'desc',
      },
      select: {
        employeeId: true,
      },
    });

    if (previousBooking?.employeeId) {
      // Check if previous employee is available
      const previousEmployee = availableEmployees.find((emp) => emp.id === previousBooking.employeeId);
      if (previousEmployee) {
        return previousEmployee.id;
      }
    }

    // Fallback to workload balance
    return availableEmployees.sort((a, b) => a.bookings.length - b.bookings.length)[0]?.id;
  }

  /**
   * ROUND_ROBIN: Distribute bookings evenly across all employees
   * Respects workloadPercentage for fair distribution
   *
   * Example:
   * - Employee A: 10 bookings, 100% workload → normalized = 10 / 1.0 = 10
   * - Employee B: 3 bookings, 10% workload  → normalized = 3 / 0.1 = 30
   * → Employee A gets next booking (has lower normalized count)
   */
  private async assignRoundRobin(
    availableEmployees: any[],
    organizationId: string,
    scheduledDate: string,
    prisma: any
  ): Promise<string | undefined> {
    // Get total booking counts for each employee in the period (e.g., last 30 days)
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

    const employeesWithCounts = await Promise.all(
      availableEmployees.map(async (emp) => {
        const count = await prisma.booking.count({
          where: {
            employeeId: emp.id,
            createdAt: {
              gte: thirtyDaysAgo,
            },
          },
        });

        // Get employee details for workloadPercentage
        const employee = await prisma.employee.findUnique({
          where: { id: emp.id },
          select: { workloadPercentage: true },
        });

        const workloadPercentage = employee?.workloadPercentage || 100;
        const workloadFactor = workloadPercentage / 100; // 0.1 for 10%, 1.0 for 100%

        // Normalize bookings based on workload
        // Someone with 10% workload should have proportionally fewer bookings
        const normalizedBookings = workloadFactor > 0 ? count / workloadFactor : 999999;

        return {
          ...emp,
          totalBookings: count,
          workloadPercentage,
          normalizedBookings,
        };
      })
    );

    // Sort by normalized bookings (ascending) to balance fairly
    employeesWithCounts.sort((a, b) => a.normalizedBookings - b.normalizedBookings);

    return employeesWithCounts[0]?.id;
  }

  /**
   * PRIORITY: Assign based on priority ranking
   */
  private async assignByPriority(
    availableEmployees: any[],
    priorityEmployeeIds?: string[]
  ): Promise<string | undefined> {
    if (!priorityEmployeeIds || priorityEmployeeIds.length === 0) {
      // No priority defined, fallback to first available
      return availableEmployees[0]?.id;
    }

    // Find first employee that matches priority list
    for (const priorityId of priorityEmployeeIds) {
      const priorityEmployee = availableEmployees.find((emp) => emp.id === priorityId);
      if (priorityEmployee) {
        return priorityEmployee.id;
      }
    }

    // No priority employee available, return first available
    return availableEmployees[0]?.id;
  }

  /**
   * WORKLOAD_BALANCE: Assign to employee with least bookings on that specific day
   */
  private async assignByWorkload(availableEmployees: any[], scheduledDate: string, prisma: any): Promise<string | undefined> {
    // Sort by number of bookings on the specific date
    availableEmployees.sort((a, b) => a.bookings.length - b.bookings.length);

    return availableEmployees[0]?.id;
  }
}

export const bookingService = new BookingService();
