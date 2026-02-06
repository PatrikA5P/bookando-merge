import express from 'express';
import { body, query, validationResult } from 'express-validator';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';
import { invoiceService } from '../services/InvoiceService';

const router = express.Router();

// Apply auth and tenancy middleware to all routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/invoices
 * Get all invoices for the organization with optional filters
 */
router.get(
  '/',
  [
    query('status').optional().isString(),
    query('customerId').optional().isString(),
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
        dateFrom: req.query.dateFrom as string,
        dateTo: req.query.dateTo as string,
      };

      const invoices = await invoiceService.getAll(req.prisma, filters);

      res.json({
        data: invoices,
        count: invoices.length,
        organizationId: req.organizationId,
      });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * GET /api/invoices/overdue
 * Get all overdue invoices
 */
router.get('/overdue', async (req: AuthenticatedRequest, res, next) => {
  try {
    const invoices = await invoiceService.getOverdue(req.prisma);

    res.json({
      data: invoices,
      count: invoices.length,
    });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/invoices/:id
 * Get a specific invoice by ID
 */
router.get('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    const invoice = await invoiceService.getById(req.params.id, req.prisma);

    if (!invoice) {
      return res.status(404).json({
        error: 'Not Found',
        message: 'Invoice not found',
      });
    }

    res.json({ data: invoice });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/invoices/customer/:customerId
 * Get all invoices for a specific customer
 */
router.get('/customer/:customerId', async (req: AuthenticatedRequest, res, next) => {
  try {
    const invoices = await invoiceService.getByCustomer(req.params.customerId, req.prisma);

    res.json({
      data: invoices,
      count: invoices.length,
    });
  } catch (error) {
    next(error);
  }
});

/**
 * POST /api/invoices
 * Create a new invoice manually
 */
router.post(
  '/',
  [
    body('customerId').notEmpty().withMessage('Customer ID is required'),
    body('bookingId').optional().isString(),
    body('dueDate').isISO8601().withMessage('Valid due date is required (YYYY-MM-DD)'),
    body('notes').optional().isString(),
    body('items').isArray({ min: 1 }).withMessage('At least one invoice item is required'),
    body('items.*.description').notEmpty().withMessage('Item description is required'),
    body('items.*.quantity').isInt({ min: 1 }).withMessage('Item quantity must be at least 1'),
    body('items.*.unitPrice').isFloat({ min: 0 }).withMessage('Item unit price is required'),
    body('items.*.taxRate').isFloat({ min: 0, max: 100 }).withMessage('Item tax rate is required (0-100)'),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const invoice = await invoiceService.create(
        {
          customerId: req.body.customerId,
          bookingId: req.body.bookingId,
          dueDate: req.body.dueDate,
          notes: req.body.notes,
          items: req.body.items,
        },
        req.prisma,
        req.organizationId!
      );

      res.status(201).json({
        data: invoice,
        message: 'Invoice created successfully',
      });
    } catch (error: any) {
      if (error.message.includes('not found')) {
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
 * POST /api/invoices/from-booking/:bookingId
 * Generate invoice from a booking
 */
router.post('/from-booking/:bookingId', async (req: AuthenticatedRequest, res, next) => {
  try {
    const invoice = await invoiceService.createFromBooking(req.params.bookingId, req.prisma, req.organizationId!);

    res.status(201).json({
      data: invoice,
      message: 'Invoice generated from booking successfully',
    });
  } catch (error: any) {
    if (error.message.includes('not found')) {
      return res.status(404).json({
        error: 'Not Found',
        message: error.message,
      });
    }
    if (error.message.includes('already exists')) {
      return res.status(400).json({
        error: 'Bad Request',
        message: error.message,
      });
    }
    next(error);
  }
});

/**
 * PUT /api/invoices/:id
 * Update an invoice
 */
router.put(
  '/:id',
  [
    body('dueDate').optional().isISO8601(),
    body('notes').optional().isString(),
    body('status').optional().isIn(['DRAFT', 'SENT', 'PAID', 'PARTIALLY_PAID', 'OVERDUE', 'CANCELLED']),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const invoice = await invoiceService.update(req.params.id, req.body, req.prisma);

      res.json({
        data: invoice,
        message: 'Invoice updated successfully',
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
 * POST /api/invoices/:id/send
 * Send invoice to customer
 */
router.post('/:id/send', async (req: AuthenticatedRequest, res, next) => {
  try {
    const invoice = await invoiceService.send(req.params.id, req.prisma);

    res.json({
      data: invoice,
      message: 'Invoice sent successfully',
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
 * POST /api/invoices/:id/mark-paid
 * Mark invoice as paid
 */
router.post(
  '/:id/mark-paid',
  [body('amount').optional().isFloat({ min: 0 }).withMessage('Payment amount must be positive')],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const invoice = await invoiceService.markAsPaid(req.params.id, req.prisma, req.body.amount);

      res.json({
        data: invoice,
        message: 'Invoice marked as paid successfully',
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
 * POST /api/invoices/:id/cancel
 * Cancel an invoice
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

      const invoice = await invoiceService.cancel(req.params.id, req.body.reason, req.prisma);

      res.json({
        data: invoice,
        message: 'Invoice cancelled successfully',
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

export default router;
