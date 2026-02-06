# Phase 4 — Non-Negotiable Invariants

> Diese Invarianten sind ABSOLUTE Regeln. Keine Ausnahmen. Jeder Verstoß ist ein Blocker.

---

## 1. Tenant Isolation

| ID | Invariante | Durchsetzung | Proof Obligation |
|----|-----------|-------------|-----------------|
| TI-1 | Jede DB-Query MUSS tenant_id filtern | `MultiTenantTrait::applyTenant()` wrapped jede SELECT als Subquery mit `WHERE t.tenant_id = %d` (`src/Core/Model/Traits/MultiTenantTrait.php:9-21`). `BaseModel::insert/update/delete()` erzwingt tenant_id (`src/Core/Model/BaseModel.php:190-256`). | Test: Insert/Select/Update/Delete ohne Tenant → RuntimeException. Test: SELECT mit Tenant A darf keine Daten von Tenant B zurückgeben. |
| TI-2 | Jeder Background-Job MUSS tenant_id im Payload tragen | QueuePort-Contract: `enqueue()` validiert `$payload['tenant_id']` vorhanden und > 0. Worker setzt `TenantManager::setCurrentTenantId($payload['tenant_id'])` vor Job-Ausführung. **Aktuell NICHT umgesetzt** → kritischer Fix. | Test: Job ohne tenant_id → Rejection. Test: Job mit tenant_id X liest nur Daten von Tenant X. |
| TI-3 | Cache-Keys MÜSSEN Tenant-Prefix haben | `CachePort` Implementierung: Key-Format `"{$tenantId}:{$key}"`. **Aktuell NICHT umgesetzt** (WP-Transients sind global). | Test: Cache Set mit Tenant A, Get mit Tenant B → null. |
| TI-4 | Cross-Tenant-Zugriff NUR über explizite ACL | `ShareService::createShare()` → `bookando_share_acl` Tabelle. `TenantManager::canAccessShared()` prüft ACL (`src/Core/Tenant/TenantManager.php:387-406`). | Test: Zugriff ohne ACL → Forbidden. Test: Zugriff mit abgelaufener ACL → Forbidden. |
| TI-5 | Kein Default-Fallback auf Tenant 1 in SaaS | Aktuell: `TenantManager::currentTenantId()` fällt auf 1 zurück (`src/Core/Tenant/TenantManager.php:28`). **Im SaaS-Kernel MUSS fehlender Tenant → Exception sein.** | Test: Request ohne Tenant-Kontext → 403 Error. |
| TI-6 | Export/Import MUSS tenant-scoped sein | Application Layer: Export-Commands enthalten mandatory `tenantId`. BaseModel-Scoping greift bereits automatisch. | Test: Export mit Tenant A enthält keine Daten von Tenant B. |

---

## 2. Authorization

| ID | Invariante | Durchsetzung | Proof Obligation |
|----|-----------|-------------|-----------------|
| AZ-1 | Jeder API-Endpoint MUSS serverseitig autorisiert sein | `RestModuleGuard::for($module)` / `Gate::evaluate()` als `permission_callback` auf jeder Route (`src/Core/Dispatcher/RestDispatcher.php`). | Test: Unautorisierter Request auf jeden Endpoint → 401/403. |
| AZ-2 | Write-Operationen MÜSSEN CSRF-geschützt sein | `Gate::verifyNonce()` für POST/PUT/PATCH/DELETE (`src/Core/Auth/Gate.php:162-173`). SaaS: Double-Submit Cookie oder SameSite als Alternative zu WP-Nonce. | Test: Write ohne Nonce/CSRF-Token → 401. |
| AZ-3 | Read-Zugriff darf NICHT pauschal für alle Login-User offen sein | **Aktueller Defekt**: `Gate.php:257` erlaubt Reads mit `current_user_can('read')` — fast jeder WP-User hat das. Kernel: Read-Zugriff pro Modul über Capability. | Test: User ohne Modul-Capability → 403 auch für Reads. |
| AZ-4 | Self-Access MUSS tenant-scoped verifiziert werden | `Gate::isSelf()` (`src/Core/Auth/Gate.php:268-277`) nutzt `$wpdb` direkt ohne Tenant-Filter. **Muss gefixt werden.** | Test: User A in Tenant A versucht isSelf() auf User B in Tenant B → false. |
| AZ-5 | API-Key Permissions MÜSSEN durchgesetzt werden | API-Keys haben `permissions` JSON-Feld in `bookando_api_keys` Tabelle. **Aktuell nicht enforced** in `AuthMiddleware`. | Test: API-Key mit read-only Permission → Write → 403. |
| AZ-6 | Dev-Bypass darf NICHT in Production funktionieren | `Gate::devBypass()` prüft `WP_ENVIRONMENT_TYPE !== 'production'`. Default = 'production' wenn nicht gesetzt (`src/Core/Auth/Gate.php:26-27`). Korrekt implementiert. | Test: devBypass() mit production environment → false. |

---

## 3. Money Correctness

| ID | Invariante | Durchsetzung | Proof Obligation |
|----|-----------|-------------|-----------------|
| MC-1 | Geldbeträge als Integer Minor Units | Ziel: `Money` Value Object mit `int $amountMinor` + `string $currencyCode`. **Aktuell**: Floats in `recalculateTotals()` (`src/modules/Finance/StateRepository.php`). Gateways nutzen bereits `formatAmount()` korrekt (`src/modules/Finance/Gateways/AbstractGateway.php`). | Test: `Money(1050, 'CHF')` → 10.50 CHF. Test: Float-Input → TypeError. |
| MC-2 | Zentrale Rundungspolicy | Ziel: `MoneyRoundingPolicy::round()`. **Aktuell**: `round($x, 2)` verstreut. Zero-Decimal-Currencies (JPY, KRW) in Gateway korrekt behandelt, aber nicht in Invoicing. | Test: Rundung für CHF, EUR, JPY mit Grenzwerten. |
| MC-3 | Rechnungen sind immutable nach Ausstellung | Korrekturen nur via Credit Note. **Aktuell**: Kein Status-Guard auf Updates vorhanden. | Test: Update auf issued Invoice → Exception. |
| MC-4 | Zahlungsverarbeitung ist idempotent | Idempotency-Key pro Payment-Intent. Webhook-Event-ID Tracking. **Aktuell NICHT implementiert** — keine Idempotency-Keys-Tabelle. | Test: Gleicher Webhook 2x → gleicher Zustand, keine doppelten Seiteneffekte. |
| MC-5 | Refund darf Original-Betrag nicht überschreiten | Validierung vor Gateway-Aufruf. **Aktuell**: Nur Gateway-seitige Validierung (Stripe gibt Error). Kein lokaler Pre-Check. | Test: Refund > Original → Error. Test: Summe aller Partial Refunds ≤ Original. |
| MC-6 | Webhook-Signatur MUSS verifiziert werden | Jeder PaymentGatewayPort-Adapter: `verifyWebhookSignature()`. **Aktuell**: Stripe ✅, TWINT ✅, PayPal ❌ (TODO), Mollie ❌ (API-Callback), Klarna ❌ (keine Signatur). | Test: Ungültige Signatur → 403. Test: Fehlende Signatur → 403. |

---

## 4. Time/Scheduling Safety

| ID | Invariante | Durchsetzung | Proof Obligation |
|----|-----------|-------------|-----------------|
| TS-1 | Alle Zeitpunkte in UTC gespeichert | DB-Columns mit `_utc` Suffix in Appointments. `DateTimeImmutable` mit `DateTimeZone('UTC')`. **Achtung**: `BaseModel::now()` nutzt `current_time('mysql')` → WP-Lokalzeit! (`src/Core/Model/BaseModel.php:55`). | Test: Insert mit lokaler Zeit → Konvertierung zu UTC verifizieren. |
| TS-2 | DST-sichere Berechnungen | Immer `DateTimeImmutable` mit expliziter `DateTimeZone`. Keine naive String-Manipulation. PHP's `DateTimeImmutable` handhabt DST korrekt. | Test: Termin am DST-Wechseltag → korrekte Dauer (23h oder 25h-Tag). |
| TS-3 | Anti-Double-Booking muss transaktional sein | **Aktuell NICHT implementiert** für Appointments. `Workday/ShiftService::detectConflicts()` existiert nur für Schichten, nutzt Application-Level Check ohne DB-Lock. | Test: Zwei gleichzeitige Buchungen für gleichen Slot → eine erfolgreich, eine Conflict. |
| TS-4 | Mindest-Ruhezeit zwischen Schichten | `DutySchedulerService` prüft 11h Minimum. Implementiert als Application-Level Check. | Test: 10h59m Abstand → Rejected. 11h00m → Accepted. |
| TS-5 | Calendar-Sync: Bookando = Source-of-Truth | Push-only zu Google/MS/Apple. Änderungen in externen Kalendern werden NICHT zurück synchronisiert. | Test: Externe Kalender-Änderung überschreibt NICHT Bookando-Daten. |

---

## 5. Sync Safety

| ID | Invariante | Durchsetzung | Proof Obligation |
|----|-----------|-------------|-----------------|
| SS-1 | Jede Mutation hat Idempotency-Key | Command-Objekte: `idempotencyKey` als required Parameter. SaaS: Lookup vor Processing. **Noch zu implementieren.** | Test: Gleicher Key 2x → gleiche Response, keine Duplikate. |
| SS-2 | Money-Entitäten: Server-Wins bei Conflict | Conflict-Resolution für Payments, Invoices, Refunds → IMMER Server-Version. Kein Client-Merge. **Kein Silent Destructive Merge.** | Test: Client-Offline-Payment-Edit + Server-Change → Server gewinnt. |
| SS-3 | Entity-Versioning für Conflict Detection | Jede Entity: `version` (auto-increment), `updated_at`. Change Feed liefert beides. **Noch zu implementieren** (keine version-Spalte in aktuellen Tabellen). | Test: Client Version 3, Server Version 5 → Client akzeptiert Server-Update. |
| SS-4 | Outbox FIFO-Verarbeitung | Mutations in Reihenfolge der Erstellung verarbeitet. Kein Reordering. | Test: Outbox [Create, Update, Delete] → Server erhält in gleicher Reihenfolge. |
| SS-5 | Keine silent destructive Merges | Bei Conflict in kritischen Entitäten: User-Benachrichtigung + manuelle Resolution. | Test: Conflict auf Appointment → Status `conflict`, User-Prompt. |
| SS-6 | Resync darf keine Daten zerstören | Resync: Server-Daten überschreiben Client-Daten. Pending Outbox-Items werden NICHT gelöscht. | Test: Resync während pending Outbox → Outbox bleibt erhalten, wird nach Resync re-evaluated. |

---

## Zusammenfassung: Implementierungsstatus

| Invariante | Status |
|-----------|--------|
| TI-1 BaseModel Tenant-Scoping | ✅ Implementiert |
| TI-2 Queue Tenant-Propagation | ❌ FEHLT |
| TI-3 Cache Tenant-Prefix | ❌ FEHLT |
| TI-4 Cross-Tenant ACL | ✅ Implementiert |
| TI-5 Kein Fallback Tenant 1 | ⚠️ Noch Fallback auf 1 |
| AZ-1 Server-side Auth | ✅ Implementiert |
| AZ-2 CSRF Protection | ✅ Implementiert (WP Nonce) |
| AZ-3 Read-Zugriff pro Modul | ❌ Zu offen |
| AZ-4 isSelf() Tenant-Scoped | ❌ FEHLT |
| AZ-5 API-Key Permissions | ❌ FEHLT |
| MC-1 Integer Minor Units | ⚠️ Nur Gateways, nicht Invoicing |
| MC-2 Rundungspolicy | ❌ FEHLT (verstreute round()) |
| MC-3 Invoice Immutability | ❌ FEHLT |
| MC-4 Webhook Idempotency | ❌ FEHLT |
| MC-6 Webhook Signatures | ⚠️ Nur 2 von 5 Gateways |
| TS-1 UTC Storage | ⚠️ Appointments ja, BaseModel::now() nein |
| TS-3 Anti-Double-Booking | ❌ FEHLT |
| SS-1 Idempotency Keys | ❌ FEHLT |
| SS-3 Entity Versioning | ❌ FEHLT |
