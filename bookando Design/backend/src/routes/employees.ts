import express from 'express';
import { body, query, validationResult } from 'express-validator';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';
import { employeeService } from '../services/EmployeeService';

const router = express.Router();

// Apply auth and tenancy middleware to all routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/employees
 * Get all employees for the organization with optional filters
 */
router.get(
  '/',
  [query('status').optional().isString(), query('department').optional().isString()],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const filters = {
        status: req.query.status as any,
        department: req.query.department as string,
      };

      const employees = await employeeService.getAll(req.prisma, filters);

      res.json({
        data: employees,
        count: employees.length,
        organizationId: req.organizationId,
      });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * GET /api/employees/active
 * Get only active employees
 */
router.get('/active', async (req: AuthenticatedRequest, res, next) => {
  try {
    const employees = await employeeService.getActive(req.prisma);

    res.json({
      data: employees,
      count: employees.length,
    });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/employees/search
 * Search employees by name or email
 */
router.get(
  '/search',
  [query('q').notEmpty().withMessage('Search query is required')],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const employees = await employeeService.search(req.query.q as string, req.prisma);

      res.json({
        data: employees,
        count: employees.length,
      });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * GET /api/employees/:id
 * Get a specific employee by ID
 */
router.get('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    const employee = await employeeService.getById(req.params.id, req.prisma);

    if (!employee) {
      return res.status(404).json({
        error: 'Not Found',
        message: 'Employee not found',
      });
    }

    res.json({ data: employee });
  } catch (error) {
    next(error);
  }
});

/**
 * GET /api/employees/:id/schedule
 * Get employee schedule for a specific date
 */
router.get(
  '/:id/schedule',
  [query('date').isISO8601().withMessage('Valid date is required (YYYY-MM-DD)')],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const schedule = await employeeService.getSchedule(req.params.id, req.query.date as string, req.prisma);

      res.json({ data: schedule });
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
 * GET /api/employees/:id/stats
 * Get employee workload statistics
 */
router.get(
  '/:id/stats',
  [
    query('dateFrom').isISO8601().withMessage('Valid start date is required (YYYY-MM-DD)'),
    query('dateTo').isISO8601().withMessage('Valid end date is required (YYYY-MM-DD)'),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const stats = await employeeService.getWorkloadStats(
        req.params.id,
        req.query.dateFrom as string,
        req.query.dateTo as string,
        req.prisma
      );

      res.json({ data: stats });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * POST /api/employees/:id/check-availability
 * Check if employee is available at a specific time
 */
router.post(
  '/:id/check-availability',
  [
    body('date').isISO8601().withMessage('Valid date is required (YYYY-MM-DD)'),
    body('time').matches(/^([01]\d|2[0-3]):([0-5]\d)$/).withMessage('Valid time is required (HH:MM)'),
    body('duration').isInt({ min: 1 }).withMessage('Duration in minutes is required'),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const isAvailable = await employeeService.isAvailable(
        req.params.id,
        req.body.date,
        req.body.time,
        req.body.duration,
        req.prisma
      );

      res.json({
        available: isAvailable,
        message: isAvailable ? 'Employee is available' : 'Employee is not available at this time',
      });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * POST /api/employees
 * Create a new employee
 */
router.post(
  '/',
  [
    body('firstName').notEmpty().withMessage('First name is required'),
    body('lastName').notEmpty().withMessage('Last name is required'),
    body('email').isEmail().withMessage('Valid email is required'),
    body('phone').optional().isString(),
    body('address').optional().isString(),
    body('dateOfBirth').optional().isISO8601(),
    body('hireDate').isISO8601().withMessage('Valid hire date is required (YYYY-MM-DD)'),
    body('position').notEmpty().withMessage('Position is required'),
    body('department').optional().isString(),
    body('hourlyRate').optional().isFloat({ min: 0 }),
    body('customFields').optional().isObject(),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const employee = await employeeService.create(
        {
          firstName: req.body.firstName,
          lastName: req.body.lastName,
          email: req.body.email,
          phone: req.body.phone,
          address: req.body.address,
          dateOfBirth: req.body.dateOfBirth,
          hireDate: req.body.hireDate,
          position: req.body.position,
          department: req.body.department,
          hourlyRate: req.body.hourlyRate,
          customFields: req.body.customFields,
        },
        req.prisma,
        req.organizationId!
      );

      res.status(201).json({
        data: employee,
        message: 'Employee created successfully',
      });
    } catch (error: any) {
      if (error.message.includes('already exists')) {
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
 * PUT /api/employees/:id
 * Update an employee
 */
router.put(
  '/:id',
  [
    body('firstName').optional().notEmpty(),
    body('lastName').optional().notEmpty(),
    body('email').optional().isEmail(),
    body('phone').optional().isString(),
    body('address').optional().isString(),
    body('dateOfBirth').optional().isISO8601(),
    body('hireDate').optional().isISO8601(),
    body('position').optional().notEmpty(),
    body('department').optional().isString(),
    body('hourlyRate').optional().isFloat({ min: 0 }),
    body('status').optional().isIn(['ACTIVE', 'INACTIVE', 'ARCHIVED']),
    body('customFields').optional().isObject(),
  ],
  async (req: AuthenticatedRequest, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const employee = await employeeService.update(req.params.id, req.body, req.prisma);

      res.json({
        data: employee,
        message: 'Employee updated successfully',
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
  }
);

/**
 * DELETE /api/employees/:id
 * Delete (archive) an employee
 */
router.delete('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    const employee = await employeeService.delete(req.params.id, req.prisma);

    res.json({
      data: employee,
      message: 'Employee archived successfully',
    });
  } catch (error: any) {
    if (error.message.includes('not found')) {
      return res.status(404).json({
        error: 'Not Found',
        message: error.message,
      });
    }
    if (error.message.includes('future bookings')) {
      return res.status(400).json({
        error: 'Bad Request',
        message: error.message,
      });
    }
    next(error);
  }
});

/**
 * POST /api/employees/:id/activate
 * Activate an employee
 */
router.post('/:id/activate', async (req: AuthenticatedRequest, res, next) => {
  try {
    const employee = await employeeService.activate(req.params.id, req.prisma);

    res.json({
      data: employee,
      message: 'Employee activated successfully',
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
 * POST /api/employees/:id/deactivate
 * Deactivate an employee
 */
router.post('/:id/deactivate', async (req: AuthenticatedRequest, res, next) => {
  try {
    const employee = await employeeService.deactivate(req.params.id, req.prisma);

    res.json({
      data: employee,
      message: 'Employee deactivated successfully',
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
