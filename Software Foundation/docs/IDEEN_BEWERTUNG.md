# Ideenbewertung & Kernel-Erweiterungsplan

> Analyse aller vorgeschlagenen Funktionen, rechtlichen Anforderungen und Ergänzungen.
> Bewertung: Was gehört ins Kernel, was in Module, was in Adapter?

---

## Legende

| Symbol | Bedeutung |
|--------|-----------|
| **K** | **Kernel** — gehört in `kernel/src/` als Port, Value Object oder Domain-Primitive |
| **S** | **Shared Library** — eigenständiges Paket, das vom Kernel genutzt wird |
| **M** | **Modul** — gehört in ein spezifisches Software-Produkt (Buchhaltung, HR, etc.) |
| **A** | **Adapter** — Implementierung eines bestehenden Kernel-Ports |

---

## Teil 1: Bewertung deiner Ideen

### 1. Mehrsprachigkeit (i18n/L10n)

**Kategorie: K (Kernel-Port)**
**Priorität: HOCH — jede Software braucht das**

| Was | Wo |
|-----|-----|
| `TranslationPort` (Interface) | Kernel |
| `Locale` Value Object (BCP-47: `de-CH`, `en-US`) | Kernel Domain |
| `TranslationKey` Value Object | Kernel Domain |
| `TranslatedString` Value Object (Map<Locale, String>) | Kernel Domain |
| Konkrete Adapter (DB-basiert, JSON-Dateien, gettext) | Adapter |
| UI-Übersetzungen (Vue i18n) | Frontend pro Produkt |

**Warum Kernel?** Jedes Produkt auf der Foundation braucht Mehrsprachigkeit. Die Schnittstelle (Port) und die Locale-Validierung gehören in den Kernel. Die konkreten Übersetzungsdateien gehören in die jeweiligen Produkte.

**Kernel-Erweiterungen:**
- `Locale` Value Object mit BCP-47-Validierung
- `TranslationPort` Interface
- `NumberFormatterPort` für länderspezifische Zahlenformate (1'000.50 vs 1.000,50)
- `DateFormatterPort` für länderspezifische Datumsformate

---

### 2. Encryption at Rest + Ledger Locking

**Kategorie: K (Kernel) + M (Modul)**

#### 2a. Encryption at Rest

**Priorität: HOCH — DSG/DSGVO-Pflicht**

| Was | Wo |
|-----|-----|
| Feld-Level-Encryption (encrypt/decrypt) | Bereits im `CryptoPort` (AES-256-GCM) |
| `EncryptedField` Value Object | Kernel Domain (**NEU**) |
| `KeyManagementPort` (Key Rotation, Envelope Encryption) | Kernel Port (**NEU**) |
| Session Storage Key-Management (Browser) | Frontend-Adapter |
| IndexedDB-Encryption | PWA-Adapter |

**Status:** `CryptoPort` existiert bereits mit `encrypt()`/`decrypt()`. Was fehlt:
- **`KeyManagementPort`**: Schlüsselrotation, Envelope Encryption (Data Key + Master Key), Key-Versioning
- **`EncryptedField`**: Wrapper der garantiert, dass ein Feld nur verschlüsselt persistiert wird
- **`FieldEncryptionPolicy`**: Deklaration welche Felder eines Entities verschlüsselt werden

#### 2b. Ledger Locking (Blockchain-lite Hash-Kette)

**Priorität: HOCH — GeBüV/GoBD-Pflicht (Unveränderbarkeit)**

| Was | Wo |
|-----|-----|
| `HashChain` Value Object (previousHash + entryHash) | Kernel Domain (**NEU**) |
| `HashChainPort` (Interface: append, verify) | Kernel Port (**NEU**) |
| `LedgerEntry` Interface | Kernel Contracts (**NEU**) |
| Konkrete Journal-Implementierung | Buchhaltungs-Modul |
| Verifizierungslogik (Lücken erkennen) | Kernel |

**Warum Kernel?** Hash-Ketten-Integrität ist eine Anforderung von GeBüV Art. 3 (Integrität), GoBD (Unveränderbarkeit) und OR Art. 958f. Jedes Modul das revisionssichere Einträge braucht (Buchhaltung, Lohnbuchhaltung, Audit-Log), nutzt denselben Mechanismus.

**Kernel-Erweiterungen:**
- `HashChain`: `previousHash`, `entryHash`, `sequenceNumber`, `tenantId`, `createdAt`
- `HashChainPort`: `append(LedgerEntry): HashChain`, `verify(tenantId, fromSeq, toSeq): VerificationResult`
- `LedgerEntry` Contract: Interface das Module implementieren, um hashbare Einträge zu liefern

---

### 3. Digitale Signatur (eIDAS / ZertES)

**Kategorie: K (Port) + A (Adapter)**
**Priorität: MITTEL — wichtig für Rechnungen, aber nicht für MVP**

| Was | Wo |
|-----|-----|
| `DigitalSignaturePort` (Interface) | Kernel Port (**NEU**) |
| `SignatureLevel` Enum (SES, AES, QES) | Kernel Domain (**NEU**) |
| `SignedDocument` Value Object | Kernel Domain (**NEU**) |
| Swisscom AIS Adapter (Qualified eID) | Adapter |
| Adobe Sign / DocuSign Adapter | Adapter |
| PDF/A Signierung | Modul (Rechnungswesen) |

**Kontext:** eIDAS (EU) definiert 3 Signaturstufen:
- **SES** (Simple Electronic Signature): Screenshot, Checkbox — geringe Beweiskraft
- **AES** (Advanced Electronic Signature): Zertifikat-basiert, eindeutige Zuordnung
- **QES** (Qualified Electronic Signature): Handschrift-Äquivalent, rechtlich bindend

Schweiz: **ZertES** (Bundesgesetz über die elektronische Signatur) mit analogen Stufen.

**Kernel-Erweiterungen:**
- `DigitalSignaturePort`: `sign(document, level, signerId): SignedDocument`, `verify(SignedDocument): VerificationResult`
- `SignatureLevel` Enum: `SES`, `AES`, `QES`
- `SignedDocument` Value Object: `documentHash`, `signatureBytes`, `signerId`, `level`, `timestamp`, `certificateChain`

---

### 4. AI-Plausibilitätsprüfung (Fraud Detection)

**Kategorie: K (Port) + M (Modul)**
**Priorität: HOCH — Killer-Feature für Treuhänder**

| Was | Wo |
|-----|-----|
| `AiGatewayPort` (Interface) | Kernel Port (**NEU**) |
| `AiRequest` / `AiResponse` Value Objects | Kernel Domain (**NEU**) |
| `AiModelConfig` (Provider, Model, Temperature) | Kernel Domain (**NEU**) |
| Fraud Detection Rules Engine | Buchhaltungs-Modul |
| "Audit-Bot" Background Jobs | Buchhaltungs-Modul |
| Gemini Adapter | Adapter |
| OpenAI/Anthropic Adapter (optional) | Adapter |

**Warum ein Kernel-Port?** AI wird in VIELEN Produkten genutzt:
- Buchhaltung: Plausibilitätsprüfung, Belegzuordnung, Kontierungsvorschläge
- HR: Bewerbungsscreening, Anomalie-Erkennung
- Booking: Nachfrageprognosen, No-Show-Vorhersage

**Kernel-Erweiterungen:**
- `AiGatewayPort`: `complete(AiRequest): AiResponse`, `embed(text): float[]`, `moderate(content): ModerationResult`
- `AiRequest`: `model`, `messages[]`, `maxTokens`, `temperature`, `tenantId`
- `AiUsageTracker`: Integration mit `UsageQuota` (AI-Calls als Lizenz-limitierte Ressource)

---

### 5. Transparenz-Bericht / Prüfer-Export

**Kategorie: M (Modul) + K (unterstützende Contracts)**
**Priorität: MITTEL**

| Was | Wo |
|-----|-----|
| `AuditReportContract` Interface | Kernel Contracts (**NEU**) |
| `IntegrityCheckResult` Value Object | Kernel Domain (**NEU**) |
| `DocumentGeneratorPort` (PDF/HTML) | Kernel Port (**NEU**) |
| Konkreter Prüfer-Export mit Hashes | Buchhaltungs-Modul |
| Nummernkreis-Lücken-Check | Kernel (via `SequencePort`) |
| CSV/XML-Export | Modul |

**Kernel-Erweiterung:**
- `DocumentGeneratorPort`: `generate(template, data, format): Document` — universell für PDFs, HTML-Berichte
- `AuditReportContract`: Interface das jedes Modul implementiert, um Integritäts-Checks zu liefern
- `IntegrityCheckResult`: `passed`, `failedChecks[]`, `timestamp`, `verifierSignature`

---

### 6. Digital Sovereignty (Self-Hosting / Datenhoheit)

**Kategorie: K (Architektur) — bereits zu 80% gelöst durch Ports & Adapters**
**Priorität: HOCH — Wettbewerbsvorteil**

| Was | Wo |
|-----|-----|
| `DataResidencyPolicy` Value Object | Kernel Domain (**NEU**) |
| `ExternalStoragePort` (Google Drive, OneDrive) | Kernel Port (**NEU** — erweitert `StoragePort`) |
| `DataExportPort` (Tenant-Datenexport) | Kernel Port (**NEU**) |
| `HostingMode` Enum (SaaS, SelfHosted, Hybrid) | Kernel Domain (**NEU**) |
| Risikohinweis + AGB-Akzeptanz | Rechtsmodul |
| Google Drive Adapter | Adapter |
| OneDrive Adapter | Adapter |
| Lokaler Filesystem Adapter | Adapter |

**Warum im Kernel?** Die Foundation muss wissen, WO Daten liegen. Self-Hosted-Kunden brauchen andere Adapter als SaaS-Kunden. Der Kernel definiert die Policy, Adapter setzen sie um.

**Kernel-Erweiterungen:**
- `DataResidencyPolicy`: `country`, `allowedStorageProviders[]`, `requiresEncryption`, `backupStrategy`
- `HostingMode` Enum: `SAAS`, `SELF_HOSTED`, `HYBRID`
- `DataExportPort`: `exportTenantData(tenantId, format): ExportResult` (DSGVO Art. 20 Datenportabilität)

---

## Teil 2: Rechtliche/Compliance-Anforderungen — Kernel-Auswirkungen

### Schweizer Recht

#### OR Art. 957-964 (Buchführungspflicht)
| Anforderung | Technische Umsetzung | Wo |
|-------------|----------------------|-----|
| Doppelte Buchhaltung | Chart of Accounts, Journal, Ledger | Buchhaltungs-Modul |
| 10 Jahre Aufbewahrung | Retention Policy im Kernel | **K** |
| Nachvollziehbarkeit | Audit Trail (existiert: `LoggerPort.audit()`) | K (existiert) |
| Klarheit, Vollständigkeit | UI-Validierung + Geschäftsregeln | Modul |

#### GeBüV (Geschäftsbücherverordnung)
| Anforderung | Technische Umsetzung | Wo |
|-------------|----------------------|-----|
| **Integrität** (Art. 3) | Hash-Kette, Unveränderbarkeit | **K** (HashChain) |
| **Verfügbarkeit** | 10 Jahre lesbarer Zugriff, PDF/A-Archivierung | K (RetentionPolicy) + M |
| **Organisation** | Systematische Ablage, Metadaten | Modul |
| Veränderbare Medien erlaubt WENN: | Digitale Signatur ODER Log-Files + Zeitstempel | **K** (existiert teilweise) |

#### MWSTG (Mehrwertsteuergesetz)
| Anforderung | Technische Umsetzung | Wo |
|-------------|----------------------|-----|
| MWST-Sätze (8.1%, 2.6%, 3.7%) | `TaxRate` Value Object, `TaxCalculator` | **K** (Steuer-Framework) |
| 10 Jahre Aufbewahrung | Gleich wie OR | K (RetentionPolicy) |
| E-Rechnung B2G >5000 CHF | XML-Signierung, eIDAS | Rechnungs-Modul |
| QR-Rechnung | `QrBillPort` | M + A |

#### DSG (Datenschutzgesetz CH, seit 1.9.2023)
| Anforderung | Technische Umsetzung | Wo |
|-------------|----------------------|-----|
| Privacy by Design / by Default | Encryption at Rest, Datenminimierung | **K** (EncryptedField) |
| Verzeichnis der Bearbeitungstätigkeiten | Processing Activities Registry | Compliance-Modul |
| DSFA (Datenschutz-Folgenabschätzung) | Risiko-Analyse Tool | Compliance-Modul |
| Recht auf Datenportabilität | `DataExportPort` | **K** |
| Meldepflicht bei Verletzungen | Incident Notification Workflow | Compliance-Modul |

### EU-Recht

#### DSGVO / GDPR
| Anforderung | Technische Umsetzung | Wo |
|-------------|----------------------|-----|
| Art. 25: Privacy by Design/Default | Default-Verschlüsselung, Pseudonymisierung | **K** |
| Art. 32: Sicherheit der Verarbeitung | Encryption, Access Control, Audit | **K** (existiert teilweise) |
| Art. 17: Recht auf Löschung | `DataDeletionPort` oder Soft-Delete mit Crypto-Shredding | **K** |
| Art. 20: Datenportabilität | `DataExportPort` | **K** |
| Art. 33: Meldung an Aufsichtsbehörde (72h) | Incident Workflow | Compliance-Modul |
| Art. 35: DSFA | Impact Assessment Tool | Compliance-Modul |

#### GoBD (Deutschland)
| Anforderung | Technische Umsetzung | Wo |
|-------------|----------------------|-----|
| **Unveränderbarkeit** | Hash-Kette, Sperrung gebuchter Einträge, SHA256 | **K** (HashChain) |
| **Lückenlose Nummerierung** | `SequencePort` mit Lücken-Erkennung | **K** (**NEU**) |
| **Audit Trail** | Zeitstempel, Benutzer, Änderungsprotokoll | K (existiert: `LoggerPort`) |
| **Maschinelle Auswertbarkeit** | Export als CSV, XML, DATEV-Format | Modul |
| **Verfahrensdokumentation** | Technische Dokumentation | Compliance-Modul |
| 10 Jahre / 6 Jahre Aufbewahrung | `RetentionPolicy` | **K** |

### Internationale Standards

#### eIDAS / ZertES
- `DigitalSignaturePort` + `SignatureLevel` Enum → **K**
- Konkrete Provider-Anbindung (Swisscom AIS) → Adapter

#### ISO 20022 (Swiss Payment Standards)
- XML-Schema-Validierung (pain.001, camt.053, etc.) → Zahlungs-Modul
- `PaymentMessagePort` Interface → **K** (**NEU**)
- QR-Referenz, ISR-Referenz Value Objects → Zahlungs-Modul

#### ISO 27001
- Zugriffskontrolle → K (existiert: `AuthorizationPort`)
- Verschlüsselung → K (existiert: `CryptoPort`)
- Incident Management → Compliance-Modul
- Change Management / Logging → K (existiert: `LoggerPort`)
- Business Continuity → Infrastruktur

#### Swiss GAAP FER
- Buchungslogik (Abschreibungen, Rückstellungen, Bewertungen) → Buchhaltungs-Modul
- Kontenrahmen-Framework → Buchhaltungs-Modul
- Keine direkten Kernel-Auswirkungen

#### PDF/A
- `DocumentGeneratorPort` mit PDF/A-1b-Format → **K** (Port) + Adapter
- Langzeitarchivierung (10 Jahre) → K (`RetentionPolicy`)

#### QR-Rechnung (SIX)
- `QrBillGeneratorPort` → Zahlungs-Modul
- QR-IBAN, QR-Referenz Value Objects → Zahlungs-Modul

---

## Teil 3: Von mir ergänzte Ideen

### E1. Nummernkreise (Sequence Generator) — **KERNEL**

**Priorität: KRITISCH — gesetzliche Pflicht (GoBD, OR)**

Lückenlose, mandantenspezifische Nummernkreise für Rechnungen, Gutschriften, Buchungen, Belege.

| Was | Wo |
|-----|-----|
| `SequencePort` Interface | Kernel Port |
| `SequenceKey` Value Object (`invoice:2026`, `journal:2026`) | Kernel Domain |
| `SequencePolicy` (Format: `RE-{YYYY}-{000001}`) | Kernel Domain |
| Gap Detection | Kernel |

```
SequencePort::next(tenantId, key): string  // "RE-2026-000042"
SequencePort::current(tenantId, key): int
SequencePort::detectGaps(tenantId, key): Gap[]
```

### E2. Steuer-Framework (Tax Calculation) — **KERNEL**

**Priorität: HOCH — jedes Finanzprodukt braucht das**

| Was | Wo |
|-----|-----|
| `TaxRate` Value Object | Kernel Domain |
| `TaxRule` Interface (Steuersatz pro Land/Kategorie/Datum) | Kernel Contracts |
| `TaxCalculationResult` Value Object | Kernel Domain |
| `TaxResolverPort` Interface | Kernel Port |
| Schweizer MWST-Sätze, EU VAT, US Sales Tax | Module/Adapter |

### E3. Notification System (Multi-Channel) — **KERNEL**

**Priorität: HOCH — jedes Produkt braucht Benachrichtigungen**

| Was | Wo |
|-----|-----|
| `NotificationPort` Interface | Kernel Port |
| `NotificationChannel` Enum (EMAIL, SMS, PUSH, IN_APP, WEBHOOK) | Kernel Domain |
| `NotificationTemplate` Value Object | Kernel Domain |
| `NotificationPreference` (User-Opt-in/Out) | Kernel Domain |
| SMS Adapter (Twilio etc.) | Adapter |
| Push Adapter (Firebase etc.) | Adapter |

`MailPort` existiert bereits, deckt aber nur E-Mail ab. Ein `NotificationPort` abstrahiert alle Kanäle.

### E4. Workflow / State Machine Engine — **KERNEL**

**Priorität: MITTEL — wird in vielen Modulen gebraucht**

| Was | Wo |
|-----|-----|
| `StateMachine` Value Object | Kernel Domain |
| `State`, `Transition`, `Guard` Interfaces | Kernel Domain |
| `WorkflowPort` Interface | Kernel Port |
| Beispiel: Rechnung (Draft → Sent → Paid → Archived) | Modul |
| Beispiel: Buchung (Pending → Confirmed → Cancelled) | Modul |

### E5. Retention & Archiving Policy — **KERNEL**

**Priorität: HOCH — OR/GeBüV/GoBD-Pflicht**

| Was | Wo |
|-----|-----|
| `RetentionPolicy` Value Object (Dauer, Grund, Auto-Löschung) | Kernel Domain |
| `RetentionCategory` Enum (FINANCIAL_10Y, COMMERCIAL_6Y, PERSONAL_DSG) | Kernel Domain |
| `ArchivalPort` Interface (Archivierung in PDF/A, WORM) | Kernel Port |
| `RetentionEnforcerPort` (automatische Löschung/Archivierung) | Kernel Port |

### E6. Data Deletion / Crypto-Shredding — **KERNEL**

**Priorität: HOCH — DSGVO Art. 17 + DSG**

| Was | Wo |
|-----|-----|
| `DataDeletionPort` Interface | Kernel Port |
| `DeletionStrategy` Enum (HARD_DELETE, SOFT_DELETE, CRYPTO_SHRED) | Kernel Domain |
| Crypto-Shredding: Lösche den Encryption Key → Daten werden unlesbar | Kernel-Strategie |

Elegante Lösung: Statt Daten zu löschen (was bei 10-Jahres-Aufbewahrung kollidiert), wird der Encryption Key vernichtet. Die verschlüsselten Daten bleiben als Integritätsnachweis, sind aber unlesbar.

### E7. Webhook Dispatcher (Outbound) — **KERNEL**

**Priorität: MITTEL — wichtig für Integrationen**

| Was | Wo |
|-----|-----|
| `WebhookDispatcherPort` Interface | Kernel Port |
| `WebhookSubscription` Entity (URL, Events, Secret, Active) | Kernel Domain |
| `WebhookDelivery` (Retry-Logik, Signierung) | Kernel |

### E8. Rate Limiter — **KERNEL**

**Priorität: MITTEL — API-Schutz**

| Was | Wo |
|-----|-----|
| `RateLimiterPort` Interface | Kernel Port |
| `RateLimit` Value Object (requests, window, tenantId) | Kernel Domain |

### E9. Currency Conversion Service — **KERNEL**

**Priorität: MITTEL — international wichtig**

| Was | Wo |
|-----|-----|
| `ExchangeRatePort` Interface | Kernel Port |
| `ExchangeRate` Value Object (from, to, rate, date) | Kernel Domain |
| `Money::convertTo(Currency, ExchangeRate): Money` | Erweiterung existierendes Money |

### E10. File Validation & Virus Scanning — **KERNEL**

**Priorität: MITTEL — Sicherheit**

| Was | Wo |
|-----|-----|
| `FileValidationPort` Interface | Kernel Port |
| `AllowedFileType` Policy | Kernel Domain |
| ClamAV / VirusTotal Adapter | Adapter |

---

## Teil 4: Zusammenfassung — Neue Kernel-Erweiterungen

### Neue Ports (Interfaces)

| # | Port | Begründung |
|---|------|------------|
| 1 | `TranslationPort` | Mehrsprachigkeit — jedes Produkt |
| 2 | `NumberFormatterPort` | Länderspezifische Formate (1'000.50) |
| 3 | `KeyManagementPort` | Schlüsselrotation, Envelope Encryption (DSG/DSGVO) |
| 4 | `HashChainPort` | Ledger Integrität (GeBüV, GoBD) |
| 5 | `DigitalSignaturePort` | eIDAS / ZertES Signaturen |
| 6 | `AiGatewayPort` | AI-Integration (Gemini, etc.) |
| 7 | `DocumentGeneratorPort` | PDF/A, HTML-Reports |
| 8 | `DataExportPort` | DSGVO Art. 20 Datenportabilität |
| 9 | `DataDeletionPort` | DSGVO Art. 17 Recht auf Löschung |
| 10 | `SequencePort` | Lückenlose Nummernkreise (GoBD) |
| 11 | `TaxResolverPort` | Steuerberechnung Framework |
| 12 | `NotificationPort` | Multi-Channel Benachrichtigungen |
| 13 | `ExchangeRatePort` | Währungsumrechnung |
| 14 | `ArchivalPort` | Langzeitarchivierung (10 Jahre) |
| 15 | `RateLimiterPort` | API-Schutz |
| 16 | `WebhookDispatcherPort` | Outbound Webhooks |
| 17 | `FileValidationPort` | Upload-Validierung + Virus-Scan |

### Neue Value Objects

| # | Value Object | Begründung |
|---|-------------|------------|
| 1 | `Locale` | BCP-47 Sprachcode |
| 2 | `EncryptedField` | Garantiert verschlüsselte Persistenz |
| 3 | `HashChain` | Kryptografische Kette für Revisionssicherheit |
| 4 | `SignatureLevel` (Enum) | SES / AES / QES |
| 5 | `SignedDocument` | Signiertes Dokument mit Zertifikatskette |
| 6 | `SequenceKey` | Nummernkreis-Identifier |
| 7 | `TaxRate` | Steuersatz mit Gültigkeitszeitraum |
| 8 | `RetentionPolicy` | Aufbewahrungsfrist + Strategie |
| 9 | `RetentionCategory` (Enum) | FINANCIAL_10Y, COMMERCIAL_6Y, etc. |
| 10 | `DeletionStrategy` (Enum) | HARD_DELETE, SOFT_DELETE, CRYPTO_SHRED |
| 11 | `HostingMode` (Enum) | SAAS, SELF_HOSTED, HYBRID |
| 12 | `DataResidencyPolicy` | Land, erlaubte Provider, Verschlüsselung |
| 13 | `NotificationChannel` (Enum) | EMAIL, SMS, PUSH, IN_APP, WEBHOOK |
| 14 | `ExchangeRate` | Wechselkurs mit Datum |
| 15 | `IntegrityCheckResult` | Ergebnis einer Hash-Chain-Prüfung |
| 16 | `AiModelConfig` | Provider, Modell, Parameter |

### Neue Contracts

| # | Contract | Begründung |
|---|----------|------------|
| 1 | `LedgerEntry` | Module implementieren dies für hashbare Einträge |
| 2 | `TaxRule` | Module liefern länderspezifische Steuerregeln |
| 3 | `AuditReportContract` | Module liefern Integritäts-Checks für Prüfer-Export |
| 4 | `Notifiable` | Module deklarieren ihre Notification-Events |

### Erweiterungen existierender Domain-Objekte

| Was | Erweiterung |
|-----|------------|
| `Money` | `convertTo(Currency, ExchangeRate): Money` |
| `SecurityContext` | `locale: Locale`, `hostingMode: HostingMode` |
| `Plan` | `aiCallsPerMonth: int`, `storageGb: int` |
| `DomainEvent` | `locale: ?Locale` (optional für UI-Events) |

---

## Teil 5: Priorisierte Implementierungsreihenfolge

### Phase 1 — Rechtskonformität (MUST HAVE)
1. `HashChainPort` + `HashChain` Value Object — GeBüV/GoBD Integrität
2. `SequencePort` + `SequenceKey` — Lückenlose Nummerierung (GoBD)
3. `KeyManagementPort` + `EncryptedField` — Encryption at Rest (DSG/DSGVO)
4. `RetentionPolicy` + `RetentionCategory` — Aufbewahrungsfristen (OR/GeBüV)
5. `DataExportPort` — Datenportabilität (DSGVO Art. 20)
6. `DataDeletionPort` + `DeletionStrategy` + Crypto-Shredding — Löschrecht (DSGVO Art. 17)

### Phase 2 — Kernfunktionalität (HIGH VALUE)
7. `Locale` + `TranslationPort` + `NumberFormatterPort` — Mehrsprachigkeit
8. `TaxRate` + `TaxResolverPort` — Steuer-Framework
9. `NotificationPort` + `NotificationChannel` — Multi-Channel Notifications
10. `AiGatewayPort` + `AiModelConfig` — AI-Integration
11. `DocumentGeneratorPort` — PDF/A, HTML-Reports
12. `HostingMode` + `DataResidencyPolicy` — Digital Sovereignty

### Phase 3 — Erweiterungen (NICE TO HAVE)
13. `DigitalSignaturePort` + `SignatureLevel` — eIDAS/ZertES
14. `ExchangeRatePort` + `ExchangeRate` — Währungsumrechnung
15. `WebhookDispatcherPort` — Outbound Webhooks
16. `RateLimiterPort` — API-Schutz
17. `FileValidationPort` — Upload-Sicherheit

---

## Teil 6: Was NICHT ins Kernel gehört

| Idee | Warum nicht Kernel | Richtiger Ort |
|------|-------------------|---------------|
| Doppelte Buchhaltung (Journal, Ledger, Kontenplan) | Fachliche Logik eines spezifischen Produkts | Buchhaltungs-Modul |
| ISO 20022 pain.001/camt.053 Parsing | Spezifisch für Zahlungsverkehr | Zahlungs-Modul |
| QR-Rechnung Generierung | Spezifisch für CH-Rechnungswesen | Rechnungs-Modul |
| Swiss GAAP FER Bewertungsregeln | Spezifisch für CH-Buchhaltung | Buchhaltungs-Modul |
| Lohnbuchhaltung (Quellensteuern, AHV, BVG) | HR/Payroll-spezifisch | Lohn-Modul |
| HR-Workflows (Personio-Style) | HR-spezifisch | HR-Modul |
| DATEV-Export | Deutschland-spezifisch | DE-Buchhaltungs-Modul |
| Konkrete Fraud-Detection-Rules | Buchhalungs-spezifisch | Buchhaltungs-Modul |
| Verfahrensdokumentation (GoBD) | Dokumentation, kein Code | Compliance-Modul |

---

## Teil 7: Performance-Anforderung >100'000 Buchungen

Deine Anforderung "mehr als 100'000 Buchungen ohne Performance-Probleme" betrifft hauptsächlich:

1. **Kernel**: `PersistencePort` muss Batch-Operationen unterstützen → **Erweiterung nötig**
   - `insertBatch(table, data[]): int[]`
   - `queryPaginated(sql, params, page, pageSize): PaginatedResult`

2. **Kernel**: `HashChainPort` muss bei >100k Einträgen effizient verifizieren können
   - Merkle-Tree statt linearer Kette für schnelle Teilverifikation

3. **Modul**: Indexierung, Partitionierung, Materialized Views → Buchhaltungs-Modul

---

*Dieses Dokument dient als Grundlage für die nächste Kernel-Erweiterungsphase.*
