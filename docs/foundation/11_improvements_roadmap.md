# Phase 5 — Improvements Roadmap

> Delta-Analyse: Aktueller Stand → Ziel-Foundation. Priorisiert und migrationstauglich.

---

## 1. Delta-Analyse (Aktuell → Ziel)

| Bereich | Aktueller Stand | Ziel (Foundation) | Gap |
|---------|----------------|-------------------|-----|
| **Tenant-Isolation** | MultiTenantTrait (SQL-Wrapping), BaseModel CRUD enforced | + Queue-Jobs mit Tenant-Propagation, + Cache Tenant-Prefix, + kein Fallback auf Tenant 1 | Mittel |
| **Auth/Authz** | Gate + RestModuleGuard + JWT + API Keys + WP Sessions | + API-Key Permission Enforcement, + isSelf() Tenant-Scope, + Read-Zugriff pro Modul statt pauschal `read` | Mittel |
| **Money** | Float-Arithmetik in Invoicing, korrekte Gateway-Konvertierung | Money Value Object (Integer Minor Units), zentrale Rundungspolicy, Invoice-Immutability | Hoch |
| **Webhook Safety** | Stripe+TWINT Signatur OK, PayPal/Mollie/Klarna fehlen | Alle Gateways Signatur-Verifizierung + Idempotency-Key-Tracking | Hoch |
| **Double-Booking** | Keine Prävention in Appointments | Transaktionale Overlap-Prüfung mit DB-Lock | Hoch |
| **DI Container** | PSR-11 Container existiert, aber meist statische Aufrufe | Konsistente DI in allen Modulen, keine statischen Service-Aufrufe | Mittel |
| **Module Boundaries** | Direkte Cross-Module-Imports (Academy↔Finance, Offers↔Academy) | Event-basierte Inter-Modul-Kommunikation | Mittel |
| **Observability** | ActivityLogger in DB, kein Correlation-ID | Structured Logging, Correlation-IDs, Metrics, Tracing | Hoch |
| **Database Adapter** | DatabaseAdapter Interface + WP-Implementierung existiert | Vollständige Nutzung in allen Modulen, PDO-Adapter für SaaS | Mittel |
| **Sync Layer** | Nicht vorhanden | Outbox/Inbox, Change Feed, Conflict Resolution, Idempotency | Neu |
| **Entity Versioning** | Kein `version`-Feld auf Entities | Auto-increment version + updated_at für Sync | Mittel |
| **Feature Flags** | Lizenz-basiertes Feature-Gating (LicenseManager) | + Generisches Feature-Flag-System (pro Tenant/User/%) | Niedrig |
| **Clock Abstraction** | `current_time('mysql')` (WP-Lokalzeit), `DateTimeImmutable` (UTC) | ClockPort mit konsistenter UTC-Nutzung überall | Niedrig |

---

## 2. Prioritized Roadmap

### Tier 1: Quick Wins (sicher, niedriges Risiko, sofort umsetzbar)

| # | Verbesserung | Nutzen | Risiko | Verifikation | Migration |
|---|-------------|--------|--------|-------------|-----------|
| QW-1 | **Queue-Jobs: tenant_id im Payload erzwingen** | Beseitigt T3 (falscher Tenant in Background-Jobs) | Niedrig | Test: Job ohne tenant_id → Rejection. Job mit tenant_id → korrekte Isolation. | Worker-Code: `TenantManager::setCurrentTenantId($payload['tenant_id'])` vor Ausführung. QueueManager::enqueue() validiert Payload. |
| QW-2 | **Cache-Keys: Tenant-Prefix** | Beseitigt T5 (Cache-Kollisionen) | Niedrig | Test: Set Tenant A, Get Tenant B → null. | Wrapper-Funktion: `"{$tenantId}:{$key}"`. Alle Transient-Aufrufe durchsuchen und migrieren. |
| QW-3 | **Gate::isSelf() mit Tenant-Scope** | Beseitigt A6 (Cross-Tenant isSelf Bypass) | Niedrig | Test: isSelf() über Tenant-Grenzen → false. | SQL um `AND tenant_id = %d` erweitern in `Gate.php:270-276`. |
| QW-4 | **Read-Zugriff auf Modul-Capability einschränken** | Beseitigt A4 (jeder Loginuser kann alles lesen) | Niedrig | Test: User ohne `view_bookando_X` → 403 bei Read. | `Gate.php:257`: `current_user_can('read')` ersetzen durch `current_user_can('view_bookando_' . $module)`. |
| QW-5 | **BaseModel::now() → UTC** | Beseitigt Z4 (inkonsistente Timestamps) | Niedrig | Test: `now()` gibt UTC zurück. | `return gmdate('Y-m-d H:i:s')` statt `current_time('mysql')`. |
| QW-6 | **API-Key Permissions enforcing** | Beseitigt A5 (API-Key hat volle User-Rechte) | Niedrig | Test: API-Key mit `read-only` → Write → 403. | `AuthMiddleware`: nach API-Key-Auth die `permissions` aus DB lesen und in Gate-Context setzen. |

### Tier 2: Medium Scope (moderat komplex, gezielter Aufwand)

| # | Verbesserung | Nutzen | Risiko | Verifikation | Migration |
|---|-------------|--------|--------|-------------|-----------|
| MS-1 | **Money Value Object einführen** | Beseitigt M1 (Float-Precision). Zentrale Rundung. | Mittel | Unit-Tests: Alle Arithmetik-Operationen. Integration: Invoice-Berechnung penny-exact. | 1) Value Object erstellen. 2) Finance-Modul schrittweise migrieren (Feature-Flag). 3) Alte Float-Berechnung als Fallback. |
| MS-2 | **Webhook Idempotency-Key-Tracking** | Beseitigt M3 (doppelte Webhook-Verarbeitung) | Mittel | Test: Gleicher Webhook 2x → gleicher Zustand. | Neue Tabelle `bookando_idempotency_keys`. Lookup vor Processing. TTL 24h. |
| MS-3 | **PayPal + Klarna Webhook-Signatur** | Beseitigt M8/A7 (unsichere Webhooks) | Mittel | Test: Ungültige Signatur → 403. | PayPal: SDK-basierte Verifizierung implementieren. Klarna: HMAC oder IP-Whitelist. |
| MS-4 | **Structured Logging + Correlation-IDs** | Ermöglicht Request-Tracing | Mittel | Test: Alle Logs eines Requests haben gleiche Correlation-ID. | LoggerPort Interface. Middleware generiert ID. Logger injiziert ID in jeden Eintrag. |
| MS-5 | **DI-Container konsequent nutzen** | Testbarkeit, Austauschbarkeit | Mittel | Test: Jeder Service via Container auflösbar. Keine `new` außerhalb Provider. | ServiceProvider für jedes Modul erstellen. Statische Aufrufe schrittweise ersetzen. |
| MS-6 | **Event Bus für Inter-Modul-Kommunikation** | Beseitigt W4 (direkte Module-Kopplung) | Mittel | Test: Academy::FinanceIntegration nutzt Event statt direkten Import. | 1) EventBusPort erstellen. 2) Events definieren. 3) Academy↔Finance über Events entkoppeln. 4) Offers↔Academy über Events entkoppeln. |
| MS-7 | **Entity-Versioning (version + updated_at)** | Basis für Sync und Conflict Detection | Mittel | Test: update() inkrementiert version. | Migration: `ALTER TABLE ADD COLUMN version INT DEFAULT 1`. BaseModel::update() inkrementiert automatisch. |
| MS-8 | **Invoice-Immutability enforcing** | Beseitigt MC-3 (editierbare ausgestellte Rechnungen) | Niedrig | Test: Update auf issued Invoice → Exception. | Status-Guard in StateRepository::update(). Corrections nur via Credit Note API. |

### Tier 3: High Impact Redesigns (nur wenn gerechtfertigt)

| # | Verbesserung | Nutzen | Risiko | Verifikation | Migration |
|---|-------------|--------|--------|-------------|-----------|
| HI-1 | **Anti-Double-Booking mit transaktionalem Lock** | Beseitigt Z2/Z7 (Race Condition bei Buchungen) | Hoch | Concurrent-Test: 2 gleichzeitige Buchungen → eine scheitert. | DB-Transaktion + `SELECT ... FOR UPDATE` auf Zeitslot. Oder: UNIQUE INDEX auf (employee_id, starts_at_utc). Feature-Flag für Rollout. |
| HI-2 | **Kernel-Extraktion (Ports & Adapters)** | Host-agnostischer Kern für SaaS + WP + PWA | Hoch | Test: Kernel läuft mit InMemory-Adaptern (kein WP nötig). | Schrittweise: 1) Ports definieren. 2) WP-Adapter als erste Implementierung. 3) PDO-Adapter für SaaS. Feature-Flag pro Adapter. |
| HI-3 | **Sync Layer (Outbox/Inbox/Change Feed)** | PWA-Offline + WP-Connected-Mode | Hoch | Test: Offline-Mutation → Online-Sync → Server korrekt. Conflict Resolution per Entity-Typ. | Neues Subsystem. Erst für PWA (einfachster Case), dann WP Connected Mode. |
| HI-4 | **Migrations-System ersetzen** | Beseitigt D1/D2 (dbDelta Limitationen, kein Rollback) | Mittel | Test: Forward + Rollback Migration. Idempotente Re-Runs. | Eigener Migrator mit up()/down(), Schema-Versioning-Tabelle, Lock-Mechanismus. |
| HI-5 | **RestDispatcher aufbrechen** | Beseitigt W1 (1173-Zeilen God-Class) | Mittel | Test: Jedes Modul registriert eigene Routes via BaseApi. | Schrittweise Route-Registration in Module-Api-Klassen verlagern. RestDispatcher wird zu dünnem Router. |

---

## 3. Empfohlene Reihenfolge

### Sprint 1 (Quick Wins — 1–2 Wochen)
1. QW-1: Queue tenant_id Enforcement
2. QW-2: Cache Tenant-Prefix
3. QW-3: isSelf() Tenant-Scope
4. QW-4: Read-Zugriff Einschränkung
5. QW-5: BaseModel::now() → UTC
6. QW-6: API-Key Permission Enforcement

### Sprint 2 (Money + Webhooks — 2–3 Wochen)
1. MS-1: Money Value Object
2. MS-2: Webhook Idempotency
3. MS-3: PayPal + Klarna Signatur
4. MS-8: Invoice Immutability

### Sprint 3 (Observability + DI — 2–3 Wochen)
1. MS-4: Structured Logging + Correlation-IDs
2. MS-5: DI-Container konsistent
3. MS-6: Event Bus
4. MS-7: Entity Versioning

### Sprint 4+ (Foundation-Architektur — 4–8 Wochen)
1. HI-1: Anti-Double-Booking
2. HI-2: Kernel-Extraktion (inkrementell)
3. HI-4: Migrations-System
4. HI-5: RestDispatcher aufbrechen

### Sprint N (Sync Layer — 4–6 Wochen)
1. HI-3: Sync Layer Design + Implementierung

---

## 4. Migrations-Prinzipien

Für jede Verbesserung:

1. **Feature-Flag**: Neuer Code hinter Flag, alter Code als Fallback
2. **Backward-Compatibility**: Keine Breaking Changes an bestehenden APIs
3. **Staged Rollout**: Dev → Staging → Canary (5%) → Full
4. **Rollback-Plan**: Flag deaktivieren = sofortiger Rollback
5. **Daten-Migration**: Additive Schema-Änderungen (neue Spalten, keine Drops)
6. **Monitoring**: Metriken für alten vs. neuen Code-Pfad vergleichen
