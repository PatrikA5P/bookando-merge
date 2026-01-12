import { PrismaClient, Service, ServiceCategory, ServiceStatus } from '@prisma/client';
import { AssignmentStrategy } from './BookingService';

/**
 * Event Types define how many hosts and participants a service can have
 * - ONE_ON_ONE: 1 host, 1 participant (e.g., private driving lesson)
 * - GROUP_SINGLE_HOST: 1 host, multiple participants (e.g., Grundkurs, VKU)
 * - GROUP_MULTI_HOST: Multiple hosts per session, multiple participants (e.g., advanced courses)
 * - COLLECTIVE: Multiple hosts, 1 participant (e.g., driving exam with instructor + examiner)
 */
export enum EventType {
  ONE_ON_ONE = 'ONE_ON_ONE',
  GROUP_SINGLE_HOST = 'GROUP_SINGLE_HOST',
  GROUP_MULTI_HOST = 'GROUP_MULTI_HOST',
  COLLECTIVE = 'COLLECTIVE',
}

interface CreateServiceData {
  name: string;
  description?: string;
  category: ServiceCategory;
  price: number;
  duration: number; // in minutes
  maxParticipants?: number;
  requiresLicense?: boolean;
  isOnline?: boolean;
  eventType?: EventType;
  assignmentStrategy?: AssignmentStrategy;
  customFields?: any;
}

interface UpdateServiceData {
  name?: string;
  description?: string;
  category?: ServiceCategory;
  price?: number;
  duration?: number;
  maxParticipants?: number;
  requiresLicense?: boolean;
  isOnline?: boolean;
  eventType?: EventType;
  assignmentStrategy?: AssignmentStrategy;
  status?: ServiceStatus;
  customFields?: any;
}

export class ServiceService {
  /**
   * Get all services for the organization
   */
  async getAll(
    prisma: any,
    filters?: {
      category?: ServiceCategory;
      status?: ServiceStatus;
      isOnline?: boolean;
    }
  ): Promise<Service[]> {
    const where: any = {};

    if (filters?.category) {
      where.category = filters.category;
    }

    if (filters?.status) {
      where.status = filters.status;
    }

    if (filters?.isOnline !== undefined) {
      where.isOnline = filters.isOnline;
    }

    return await prisma.service.findMany({
      where,
      include: {
        _count: {
          select: {
            bookings: true,
            courses: true,
          },
        },
      },
      orderBy: {
        name: 'asc',
      },
    });
  }

  /**
   * Get active services only (for customer booking)
   */
  async getActive(prisma: any, category?: ServiceCategory): Promise<Service[]> {
    const where: any = {
      status: 'ACTIVE',
    };

    if (category) {
      where.category = category;
    }

    return await prisma.service.findMany({
      where,
      orderBy: {
        name: 'asc',
      },
    });
  }

  /**
   * Get service by ID
   */
  async getById(id: string, prisma: any): Promise<Service | null> {
    return await prisma.service.findUnique({
      where: { id },
      include: {
        bookings: {
          take: 10,
          orderBy: {
            createdAt: 'desc',
          },
          include: {
            customer: {
              select: {
                id: true,
                firstName: true,
                lastName: true,
              },
            },
          },
        },
        courses: {
          take: 10,
          orderBy: {
            createdAt: 'desc',
          },
        },
      },
    });
  }

  /**
   * Create a new service
   */
  async create(data: CreateServiceData, prisma: any, organizationId: string): Promise<Service> {
    return await prisma.service.create({
      data: {
        ...data,
        status: 'ACTIVE',
        organizationId,
      },
    });
  }

  /**
   * Update service
   */
  async update(id: string, data: UpdateServiceData, prisma: any): Promise<Service> {
    const existing = await prisma.service.findUnique({
      where: { id },
    });

    if (!existing) {
      throw new Error('Service not found');
    }

    return await prisma.service.update({
      where: { id },
      data,
    });
  }

  /**
   * Delete service (soft delete to ARCHIVED)
   */
  async delete(id: string, prisma: any): Promise<Service> {
    // Check if service has active bookings
    const activeBookings = await prisma.booking.count({
      where: {
        serviceId: id,
        status: {
          in: ['PENDING', 'CONFIRMED', 'PAID'],
        },
      },
    });

    if (activeBookings > 0) {
      throw new Error('Cannot delete service with active bookings. Please archive instead.');
    }

    return await prisma.service.update({
      where: { id },
      data: {
        status: 'ARCHIVED',
      },
    });
  }

  /**
   * Activate service
   */
  async activate(id: string, prisma: any): Promise<Service> {
    return await prisma.service.update({
      where: { id },
      data: {
        status: 'ACTIVE',
      },
    });
  }

  /**
   * Deactivate service
   */
  async deactivate(id: string, prisma: any): Promise<Service> {
    return await prisma.service.update({
      where: { id },
      data: {
        status: 'INACTIVE',
      },
    });
  }

  /**
   * Get services by category
   */
  async getByCategory(category: ServiceCategory, prisma: any): Promise<Service[]> {
    return await prisma.service.findMany({
      where: {
        category,
        status: 'ACTIVE',
      },
      orderBy: {
        name: 'asc',
      },
    });
  }

  /**
   * Search services
   */
  async search(query: string, prisma: any): Promise<Service[]> {
    return await prisma.service.findMany({
      where: {
        OR: [
          {
            name: {
              contains: query,
              mode: 'insensitive',
            },
          },
          {
            description: {
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
   * Get popular services (most bookings)
   */
  async getPopular(prisma: any, limit: number = 5): Promise<Service[]> {
    return await prisma.service.findMany({
      where: {
        status: 'ACTIVE',
      },
      include: {
        _count: {
          select: {
            bookings: true,
          },
        },
      },
      orderBy: {
        bookings: {
          _count: 'desc',
        },
      },
      take: limit,
    });
  }

  /**
   * Create default Swiss driving school services
   * Includes: Grundkurs (1-3), VKU, Nothelfer, 2-Phasen, Fahrstunden (Auto/Motorrad), Offroad
   */
  async createDefaultServices(prisma: any, organizationId: string): Promise<Service[]> {
    const defaultServices = [
      // === GRUNDKURS (Parts 1-3) ===
      {
        name: 'Grundkurs Teil 1',
        description: 'Obligatorischer Grundkurs Teil 1 für Motorrad-Lernfahrausweis',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 120,
        duration: 480, // 8 hours
        maxParticipants: 12,
        requiresLicense: false,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.ROUND_ROBIN,
      },
      {
        name: 'Grundkurs Teil 2',
        description: 'Obligatorischer Grundkurs Teil 2 für Motorrad-Lernfahrausweis',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 120,
        duration: 480,
        maxParticipants: 12,
        requiresLicense: false,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.ROUND_ROBIN,
      },
      {
        name: 'Grundkurs Teil 3',
        description: 'Obligatorischer Grundkurs Teil 3 für Motorrad-Lernfahrausweis',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 120,
        duration: 480,
        maxParticipants: 12,
        requiresLicense: false,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.ROUND_ROBIN,
      },

      // === THEORY COURSES ===
      {
        name: 'Verkehrskunde (VKU)',
        description: 'Obligatorischer Verkehrskundekurs (4 Abende à 2 Stunden)',
        category: 'THEORY' as ServiceCategory,
        price: 280,
        duration: 480, // 8 hours total
        maxParticipants: 15,
        requiresLicense: false,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.ROUND_ROBIN,
      },
      {
        name: 'Nothelferkurs',
        description: 'Obligatorischer Nothelferkurs (10 Stunden)',
        category: 'FIRST_AID' as ServiceCategory,
        price: 150,
        duration: 600, // 10 hours
        maxParticipants: 20,
        requiresLicense: false,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.AVAILABILITY,
      },

      // === ADVANCED TRAINING ===
      {
        name: '2-Phasen Kurs',
        description: 'Obligatorischer 2-Phasen Weiterausbildungskurs',
        category: 'ADVANCED_TRAINING' as ServiceCategory,
        price: 400,
        duration: 480, // 8 hours
        maxParticipants: 12,
        requiresLicense: true,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.ROUND_ROBIN,
      },

      // === MOTORRAD FAHRSTUNDEN (60-120 Min) ===
      {
        name: 'Fahrstunde Motorrad 60 Min',
        description: 'Praktische Motorrad-Fahrlektion (60 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 95,
        duration: 60,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },
      {
        name: 'Fahrstunde Motorrad 75 Min',
        description: 'Praktische Motorrad-Fahrlektion (75 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 115,
        duration: 75,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },
      {
        name: 'Fahrstunde Motorrad 90 Min',
        description: 'Praktische Motorrad-Fahrlektion (90 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 135,
        duration: 90,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },
      {
        name: 'Fahrstunde Motorrad 105 Min',
        description: 'Praktische Motorrad-Fahrlektion (105 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 155,
        duration: 105,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },
      {
        name: 'Fahrstunde Motorrad 120 Min',
        description: 'Praktische Motorrad-Fahrlektion (120 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 175,
        duration: 120,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },

      // === AUTO FAHRSTUNDEN (60-120 Min) ===
      {
        name: 'Fahrstunde Auto 60 Min',
        description: 'Praktische Auto-Fahrlektion (60 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 90,
        duration: 60,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },
      {
        name: 'Fahrstunde Auto 75 Min',
        description: 'Praktische Auto-Fahrlektion (75 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 110,
        duration: 75,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },
      {
        name: 'Fahrstunde Auto 90 Min',
        description: 'Praktische Auto-Fahrlektion (90 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 130,
        duration: 90,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },
      {
        name: 'Fahrstunde Auto 105 Min',
        description: 'Praktische Auto-Fahrlektion (105 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 150,
        duration: 105,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },
      {
        name: 'Fahrstunde Auto 120 Min',
        description: 'Praktische Auto-Fahrlektion (120 Minuten)',
        category: 'DRIVING_LESSON' as ServiceCategory,
        price: 170,
        duration: 120,
        maxParticipants: 1,
        requiresLicense: true,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.SAME_EMPLOYEE,
      },

      // === OFFROAD TRAINING (Motorrad) ===
      {
        name: 'Offroadtraining Basic',
        description: 'Einführung ins Offroad-Fahren für Motorräder',
        category: 'ADVANCED_TRAINING' as ServiceCategory,
        price: 350,
        duration: 480, // 8 hours
        maxParticipants: 8,
        requiresLicense: true,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.PRIORITY,
      },
      {
        name: 'Offroadtraining Advanced',
        description: 'Fortgeschrittenes Offroad-Training für Motorräder',
        category: 'ADVANCED_TRAINING' as ServiceCategory,
        price: 400,
        duration: 480,
        maxParticipants: 8,
        requiresLicense: true,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.PRIORITY,
      },
      {
        name: 'Offroadtraining Travel',
        description: 'Offroad-Training für Reise-Enduro Fahrer',
        category: 'ADVANCED_TRAINING' as ServiceCategory,
        price: 450,
        duration: 480,
        maxParticipants: 6,
        requiresLicense: true,
        eventType: EventType.GROUP_SINGLE_HOST,
        assignmentStrategy: AssignmentStrategy.PRIORITY,
      },

      // === OTHER ===
      {
        name: 'Schnupperstunde',
        description: 'Kostenlose Schnupperstunde zum Kennenlernen',
        category: 'OTHER' as ServiceCategory,
        price: 0,
        duration: 30,
        maxParticipants: 1,
        requiresLicense: false,
        eventType: EventType.ONE_ON_ONE,
        assignmentStrategy: AssignmentStrategy.AVAILABILITY,
      },
    ];

    const createdServices: Service[] = [];

    for (const serviceData of defaultServices) {
      const existing = await prisma.service.findFirst({
        where: {
          name: serviceData.name,
          organizationId,
        },
      });

      if (!existing) {
        const service = await this.create(serviceData, prisma, organizationId);
        createdServices.push(service);
      }
    }

    return createdServices;
  }
}

export const serviceService = new ServiceService();
