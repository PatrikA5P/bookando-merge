# Phase 3 — Risk Register

> Risikobewertung basierend auf verifizierter Codeanalyse.
> Kategorien: **SAFE** (gut implementiert), **RISKY** (funktioniert, aber fragil), **FLAWED** (aktiver Defekt/Lücke).

---

## 1. Tenant-Isolation

| # | Risiko | Bewertung | Code-Referenz | Beschreibung |
|---|--------|-----------|---------------|--------------|
| T1 | Automatische Tenant-Scoping in BaseModel | **SAFE** | `src/Core/Model/Traits/MultiTenantTrait.php:9-21` | `applyTenant()` wrapping jeder SELECT-Query als Subquery mit `WHERE t.tenant_id = %d`. Harte Exception bei fehlendem Tenant. |
| T2 | Insert/Update/Delete mit Tenant-Erzwingung | **SAFE** | `src/Core/Model/BaseModel.php:190-256` | `insert()` setzt auto `tenant_id`, `update()`/`delete()` filtern auf `(id, tenant_id)`. `tenant_id`-Mutation in `update()` explizit verhindert (unset). |
| T3 | Background-Jobs (Queue) ohne Tenant-Propagation | **FLAWED** | `src/Core/Queue/QueueManager.php` | Jobs tragen keinen `tenant_id` im Payload. Im Cron-Kontext fällt `TenantManager::currentTenantId()` auf Default (1) zurück. Alle Job-Operationen laufen dann unter falschem Tenant. |
| T4 | `fetchOneUnsafeNoScope()` Bypass | **RISKY** | `src/Core/Model/BaseModel.php:310-315` | Existiert für ShareService/Diagnose. Kommentar warnt vor ACL-Prüfung, aber keine programmatische Erzwingung. Jede Subklasse kann es unsicher aufrufen. |
| T5 | Transient/Cache ohne Tenant-Prefix | **RISKY** | Diverse: `RateLimitMiddleware`, `JWTService`, Calendar-Token-Cache | WP-Transients sind global, nicht tenant-scoped. Key-Kollisionen möglich, wenn gleiche Keys für verschiedene Tenants verwendet werden (z.B. Rate-Limit-Keys basieren auf User-ID, nicht Tenant+User). |
| T6 | Direkte `$wpdb`-Queries ohne BaseModel | **RISKY** | `src/Core/Auth/Gate.php:270-276`, `src/Core/Tenant/TenantManager.php:396-404`, `src/Core/Service/OAuthTokenStorage.php` | Mehrere Stellen nutzen `$wpdb` direkt. Dabei wird kein automatischer Tenant-Filter angewendet. Gate::isSelf() greift auf `bookando_users` ohne Tenant-Scope zu. |
| T7 | Shared Tenants Option | **RISKY** | `src/Core/Tenant/TenantManager.php:70-84` | `bookando_shared_tenants` erlaubt Cross-Tenant-Reads per Konfiguration. Fehlkonfiguration = Datenleck. Keine Validierung, dass geteilte Tenants tatsächlich Partner sind. |
| T8 | `runAsTenant()` temporärer Kontextwechsel | **RISKY** | `src/Core/Model/BaseModel.php:295-304` | try/finally sichert Rücksetzung ab. Aber: wenn innerhalb des Callbacks eine Exception auftritt, die vor dem finally escaped (z.B. Fatal Error), bleibt falscher Tenant gesetzt. |
| T9 | Export/Report-Flows | **RISKY** | `src/modules/Finance/StateRepository.php` (Ledger-Export) | Ledger-Export nutzt StateRepository, der BaseModel verwendet → tenant-scoped. Aber: keine explizite Prüfung, dass Export-Empfänger = aktueller Tenant. |

---

## 2. Authz-Bypass-Risiken

| # | Risiko | Bewertung | Code-Referenz | Beschreibung |
|---|--------|-----------|---------------|--------------|
| A1 | Gate::evaluate() als zentraler Checkpoint | **SAFE** | `src/Core/Auth/Gate.php:189-266` | Prüft Login, Tenant, Nonce (für Writes), Lizenz-Feature, Modul-Capability. Klare Fehlercodes. |
| A2 | Dev-Bypass mit Audit-Logging | **SAFE** | `src/Core/Auth/Gate.php:22-50` | Nur in Non-Production + BOOKANDO_DEV + manage_options. Default ist 'production' wenn WP_ENVIRONMENT_TYPE nicht gesetzt. Audit-Log mit Backtrace. |
| A3 | Modul-Boot ohne Lizenz-Check | **FLAWED** | `src/Core/Loader.php:142-170`, `src/Core/Base/BaseModule.php:29-33` | Module werden geladen und gebootet auch wenn Lizenz sie nicht erlaubt. `LicenseManager::isModuleAllowed()` wird nur in UI (`buildModuleVars`) und REST-Guard geprüft. Module-Code (Crons, Hooks) läuft trotzdem. |
| A4 | Read-Berechtigung mit `can('read')` | **RISKY** | `src/Core/Auth/Gate.php:257` | Lesezugriff erlaubt für `canManage($module) || current_user_can('read')`. `read` ist eine Standard-WP-Capability, die fast jeder Benutzer hat. Effektiv: jeder eingeloggte User kann alle Module lesen. |
| A5 | API-Key Permission-Feld nicht durchgesetzt | **FLAWED** | `src/Core/Auth/AuthMiddleware.php` | API-Keys haben ein `permissions` JSON-Feld in der DB, aber es wird nirgends in der Berechtigungskette geprüft. Ein API-Key hat effektiv gleiche Berechtigungen wie der zugeordnete User. |
| A6 | Self-Access via Gate::isSelf() | **RISKY** | `src/Core/Auth/Gate.php:268-277` | Prüft `external_id` in `bookando_users` → vergleicht mit `get_current_user_id()`. Kein Tenant-Scope auf die Query. Theoretisch könnte ein User in Tenant A eine `bookando_users`-ID aus Tenant B matchen. |
| A7 | Webhook-Endpoints ohne Auth | **RISKY** | `src/modules/Finance/PaymentWebhookHandler.php` | Webhook-URLs sind öffentlich. Sicherheit hängt von Signaturverifikation ab. PayPal-Signatur ist nicht implementiert (TODO). Klarna hat keine Signaturverifikation. |
| A8 | Bulk-Operationen ohne Per-Record-Check | **RISKY** | `src/modules/Employees/Handlers/BulkEmployeeHandler.php` | Bulk-Handler prüfen Modul-Berechtigung einmal, aber nicht pro Datensatz. Erlaubt potenziell Operationen auf Records außerhalb des erlaubten Scopes. |

---

## 3. Geld-/Korrektheit-Risiken

| # | Risiko | Bewertung | Code-Referenz | Beschreibung |
|---|--------|-----------|---------------|--------------|
| M1 | Float-Arithmetik in Invoice-Berechnungen | **FLAWED** | `src/modules/Finance/StateRepository.php` (`recalculateTotals()`) | `$subtotal += $item['total']` und `round($subtotal, 2)` — PHP-Floats haben binäre Rundungsfehler. Bei vielen Positionen akkumulieren sich Abweichungen. **MUSS integer-basiert (Minor Units/Cents) sein.** |
| M2 | Gateway formatAmount()/parseAmount() | **SAFE** | `src/modules/Finance/Gateways/AbstractGateway.php` | Korrekt: `(int) round($amount * 100)` für Standard-Währungen, spezielle Handling für Zero-Decimal-Currencies (JPY, KRW etc.). |
| M3 | Keine Idempotenz bei Webhook-Verarbeitung | **FLAWED** | `src/modules/Finance/PaymentWebhookHandler.php` | Kein Tracking verarbeiteter Webhook-IDs. Doppelte Zustellung = doppelte Seiteneffekte (Notifications, Status-Updates die eigentlich idempotent sein sollten, aber Hooks feuern mehrfach). |
| M4 | Klarna Refund nur Stub | **FLAWED** | `src/modules/Finance/Gateways/Klarna/KlarnaGateway.php` | `refundPayment()` gibt `['refund_id' => uniqid(), 'status' => 'pending']` zurück — keine echte Rückerstattung. Täuscht Erfolg vor. |
| M5 | Keine Reconciliation zwischen Gateway und DB | **FLAWED** | Finance-Modul generell | Wenn `createPayment()` am Gateway erfolgreich ist, aber die lokale DB-Speicherung fehlschlägt, gibt es keine automatische Abgleichung. Zahlungen gehen verloren. |
| M6 | Invoice-Total vs. Payment-Betrag nicht verknüpft | **RISKY** | Kein Link zwischen `invoices` und `payments` | Keine automatische Prüfung, ob bezahlter Betrag = Rechnungsbetrag. |
| M7 | Tax-Rate ohne Versioning | **RISKY** | `src/modules/Finance/StateRepository.php` | `tax_rate` pro Line-Item gespeichert, aber keine Zeitbindung. Wenn sich der Steuersatz ändert, gelten bestehende Rechnungen mit dem alten Satz — korrekt. Aber Nachberechnung bei Änderung ist nicht geschützt. |
| M8 | PayPal Webhook-Signatur nicht implementiert | **FLAWED** | `src/modules/Finance/Gateways/PayPal/PayPalGateway.php` (TODO-Kommentar) | Angreifer könnten gefälschte PayPal-Webhooks senden und Zahlungsstatus manipulieren. |

---

## 4. Zeit-/Scheduling-Risiken

| # | Risiko | Bewertung | Code-Referenz | Beschreibung |
|---|--------|-----------|---------------|--------------|
| Z1 | UTC-Speicherung in Appointments | **SAFE** | `src/modules/Appointments/RestHandler.php`, `Model.php` | `starts_at_utc`, `ends_at_utc` gespeichert. `client_tz` als Referenz. `DateTimeImmutable` für Konvertierungen. |
| Z2 | Keine Doppelbuchungsprävention in Appointments | **FLAWED** | `src/modules/Appointments/Model.php` | `createAppointment()` hat keine Overlap-Prüfung. Nur Workday/ShiftService hat `detectConflicts()` — aber für Mitarbeiter-Schichten, nicht für Kundenbuchungen. |
| Z3 | Schicht-Konflikterkennung ohne DB-Lock | **RISKY** | `src/modules/Workday/Services/ShiftService.php` (`detectConflicts()`) | Application-Level Check (SELECT → Validate → INSERT), keine Transaktion, kein Row-Lock. Race-Condition bei gleichzeitigen Schichtzuweisungen. |
| Z4 | `current_time('mysql')` in BaseModel::now() | **RISKY** | `src/Core/Model/BaseModel.php:55-57` | `current_time('mysql')` gibt WP-Server-Lokalzeit zurück, nicht UTC. Wenn als `created_at` gespeichert, inkonsistent mit UTC-basierten `starts_at_utc` Feldern. |
| Z5 | Shift-Zeiten als lokale TIME-Felder | **RISKY** | `wp_bookando_shifts`: `start_time`, `end_time` (TIME) | Schichtzeiten ohne Timezone-Referenz gespeichert. Korrekt nur wenn alle Mitarbeiter in derselben Zeitzone sind. Für SaaS mit Multi-Timezone-Tenants problematisch. |
| Z6 | DST-Boundary in Recurring Patterns | **RISKY** | `offers.recurrence_pattern` (JSON), DutySchedulerService | Recurring-Patterns definieren Wiederholungen auf Basis von Wochentagen. Bei DST-Wechsel verschiebt sich die UTC-Entsprechung — nicht explizit behandelt. |
| Z7 | TOCTOU bei Verfügbarkeitsprüfung | **FLAWED** | `src/modules/Offers/CalendarViewController.php` | Verfügbarkeit wird als Read geprüft (`current_participants < max_participants`), aber nicht atomar mit der Buchung verknüpft. Zwei Clients können gleichzeitig den letzten Platz sehen und buchen. |

---

## 5. Integrations-Zuverlässigkeit

| # | Risiko | Bewertung | Code-Referenz | Beschreibung |
|---|--------|-----------|---------------|--------------|
| I1 | Kalender-Sync ist One-Way (Push only) | **RISKY** | `src/Core/Integrations/Calendar/GoogleCalendarSync.php` | Änderungen in Google Calendar werden nicht zurück zu Bookando synchronisiert. Dateninkonsistenz bei manueller Bearbeitung im Kalender. |
| I2 | Kein Retry/Backoff für Google/MS API-Calls | **FLAWED** | `GoogleCalendarSync`, `MicrosoftCalendarSync` | Bei API-Failure wird nur geloggt. Kein Queue-basierter Retry. Events gehen verloren. |
| I3 | Token-Refresh ohne Locking | **RISKY** | `OAuthTokenStorage`, Calendar-Sync-Klassen | Gleichzeitige Requests können parallele Token-Refreshes auslösen. Zweiter Refresh invalidiert den ersten Access-Token. |
| I4 | Apple Calendar nur ICS-Parsing | **SAFE** | `src/Core/Integrations/Calendar/AppleCalendarSync.php` | Korrekt als read-only implementiert. Kein Schreibzugriff → keine Sync-Konflikte. |
| I5 | Webhook-Endpoint-URL Sicherheit | **RISKY** | Finance Webhook-Endpoints | URLs enthalten Gateway-ID aber keine Secrets. Für Gateways ohne Signatur (Klarna, Mollie) → Endpoint-Discoverable. |

---

## 6. Wartbarkeit

| # | Risiko | Bewertung | Code-Referenz | Beschreibung |
|---|--------|-----------|---------------|--------------|
| W1 | RestDispatcher als God-Class | **RISKY** | `src/Core/Dispatcher/RestDispatcher.php` (1.173 Zeilen) | Registriert alle Routen, handelt OAuth, Sharing, Avatar-Upload, Employee-Subrouten. Zu viel Verantwortung in einer Klasse. |
| W2 | Statische Klassen statt DI | **RISKY** | `TenantManager`, `Gate`, `LicenseManager`, `ActivityLogger` — alles `static` | Erschwert Testing (Mocking), verhindert Austauschbarkeit, widerspricht PSR-11 Container-Ansatz. |
| W3 | Inkonsistenter Repository-Pattern | **RISKY** | Customers: Repository+Service+Validator. Employees: 16 Handler. Finance: StateRepository. | Kein einheitliches Pattern über Module hinweg. Neue Entwickler müssen jedes Modul individuell verstehen. |
| W4 | Module dürfen andere Module direkt referenzieren | **RISKY** | `src/modules/Academy/FinanceIntegration.php`, `src/modules/Offers/AcademyEnrollmentHandler.php` | Direkte Kopplung zwischen Modulen. Keine Event-basierte Kommunikation. Entfernung eines Moduls bricht andere. |
| W5 | DI-Container nur teilweise genutzt | **RISKY** | `src/Core/Providers/ServiceProvider.php:98-122` | Nur Customers-Modul vollständig via DI registriert. Andere Module nutzen `new` direkt oder statische Aufrufe. |
| W6 | Keine formale Modul-API-Boundary | **RISKY** | config/modules.php, BaseModule | Module haben kein explizites Interface, das ihre öffentliche API definiert. Alles ist intern erreichbar. |
| W7 | 149 Vue-Dateien ohne Shared Component Library | **RISKY** | 2 Core-Komponenten in Design/components, Rest in Modulen | Wenig Code-Reuse im Frontend. Jedes Modul baut eigene UI-Komponenten. DesignSystem-Modul existiert, wird aber wenig genutzt. |

---

## 7. Datenmigrations-Risiken

| # | Risiko | Bewertung | Code-Referenz | Beschreibung |
|---|--------|-----------|---------------|--------------|
| D1 | dbDelta() für Schema-Erstellung | **RISKY** | `src/Core/Installer.php` | WordPress `dbDelta()` ist unzuverlässig für komplexe Änderungen (keine DROP COLUMN, keine RENAME). Für SaaS-Migration ungeeignet. |
| D2 | Migrator ohne Rollback | **RISKY** | `src/Core/Database/Migrator.php` | Migrations laufen forward-only. Kein down()/rollback(). Fehler in Migration = manueller Eingriff nötig. |
| D3 | Unified Users Migration | **SAFE** | `database/migrations/migrate_to_unified_users.php` | Existiert und migriert separate Customers/Employees-Tabellen zu unified `bookando_users`. Gut dokumentiert. |
| D4 | Keine Schema-Versionierung | **RISKY** | Installer + Migrator | Kein formales Schema-Versioning (kein `schema_version` Feld). Migrator nutzt Nummern (002, 003, 004), aber kein Lock oder idempotente Re-Runs. |

---

## Zusammenfassung

| Kategorie | SAFE | RISKY | FLAWED |
|-----------|------|-------|--------|
| Tenant-Isolation | 2 | 6 | 1 |
| Authz | 2 | 4 | 2 |
| Geld/Korrektheit | 1 | 2 | 5 |
| Zeit/Scheduling | 1 | 4 | 2 |
| Integrationen | 1 | 3 | 1 |
| Wartbarkeit | 0 | 7 | 0 |
| Datenmigration | 1 | 3 | 0 |
| **Gesamt** | **8** | **29** | **11** |

### Top-5 Kritische Befunde (FLAWED)

1. **M1**: Float-Arithmetik in Rechnungsberechnungen — muss auf Integer (Minor Units) umgestellt werden
2. **M3**: Keine Webhook-Idempotenz — Doppelverarbeitung bei Replay
3. **Z2**: Keine Doppelbuchungsprävention für Appointments
4. **T3**: Queue-Jobs ohne Tenant-Propagation — falscher Tenant in Background-Tasks
5. **M8/A7**: PayPal-Webhook-Signatur nicht implementiert + Klarna ohne Signatur
