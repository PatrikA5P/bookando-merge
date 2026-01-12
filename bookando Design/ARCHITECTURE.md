# Bookando - System-Architektur & Integration-Patterns

## 1. System-Übersicht

```
┌─────────────────────────────────────────────────────────────────┐
│                        BOOKANDO PLATFORM                         │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────┐     ┌──────────────────┐     ┌──────────────┐
│   Public Portal  │     │   Admin Panel    │     │  Mobile App  │
│  (Customer-Side) │     │  (Staff-Side)    │     │   (Future)   │
└────────┬─────────┘     └────────┬─────────┘     └──────┬───────┘
         │                        │                       │
         └────────────────────────┼───────────────────────┘
                                  │
                         ┌────────▼────────┐
                         │   API Gateway   │
                         │   (Express.js)  │
                         └────────┬────────┘
                                  │
         ┌────────────────────────┼────────────────────────┐
         │                        │                        │
    ┌────▼─────┐         ┌───────▼────────┐      ┌───────▼──────┐
    │  Auth    │         │   Business     │      │   Webhook    │
    │  Service │         │   Logic Layer  │      │   Handler    │
    └──────────┘         └───────┬────────┘      └──────────────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
         │                       │                       │
    ┌────▼─────┐         ┌──────▼──────┐        ┌──────▼──────┐
    │ Booking  │         │   Course    │        │   Finance   │
    │ Service  │         │   Service   │        │   Service   │
    └────┬─────┘         └──────┬──────┘        └──────┬──────┘
         │                      │                       │
         └──────────────────────┼───────────────────────┘
                                │
                       ┌────────▼────────┐
                       │   Data Layer    │
                       │   (Prisma ORM)  │
                       └────────┬────────┘
                                │
                       ┌────────▼────────┐
                       │   PostgreSQL    │
                       │    Database     │
                       └─────────────────┘

┌────────────────────────────────────────────────────────────────┐
│                    EXTERNAL SERVICES                            │
├────────────────┬─────────────────┬──────────────┬──────────────┤
│  Stripe/PayPal │   SendGrid      │   Twilio     │  AWS S3      │
│   (Payments)   │   (Email)       │   (SMS)      │  (Storage)   │
└────────────────┴─────────────────┴──────────────┴──────────────┘
```

---

## 2. Modul-Verknüpfung: Event-Flow-Diagramm

### 2.1 Buchungs-Workflow (Vollständig)

```
┌──────────────┐
│   CUSTOMER   │
│ Books Service│
│  on Website  │
└──────┬───────┘
       │
       ▼
┌────────────────────────────────────────────────────────────┐
│  Step 1: CREATE BOOKING                                    │
│  - Validate service availability                           │
│  - Check customer exists (create if new)                   │
│  - Apply dynamic pricing                                   │
│  - Check employee availability                             │
│  - Check resource availability                             │
│  - Generate booking number                                 │
│  Status: PENDING                                           │
└──────┬─────────────────────────────────────────────────────┘
       │
       │ Event: BOOKING_CREATED
       ├──────────────────┬──────────────────┬──────────────────┐
       ▼                  ▼                  ▼                  ▼
┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
│  Customer   │   │  Employee   │   │  Resource   │   │   Finance   │
│  Service    │   │  Service    │   │  Service    │   │   Service   │
├─────────────┤   ├─────────────┤   ├─────────────┤   ├─────────────┤
│ Create if   │   │ Assign best │   │ Reserve room│   │ Create draft│
│ not exists  │   │ match based │   │ and equip.  │   │ invoice     │
│             │   │ on skills   │   │             │   │             │
└─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘
       │                  │                  │                  │
       └──────────────────┴──────────────────┴──────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────┐
│  Step 2: SEND CONFIRMATION EMAIL                           │
│  - Email template: "Booking Confirmation"                  │
│  - Include: Service details, date/time, price, employee    │
│  - Attach: Invoice PDF                                     │
│  - CTA: "Confirm Booking" button                           │
└──────┬─────────────────────────────────────────────────────┘
       │
       │ Customer clicks "Confirm"
       ▼
┌────────────────────────────────────────────────────────────┐
│  Step 3: CONFIRM BOOKING                                   │
│  Status: CONFIRMED                                         │
└──────┬─────────────────────────────────────────────────────┘
       │
       │ Event: BOOKING_CONFIRMED
       ├──────────────────┬──────────────────┬──────────────────┐
       ▼                  ▼                  ▼                  ▼
┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
│ Notification│   │   Academy   │   │  Resource   │   │   Calendar  │
│  Service    │   │   Service   │   │  Service    │   │   Service   │
├─────────────┤   ├─────────────┤   ├─────────────┤   ├─────────────┤
│ Schedule    │   │ If linked   │   │ Finalize    │   │ Create      │
│ reminders:  │   │ course: auto│   │ allocation  │   │ calendar    │
│ -24h before │   │ enroll      │   │             │   │ event       │
│ -1h before  │   │ student     │   │             │   │             │
└─────────────┘   └─────────────┘   └─────────────┘   └─────────────┘
       │                  │
       │                  │ If Education Card configured
       │                  ▼
       │          ┌─────────────┐
       │          │ Education   │
       │          │ Card Service│
       │          ├─────────────┤
       │          │ Auto-assign │
       │          │ card to     │
       │          │ customer    │
       │          └─────────────┘
       │
       ▼
┌────────────────────────────────────────────────────────────┐
│  Step 4: PAYMENT                                           │
│  - Customer pays via Stripe/PayPal/Invoice                 │
│  - Webhook receives payment confirmation                   │
└──────┬─────────────────────────────────────────────────────┘
       │
       │ Event: BOOKING_PAID
       ├──────────────────┬──────────────────┐
       ▼                  ▼                  ▼
┌─────────────┐   ┌─────────────┐   ┌─────────────┐
│   Finance   │   │ Notification│   │   Academy   │
│   Service   │   │  Service    │   │   Service   │
├─────────────┤   ├─────────────┤   ├─────────────┤
│ Mark invoice│   │ Send receipt│   │ If online   │
│ as PAID     │   │ email       │   │ course:     │
│             │   │             │   │ send access │
└─────────────┘   └─────────────┘   └─────────────┘
       │
       ▼
┌────────────────────────────────────────────────────────────┐
│  Step 5: SESSION DATE                                      │
│  - Reminders sent automatically                            │
│  - Employee notified                                       │
└──────┬─────────────────────────────────────────────────────┘
       │
       │ After session ends
       ▼
┌────────────────────────────────────────────────────────────┐
│  Step 6: COMPLETE BOOKING                                  │
│  Status: COMPLETED                                         │
└──────┬─────────────────────────────────────────────────────┘
       │
       │ Event: BOOKING_COMPLETED
       ├──────────────────┬──────────────────┐
       ▼                  ▼                  ▼
┌─────────────┐   ┌─────────────┐   ┌─────────────┐
│   Academy   │   │  Resource   │   │ Notification│
│   Service   │   │  Service    │   │  Service    │
├─────────────┤   ├─────────────┤   ├─────────────┤
│ Mark lesson │   │ Release     │   │ Request     │
│ complete    │   │ resources   │   │ review      │
│ Check cert. │   │             │   │             │
│ eligibility │   │             │   │             │
└─────────────┘   └─────────────┘   └─────────────┘
```

---

## 3. Datenmodell: Entity Relationship Diagram

```
┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
│  Organization   │────────<│      User       │>───────│      Role       │
│                 │  1:N    │                 │  N:1   │                 │
│ - id            │         │ - id            │        │ - id            │
│ - name          │         │ - email         │        │ - name          │
│ - license_id ───┼───┐     │ - role_id       │        │ - permissions   │
└─────────────────┘   │     └─────────────────┘        └─────────────────┘
                      │              │
                      │              │ 1:N
                      │              ▼
                      │     ┌─────────────────┐
                      │     │    Employee     │
                      │     │                 │
                      │     │ - id            │
                      │     │ - user_id       │
                      │     │ - skills[]      │
                      │     └────────┬────────┘
                      │              │
                      │              │ N:M (assigned to)
                      │              ▼
┌─────────────────┐   │     ┌─────────────────┐
│    License      │   │     │    Booking      │
│                 │<──┘     │                 │
│ - id            │         │ - id            │
│ - tier          │         │ - booking_no    │
│ - enabled_mods  │         │ - customer_id ──┼───┐
│ - limits        │         │ - service_id ───┼───┼───┐
└─────────────────┘         │ - session_id ───┼───┼───┼───┐
                            │ - employee_id ──┼───┼───┼───┼───┐
                            │ - invoice_id ───┼───┼───┼───┼───┼───┐
                            │ - status        │   │   │   │   │   │
                            │ - payment_stat  │   │   │   │   │   │
                            │ - total_price   │   │   │   │   │   │
                            │ - form_resp[]   │   │   │   │   │   │
                            └─────────────────┘   │   │   │   │   │
                                                  │   │   │   │   │
┌─────────────────┐                               │   │   │   │   │
│    Customer     │<──────────────────────────────┘   │   │   │   │
│                 │  N:1                               │   │   │   │
│ - id            │                                    │   │   │   │
│ - first_name    │                                    │   │   │   │
│ - last_name     │                                    │   │   │   │
│ - email         │                                    │   │   │   │
│ - custom_flds[] │                                    │   │   │   │
└─────────────────┘                                    │   │   │   │
                                                       │   │   │   │
┌─────────────────┐         ┌─────────────────┐       │   │   │   │
│     Course      │────────<│  CourseSession  │<──────┘   │   │   │
│                 │  1:N    │                 │  N:1      │   │   │
│ - id            │         │ - id            │           │   │   │
│ - title         │         │ - course_id     │           │   │   │
│ - type          │         │ - date/time     │           │   │   │
│ - curriculum[]  │         │ - instructor_id │───────────┘   │   │
│ - certificate   │         │ - location_id ──┼───┐           │   │
└─────┬───────────┘         │ - capacity      │   │           │   │
      │                     └─────────────────┘   │           │   │
      │ N:M (enrolled)                            │           │   │
      ▼                                           │           │   │
┌─────────────────┐                               │           │   │
│   Enrollment    │                               │           │   │
│                 │                               │           │   │
│ - customer_id   │                               │           │   │
│ - course_id     │                               │           │   │
│ - progress[]    │                               │           │   │
│ - completed     │                               │           │   │
└─────────────────┘                               │           │   │
                                                  │           │   │
┌─────────────────┐                               │           │   │
│    Location     │<──────────────────────────────┘           │   │
│                 │  N:1                                      │   │
│ - id            │                                           │   │
│ - name          │                                           │   │
│ - address       │                                           │   │
│ - rooms[] ──────┼───┐                                       │   │
└─────────────────┘   │                                       │   │
                      │ 1:N                                   │   │
                      ▼                                       │   │
┌─────────────────┐                                           │   │
│      Room       │                                           │   │
│                 │                                           │   │
│ - id            │                                           │   │
│ - location_id   │                                           │   │
│ - capacity      │                                           │   │
│ - equipment[]   │                                           │   │
└─────────────────┘                                           │   │
                                                              │   │
┌─────────────────┐         ┌─────────────────┐              │   │
│  ServiceItem    │────────<│  ServiceExtra   │              │   │
│                 │  1:N    │                 │              │   │
│ - id            │         │ - id            │              │   │
│ - title         │         │ - service_id    │              │   │
│ - type          │         │ - name          │              │   │
│ - price         │         │ - price         │              │   │
│ - course_id ────┼─────────┼─────────────────┼──────────────┘   │
│ - form_tpl_id ──┼─────────┼─────────────────┼──────────────────┘
│ - pricing_rl_id │         └─────────────────┘
└─────┬───────────┘
      │
      │ N:1
      ▼
┌─────────────────┐
│ DynamicPricing  │
│      Rule       │
│                 │
│ - id            │
│ - type          │
│ - tiers[]       │
│ - conditions    │
└─────────────────┘
```

---

## 4. API-Endpunkte: Vollständige Übersicht

### 4.1 Authentication & Users

```http
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/auth/me
PUT    /api/auth/me
POST   /api/auth/forgot-password
POST   /api/auth/reset-password

GET    /api/users
POST   /api/users
GET    /api/users/:id
PUT    /api/users/:id
DELETE /api/users/:id
```

### 4.2 Customers

```http
GET    /api/customers
POST   /api/customers
GET    /api/customers/:id
PUT    /api/customers/:id
DELETE /api/customers/:id
GET    /api/customers/:id/bookings
GET    /api/customers/:id/invoices
GET    /api/customers/:id/courses
POST   /api/customers/import        # CSV import
GET    /api/customers/export        # CSV export
```

### 4.3 Courses & Academy

```http
GET    /api/courses
POST   /api/courses
GET    /api/courses/:id
PUT    /api/courses/:id
DELETE /api/courses/:id
POST   /api/courses/:id/publish
POST   /api/courses/:id/unpublish

GET    /api/courses/:id/sessions
POST   /api/courses/:id/sessions
PUT    /api/courses/:courseId/sessions/:sessionId
DELETE /api/courses/:courseId/sessions/:sessionId

GET    /api/courses/:id/enrollments
POST   /api/courses/:id/enroll      # Enroll a customer
DELETE /api/courses/:courseId/enrollments/:customerId

GET    /api/lessons
POST   /api/lessons
GET    /api/lessons/:id
PUT    /api/lessons/:id
DELETE /api/lessons/:id

GET    /api/education-cards
POST   /api/education-cards
GET    /api/education-cards/:id
PUT    /api/education-cards/:id
DELETE /api/education-cards/:id
POST   /api/education-cards/:id/assign/:customerId
```

### 4.4 Services & Offers

```http
GET    /api/services
POST   /api/services
GET    /api/services/:id
PUT    /api/services/:id
DELETE /api/services/:id
GET    /api/services/:id/availability  # Check slots
POST   /api/services/:id/book           # Public booking endpoint

GET    /api/bundles
POST   /api/bundles
GET    /api/bundles/:id
PUT    /api/bundles/:id
DELETE /api/bundles/:id

GET    /api/vouchers
POST   /api/vouchers
GET    /api/vouchers/:id
PUT    /api/vouchers/:id
DELETE /api/vouchers/:id
POST   /api/vouchers/validate          # Validate voucher code

GET    /api/categories
POST   /api/categories
PUT    /api/categories/:id
DELETE /api/categories/:id

GET    /api/extras
POST   /api/extras
PUT    /api/extras/:id
DELETE /api/extras/:id

GET    /api/pricing-rules
POST   /api/pricing-rules
GET    /api/pricing-rules/:id
PUT    /api/pricing-rules/:id
DELETE /api/pricing-rules/:id
POST   /api/pricing-rules/:id/calculate  # Test price calculation
```

### 4.5 Bookings

```http
GET    /api/bookings
POST   /api/bookings
GET    /api/bookings/:id
PUT    /api/bookings/:id
DELETE /api/bookings/:id
POST   /api/bookings/:id/confirm
POST   /api/bookings/:id/cancel
POST   /api/bookings/:id/reschedule
POST   /api/bookings/:id/complete
GET    /api/bookings/calendar          # Calendar view data
GET    /api/bookings/timeline          # Gantt chart data
```

### 4.6 Finance

```http
GET    /api/invoices
POST   /api/invoices
GET    /api/invoices/:id
PUT    /api/invoices/:id
DELETE /api/invoices/:id
POST   /api/invoices/:id/send
POST   /api/invoices/:id/mark-paid
POST   /api/invoices/:id/mark-overdue
GET    /api/invoices/:id/pdf
POST   /api/invoices/:id/reminder

GET    /api/payments
POST   /api/payments                   # Record manual payment
POST   /api/payments/webhook/stripe    # Stripe webhook
POST   /api/payments/webhook/paypal    # PayPal webhook

GET    /api/vat-rates
POST   /api/vat-rates
PUT    /api/vat-rates/:id

GET    /api/reports/revenue
GET    /api/reports/outstanding
GET    /api/reports/vat
```

### 4.7 Employees & HR

```http
GET    /api/employees
POST   /api/employees
GET    /api/employees/:id
PUT    /api/employees/:id
DELETE /api/employees/:id
GET    /api/employees/:id/schedule
GET    /api/employees/:id/availability

GET    /api/time-entries
POST   /api/time-entries
PUT    /api/time-entries/:id
DELETE /api/time-entries/:id
POST   /api/time-entries/:id/approve

GET    /api/shifts
POST   /api/shifts
PUT    /api/shifts/:id
DELETE /api/shifts/:id

GET    /api/absences
POST   /api/absences
PUT    /api/absences/:id
POST   /api/absences/:id/approve
POST   /api/absences/:id/reject
```

### 4.8 Resources

```http
GET    /api/locations
POST   /api/locations
GET    /api/locations/:id
PUT    /api/locations/:id
DELETE /api/locations/:id

GET    /api/rooms
POST   /api/rooms
GET    /api/rooms/:id
PUT    /api/rooms/:id
DELETE /api/rooms/:id
GET    /api/rooms/:id/availability

GET    /api/equipment
POST   /api/equipment
PUT    /api/equipment/:id
DELETE /api/equipment/:id
```

### 4.9 Settings & Configuration

```http
GET    /api/settings/company
PUT    /api/settings/company
GET    /api/settings/localization
PUT    /api/settings/localization

GET    /api/roles
POST   /api/roles
PUT    /api/roles/:id
DELETE /api/roles/:id

GET    /api/form-templates
POST   /api/form-templates
GET    /api/form-templates/:id
PUT    /api/form-templates/:id
DELETE /api/form-templates/:id
```

### 4.10 Notifications

```http
GET    /api/notifications
POST   /api/notifications/:id/read
POST   /api/notifications/read-all
DELETE /api/notifications/:id

GET    /api/email-templates
POST   /api/email-templates
PUT    /api/email-templates/:id
POST   /api/email-templates/:id/test   # Send test email
```

### 4.11 Licensing

```http
GET    /api/licenses/:organizationId
PUT    /api/licenses/:organizationId
POST   /api/licenses/upgrade
POST   /api/licenses/downgrade
GET    /api/licenses/usage             # Current usage stats
```

---

## 5. Code-Beispiele: Kritische Integrationen

### 5.1 Booking Creation mit voller Integration

```typescript
// backend/services/BookingService.ts
import { PrismaClient } from '@prisma/client';
import { EventEmitter } from 'events';
import { PricingEngine } from './PricingEngine';
import { EmployeeAssignmentService } from './EmployeeAssignmentService';
import { ResourceAllocationService } from './ResourceAllocationService';
import { InvoiceService } from './InvoiceService';
import { NotificationService } from './NotificationService';
import { CustomerService } from './CustomerService';

const prisma = new PrismaClient();
const eventBus = new EventEmitter();

interface CreateBookingDTO {
  customerId?: string;
  customerEmail?: string;
  serviceId: string;
  courseSessionId?: string;
  scheduledDate: string;
  scheduledTime: string;
  formResponses: any[];
  extras: { extraId: string; quantity: number }[];
}

export class BookingService {
  async createBooking(dto: CreateBookingDTO) {
    // Start transaction
    return await prisma.$transaction(async (tx) => {
      // 1. Get or create customer
      let customer;
      if (dto.customerId) {
        customer = await tx.customer.findUnique({
          where: { id: dto.customerId },
        });
      } else if (dto.customerEmail) {
        customer = await CustomerService.findOrCreate(dto.customerEmail, tx);
      } else {
        throw new Error('Customer ID or email required');
      }

      // 2. Get service
      const service = await tx.service.findUnique({
        where: { id: dto.serviceId },
        include: { pricingRule: true, formTemplate: true },
      });

      if (!service) throw new Error('Service not found');

      // 3. Check availability
      const availability = await this.checkAvailability(
        service.id,
        dto.scheduledDate,
        dto.scheduledTime,
        tx
      );

      if (!availability.available) {
        throw new Error(`Not available: ${availability.reason}`);
      }

      // 4. Calculate price (with dynamic pricing)
      const pricingResult = await PricingEngine.calculate(
        service,
        new Date(dto.scheduledDate),
        dto.scheduledTime,
        availability.currentDemand
      );

      // 5. Calculate extras
      const extrasTotal = dto.extras.reduce((sum, extra) => {
        const extraItem = service.extras.find((e) => e.id === extra.extraId);
        return sum + (extraItem?.price || 0) * extra.quantity;
      }, 0);

      const totalPrice = pricingResult.finalPrice + extrasTotal;

      // 6. Assign employee (smart matching)
      const assignedEmployee = await EmployeeAssignmentService.findBestMatch({
        serviceId: service.id,
        date: dto.scheduledDate,
        time: dto.scheduledTime,
        requiredSkills: service.requiredSkills,
        tx,
      });

      // 7. Reserve resources
      const resourceAllocation = await ResourceAllocationService.reserve({
        serviceId: service.id,
        date: dto.scheduledDate,
        time: dto.scheduledTime,
        duration: service.duration,
        capacity: service.capacity,
        tx,
      });

      // 8. Generate booking number
      const bookingNumber = await this.generateBookingNumber(tx);

      // 9. Create booking
      const booking = await tx.booking.create({
        data: {
          bookingNumber,
          customerId: customer.id,
          serviceId: service.id,
          courseSessionId: dto.courseSessionId,
          scheduledDate: dto.scheduledDate,
          scheduledTime: dto.scheduledTime,
          status: 'PENDING',
          paymentStatus: 'UNPAID',
          totalPrice,
          basePrice: service.price,
          appliedPricing: pricingResult,
          extras: dto.extras,
          formResponses: dto.formResponses,
          assignedEmployeeId: assignedEmployee?.id,
          resourceAllocation: resourceAllocation,
        },
      });

      // 10. Create invoice draft
      const invoice = await InvoiceService.createFromBooking(booking, tx);

      // 11. Update booking with invoice
      await tx.booking.update({
        where: { id: booking.id },
        data: { invoiceId: invoice.id },
      });

      // 12. Emit event
      eventBus.emit('BOOKING_CREATED', {
        booking,
        customer,
        service,
        employee: assignedEmployee,
      });

      return { booking, invoice };
    });
  }

  private async checkAvailability(
    serviceId: string,
    date: string,
    time: string,
    tx: any
  ) {
    const service = await tx.service.findUnique({ where: { id: serviceId } });

    // Check minimum notice
    const now = new Date();
    const scheduledDateTime = new Date(`${date}T${time}`);
    const hoursUntil = (scheduledDateTime.getTime() - now.getTime()) / (1000 * 60 * 60);

    if (service.minNoticeHours && hoursUntil < service.minNoticeHours) {
      return {
        available: false,
        reason: `Minimum notice: ${service.minNoticeHours} hours`,
      };
    }

    // Check capacity
    const bookingsAtTime = await tx.booking.count({
      where: {
        serviceId,
        scheduledDate: date,
        scheduledTime: time,
        status: { in: ['PENDING', 'CONFIRMED', 'PAID'] },
      },
    });

    if (service.capacity && bookingsAtTime >= service.capacity) {
      return { available: false, reason: 'Fully booked' };
    }

    // Calculate current demand (for dynamic pricing)
    const last24hBookings = await tx.booking.count({
      where: {
        serviceId,
        createdAt: { gte: new Date(Date.now() - 24 * 60 * 60 * 1000) },
      },
    });

    return {
      available: true,
      currentDemand: last24hBookings,
      remainingCapacity: service.capacity - bookingsAtTime,
    };
  }

  private async generateBookingNumber(tx: any): Promise<string> {
    const year = new Date().getFullYear();
    const lastBooking = await tx.booking.findFirst({
      where: {
        bookingNumber: { startsWith: `BK-${year}-` },
      },
      orderBy: { createdAt: 'desc' },
    });

    let nextNumber = 1;
    if (lastBooking) {
      const lastNumber = parseInt(lastBooking.bookingNumber.split('-')[2]);
      nextNumber = lastNumber + 1;
    }

    return `BK-${year}-${nextNumber.toString().padStart(6, '0')}`;
  }
}

// Event Handlers
eventBus.on('BOOKING_CREATED', async (payload) => {
  const { booking, customer, service, employee } = payload;

  // Send confirmation email
  await NotificationService.sendEmail({
    to: customer.email,
    template: 'booking-confirmation',
    data: { booking, service, employee },
    attachments: [
      {
        filename: `Invoice-${booking.invoiceId}.pdf`,
        path: await InvoiceService.generatePDF(booking.invoiceId),
      },
    ],
  });

  // Schedule reminders
  await NotificationService.scheduleReminder({
    bookingId: booking.id,
    type: '24h-before',
    scheduledFor: new Date(
      new Date(`${booking.scheduledDate}T${booking.scheduledTime}`).getTime() -
        24 * 60 * 60 * 1000
    ),
  });

  await NotificationService.scheduleReminder({
    bookingId: booking.id,
    type: '1h-before',
    scheduledFor: new Date(
      new Date(`${booking.scheduledDate}T${booking.scheduledTime}`).getTime() -
        60 * 60 * 1000
    ),
  });

  // If linked to course, auto-enroll
  if (service.linkedCourseId) {
    await CourseService.enrollStudent(service.linkedCourseId, customer.id);
  }

  // If education card configured, assign
  if (service.linkedEducationCardId) {
    await EducationCardService.assign(service.linkedEducationCardId, customer.id);
  }
});

eventBus.on('BOOKING_CONFIRMED', async (payload) => {
  // Implementation...
});

eventBus.on('BOOKING_PAID', async (payload) => {
  const { booking } = payload;

  // Mark invoice as paid
  await InvoiceService.markAsPaid(booking.invoiceId);

  // Send receipt
  await NotificationService.sendEmail({
    to: booking.customer.email,
    template: 'payment-receipt',
    data: { booking },
  });

  // If online course, send access
  if (booking.service.type === 'ONLINE_COURSE' && booking.service.linkedCourseId) {
    const courseAccess = await CourseService.grantAccess(
      booking.service.linkedCourseId,
      booking.customerId
    );

    await NotificationService.sendEmail({
      to: booking.customer.email,
      template: 'course-access',
      data: { courseAccess },
    });
  }
});

eventBus.on('BOOKING_COMPLETED', async (payload) => {
  // Mark session complete
  // Check certificate eligibility
  // Request review
});
```

### 5.2 Dynamic Pricing Engine

```typescript
// backend/services/PricingEngine.ts
import { Service, DynamicPricingRule } from '@prisma/client';

interface PricingResult {
  originalPrice: number;
  finalPrice: number;
  adjustments: PricingAdjustment[];
  appliedRule?: string;
}

interface PricingAdjustment {
  type: string;
  name: string;
  amount: number;
  percentage: number;
  reason: string;
}

export class PricingEngine {
  static async calculate(
    service: Service & { pricingRule?: DynamicPricingRule },
    bookingDate: Date,
    bookingTime: string,
    currentDemand: number
  ): Promise<PricingResult> {
    let price = service.price;
    const adjustments: PricingAdjustment[] = [];

    if (!service.pricingRule || service.dynamicPricing === 'Off') {
      return {
        originalPrice: price,
        finalPrice: price,
        adjustments: [],
      };
    }

    const rule = service.pricingRule;

    switch (rule.type) {
      case 'EarlyBird':
        const adjustment = this.applyEarlyBird(price, rule, bookingDate);
        if (adjustment) {
          price = adjustment.newPrice;
          adjustments.push(adjustment.adjustment);
        }
        break;

      case 'LastMinute':
        const lmAdjustment = this.applyLastMinute(price, rule, bookingDate);
        if (lmAdjustment) {
          price = lmAdjustment.newPrice;
          adjustments.push(lmAdjustment.adjustment);
        }
        break;

      case 'Season':
        const seasonAdjustment = this.applySeasonal(price, rule, bookingDate);
        if (seasonAdjustment) {
          price = seasonAdjustment.newPrice;
          adjustments.push(seasonAdjustment.adjustment);
        }
        break;

      case 'Demand':
        const demandAdjustment = this.applyDemand(price, rule, currentDemand);
        if (demandAdjustment) {
          price = demandAdjustment.newPrice;
          adjustments.push(demandAdjustment.adjustment);
        }
        break;

      case 'History':
        // AI/ML-based prediction - placeholder
        break;
    }

    // Apply safety limits
    const maxIncrease = service.price * (rule.maxIncreasePercent / 100);
    const maxDecrease = service.price * (rule.maxDecreasePercent / 100);

    price = Math.min(price, service.price + maxIncrease);
    price = Math.max(price, service.price - maxDecrease);

    // Apply rounding
    if (rule.roundingMode !== 'None') {
      price = this.roundPrice(price, rule.roundingMode);
    }

    return {
      originalPrice: service.price,
      finalPrice: price,
      adjustments,
      appliedRule: rule.name,
    };
  }

  private static applyEarlyBird(
    basePrice: number,
    rule: DynamicPricingRule,
    bookingDate: Date
  ) {
    const now = new Date();
    const daysUntil = Math.floor(
      (bookingDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
    );

    const tier = rule.earlyBirdTiers
      ?.sort((a, b) => b.daysBeforeEvent - a.daysBeforeEvent)
      .find((t) => daysUntil >= t.daysBeforeEvent);

    if (!tier) return null;

    const discount = basePrice * (tier.discountPercent / 100);
    const newPrice = basePrice - discount;

    return {
      newPrice,
      adjustment: {
        type: 'EarlyBird',
        name: `Early Bird (${tier.daysBeforeEvent}+ days)`,
        amount: -discount,
        percentage: -tier.discountPercent,
        reason: `Booked ${daysUntil} days in advance`,
      },
    };
  }

  private static applyLastMinute(
    basePrice: number,
    rule: DynamicPricingRule,
    bookingDate: Date
  ) {
    const now = new Date();
    const hoursUntil = (bookingDate.getTime() - now.getTime()) / (1000 * 60 * 60);

    const tier = rule.lastMinuteTiers
      ?.sort((a, b) => a.hoursBeforeEvent - b.hoursBeforeEvent)
      .find((t) => hoursUntil <= t.hoursBeforeEvent);

    if (!tier) return null;

    const surcharge = basePrice * (tier.surchargePercent / 100);
    const newPrice = basePrice + surcharge;

    return {
      newPrice,
      adjustment: {
        type: 'LastMinute',
        name: `Last Minute (<${tier.hoursBeforeEvent}h)`,
        amount: surcharge,
        percentage: tier.surchargePercent,
        reason: `Booked ${Math.floor(hoursUntil)}h before event`,
      },
    };
  }

  private static applySeasonal(
    basePrice: number,
    rule: DynamicPricingRule,
    bookingDate: Date
  ) {
    const matchedRule = rule.seasonalRules?.find((seasonRule) => {
      // Check date ranges
      if (seasonRule.dateRanges) {
        for (const range of seasonRule.dateRanges) {
          const start = new Date(range.startDate);
          const end = new Date(range.endDate);
          if (bookingDate >= start && bookingDate <= end) {
            return true;
          }
        }
      }

      // Check specific dates
      if (seasonRule.specificDates) {
        const dateStr = bookingDate.toISOString().split('T')[0];
        if (seasonRule.specificDates.includes(dateStr)) {
          return true;
        }
      }

      // Check day of week
      if (seasonRule.dayOfWeek) {
        const day = bookingDate.getDay();
        if (seasonRule.dayOfWeek.includes(day)) {
          return true;
        }
      }

      return false;
    });

    if (!matchedRule) return null;

    const adjustment = basePrice * (matchedRule.adjustmentPercent / 100);
    const newPrice = basePrice + adjustment;

    return {
      newPrice,
      adjustment: {
        type: 'Seasonal',
        name: matchedRule.name,
        amount: adjustment,
        percentage: matchedRule.adjustmentPercent,
        reason: matchedRule.name,
      },
    };
  }

  private static applyDemand(
    basePrice: number,
    rule: DynamicPricingRule,
    currentDemand: number
  ) {
    const demandConfig = rule.demandConfig;
    if (!demandConfig) return null;

    const velocityThreshold = demandConfig.velocityThreshold;
    const lookbackHours = demandConfig.lookbackHours;

    // Calculate velocity (bookings per hour)
    const velocity = currentDemand / lookbackHours;

    if (velocity < velocityThreshold) return null;

    // Calculate surge multiplier based on aggressiveness
    let surgePercent = 0;
    switch (demandConfig.aggressiveness) {
      case 'Mild':
        surgePercent = demandConfig.priceIncreasePercent * 0.5;
        break;
      case 'Neutral':
        surgePercent = demandConfig.priceIncreasePercent;
        break;
      case 'Aggressive':
        surgePercent = demandConfig.priceIncreasePercent * 1.5;
        break;
    }

    const surge = basePrice * (surgePercent / 100);
    const newPrice = basePrice + surge;

    return {
      newPrice,
      adjustment: {
        type: 'Demand',
        name: 'High Demand Pricing',
        amount: surge,
        percentage: surgePercent,
        reason: `${currentDemand} bookings in last ${lookbackHours}h`,
      },
    };
  }

  private static roundPrice(price: number, mode: string): number {
    switch (mode) {
      case '.99':
        return Math.floor(price) + 0.99;
      case '.95':
        return Math.floor(price) + 0.95;
      case '.49':
        return Math.floor(price) + 0.49;
      case 'Nearest':
        return Math.round(price);
      default:
        return price;
    }
  }
}
```

### 5.3 Permission Guard Hook

```typescript
// frontend/hooks/usePermission.ts
import { useMemo } from 'react';
import { useApp } from '../context/AppContext';
import { useLicense } from '../context/LicenseContext';
import { ModuleName } from '../types';

export function usePermission(
  module: ModuleName,
  action: 'read' | 'write' | 'delete'
): {
  hasPermission: boolean;
  hasLicense: boolean;
  canAccess: boolean;
  reason?: string;
} {
  const { currentUser, roles } = useApp();
  const license = useLicense();

  return useMemo(() => {
    // Check if module is enabled in license
    const hasLicense = license.canAccessModule(module);
    if (!hasLicense) {
      return {
        hasPermission: false,
        hasLicense: false,
        canAccess: false,
        reason: 'Module not included in your license',
      };
    }

    // Check if user is logged in
    if (!currentUser) {
      return {
        hasPermission: false,
        hasLicense: true,
        canAccess: false,
        reason: 'Not authenticated',
      };
    }

    // Get user's role
    const userRole = roles.find((r) => r.id === currentUser.roleId);
    if (!userRole) {
      return {
        hasPermission: false,
        hasLicense: true,
        canAccess: false,
        reason: 'No role assigned',
      };
    }

    // Check role permission
    const hasPermission = userRole.permissions[module]?.[action] ?? false;

    return {
      hasPermission,
      hasLicense: true,
      canAccess: hasPermission,
    };
  }, [currentUser, roles, module, action, license]);
}

// Usage in components
function DeleteCustomerButton({ customerId }: { customerId: string }) {
  const { canAccess, reason } = usePermission('customers', 'delete');
  const { deleteCustomer } = useApp();

  if (!canAccess) {
    return (
      <Tooltip content={reason}>
        <button disabled className="btn-secondary opacity-50 cursor-not-allowed">
          <Trash2 size={16} />
          Delete
        </button>
      </Tooltip>
    );
  }

  return (
    <button
      onClick={() => {
        if (confirm('Delete customer?')) {
          deleteCustomer(customerId);
        }
      }}
      className="btn-danger"
    >
      <Trash2 size={16} />
      Delete
    </button>
  );
}
```

---

## 6. Deployment-Architektur

### 6.1 Production Setup

```
┌─────────────────────────────────────────────────────────────┐
│                      CLOUDFLARE CDN                          │
│  - DNS Management                                            │
│  - DDoS Protection                                           │
│  - SSL Termination                                           │
└──────────────────────┬──────────────────────────────────────┘
                       │
         ┌─────────────┴─────────────┐
         │                           │
    ┌────▼────┐                 ┌────▼────┐
    │ VERCEL  │                 │ RAILWAY │
    │Frontend │                 │ Backend │
    │         │                 │         │
    │ - React │                 │ - API   │
    │ - SSG   │                 │ - Jobs  │
    │ - Edge  │                 │ - Queue │
    └─────────┘                 └────┬────┘
                                     │
                     ┌───────────────┼───────────────┐
                     │               │               │
                ┌────▼────┐     ┌────▼────┐    ┌────▼────┐
                │PostgreSQL│     │  Redis  │    │   S3    │
                │ (Hosted) │     │ (Cache) │    │(Storage)│
                └──────────┘     └─────────┘    └─────────┘
```

### 6.2 Environment Variables

```bash
# Frontend (.env)
VITE_API_URL=https://api.bookando.ch
VITE_STRIPE_PUBLIC_KEY=pk_live_...
VITE_SENTRY_DSN=https://...

# Backend (.env)
DATABASE_URL=postgresql://user:pass@host:5432/bookando
REDIS_URL=redis://host:6379
JWT_SECRET=your-secret-key
JWT_EXPIRY=15m
REFRESH_TOKEN_EXPIRY=7d

STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

PAYPAL_CLIENT_ID=...
PAYPAL_SECRET=...

SENDGRID_API_KEY=SG....
TWILIO_ACCOUNT_SID=AC...
TWILIO_AUTH_TOKEN=...
TWILIO_PHONE_NUMBER=+41...

AWS_S3_BUCKET=bookando-files
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...

SENTRY_DSN=https://...
```

---

## 7. Zusammenfassung

Dieses Dokument beschreibt die vollständige Architektur für Bookando. Die Hauptkomponenten sind:

1. **Event-Driven Architecture** für Modul-Verknüpfungen
2. **Comprehensive API Layer** mit 100+ Endpoints
3. **Dynamic Pricing Engine** mit 5 Strategien
4. **Permission System** mit Role-Based Access Control
5. **License Management** mit Tier-basiertem Feature-Toggle
6. **Multi-Tenant Setup** mit Organization-Isolation

**Nächste Schritte:** Backend gemäß IMPLEMENTATION_ROADMAP.md aufbauen.
