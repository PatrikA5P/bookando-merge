# @bookando/api-client

Unified, type-safe API client for the Bookando platform.

## 📦 Installation

```bash
npm install @bookando/api-client @bookando/types
```

## 🎯 Features

- ✅ **Type-Safe** - Full TypeScript support with type inference
- ✅ **Multi-Platform** - Works in WordPress, SaaS, and Mobile apps
- ✅ **Multi-Tenant** - Automatic tenant_id injection
- ✅ **Retry Logic** - Configurable retry with exponential backoff
- ✅ **Authentication** - Supports WordPress nonce, JWT tokens, and API keys
- ✅ **Vue 3 Composables** - First-class Vue 3 support

## 🚀 Usage

### WordPress Plugin (Vue 3)

```typescript
import { createApp } from 'vue';
import { provideWordPressClient, useApiClient } from '@bookando/api-client/composables';

const app = createApp({
  setup() {
    // Provide client to all child components
    provideWordPressClient(); // Auto-uses window.BOOKANDO_VARS
  }
});

// In child components
const { customers, employees } = useApiClient();

// Type-safe API calls
const customerList = await customers.list({ page: 1, perPage: 20 });
const customer = await customers.get('123');
const newCustomer = await customers.create({
  firstName: 'John',
  lastName: 'Doe',
  email: 'john@example.com',
  phone: '+1234567890',
  status: 'Active'
});
```

### SaaS App (Vue 3)

```typescript
import { provideApiClient } from '@bookando/api-client/composables';

provideApiClient({
  baseUrl: 'https://api.bookando.com/v1',
  auth: {
    token: 'your-jwt-token',
  },
  tenantId: 42,
});
```

### Standalone (any framework)

```typescript
import { createBookandoClient } from '@bookando/api-client';

const api = createBookandoClient({
  baseUrl: 'https://api.bookando.com/v1',
  auth: {
    token: 'your-jwt-token',
  },
  tenantId: 42,
});

// Use the API
const customers = await api.customers.list();
const employee = await api.employees.get('456');
```

### React / React Native

```tsx
import { createBookandoClient } from '@bookando/api-client';
import { useState, useEffect } from 'react';

const api = createBookandoClient({
  baseUrl: 'https://api.bookando.com/v1',
  auth: { token: 'your-jwt-token' },
  tenantId: 1,
});

function CustomerList() {
  const [customers, setCustomers] = useState([]);

  useEffect(() => {
    api.customers.list().then(response => {
      setCustomers(response.data);
    });
  }, []);

  return <div>...</div>;
}
```

## 📚 API Reference

### Customers

```typescript
api.customers.list(params?: CustomersListParams)
api.customers.get(id: string)
api.customers.create(data: Omit<Customer, 'id' | 'tenantId' | ...>)
api.customers.update(id: string, data: Partial<Customer>)
api.customers.delete(id: string)
api.customers.block(id: string)
api.customers.activate(id: string)
api.customers.export(params?: CustomersListParams)
```

### Employees

```typescript
api.employees.list(params?: EmployeesListParams)
api.employees.get(id: string)
api.employees.create(data: Omit<Employee, 'id' | 'tenantId' | ...>)
api.employees.update(id: string, data: Partial<Employee>)
api.employees.delete(id: string)
api.employees.listCalendars(id: string)
api.employees.startOauth(id: string, provider, mode)
api.employees.connectIcs(id: string, url, name?)
```

### Appointments

```typescript
api.appointments.list(params?: AppointmentsListParams)
api.appointments.get(id: string)
api.appointments.create(data: Omit<Appointment, 'id' | 'tenantId' | ...>)
api.appointments.update(id: string, data: Partial<Appointment>)
api.appointments.delete(id: string)
api.appointments.cancel(id: string, reason?)
api.appointments.confirm(id: string)
api.appointments.complete(id: string)
api.appointments.reschedule(id: string, newDate, newStartTime)
```

## 🔧 Configuration

```typescript
interface ApiClientConfig {
  baseUrl: string;                    // API base URL
  auth?: {
    token?: string;                   // JWT token
    nonce?: string;                   // WordPress nonce
    apiKey?: string;                  // API key
  };
  tenantId?: number;                  // Auto-inject tenant_id
  defaultTimeout?: number;            // Request timeout (default: 10000ms)
  defaultRetries?: number;            // Retry count (default: 0)
  onError?: (error: BookandoApiError) => void; // Global error handler
}
```

## 🔄 Error Handling

```typescript
import { BookandoApiError } from '@bookando/api-client';

try {
  await api.customers.get('123');
} catch (error) {
  if (error instanceof BookandoApiError) {
    console.error('Status:', error.status);
    console.error('Kind:', error.kind); // 'http' | 'timeout' | 'network'
    console.error('Body:', error.body);
  }
}
```

## 📄 License

Proprietary - Bookando Team
