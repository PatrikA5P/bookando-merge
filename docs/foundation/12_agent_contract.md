# Phase 6 — Agent-Ready Contract (Binding Rules)

> Verbindliche Regeln für autonome Agenten, die am Bookando Platform Kernel arbeiten.
> Jede Regel ist ein HARD CONSTRAINT. Verstöße sind Blocker.

---

## 1. DO / DO NOT Rules (Non-Negotiable)

### DO:

| # | Regel |
|---|-------|
| D1 | **DO** schreibe tenant_id in JEDE DB-Query (SELECT, INSERT, UPDATE, DELETE). Nutze BaseModel oder PersistencePort mit explizitem tenant_id Parameter. |
| D2 | **DO** nutze den DI-Container für Service-Instanziierung. Registriere Services im ModuleServiceProvider. |
| D3 | **DO** speichere Geldbeträge als Integer Minor Units (Cents). Nutze das Money Value Object. |
| D4 | **DO** speichere alle Zeitpunkte in UTC. Konvertiere Zeitzonen nur für Display. |
| D5 | **DO** prüfe Berechtigungen serverseitig (Gate / AuthorizationPort). Niemals nur im Frontend. |
| D6 | **DO** logge Audit-Events für: Create, Update, Delete, Auth-Events, Security-Events. |
| D7 | **DO** füge Idempotency-Keys zu jeder Mutation hinzu (Commands, Webhooks, Sync). |
| D8 | **DO** verifiziere Webhook-Signaturen VOR der Verarbeitung. |
| D9 | **DO** schreibe Unit-Tests für Domain-Logik und Integration-Tests für DB-Flows. |
| D10 | **DO** nutze DateTimeImmutable mit expliziter DateTimeZone für alle Zeitberechnungen. |
| D11 | **DO** validiere Input an der System-Grenze (API Controller). Domain akzeptiert nur typisierte Value Objects. |
| D12 | **DO** propagiere tenant_id und correlation_id in Queue-Job-Payloads. |
| D13 | **DO** nutze DB-Transaktionen für zusammenhängende Schreiboperationen. |
| D14 | **DO** verwende prepared Statements / Parameter-Binding für alle SQL-Queries. |

### DO NOT:

| # | Regel |
|---|-------|
| N1 | **DO NOT** verwende `$wpdb` direkt in Modulen. Nutze PersistencePort / BaseModel. |
| N2 | **DO NOT** verwende PHP-Floats für Geldbeträge. NIEMALS `round($amount, 2)` für Geld. |
| N3 | **DO NOT** verwende `current_time('mysql')` — nutze ClockPort::nowUtcString(). |
| N4 | **DO NOT** importiere Klassen aus anderen Modulen direkt. Nutze Events oder Kernel-Contracts. |
| N5 | **DO NOT** nutze WordPress-Funktionen im Kernel oder in Modulen. WP-Funktionen gehören in Adapter. |
| N6 | **DO NOT** erstelle globale Variablen oder Singletons in Modulen. |
| N7 | **DO NOT** übersprage Tenant-Scoping. Kein `fetchOneUnsafeNoScope()` ohne vorherige ACL-Prüfung UND Audit-Log. |
| N8 | **DO NOT** verarbeite Webhooks ohne Signaturverifikation. |
| N9 | **DO NOT** speichere Secrets in Code, Config-Dateien oder Git. |
| N10 | **DO NOT** ändere DB-Schema ohne Migration-Datei. Kein manuelles ALTER TABLE. |
| N11 | **DO NOT** verwende `sleep()`, busy-waiting oder synchrone External-API-Calls in Request-Kontext ohne Timeout. |
| N12 | **DO NOT** lösche Audit-Log-Einträge. Audit-Log ist append-only. |
| N13 | **DO NOT** nutze statische Aufrufe auf Kernel-Services in neuem Code (TenantManager::, Gate::, etc.). Nutze DI. |
| N14 | **DO NOT** erstelle automatisch Tenant-Fallback auf ID 1. Fehlender Tenant = Exception. |

---

## 2. Kernel Boundary Rules

### Import-Hierarchie (streng):

```
Kernel Domain
  ← Kernel Application (darf Domain importieren)
    ← Kernel Contracts (darf Domain + Application importieren)
      ← Kernel Infrastructure Ports (Interfaces nur)
        ← Host Adapters (dürfen Ports + Contracts importieren, implementieren Ports)
          ← Modules (dürfen Kernel Contracts + Ports nutzen, NICHT andere Module)
```

### Erlaubte Imports pro Layer:

| Layer | Darf importieren | Darf NICHT importieren |
|-------|-----------------|----------------------|
| Kernel/Domain | PHP Stdlib, eigene Value Objects | Application, Infrastructure, Modules, WP |
| Kernel/Application | Domain, Contracts, Port-Interfaces | Infrastructure-Implementierungen, Modules, WP |
| Kernel/Contracts | Domain Value Objects | Application, Infrastructure, Modules, WP |
| Kernel/Infrastructure/Ports | Domain (Interfaces only) | Application, Modules, WP |
| Host/WP-Adapter | Ports, Contracts, WP-Funktionen | Domain-Implementierungen, Module-Internals |
| Host/SaaS-Adapter | Ports, Contracts, Framework (Laravel/Slim) | Domain-Implementierungen, Module-Internals, WP |
| Modules | Kernel Contracts, Ports | Andere Module, WP-Funktionen, Adapter-Internals |

### Automatische Validierung:
- PHPStan-Regel oder deptrac-Config um Cross-Boundary-Imports zu erkennen
- CI-Gate: Import-Violation = blockierender Fehler

---

## 3. Decision Checklist vor Core-Änderungen

Vor JEDER Änderung am Kernel oder an kritischen Modulen:

- [ ] **Tenant-Impact**: Ändert sich, wie tenant_id propagiert oder gefiltert wird?
- [ ] **Auth-Impact**: Ändert sich, wer was tun darf?
- [ ] **Money-Impact**: Ändert sich, wie Geldbeträge berechnet oder gespeichert werden?
- [ ] **Time-Impact**: Ändert sich, wie Zeiten berechnet, gespeichert oder angezeigt werden?
- [ ] **Schema-Impact**: Ändert sich das DB-Schema? Ist eine Migration nötig?
- [ ] **API-Impact**: Ändert sich ein REST-Endpoint (URL, Request, Response)?
- [ ] **Sync-Impact**: Ändert sich, wie Daten synchronisiert werden?
- [ ] **Security-Impact**: Könnte die Änderung eine Sicherheitslücke öffnen?
- [ ] **Test-Coverage**: Gibt es Tests für alle neuen/geänderten Pfade?
- [ ] **Rollback-Plan**: Wie wird die Änderung rückgängig gemacht bei Problemen?

---

## 4. Proof Obligations

### Bei jeder kritischen Änderung MUSS der Agent folgende Beweise liefern:

#### 4.1 Tenant Isolation Proof

```
BEWEIS erforderlich wenn: Änderung betrifft DB-Queries, Jobs, Cache, Export/Import

Pflicht:
1. Code-Nachweis: Jede neue Query enthält tenant_id Filter
2. Test: TenantIsolationTest mit 2 Tenants
   - Tenant A: Daten erstellen
   - Tenant B: gleiche Query → leeres Ergebnis
3. Test: Background-Job mit tenant_id X → nur Daten von Tenant X
4. Test: fetchOneUnsafeNoScope → nur nach ACL-Check + Audit
```

#### 4.2 Authorization Enforcement Proof

```
BEWEIS erforderlich wenn: Neuer/geänderter API-Endpoint oder Command-Handler

Pflicht:
1. Code-Nachweis: permission_callback auf Route ODER Gate::evaluate() im Handler
2. Test: PermissionMatrixTest
   - Anonymous → 401
   - User ohne Capability → 403
   - User mit Capability → 200
   - Write ohne CSRF-Token → 401
3. Test: Self-Access nur für eigene Records
```

#### 4.3 Money Correctness Proof

```
BEWEIS erforderlich wenn: Änderung betrifft Beträge, Rechnungen, Zahlungen, Steuern

Pflicht:
1. Code-Nachweis: Kein Float in Money-Pfaden
2. Test: MoneyCalculationTest mit:
   - Bekannte Eingabe → exakter erwarteter Wert (auf Cent)
   - Grenzwert: 0.01 + 0.02 → 3 (nicht 2.9999...)
   - Alle verwendeten Währungen
   - Zero-Decimal-Currencies (JPY)
3. Test: Refund ≤ Original
4. Test: Invoice-Total = Summe(Line-Items) + Tax
```

#### 4.4 Time/Scheduling Proof

```
BEWEIS erforderlich wenn: Änderung betrifft Termine, Zeitberechnung, Kalender-Sync

Pflicht:
1. Code-Nachweis: Alle Zeiten als DateTimeImmutable mit expliziter TZ
2. Test: DST-Wechsel-Tag (Europe/Zurich, spring-forward + fall-back)
3. Test: Overlap-Detection für alle 7 Konstellationen
4. Test: Rest-Period (11h Grenzwert)
5. Test: UTC-Speicherung → korrekte lokale Anzeige
```

#### 4.5 Sync Safety Proof

```
BEWEIS erforderlich wenn: Änderung betrifft Sync, Outbox, Change Feed, Conflict Resolution

Pflicht:
1. Test: Idempotency-Key 2x → gleiche Response
2. Test: Conflict in Money-Entity → Server-Wins
3. Test: Offline-Mutation → Online-Sync → korrekte Zusammenführung
4. Test: Outbox FIFO Reihenfolge erhalten
5. Test: Resync überschreibt lokale Daten NICHT wenn Outbox pending
```

---

## 5. PR Safety Policy

### PR-Regeln:

| Regel | Beschreibung |
|-------|-------------|
| **PR-only** | Kein direkter Push auf main/develop. Nur über Pull Request. |
| **Small Diffs** | Maximal 500 Zeilen geändert pro PR. Große Änderungen aufteilen. |
| **Review** | Mindestens 1 Review für Module, 2 Reviews für Kernel-Änderungen. |
| **Tests** | Alle CI-Gates müssen grün sein. Neue Tests für neuen Code. |
| **Feature-Flag** | Riskante Änderungen hinter Feature-Flag. |
| **Migration** | DB-Schema-Änderungen in eigener Migration-Datei. Forward-only, backward-compatible. |
| **Rollback-Plan** | Im PR beschrieben: Wie wird bei Problemen zurückgerollt? |
| **No Force-Push** | Kein `git push --force` auf shared Branches. |
| **Secrets** | Keine Secrets, API-Keys oder Passwörter im Diff. |
| **Commit Messages** | Conventional Commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:` |

---

## 6. Stop Conditions (Agent MUSS pausieren)

### Der Agent MUSS die Arbeit stoppen und menschliche Bestätigung einholen wenn:

| # | Condition | Begründung |
|---|-----------|------------|
| S1 | **Änderung am TenantManager oder MultiTenantTrait** | Risiko: Tenant-Isolation-Bruch für alle Tenants |
| S2 | **Änderung an Gate, AuthMiddleware oder JWTService** | Risiko: Auth-Bypass für alle Endpoints |
| S3 | **Änderung an Payment-Gateway oder Webhook-Handler** | Risiko: Geldverlust oder doppelte Belastung |
| S4 | **DB-Schema-Änderung an bestehenden Spalten** (Typ-Change, Drop, Rename) | Risiko: Datenverlust, Backward-Incompatibility |
| S5 | **Löschung oder Umbenennung einer öffentlichen API-Route** | Risiko: Breaking Change für Clients |
| S6 | **Änderung an Encryption/Decryption** (OAuthTokenStorage, CryptoPort) | Risiko: Token-Verlust, Daten nicht mehr entschlüsselbar |
| S7 | **Cross-Tenant-Operation** (runAsTenant, fetchOneUnsafeNoScope, ShareService) | Risiko: Datenleck zwischen Tenants |
| S8 | **Refund oder Rückerstattungslogik** | Risiko: Unkontrollierte Geldbewegung |
| S9 | **Test-Suite wird rot und Fix nicht offensichtlich** | Risiko: Unbekannter Seiteneffekt |
| S10 | **Unbekannte externe API-Interaktion entdeckt** | Risiko: Unerwartete Seiteneffekte in Produktivsystemen |
| S11 | **Mehr als 500 Zeilen geändert in einem PR** | Risiko: Review-Qualität sinkt |
| S12 | **Widerspruch zwischen Code und Dokumentation entdeckt** | Risiko: Falsche Annahmen. Dokumentation als untrusted behandeln. |

### Bei JEDEM Stop:
1. Aktuelle Änderungen commiten (WIP-Commit)
2. Klare Beschreibung des Problems
3. Vorgeschlagene Lösung(en)
4. Warten auf menschliche Entscheidung
