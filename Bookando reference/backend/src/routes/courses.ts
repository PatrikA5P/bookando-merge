import express from 'express';
import { body, validationResult } from 'express-validator';
import { courseService } from '../services/CourseService';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';

const router = express.Router();

// Apply authentication and tenancy to all routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/courses
 * Get all courses for organization
 */
router.get('/', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { status, categoryId, type, showOnWebsite, published, search } = req.query;

    const courses = await courseService.getAll(req.prisma, {
      status,
      categoryId,
      type,
      showOnWebsite: showOnWebsite === 'true' ? true : showOnWebsite === 'false' ? false : undefined,
      published: published === 'true' ? true : published === 'false' ? false : undefined,
      search,
    });

    return res.json({ data: courses, count: courses.length });
  } catch (error) {
    return next(error);
  }
});

/**
 * GET /api/courses/:id
 * Get course by ID
 */
router.get('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    const course = await courseService.getById(id, req.prisma);

    if (!course || course.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Course not found' });
    }

    return res.json({ data: course });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/courses
 * Create course
 */
router.post(
  '/',
  body('title').notEmpty().withMessage('Title is required'),
  body('type').notEmpty().withMessage('Type is required'),
  body('price').isFloat({ min: 0 }).withMessage('Price must be a positive number'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      if (!req.organizationId) {
        return res.status(400).json({ error: 'Organization ID is required' });
      }

      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const course = await courseService.create(
        {
          ...req.body,
          organizationId: req.organizationId,
        },
        req.prisma
      );

      return res.status(201).json({
        data: course,
        message: 'Course created successfully',
      });
    } catch (error) {
      return next(error);
    }
  }
);

/**
 * PUT /api/courses/:id
 * Update course
 */
router.put('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    // Verify course belongs to organization
    const existing = await req.prisma.course.findUnique({
      where: { id },
      select: { organizationId: true },
    });

    if (!existing || existing.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Course not found' });
    }

    const course = await courseService.update(id, req.body, req.prisma);

    return res.json({
      data: course,
      message: 'Course updated successfully',
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * DELETE /api/courses/:id
 * Delete (archive) course
 */
router.delete('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    // Verify course belongs to organization
    const existing = await req.prisma.course.findUnique({
      where: { id },
      select: { organizationId: true },
    });

    if (!existing || existing.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Course not found' });
    }

    const course = await courseService.delete(id, req.prisma);

    return res.json({
      data: course,
      message: 'Course archived successfully',
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/courses/:id/publish
 * Publish course
 */
router.post('/:id/publish', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    // Verify course belongs to organization
    const existing = await req.prisma.course.findUnique({
      where: { id },
      select: { organizationId: true },
    });

    if (!existing || existing.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Course not found' });
    }

    const course = await courseService.publish(id, req.prisma);

    return res.json({
      data: course,
      message: 'Course published successfully',
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/courses/:id/unpublish
 * Unpublish course
 */
router.post('/:id/unpublish', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    // Verify course belongs to organization
    const existing = await req.prisma.course.findUnique({
      where: { id },
      select: { organizationId: true },
    });

    if (!existing || existing.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Course not found' });
    }

    const course = await courseService.unpublish(id, req.prisma);

    return res.json({
      data: course,
      message: 'Course unpublished successfully',
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * GET /api/courses/:id/availability
 * Check course availability
 */
router.get('/:id/availability', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    const isOpen = await courseService.isBookingWindowOpen(id, req.prisma);
    const availableSpots = await courseService.getAvailableSpots(id, req.prisma);
    const isFull = await courseService.isFull(id, req.prisma);

    return res.json({
      data: {
        bookingWindowOpen: isOpen,
        availableSpots: availableSpots === Infinity ? null : availableSpots,
        isFull,
      },
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/courses/:id/enroll
 * Enroll customer in course
 */
router.post(
  '/:id/enroll',
  body('customerId').notEmpty().withMessage('Customer ID is required'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      if (!req.organizationId) {
        return res.status(400).json({ error: 'Organization ID is required' });
      }

      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { id } = req.params;
      const { customerId } = req.body;

      const enrollment = await courseService.enroll(id, customerId, req.prisma);

      return res.status(201).json({
        data: enrollment,
        message: 'Customer enrolled successfully',
      });
    } catch (error: any) {
      if (error.message === 'Booking window is closed') {
        return res.status(400).json({ error: error.message });
      }
      if (error.message === 'Course is full') {
        return res.status(400).json({ error: error.message });
      }
      if (error.message === 'Customer is already enrolled in this course') {
        return res.status(409).json({ error: error.message });
      }
      return next(error);
    }
  }
);

/**
 * DELETE /api/courses/:id/enroll/:customerId
 * Unenroll customer from course
 */
router.delete('/:id/enroll/:customerId', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id, customerId } = req.params;

    const enrollment = await courseService.unenroll(id, customerId, req.prisma);

    return res.json({
      data: enrollment,
      message: 'Customer unenrolled successfully',
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * GET /api/courses/:id/enrollments
 * Get enrollments for a course
 */
router.get('/:id/enrollments', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    // Verify course belongs to organization
    const course = await req.prisma.course.findUnique({
      where: { id },
      select: { organizationId: true },
    });

    if (!course || course.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Course not found' });
    }

    const enrollments = await courseService.getEnrollments(id, req.prisma);

    return res.json({
      data: enrollments,
      count: enrollments.length,
    });
  } catch (error) {
    return next(error);
  }
});

// ============================================
// COURSE SESSIONS
// ============================================

/**
 * GET /api/courses/:id/sessions
 * Get sessions for a course
 */
router.get('/:id/sessions', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    // Verify course belongs to organization
    const course = await req.prisma.course.findUnique({
      where: { id },
      select: { organizationId: true },
    });

    if (!course || course.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Course not found' });
    }

    const sessions = await courseService.getSessions(id, req.prisma);

    return res.json({
      data: sessions,
      count: sessions.length,
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/courses/:id/sessions
 * Create session for a course
 */
router.post(
  '/:id/sessions',
  body('date').notEmpty().withMessage('Date is required'),
  body('startTime').notEmpty().withMessage('Start time is required'),
  body('endTime').notEmpty().withMessage('End time is required'),
  body('maxParticipants').isInt({ min: 1 }).withMessage('Max participants must be at least 1'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      if (!req.organizationId) {
        return res.status(400).json({ error: 'Organization ID is required' });
      }

      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { id } = req.params;

      // Verify course belongs to organization
      const course = await req.prisma.course.findUnique({
        where: { id },
        select: { organizationId: true },
      });

      if (!course || course.organizationId !== req.organizationId) {
        return res.status(404).json({ error: 'Course not found' });
      }

      const session = await courseService.createSession(id, req.body, req.prisma);

      return res.status(201).json({
        data: session,
        message: 'Session created successfully',
      });
    } catch (error) {
      return next(error);
    }
  }
);

/**
 * PUT /api/courses/:courseId/sessions/:sessionId
 * Update session
 */
router.put('/:courseId/sessions/:sessionId', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { courseId, sessionId } = req.params;

    // Verify session belongs to course and organization
    const session = await req.prisma.courseSession.findUnique({
      where: { id: sessionId },
      include: {
        course: {
          select: { id: true, organizationId: true },
        },
      },
    });

    if (!session || session.courseId !== courseId || session.course.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Session not found' });
    }

    const updatedSession = await courseService.updateSession(sessionId, req.body, req.prisma);

    return res.json({
      data: updatedSession,
      message: 'Session updated successfully',
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * DELETE /api/courses/:courseId/sessions/:sessionId
 * Cancel session
 */
router.delete('/:courseId/sessions/:sessionId', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { courseId, sessionId } = req.params;

    // Verify session belongs to course and organization
    const session = await req.prisma.courseSession.findUnique({
      where: { id: sessionId },
      include: {
        course: {
          select: { id: true, organizationId: true },
        },
      },
    });

    if (!session || session.courseId !== courseId || session.course.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Session not found' });
    }

    const cancelledSession = await courseService.deleteSession(sessionId, req.prisma);

    return res.json({
      data: cancelledSession,
      message: 'Session cancelled successfully',
    });
  } catch (error) {
    return next(error);
  }
});

export default router;
