import express from 'express';
import { body, query, validationResult } from 'express-validator';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';
import { serviceService } from '../services/ServiceService';

const router = express.Router();

// Apply auth and tenancy middleware to all routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/services
 * Get all services for the organization with optional filters
 */
router.get(
  '/',
  [
    query('category').optional().isString(),
    query('status').optional().isString(),
    query('isOnline').optional().isBoolean(),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const filters = {
        category: req.query.category as any,
        status: req.query.status as any,
        isOnline: req.query.isOnline === 'true' ? true : req.query.isOnline === 'false' ? false : undefined,
      };

      const services = await serviceService.getAll(req.prisma, filters);

      res.json({
        data: services,
        count: services.length,
        organizationId: req.organizationId,
      });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * GET /api/services/active
 * Get only active services (for customer booking)
 */
router.get('/active', [query('category').optional().isString()], async (req: AuthenticatedRequest, res, next) => {
  try {
    const category = req.query.category as any;
    const services = await serviceService.getActive(req.prisma, category);

    res.json({
      data: services,
      count: services.length,
    });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/services/popular
 * Get popular services (most bookings)
 */
router.get('/popular', [query('limit').optional().isInt({ min: 1, max: 20 })], async (req: AuthenticatedRequest, res, next) => {
  try {
    const limit = req.query.limit ? parseInt(req.query.limit as string) : 5;
    const services = await serviceService.getPopular(req.prisma, limit);

    res.json({
      data: services,
      count: services.length,
    });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/services/search
 * Search services by name or description
 */
router.get('/search', [query('q').notEmpty().withMessage('Search query is required')], async (req: AuthenticatedRequest, res, next) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const services = await serviceService.search(req.query.q as string, req.prisma);

    res.json({
      data: services,
      count: services.length,
    });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/services/:id
 * Get a specific service by ID
 */
router.get('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    const service = await serviceService.getById(req.params.id, req.prisma);

    if (!service) {
      return res.status(404).json({
        error: 'Not Found',
        message: 'Service not found',
      });
    }

    res.json({ data: service });
  } catch (error) {
    next(error);
  }
});

/**
 * POST /api/services
 * Create a new service
 */
router.post(
  '/',
  [
    body('name').notEmpty().withMessage('Service name is required'),
    body('description').optional().isString(),
    body('category')
      .notEmpty()
      .isIn(['DRIVING_LESSON', 'THEORY', 'FIRST_AID', 'ADVANCED_TRAINING', 'EXAM', 'OTHER'])
      .withMessage('Valid category is required'),
    body('price').isFloat({ min: 0 }).withMessage('Valid price is required'),
    body('duration').isInt({ min: 1 }).withMessage('Duration in minutes is required'),
    body('maxParticipants').optional().isInt({ min: 1 }),
    body('requiresLicense').optional().isBoolean(),
    body('isOnline').optional().isBoolean(),
    body('customFields').optional().isObject(),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const service = await serviceService.create(
        {
          name: req.body.name,
          description: req.body.description,
          category: req.body.category,
          price: req.body.price,
          duration: req.body.duration,
          maxParticipants: req.body.maxParticipants,
          requiresLicense: req.body.requiresLicense,
          isOnline: req.body.isOnline,
          customFields: req.body.customFields,
        },
        req.prisma,
        req.organizationId!
      );

      res.status(201).json({
        data: service,
        message: 'Service created successfully',
      });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * PUT /api/services/:id
 * Update a service
 */
router.put(
  '/:id',
  [
    body('name').optional().notEmpty(),
    body('description').optional().isString(),
    body('category')
      .optional()
      .isIn(['DRIVING_LESSON', 'THEORY', 'FIRST_AID', 'ADVANCED_TRAINING', 'EXAM', 'OTHER']),
    body('price').optional().isFloat({ min: 0 }),
    body('duration').optional().isInt({ min: 1 }),
    body('maxParticipants').optional().isInt({ min: 1 }),
    body('requiresLicense').optional().isBoolean(),
    body('isOnline').optional().isBoolean(),
    body('status').optional().isIn(['ACTIVE', 'INACTIVE', 'ARCHIVED']),
    body('customFields').optional().isObject(),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const service = await serviceService.update(req.params.id, req.body, req.prisma);

      res.json({
        data: service,
        message: 'Service updated successfully',
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
 * DELETE /api/services/:id
 * Delete (archive) a service
 */
router.delete('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    const service = await serviceService.delete(req.params.id, req.prisma);

    res.json({
      data: service,
      message: 'Service archived successfully',
    });
  } catch (error: any) {
    if (error.message.includes('not found')) {
      return res.status(404).json({
        error: 'Not Found',
        message: error.message,
      });
    }
    if (error.message.includes('active bookings')) {
      return res.status(400).json({
        error: 'Bad Request',
        message: error.message,
      });
    }
    next(error);
  }
});

/**
 * POST /api/services/:id/activate
 * Activate a service
 */
router.post('/:id/activate', async (req: AuthenticatedRequest, res, next) => {
  try {
    const service = await serviceService.activate(req.params.id, req.prisma);

    res.json({
      data: service,
      message: 'Service activated successfully',
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
 * POST /api/services/:id/deactivate
 * Deactivate a service
 */
router.post('/:id/deactivate', async (req: AuthenticatedRequest, res, next) => {
  try {
    const service = await serviceService.deactivate(req.params.id, req.prisma);

    res.json({
      data: service,
      message: 'Service deactivated successfully',
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
 * POST /api/services/seed-defaults
 * Create default Swiss driving school services (helper for setup)
 */
router.post('/seed-defaults', async (req: AuthenticatedRequest, res, next) => {
  try {
    const services = await serviceService.createDefaultServices(req.prisma, req.organizationId!);

    res.status(201).json({
      data: services,
      message: `${services.length} default services created successfully`,
    });
  } catch (error) {
    next(error);
  }
});

export default router;
