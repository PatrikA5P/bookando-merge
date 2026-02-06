import { PrismaClient, Customer } from '@prisma/client';

/**
 * Customer Service
 *
 * IMPORTANT: This service uses the scoped Prisma client from req.prisma
 * which automatically filters by organizationId (Multi-Tenancy)
 */
export class CustomerService {
  /**
   * Get all customers (scoped to organization)
   * @param prisma Scoped Prisma client
   * @param filters Optional filters: { status?: string, search?: string }
   */
  async getAll(prisma: any, filters?: { status?: string; search?: string }): Promise<Customer[]> {
    const where: any = {};

    // Status filter
    if (filters?.status) {
      where.status = filters.status;
    }

    // Search filter
    if (filters?.search) {
      where.OR = [
        { firstName: { contains: filters.search, mode: 'insensitive' } },
        { lastName: { contains: filters.search, mode: 'insensitive' } },
        { email: { contains: filters.search, mode: 'insensitive' } },
        { phone: { contains: filters.search, mode: 'insensitive' } }
      ];
    }

    return await prisma.customer.findMany({
      where,
      orderBy: { createdAt: 'desc' },
      include: {
        bookings: {
          take: 5,
          orderBy: { createdAt: 'desc' }
        },
        invoices: {
          take: 5,
          orderBy: { createdAt: 'desc' }
        }
      }
    });
  }

  /**
   * Get customer by ID (scoped to organization)
   */
  async getById(id: string, prisma: any): Promise<Customer | null> {
    return await prisma.customer.findUnique({
      where: { id },
      include: {
        bookings: {
          orderBy: { createdAt: 'desc' }
        },
        invoices: {
          orderBy: { createdAt: 'desc' }
        },
        enrollments: {
          include: { course: true }
        },
        educationCards: {
          include: { card: true }
        }
      }
    });
  }

  /**
   * Create customer (organizationId auto-injected)
   */
  async create(data: {
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
  }, prisma: any): Promise<Customer> {
    return await prisma.customer.create({
      data: {
        ...data,
        status: 'ACTIVE'
      }
    });
  }

  /**
   * Update customer (scoped to organization)
   */
  async update(id: string, data: Partial<Customer>, prisma: any): Promise<Customer> {
    return await prisma.customer.update({
      where: { id },
      data
    });
  }

  /**
   * Delete customer (soft delete - mark as ARCHIVED)
   */
  async delete(id: string, prisma: any): Promise<Customer> {
    return await prisma.customer.update({
      where: { id },
      data: { status: 'ARCHIVED' }
    });
  }

  /**
   * Find or create customer by email
   */
  async findOrCreate(email: string, organizationId: string, prisma: any): Promise<Customer> {
    // Try to find
    let customer = await prisma.customer.findFirst({
      where: { email }
    });

    // Create if not exists
    if (!customer) {
      customer = await prisma.customer.create({
        data: {
          email,
          firstName: '',
          lastName: '',
          status: 'ACTIVE'
        }
      });
    }

    return customer;
  }

  /**
   * Search customers (scoped to organization)
   */
  async search(query: string, prisma: any): Promise<Customer[]> {
    return await prisma.customer.findMany({
      where: {
        OR: [
          { firstName: { contains: query, mode: 'insensitive' } },
          { lastName: { contains: query, mode: 'insensitive' } },
          { email: { contains: query, mode: 'insensitive' } },
          { phone: { contains: query, mode: 'insensitive' } }
        ]
      },
      orderBy: { createdAt: 'desc' },
      take: 20
    });
  }
}
