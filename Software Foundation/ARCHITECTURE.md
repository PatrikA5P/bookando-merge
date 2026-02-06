# Software Foundation — Architecture

## Vision

The **Software Foundation** is a host-agnostic platform kernel that powers multiple independent software products. Each product (e.g. a SaaS booking system, a CRM, an invoicing tool) is a standalone application built on top of this shared foundation, with its own repository, marketing, and release cycle.

The foundation itself never becomes a product — it is the engine that accelerates product development by providing battle-tested domain primitives, licensing, multi-tenancy, and a hexagonal ports-and-adapters architecture.

## Design Principles

### 1. SaaS/Cloud First
The primary runtime target is a cloud-hosted SaaS application. WordPress plugins and mobile apps are **optional channels** that may be added later as separate products on the same foundation.

### 2. Products Are Independent
Each software product is a self-contained unit:
- Own repository, own composer package
- Own deployment, own marketing
- Communicates with the foundation via well-defined contracts

The foundation does NOT prescribe how products are deployed, marketed, or sold.

### 3. Host-Agnostic Kernel
The kernel (`kernel/`) contains zero host-specific dependencies:
- No WordPress functions, no Laravel facades, no Symfony services
- No database driver imports, no HTTP framework coupling
- All I/O flows through **Ports** (interfaces) that adapters implement per host

### 4. Money Is Never a Float
All monetary amounts are stored as **integer minor units** (cents). The `Money` value object enforces this invariant at the type level. Currency-specific decimal places are handled by the `Currency` enum.

### 5. Tenant Is Never Optional
Every operation carries an explicit `TenantId`. There is no "default tenant", no fallback, no implicit context. A missing tenant is always an error.

### 6. Licensing Is a Kernel Concern
Feature gating, module access, usage quotas, and seat limits are all enforced at the kernel level via `Plan`, `License`, `LicenseGuard`, and the `LicenseResolverPort`. Products never bypass the license check.

## Architecture Overview

```
Software Foundation/
├── kernel/                          # The platform kernel
│   ├── src/
│   │   ├── Domain/                  # Value Objects, Entities, Domain Events
│   │   │   ├── Money/               # Money, Currency (integer minor units)
│   │   │   ├── Tenant/              # TenantId (always positive, never null)
│   │   │   ├── Time/                # TimeRange (UTC-native, DST-safe)
│   │   │   ├── Identity/            # UserId, Email, Permission, Role
│   │   │   ├── Licensing/           # Plan, License, LicenseStatus, UsageQuota
│   │   │   └── Shared/              # EntityId (UUID v4), DomainEvent
│   │   ├── Ports/                   # Infrastructure interfaces (16 ports)
│   │   ├── Application/             # SecurityContext, Guards, Command/Query
│   │   └── Contracts/               # ModuleManifest, ModuleServiceProvider
│   └── tests/
│       ├── Domain/                  # Value Object unit tests
│       ├── Application/             # Guard and SecurityContext tests
│       └── TestAdapters/            # InMemoryCache, FrozenClock, etc.
└── ARCHITECTURE.md                  # This file
```

## Layer Responsibilities

### Domain Layer (`src/Domain/`)
Pure business logic. No dependencies on infrastructure. Contains:
- **Value Objects**: Money, Currency, TenantId, EntityId, TimeRange, Email, UserId, Permission, Role
- **Licensing Model**: Plan (what a tier includes), License (tenant→plan binding), UsageQuota
- **Domain Events**: Base class with tenantId + schemaVersion for event-driven communication

### Ports Layer (`src/Ports/`)
16 interface definitions that the kernel depends on. Adapters are provided per host:

| Port | Purpose |
|------|---------|
| `PersistencePort` | Relational database abstraction |
| `CachePort` | Key-value cache (tenant-scoped) |
| `QueuePort` | Async job queue |
| `EventBusPort` | Domain event pub/sub |
| `ClockPort` | Time abstraction (testable) |
| `CryptoPort` | Encryption, hashing, token generation |
| `LoggerPort` | Structured logging + audit trail |
| `IdentityPort` | User resolution from external auth |
| `AuthorizationPort` | Permission checking |
| `SettingsPort` | Tenant-scoped configuration |
| `MailPort` | Email dispatch |
| `StoragePort` | File/blob storage |
| `HttpClientPort` | Outbound HTTP calls |
| `LicenseResolverPort` | License resolution + quota tracking |
| `PaymentGatewayPort` | Payment processing abstraction |

### Application Layer (`src/Application/`)
Orchestration and cross-cutting concerns:
- **SecurityContext**: Immutable request context (who, where, what permissions, which license)
- **LicenseGuard**: Enforces module access, feature availability, and quota consumption
- **IdempotencyGuard**: Prevents duplicate command execution via cache-backed dedup
- **Command/Query interfaces**: CQRS-lite separation with mandatory tenantId + idempotencyKey

### Contracts Layer (`src/Contracts/`)
Interfaces that modules must implement to plug into the platform:
- **ModuleManifest**: Declares slug, version, required permissions, events published/consumed
- **ModuleServiceProvider**: Registration hook for DI bindings and event subscriptions

## Key Decisions (ADR Summary)

### ADR-001: Integer Money
**Context**: Financial calculations with floats cause rounding errors.
**Decision**: All monetary values use integer minor units. `Money::fromDisplay()` is the only float entry point, used at system boundaries only.

### ADR-002: UUID v4 for Entity IDs
**Context**: Auto-increment IDs leak information and cause conflicts in distributed systems.
**Decision**: All entity identifiers use UUID v4 (`EntityId`). External system IDs (e.g. WordPress user IDs) are mapped via `IdentityPort`.

### ADR-003: Ports & Adapters
**Context**: The kernel must run in SaaS (Laravel/Symfony), WordPress, and potentially mobile runtimes.
**Decision**: All infrastructure access goes through port interfaces. Each host provides adapter implementations. The kernel never imports host-specific code.

### ADR-004: Licensing in the Kernel
**Context**: Feature gating must be consistent across all products and runtimes.
**Decision**: `Plan` defines what a tier includes. `License` binds a tenant to a plan. `LicenseGuard` enforces access at the application layer. `LicenseResolverPort` is the pluggable resolution strategy.

### ADR-005: Mandatory TenantId
**Context**: Multi-tenancy bugs (data leaks) are critical security vulnerabilities.
**Decision**: `TenantId` is a required value object that rejects zero, negative, and null values. Every command, query, and event carries it explicitly.

### ADR-006: Event-Driven Module Communication
**Context**: Modules must remain loosely coupled to enable independent deployment.
**Decision**: Modules communicate via `DomainEvent` objects published through `EventBusPort`. Direct module-to-module imports are forbidden.

### ADR-007: Idempotent Commands
**Context**: Network retries and webhook replays can cause duplicate operations.
**Decision**: Every `Command` carries an `idempotencyKey`. The `IdempotencyGuard` deduplicates using a 24-hour cache window.

## License Resolution Modes

The `LicenseResolverPort` supports three resolution strategies:

1. **SaaS (Primary)**: License resolved from database/subscription management system
2. **WP Standalone**: License key verified via HTTP against the licensing server
3. **WP Connected**: License inherited from the SaaS tenant (data sync via outbox/inbox)

## Testing Strategy

- **Domain tests**: Pure unit tests with no infrastructure dependencies
- **Application tests**: Use `TestAdapters/` (InMemoryCache, FakeLicenseResolver, FrozenClock, NullLogger, InMemoryEventBus)
- **No mocking frameworks**: Fakes and in-memory implementations ensure tests verify real behavior
- **Test pyramid**: Heavily weighted towards fast domain/unit tests

## Next Steps

1. **Module Skeleton**: Create a reference module (e.g. `booking/`) demonstrating how to build on the kernel
2. **SaaS Host Adapters**: Implement ports for the chosen SaaS framework (Laravel/Symfony)
3. **Frontend Foundation**: Vue 3 + TypeScript + Pinia + Vite project structure
4. **CI Pipeline**: PHPUnit + PHPStan + php-cs-fixer in GitHub Actions
5. **License Server**: API for license validation, quota tracking, and subscription management
