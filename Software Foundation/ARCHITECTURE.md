# Software Foundation — Architecture

## Vision

The **Software Foundation** is a host-agnostic platform kernel that powers multiple independent software products — from booking systems and CRMs to full accounting suites (like Bexio) and HR platforms (like Personio) with integrated AI. Each product is a standalone application built on top of this shared foundation, with its own repository, marketing, and release cycle.

The foundation itself never becomes a product — it is the engine that accelerates product development by providing battle-tested domain primitives, licensing, multi-tenancy, compliance, and a hexagonal ports-and-adapters architecture.

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
All monetary amounts are stored as **integer minor units** (cents). The `Money` value object enforces this invariant at the type level. Currency-specific decimal places are handled by the `Currency` enum. Currency conversion is supported via `Money::convertTo()` with explicit exchange rates.

### 5. Tenant Is Never Optional
Every operation carries an explicit `TenantId`. There is no "default tenant", no fallback, no implicit context. A missing tenant is always an error.

### 6. Licensing Is a Kernel Concern
Feature gating, module access, usage quotas, seat limits, AI quotas, and storage limits are all enforced at the kernel level via `Plan`, `License`, `LicenseGuard`, and the `LicenseResolverPort`. Products never bypass the license check.

### 7. Swiss & EU Compliance by Design
The kernel natively supports the requirements of Swiss OR, GeBüV, MWSTG, DSG, EU DSGVO, GoBD, eIDAS, and ISO standards. This includes hash chains for ledger integrity, gap-free sequences for invoices, crypto-shredding for DSGVO Art. 17, and configurable retention policies.

### 8. Digital Sovereignty
The platform supports three hosting modes (`saas`, `self_hosted`, `hybrid`) with data residency policies that control where data is stored, which providers are allowed, and whether encryption at rest is required.

## Architecture Overview

```
Software Foundation/
├── kernel/                              # The platform kernel
│   ├── src/
│   │   ├── Domain/                      # Value Objects, Entities, Domain Events
│   │   │   ├── Money/                   # Money, Currency (integer minor units)
│   │   │   ├── Tenant/                  # TenantId (always positive, never null)
│   │   │   ├── Time/                    # TimeRange (UTC-native, DST-safe)
│   │   │   ├── Identity/               # UserId, Email, Permission, Role
│   │   │   ├── Licensing/              # Plan, License, LicenseStatus, UsageQuota
│   │   │   ├── Shared/                 # EntityId (UUID v4), DomainEvent
│   │   │   ├── Integrity/              # HashChain, IntegrityCheckResult
│   │   │   ├── Sequence/               # SequenceKey, SequenceGap
│   │   │   ├── Security/               # EncryptedField
│   │   │   ├── Compliance/             # RetentionPolicy, DeletionStrategy, DataResidency
│   │   │   ├── I18n/                   # Locale (BCP-47)
│   │   │   ├── Tax/                    # TaxRate, TaxCalculationResult
│   │   │   ├── Notification/           # NotificationChannel, Template, Preference
│   │   │   ├── Ai/                     # AiModelConfig, AiMessage, AiResponse
│   │   │   ├── Signature/              # SignatureLevel (SES/AES/QES), SignedDocument
│   │   │   ├── Currency/               # ExchangeRate
│   │   │   ├── RateLimit/              # RateLimit
│   │   │   ├── Webhook/                # WebhookSubscription, WebhookDelivery
│   │   │   ├── Storage/                # FileValidationResult, MalwareScanResult
│   │   │   ├── Dossier/                # Dossier, DossierEntry, AccessLog (GeBüV)
│   │   │   ├── TimeTracking/           # TimeEntry, WorkTimeValidation (ArG)
│   │   │   ├── Consent/                # Consent, ConsentPurpose (DSG/DSGVO)
│   │   │   └── Payroll/                # SalaryDeclaration, SwissdecDomain, WageType
│   │   ├── Ports/                       # Infrastructure interfaces (37 ports)
│   │   ├── Application/                 # SecurityContext, Guards, Command/Query
│   │   └── Contracts/                   # Module contracts + cross-cutting interfaces
│   └── tests/
│       ├── Domain/                      # Value Object unit tests (25 domains)
│       ├── Application/                 # Guard and SecurityContext tests
│       └── TestAdapters/                # 15 in-memory/fake adapters
├── docs/                                # Analysis and evaluation documents
└── ARCHITECTURE.md                      # This file
```

## Layer Responsibilities

### Domain Layer (`src/Domain/`)
Pure business logic. No dependencies on infrastructure. Contains:

**Core Value Objects:**
- **Money/**: Money, Currency (integer minor units, currency conversion)
- **Tenant/**: TenantId (always positive, never null)
- **Time/**: TimeRange (UTC-native, DST-safe)
- **Identity/**: UserId, Email, Permission, Role
- **Licensing/**: Plan (tiers with modules/features/quotas/seats/AI/storage), License, UsageQuota
- **Shared/**: EntityId (UUID v4), DomainEvent

**Phase 1 — Legal Compliance:**
- **Integrity/**: HashChain (SHA-256 tamper-evident chain for GeBüV/GoBD), IntegrityCheckResult
- **Sequence/**: SequenceKey (gap-free numbering for invoices/journals), SequenceGap
- **Security/**: EncryptedField (envelope encryption with key versioning)
- **Compliance/**: RetentionCategory, RetentionPolicy, DeletionStrategy (incl. crypto-shredding), DataResidencyPolicy, HostingMode

**Phase 2 — Core Functionality:**
- **I18n/**: Locale (BCP-47 with named constructors for CH/DE/FR/IT/EN)
- **Tax/**: TaxRate (with CH standard 8.1%, reduced 2.6%, accommodation 3.7%), TaxCalculationResult
- **Notification/**: NotificationChannel (email/SMS/push/in-app/webhook), NotificationTemplate, NotificationPreference
- **Ai/**: AiModelConfig (multi-provider: Gemini, OpenAI, Anthropic), AiMessage, AiResponse

**Phase 3 — Extensions:**
- **Signature/**: SignatureLevel (SES/AES/QES per eIDAS/ZertES), SignedDocument
- **Currency/**: ExchangeRate (with convert() and inverse())
- **RateLimit/**: RateLimit (per-minute/per-hour sliding windows)
- **Webhook/**: WebhookSubscription (HTTPS-only), WebhookDelivery (with retry logic)
- **Storage/**: FileValidationResult, MalwareScanResult

**Phase 4 — Dossier, Zeiterfassung, Consent, Payroll:**
- **Dossier/**: DossierType (9 types with CH retention periods), Dossier (revisionssicher), DossierEntry (SHA-256 integrity), DossierAccessLog/Action (GeBüV audit trail), DossierStatus
- **TimeTracking/**: TimeEntry (ArG Art. 46 compliant), TimeEntryType, WorkTimeValidation (ArG break/overtime rules)
- **Consent/**: Consent (DSG Art. 6 / DSGVO Art. 7), ConsentPurpose (photo storage, biometric, marketing, cross-border)
- **Payroll/**: SwissdecDomain (7 ELM domains), SocialInsuranceType (AHV/IV/EO/ALV/BVG/UVG/KTG/FAK), WageType (Swissdec XML mapping), SalaryDeclaration, SalaryDeclarationStatus

### Ports Layer (`src/Ports/`)
33 interface definitions that the kernel depends on. Adapters are provided per host:

| Port | Purpose | Compliance |
|------|---------|------------|
| `PersistencePort` | Relational database (with batch insert + pagination) | — |
| `CachePort` | Key-value cache (tenant-scoped) | — |
| `QueuePort` | Async job queue | — |
| `EventBusPort` | Domain event pub/sub | — |
| `ClockPort` | Time abstraction (testable) | — |
| `CryptoPort` | Encryption, hashing, token generation | — |
| `LoggerPort` | Structured logging + audit trail | — |
| `IdentityPort` | User resolution from external auth | — |
| `AuthorizationPort` | Permission checking | — |
| `SettingsPort` | Tenant-scoped configuration | — |
| `MailPort` | Email dispatch | — |
| `StoragePort` | File/blob storage | — |
| `HttpClientPort` | Outbound HTTP calls | — |
| `LicenseResolverPort` | License resolution + quota tracking | — |
| `PaymentGatewayPort` | Payment processing abstraction | — |
| `HashChainPort` | Append/verify tamper-evident hash chain | GeBüV, GoBD |
| `SequencePort` | Gap-free numbering (invoices, journals) | GoBD |
| `KeyManagementPort` | Envelope encryption, key rotation, crypto-shredding | DSG, DSGVO |
| `DataExportPort` | DSGVO Art. 20 — data portability export | DSGVO |
| `DataDeletionPort` | DSGVO Art. 17 — erasure, anonymize, crypto-shred | DSGVO, DSG |
| `ArchivalPort` | Long-term archival with retention enforcement | OR, GeBüV |
| `TranslationPort` | i18n — translate keys with parameter interpolation | — |
| `NumberFormatterPort` | Locale-aware number/currency/percent formatting | — |
| `TaxResolverPort` | Multi-jurisdiction tax rate resolution | MWSTG |
| `NotificationPort` | Multi-channel notification dispatch | — |
| `AiGatewayPort` | AI inference, embeddings, content moderation | — |
| `DocumentGeneratorPort` | Document generation incl. PDF/A | GeBüV, PDF/A |
| `DigitalSignaturePort` | eIDAS SES/AES/QES signing + verification | eIDAS, ZertES |
| `ExchangeRatePort` | Currency exchange rates + conversion | ISO 20022 |
| `WebhookDispatcherPort` | Outbound webhook dispatch + subscription mgmt | — |
| `RateLimiterPort` | Rate limiting (check/attempt/reset) | — |
| `FileValidationPort` | File type validation + malware scanning | ISO 27001 |
| `DossierPort` | Revisionssichere dossier management + access logging | GeBüV Art. 4/7/8 |
| `TimeTrackingPort` | ArG-compliant time tracking + validation | ArG Art. 46 |
| `ConsentPort` | DSG/DSGVO consent lifecycle management | DSG Art. 6, DSGVO Art. 7 |
| `SwissdecTransmitterPort` | Swissdec ELM payroll data transmission | Swissdec ELM 5.0+ |

### Application Layer (`src/Application/`)
Orchestration and cross-cutting concerns:
- **SecurityContext**: Immutable request context (who, where, what permissions, which license, locale, hosting mode)
- **LicenseGuard**: Enforces module access, feature availability, and quota consumption
- **IdempotencyGuard**: Prevents duplicate command execution via cache-backed dedup
- **Command/Query interfaces**: CQRS-lite separation with mandatory tenantId + idempotencyKey

### Contracts Layer (`src/Contracts/`)
Interfaces that modules must implement to plug into the platform:
- **ModuleManifest**: Declares slug, version, required permissions, events published/consumed
- **ModuleServiceProvider**: Registration hook for DI bindings and event subscriptions
- **LedgerEntry**: Entries that participate in hash chain integrity verification
- **TaxRule**: Country-specific tax rule implementations
- **AuditReportContract**: Module-level integrity checks and audit data export
- **Notifiable**: Entities that can trigger notifications
- **SwissdecCertifiable**: Modules participating in Swissdec ELM certification

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
**Decision**: `Plan` defines what a tier includes (modules, features, quotas, seats, AI calls, storage). `License` binds a tenant to a plan. `LicenseGuard` enforces access at the application layer.

### ADR-005: Mandatory TenantId
**Context**: Multi-tenancy bugs (data leaks) are critical security vulnerabilities.
**Decision**: `TenantId` is a required value object that rejects zero, negative, and null values. Every command, query, and event carries it explicitly.

### ADR-006: Event-Driven Module Communication
**Context**: Modules must remain loosely coupled to enable independent deployment.
**Decision**: Modules communicate via `DomainEvent` objects published through `EventBusPort`. Direct module-to-module imports are forbidden.

### ADR-007: Idempotent Commands
**Context**: Network retries and webhook replays can cause duplicate operations.
**Decision**: Every `Command` carries an `idempotencyKey`. The `IdempotencyGuard` deduplicates using a 24-hour cache window.

### ADR-008: Hash Chain for Ledger Integrity
**Context**: Swiss GeBüV and German GoBD require tamper-evident financial records.
**Decision**: A SHA-256 hash chain (`HashChain`) links every ledger entry to its predecessor. The `HashChainPort` enforces append-only semantics. Integrity can be verified at any time.

### ADR-009: Crypto-Shredding for DSGVO Compliance
**Context**: DSGVO Art. 17 requires deletion of personal data, but GeBüV/OR require 10-year retention of financial records.
**Decision**: Personal data is encrypted with per-tenant keys via `KeyManagementPort`. Deletion means destroying the encryption key (crypto-shredding), making ciphertext permanently unreadable while preserving the hash chain integrity.

### ADR-010: Gap-Free Sequences
**Context**: German GoBD prohibits gaps in invoice/journal numbering.
**Decision**: `SequencePort` provides atomic, gap-free number generation per sequence key (prefix:year:tenant). Gaps are automatically detected via `SequenceGap`.

### ADR-011: Multi-Channel Notifications
**Context**: Products need to reach users via email, SMS, push, in-app, and webhooks.
**Decision**: `NotificationPort` provides a unified interface for all channels. Users control preferences per channel via `NotificationPreference`.

### ADR-012: AI Gateway Abstraction
**Context**: AI providers change rapidly. Products should not be locked to a single provider.
**Decision**: `AiGatewayPort` abstracts AI inference, embeddings, and content moderation. `AiModelConfig` supports multiple providers (Gemini, OpenAI, Anthropic) with named constructors.

### ADR-013: Digital Signatures (eIDAS/ZertES)
**Context**: Legal documents require varying levels of digital signatures.
**Decision**: `DigitalSignaturePort` supports SES (simple), AES (advanced), and QES (qualified) signature levels per eIDAS/ZertES. `SignatureLevel::isLegallyBinding()` distinguishes non-binding from legally binding signatures.

### ADR-014: Revisionssichere Dossier-Verwaltung (GeBüV)
**Context**: Swiss GeBüV requires that business documents are stored completely, immutably, and with full access logging.
**Decision**: `Dossier` enforces encryption for personal data, `DossierEntry` uses SHA-256 content hashes for integrity verification, and `DossierAccessLog` records every VIEW, DOWNLOAD, UPLOAD, DELETE, EXPORT, and PRINT action.

### ADR-015: ArG-Compliant Time Tracking
**Context**: Swiss ArG Art. 46 / ArGV 1 Art. 73 requires recording of work hours, breaks, and overtime with 5-year retention.
**Decision**: `TimeEntry` captures start/end/breaks per ArG. `WorkTimeValidation` enforces Swiss break rules (15min >5.5h, 30min >7h, 60min >9h) and overtime limits (max 2h/day).

### ADR-016: Consent Management (DSG/DSGVO)
**Context**: Storing employee photos, biometric data, or transferring data cross-border requires explicit consent under DSG Art. 6 / DSGVO Art. 7.
**Decision**: `Consent` records grant/revoke decisions with timestamps, IP, and expiry. `ConsentPurpose` distinguishes sensitive from non-sensitive purposes. `ConsentPort` provides lifecycle management including bulk revocation.

### ADR-017: Swissdec ELM Integration
**Context**: Swiss payroll software must transmit salary declarations via Swissdec ELM to AHV, BVG, UVG, KTG, tax authorities, and BFS. Certification by Swissdec association is required.
**Decision**: The kernel provides domain primitives (`SwissdecDomain`, `SocialInsuranceType`, `WageType`, `SalaryDeclaration`) and a `SwissdecTransmitterPort`. The actual certified implementation is an adapter, potentially using SwissDecTX or equivalent certified transmitter component. `SwissdecCertifiable` contract enables modules to participate in certification.

## Compliance Matrix

| Regulation | Kernel Component |
|------------|-----------------|
| Swiss OR (Art. 957-963) | RetentionPolicy (10Y financial), HashChain, ArchivalPort |
| GeBüV | HashChain (tamper-evident), SequencePort (gap-free), ArchivalPort (PDF/A) |
| MWSTG | TaxRate (CH 8.1%/2.6%/3.7%), TaxResolverPort |
| DSG (CH) | DataDeletionPort, KeyManagementPort (crypto-shredding) |
| DSGVO (EU) | DataExportPort (Art. 20), DataDeletionPort (Art. 17), RetentionPolicy |
| GoBD (DE) | HashChain, SequencePort (gap-free), RetentionPolicy |
| eIDAS | DigitalSignaturePort (SES/AES/QES), SignatureLevel |
| ISO 20022 | ExchangeRate, Money (integer minor units) |
| ISO 27001 | FileValidationPort (malware scanning), EncryptedField, KeyManagement |
| Swiss GAAP FER | HashChain (audit trail), ArchivalPort |
| PDF/A | DocumentGeneratorPort::generatePdfA() |
| ArG Art. 46 | TimeTrackingPort (5Y retention), WorkTimeValidation |
| Swissdec ELM 5.0+ | SwissdecTransmitterPort, SalaryDeclaration, WageType |

## Testing Strategy

- **Domain tests**: Pure unit tests with no infrastructure dependencies (46 test classes)
- **Application tests**: Use `TestAdapters/` (15 in-memory/fake adapters)
- **No mocking frameworks**: Fakes and in-memory implementations ensure tests verify real behavior
- **Test pyramid**: Heavily weighted towards fast domain/unit tests

### Test Adapters
| Adapter | Port |
|---------|------|
| `InMemoryCache` | `CachePort` |
| `InMemoryEventBus` | `EventBusPort` |
| `FrozenClock` | `ClockPort` |
| `FakeLicenseResolver` | `LicenseResolverPort` |
| `NullLogger` | `LoggerPort` |
| `InMemoryHashChain` | `HashChainPort` |
| `InMemorySequence` | `SequencePort` |
| `InMemoryKeyManagement` | `KeyManagementPort` |
| `InMemoryTranslation` | `TranslationPort` |
| `InMemoryNotification` | `NotificationPort` |
| `FakeAiGateway` | `AiGatewayPort` |
| `InMemoryDossier` | `DossierPort` |
| `InMemoryTimeTracking` | `TimeTrackingPort` |
| `InMemoryConsent` | `ConsentPort` |
| `FakeSwissdecTransmitter` | `SwissdecTransmitterPort` |

## Next Steps

1. **Module Skeleton**: Create a reference module (e.g. `booking/`) demonstrating how to build on the kernel
2. **SaaS Host Adapters**: Implement ports for the chosen SaaS framework (Laravel/Symfony)
3. **Frontend Foundation**: Vue 3 + TypeScript + Pinia + Vite project structure
4. **CI Pipeline**: PHPUnit + PHPStan + php-cs-fixer in GitHub Actions
5. **License Server**: API for license validation, quota tracking, and subscription management
6. **QR-Rechnung Module**: Swiss QR-bill generation per ISO 20022
7. **Payroll Module**: Salary computation, Swiss social deduction engine (AHV/IV/EO/ALV)
