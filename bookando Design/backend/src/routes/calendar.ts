import express from 'express';
import { calendarService } from '../events/eventHandlers';
import { requireAuth } from '../middleware/auth';
import { tenancyMiddleware, AuthenticatedRequest } from '../middleware/tenancy';
import { CalendarProvider } from '../services/CalendarIntegrationService';

const router = express.Router();

// Apply authentication and tenancy to protected routes
router.use(requireAuth);
router.use(tenancyMiddleware);

/**
 * GET /api/calendar/connections
 * Get all calendar connections for current organization's employees
 */
router.get('/connections', async (req: AuthenticatedRequest, res) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    // Get all connections for this organization
    const connections = await req.prisma.calendarConnection.findMany({
      where: { organizationId: req.organizationId },
      include: {
        employee: {
          select: {
            id: true,
            firstName: true,
            lastName: true,
            email: true,
          },
        },
        _count: {
          select: { events: true },
        },
      },
    });

    return res.json({ data: connections, count: connections.length });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * GET /api/calendar/connections/employee/:employeeId
 * Get calendar connections for specific employee
 */
router.get('/connections/employee/:employeeId', async (req: AuthenticatedRequest, res) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { employeeId } = req.params;

    const connections = await calendarService.getConnections(employeeId);

    return res.json({ data: connections, count: connections.length });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * GET /api/calendar/connections/:id
 * Get specific calendar connection
 */
router.get('/connections/:id', async (req: AuthenticatedRequest, res) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    const connection = await calendarService.getConnection(id);

    if (!connection || connection.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Connection not found' });
    }

    return res.json({ data: connection });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * POST /api/calendar/connect
 * Initiate calendar connection (returns OAuth URL)
 */
router.post('/connect', async (req: AuthenticatedRequest, res) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { employeeId, provider } = req.body;

    if (!employeeId || !provider) {
      return res.status(400).json({ error: 'employeeId and provider are required' });
    }

    // Validate provider
    if (!Object.values(CalendarProvider).includes(provider)) {
      return res.status(400).json({ error: 'Invalid calendar provider' });
    }

    // Verify employee belongs to organization
    const employee = await req.prisma.employee.findFirst({
      where: {
        id: employeeId,
        organizationId: req.organizationId,
      },
    });

    if (!employee) {
      return res.status(404).json({ error: 'Employee not found' });
    }

    // Get OAuth URL
    const authUrl = await calendarService.initiateConnection(
      employeeId,
      req.organizationId,
      provider
    );

    return res.json({
      authUrl,
      message: 'Redirect user to authUrl to complete authorization',
    });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * GET /api/calendar/callback
 * OAuth callback endpoint (public, no auth required)
 * This is called by the OAuth provider after user authorizes
 */
router.get('/callback', async (req, res) => {
  try {
    const { code, state, error } = req.query;

    if (error) {
      return res.status(400).send(`
        <html>
          <body>
            <h1>Calendar Connection Failed</h1>
            <p>Error: ${error}</p>
            <p>You can close this window and try again.</p>
          </body>
        </html>
      `);
    }

    if (!code || !state) {
      return res.status(400).send(`
        <html>
          <body>
            <h1>Calendar Connection Failed</h1>
            <p>Missing authorization code or state.</p>
          </body>
        </html>
      `);
    }

    // Decode state to get organizationId
    const stateData = JSON.parse(Buffer.from(state as string, 'base64').toString());
    const { organizationId } = stateData;

    // Handle callback
    const connection = await calendarService.handleCallback(
      code as string,
      state as string,
      organizationId
    );

    return res.send(`
      <html>
        <head>
          <style>
            body {
              font-family: Arial, sans-serif;
              display: flex;
              justify-content: center;
              align-items: center;
              height: 100vh;
              margin: 0;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .success-card {
              background: white;
              padding: 40px;
              border-radius: 10px;
              box-shadow: 0 10px 40px rgba(0,0,0,0.2);
              text-align: center;
              max-width: 400px;
            }
            h1 {
              color: #4CAF50;
              margin-bottom: 20px;
            }
            p {
              color: #666;
              line-height: 1.6;
            }
          </style>
        </head>
        <body>
          <div class="success-card">
            <h1>✅ Calendar Connected Successfully!</h1>
            <p><strong>Provider:</strong> ${connection.provider}</p>
            <p><strong>Calendar:</strong> ${connection.email}</p>
            <p>Your calendar is now synced. You can close this window.</p>
          </div>
        </body>
      </html>
    `);
  } catch (error: any) {
    console.error('Calendar callback error:', error);
    return res.status(500).send(`
      <html>
        <body>
          <h1>Calendar Connection Failed</h1>
          <p>Error: ${error.message}</p>
          <p>Please try again or contact support if the problem persists.</p>
        </body>
      </html>
    `);
  }
});

/**
 * DELETE /api/calendar/connections/:id
 * Disconnect calendar
 */
router.delete('/connections/:id', async (req: AuthenticatedRequest, res) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;

    // Verify connection belongs to organization
    const connection = await req.prisma.calendarConnection.findUnique({
      where: { id },
    });

    if (!connection || connection.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Connection not found' });
    }

    await calendarService.disconnect(id);

    return res.json({ message: 'Calendar disconnected successfully' });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * PUT /api/calendar/connections/:id
 * Update calendar connection settings
 */
router.put('/connections/:id', async (req: AuthenticatedRequest, res) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { id } = req.params;
    const { syncDirection, autoCreateEvents, autoUpdateEvents, autoDeleteEvents, isActive } = req.body;

    // Verify connection belongs to organization
    const existingConnection = await req.prisma.calendarConnection.findUnique({
      where: { id },
    });

    if (!existingConnection || existingConnection.organizationId !== req.organizationId) {
      return res.status(404).json({ error: 'Connection not found' });
    }

    // Update connection
    const connection = await req.prisma.calendarConnection.update({
      where: { id },
      data: {
        syncDirection,
        autoCreateEvents,
        autoUpdateEvents,
        autoDeleteEvents,
        isActive,
      },
    });

    return res.json({
      data: connection,
      message: 'Calendar connection updated successfully',
    });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * GET /api/calendar/availability/:employeeId
 * Get employee availability for a date range
 */
router.get('/availability/:employeeId', async (req: AuthenticatedRequest, res) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { employeeId } = req.params;
    const { startDate, endDate } = req.query;

    if (!startDate || !endDate) {
      return res.status(400).json({ error: 'startDate and endDate are required' });
    }

    // Verify employee belongs to organization
    const employee = await req.prisma.employee.findFirst({
      where: {
        id: employeeId,
        organizationId: req.organizationId,
      },
    });

    if (!employee) {
      return res.status(404).json({ error: 'Employee not found' });
    }

    const availability = await calendarService.getAvailability(
      employeeId,
      new Date(startDate as string),
      new Date(endDate as string)
    );

    return res.json({ data: availability, count: availability.length });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * GET /api/calendar/events
 * Get synced calendar events
 */
router.get('/events', async (req: AuthenticatedRequest, res) => {
  try {
    if (!req.organizationId) {
      return res.status(400).json({ error: 'Organization ID is required' });
    }

    const { employeeId, bookingId, connectionId, limit = 50, offset = 0 } = req.query;

    const where: any = {
      connection: {
        organizationId: req.organizationId,
      },
    };

    if (employeeId) {
      where.connection = { ...where.connection, employeeId: employeeId as string };
    }
    if (bookingId) where.bookingId = bookingId as string;
    if (connectionId) where.connectionId = connectionId as string;

    const events = await req.prisma.calendarEvent.findMany({
      where,
      include: {
        connection: {
          select: {
            id: true,
            provider: true,
            email: true,
            employee: {
              select: {
                id: true,
                firstName: true,
                lastName: true,
              },
            },
          },
        },
        booking: {
          select: {
            id: true,
            bookingNumber: true,
            status: true,
          },
        },
      },
      orderBy: { startTime: 'desc' },
      take: Number(limit),
      skip: Number(offset),
    });

    const total = await req.prisma.calendarEvent.count({ where });

    return res.json({
      data: events,
      count: events.length,
      total,
      limit: Number(limit),
      offset: Number(offset),
    });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

/**
 * GET /api/calendar/providers
 * Get list of supported calendar providers
 */
router.get('/providers', async (_req: AuthenticatedRequest, res) => {
  try {
    const providers = Object.values(CalendarProvider).map((provider) => ({
      id: provider,
      name: provider === CalendarProvider.GOOGLE ? 'Google Calendar' :
            provider === CalendarProvider.MICROSOFT ? 'Microsoft Outlook' :
            provider === CalendarProvider.APPLE ? 'Apple Calendar' : provider,
      supported: provider !== CalendarProvider.APPLE, // Apple requires special setup
    }));

    return res.json({ data: providers });
  } catch (error: any) {
    return res.status(500).json({ error: error.message });
  }
});

export default router;
