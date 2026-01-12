# Bookando Monorepo Architecture

**Version:** 2.0.0
**Migration Date:** November 2025
**Architecture:** Monorepo with Shared Packages

---

## 📋 Overview

Bookando has been restructured as a **Monorepo** to support multiple platforms from a single codebase:
- **WordPress Plugin** (Vue 3)
- **SaaS Web App** (Vue 3 standalone)
- **Mobile App** (React Native / Flutter - future)

This architecture ensures **maximum code reuse**, **type safety**, and **consistent behavior** across all platforms.

---

## 🏗️ Project Structure

```
bookando/
├── packages/                          # Shared packages (platform-agnostic)
│   ├── types/                         # @bookando/types
│   │   ├── src/
│   │   │   ├── base.ts               # Base interfaces (TenantScoped, etc.)
│   │   │   ├── enums.ts              # All enumerations
│   │   │   ├── models.ts             # Core business models
│   │   │   ├── academy.ts            # Academy-related types
│   │   │   ├── offers.ts             # Offers-related types
│   │   │   └── index.ts              # Main export
│   │   ├── package.json
│   │   └── README.md
│   │
│   ├── api-client/                    # @bookando/api-client
│   │   ├── src/
│   │   │   ├── client.ts             # Core API client
│   │   │   ├── endpoints/            # Type-safe endpoints
│   │   │   │   ├── customers.ts
│   │   │   │   ├── employees.ts
│   │   │   │   ├── appointments.ts
│   │   │   │   └── index.ts
│   │   │   ├── composables.ts        # Vue 3 composables
│   │   │   └── index.ts              # Main export
│   │   ├── package.json
│   │   └── README.md
│   │
│   └── design-system/                 # @bookando/design-system (WIP)
│       ├── src/
│       │   ├── vue/                  # Vue 3 components (54+)
│       │   ├── styles/               # Shared SCSS
│       │   └── tokens/               # Design tokens
│       ├── package.json
│       └── README.md
│
├── apps/                              # Platform-specific applications
│   └── wordpress-plugin/             # (to be moved from src/)
│
├── src/                               # Current WordPress plugin code
│   ├── Core/                         # Core functionality
│   │   ├── Design/                   # 54+ Vue components (→ packages/design-system)
│   │   ├── Base/                     # Base classes
│   │   └── ...
│   └── modules/                      # Plugin modules
│
├── scripts/                           # Build scripts
├── tests/                             # Test suites
├── bookandoGoogleAI/                 # Google AI Studio app (to be migrated)
├── package.json                       # Root workspace config
└── README.md                          # Main documentation
```

---

## 📦 Shared Packages

### 1. `@bookando/types`

**Purpose:** Single source of truth for all TypeScript types

**Exports:**
- Base types (`BaseEntity`, `TenantScoped`, `Timestamped`)
- Enums (`CustomerStatus`, `EmployeeStatus`, `ModuleName`, etc.)
- Models (`Customer`, `Employee`, `Appointment`, `Invoice`, etc.)
- Academy types (`Course`, `Quiz`, `Badge`)
- Offers types (`ServiceItem`, `DynamicPricingRule`, `Voucher`)

**Usage:**
```typescript
import { Customer, CustomerStatus, BaseEntity } from '@bookando/types';
```

**Key Features:**
- ✅ All entities include `tenantId` for Multi-Tenant support
- ✅ Soft delete support with `deletedAt`
- ✅ Timestamps (`createdAt`, `updatedAt`)
- ✅ Type-safe API responses with `ListResponse<T>`

---

### 2. `@bookando/api-client`

**Purpose:** Unified, type-safe API client for all platforms

**Exports:**
- `BookandoApiClient` - Core client class
- `createBookandoClient()` - Factory function
- `createWordPressClient()` - WordPress-specific factory
- Type-safe endpoints (customers, employees, appointments, etc.)
- Vue 3 composables (`provideApiClient`, `useApiClient`)

**Usage:**

**WordPress Plugin:**
```typescript
import { provideWordPressClient, useApiClient } from '@bookando/api-client/composables';

// In Vue app setup
provideWordPressClient(); // Auto-uses window.BOOKANDO_VARS

// In components
const { customers } = useApiClient();
const list = await customers.list({ page: 1, perPage: 20 });
```

**SaaS App:**
```typescript
import { provideApiClient } from '@bookando/api-client/composables';

provideApiClient({
  baseUrl: 'https://api.bookando.com/v1',
  auth: { token: 'jwt-token' },
  tenantId: 42,
});
```

**Key Features:**
- ✅ Automatic `tenantId` injection
- ✅ Authentication (WordPress nonce, JWT, API keys)
- ✅ Retry logic with exponential backoff
- ✅ Type-safe endpoints with full IntelliSense
- ✅ Vue 3 composables for easy integration

---

### 3. `@bookando/design-system` (WIP)

**Purpose:** Shared UI components for all platforms

**Planned Exports:**
- 54+ Vue 3 components (AppButton, AppModal, AppCard, etc.)
- SCSS variables and mixins
- Design tokens (colors, spacing, typography)

**Status:** 🚧 Structure created, component migration pending

---

## 🔄 Migration Plan

### Phase 1: Shared Packages ✅ **COMPLETED**
- [x] Create monorepo structure
- [x] Setup npm workspaces
- [x] Create `@bookando/types` package
- [x] Create `@bookando/api-client` package
- [x] Create `@bookando/design-system` package (structure only)

### Phase 2: Google AI Studio Integration (Next)
- [ ] Migrate Google AI Studio types to `@bookando/types`
- [ ] Integrate API client in Google AI Studio modules
- [ ] Start migrating React components to Vue 3
- [ ] Use `@bookando/design-system` components

### Phase 3: WordPress Plugin Refactoring
- [ ] Refactor existing modules to use `@bookando/types`
- [ ] Replace `src/frontend/apiClient.ts` with `@bookando/api-client`
- [ ] Move modules to use shared API client

### Phase 4: Design System Migration
- [ ] Move 54+ components from `src/Core/Design` to `@bookando/design-system`
- [ ] Extract SCSS to shared styles
- [ ] Create design tokens
- [ ] Setup Storybook

### Phase 5: SaaS App
- [ ] Create standalone SaaS app in `apps/saas-web/`
- [ ] Use all shared packages
- [ ] Deploy independently from WordPress

---

## 🛠️ Development

### Install Dependencies
```bash
npm install
```

### Build All Packages
```bash
npm run build --workspaces
```

### Build Specific Package
```bash
npm run build --workspace=@bookando/types
npm run build --workspace=@bookando/api-client
```

### Watch Mode (Development)
```bash
npm run watch --workspace=@bookando/api-client
```

---

## 🎯 Benefits

| Aspect | Before | After (Monorepo) |
|--------|--------|------------------|
| **Code Duplication** | High - Each platform had own types | ✅ Zero - Single source of truth |
| **Type Safety** | Partial - Types not shared | ✅ 100% - Shared types everywhere |
| **API Consistency** | Varied - Different API clients | ✅ Unified - Same client, same behavior |
| **Maintenance** | Hard - Changes in multiple places | ✅ Easy - Update once, applies everywhere |
| **Developer Onboarding** | Complex - Learn each platform | ✅ Simple - Learn once, use everywhere |
| **Multi-Platform** | Manual sync required | ✅ Automatic - Shared packages |

---

## 📖 Documentation

- **[@bookando/types README](packages/types/README.md)** - Types documentation
- **[@bookando/api-client README](packages/api-client/README.md)** - API client documentation
- **[@bookando/design-system README](packages/design-system/README.md)** - Design system (WIP)
- **[BOOKANDO_ARCHITECTURE_GUIDE.md](BOOKANDO_ARCHITECTURE_GUIDE.md)** - Overall architecture

---

## 🤝 Contributing

When creating new features:
1. **Types first** - Add types to `@bookando/types`
2. **API second** - Add endpoints to `@bookando/api-client`
3. **UI third** - Use components from `@bookando/design-system`
4. **Module last** - Implement in platform-specific code

This ensures maximum reusability across all platforms.

---

## 📊 Package Dependencies

```
@bookando/types
    ↑
    |
@bookando/api-client
    ↑
    |
@bookando/design-system
    ↑
    |
WordPress Plugin / SaaS App / Mobile App
```

---

**Last Updated:** November 21, 2025
**Migration Status:** Phase 1 Complete ✅
**Next Steps:** Google AI Studio Integration
