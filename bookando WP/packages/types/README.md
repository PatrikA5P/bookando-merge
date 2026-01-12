# @bookando/types

Shared TypeScript types and interfaces for the Bookando platform.

## 📦 Installation

```bash
npm install @bookando/types
```

## 🎯 Purpose

This package provides a **single source of truth** for all TypeScript types used across:
- WordPress Plugin (Vue 3)
- SaaS Web App (Vue 3)
- Mobile App (React Native / Flutter)
- Backend API

## 🚀 Usage

### Import all types
```typescript
import { Customer, Employee, Appointment } from '@bookando/types';
```

### Import specific modules
```typescript
import { CustomerStatus, EmployeeStatus } from '@bookando/types/enums';
import { Customer, Employee } from '@bookando/types/models';
import { Course, Badge } from '@bookando/types/academy';
import { ServiceItem, DynamicPricingRule } from '@bookando/types/offers';
```

## 🏗️ Structure

- **base.ts** - Base interfaces (BaseEntity, TenantScoped, Timestamped)
- **enums.ts** - All enumerations and type unions
- **models.ts** - Core business models (Customer, Employee, Appointment, Invoice, etc.)
- **academy.ts** - Academy-related models (Course, Quiz, Lesson, Badge)
- **offers.ts** - Offer-related models (ServiceItem, Bundle, Voucher, Pricing)

## 🔑 Key Features

### Multi-Tenant Support
All entities extend `BaseEntity` which includes `tenantId`:

```typescript
interface BaseEntity extends TenantScoped, Timestamped {
  id: string;
  tenantId: number; // Multi-tenant isolation
  createdAt?: string;
  updatedAt?: string;
  deletedAt?: string | null; // Soft delete support
}
```

### Type-Safe API Responses
```typescript
import { ListResponse, Customer } from '@bookando/types';

const response: ListResponse<Customer> = {
  data: [...],
  meta: {
    total: 100,
    page: 1,
    perPage: 20,
    totalPages: 5
  }
};
```

## 📝 Example Usage

### WordPress Plugin (Vue 3)
```typescript
import { ref } from 'vue';
import type { Customer, CustomerStatus } from '@bookando/types';

const customers = ref<Customer[]>([]);
const newCustomer: Customer = {
  id: '123',
  tenantId: 1,
  firstName: 'John',
  lastName: 'Doe',
  email: 'john@example.com',
  phone: '+1234567890',
  status: 'Active' as CustomerStatus,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
};
```

### SaaS App (Vue 3)
```typescript
import { useApi } from '@bookando/api-client';
import type { Customer } from '@bookando/types';

const api = useApi();
const customers = await api.customers.list<Customer[]>();
```

### Mobile App (React Native)
```typescript
import { useState } from 'react';
import type { Customer } from '@bookando/types';

const [customers, setCustomers] = useState<Customer[]>([]);
```

## 🔄 Versioning

This package follows semantic versioning. Breaking changes will result in a major version bump.

## 📄 License

Proprietary - Bookando Team
