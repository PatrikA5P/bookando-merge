import express from 'express';
import { body, validationResult } from 'express-validator';
import { notificationService } from '../events/eventHandlers';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';
import { NotificationChannel, NotificationEvent } from '../services/NotificationService';

const router = express.Router();

// Apply authentication and tenancy to all routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/notifications/settings
 * Get organization notification settings
 */
router.get('/settings', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const settings = await req.prisma.notificationSettings.findUnique({
      where: { organizationId: req.organizationId },
    });

    if (!settings) {
      // Create default settings if none exist
      await notificationService.createDefaultSettings(req.organizationId);
      const newSettings = await req.prisma.notificationSettings.findUnique({
        where: { organizationId: req.organizationId },
      });
      return res.json({ data: newSettings });
    }

    return res.json({ data: settings });
  } catch (error) {
    return next(error);
  }
});

/**
 * PUT /api/notifications/settings
 * Update organization notification settings
 */
router.put('/settings', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const {
      emailProvider,
      emailConfig,
      smsProvider,
      smsConfig,
      whatsappProvider,
      whatsappConfig,
      pushProvider,
      pushConfig,
      enabledEvents,
    } = req.body;

    // Check if settings exist
    const existingSettings = await req.prisma.notificationSettings.findUnique({
      where: { organizationId: req.organizationId },
    });

    let settings;
    if (existingSettings) {
      // Update existing settings
      settings = await req.prisma.notificationSettings.update({
        where: { organizationId: req.organizationId },
        data: {
          emailProvider,
          emailConfig,
          smsProvider,
          smsConfig,
          whatsappProvider,
          whatsappConfig,
          pushProvider,
          pushConfig,
          enabledEvents,
        },
      });
    } else {
      // Create new settings
      settings = await req.prisma.notificationSettings.create({
        data: {
          organizationId: req.organizationId,
          emailProvider,
          emailConfig,
          smsProvider,
          smsConfig,
          whatsappProvider,
          whatsappConfig,
          pushProvider,
          pushConfig,
          enabledEvents,
        },
      });
    }

    return res.json({
      data: settings,
      message: 'Notification settings updated successfully',
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * GET /api/notifications/templates
 * Get all notification templates for organization
 */
router.get('/templates', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const templates = await req.prisma.notificationTemplate.findMany({
      where: { organizationId: req.organizationId },
      orderBy: [{ event: 'asc' }, { channel: 'asc' }],
    });

    return res.json({
      data: templates,
      count: templates.length,
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * GET /api/notifications/templates/:id
 * Get notification template by ID
 */
router.get('/templates/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    const template = await req.prisma.notificationTemplate.findFirst({
      where: {
        id,
        organizationId: req.organizationId,
      },
    });

    if (!template) {
      return res.status(404).json({ error: 'Template not found' });
    }

    return res.json({ data: template });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/notifications/templates
 * Create notification template
 */
router.post(
  '/templates',
  body('event').notEmpty().withMessage('Event is required'),
  body('channel').notEmpty().withMessage('Channel is required'),
  body('body').notEmpty().withMessage('Body is required'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      if (!req.organizationId) {
        return res.status(400).json({ error: 'Organization ID is required' });
      }

      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { event, channel, subject, body, isActive, serviceCategories, serviceIds } = req.body;

      const template = await req.prisma.notificationTemplate.create({
        data: {
          organizationId: req.organizationId,
          event,
          channel,
          subject: subject || '',
          body,
          isActive: isActive !== undefined ? isActive : true,
          serviceCategories: serviceCategories || [],
          serviceIds: serviceIds || [],
        },
      });

      return res.status(201).json({
        data: template,
        message: 'Template created successfully',
      });
    } catch (error) {
      return next(error);
    }
  }
);

/**
 * PUT /api/notifications/templates/:id
 * Update notification template
 */
router.put('/templates/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;
    const { event, channel, subject, body, isActive, serviceCategories, serviceIds } = req.body;

    const template = await req.prisma.notificationTemplate.updateMany({
      where: {
        id,
        organizationId: req.organizationId,
      },
      data: {
        event,
        channel,
        subject,
        body,
        isActive,
        serviceCategories,
        serviceIds,
      },
    });

    if (template.count === 0) {
      return res.status(404).json({ error: 'Template not found' });
    }

    // Fetch updated template
    const updatedTemplate = await req.prisma.notificationTemplate.findUnique({
      where: { id },
    });

    return res.json({
      data: updatedTemplate,
      message: 'Template updated successfully',
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * DELETE /api/notifications/templates/:id
 * Delete notification template
 */
router.delete('/templates/:id', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    const result = await req.prisma.notificationTemplate.deleteMany({
      where: {
        id,
        organizationId: req.organizationId,
      },
    });

    if (result.count === 0) {
      return res.status(404).json({ error: 'Template not found' });
    }

    return res.json({ message: 'Template deleted successfully' });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/notifications/templates/default
 * Create default notification templates for organization
 */
router.post('/templates/default', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    // Check if templates already exist
    const existingTemplates = await req.prisma.notificationTemplate.count({
      where: { organizationId: req.organizationId },
    });

    if (existingTemplates > 0) {
      return res.status(400).json({
        error: 'Default templates already exist. Delete existing templates first if you want to recreate them.',
      });
    }

    await notificationService.createDefaultTemplates(req.organizationId);

    const templates = await req.prisma.notificationTemplate.findMany({
      where: { organizationId: req.organizationId },
    });

    return res.status(201).json({
      data: templates,
      message: 'Default templates created successfully',
      count: templates.length,
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * GET /api/notifications/logs
 * Get notification logs (audit trail)
 */
router.get('/logs', async (req: AuthenticatedRequest, res, next) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { status, event, channel, limit = 50, offset = 0 } = req.query;

    const where: any = {
      organizationId: req.organizationId,
    };

    if (status) where.status = status;
    if (event) where.event = event;
    if (channel) where.channel = channel;

    const logs = await req.prisma.notificationLog.findMany({
      where,
      orderBy: { createdAt: 'desc' },
      take: Number(limit),
      skip: Number(offset),
    });

    const total = await req.prisma.notificationLog.count({ where });

    return res.json({
      data: logs,
      count: logs.length,
      total,
      limit: Number(limit),
      offset: Number(offset),
    });
  } catch (error) {
    return next(error);
  }
});

/**
 * POST /api/notifications/test
 * Send test notification
 */
router.post(
  '/test',
  body('event').notEmpty().withMessage('Event is required'),
  body('channel').notEmpty().withMessage('Channel is required'),
  body('recipient').notEmpty().withMessage('Recipient is required'),
  async (req: AuthenticatedRequest, res, next) => {
    try {
      if (!req.organizationId) {
        return res.status(400).json({ error: 'Organization ID is required' });
      }

      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
      }

      const { event, channel, recipient } = req.body;

      // Create test data
      const testData = {
        customer: {
          firstName: 'Test',
          lastName: 'Kunde',
          email: recipient,
          phone: recipient,
          name: 'Test Kunde',
        },
        bookingNumber: 'BKG-TEST123',
        scheduledDate: '2026-01-15',
        scheduledTime: '10:00',
        status: 'CONFIRMED',
        service: {
          name: 'Test Service',
          title: 'Test Service',
          price: 100,
        },
        employee: {
          firstName: 'Test',
          name: 'Test Mitarbeiter',
        },
        organization: {
          name: 'Test Fahrschule',
          email: 'info@test.ch',
          phone: '+41 44 123 45 67',
        },
        invoiceNumber: 'INV-TEST123',
        totalAmount: 100,
        dueDate: '2026-01-30',
      };

      // Send notification
      const recipientContact: any = {};
      if (channel === NotificationChannel.EMAIL) {
        recipientContact.email = recipient;
      } else if (channel === NotificationChannel.SMS || channel === NotificationChannel.WHATSAPP) {
        recipientContact.phone = recipient;
      } else if (channel === NotificationChannel.PUSH) {
        recipientContact.pushToken = recipient;
      }

      await notificationService.send(req.organizationId, event as NotificationEvent, testData, recipientContact);

      return res.json({
        message: 'Test notification sent successfully',
        event,
        channel,
        recipient,
      });
    } catch (error: any) {
      return res.status(500).json({
        error: 'Failed to send test notification',
        message: error.message,
      });
    }
  }
);

/**
 * GET /api/notifications/events
 * Get list of available notification events
 */
router.get('/events', async (_req: AuthenticatedRequest, res) => {
  try {
    const events = Object.values(NotificationEvent);
    return res.json({ data: events });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * GET /api/notifications/channels
 * Get list of available notification channels
 */
router.get('/channels', async (_req: AuthenticatedRequest, res) => {
  try {
    const channels = Object.values(NotificationChannel);
    return res.json({ data: channels });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

export default router;
