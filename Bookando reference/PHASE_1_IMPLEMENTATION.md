# Phase 1: Fundament - Detaillierte Umsetzung (6 Wochen)

## Woche 1-2: Backend Setup & Database

### Tag 1-2: Project Setup

#### 1. Backend-Ordner erstellen
```bash
cd /home/user/bookando-monorepo
mkdir -p backend/{src,prisma,tests}
cd backend
npm init -y
```

#### 2. Dependencies installieren
```bash
npm install express cors dotenv
npm install @prisma/client prisma
npm install jsonwebtoken bcryptjs
npm install express-validator
npm install bullmq ioredis
npm install socket.io
npm install stripe
npm install @sendgrid/mail
npm install helmet express-rate-limit

npm install -D typescript @types/node @types/express
npm install -D @types/jsonwebtoken @types/bcryptjs
npm install -D @types/cors
npm install -D tsx nodemon
npm install -D vitest @vitest/ui
```

#### 3. TypeScript Setup
```json
// tsconfig.json
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "commonjs",
    "lib": ["ES2022"],
    "outDir": "./dist",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "resolveJsonModule": true,
    "moduleResolution": "node"
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist", "tests"]
}
```

#### 4. Package.json Scripts
```json
{
  "scripts": {
    "dev": "tsx watch src/server.ts",
    "build": "tsc",
    "start": "node dist/server.js",
    "prisma:generate": "prisma generate",
    "prisma:migrate": "prisma migrate dev",
    "prisma:studio": "prisma studio",
    "test": "vitest",
    "test:ui": "vitest --ui"
  }
}
```

---

### Tag 3-5: Prisma Schema & Database

#### 1. Prisma Init
```bash
npx prisma init
```

#### 2. Complete Prisma Schema
```prisma
// prisma/schema.prisma
generator client {
  provider = "prisma-client-js"
}

datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

// ============================================
// CORE ENTITIES
// ============================================

model Organization {
  id          String   @id @default(uuid())
  name        String
  subdomain   String?  @unique
  email       String
  phone       String?
  address     String?
  city        String?
  zip         String?
  country     String   @default("CH")

  // Settings
  language    String   @default("de")
  timezone    String   @default("Europe/Zurich")
  currency    String   @default("CHF")

  // License
  licenseId   String?  @unique
  license     License? @relation(fields: [licenseId], references: [id])

  // Timestamps
  createdAt   DateTime @default(now())
  updatedAt   DateTime @updatedAt

  // Relations
  users       User[]
  customers   Customer[]
  employees   Employee[]
  courses     Course[]
  services    Service[]
  bookings    Booking[]
  invoices    Invoice[]
  locations   Location[]

  @@map("organizations")
}

model User {
  id             String   @id @default(uuid())
  email          String   @unique
  passwordHash   String
  firstName      String
  lastName       String

  // Organization
  organizationId String
  organization   Organization @relation(fields: [organizationId], references: [id])

  // Role
  roleId         String
  role           Role     @relation(fields: [roleId], references: [id])

  // Status
  status         UserStatus @default(ACTIVE)
  lastLogin      DateTime?

  // Timestamps
  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  // Relations
  employee       Employee?
  devices        Device[]
  refreshTokens  RefreshToken[]

  @@map("users")
}

enum UserStatus {
  ACTIVE
  INACTIVE
  SUSPENDED
}

model Role {
  id          String   @id @default(uuid())
  name        String
  permissions Json     // ModulePermissions object

  users       User[]

  @@map("roles")
}

model RefreshToken {
  id        String   @id @default(uuid())
  token     String   @unique
  userId    String
  user      User     @relation(fields: [userId], references: [id], onDelete: Cascade)
  expiresAt DateTime
  createdAt DateTime @default(now())

  @@map("refresh_tokens")
}

model Device {
  id        String   @id @default(uuid())
  userId    String
  user      User     @relation(fields: [userId], references: [id], onDelete: Cascade)

  fcmToken  String   @unique
  platform  DevicePlatform
  model     String?
  osVersion String?

  createdAt DateTime @default(now())
  updatedAt DateTime @updatedAt

  @@map("devices")
}

enum DevicePlatform {
  IOS
  ANDROID
  WEB
}

// ============================================
// CUSTOMERS
// ============================================

model Customer {
  id             String   @id @default(uuid())
  organizationId String
  organization   Organization @relation(fields: [organizationId], references: [id])

  firstName      String
  lastName       String
  email          String
  phone          String?

  // Address
  address        String?
  zip            String?
  city           String?
  country        String?

  // Personal
  birthday       String?
  gender         String?

  // Custom Fields (JSON)
  customFields   Json?

  // Status
  status         CustomerStatus @default(ACTIVE)

  // Timestamps
  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  // Relations
  bookings       Booking[]
  invoices       Invoice[]
  enrollments    Enrollment[]
  educationCards CustomerEducationCard[]

  @@unique([organizationId, email])
  @@map("customers")
}

enum CustomerStatus {
  ACTIVE
  INACTIVE
  ARCHIVED
}

// ============================================
// EMPLOYEES
// ============================================

model Employee {
  id             String   @id @default(uuid())
  organizationId String
  organization   Organization @relation(fields: [organizationId], references: [id])

  userId         String   @unique
  user           User     @relation(fields: [userId], references: [id])

  position       String
  department     String?
  hireDate       String
  exitDate       String?

  // Skills (for auto-assignment)
  skills         String[]
  qualifications String[]

  // Status
  status         EmployeeStatus @default(ACTIVE)

  // Timestamps
  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  // Relations
  bookings       Booking[]
  timeEntries    TimeEntry[]
  shifts         Shift[]
  absences       Absence[]
  courseSessions CourseSession[]

  @@map("employees")
}

enum EmployeeStatus {
  ACTIVE
  ON_LEAVE
  INACTIVE
}

model TimeEntry {
  id         String   @id @default(uuid())
  employeeId String
  employee   Employee @relation(fields: [employeeId], references: [id])

  date       String
  startTime  String
  endTime    String?
  type       TimeEntryType
  status     TimeEntryStatus

  createdAt  DateTime @default(now())
  updatedAt  DateTime @updatedAt

  @@map("time_entries")
}

enum TimeEntryType {
  WORK
  BREAK
  SICK
  VACATION
}

enum TimeEntryStatus {
  PENDING
  APPROVED
  REJECTED
}

model Shift {
  id         String   @id @default(uuid())
  employeeId String
  employee   Employee @relation(fields: [employeeId], references: [id])

  date       String
  type       ShiftType
  startTime  String
  endTime    String

  createdAt  DateTime @default(now())

  @@map("shifts")
}

enum ShiftType {
  EARLY
  LATE
  NIGHT
  OFF
}

model Absence {
  id           String   @id @default(uuid())
  employeeId   String
  employee     Employee @relation(fields: [employeeId], references: [id])

  type         AbsenceType
  startDate    String
  endDate      String
  reason       String?
  status       AbsenceStatus

  requestedAt  DateTime @default(now())

  @@map("absences")
}

enum AbsenceType {
  VACATION
  SICK
  PERSONAL
  OTHER
}

enum AbsenceStatus {
  PENDING
  APPROVED
  REJECTED
}

// ============================================
// COURSES & ACADEMY
// ============================================

model Course {
  id             String   @id @default(uuid())
  organizationId String
  organization   Organization @relation(fields: [organizationId], references: [id])

  title          String
  description    String?
  coverImage     String?

  type           CourseType
  visibility     CourseVisibility
  category       String

  // Content
  curriculum     Json     // Array of Topics

  // Certificate
  certificate    Boolean  @default(false)
  certificateTemplate String?

  // Metadata
  duration       Int?     // Total duration in hours
  difficulty     String?
  tags           String[]

  // Status
  published      Boolean  @default(false)

  // Timestamps
  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  // Relations
  sessions       CourseSession[]
  enrollments    Enrollment[]
  services       Service[]

  @@map("courses")
}

enum CourseType {
  ONLINE
  IN_PERSON
  BLENDED
}

enum CourseVisibility {
  PUBLIC
  PRIVATE
  INTERNAL
}

model CourseSession {
  id         String   @id @default(uuid())
  courseId   String
  course     Course   @relation(fields: [courseId], references: [id])

  date       String
  startTime  String
  endTime    String

  // Instructor
  instructorId String?
  instructor   Employee? @relation(fields: [instructorId], references: [id])

  // Location
  locationId String?
  location   Location? @relation(fields: [locationId], references: [id])
  roomId     String?
  room       Room?     @relation(fields: [roomId], references: [id])

  // Capacity
  maxParticipants    Int
  currentEnrollment  Int      @default(0)

  // Status
  status     SessionStatus

  // Timestamps
  createdAt  DateTime @default(now())
  updatedAt  DateTime @updatedAt

  // Relations
  bookings   Booking[]

  @@map("course_sessions")
}

enum SessionStatus {
  SCHEDULED
  FULL
  CANCELLED
  COMPLETED
}

model Enrollment {
  id         String   @id @default(uuid())
  customerId String
  customer   Customer @relation(fields: [customerId], references: [id])
  courseId   String
  course     Course   @relation(fields: [courseId], references: [id])

  // Progress
  progress   Json     // Array of completed lessons
  completed  Boolean  @default(false)
  completedAt DateTime?

  // Certificate
  certificateIssued Boolean  @default(false)
  certificateUrl    String?

  enrolledAt DateTime @default(now())

  @@unique([customerId, courseId])
  @@map("enrollments")
}

model EducationCard {
  id             String   @id @default(uuid())
  title          String
  description    String?

  chapters       Json     // Array of chapters
  gradingConfig  Json     // Grading configuration
  automation     Json     // Automation rules

  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  assignments    CustomerEducationCard[]
  services       Service[]

  @@map("education_cards")
}

model CustomerEducationCard {
  id             String   @id @default(uuid())
  customerId     String
  customer       Customer @relation(fields: [customerId], references: [id])
  cardId         String
  card           EducationCard @relation(fields: [cardId], references: [id])

  progress       Json     // Progress tracking
  completed      Boolean  @default(false)

  assignedAt     DateTime @default(now())
  completedAt    DateTime?

  @@unique([customerId, cardId])
  @@map("customer_education_cards")
}

// ============================================
// SERVICES & OFFERS
// ============================================

model Service {
  id             String   @id @default(uuid())
  organizationId String
  organization   Organization @relation(fields: [organizationId], references: [id])

  title          String
  description    String?
  type           ServiceType

  // Pricing
  price          Float
  currency       String   @default("CHF")

  // Details
  duration       Int?     // minutes
  capacity       Int?

  // Booking Rules
  minNoticeHours Int?
  maxAdvanceDays Int?

  // Dynamic Pricing
  dynamicPricing Boolean  @default(false)
  pricingRuleId  String?
  pricingRule    PricingRule? @relation(fields: [pricingRuleId], references: [id])

  // Linked Content
  linkedCourseId       String?
  linkedCourse         Course? @relation(fields: [linkedCourseId], references: [id])
  linkedEducationCardId String?
  linkedEducationCard   EducationCard? @relation(fields: [linkedEducationCardId], references: [id])

  // Form
  formTemplateId String?
  formTemplate   FormTemplate? @relation(fields: [formTemplateId], references: [id])

  // Category
  categoryId     String?
  category       Category? @relation(fields: [categoryId], references: [id])

  // Status
  active         Boolean  @default(true)

  // Timestamps
  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  // Relations
  bookings       Booking[]
  extras         ServiceExtra[]

  @@map("services")
}

enum ServiceType {
  SERVICE
  ONLINE_COURSE
  EVENT
}

model Category {
  id          String   @id @default(uuid())
  name        String
  description String?
  color       String?
  image       String?

  services    Service[]

  @@map("categories")
}

model ServiceExtra {
  id        String   @id @default(uuid())
  serviceId String
  service   Service  @relation(fields: [serviceId], references: [id])

  name      String
  description String?
  price     Float
  priceType ExtraPriceType

  @@map("service_extras")
}

enum ExtraPriceType {
  FIXED
  PERCENTAGE
}

model PricingRule {
  id          String   @id @default(uuid())
  name        String
  type        PricingRuleType

  config      Json     // Rule configuration

  services    Service[]

  @@map("pricing_rules")
}

enum PricingRuleType {
  EARLY_BIRD
  LAST_MINUTE
  SEASONAL
  DEMAND
  HISTORY
}

model FormTemplate {
  id          String   @id @default(uuid())
  name        String
  description String?
  elements    Json     // Array of FormElements
  active      Boolean  @default(true)

  services    Service[]

  @@map("form_templates")
}

// ============================================
// BOOKINGS
// ============================================

model Booking {
  id             String   @id @default(uuid())
  bookingNumber  String   @unique
  organizationId String
  organization   Organization @relation(fields: [organizationId], references: [id])

  // Customer
  customerId     String
  customer       Customer @relation(fields: [customerId], references: [id])

  // Service
  serviceId      String
  service        Service  @relation(fields: [serviceId], references: [id])

  // Session (for courses)
  sessionId      String?
  session        CourseSession? @relation(fields: [sessionId], references: [id])

  // Date & Time
  scheduledDate  String
  scheduledTime  String

  // Pricing
  basePrice      Float
  appliedPricing Json?    // DynamicPricingResult
  extras         Json[]   // BookingExtras
  totalPrice     Float

  // Assignment
  employeeId     String?
  employee       Employee? @relation(fields: [employeeId], references: [id])

  // Resources
  resourceAllocation Json?

  // Form
  formResponses  Json[]

  // Status
  status         BookingStatus
  paymentStatus  PaymentStatus

  // Invoice
  invoiceId      String?  @unique
  invoice        Invoice? @relation(fields: [invoiceId], references: [id])

  // Timestamps
  createdAt      DateTime @default(now())
  confirmedAt    DateTime?
  paidAt         DateTime?
  completedAt    DateTime?
  cancelledAt    DateTime?

  @@map("bookings")
}

enum BookingStatus {
  PENDING
  CONFIRMED
  PAID
  COMPLETED
  CANCELLED
  NO_SHOW
}

enum PaymentStatus {
  UNPAID
  PARTIAL
  PAID
  REFUNDED
}

// ============================================
// FINANCE
// ============================================

model Invoice {
  id             String   @id @default(uuid())
  invoiceNumber  String   @unique
  organizationId String
  organization   Organization @relation(fields: [organizationId], references: [id])

  // Customer
  customerId     String
  customer       Customer @relation(fields: [customerId], references: [id])

  // Booking
  booking        Booking?

  // Amounts
  amount         Float
  vatRate        Float?
  vatAmount      Float?
  totalAmount    Float
  currency       String   @default("CHF")

  // Payment
  status         InvoiceStatus
  dueDate        String
  paidAt         DateTime?

  // Dunning
  dunningLevel   Int      @default(0)
  lastReminderAt DateTime?

  // Items
  items          Json[]

  // Timestamps
  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  @@map("invoices")
}

enum InvoiceStatus {
  DRAFT
  SENT
  PAID
  OVERDUE
  CANCELLED
  PARTIAL
}

// ============================================
// RESOURCES
// ============================================

model Location {
  id             String   @id @default(uuid())
  organizationId String
  organization   Organization @relation(fields: [organizationId], references: [id])

  name           String
  address        String?
  city           String?
  zip            String?
  country        String?

  rooms          Room[]
  courseSessions CourseSession[]

  @@map("locations")
}

model Room {
  id         String   @id @default(uuid())
  locationId String
  location   Location @relation(fields: [locationId], references: [id])

  name       String
  capacity   Int
  equipment  String[]

  courseSessions CourseSession[]

  @@map("rooms")
}

// ============================================
// LICENSING
// ============================================

model License {
  id             String   @id @default(uuid())
  organization   Organization?

  tier           LicenseTier
  status         LicenseStatus

  // Platforms
  platforms      Json     // PlatformAccess object

  // Modules
  enabledModules Json     // ModuleCapabilities object

  // Limits
  limits         Json     // LicenseLimits object

  // Features
  features       Json     // LicenseFeatures object

  // Billing
  validFrom      DateTime
  validUntil     DateTime
  billingCycle   BillingCycle

  price          Float
  currency       String   @default("CHF")

  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  @@map("licenses")
}

enum LicenseTier {
  STARTER
  PROFESSIONAL
  ENTERPRISE
}

enum LicenseStatus {
  ACTIVE
  TRIAL
  SUSPENDED
  CANCELLED
  EXPIRED
}

enum BillingCycle {
  MONTHLY
  YEARLY
}

// ============================================
// WORDPRESS INTEGRATION
// ============================================

model WordPressSite {
  id             String   @id @default(uuid())
  organizationId String

  siteUrl        String
  apiKey         String

  webhooksEnabled Boolean  @default(true)
  webhookUrl      String?
  webhookSecret   String?

  cacheDuration   Int      @default(900) // 15 minutes in seconds

  lastSync        DateTime?

  createdAt       DateTime @default(now())
  updatedAt       DateTime @updatedAt

  @@map("wordpress_sites")
}
```

#### 3. Environment Variables
```bash
# .env
DATABASE_URL="postgresql://postgres:password@localhost:5432/bookando"
REDIS_URL="redis://localhost:6379"

JWT_SECRET="your-super-secret-jwt-key-change-in-production"
JWT_EXPIRY="15m"
REFRESH_TOKEN_EXPIRY="7d"

STRIPE_SECRET_KEY="sk_test_..."
STRIPE_WEBHOOK_SECRET="whsec_..."

SENDGRID_API_KEY="SG...."

PORT=3001
NODE_ENV="development"
```

#### 4. Run Migrations
```bash
npx prisma migrate dev --name init
npx prisma generate
```

---

### Tag 6-7: Basic Server Setup

#### 1. Server Entry Point
```typescript
// src/server.ts
import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { rateLimit } from 'express-rate-limit';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(helmet());
app.use(cors({
    origin: process.env.CORS_ORIGIN || 'http://localhost:5173',
    credentials: true
}));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Rate Limiting
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // limit each IP to 100 requests per windowMs
    message: 'Too many requests from this IP, please try again later.'
});
app.use('/api/', limiter);

// Health Check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// API Routes (will add later)
app.use('/api/auth', require('./routes/auth').default);
app.use('/api/customers', require('./routes/customers').default);
app.use('/api/courses', require('./routes/courses').default);
app.use('/api/services', require('./routes/services').default);
app.use('/api/bookings', require('./routes/bookings').default);

// Error Handler
app.use((err: any, req: express.Request, res: express.Response, next: express.NextFunction) => {
    console.error(err.stack);
    res.status(err.status || 500).json({
        error: {
            message: err.message || 'Internal Server Error',
            ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
        }
    });
});

app.listen(PORT, () => {
    console.log(`🚀 Server running on http://localhost:${PORT}`);
    console.log(`📊 Prisma Studio: npx prisma studio`);
});
```

---

## Woche 3-4: Authentication & Core Services

### Authentication Service
```typescript
// src/services/AuthService.ts
import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';

const prisma = new PrismaClient();

export class AuthService {
    async register(data: {
        email: string;
        password: string;
        firstName: string;
        lastName: string;
        organizationId: string;
        roleId: string;
    }) {
        // Check if user exists
        const existing = await prisma.user.findUnique({
            where: { email: data.email }
        });

        if (existing) {
            throw new Error('User already exists');
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
                roleId: data.roleId,
                status: 'ACTIVE'
            },
            include: {
                role: true,
                organization: true
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

    async login(email: string, password: string) {
        const user = await prisma.user.findUnique({
            where: { email },
            include: {
                role: true,
                organization: {
                    include: { license: true }
                }
            }
        });

        if (!user) {
            throw new Error('Invalid credentials');
        }

        const valid = await bcrypt.compare(password, user.passwordHash);

        if (!valid) {
            throw new Error('Invalid credentials');
        }

        // Update last login
        await prisma.user.update({
            where: { id: user.id },
            data: { lastLogin: new Date() }
        });

        const token = this.generateToken(user);
        const refreshToken = await this.generateRefreshToken(user.id);

        return {
            user: this.sanitizeUser(user),
            token,
            refreshToken
        };
    }

    private generateToken(user: any): string {
        return jwt.sign(
            {
                userId: user.id,
                email: user.email,
                organizationId: user.organizationId,
                roleId: user.roleId
            },
            process.env.JWT_SECRET!,
            { expiresIn: process.env.JWT_EXPIRY }
        );
    }

    private async generateRefreshToken(userId: string): Promise<string> {
        const token = jwt.sign(
            { userId },
            process.env.JWT_SECRET!,
            { expiresIn: process.env.REFRESH_TOKEN_EXPIRY }
        );

        await prisma.refreshToken.create({
            data: {
                token,
                userId,
                expiresAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000) // 7 days
            }
        });

        return token;
    }

    private sanitizeUser(user: any) {
        const { passwordHash, ...sanitized } = user;
        return sanitized;
    }
}
```

### Auth Routes
```typescript
// src/routes/auth.ts
import express from 'express';
import { body, validationResult } from 'express-validator';
import { AuthService } from '../services/AuthService';

const router = express.Router();
const authService = new AuthService();

// POST /api/auth/login
router.post('/login',
    body('email').isEmail(),
    body('password').isLength({ min: 6 }),
    async (req, res, next) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({ errors: errors.array() });
            }

            const { email, password } = req.body;
            const result = await authService.login(email, password);

            res.json(result);
        } catch (error) {
            next(error);
        }
    }
);

// POST /api/auth/register
router.post('/register',
    body('email').isEmail(),
    body('password').isLength({ min: 6 }),
    body('firstName').notEmpty(),
    body('lastName').notEmpty(),
    async (req, res, next) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({ errors: errors.array() });
            }

            const result = await authService.register(req.body);
            res.status(201).json(result);
        } catch (error) {
            next(error);
        }
    }
);

export default router;
```

---

## Woche 5-6: Core Endpoints Implementation

### Customer Service
```typescript
// src/services/CustomerService.ts
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

export class CustomerService {
    async getAll(organizationId: string) {
        return await prisma.customer.findMany({
            where: { organizationId },
            orderBy: { createdAt: 'desc' }
        });
    }

    async getById(id: string, organizationId: string) {
        return await prisma.customer.findFirst({
            where: { id, organizationId },
            include: {
                bookings: true,
                invoices: true,
                enrollments: {
                    include: { course: true }
                }
            }
        });
    }

    async create(data: any, organizationId: string) {
        return await prisma.customer.create({
            data: {
                ...data,
                organizationId,
                status: 'ACTIVE'
            }
        });
    }

    async update(id: string, data: any, organizationId: string) {
        return await prisma.customer.updateMany({
            where: { id, organizationId },
            data
        });
    }

    async delete(id: string, organizationId: string) {
        return await prisma.customer.updateMany({
            where: { id, organizationId },
            data: { status: 'ARCHIVED' }
        });
    }

    async findOrCreate(email: string, organizationId: string) {
        let customer = await prisma.customer.findFirst({
            where: { email, organizationId }
        });

        if (!customer) {
            customer = await prisma.customer.create({
                data: {
                    email,
                    firstName: '',
                    lastName: '',
                    organizationId,
                    status: 'ACTIVE'
                }
            });
        }

        return customer;
    }
}
```

---

## Next Steps

1. ✅ Complete all service implementations (Courses, Services, Bookings)
2. ✅ Add authentication middleware
3. ✅ Implement license checking
4. ✅ Add WebSocket support for real-time
5. ✅ Setup email queue with BullMQ
6. ✅ Frontend integration

**Timeline:** 6 Wochen
**Output:** Vollständig funktionierendes Backend mit Core Endpoints

Siehe `BACKEND_SERVICES.md` für alle Service-Implementierungen.
