
import express from 'express';
import { body, validationResult } from 'express-validator';
import { AuthService } from '../services/AuthService';
import { requireAuth } from '../middleware/auth';
import { AuthenticatedRequest } from '../middleware/tenancy';

const router = express.Router();
const authService = new AuthService();

/**
 * POST /api/auth/register
 * Register a new user
 */
router.post('/register',
  body('email').isEmail().withMessage('Valid email is required'),
  body('password').isLength({ min: 6 }).withMessage('Password must be at least 6 characters'),
  body('firstName').notEmpty().withMessage('First name is required'),
  body('lastName').notEmpty().withMessage('Last name is required'),
  body('organizationId').notEmpty().withMessage('Organization ID is required'),
  async (req, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { email, password, firstName, lastName, organizationId, roleId } = req.body;

      const result = await authService.register({
        email,
        password,
        firstName,
        lastName,
        organizationId,
        roleId
      });

      res.status(201).json(result);
    } catch (error: any) {
      if (error.message === 'User with this email already exists') {
        return res.status(409).json({ error: error.message });
      }
      next(error);
    }
  }
);

/**
 * POST /api/auth/login
 * Login user
 * Optional: organizationId for multi-tenant scenarios
 */
router.post('/login',
  body('email').isEmail().withMessage('Valid email is required'),
  body('password').notEmpty().withMessage('Password is required'),
  body('organizationId').optional().isString(),
  async (req, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { email, password, organizationId } = req.body;

      const result = await authService.login(email, password, organizationId);

      res.json(result);
    } catch (error: any) {
      if (error.message === 'Invalid email or password' ||
          error.message === 'Account is not active' ||
          error.message === 'Multiple accounts found. Please specify organization.') {
        return res.status(401).json({ error: error.message });
      }
      next(error);
    }
  }
);

/**
 * POST /api/auth/refresh
 * Refresh access token
 */
router.post('/refresh',
  body('refreshToken').notEmpty().withMessage('Refresh token is required'),
  async (req, res, next) => {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { refreshToken } = req.body;

      const result = await authService.refreshAccessToken(refreshToken);

      res.json(result);
    } catch (error: any) {
      if (error.message.includes('refresh token')) {
        return res.status(401).json({ error: error.message });
      }
      next(error);
    }
  }
);

/**
 * POST /api/auth/logout
 * Logout (invalidate refresh token)
 */
router.post('/logout',
  body('refreshToken').notEmpty().withMessage('Refresh token is required'),
  async (req, res, next) => {
    try {
      const { refreshToken } = req.body;

      await authService.logout(refreshToken);

      res.json({ message: 'Logged out successfully' });
    } catch (error) {
      next(error);
    }
  }
);

/**
 * GET /api/auth/me
 * Get current user info
 */
router.get('/me', requireAuth, async (req: AuthenticatedRequest, res, next) => {
  try {
    const { PrismaClient } = await import('@prisma/client');
    const prisma = new PrismaClient();

    const user = await prisma.user.findUnique({
      where: { id: req.user!.userId },
      include: {
        role: true,
        organization: {
          include: { license: true }
        }
      }
    });

    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    const { passwordHash, ...sanitized } = user;

    res.json({ user: sanitized });
  } catch (error) {
    next(error);
  }
});

export default router;
