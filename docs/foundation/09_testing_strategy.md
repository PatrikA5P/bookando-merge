# Phase 4 — Testing Strategy

> Teststrategie für den Platform Kernel und Module.

---

## 1. Test-Pyramide

```
        ╱╲
       ╱E2E╲           ~5%  (Playwright, echte Browser)
      ╱──────╲
     ╱ Integr. ╲       ~25% (PHPUnit + DB, Vitest + API Mock)
    ╱────────────╲
   ╱    Unit      ╲    ~70% (PHPUnit, Vitest — kein IO)
  ╱────────────────╲
```

---

## 2. Unit Tests

### Scope: Domain Layer + Application Layer (ohne IO)

**PHP (PHPUnit)**:
- Entities, Value Objects, Policies
- Command/Query Handlers mit gemockten Ports
- Money-Berechnungen (exhaustive Grenzwerte)
- Time/Scheduling-Logik (DST, Overlap, Ruhezeiten)

**TypeScript (Vitest)**:
- Pinia Store Logic (ohne API-Calls)
- Composables (7 in `src/Core/Design/composables/`)
- Utility-Funktionen
- API-Client Transformations (`packages/api-client/`)

### Mindest-Coverage: 80% Line Coverage für Domain + Application Layer

### Kritische Test-Bereiche (MUSS 100% Branch Coverage):

| Bereich | Referenz | Tests |
|---------|----------|-------|
| Money Value Object | `Kernel/Domain/Money.php` (neu) | Alle Operationen: add, subtract, multiply, compare. Alle Währungen. Overflow. Precision. |
| MultiTenantTrait | `src/Core/Model/Traits/MultiTenantTrait.php` | `applyTenant()` mit gültigen/ungültigen SQL-Strings. Null-Tenant → Exception. |
| DoubleBookingPolicy | `Kernel/Domain/Policies/DoubleBookingPolicy.php` (neu) | Alle Overlap-Konstellationen: before, after, contains, contained-by, starts-during, ends-during, exact-match. Mindestens 7 Cases. |
| RestPeriodPolicy | Workday-Modul Domain | Grenzwerte: 10h59m (fail), 11h00m (pass), 11h01m (pass). DST-Tag (23h/25h). |
| Gate::evaluate() | `src/Core/Auth/Gate.php` | Alle Pfade: devBypass, unauthenticated, wrong tenant, missing nonce, missing cap, read-only, write. |
| TenantManager Resolution | `src/Core/Tenant/TenantManager.php` | Alle 5 Resolution-Stufen (Header, Param, UserMeta, Subdomain, Fallback). Edge: ungültige Werte, negative IDs, Array-Injection. |

---

## 3. Integration Tests

### Scope: Application Layer + Infrastructure (echte DB, gemockte externe APIs)

**Setup**:
- Separate Test-Datenbank (SQLite in-memory oder MySQL Test-Schema)
- Fixtures: 2 Tenants, je 3 Users (admin, manager, employee), Testdaten pro Modul
- Transaction Rollback nach jedem Test (via `setUp()`/`tearDown()`)
- `TenantManager::reset()` in `tearDown()`

### Kritische Integration Tests:

| Test-Suite | Scope | Assertions |
|-----------|-------|-----------|
| **Tenant Isolation** | 2 Tenants, gleiche Operationen | Tenant A sieht nur eigene Daten. Cross-Tenant-Query → leere Ergebnisse. Background-Job mit Tenant B → korrekte Isolation. |
| **Auth Flow** | Login → JWT → API Call → Refresh → Logout | Korrekte Token-Generierung/Validierung. Revoked Token → 401. Expired → 401. Refresh → neuer Token. |
| **Payment Flow** | Create → Webhook → Status Update → Refund | Idempotente Webhook-Verarbeitung. Korrekte Status-Transitionen. Refund ≤ Original. |
| **Booking Flow** | Verfügbarkeit → Buchung → Bestätigung | Kapazitäts-Check. DST-korrekte Zeitberechnung. Concurrent Booking → eine scheitert. |
| **Shift Conflicts** | Schichtplanung mit Überlappungen | Alle Conflict-Typen erkannt (overlap, absence, rest-period). Rest-Period enforced (11h). |
| **REST Permissions** | Alle Endpoints × alle Rollen | Matrix: admin, manager, employee, customer, anonymous. Jede Kombination = erwarteter Status-Code. |
| **Module Lifecycle** | Activate → Boot → Routes → Deactivate | Routes registriert. Capabilities gesetzt. Assets referenzierbar. |
| **Invoice Calculation** | Multi-Line, Multi-Tax, Multi-Currency | Beträge stimmen auf Cent. Float-Precision-Grenzfälle erkannt. |
| **Calendar Sync** | OAuth → Sync → Error Recovery | Token-Refresh Mock. Event-Creation Mock. Error-Handling verifiziert. |
| **Rate Limiting** | Burst-Requests | Rate-Limit greift nach N Requests. Reset funktioniert. Verschiedene Identifier (User/IP). |

---

## 4. E2E Tests

### Scope: UI → API → DB (volle Anwendung)

**Framework**: Playwright (bereits konfiguriert: `playwright.config.ts`)

### Kritische E2E Tests:

| # | Test | Priorität |
|---|------|-----------|
| 1 | **Buchungsflow**: Kalender → Slot wählen → Formular → Buchen → Bestätigung | P0 |
| 2 | **Login/Logout**: Login-Form → JWT → Geschützter Bereich → Logout → Redirect | P0 |
| 3 | **Admin CRUD**: Login als Admin → Kunden erstellen/bearbeiten/löschen → Liste prüfen | P0 |
| 4 | **Zahlungsflow**: Service wählen → Checkout → (Stripe Test-Mode) → Bestätigung | P1 |
| 5 | **Employee Self-Service**: Login als Employee → Eigenes Profil → Kalender | P1 |
| 6 | **Multi-Tenant**: Tenant A Login → Tenant A Daten → Logout → Tenant B Login → Tenant B Daten → Keine Cross-Contamination | P0 |
| 7 | **Modul-Navigation**: Dashboard → jedes Modul → Daten sichtbar → keine Fehler in Console | P1 |
| 8 | **Responsive/PWA**: Mobile Viewport → Buchung → funktional | P2 |

---

## 5. CI Gates (Minimal Required Checks to Merge)

| Gate | Tool | Blocking? | Beschreibung |
|------|------|-----------|-------------|
| **PHP Lint** | `php -l` | ✅ Ja | Syntax-Check aller PHP-Dateien |
| **PHPStan** | `phpstan analyse` Level 6+ | ✅ Ja | Statische Typanalyse (Script: `composer lint:phpstan`) |
| **ESLint** | `eslint --max-warnings=0` | ✅ Ja | TypeScript/Vue Linting (existiert: `eslint.config.js`) |
| **TypeScript** | `tsc --noEmit` | ✅ Ja | Typ-Check ohne Build |
| **PHP Unit Tests** | `phpunit` | ✅ Ja | Alle Unit + Integration Tests (existiert: `phpunit.xml.dist`) |
| **Vitest** | `vitest run` | ✅ Ja | Frontend Unit Tests (existiert: `vitest.config.ts`) |
| **Security Scan** | `composer audit` + `npm audit` | ✅ Ja | Dependency-Vulnerabilities |
| **Debug Artifact Check** | `scripts/qa/check-debug-artifacts.php` | ✅ Ja | Verhindert `var_dump`, `console.log` etc. in PRs |
| **Module Validation** | `scripts/validate-modules.mjs` | ✅ Ja | Modul-Manifeste und Struktur-Checks |
| **Playwright E2E** | `playwright test` | ⚠️ Nightly | Zu langsam für jeden PR |
| **Coverage Check** | `phpunit --coverage-text` | ⚠️ Advisory | Coverage-Report (80% Minimum-Ziel) |

---

## 6. Test-Fixtures & Factories

### Tenant-Fixture:

```php
class TenantFixture
{
    public static function create(array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => random_int(1000, 99999),
            'slug' => 'test-' . bin2hex(random_bytes(4)),
            'name' => 'Test Tenant',
            'status' => 'active',
            'plan' => 'professional',
        ], $overrides);
    }

    public static function pair(): array
    {
        return [self::create(['slug' => 'tenant-a']), self::create(['slug' => 'tenant-b'])];
    }
}
```

### User/Appointment/Payment Factories analog für jeden Entity-Typ.

---

## 7. Aktueller Stand der Tests

### phpunit.xml.dist Suiten:
- `Tenant` → TenantManager-Tests
- `License` → Lizenz-Tests
- `Module` → ModuleManager-Tests
- `Unit` → Unit-Tests
- `Integration` → REST-Integrationstests
- `Misc` → Sonstige

### Dateien: 49 Test-Dateien in `tests/`

### Ziel-Konfiguration (nach Kernel-Extraktion):
```xml
<testsuites>
    <testsuite name="kernel-unit">
        <directory>src/Kernel/Tests/Unit</directory>
    </testsuite>
    <testsuite name="kernel-integration">
        <directory>src/Kernel/Tests/Integration</directory>
    </testsuite>
    <testsuite name="modules-unit">
        <directory>src/modules/*/Tests/Unit</directory>
    </testsuite>
    <testsuite name="modules-integration">
        <directory>src/modules/*/Tests/Integration</directory>
    </testsuite>
    <testsuite name="tenant-isolation">
        <directory>tests/TenantIsolation</directory>
    </testsuite>
    <testsuite name="security">
        <directory>tests/Security</directory>
    </testsuite>
</testsuites>
```

### Minimal-Testliste für Phase-1-Kernel (Top Priority):

1. `MultiTenantTraitTest` — SQL-Wrapping, Null-Tenant
2. `BaseModelInsertTest` — Tenant enforcement auf Insert
3. `BaseModelUpdateDeleteTest` — Tenant enforcement auf Update/Delete
4. `TenantManagerTest` — Resolution-Kette (existiert bereits in `tests/Tenant/`)
5. `GateEvaluateTest` — Alle Auth-Pfade
6. `JWTServiceTest` — Sign/Verify/Expire/Revoke
7. `MoneyValueObjectTest` — Integer arithmetic, rounding, currency
8. `RateLimitTest` — Limit enforcement + reset
9. `QueueManagerTest` — Enqueue/Process/Retry/Dead-Letter
10. `ContainerTest` — Singleton, Bind, Circular Detection
