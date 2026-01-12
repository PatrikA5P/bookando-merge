# Bookando Backend API

Multi-tenant booking and course management system backend.

## 🚀 Quick Start

### 1. Install Dependencies
```bash
cd backend
npm install
```

### 2. Setup Database
```bash
# Create .env file
cp .env.example .env

# Edit .env and set DATABASE_URL
# DATABASE_URL="postgresql://postgres:password@localhost:5432/bookando"

# Run migrations
npm run prisma:migrate

# (Optional) Seed database
npm run prisma:seed
```

### 3. Start Development Server
```bash
npm run dev
```

Server runs on `http://localhost:3001`

## 📋 Available Scripts

| Script | Description |
|--------|-------------|
| `npm run dev` | Start development server with auto-reload |
| `npm run build` | Build for production |
| `npm start` | Start production server |
| `npm run prisma:generate` | Generate Prisma Client |
| `npm run prisma:migrate` | Run database migrations |
| `npm run prisma:studio` | Open Prisma Studio (DB GUI) |
| `npm run prisma:seed` | Seed database with test data |
| `npm test` | Run tests |

## 🏗️ Project Structure

```
backend/
├── src/
│   ├── routes/          # API route handlers
│   ├── services/        # Business logic services
│   ├── middleware/      # Express middleware (auth, etc.)
│   ├── utils/           # Utility functions
│   ├── jobs/            # Background jobs (BullMQ)
│   └── server.ts        # Express server entry point
├── prisma/
│   ├── schema.prisma    # Database schema
│   ├── migrations/      # Database migrations
│   └── seed.ts          # Seed script
├── tests/               # Unit & integration tests
└── dist/                # Compiled TypeScript (production)
```

## 🔑 Environment Variables

See `.env.example` for all available environment variables.

**Required:**
- `DATABASE_URL` - PostgreSQL connection string
- `JWT_SECRET` - Secret key for JWT signing (min 32 chars)

**Optional:**
- `REDIS_URL` - Redis for caching & queues
- `STRIPE_SECRET_KEY` - Stripe payment processing
- `SENDGRID_API_KEY` - Email sending via SendGrid

## 📡 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `POST /api/auth/logout` - Logout user
- `POST /api/auth/refresh` - Refresh access token
- `GET /api/auth/me` - Get current user

### Customers
- `GET /api/customers` - List all customers
- `POST /api/customers` - Create customer
- `GET /api/customers/:id` - Get customer by ID
- `PUT /api/customers/:id` - Update customer
- `DELETE /api/customers/:id` - Delete customer

### Courses
- `GET /api/courses` - List all courses
- `POST /api/courses` - Create course
- `GET /api/courses/:id` - Get course by ID
- `PUT /api/courses/:id` - Update course
- `DELETE /api/courses/:id` - Delete course
- `GET /api/courses/:id/sessions` - Get course sessions
- `POST /api/courses/:id/sessions` - Create session

### Services
- `GET /api/services` - List all services
- `POST /api/services` - Create service
- `GET /api/services/:id` - Get service by ID
- `PUT /api/services/:id` - Update service
- `DELETE /api/services/:id` - Delete service

### Bookings
- `GET /api/bookings` - List all bookings
- `POST /api/bookings` - Create booking
- `GET /api/bookings/:id` - Get booking by ID
- `PUT /api/bookings/:id` - Update booking
- `POST /api/bookings/:id/confirm` - Confirm booking
- `POST /api/bookings/:id/cancel` - Cancel booking

Full API documentation available at `/api/docs` (Swagger UI)

## 🔒 Authentication

The API uses JWT (JSON Web Tokens) for authentication.

**Request Header:**
```
Authorization: Bearer <your-jwt-token>
```

**Token Lifetime:**
- Access Token: 15 minutes
- Refresh Token: 7 days

## 🏢 Multi-Tenancy

All API requests are scoped to an organization. The organization ID is extracted from:
1. JWT token (for authenticated requests)
2. Custom subdomain (e.g., `fahrschule-mueller.bookando.ch`)
3. API key header (for WordPress integration)

## 📦 Database Schema

See `prisma/schema.prisma` for complete database schema.

**Core Models:**
- `Organization` - Multi-tenant organization
- `User` - System users
- `Customer` - End customers
- `Employee` - Staff members
- `Course` - Academy courses
- `CourseSession` - Physical course sessions
- `Service` - Bookable services/offers
- `Booking` - Customer bookings
- `Invoice` - Financial invoices
- `License` - Organization licensing

## 🧪 Testing

```bash
# Run all tests
npm test

# Run with UI
npm run test:ui

# Run specific test file
npm test auth.test.ts
```

## 🚢 Deployment

### Production Build
```bash
npm run build
npm start
```

### Docker (Optional)
```bash
docker build -t bookando-backend .
docker run -p 3001:3001 bookando-backend
```

### Environment
- Node.js >= 18.0.0
- PostgreSQL >= 14
- Redis >= 6 (optional, for caching & queues)

## 📚 Additional Documentation

- [Multi-Platform Architecture](../MULTI_PLATFORM_ARCHITECTURE.md)
- [Implementation Roadmap](../IMPLEMENTATION_ROADMAP.md)
- [Phase 1 Implementation](../PHASE_1_IMPLEMENTATION.md)

## 🐛 Troubleshooting

**Database connection fails:**
```bash
# Check PostgreSQL is running
pg_isready

# Test connection
psql $DATABASE_URL
```

**Prisma errors:**
```bash
# Regenerate Prisma Client
npm run prisma:generate

# Reset database (WARNING: deletes all data)
npx prisma migrate reset
```

## 📝 License

MIT License - see LICENSE file for details

## 🤝 Contributing

1. Create feature branch (`git checkout -b feature/my-feature`)
2. Commit changes (`git commit -m 'Add my feature'`)
3. Push to branch (`git push origin feature/my-feature`)
4. Open Pull Request

---

**Questions?** Open an issue or contact the team.
