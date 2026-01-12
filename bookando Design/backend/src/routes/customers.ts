import express from 'express';
import { body, validationResult } from 'express-validator';
import { CustomerService } from '../services/CustomerService';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';

const router = express.Router();
const customerService = new CustomerService();

// Apply authentication and tenancy to all routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/customers
 * Get all customers (scoped to organization)
 * Query params:
 * - status: ACTIVE | INACTIVE | ARCHIVED (default: ACTIVE)
 * - includeArchived: boolean (includes archived if true)
 * - search: string (search in name and email)
 */
router.get('/', async (req: AuthenticatedRequest, res, next) => {
  try {
    const { status, includeArchived, search } = req.query;

    // Build filters
    const filters: any = {};

    // Default: nur ACTIVE, außer explizit anders angegeben
    if (status) {
      filters.status = status as string;
    } else if (includeArchived === 'true' || includeArchived === '1') {
      // Kein Status-Filter, zeige alle
    } else {
      // Default: nur ACTIVE
      filters.status = 'ACTIVE';
    }

    // Search filter
    if (search && typeof search === 'string') {
      filters.search = search;
    }

    const customers = await customerService.getAll(req.prisma, filters);

    res.json({
      data: customers,
      organizationId: req.organizationId, // For debugging
      count: customers.length,
      filters // Return applied filters for debugging
    });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/customers/search?q=query
 * Search customers
 */
router.get('/search', async (req: AuthenticatedRequest, res, next) => {
  try {
    const query = req.query.q as string;

    if (!query) {
      return res.status(400).json({ error: 'Search query is required' });
    }

    const customers = await customerService.search(query, req.prisma);

    res.json({
      data: customers,
      count: customers.length
    });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/customers/:id
 * Get customer by ID (scoped to organization)
 */
router.get('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    const { id } = req.params;

    const customer = await customerService.getById(id, req.prisma);

    if (!customer) {
      return res.status(404).json({ error: 'Customer not found' });
    }

    res.json({ data: customer });
  } catch (error) {
    next(error);
  }
});

/**
 * POST /api/customers
 * Create customer (organizationId auto-injected)
 */
router.post('/',
  body('firstName').notEmpty().withMessage('First name is required'),
  body('lastName').notEmpty().withMessage('Last name is required'),
  body('email').isEmail().withMessage('Valid email is required'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const customer = await customerService.create(req.body, req.prisma);

      res.status(201).json({
        data: customer,
        message: 'Customer created successfully'
      });
    } catch (error: any) {
      // Unique constraint violation (email)
      if (error.code === 'P2002') {
        return res.status(409).json({
          error: 'A customer with this email already exists in your organization'
        });
      }
      next(error);
    }
  }
);

/**
 * PUT /api/customers/:id
 * Update customer (full update, scoped to organization)
 */
router.put('/:id',
  body('email').optional().isEmail().withMessage('Valid email is required'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { id } = req.params;

      const customer = await customerService.update(id, req.body, req.prisma);

      res.json({
        data: customer,
        message: 'Customer updated successfully'
      });
    } catch (error: any) {
      if (error.code === 'P2025') {
        return res.status(404).json({ error: 'Customer not found' });
      }
      next(error);
    }
  }
);

/**
 * PATCH /api/customers/:id
 * Partial update customer (scoped to organization)
 */
router.patch('/:id',
  body('email').optional().isEmail().withMessage('Valid email is required'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { id } = req.params;

      const customer = await customerService.update(id, req.body, req.prisma);

      res.json({
        data: customer,
        message: 'Customer updated successfully'
      });
    } catch (error: any) {
      if (error.code === 'P2025') {
        return res.status(404).json({ error: 'Customer not found' });
      }
      next(error);
    }
  }
);

/**
 * DELETE /api/customers/:id
 * Delete customer (soft delete - mark as ARCHIVED)
 */
router.delete('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    const { id } = req.params;

    const customer = await customerService.delete(id, req.prisma);

    res.json({
      data: customer,
      message: 'Customer archived successfully'
    });
  } catch (error: any) {
    if (error.code === 'P2025') {
      return res.status(404).json({ error: 'Customer not found' });
    }
    next(error);
  }
});

export default router;
