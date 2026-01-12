import express from 'express';
import { body, validationResult } from 'express-validator';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';

const router = express.Router();

// Apply authentication and tenancy to all routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/tags
 * Get all tags for organization
 */
router.get('/', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const tags = await req.prisma.tag.findMany({
      where: { organizationId: req.organizationId },
      include: {
        _count: {
          select: {
            courses: true,
          },
        },
      },
      orderBy: { name: 'asc' },
    });

    return res.json({ data: tags, count: tags.length });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/tags
 * Create tag
 */
router.post(
  '/',
  body('name').notEmpty().withMessage('Name is required'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      if (!req.organizationId) {
        return res.status(400).json({ error: 'Organization ID is required' });
      }

      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { name, color, description } = req.body;

      const tag = await req.prisma.tag.create({
        data: {
          organizationId: req.organizationId,
          name,
          color,
          description,
        },
      });

      return res.status(201).json({
        data: tag,
        message: 'Tag created successfully',
      });
    } catch (error: any) {
      if (error.code === 'P2002') {
        return res.status(409).json({ error: 'Tag with this name already exists' });
      }
      return next(error);
    }
  }
);

/**
 * PUT /api/tags/:id
 * Update tag
 */
router.put('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;
    const { name, color, description } = req.body;

    const tag = await req.prisma.tag.updateMany({
      where: {
        id,
        organizationId: req.organizationId,
      },
      data: {
        name,
        color,
        description,
      },
    });

    if (tag.count === 0) {
      return res.status(404).json({ error: 'Tag not found' });
    }

    const updatedTag = await req.prisma.tag.findUnique({
      where: { id },
    });

    return res.json({
      data: updatedTag,
      message: 'Tag updated successfully',
    });
  } catch (error: any) {
    if (error.code === 'P2002') {
      return res.status(409).json({ error: 'Tag with this name already exists' });
    }
    return next(error);
  }
});

/**
 * DELETE /api/tags/:id
 * Delete tag
 */
router.delete('/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    const result = await req.prisma.tag.deleteMany({
      where: {
        id,
        organizationId: req.organizationId,
      },
    });

    if (result.count === 0) {
      return res.status(404).json({ error: 'Tag not found' });
    }

    return res.json({ message: 'Tag deleted successfully' });
  } catch (error) {
    return next(error);
  }
});

export default router;
