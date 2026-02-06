# Phase 7 — Agent Verification Plan

> 6 spezialisierte Missionen für Verifikations-Agenten.
> Jeder Agent soll versuchen, die Foundation zu WIDERLEGEN und Gegenbeispiele zu finden.

---

## Mission 1: Tenant Isolation Red Team

### Ziel
Beweise, dass Tenant-Isolation in JEDEM Code-Pfad durchgesetzt wird — oder finde Bypasses.

### Scope (exakte Dateien)
```
Primary:
  bookando WP/src/Core/Model/BaseModel.php
  bookando WP/src/Core/Model/Traits/MultiTenantTrait.php
  bookando WP/src/Core/Tenant/TenantManager.php
  bookando WP/src/Core/Tenant/TenantProvisioner.php
  bookando WP/src/Core/Queue/QueueManager.php
  bookando WP/src/Core/Sharing/ShareService.php
  bookando WP/src/Core/Service/ActivityLogger.php

Secondary (alle Stellen die $wpdb direkt nutzen):
  bookando WP/src/Core/Auth/Gate.php (isSelf, Zeile 270-276)
  bookando WP/src/Core/Service/OAuthTokenStorage.php
  bookando WP/src/Core/Loader.php (updateApiKeyUsage, Zeile 76-85)
  bookando WP/src/modules/*/RestHandler.php (alle Module)
  bookando WP/src/modules/*/Model.php (alle Module)
  bookando WP/src/modules/*/*.php (grep für $wpdb)
```

### Attack-Hypothesen
1. **H1**: Finde eine DB-Query die BaseModel/MultiTenantTrait NICHT nutzt und keinen tenant_id Filter hat
2. **H2**: Finde einen Background-Job der ohne tenant_id ausgeführt wird
3. **H3**: Finde einen Cache-Key der nicht tenant-scoped ist und zu Cross-Tenant-Datenleaks führen kann
4. **H4**: Finde einen Export/Report/Aggregation-Endpunkt der Daten aus mehreren Tenants mischt
5. **H5**: Finde eine Stelle wo `fetchOneUnsafeNoScope()` ohne vorherige ACL-Prüfung aufgerufen wird
6. **H6**: Finde eine Stelle wo `runAsTenant()` den Kontext nicht korrekt zurücksetzt (Exception-Pfad)
7. **H7**: Finde einen REST-Endpoint der tenant_id aus dem Request-Body statt aus dem Auth-Kontext nimmt und damit Tenant-Escalation erlaubt

### Test Cases zu generieren
- `TenantIsolationTest::two_tenants_no_cross_read()`
- `TenantIsolationTest::insert_without_tenant_throws()`
- `TenantIsolationTest::queue_job_without_tenant_rejected()`
- `TenantIsolationTest::cache_key_tenant_scoped()`
- `TenantIsolationTest::run_as_tenant_restores_on_exception()`
- `TenantIsolationTest::export_only_current_tenant_data()`
- `TenantIsolationTest::is_self_respects_tenant_boundary()`
- `TenantIsolationTest::webhook_resolves_tenant_from_payment_not_request()`

### Acceptance Criteria
- ALLE direkten $wpdb-Nutzungen dokumentiert und als safe/unsafe klassifiziert
- Kein Code-Pfad gefunden der Cross-Tenant-Reads ohne ACL ermöglicht
- Tests für alle gefundenen Risiko-Stellen erstellt
- Queue-Job-Tenant-Propagation verifiziert oder als Gap markiert

### Budget: **MITTEL** (4-6 Stunden Agent-Zeit)

---

## Mission 2: Authz Enforcement Red Team

### Ziel
Beweise, dass jeder API-Endpoint serverseitig autorisiert ist — oder finde Bypasses.

### Scope
```
Primary:
  bookando WP/src/Core/Auth/Gate.php
  bookando WP/src/Core/Auth/AuthMiddleware.php
  bookando WP/src/Core/Auth/JWTService.php
  bookando WP/src/Core/Dispatcher/RestDispatcher.php
  bookando WP/src/Core/Dispatcher/RestModuleGuard.php
  bookando WP/src/Core/Dispatcher/RestPermissions.php
  bookando WP/src/Core/Dispatcher/WebhookDispatcher.php
  bookando WP/src/Core/Role/CapabilityService.php

Secondary:
  bookando WP/src/modules/*/Api.php (alle Module)
  bookando WP/src/modules/*/RestHandler.php (alle Module)
  bookando WP/src/modules/Employees/Handlers/EmployeeAuthorizationGuard.php
```

### Attack-Hypothesen
1. **H1**: Finde einen REST-Endpoint ohne permission_callback (oder mit `__return_true`)
2. **H2**: Finde einen Write-Endpoint der keine Nonce/CSRF-Prüfung hat
3. **H3**: Finde eine Stelle wo UI-Only-Checks (Vue/JS) die einzige Berechtigung sind
4. **H4**: Finde einen Pfad wo devBypass in Production funktionieren könnte
5. **H5**: Finde einen API-Key-authentifizierten Request der mehr darf als die API-Key Permissions erlauben
6. **H6**: Finde einen Endpoint wo ein Employee Daten anderer Employees lesen/schreiben kann (IDOR)
7. **H7**: Finde einen Public-Endpoint (Webhook/Health/Auth) der unbeabsichtigt Daten preisgibt
8. **H8**: Finde einen Pfad wo Rate-Limiting umgangen werden kann (z.B. durch Header-Manipulation)

### Test Cases zu generieren
- `AuthzTest::every_rest_route_has_permission_callback()` (enumerate all routes)
- `AuthzTest::write_without_nonce_rejected()`
- `AuthzTest::anonymous_access_to_protected_endpoint_rejected()`
- `AuthzTest::dev_bypass_disabled_in_production()`
- `AuthzTest::api_key_read_only_rejects_write()`
- `AuthzTest::employee_cannot_read_other_employee_data()`
- `AuthzTest::customer_cannot_escalate_to_admin()`
- `AuthzTest::rate_limit_blocks_after_threshold()`
- `AuthzTest::jwt_expired_rejected()`
- `AuthzTest::jwt_revoked_rejected()`

### Acceptance Criteria
- Alle REST-Routen mit permission_callback inventarisiert
- Kein Endpoint ohne serverseitige Auth gefunden (außer explizit öffentliche)
- IDOR-Versuche für alle CRUD-Endpoints getestet
- API-Key Permission Enforcement verifiziert oder als Gap markiert

### Budget: **MITTEL** (4-6 Stunden)

---

## Mission 3: Money/Accounting Correctness Verifier

### Ziel
Beweise, dass Geldberechnungen korrekt sind — oder finde Precision-Fehler, Rounding-Bugs, Reconciliation-Gaps.

### Scope
```
Primary:
  bookando WP/src/modules/Finance/Gateways/AbstractGateway.php (formatAmount/parseAmount)
  bookando WP/src/modules/Finance/Gateways/Stripe/StripeGateway.php
  bookando WP/src/modules/Finance/Gateways/PayPal/PayPalGateway.php
  bookando WP/src/modules/Finance/Gateways/Mollie/MollieGateway.php
  bookando WP/src/modules/Finance/Gateways/Klarna/KlarnaGateway.php
  bookando WP/src/modules/Finance/Gateways/Twint/TwintGateway.php
  bookando WP/src/modules/Finance/PaymentWebhookHandler.php
  bookando WP/src/modules/Finance/PaymentRestHandler.php
  bookando WP/src/modules/Finance/StateRepository.php
  bookando WP/src/modules/Finance/RestHandler.php

Secondary:
  bookando WP/src/modules/Academy/FinanceIntegration.php
```

### Attack-Hypothesen
1. **H1**: Finde eine Stelle wo Float-Arithmetik für Geldberechnung genutzt wird (round, +, -, *)
2. **H2**: Berechne einen Fall wo die Rechnungssumme nicht der Summe der Einzelpositionen entspricht (Penny-Rounding-Error)
3. **H3**: Finde einen Webhook-Pfad der ohne Idempotency-Check doppelt verarbeitet werden kann
4. **H4**: Finde einen Refund-Pfad der den Original-Betrag überschreiten kann
5. **H5**: Finde eine Stelle wo Zero-Decimal-Currencies (JPY, KRW) falsch behandelt werden
6. **H6**: Finde einen Gateway ohne Webhook-Signaturverifikation
7. **H7**: Finde einen Reconciliation-Gap (Payment bei Gateway, aber nicht in DB, oder umgekehrt)
8. **H8**: Berechne einen konkreten Fall wo `0.1 + 0.2 != 0.3` zu einem falschen Rechnungsbetrag führt

### Test Cases zu generieren
- `MoneyTest::float_precision_attack()` (100 Items à 0.10 → erwartet 10.00)
- `MoneyTest::format_amount_all_currencies()` (Standard + Zero-Decimal)
- `MoneyTest::refund_exceeds_original_rejected()`
- `MoneyTest::partial_refunds_sum_does_not_exceed_original()`
- `WebhookTest::duplicate_event_idempotent()`
- `WebhookTest::invalid_signature_rejected_per_gateway()`
- `InvoiceTest::total_equals_line_items_plus_tax()`
- `InvoiceTest::credit_note_references_original()`
- `ReconciliationTest::payment_at_gateway_exists_in_db()`

### Acceptance Criteria
- Alle Float-Nutzungen in Money-Pfaden dokumentiert
- Konkreter Penny-Rounding-Error reproduziert ODER bewiesene Korrektheit
- Webhook-Signatur-Status pro Gateway dokumentiert (OK/FEHLT)
- Idempotency-Status dokumentiert (OK/FEHLT)

### Budget: **HOCH** (6-8 Stunden — höchstes Risiko)

---

## Mission 4: Scheduling/Time/DST Verifier

### Ziel
Beweise, dass Zeitberechnungen DST-sicher sind und Doppelbuchungen verhindert werden — oder finde Bugs.

### Scope
```
Primary:
  bookando WP/src/modules/Appointments/Model.php
  bookando WP/src/modules/Appointments/RestHandler.php
  bookando WP/src/modules/Workday/Services/ShiftService.php
  bookando WP/src/modules/Workday/Services/DutySchedulerService.php
  bookando WP/src/modules/Workday/Services/VacationRequestService.php
  bookando WP/src/modules/Offers/CalendarViewController.php
  bookando WP/src/Core/Model/BaseModel.php (now() Methode)
  bookando WP/src/Core/Integrations/Calendar/GoogleCalendarSync.php

Secondary:
  bookando WP/src/modules/Tools/Services/SchedulerService.php
  bookando WP/src/modules/Tools/Services/CoursePlanningService.php
```

### Attack-Hypothesen
1. **H1**: Erstelle einen Termin am DST-Wechseltag (Europe/Zurich, letzter Sonntag im März um 02:00) und zeige dass die Dauer falsch berechnet wird
2. **H2**: Buche zwei Termine für den gleichen Slot gleichzeitig und zeige dass beide durchgehen (Race Condition)
3. **H3**: Finde eine Zeitberechnung die `current_time('mysql')` (WP-Lokalzeit) statt UTC nutzt
4. **H4**: Finde eine Schicht die über Mitternacht geht und zeige dass die Dauer falsch ist
5. **H5**: Zeige dass Recurring-Patterns am DST-Boundary falsche Instanzen produzieren
6. **H6**: Finde eine Stelle wo Zeitzonen-Offset statt IANA-Timezone-Name (Europe/Zurich) gespeichert wird
7. **H7**: Erstelle einen Termin in Timezone A und zeige ihn in Timezone B — sind die Zeiten korrekt?
8. **H8**: Prüfe ob die 11-Stunden-Ruhezeit-Berechnung am DST-Tag (23h oder 25h) korrekt ist

### Test Cases zu generieren
- `DSTTest::appointment_during_spring_forward()` (Europe/Zurich 2026-03-29 02:00)
- `DSTTest::appointment_during_fall_back()` (Europe/Zurich 2026-10-25 02:00)
- `DSTTest::shift_duration_on_23h_day()` (Spring forward: 8h-Schicht = 7h effektiv)
- `DSTTest::rest_period_on_25h_day()` (Fall back: 11h = 12h Wanduhrzeit)
- `OverlapTest::concurrent_booking_race_condition()`
- `OverlapTest::all_seven_overlap_configurations()`
- `TimezoneTest::store_utc_display_local()`
- `TimezoneTest::multi_timezone_availability()`
- `RecurringTest::weekly_pattern_across_dst_boundary()`

### Acceptance Criteria
- Konkrete DST-Bugs reproduziert ODER DST-Korrektheit bewiesen
- TOCTOU-Race-Condition für Buchungen reproduziert und dokumentiert
- Alle Zeitberechnungen als UTC-korrekt/inkorrekt klassifiziert
- `current_time('mysql')` Nutzung in zeitkritischen Pfaden dokumentiert

### Budget: **MITTEL** (4-6 Stunden)

---

## Mission 5: Sync Safety & Conflict Verifier

### Ziel
Verifiziere, dass die geplante Sync-Architektur (Outbox/Inbox/Change Feed) korrekt und sicher ist — oder finde Lücken im Design.

### Scope
```
Primary (Designdokumente):
  docs/foundation/07_sync_spec.md
  docs/foundation/08_invariants.md (Section 5: Sync Safety)

Referenz-Code (bestehende Patterns):
  bookando WP/src/Core/Queue/QueueManager.php (als Outbox-Analogie)
  bookando WP/src/Core/Sharing/ShareService.php (Cross-Tenant-Muster)
  bookando WP/src/Core/Model/BaseModel.php (Concurrency-Handling)
  bookando WP/src/modules/Finance/PaymentWebhookHandler.php (Idempotency-Analogie)
```

### Attack-Hypothesen
1. **H1**: Zeige ein Szenario wo zwei offline Clients gleichzeitig denselben Appointment bearbeiten und der Merge inkonsistente Daten erzeugt
2. **H2**: Zeige ein Szenario wo ein offline erstellter Payment-Record nach Sync einen falschen Zustand erzeugt
3. **H3**: Zeige ein Szenario wo Outbox-Reordering zu einem logisch unmöglichen Zustand führt (z.B. Update vor Create)
4. **H4**: Zeige ein Szenario wo Resync + pending Outbox zu Datenverlust führt
5. **H5**: Zeige ein Szenario wo Idempotency-Key-TTL abläuft und ein Retry einen Duplikat erzeugt
6. **H6**: Zeige ein Szenario wo Change-Feed-Cursor-Inkonsistenz zu fehlenden Updates führt
7. **H7**: Prüfe ob Last-Writer-Wins für Customer-Daten zu Datenverlust führen kann (gleichzeitige Edits verschiedener Felder)

### Test Cases zu generieren (Design-Reviews / Gedankenexperimente)
- `SyncConflictTest::concurrent_appointment_edit_resolved()`
- `SyncConflictTest::money_entity_always_server_wins()`
- `SyncConflictTest::outbox_fifo_maintained()`
- `SyncConflictTest::resync_preserves_pending_outbox()`
- `SyncConflictTest::idempotency_key_expiry_handling()`
- `SyncConflictTest::cursor_gap_detection()`
- `SyncConflictTest::field_level_merge_no_data_loss()`

### Acceptance Criteria
- Alle Conflict-Szenarien pro Entity-Typ durchgespielt
- Money-Flows: bewiesen dass KEIN silent destructive merge möglich ist
- Outbox-Reihenfolge: bewiesen oder als Risiko markiert
- Design-Lücken identifiziert und Fixes vorgeschlagen

### Budget: **NIEDRIG** (2-4 Stunden — Design-Review, kein Code)

---

## Mission 6: Integration Reliability Verifier

### Ziel
Verifiziere die Zuverlässigkeit aller externen Integrationen — oder finde Failure-Modes die nicht behandelt werden.

### Scope
```
Primary:
  bookando WP/src/Core/Integrations/Calendar/GoogleCalendarSync.php
  bookando WP/src/Core/Integrations/Calendar/AppleCalendarSync.php
  bookando WP/src/Core/Integrations/Calendar/MicrosoftCalendarSync.php
  bookando WP/src/Core/Integrations/VideoConference/ZoomIntegration.php
  bookando WP/src/Core/Integrations/VideoConference/GoogleMeetIntegration.php
  bookando WP/src/Core/Service/OAuthTokenStorage.php
  bookando WP/src/modules/Finance/Gateways/*.php (alle 5 Gateways)
  bookando WP/src/modules/Finance/PaymentWebhookHandler.php
  bookando WP/src/Core/Dispatcher/WebhookDispatcher.php
```

### Attack-Hypothesen
1. **H1**: Google Calendar API gibt 429 (Rate Limit) zurück — wird der Sync korrekt wiederversucht?
2. **H2**: OAuth Refresh Token ist expired — wird der User informiert und zur Re-Autorisierung aufgefordert?
3. **H3**: Stripe Webhook kommt 3x innerhalb von 1 Sekunde — werden alle 3 korrekt idempotent verarbeitet?
4. **H4**: Microsoft Graph API ist 5 Minuten down — gehen Events verloren oder werden sie nachgeholt?
5. **H5**: Payment-Gateway gibt 500 zurück während createPayment() — wird das dem User korrekt kommuniziert?
6. **H6**: OAuth Token wird im Transit abgefangen — ist der Token sicher verschlüsselt in der DB?
7. **H7**: Webhook-Endpoint wird mit gefälschtem Payload aufgerufen — alle Gateways verifizieren Signatur?
8. **H8**: Zwei gleichzeitige Token-Refreshes für denselben Employee — Race Condition?

### Test Cases zu generieren
- `CalendarReliabilityTest::google_rate_limit_retry()`
- `CalendarReliabilityTest::expired_refresh_token_handling()`
- `CalendarReliabilityTest::api_timeout_handling()`
- `CalendarReliabilityTest::concurrent_token_refresh()`
- `PaymentReliabilityTest::gateway_500_error_propagated()`
- `PaymentReliabilityTest::webhook_signature_per_gateway()`
- `PaymentReliabilityTest::webhook_replay_idempotent()`
- `SecurityTest::oauth_token_encrypted_at_rest()`
- `SecurityTest::webhook_forged_payload_rejected()`

### Acceptance Criteria
- Jede externe Integration: Fehler-Handling dokumentiert (Retry/Fail/Compensate)
- Webhook-Signatur-Status pro Gateway: OK/FEHLT/STUB
- Token-Sicherheit verifiziert (AES-256-GCM, kein Plaintext)
- Rate-Limiting und Retry-Verhalten dokumentiert pro Integration

### Budget: **MITTEL** (4-6 Stunden)

---

## Budget-Zusammenfassung

| Mission | Budget | Priorität | Begründung |
|---------|--------|-----------|------------|
| 1: Tenant Isolation | Mittel (4-6h) | P0 | Fundamentale Sicherheit |
| 2: Authz Enforcement | Mittel (4-6h) | P0 | Fundamentale Sicherheit |
| 3: Money Correctness | Hoch (6-8h) | P0 | Höchstes Geschäftsrisiko |
| 4: Time/DST | Mittel (4-6h) | P1 | Funktionale Korrektheit |
| 5: Sync Safety | Niedrig (2-4h) | P2 | Design-Phase, kein Code |
| 6: Integration Reliability | Mittel (4-6h) | P1 | Produktionsstabilität |

**Gesamt: 24-36 Stunden Agent-Zeit**

---

## Allgemeine Anweisungen für Verifikations-Agenten

1. **Hypothesen-getrieben**: Versuche aktiv zu WIDERLEGEN, nicht zu bestätigen
2. **Code > Docs**: Wenn Dokumentation und Code sich widersprechen, ist der Code die Wahrheit
3. **Konkrete Beispiele**: Für jeden gefundenen Bug: exakter Input, erwarteter Output, tatsächlicher Output
4. **Tests generieren**: Für jeden bestätigten Bug einen reproduzierbaren Test schreiben
5. **False Positives markieren**: Wenn eine Hypothese nicht bestätigt wird, dokumentiere warum
6. **Severity bewerten**: Critical (Datenverlust/Sicherheit), High (falsche Berechnung), Medium (Edge-Case), Low (Cosmetic)
7. **Keine Code-Änderungen**: Nur Analyse + Test-Generierung. Kein Produktionscode ändern.
