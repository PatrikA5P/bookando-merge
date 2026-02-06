import { PrismaClient, Employee, EmployeeStatus } from '@prisma/client';

interface CreateEmployeeData {
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  address?: string;
  dateOfBirth?: string;
  hireDate: string;
  position: string;
  department?: string;
  hourlyRate?: number;
  customFields?: any;
}

interface UpdateEmployeeData {
  firstName?: string;
  lastName?: string;
  email?: string;
  phone?: string;
  address?: string;
  dateOfBirth?: string;
  hireDate?: string;
  position?: string;
  department?: string;
  hourlyRate?: number;
  status?: EmployeeStatus;
  customFields?: any;
}

export class EmployeeService {
  /**
   * Get all employees for the organization
   */
  async getAll(
    prisma: any,
    filters?: {
      status?: EmployeeStatus;
      department?: string;
    }
  ): Promise<Employee[]> {
    const where: any = {};

    if (filters?.status) {
      where.status = filters.status;
    }

    if (filters?.department) {
      where.department = filters.department;
    }

    return await prisma.employee.findMany({
      where,
      include: {
        user: {
          select: {
            id: true,
            email: true,
            role: {
              select: {
                id: true,
                name: true,
              },
            },
          },
        },
        _count: {
          select: {
            bookings: true,
          },
        },
      },
      orderBy: {
        lastName: 'asc',
      },
    });
  }

  /**
   * Get active employees only
   */
  async getActive(prisma: any): Promise<Employee[]> {
    return await prisma.employee.findMany({
      where: {
        status: 'ACTIVE',
      },
      orderBy: {
        lastName: 'asc',
      },
    });
  }

  /**
   * Get employee by ID
   */
  async getById(id: string, prisma: any): Promise<Employee | null> {
    return await prisma.employee.findUnique({
      where: { id },
      include: {
        user: {
          select: {
            id: true,
            email: true,
            firstName: true,
            lastName: true,
            role: true,
          },
        },
        bookings: {
          take: 20,
          orderBy: {
            scheduledDate: 'desc',
          },
          include: {
            customer: {
              select: {
                id: true,
                firstName: true,
                lastName: true,
              },
            },
            service: {
              select: {
                id: true,
                name: true,
              },
            },
          },
        },
      },
    });
  }

  /**
   * Create a new employee
   */
  async create(data: CreateEmployeeData, prisma: any, organizationId: string): Promise<Employee> {
    // Check if email is already used
    const existing = await prisma.employee.findFirst({
      where: {
        email: data.email,
        organizationId,
      },
    });

    if (existing) {
      throw new Error('Employee with this email already exists');
    }

    return await prisma.employee.create({
      data: {
        ...data,
        status: 'ACTIVE',
        organizationId,
      },
    });
  }

  /**
   * Update employee
   */
  async update(id: string, data: UpdateEmployeeData, prisma: any): Promise<Employee> {
    const existing = await prisma.employee.findUnique({
      where: { id },
    });

    if (!existing) {
      throw new Error('Employee not found');
    }

    // If email is being updated, check for duplicates
    if (data.email && data.email !== existing.email) {
      const duplicate = await prisma.employee.findFirst({
        where: {
          email: data.email,
          organizationId: existing.organizationId,
          id: {
            not: id,
          },
        },
      });

      if (duplicate) {
        throw new Error('Another employee with this email already exists');
      }
    }

    return await prisma.employee.update({
      where: { id },
      data,
    });
  }

  /**
   * Delete employee (soft delete to ARCHIVED)
   */
  async delete(id: string, prisma: any): Promise<Employee> {
    // Check if employee has future bookings
    const futureBookings = await prisma.booking.count({
      where: {
        employeeId: id,
        scheduledDate: {
          gte: new Date().toISOString().split('T')[0],
        },
        status: {
          in: ['PENDING', 'CONFIRMED', 'PAID'],
        },
      },
    });

    if (futureBookings > 0) {
      throw new Error(
        'Cannot delete employee with future bookings. Please reassign bookings first or archive instead.'
      );
    }

    return await prisma.employee.update({
      where: { id },
      data: {
        status: 'ARCHIVED',
      },
    });
  }

  /**
   * Activate employee
   */
  async activate(id: string, prisma: any): Promise<Employee> {
    return await prisma.employee.update({
      where: { id },
      data: {
        status: 'ACTIVE',
      },
    });
  }

  /**
   * Deactivate employee
   */
  async deactivate(id: string, prisma: any): Promise<Employee> {
    return await prisma.employee.update({
      where: { id },
      data: {
        status: 'INACTIVE',
      },
    });
  }

  /**
   * Get employee schedule for a specific date
   */
  async getSchedule(
    employeeId: string,
    date: string,
    prisma: any
  ): Promise<{
    employee: Employee;
    bookings: any[];
    availableSlots: string[];
  }> {
    const employee = await prisma.employee.findUnique({
      where: { id: employeeId },
    });

    if (!employee) {
      throw new Error('Employee not found');
    }

    // Get all bookings for the employee on this date
    const bookings = await prisma.booking.findMany({
      where: {
        employeeId,
        scheduledDate: date,
        status: {
          in: ['PENDING', 'CONFIRMED', 'PAID'],
        },
      },
      include: {
        customer: {
          select: {
            id: true,
            firstName: true,
            lastName: true,
          },
        },
        service: {
          select: {
            id: true,
            name: true,
            duration: true,
          },
        },
      },
      orderBy: {
        scheduledTime: 'asc',
      },
    });

    // Calculate available time slots (simplified - could be enhanced with work hours)
    const workStart = 8; // 8 AM
    const workEnd = 18; // 6 PM
    const slotDuration = 60; // 1 hour slots

    const availableSlots: string[] = [];

    for (let hour = workStart; hour < workEnd; hour++) {
      const timeSlot = `${hour.toString().padStart(2, '0')}:00`;

      // Check if this slot conflicts with any booking
      const hasConflict = bookings.some((booking) => {
        const bookingStartHour = parseInt(booking.scheduledTime.split(':')[0]);
        const bookingDurationHours = Math.ceil(booking.service.duration / 60);
        const bookingEndHour = bookingStartHour + bookingDurationHours;

        return hour >= bookingStartHour && hour < bookingEndHour;
      });

      if (!hasConflict) {
        availableSlots.push(timeSlot);
      }
    }

    return {
      employee,
      bookings,
      availableSlots,
    };
  }

  /**
   * Get employee workload statistics
   */
  async getWorkloadStats(
    employeeId: string,
    dateFrom: string,
    dateTo: string,
    prisma: any
  ): Promise<{
    totalBookings: number;
    completedBookings: number;
    pendingBookings: number;
    cancelledBookings: number;
    totalHours: number;
    totalRevenue: number;
  }> {
    const bookings = await prisma.booking.findMany({
      where: {
        employeeId,
        scheduledDate: {
          gte: dateFrom,
          lte: dateTo,
        },
      },
      include: {
        service: {
          select: {
            duration: true,
          },
        },
      },
    });

    const stats = {
      totalBookings: bookings.length,
      completedBookings: bookings.filter((b) => b.status === 'COMPLETED').length,
      pendingBookings: bookings.filter((b) => b.status === 'PENDING').length,
      cancelledBookings: bookings.filter((b) => b.status === 'CANCELLED').length,
      totalHours: bookings.reduce((sum, b) => sum + b.service.duration / 60, 0),
      totalRevenue: bookings
        .filter((b) => b.status === 'COMPLETED' && b.paymentStatus === 'PAID')
        .reduce((sum, b) => sum + b.totalPrice, 0),
    };

    return stats;
  }

  /**
   * Search employees
   */
  async search(query: string, prisma: any): Promise<Employee[]> {
    return await prisma.employee.findMany({
      where: {
        OR: [
          {
            firstName: {
              contains: query,
              mode: 'insensitive',
            },
          },
          {
            lastName: {
              contains: query,
              mode: 'insensitive',
            },
          },
          {
            email: {
              contains: query,
              mode: 'insensitive',
            },
          },
        ],
        status: 'ACTIVE',
      },
      take: 20,
    });
  }

  /**
   * Get employees by department
   */
  async getByDepartment(department: string, prisma: any): Promise<Employee[]> {
    return await prisma.employee.findMany({
      where: {
        department,
        status: 'ACTIVE',
      },
      orderBy: {
        lastName: 'asc',
      },
    });
  }

  /**
   * Check if employee is available at a specific time
   */
  async isAvailable(
    employeeId: string,
    date: string,
    time: string,
    durationMinutes: number,
    prisma: any
  ): Promise<boolean> {
    const employee = await prisma.employee.findUnique({
      where: { id: employeeId },
    });

    if (!employee || employee.status !== 'ACTIVE') {
      return false;
    }

    // Calculate end time
    const [hours, minutes] = time.split(':').map(Number);
    const startMinutes = hours * 60 + minutes;
    const endMinutes = startMinutes + durationMinutes;

    // Get all bookings for this employee on this date
    const bookings = await prisma.booking.findMany({
      where: {
        employeeId,
        scheduledDate: date,
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
    });

    // Check for time conflicts
    for (const booking of bookings) {
      const [bHours, bMinutes] = booking.scheduledTime.split(':').map(Number);
      const bookingStartMinutes = bHours * 60 + bMinutes;
      const bookingEndMinutes = bookingStartMinutes + booking.service.duration;

      // Check for overlap
      const hasOverlap =
        (startMinutes >= bookingStartMinutes && startMinutes < bookingEndMinutes) ||
        (endMinutes > bookingStartMinutes && endMinutes <= bookingEndMinutes) ||
        (startMinutes <= bookingStartMinutes && endMinutes >= bookingEndMinutes);

      if (hasOverlap) {
        return false;
      }
    }

    return true;
  }
}

export const employeeService = new EmployeeService();
