/**
 * COURSE SERVICE - Full Implementation
 *
 * Handles all course management operations including:
 * - CRUD operations for courses
 * - Course session management
 * - Enrollment handling
 * - Booking window validation
 * - Capacity management
 * - Tag management
 */

import { PrismaClient } from '@prisma/client';
import { EventEmitter } from 'events';

export const courseEvents = new EventEmitter();

export class CourseService {
  /**
   * Get all courses (scoped to organization)
   */
  async getAll(prisma: any, filters?: any) {
    const where: any = {};

    if (filters) {
      if (filters.status) where.status = filters.status;
      if (filters.categoryId) where.categoryId = filters.categoryId;
      if (filters.type) where.type = filters.type;
      if (filters.showOnWebsite !== undefined) where.showOnWebsite = filters.showOnWebsite;
      if (filters.published !== undefined) where.published = filters.published;
      if (filters.search) {
        where.OR = [
          { title: { contains: filters.search, mode: 'insensitive' } },
          { description: { contains: filters.search, mode: 'insensitive' } },
        ];
      }
    }

    return await prisma.course.findMany({
      where,
      include: {
        category: true,
        tags: {
          include: {
            tag: true,
          },
        },
        defaultLocation: true,
        organizer: {
          select: {
            id: true,
            user: {
              select: {
                firstName: true,
                lastName: true,
                email: true,
              },
            },
          },
        },
        sessions: {
          include: {
            instructor: {
              select: {
                id: true,
                user: {
                  select: {
                    firstName: true,
                    lastName: true,
                  },
                },
              },
            },
            location: true,
          },
          orderBy: [{ date: 'asc' }, { startTime: 'asc' }],
        },
        _count: {
          select: {
            sessions: true,
            enrollments: true,
          },
        },
      },
      orderBy: { createdAt: 'desc' },
    });
  }

  /**
   * Get course by ID
   */
  async getById(id: string, prisma: any) {
    return await prisma.course.findUnique({
      where: { id },
      include: {
        category: true,
        tags: {
          include: {
            tag: true,
          },
        },
        defaultLocation: {
          include: {
            rooms: true,
          },
        },
        organizer: {
          select: {
            id: true,
            user: {
              select: {
                firstName: true,
                lastName: true,
                email: true,
              },
            },
          },
        },
        sessions: {
          include: {
            instructor: {
              select: {
                id: true,
                user: {
                  select: {
                    firstName: true,
                    lastName: true,
                  },
                },
              },
            },
            location: true,
            room: true,
            bookings: {
              include: {
                customer: true,
              },
            },
          },
          orderBy: [{ date: 'asc' }, { startTime: 'asc' }],
        },
        enrollments: {
          include: {
            customer: true,
          },
          orderBy: { enrolledAt: 'desc' },
        },
        _count: {
          select: {
            sessions: true,
            enrollments: true,
          },
        },
      },
    });
  }

  /**
   * Create course
   */
  async create(data: any, prisma: any) {
    const {
      tagIds,
      ...courseData
    } = data;

    // Create course with tags
    const course = await prisma.course.create({
      data: {
        ...courseData,
        tags: tagIds && tagIds.length > 0 ? {
          create: tagIds.map((tagId: string) => ({
            tag: { connect: { id: tagId } },
          })),
        } : undefined,
      },
      include: {
        category: true,
        tags: {
          include: {
            tag: true,
          },
        },
        defaultLocation: true,
        organizer: true,
      },
    });

    // Emit event
    courseEvents.emit('course:created', { course });

    return course;
  }

  /**
   * Update course
   */
  async update(id: string, data: any, prisma: any) {
    const {
      tagIds,
      ...courseData
    } = data;

    // If tagIds provided, update tags
    if (tagIds !== undefined) {
      // Delete existing tags
      await prisma.courseTag.deleteMany({
        where: { courseId: id },
      });

      // Create new tags
      if (tagIds.length > 0) {
        await prisma.courseTag.createMany({
          data: tagIds.map((tagId: string) => ({
            courseId: id,
            tagId,
          })),
        });
      }
    }

    // Update course
    const course = await prisma.course.update({
      where: { id },
      data: courseData,
      include: {
        category: true,
        tags: {
          include: {
            tag: true,
          },
        },
        defaultLocation: true,
        organizer: true,
        sessions: true,
      },
    });

    // Emit event
    courseEvents.emit('course:updated', { course });

    return course;
  }

  /**
   * Delete course (soft delete by archiving)
   */
  async delete(id: string, prisma: any) {
    const course = await prisma.course.update({
      where: { id },
      data: { status: 'ARCHIVED' },
    });

    // Emit event
    courseEvents.emit('course:deleted', { course });

    return course;
  }

  /**
   * Publish course
   */
  async publish(id: string, prisma: any) {
    const course = await prisma.course.update({
      where: { id },
      data: {
        published: true,
        status: 'ACTIVE',
      },
    });

    // Emit event (for notifications)
    courseEvents.emit('course:published', { course });

    return course;
  }

  /**
   * Unpublish course
   */
  async unpublish(id: string, prisma: any) {
    const course = await prisma.course.update({
      where: { id },
      data: {
        published: false,
        status: 'DRAFT',
      },
    });

    return course;
  }

  /**
   * Check if booking window is open for a course
   */
  async isBookingWindowOpen(courseId: string, prisma: any): Promise<boolean> {
    const course = await prisma.course.findUnique({
      where: { id: courseId },
      select: {
        bookingStartsImmediately: true,
        bookingStartDate: true,
        bookingStartTime: true,
        bookingClosesOnStart: true,
        bookingEndDate: true,
        bookingEndTime: true,
        sessions: {
          select: { date: true, startTime: true },
          orderBy: { date: 'asc' },
          take: 1,
        },
      },
    });

    if (!course) return false;

    const now = new Date();

    // If booking starts immediately, it's always open (unless closed on start)
    if (course.bookingStartsImmediately) {
      if (course.bookingClosesOnStart && course.sessions.length > 0) {
        const firstSession = course.sessions[0];
        const sessionStart = new Date(`${firstSession.date}T${firstSession.startTime}`);
        return now < sessionStart;
      }
      return true;
    }

    // Check booking start
    if (course.bookingStartDate) {
      const startDateTime = new Date(
        `${course.bookingStartDate}T${course.bookingStartTime || '00:00:00'}`
      );
      if (now < startDateTime) {
        return false;
      }
    }

    // Check booking end
    if (course.bookingClosesOnStart && course.sessions.length > 0) {
      const firstSession = course.sessions[0];
      const sessionStart = new Date(`${firstSession.date}T${firstSession.startTime}`);
      if (now >= sessionStart) {
        return false;
      }
    } else if (course.bookingEndDate) {
      const endDateTime = new Date(
        `${course.bookingEndDate}T${course.bookingEndTime || '23:59:59'}`
      );
      if (now > endDateTime) {
        return false;
      }
    }

    return true;
  }

  /**
   * Get available spots for a course
   */
  async getAvailableSpots(courseId: string, prisma: any): Promise<number> {
    const course = await prisma.course.findUnique({
      where: { id: courseId },
      select: {
        capacity: true,
        _count: {
          select: {
            enrollments: true,
          },
        },
      },
    });

    if (!course || !course.capacity) {
      return Infinity; // No limit
    }

    return course.capacity - course._count.enrollments;
  }

  /**
   * Check if course is full
   */
  async isFull(courseId: string, prisma: any): Promise<boolean> {
    const availableSpots = await this.getAvailableSpots(courseId, prisma);
    return availableSpots <= 0;
  }

  /**
   * Enroll customer in course
   */
  async enroll(courseId: string, customerId: string, prisma: any) {
    // Check if booking window is open
    const isOpen = await this.isBookingWindowOpen(courseId, prisma);
    if (!isOpen) {
      throw new Error('Booking window is closed');
    }

    // Check if course is full
    const full = await this.isFull(courseId, prisma);
    if (full) {
      throw new Error('Course is full');
    }

    // Check if customer is already enrolled
    const existing = await prisma.enrollment.findUnique({
      where: {
        customerId_courseId: {
          customerId,
          courseId,
        },
      },
    });

    if (existing) {
      throw new Error('Customer is already enrolled in this course');
    }

    // Create enrollment
    const enrollment = await prisma.enrollment.create({
      data: {
        customerId,
        courseId,
        progress: [],
      },
      include: {
        customer: true,
        course: true,
      },
    });

    // Emit event
    courseEvents.emit('course:enrolled', { enrollment });

    // Check if course should be marked as full
    const isFull = await this.isFull(courseId, prisma);
    if (isFull) {
      await prisma.course.update({
        where: { id: courseId },
        data: { status: 'FULL' },
      });
    }

    return enrollment;
  }

  /**
   * Unenroll customer from course
   */
  async unenroll(courseId: string, customerId: string, prisma: any) {
    const enrollment = await prisma.enrollment.delete({
      where: {
        customerId_courseId: {
          customerId,
          courseId,
        },
      },
    });

    // Emit event
    courseEvents.emit('course:unenrolled', { enrollment });

    // Update course status if it was full
    const course = await prisma.course.findUnique({
      where: { id: courseId },
      select: { status: true },
    });

    if (course?.status === 'FULL') {
      await prisma.course.update({
        where: { id: courseId },
        data: { status: 'ACTIVE' },
      });
    }

    return enrollment;
  }

  /**
   * Create course session
   */
  async createSession(courseId: string, sessionData: any, prisma: any) {
    const session = await prisma.courseSession.create({
      data: {
        courseId,
        ...sessionData,
        status: 'SCHEDULED',
        currentEnrollment: 0,
      },
      include: {
        course: true,
        instructor: true,
        location: true,
        room: true,
      },
    });

    // Emit event
    courseEvents.emit('session:created', { session });

    return session;
  }

  /**
   * Update course session
   */
  async updateSession(sessionId: string, sessionData: any, prisma: any) {
    const session = await prisma.courseSession.update({
      where: { id: sessionId },
      data: sessionData,
      include: {
        course: true,
        instructor: true,
        location: true,
        room: true,
      },
    });

    // Emit event (for notifications to participants)
    courseEvents.emit('session:updated', { session });

    return session;
  }

  /**
   * Delete course session
   */
  async deleteSession(sessionId: string, prisma: any) {
    const session = await prisma.courseSession.update({
      where: { id: sessionId },
      data: { status: 'CANCELLED' },
    });

    // Emit event
    courseEvents.emit('session:cancelled', { session });

    return session;
  }

  /**
   * Get sessions for a course
   */
  async getSessions(courseId: string, prisma: any) {
    return await prisma.courseSession.findMany({
      where: { courseId },
      include: {
        instructor: {
          select: {
            id: true,
            user: {
              select: {
                firstName: true,
                lastName: true,
              },
            },
          },
        },
        location: true,
        room: true,
        _count: {
          select: {
            bookings: true,
          },
        },
      },
      orderBy: [{ date: 'asc' }, { startTime: 'asc' }],
    });
  }

  /**
   * Get enrollments for a course
   */
  async getEnrollments(courseId: string, prisma: any) {
    return await prisma.enrollment.findMany({
      where: { courseId },
      include: {
        customer: true,
      },
      orderBy: { enrolledAt: 'desc' },
    });
  }
}

export const courseService = new CourseService();
