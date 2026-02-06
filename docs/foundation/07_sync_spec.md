# Phase 4I — Sync Layer Spezifikation

> Bidirektionaler Sync-Mechanismus für PWA (Offline) und WordPress Plugin (Connected Mode).

---

## 1. Architektur-Überblick

```
┌──────────────┐                          ┌──────────────┐
│  PWA Client  │                          │  WP Plugin   │
│  (Offline)   │                          │ (Connected)  │
│              │                          │              │
│  ┌────────┐  │        ┌────────┐        │  ┌────────┐  │
│  │ Outbox │──┼───────>│  SaaS  │<───────┼──│ Outbox │  │
│  └────────┘  │        │  API   │        │  └────────┘  │
│  ┌────────┐  │        │        │        │  ┌────────┐  │
│  │ Inbox  │<─┼────────│ Change │────────┼─>│ Inbox  │  │
│  └────────┘  │        │  Feed  │        │  └────────┘  │
└──────────────┘        └────────┘        └──────────────┘
```

---

## 2. Outbox Pattern

### Outbox-Tabelle (Client-seitig):

```sql
CREATE TABLE bookando_sync_outbox (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  idempotency_key VARCHAR(64) NOT NULL UNIQUE,
  tenant_id     BIGINT NOT NULL,
  entity_type   VARCHAR(64) NOT NULL,
  entity_id     BIGINT NULL,
  operation     ENUM('create','update','delete') NOT NULL,
  payload       JSON NOT NULL,
  status        ENUM('pending','sent','confirmed','failed','conflict') DEFAULT 'pending',
  attempts      INT DEFAULT 0,
  max_attempts  INT DEFAULT 5,
  created_at    DATETIME NOT NULL,
  sent_at       DATETIME NULL,
  confirmed_at  DATETIME NULL,
  error_message TEXT NULL,
  INDEX idx_status (status, created_at)
);
```

### Outbox-Verarbeitung:

1. Client erstellt Mutation → schreibt in lokale DB + Outbox
2. Sync-Worker (Service Worker / WP-Cron) verarbeitet Outbox FIFO
3. Sendet an SaaS API mit `Idempotency-Key` Header
4. SaaS prüft Idempotency-Key:
   - Neu → verarbeiten, Ergebnis speichern
   - Bekannt → gespeichertes Ergebnis zurückgeben (kein Re-Processing)
5. Bei Erfolg: Status → `confirmed`, lokale Entity-ID mit Server-ID aktualisieren
6. Bei Conflict: Status → `conflict`, Conflict-Resolution starten
7. Bei Fehler: Retry mit exponential Backoff (2^attempt * 30s, max 5 Versuche)
8. Nach max_attempts: Status → `failed`, User-Benachrichtigung

---

## 3. Inbox Pattern (Change Feed)

### SaaS Change Feed API:

```
GET /api/v1/sync/changes?cursor={cursor}&limit=100
Authorization: Bearer {token}
X-Bookando-Tenant: {tenant_id}
```

**Response:**
```json
{
  "changes": [
    {
      "change_id": "uuid-v4",
      "entity_type": "appointment",
      "entity_id": 42,
      "operation": "update",
      "version": 5,
      "data": { ... },
      "occurred_at": "2026-01-15T14:30:00Z"
    }
  ],
  "cursor": "base64-encoded-checkpoint",
  "has_more": true
}
```

### Inbox-Verarbeitung:

1. Client pollt Change Feed mit letztem Cursor
2. Für jede Änderung:
   a. Prüfe: ist Entity in lokalem Outbox pending? → Conflict
   b. Prüfe: lokale Version < Server-Version? → Update anwenden
   c. Prüfe: lokale Version == Server-Version? → Skip (bereits aktuell)
   d. Prüfe: lokale Version > Server-Version? → Anomalie (sollte nie passieren)
3. Cursor aktualisieren nach erfolgreicher Batch-Verarbeitung
4. Polling-Intervall: 30s (online) / keine Polls (offline)

---

## 4. Conflict Detection & Resolution

### Conflict-Erkennung (per Entity):

Ein Conflict liegt vor, wenn:
- Outbox enthält pending Mutation für Entity X
- UND Inbox erhält Änderung für Entity X
- UND Inbox-Version > lokale Version

### Conflict-Resolution nach Domäne:

| Entity-Typ | Strategie | Begründung |
|------------|-----------|------------|
| **Appointment** | Server-Wins + Merge non-conflicting fields | Buchungskonsistenz > lokale Edits |
| **Payment/Invoice** | Server-Wins IMMER | Geldflüsse dürfen NIEMALS durch Client-Merge verändert werden |
| **Customer** (Name, Email, etc.) | Last-Writer-Wins mit Feldebene | Kontaktdaten-Updates sind meist unabhängig |
| **Employee Schedule** | Server-Wins | Dienstpläne erfordern zentrale Koordination |
| **Settings** | Server-Wins | Konfigurationsänderungen müssen konsistent sein |
| **Custom Fields** | Last-Writer-Wins mit Feldebene | Parallel-Edits auf verschiedene Felder möglich |
| **Resources** | Server-Wins | Verfügbarkeit muss zentral koordiniert sein |

### VERBOTEN:
- **KEIN Silent-Merge für Money/Payment/Invoice**: Jeder Konflikt bei Geldflüssen → manuelles Review erforderlich
- **KEIN automatisches Überschreiben von Server-Daten durch Client** für: Payments, Invoices, Refunds

---

## 5. Idempotency Keys

### Regeln:
1. Jede Mutation hat einen client-generierten `idempotency_key` (UUID v4)
2. SaaS speichert: `idempotency_key → (response, created_at)`
3. TTL: 24 Stunden (danach: Key wird gelöscht, Replay erzeugt neuen Record)
4. Scope: pro Tenant + Endpoint

### SaaS-Tabelle:

```sql
CREATE TABLE bookando_idempotency_keys (
  idempotency_key VARCHAR(64) NOT NULL,
  tenant_id       BIGINT NOT NULL,
  endpoint        VARCHAR(128) NOT NULL,
  response_status INT NOT NULL,
  response_body   JSON NULL,
  created_at      DATETIME NOT NULL,
  PRIMARY KEY (idempotency_key, tenant_id),
  INDEX idx_cleanup (created_at)
);
```

---

## 6. Backfill & Resync

### Initial Sync (neue Client-Verbindung):

```
GET /api/v1/sync/backfill?entity_type=appointments&page=1&per_page=500
```

- Paginierter Full-Export aller aktiven Entities für Tenant
- Client schreibt in lokale DB
- Nach Abschluss: normaler Change-Feed-Polling beginnt

### Resync (Daten-Reparatur):

```
POST /api/v1/sync/resync
{
  "entity_types": ["appointments", "customers"],
  "since": "2026-01-01T00:00:00Z"
}
```

- Server markiert alle Entities als "dirty" im Change Feed
- Client erhält volle Daten bei nächstem Poll
- Lokale Daten werden mit Server-Version überschrieben

---

## 7. Versionierung & Schema Evolution

### API-Versionierung:
- URL-Prefix: `/api/v1/`, `/api/v2/`
- Backward-compatible Änderungen in gleicher Version (neue optionale Felder)
- Breaking Changes → neue Version
- Alte Version mindestens 12 Monate supported

### Entity-Versionierung:
- Jede Entity hat `version` (Integer, auto-increment bei Update)
- Jede Entity hat `updated_at` (UTC Timestamp)
- Change Feed liefert `version` + `updated_at` pro Änderung
- Client speichert `last_synced_version` pro Entity-Typ

### Schema Evolution:
- Neue Felder: optional mit Default-Value → backward-compatible
- Entfernte Felder: erst deprecaten (12 Monate), dann entfernen
- Typ-Änderungen: VERBOTEN (neues Feld erstellen)
- Sync-Payload enthält `schema_version` → Client kann Migration entscheiden

---

## 8. Source-of-Truth Matrix (Connected Mode)

| Entity-Typ | Source-of-Truth | Client darf offline | Sync-Richtung |
|------------|----------------|--------------------|--------------|
| **Tenant Config** | SaaS | Nein (read-only) | SaaS → Client |
| **Users/Identity** | SaaS | Nein | SaaS → Client |
| **Appointments** | SaaS | Ja (create/update) | Bidirektional |
| **Customers** | SaaS | Ja (create/update) | Bidirektional |
| **Offers/Services** | SaaS | Nein (read-only) | SaaS → Client |
| **Payments** | SaaS | Nein (read-only) | SaaS → Client |
| **Invoices** | SaaS | Nein (read-only) | SaaS → Client |
| **Employee Schedules** | SaaS | Nein (read-only) | SaaS → Client |
| **Settings** | SaaS | Nein (read-only) | SaaS → Client |
| **Calendar Connections** | SaaS | Nein | SaaS → Client |
| **Audit Log** | SaaS | Ja (append-only) | Client → SaaS |

---

## 9. Offline Permissions

### Erlaubte Offline-Operationen:
- Appointment erstellen/bearbeiten (→ Outbox)
- Customer erstellen/bearbeiten (→ Outbox)
- Audit-Events loggen (→ Outbox)
- Gecachte Daten lesen (Calendar, Offers, Employees)

### VERBOTENE Offline-Operationen:
- Zahlungen verarbeiten (erfordert Gateway-Kommunikation)
- Rechnungen ausstellen (erfordert serverseitige Nummerierung)
- Rückerstattungen (erfordert Gateway-Kommunikation)
- Settings ändern (erfordert zentrale Konsistenz)
- Rollen/Berechtigungen ändern (erfordert zentrale Konsistenz)

---

## 10. Sicherheit

### Token-Management:
- Sync-Clients authentifizieren sich via JWT oder API-Key
- JWT Refresh offline: cached Refresh-Token mit langem TTL (30 Tage)
- Bei abgelaufenem Token: Outbox pausiert, User wird zum Login aufgefordert

### Transport:
- HTTPS only (TLS 1.3)
- Certificate Pinning optional für Mobile/PWA

### Payload-Schutz:
- Sync-Payloads enthalten tenant_id → Server validiert gegen Auth-Token
- Keine Cross-Tenant-Daten im Change Feed (Change Feed ist tenant-scoped)
- Sensible Felder (payment_details, tokens) werden NICHT synchronisiert
