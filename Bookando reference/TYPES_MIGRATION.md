# Types Migration Guide

Migration von `types.ts` (Frontend) zu `types-api.ts` (Backend-synchronisiert).

## 🎯 Ziel

Frontend-Types mit Backend Prisma Schema synchronisieren, um spätere Refactoring-Arbeit zu vermeiden.

## 📋 Status

- ✅ `types-api.ts` erstellt (synchronisiert mit Prisma Schema)
- ⏳ AppContext Migration auf neue Types (ausstehend)
- ⏳ Komponenten Migration (ausstehend)

## 🔄 Hauptunterschiede

### Enums

**Vorher (types.ts):**
```typescript
export enum CustomerStatus {
  ACTIVE = 'Active',
  BLOCKED = 'Blocked',
  DELETED = 'Deleted'
}
```

**Nachher (types-api.ts):**
```typescript
export enum CustomerStatus {
  ACTIVE = 'ACTIVE',      // Uppercase, wie in Prisma
  INACTIVE = 'INACTIVE',  // Statt BLOCKED
  ARCHIVED = 'ARCHIVED'   // Statt DELETED
}
```

### Interfaces

**Vorher (types.ts):**
```typescript
export interface Customer {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  status: CustomerStatus;
  street?: string;        // ❌ In Prisma: "address"
  zip?: string;
  city?: string;
  country?: string;
  customFields?: CustomField[];  // ❌ In Prisma: JSON
}
```

**Nachher (types-api.ts):**
```typescript
export interface Customer {
  id: string;
  organizationId: string;  // ✅ Multi-Tenant
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  address?: string;        // ✅ Statt "street"
  zip?: string;
  city?: string;
  country?: string;
  birthday?: string;
  gender?: string;
  customFields?: any;      // ✅ JSON type
  status: CustomerStatus;
  createdAt: Date | string;
  updatedAt: Date | string;
}
```

### Booking

**Vorher (types.ts):**
```typescript
export interface Booking {
  id: string;
  serviceName: string;    // ❌ Kein serviceId
  date: string;
  time: string;
  status: 'Confirmed' | 'Pending' | ...;
  price: number;
}
```

**Nachher (types-api.ts):**
```typescript
export interface Booking {
  id: string;
  bookingNumber: string;  // ✅ Eindeutige Nummer
  organizationId: string;
  customerId: string;
  serviceId: string;      // ✅ Relation
  sessionId?: string;     // ✅ Für Kurse
  scheduledDate: string;  // ✅ Statt "date"
  scheduledTime: string;  // ✅ Statt "time"
  basePrice: number;
  totalPrice: number;
  employeeId?: string;    // ✅ Zugewiesen
  status: BookingStatus;  // ✅ Enum
  paymentStatus: PaymentStatus;
  invoiceId?: string;     // ✅ Verknüpft
  createdAt: Date | string;
  confirmedAt?: Date | string;
  paidAt?: Date | string;
  completedAt?: Date | string;
}
```

## 📝 Migrations-Strategie

### Phase 1: Parallel Betrieb (AKTUELL)

Beide Type-Dateien existieren parallel:
- `types.ts` - Frontend (alt, funktioniert noch)
- `types-api.ts` - Backend (neu, Prisma-synchronisiert)

**Kein Breaking Change** - Frontend funktioniert weiterhin.

### Phase 2: Schrittweise Migration (SPÄTER)

1. **API Client erstellen** (nutzt types-api.ts)
   ```typescript
   // services/api/client.ts
   import { Customer, Booking } from '../types-api';
   ```

2. **AppContext erweitern**
   ```typescript
   // Neue State-Variablen mit neuen Types
   const [customersAPI, setCustomersAPI] = useState<Customer[]>([]);
   // Alte bleiben für Kompatibilität
   const [customers, setCustomers] = useState<OldCustomer[]>([]);
   ```

3. **Komponenten migrieren**
   ```typescript
   // Vorher
   import { Customer } from '../types';

   // Nachher
   import { Customer } from '../types-api';
   ```

4. **types.ts deprecaten**
   ```typescript
   /** @deprecated Use types-api.ts instead */
   export interface Customer { ... }
   ```

### Phase 3: Backend Integration (NACH DB-SETUP)

Wenn Backend läuft:
1. API Client implementieren
2. Frontend calls Backend
3. types-api.ts wird zur Single Source of Truth
4. types.ts löschen

## 🛠️ Mapping Utilities (Später nötig)

```typescript
// utils/typeMappers.ts
import { Customer as OldCustomer } from './types';
import { Customer as NewCustomer } from './types-api';

export function mapOldToNewCustomer(old: OldCustomer): NewCustomer {
  return {
    id: old.id,
    organizationId: 'org_default', // Aus Context
    firstName: old.firstName,
    lastName: old.lastName,
    email: old.email,
    phone: old.phone,
    address: old.street,  // Mapping!
    zip: old.zip,
    city: old.city,
    country: old.country,
    birthday: old.birthday,
    gender: old.gender,
    customFields: old.customFields,
    status: mapCustomerStatus(old.status),
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  };
}

function mapCustomerStatus(old: OldCustomerStatus): CustomerStatus {
  switch (old) {
    case 'Active': return CustomerStatus.ACTIVE;
    case 'Blocked': return CustomerStatus.INACTIVE;
    case 'Deleted': return CustomerStatus.ARCHIVED;
  }
}
```

## ✅ Vorteile der neuen Types

1. **Type-Safety** - Exakt wie Backend
2. **Keine Refactoring** - Wenn Backend kommt
3. **Multi-Tenant** - organizationId überall
4. **Vollständig** - Alle Felder aus Prisma
5. **Zukunftssicher** - Prisma als Source of Truth

## ⚠️ Breaking Changes (später)

Wenn wir migrieren:
- CustomerStatus Werte ändern sich
- Einige Feldnamen ändern sich
- Neue Pflichtfelder (organizationId)

**Lösung:** Mapping-Utilities (siehe oben)

## 🎯 Nächste Schritte

1. ⏳ PostgreSQL lokal starten
2. ⏳ Backend server starten
3. ⏳ API Client implementieren
4. ⏳ AppContext auf API umstellen
5. ⏳ types.ts deprecaten
6. ⏳ types.ts löschen

## 📚 Related Docs

- `backend/prisma/schema.prisma` - Source of Truth
- `types-api.ts` - Generiert aus Prisma
- `types.ts` - Alt (wird deprecated)

---

**Stand:** 2026-01-11
**Status:** Phase 1 (Parallel Betrieb)
**Nächster Meilenstein:** Backend Integration
