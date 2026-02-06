# Phase 4J — Observability, Operations & CI Gates

---

## 1. Structured Logging

### Ziel Log-Format (JSON Lines):

```json
{
  "timestamp": "2026-01-15T14:30:00.123Z",
  "level": "info",
  "message": "Appointment created",
  "correlation_id": "req-abc123",
  "tenant_id": 42,
  "user_id": 7,
  "module": "appointments",
  "action": "appointment.created",
  "duration_ms": 45,
  "context": {
    "appointment_id": 123,
    "customer_id": 456,
    "employee_id": 789
  }
}
```

### Pflichtfelder für jeden Log-Eintrag:
- `timestamp` (ISO 8601 UTC)
- `level` (debug, info, warning, error, critical)
- `message` (menschenlesbar)
- `correlation_id` (Request/Job-übergreifend)
- `tenant_id` (IMMER, auch in System-Logs als 0)

### Aktueller Stand:
- `ActivityLogger` (`src/Core/Service/ActivityLogger.php`) loggt in `bookando_activity_log` Tabelle
- Felder: `logged_at`, `severity`, `context`, `message`, `payload` (JSON), `tenant_id`, `module_slug`
- Freitext-Message + JSON-Payload
- **KEIN** correlation_id
- **KEIN** structured JSON-Output-Format
- **KEIN** externer Log-Export

### Ziel-Architektur:
- `LoggerPort` schreibt structured JSON
- SaaS: stdout (12-Factor) → Log-Aggregation (ELK/Loki/CloudWatch)
- WP: DB-Tabelle + optional File-Logging
- PWA: console + remote API batch

---

## 2. Correlation IDs

### Generierung:
- HTTP Request: Kernel-Middleware generiert `X-Correlation-ID` Header (UUID v4)
- Background Job: Worker überträgt `correlation_id` aus Job-Payload
- Webhook: Eingehender Request generiert neue Correlation-ID, verlinkt mit Webhook-Event-ID

### Propagation:
```
HTTP Request empfangen
  → Correlation-ID generiert (oder aus X-Correlation-ID Header übernommen)
  → LoggerPort::setCorrelationId($id)
  → Alle Logs in diesem Request enthalten die ID automatisch
  → QueuePort::enqueue({..., 'correlation_id' => $id})
  → Worker: LoggerPort::setCorrelationId($payload['correlation_id'])
  → Webhook: LoggerPort::setCorrelationId($newId . ' linked:' . $eventId)
```

### Aktueller Stand: **Nicht implementiert.** Logs sind nicht korrelierbar.

---

## 3. Tracing Boundaries

| Boundary | Start | End | Instrumentierung |
|----------|-------|-----|-----------------|
| **HTTP Request** | Request empfangen | Response gesendet | Duration, Status-Code, Route, Tenant, User |
| **Background Job** | Job dequeued | Job completed/failed | Duration, Job-Class, Status, Tenant, Attempts |
| **DB Query** | Query start | Query end | Duration, Table, Operation (SELECT/INSERT/UPDATE/DELETE) |
| **External API Call** | Request gesendet | Response empfangen | Duration, Host, Status-Code, Retry-Count |
| **Webhook Processing** | Payload empfangen | Status-Update geschrieben | Duration, Gateway, Event-Type, Signature-Valid |
| **Calendar Sync** | Sync initiated | Sync completed/failed | Duration, Provider, Operation, Error-Type |

---

## 4. Metrics

### Business Metrics:

| Metrik | Typ | Labels |
|--------|-----|--------|
| `bookando_appointments_total` | Counter | tenant_id, status |
| `bookando_payments_total` | Counter | tenant_id, gateway, status |
| `bookando_invoices_total` | Counter | tenant_id, currency |
| `bookando_active_tenants` | Gauge | plan |
| `bookando_active_users` | Gauge | tenant_id, role |
| `bookando_bookings_per_day` | Histogram | tenant_id |

### Technical Metrics:

| Metrik | Typ | Labels |
|--------|-----|--------|
| `bookando_http_request_duration_seconds` | Histogram | method, route, status |
| `bookando_http_requests_total` | Counter | method, route, status |
| `bookando_db_query_duration_seconds` | Histogram | operation, table |
| `bookando_queue_depth` | Gauge | status (pending/processing/failed/dead) |
| `bookando_queue_job_duration_seconds` | Histogram | job_class, status |
| `bookando_external_api_duration_seconds` | Histogram | host, status |
| `bookando_cache_hits_total` | Counter | — |
| `bookando_cache_misses_total` | Counter | — |
| `bookando_sync_outbox_pending` | Gauge | tenant_id |
| `bookando_sync_conflicts_total` | Counter | entity_type |
| `bookando_rate_limit_exceeded_total` | Counter | type (user/ip) |
| `bookando_auth_failures_total` | Counter | method, reason |

### Aktueller Stand: **Keine Metrics-Infrastruktur vorhanden.** Kein Prometheus/StatsD/etc.

---

## 5. Audit Logs

### Audit-pflichtige Aktionen:

| Kategorie | Aktionen |
|-----------|---------|
| **Auth** | login, logout, login_failed, register, token_refresh, token_revoke, password_change |
| **Tenant** | create, suspend, delete, config_change, tenant_switch |
| **Users** | create, update, delete, role_change, permission_change |
| **Appointments** | create, update, cancel, reschedule, assign |
| **Payments** | create, capture, refund, webhook_received, reconciliation_mismatch |
| **Invoices** | issue, correct (credit note), export |
| **Settings** | update (mit before/after Werten) |
| **Security** | dev_bypass, rate_limit_exceeded, path_traversal, invalid_webhook_signature |
| **Sync** | outbox_sent, inbox_received, conflict_detected, conflict_resolved, resync_started |

### Aktueller Stand:
- `ActivityLogger` loggt bereits in `bookando_activity_log` Tabelle
- Events vorhanden: `auth.success/failed`, `security.devbypass`, `security.rate_limit`, `rest.dispatch`
- **Fehlt**: before/after Snapshots, Entity-ID/Type, structured audit schema

### Ziel Audit-Log Schema:
```json
{
  "audit_id": "uuid-v4",
  "timestamp": "2026-01-15T14:30:00Z",
  "tenant_id": 42,
  "actor_id": 7,
  "actor_type": "user|system|api_key|webhook",
  "action": "appointment.created",
  "entity_type": "appointment",
  "entity_id": 123,
  "before": null,
  "after": { "status": "confirmed", "starts_at_utc": "..." },
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "correlation_id": "req-abc123"
}
```

---

## 6. Feature Flags

### Konzept:
- Feature-Flag-Service im Kernel (nicht extern/SaaS-only)
- Scoping: per Tenant, per User-Rolle, per Prozentsatz
- Persistent in DB, cachebar via CachePort
- Kein automatisches Zeitlimit (manuelles Cleanup)

### Verwendung:
```php
if ($featureFlags->isEnabled('new_booking_engine', $tenantId)) {
    // neuer Code-Pfad
} else {
    // alter Code-Pfad
}
```

### Regeln:
- Jedes riskante Deployment hinter Feature-Flag
- Flag-Name-Convention: `{module}_{feature}_{version}` (z.B. `appointments_double_booking_check_v2`)
- Cleanup: Flags nach 30 Tagen vollständigem Rollout entfernen
- Kill-Switch: Flag kann sofort deaktiviert werden bei Problemen

### Aktueller Stand:
- `LicenseManager::isFeatureEnabled()` — Feature-Gating basierend auf Lizenz
- **Kein** generisches Feature-Flag-System (nur lizenzbasiert)

---

## 7. Config/Secrets Management

### Hierarchie (Priorität absteigend):
1. Environment Variables (höchste Priorität)
2. `.env` Datei (nur lokal/dev) — geladen via `EnvLoader` (`src/Core/Config/EnvLoader.php`)
3. Config-Dateien (`config/modules.php`, `config/tenants.php`)
4. DB Settings (Tenant-spezifisch)

### Secrets:
- **NIEMALS** in Code oder Config-Dateien
- **NIEMALS** in Git
- Quelle: Environment Variables oder Secrets Manager (AWS SSM, Vault, etc.)
- Rotation: Mindestens jährlich für API-Keys, vierteljährlich für DB-Passwörter

### Aktuelle Risiken:
- WP Salt-Konstanten (`AUTH_KEY`, `SECURE_AUTH_KEY`) als JWT-Secret und HMAC-Key — in `wp-config.php` definiert
- Gateway API-Keys in DB gespeichert (verschlüsselt mit AES-256-GCM via `OAuthTokenStorage` — `src/Core/Service/OAuthTokenStorage.php`)
- `.env.example` dokumentiert erwartete Variablen

---

## 8. CI/CD Pipeline

### PR-Pipeline (bei jedem Push auf Feature-Branch):

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   PHP Lint   │    │   PHPStan    │    │ ESLint + TSC │    │  Security    │
│              │    │   Level 6    │    │              │    │   Audit      │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │                   │
       └───────────────────┴───────────────────┴───────────────────┘
                                     │
                           ┌─────────▼─────────┐
                           │   Unit Tests       │
                           │ (PHPUnit + Vitest) │
                           └─────────┬─────────┘
                                     │
                           ┌─────────▼─────────┐
                           │ Integration Tests  │
                           │ (DB + API Mocks)   │
                           └─────────┬─────────┘
                                     │
                           ┌─────────▼─────────┐
                           │  Coverage Report   │
                           │  (advisory ≥ 80%)  │
                           └───────────────────┘
```

### Nightly Pipeline:
- E2E Tests (Playwright) — `playwright.config.ts`
- Full Security Scan (Snyk/Trivy)
- Dependency License Audit
- Performance Regression (optional)

### Existierende CI-relevante Scripts:
- `scripts/qa/check-debug-artifacts.php` — verhindert Debug-Code in PRs
- `scripts/validate-modules.mjs` — Modul-Struktur-Validierung
- `scripts/bookando-audit.mjs` — Repository-Audit
- `scripts/check-rest-i18n.mjs` — i18n-Vollständigkeit

### Deployment Pipeline (SaaS-Ziel):
```
Main Merge → Build → Docker Image → Staging Deploy → Smoke Tests → Canary → Full Rollout
```

### Rollback-Strategie:
- Jedes Deployment hat Rollback-Plan (vorheriges Image)
- DB-Migrationen sind forward-only aber backward-compatible (additive Änderungen)
- Feature-Flags für instant Kill-Switch
- Canary-Deployment: 5% → 25% → 50% → 100% mit Metriken-Monitoring zwischen Stufen
