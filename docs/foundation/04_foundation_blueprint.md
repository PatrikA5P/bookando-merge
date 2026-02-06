# Phase 4A — Foundation Blueprint

> Referenzarchitektur für den Bookando Platform Kernel.
> Host-agnostisch: SaaS (primär) + PWA + WordPress Plugin (Adapter).

---

## 1. Architektur-Ziele

| Ziel | Beschreibung |
|------|-------------|
| **Host-Agnostik** | Kernel hat ZERO WordPress-Abhängigkeiten. WP ist ein Adapter. |
| **Tenant-First** | Jede Operation hat einen expliziten Tenant-Kontext. Kein Default-Fallback auf Tenant 1. |
| **Money-Safe** | Geldbeträge ausschließlich als Integer (Minor Units). Keine Floats. |
| **Time-Safe** | UTC-Speicherung, DST-sichere Berechnungen, explizite Zeitzonen. |
| **Sync-Safe** | Idempotente Mutationen, Outbox-Pattern, Konflikt-Erkennung. |
| **Module-Thin** | Module enthalten nur Domänenlogik. Cross-Cutting-Concerns im Kernel. |
| **Testbar** | Alle Ports als Interfaces. DI statt Static. Keine versteckten Abhängigkeiten. |
| **Beobachtbar** | Structured Logging, Correlation-IDs, Metriken. |

---

## 2. Schichtenmodell (Ports & Adapters)

```
┌─────────────────────────────────────────────────────────┐
│                    HOST ADAPTERS                         │
│  ┌──────────┐  ┌──────────┐  ┌───────────────────────┐ │
│  │ SaaS API │  │   PWA    │  │ WordPress Plugin      │ │
│  │ + Worker  │  │ (Client) │  │ (standalone/connected)│ │
│  │ + Sched.  │  │          │  │                       │ │
│  └────┬─────┘  └────┬─────┘  └──────────┬────────────┘ │
└───────┼──────────────┼───────────────────┼──────────────┘
        │              │                   │
┌───────▼──────────────▼───────────────────▼──────────────┐
│              PLATFORM KERNEL                             │
│  ┌────────────────────────────────────────────────────┐ │
│  │  Application Layer                                  │ │
│  │  (Use Cases / Commands / Queries / Transactions)    │ │
│  ├────────────────────────────────────────────────────┤ │
│  │  Domain Layer                                       │ │
│  │  (Entities, Value Objects, Domain Events, Policies) │ │
│  ├────────────────────────────────────────────────────┤ │
│  │  Contracts Layer                                    │ │
│  │  (API Schemas, Validation Rules, Event Schemas)     │ │
│  ├────────────────────────────────────────────────────┤ │
│  │  Infrastructure Ports (Interfaces)                  │ │
│  │  Persistence │ Queue │ Clock │ Crypto │ Mail │ ...  │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

---

## 3. Platform Kernel — Innere Struktur

### 3.1 Domain Layer

Enthält die Geschäftslogik, FREI von Framework- und IO-Abhängigkeiten.

**Entities** (Aggregat-Roots):
- `Tenant` — ID, Slug, Status, Plan, Limits
- `User` — ID, Email, Roles[], TenantId, ExternalId
- `Appointment` — ID, TenantId, CustomerId, EmployeeId, ServiceId, StartsAtUtc, EndsAtUtc, ClientTz, Status, Price (Money VO)
- `Offer` (Service/Course) — ID, TenantId, Type, MaxParticipants, Pricing, RecurrenceRule
- `Employee` — ID, TenantId, UserId, WorkdaySets, DaysOff, CalendarConnections
- `Payment` — ID, TenantId, GatewayId, Amount (Money VO), Currency, Status, IdempotencyKey, ExternalId
- `Invoice` — ID, TenantId, LineItems[], SubtotalMinor, TaxTotalMinor, TotalMinor, Currency, Status
- `Shift` — ID, TenantId, EmployeeId, Date, StartTime, EndTime, Status

**Value Objects**:
- `Money(int $amountMinor, string $currencyCode)` — Immutable, arithmetische Operationen nur mit gleicher Währung
- `TenantId(int $value)` — Validiert > 0
- `UserId(int $value)`
- `TimeRange(DateTimeImmutable $start, DateTimeImmutable $end, DateTimeZone $tz)`
- `RecurrenceRule(string $frequency, array $days, ?DateTimeImmutable $until)`
- `IdempotencyKey(string $value)` — UUID-basiert

**Domain Events**:
- `AppointmentCreated`, `AppointmentCancelled`, `AppointmentRescheduled`
- `PaymentSucceeded`, `PaymentFailed`, `RefundCompleted`
- `InvoiceIssued`, `InvoiceCorrected`
- `EmployeeScheduleChanged`, `ShiftAssigned`, `ShiftConflictDetected`
- `TenantProvisioned`, `TenantSuspended`
- `CalendarSyncRequested`, `CalendarSyncFailed`

**Policies** (Domänen-Regeln):
- `DoubleBookingPolicy` — Prüft Zeitraum-Überlappungen für Appointments + Shifts
- `AvailabilityPolicy` — Prüft Kapazität, Arbeitszeiten, Abwesenheiten
- `RestPeriodPolicy` — Mindest-Ruhezeit zwischen Schichten (11h)
- `RefundPolicy` — Maximaler Erstattungsbetrag, Zeitlimit
- `MoneyRoundingPolicy` — Zentrale Rundungsregeln pro Währung

### 3.2 Application Layer

**Commands** (schreibend, immer idempotent):
- `CreateAppointment(idempotencyKey, tenantId, customerId, employeeId, serviceId, startsAt, endsAt, clientTz)`
- `CancelAppointment(tenantId, appointmentId, reason)`
- `ProcessPayment(idempotencyKey, tenantId, gatewayId, amount, currency, metadata)`
- `IssueInvoice(tenantId, lineItems[], currency)`
- `AssignShift(tenantId, employeeId, date, startTime, endTime, templateId?)`
- `RefundPayment(idempotencyKey, tenantId, paymentId, amount?, reason?)`

**Queries** (lesend):
- `GetTimeline(tenantId, fromUtc, toUtc, filters)`
- `GetAvailability(tenantId, serviceId, dateRange, timezone)`
- `GetInvoice(tenantId, invoiceId)`
- `GetPaymentStatus(tenantId, paymentId)`
- `GetEmployeeSchedule(tenantId, employeeId, dateRange)`

**Transaction Boundaries**:
- Jeder Command läuft in einer DB-Transaktion
- Bei Failure: Rollback + Domain-Event für Kompensation
- Idempotency: Check auf idempotencyKey vor Ausführung

### 3.3 Contracts Layer

- **API Schemas**: OpenAPI 3.1 Definitionen für alle Endpunkte
- **Event Schemas**: Versionierte Event-Payloads (JSON Schema)
- **Validation Rules**: Deklarative Validierung (PHP Attributes oder Schema-Objekte)
- **Error Codes**: Kanonisches Fehler-Mapping (HTTP Status + Machine-Code + Message)

### 3.4 Infrastructure Ports (Interfaces)

| Port | Verantwortung | Aktueller Adapter | SaaS-Adapter | PWA-Adapter |
|------|---------------|-------------------|--------------|-------------|
| `PersistencePort` | DB CRUD + Transactions | WordPressDatabaseAdapter | PostgreSQL/MySQL | IndexedDB/SQLite |
| `QueuePort` | Async Jobs | QueueManager (wpdb-basiert) | Redis/SQS Queue | Service Worker Queue |
| `CachePort` | Key-Value Cache | WP Transients | Redis | localStorage |
| `ClockPort` | Aktuelle Zeit | `current_time()` | `new DateTimeImmutable('now', new DateTimeZone('UTC'))` | `Date.now()` |
| `CryptoPort` | Hashing, Encryption | WP Salts + openssl | Env Secrets + libsodium | WebCrypto API |
| `MailPort` | E-Mail-Versand | `wp_mail()` | SendGrid/SES | — (via SaaS API) |
| `HttpClientPort` | Ausgehende HTTP-Requests | `wp_remote_get/post()` | Guzzle/cURL | fetch() |
| `IdentityPort` | Aktueller User + Auth | WP User System | OIDC/JWT Provider | JWT Token Store |
| `AuthorizationPort` | Capability/Permission Check | `current_user_can()` | RBAC-Engine | Token Claims |
| `CsrfPort` | CSRF-Token-Validierung | `wp_verify_nonce()` | Double-Submit Cookie / SameSite | — (SPA: Token-Auth) |
| `KeyValueStorePort` | Persistente Settings | `get_option()`/`update_option()` | DB Settings Table | localStorage |
| `EventBusPort` | Domain Events Publish/Subscribe | `do_action()`/`add_action()` | In-Process EventDispatcher | CustomEvent / MessageChannel |
| `LoggerPort` | Structured Logging | ActivityLogger (wpdb) | stdout JSON (12-factor) | console + remote API |
| `FileStoragePort` | Datei-Upload/Download | WP Media Library | S3/GCS | Cache API |
| `SchedulerPort` | Cron/Scheduled Tasks | WP-Cron | System Cron / CloudScheduler | — (via SaaS API) |
| `PaymentGatewayPort` | Zahlungsabwicklung | GatewayInterface (bereits vorhanden) | Gleich | — (via SaaS API) |
| `CalendarSyncPort` | Kalender-Synchronisation | Google/MS/Apple Sync-Klassen | Gleich | — (via SaaS API) |
| `MigrationPort` | Schema-Migrationen | dbDelta() + Migrator | Doctrine Migrations / Phinx | — |

---

## 4. Host-Adapter-Spezifikationen

### 4.1 SaaS Host Adapter (PRIMÄR)

```
saas/
├── api/                    # HTTP API Server (Laravel/Slim/Custom)
│   ├── routes.php          # REST-Route-Registration
│   ├── middleware/          # Auth, Tenant, RateLimit, CORS
│   └── controllers/        # Thin Controllers → Application Commands/Queries
├── worker/                 # Background Job Worker
│   ├── consumer.php        # Queue Consumer (Redis/SQS)
│   └── handlers/           # Job → Command Mapping
├── scheduler/              # Cron/Scheduled Tasks
│   ├── crontab             # System Cron Definition
│   └── tasks/              # Registered Tasks
├── config/                 # Environment Configuration
│   ├── database.php
│   ├── queue.php
│   ├── auth.php
│   └── tenants.php
├── migrations/             # Database Migrations (versioned, reversible)
└── observability/
    ├── logging.php         # Structured JSON Logging
    ├── tracing.php         # OpenTelemetry Config
    └── metrics.php         # Prometheus Metrics
```

**Auth**: OIDC / JWT (self-issued oder Auth0/Keycloak)
**DB**: PostgreSQL (RLS für Tenant-Isolation) oder MySQL mit Application-Level Scoping
**Queue**: Redis (Bull/Sidekiq-like) oder AWS SQS
**Cache**: Redis
**Secrets**: Vault / AWS Secrets Manager / Environment Variables

### 4.2 PWA Adapter (Client)

```
pwa/
├── src/
│   ├── api/                # API Client (Axios/Fetch)
│   ├── store/              # Pinia Stores (Offline-First)
│   ├── sync/               # Sync Engine
│   │   ├── outbox.ts       # Pending Mutations Queue
│   │   ├── inbox.ts        # Incoming Changes
│   │   ├── conflict.ts     # Conflict Detection/Resolution
│   │   └── cursor.ts       # Sync Cursor Management
│   ├── cache/              # IndexedDB / Cache API
│   ├── auth/               # JWT Session Management
│   └── components/         # Vue Components (Design System)
├── service-worker.ts       # Background Sync + Offline Cache
└── manifest.json           # PWA Manifest
```

**Offline-Modus**: Lesezugriff auf gecachte Daten. Schreiboperationen in Outbox Queue.
**Sync**: Beim Online-Kommen: Outbox → SaaS API. Conflict Detection per Entity-Version.
**Auth**: JWT mit Refresh-Token. Offline: cached Token + limited Operations.

### 4.3 WordPress Plugin Adapter

```
wp-adapter/
├── bookando.php            # WP Plugin Entry (Minimal: Bootstrap + Adapter-Wiring)
├── adapters/
│   ├── WpPersistenceAdapter.php    # $wpdb → PersistencePort
│   ├── WpCacheAdapter.php          # Transients → CachePort
│   ├── WpIdentityAdapter.php       # WP Users → IdentityPort
│   ├── WpAuthorizationAdapter.php  # Capabilities → AuthorizationPort
│   ├── WpCsrfAdapter.php           # Nonces → CsrfPort
│   ├── WpMailAdapter.php           # wp_mail → MailPort
│   ├── WpHttpAdapter.php           # wp_remote → HttpClientPort
│   ├── WpSettingsAdapter.php       # Options → KeyValueStorePort
│   ├── WpEventBusAdapter.php       # Hooks → EventBusPort
│   ├── WpCronAdapter.php           # WP-Cron → SchedulerPort
│   └── WpLoggerAdapter.php         # error_log → LoggerPort
├── admin/                  # WP Admin Pages (Vue Container)
├── rest/                   # WP REST API Bridge → Kernel Routes
├── sync/                   # Connected-Mode Sync Client
│   ├── outbox.php
│   ├── inbox.php
│   └── sync-worker.php
└── standalone/             # Standalone-Mode: Kernel direkt in WP
    └── config.php          # Feature-Flags: standalone vs connected
```

**Standalone-Mode**: Kernel läuft direkt in WP. Alle Ports via WP-Adapter.
**Connected-Mode**: WP als UI-Client. Daten per Sync-Layer mit SaaS synchronisiert. SaaS ist Source-of-Truth für Money/Accounting. WP darf offline Bookings erstellen (→ Outbox).

---

## 5. Multi-Tenancy Strategy (SaaS-First)

### Empfehlung: Shared DB + Application-Level Scoping + Optional RLS

**Begründung**:
- Aktueller Code nutzt bereits `tenant_id` auf allen Tabellen
- Shared DB minimiert Ops-Komplexität (1 Connection Pool, 1 Migration-Run)
- Application-Level Scoping via `MultiTenantTrait` bereits implementiert
- Optional: PostgreSQL Row-Level Security als zusätzliche Sicherheitsebene

### Invarianten:

| Bereich | Invariante | Durchsetzung |
|---------|-----------|-------------|
| **Reads** | Jede SELECT-Query MUSS tenant_id = current_tenant filtern | `MultiTenantTrait::applyTenant()` + RLS |
| **Writes** | Jede INSERT MUSS tenant_id setzen. UPDATE/DELETE MÜSSEN tenant_id in WHERE haben | `BaseModel::insert/update/delete()` |
| **Background Jobs** | Jeder Job MUSS tenant_id im Payload tragen. Worker MUSS Tenant-Kontext vor Ausführung setzen | QueuePort-Contract |
| **Exports/Imports** | Export MUSS Tenant-Scope erzwingen. Import MUSS Ziel-Tenant validieren | Application Layer |
| **Caching** | Jeder Cache-Key MUSS Tenant-Prefix haben: `{tenant_id}:{key}` | CachePort-Contract |
| **Analytics** | Aggregations MÜSSEN tenant-scoped sein oder explizit als cross-tenant markiert | Query Layer |
| **Search** | Index MUSS Tenant-ID als Filter unterstützen | Search Adapter |

---

## 6. Authn/Authz Model (SaaS-First)

### Authentication:

| Runtime | Methode | Details |
|---------|---------|---------|
| **SaaS** | OIDC/JWT | Self-issued JWT oder externer Provider (Auth0/Keycloak). Access-Token (15 min) + Refresh-Token (30 Tage). |
| **PWA** | JWT (via SaaS) | Login → SaaS Auth API → JWT. Offline: cached Token. |
| **WP Standalone** | WP Sessions + JWT Bridge | WP-Login → lokaler JWT für API-Calls. |
| **WP Connected** | Service Token | WP ↔ SaaS: Service-Token (API Key) für Sync. User-Auth via WP lokal. |

### Authorization:

**RBAC-Modell** (Kernel-definiert):
- `Role` → `Permission[]`
- Built-in Roles: `admin`, `manager`, `employee`, `customer`
- Permissions: `module.action` Format (z.B. `appointments.create`, `finance.refund`, `employees.manage`)
- Enforcement: Kernel-seitig im Application Layer (Command/Query Guards)

**Verbotene Shortcuts**:
- KEIN UI-only Check ohne Server-Enforcement
- KEINE implizite Tenant-Eskalation (Admin sieht nicht automatisch alle Tenants)
- KEIN Capability-Check nur über WP-Hooks (muss im Kernel-Guard passieren)

---

## 7. Money/Accounting Foundation

### Kernregeln:

1. **Money als Integer Minor Units**: `Money(1000, 'CHF')` = 10.00 CHF
2. **Zentralisierte Rounding Policy**:
   - Standard: `ROUND_HALF_UP`
   - Pro Währung definiert (Null-Dezimal-Währungen: JPY, KRW etc.)
3. **Tax/VAT Policy Interface**:
   ```
   TaxPolicy::calculate(Money $amount, TaxRate $rate): TaxBreakdown
   TaxBreakdown { netMinor, taxMinor, grossMinor, rate, rateLabel }
   ```
4. **Invoice Lifecycle**: `draft → issued → paid → (corrected|cancelled)`
   - Corrections via Credit Note (neue Rechnung mit negativem Betrag)
   - Kein Löschen von ausgestellten Rechnungen (Audit-Trail)
5. **Idempotente Zahlungsverarbeitung**:
   - Jeder Payment-Intent hat einen `IdempotencyKey`
   - Webhook-Events tracken `event_id` → Deduplizierung vor Verarbeitung
   - Status-Transitionen sind unidirektional: `pending → processing → succeeded|failed`
6. **Reconciliation**: Täglicher Job vergleicht lokale Payments mit Gateway-Status

---

## 8. Time/Scheduling Foundation

### Kernregeln:

1. **Speicherung**: Alle Zeitpunkte in UTC (`DATETIME` oder `TIMESTAMP WITH TIME ZONE`)
2. **Darstellung**: Konvertierung in Client-Timezone nur für Display
3. **DST-Safe Berechnung**: `DateTimeImmutable` mit expliziter `DateTimeZone`
4. **Concurrency**: Optimistic Locking via `version` Column oder `SELECT ... FOR UPDATE`
5. **Anti-Double-Booking**:
   - Transaktionale Overlap-Prüfung: `SELECT ... FOR UPDATE WHERE overlaps(start, end) AND tenant_id = ? AND employee_id = ?`
   - Alternative: Slot-Lock-Tabelle mit `UNIQUE(tenant_id, employee_id, slot_start)`
6. **Calendar-Sync Boundaries**:
   - Bookando = Source-of-Truth für Appointments
   - Externe Kalender = Read-Only für FreeBusy-Checks
   - Kein bidirektionaler Merge von Appointment-Daten
