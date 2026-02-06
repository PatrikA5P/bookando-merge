import express from 'express';
import { body, query, validationResult } from 'express-validator';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';
import { bookingService } from '../services/BookingService';

const router = express.Router();

// Apply auth and tenancy middleware to all routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/bookings
 * Get all bookings for the organization with optional filters
 */
router.get(
  '/',
  [
    query('status').optional().isString(),
    query('customerId').optional().isString(),
    query('serviceId').optional().isString(),
    query('dateFrom').optional().isISO8601(),
    query('dateTo').optional().isISO8601(),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const filters = {
        status: req.query.status as any,
        customerId: req.query.customerId as string,
        serviceId: req.query.serviceId as string,
        dateFrom: req.query.dateFrom as string,
        dateTo: req.query.dateTo as string,
      };

      const bookings = await bookingService.getAll(req.prisma, filters);

      res.json({
        data: bookings,
        count: bookings.length,
        organizationId: req.organizationId,
      });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * GET /api/bookings/:id
 * Get a specific booking by ID
 */
router.get('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    const booking = await bookingService.getById(req.params.id, req.prisma);

    if (!booking) {
      return res.status(404).json({
        error: 'Not Found',
        message: 'Booking not found',
      });
    }

    res.json({ data: booking });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/bookings/customer/:customerId
 * Get all bookings for a specific customer
 */
router.get('/customer/:customerId', async (req: AuthenticatedRequest, res, next) => {
  try {
    const bookings = await bookingService.getByCustomer(req.params.customerId, req.prisma);

    res.json({
      data: bookings,
      count: bookings.length,
    });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/bookings/employee/:employeeId
 * Get all bookings for a specific employee
 */
router.get('/employee/:employeeId', async (req: AuthenticatedRequest, res, next) => {
  try {
    const bookings = await bookingService.getByEmployee(req.params.employeeId, req.prisma);

    res.json({
      data: bookings,
      count: bookings.length,
    });
  } catch (error) {
    next(error);
  }
});

/**
 * POST /api/bookings
 * Create a new booking
 */
router.post(
  '/',
  [
    body('customerId').notEmpty().withMessage('Customer ID is required'),
    body('serviceId').notEmpty().withMessage('Service ID is required'),
    body('scheduledDate').isISO8601().withMessage('Valid scheduled date is required (YYYY-MM-DD)'),
    body('scheduledTime').matches(/^([01]\d|2[0-3]):([0-5]\d)$/).withMessage('Valid scheduled time is required (HH:MM)'),
    body('sessionId').optional().isString(),
    body('participants').optional().isInt({ min: 1 }),
    body('notes').optional().isString(),
    body('employeeId').optional().isString(),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const booking = await bookingService.create(
        {
          customerId: req.body.customerId,
          serviceId: req.body.serviceId,
          sessionId: req.body.sessionId,
          scheduledDate: req.body.scheduledDate,
          scheduledTime: req.body.scheduledTime,
          participants: req.body.participants,
          notes: req.body.notes,
          employeeId: req.body.employeeId,
        },
        req.prisma,
        req.organizationId!
      );

      res.status(201).json({
        data: booking,
        message: 'Booking created successfully',
      });
    } catch (error: any) {
      if (error.message.includes('not found') || error.message.includes('fully booked')) {
        return res.status(400).json({
          error: 'Bad Request',
          message: error.message,
        });
      }
      next(error);
    }
  }
);

/**
 * PUT /api/bookings/:id
 * Update a booking
 */
router.put(
  '/:id',
  [
    body('scheduledDate').optional().isISO8601(),
    body('scheduledTime').optional().matches(/^([01]\d|2[0-3]):([0-5]\d)$/),
    body('participants').optional().isInt({ min: 1 }),
    body('notes').optional().isString(),
    body('employeeId').optional().isString(),
    body('status').optional().isIn(['PENDING', 'CONFIRMED', 'PAID', 'COMPLETED', 'CANCELLED']),
    body('paymentStatus').optional().isIn(['PENDING', 'PAID', 'PARTIALLY_PAID', 'REFUNDED']),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const booking = await bookingService.update(req.params.id, req.body, req.prisma);

      res.json({
        data: booking,
        message: 'Booking updated successfully',
      });
    } catch (error: any) {
      if (error.message.includes('not found')) {
        return res.status(404).json({
          error: 'Not Found',
          message: error.message,
        });
      }
      next(error);
    }
  }
);

/**
 * POST /api/bookings/:id/confirm
 * Confirm a booking (triggers invoice generation)
 */
router.post('/:id/confirm', async (req: AuthenticatedRequest, res, next) => {
  try {
    const booking = await bookingService.confirm(req.params.id, req.prisma);

    res.json({
      data: booking,
      message: 'Booking confirmed successfully. Invoice will be generated.',
    });
  } catch (error: any) {
    if (error.message.includes('not found')) {
      return res.status(404).json({
        error: 'Not Found',
        message: error.message,
      });
    }
    next(error);
  }
});

/**
 * POST /api/bookings/:id/complete
 * Mark booking as completed
 */
router.post('/:id/complete', async (req: AuthenticatedRequest, res, next) => {
  try {
    const booking = await bookingService.complete(req.params.id, req.prisma);

    res.json({
      data: booking,
      message: 'Booking completed successfully',
    });
  } catch (error: any) {
    if (error.message.includes('not found')) {
      return res.status(404).json({
        error: 'Not Found',
        message: error.message,
      });
    }
    next(error);
  }
});

/**
 * POST /api/bookings/:id/cancel
 * Cancel a booking
 */
router.post(
  '/:id/cancel',
  [body('reason').notEmpty().withMessage('Cancellation reason is required')],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const booking = await bookingService.cancel(req.params.id, req.body.reason, req.prisma);

      res.json({
        data: booking,
        message: 'Booking cancelled successfully',
      });
    } catch (error: any) {
      if (error.message.includes('not found')) {
        return res.status(404).json({
          error: 'Not Found',
          message: error.message,
        });
      }
      next(error);
    }
  }
);

/**
 * POST /api/bookings/:id/mark-paid
 * Mark booking as paid
 */
router.post('/:id/mark-paid', async (req: AuthenticatedRequest, res, next) => {
  try {
    const booking = await bookingService.markAsPaid(req.params.id, req.prisma);

    res.json({
      data: booking,
      message: 'Booking marked as paid successfully',
    });
  } catch (error: any) {
    if (error.message.includes('not found')) {
      return res.status(404).json({
        error: 'Not Found',
        message: error.message,
      });
    }
    next(error);
  }
});

export default router;
