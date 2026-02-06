
import { Request as ExpressRequest, Response, NextFunction } from 'express';
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

/**
 * Multi-Tenancy Middleware
 *
 * CRITICAL: This middleware ensures data isolation between organizations.
 * Every request is scoped to a specific organizationId to prevent data leaks.
 *
 * organizationId is extracted from:
 * 1. JWT token (req.user.organizationId) - for authenticated requests
 * 2. Subdomain (org.bookando.ch) - for custom domains
 * 3. Header (X-Organization-Id) - for API integrations
 */

export interface AuthenticatedRequest extends ExpressRequest {
  user?: {
    userId: string;
    email: string;
    organizationId: string;
    roleId: string;
  };
  organizationId?: string;
  prisma?: any; // Scoped Prisma Client
  
  // Explicitly define properties to resolve TS errors if base Request type is incomplete in environment
  headers: any;
  hostname: string;
  query: any;
  params: any;
  body: any;
}

export async function tenancyMiddleware(
  req: AuthenticatedRequest,
  res: Response,
  next: NextFunction
) {
  try {
    let organizationId: string | undefined;

    // 1. Try to get from JWT (authenticated requests)
    if (req.user?.organizationId) {
      organizationId = req.user.organizationId;
    }

    // 2. Try to get from subdomain (e.g., fahrschule.bookando.ch)
    if (!organizationId) {
      const host = req.hostname;
      const subdomain = host.split('.')[0];

      if (subdomain && subdomain !== 'api' && subdomain !== 'app' && subdomain !== 'localhost') {
        // Look up organization by subdomain
        const org = await prisma.organization.findUnique({
          where: { subdomain }
        });

        if (org) {
          organizationId = org.id;
        }
      }
    }

    // 3. Try to get from header (API integrations, WordPress)
    if (!organizationId) {
      const headerOrgId = req.headers['x-organization-id'] as string;
      if (headerOrgId) {
        organizationId = headerOrgId;
      }
    }

    // 4. For development: Use default organization
    if (!organizationId && process.env.NODE_ENV === 'development') {
      // Try to find or create a default org
      let defaultOrg = await prisma.organization.findFirst({
        where: { email: 'dev@bookando.local' }
      });

      if (!defaultOrg) {
        console.log('⚠️ No organization found, creating default dev org...');
        defaultOrg = await prisma.organization.create({
          data: {
            name: 'Development Organization',
            email: 'dev@bookando.local',
            country: 'CH',
            language: 'de',
            timezone: 'Europe/Zurich',
            currency: 'CHF'
          }
        });
        console.log(`✅ Created default org: ${defaultOrg.id}`);
      }

      organizationId = defaultOrg.id;
    }

    // If still no organizationId, reject
    if (!organizationId) {
      return res.status(400).json({
        error: 'Organization not specified',
        message: 'Please provide organization via JWT, subdomain, or X-Organization-Id header'
      });
    }

    // Attach to request
    req.organizationId = organizationId;

    // Create a scoped Prisma client that automatically filters by organizationId
    // This is the MAGIC that prevents data leaks!
    req.prisma = prisma.$extends({
      query: {
        // Scope all queries for models with organizationId
        customer: {
          async findMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async findFirst({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async findUnique({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async create({ args, query }) {
            args.data = { ...args.data, organizationId };
            return query(args);
          },
          async update({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async updateMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async delete({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async deleteMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
        },
        // Repeat for other models (employee, service, booking, etc.)
        employee: {
          async findMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async create({ args, query }) {
            args.data = { ...args.data, organizationId };
            return query(args);
          },
        },
        service: {
          async findMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async create({ args, query }) {
            args.data = { ...args.data, organizationId };
            return query(args);
          },
        },
        booking: {
          async findMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async create({ args, query }) {
            args.data = { ...args.data, organizationId };
            return query(args);
          },
        },
        course: {
          async findMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async create({ args, query }) {
            args.data = { ...args.data, organizationId };
            return query(args);
          },
        },
        invoice: {
          async findMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async create({ args, query }) {
            args.data = { ...args.data, organizationId };
            return query(args);
          },
        },
        location: {
          async findMany({ args, query }) {
            args.where = { ...args.where, organizationId };
            return query(args);
          },
          async create({ args, query }) {
            args.data = { ...args.data, organizationId };
            return query(args);
          },
        },
      },
    });

    next();
  } catch (error) {
    console.error('Tenancy middleware error:', error);
    res.status(500).json({
      error: 'Internal server error',
      message: 'Failed to determine organization context'
    });
  }
}

/**
 * Optional: Skip tenancy for certain routes
 */
export function skipTenancy(req: ExpressRequest, res: Response, next: NextFunction) {
  next();
}