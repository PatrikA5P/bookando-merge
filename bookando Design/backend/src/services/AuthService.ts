import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { randomUUID } from 'crypto';

const prisma = new PrismaClient();
const JWT_SECRET = process.env.JWT_SECRET || 'development-secret-change-in-production';
const JWT_EXPIRY = process.env.JWT_EXPIRY || '15m';
const REFRESH_TOKEN_EXPIRY = process.env.REFRESH_TOKEN_EXPIRY || '7d';

export class AuthService {
  /**
   * Register a new user
   */
  async register(data: {
    email: string;
    password: string;
    firstName: string;
    lastName: string;
    organizationId: string;
    roleId?: string;
  }) {
    // Check if user exists in this organization
    const existing = await prisma.user.findUnique({
      where: {
        organizationId_email: {
          organizationId: data.organizationId,
          email: data.email
        }
      }
    });

    if (existing) {
      throw new Error('User with this email already exists in this organization');
    }

    // If no roleId provided, find or create a default role
    let roleId = data.roleId;

    if (!roleId) {
      // Find "Admin" role
      let adminRole = await prisma.role.findFirst({
        where: { name: 'Admin' }
      });

      // Create default role if not exists
      if (!adminRole) {
        adminRole = await prisma.role.create({
          data: {
            name: 'Admin',
            permissions: {
              dashboard: { read: true, write: true, delete: true },
              customers: { read: true, write: true, delete: true },
              employees: { read: true, write: true, delete: true },
              workday: { read: true, write: true, delete: true },
              finance: { read: true, write: true, delete: true },
              offers: { read: true, write: true, delete: true },
              academy: { read: true, write: true, delete: true },
              resources: { read: true, write: true, delete: true },
              tools: { read: true, write: true, delete: true },
              settings: { read: true, write: true, delete: true },
            }
          }
        });
      }

      roleId = adminRole.id;
    }

    // Hash password
    const passwordHash = await bcrypt.hash(data.password, 10);

    // Create user
    const user = await prisma.user.create({
      data: {
        email: data.email,
        passwordHash,
        firstName: data.firstName,
        lastName: data.lastName,
        organizationId: data.organizationId,
        roleId,
        status: 'ACTIVE'
      },
      include: {
        role: true,
        organization: {
          include: { license: true }
        }
      }
    });

    // Generate tokens
    const token = this.generateToken(user);
    const refreshToken = await this.generateRefreshToken(user.id);

    return {
      user: this.sanitizeUser(user),
      token,
      refreshToken
    };
  }

  /**
   * Login user
   * @param email User email
   * @param password User password
   * @param organizationId Optional organization ID for multi-tenant scenarios
   */
  async login(email: string, password: string, organizationId?: string) {
    let user;

    if (organizationId) {
      // Login with specific organization
      user = await prisma.user.findUnique({
        where: {
          organizationId_email: {
            organizationId,
            email
          }
        },
        include: {
          role: true,
          organization: {
            include: { license: true }
          }
        }
      });
    } else {
      // Legacy: Find user by email only (for backward compatibility)
      const users = await prisma.user.findMany({
        where: { email },
        include: {
          role: true,
          organization: {
            include: { license: true }
          }
        }
      });

      if (users.length > 1) {
        throw new Error('Multiple accounts found. Please specify organization.');
      }

      user = users[0];
    }

    if (!user) {
      throw new Error('Invalid email or password');
    }

    if (user.status !== 'ACTIVE') {
      throw new Error('Account is not active');
    }

    // Verify password
    const valid = await bcrypt.compare(password, user.passwordHash);

    if (!valid) {
      throw new Error('Invalid email or password');
    }

    // Update last login
    await prisma.user.update({
      where: { id: user.id },
      data: { lastLogin: new Date() }
    });

    // Generate tokens
    const token = this.generateToken(user);
    const refreshToken = await this.generateRefreshToken(user.id);

    return {
      user: this.sanitizeUser(user),
      token,
      refreshToken
    };
  }

  /**
   * Refresh access token
   */
  async refreshAccessToken(refreshToken: string) {
    // Find refresh token
    const tokenRecord = await prisma.refreshToken.findUnique({
      where: { token: refreshToken },
      include: {
        user: {
          include: {
            role: true,
            organization: {
              include: { license: true }
            }
          }
        }
      }
    });

    if (!tokenRecord) {
      throw new Error('Invalid refresh token');
    }

    // Check expiry
    if (tokenRecord.expiresAt < new Date()) {
      // Delete expired token
      await prisma.refreshToken.delete({
        where: { id: tokenRecord.id }
      });
      throw new Error('Refresh token expired');
    }

    // Generate new access token
    const newToken = this.generateToken(tokenRecord.user);

    return {
      user: this.sanitizeUser(tokenRecord.user),
      token: newToken,
      refreshToken: tokenRecord.token
    };
  }

  /**
   * Logout (invalidate refresh token)
   */
  async logout(refreshToken: string) {
    await prisma.refreshToken.deleteMany({
      where: { token: refreshToken }
    });
  }

  /**
   * Generate JWT access token
   */
  private generateToken(user: any): string {
    return jwt.sign(
      {
        userId: user.id,
        email: user.email,
        organizationId: user.organizationId,
        roleId: user.roleId
      },
      JWT_SECRET,
      { expiresIn: JWT_EXPIRY }
    );
  }

  /**
   * Generate refresh token
   */
  private async generateRefreshToken(userId: string): Promise<string> {
    // Calculate expiry
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 7); // 7 days

    // Generate random token with unique jti (JWT ID) to prevent collisions
    const token = jwt.sign(
      {
        userId,
        jti: randomUUID() // Ensures uniqueness even with rapid logins
      },
      JWT_SECRET,
      { expiresIn: REFRESH_TOKEN_EXPIRY }
    );

    // Store in database
    await prisma.refreshToken.create({
      data: {
        token,
        userId,
        expiresAt
      }
    });

    return token;
  }

  /**
   * Remove password from user object
   */
  private sanitizeUser(user: any) {
    const { passwordHash, ...sanitized } = user;
    return sanitized;
  }
}
