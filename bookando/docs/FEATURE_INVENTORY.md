# Bookando — Feature-Inventar & Modul-Map

> Vollständige Bestandsaufnahme aller Features aus der Referenz-Applikation
> mit Bewertung des Implementierungsstands und Verbesserungspotenzial.

---

## Modul-Übersicht

| # | Modul | Beschreibung | Referenz-Status | Priorität |
|---|-------|-------------|-----------------|-----------|
| 1 | **Dashboard** | KPIs, Widgets, Alerts, Analytics | Prototyp (Mock-Daten) | Hoch |
| 2 | **Termine** | Kalender, Terminbuchung, Dynamic Pricing | Funktional (kein Backend) | Hoch |
| 3 | **Kunden** | CRM, Custom Fields, CSV-Export | Funktional (kein Backend) | Hoch |
| 4 | **Mitarbeiter** | Personal, HR-Daten, Service-Zuordnung | Funktional (kein Backend) | Hoch |
| 5 | **Arbeitstag** | Zeiterfassung, Schichtplan, Abwesenheiten, Kursplaner | Teilweise (UI-Stubs) | Hoch |
| 6 | **Finanzen** | Rechnungen, FIBU, MwSt, Lohn, Gutscheine | Teilweise (viele Stubs) | Hoch |
| 7 | **Angebote** | Katalog, Bundles, Gutscheine, Dynamic Pricing, Formulare | Funktional (kein Backend) | Hoch |
| 8 | **Akademie** | Kurse, Lektionen, Quizze, Badges, Bildungskarten | Funktional (teilweise Stubs) | Mittel |
| 9 | **Ressourcen** | Standorte, Räume, Equipment | Nur Anzeige (kein CRUD) | Mittel |
| 10 | **Einstellungen** | Sprache, Firma, Integrationen, Lizenzen, Rollen | Funktional | Hoch |
| 11 | **Tools** | Reports, Import, API, Design-Widget | Teilweise (viele Stubs) | Mittel |
| 12 | **Partner Hub** | Partnernetzwerk, DSGVO, API-Keys | Nur Anzeige (Mock) | Niedrig |
| 13 | **Design Templates** | Frontend-Katalog-Darstellung | Prototyp | Mittel |
| 14 | **Design Frontend** | Widget-Design-Studio, Portal-Konfiguration | Prototyp (abgeschnitten) | Mittel |

---

## 1. Dashboard

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| KPI-Widgets (Umsatz, Kunden, Termine, Zeit) | Mock-Daten | Live-Daten via API |
| Revenue-Chart (Area) | Recharts, statisch | Echtzeit-Daten, Periodenauswahl |
| Termin-Chart (Bar) | Recharts, statisch | Live, filterbar |
| Aktivitäts-Feed | Mock-Array | Event-System (Foundation EventPort) |
| Kommende Termine | Mock-Array | Live-Query |
| Infocenter / Alerts | Context-basiert | Notification-System (Foundation) |
| Widget-Konfiguration | Lokal (kein Persist) | User-Preferences API, persistiert |
| Widget-Reihenfolge | Array-Move | Drag & Drop, persistiert |

### Verbesserungen
- **NEU:** Personalisierte Dashboard-Ansichten pro Rolle
- **NEU:** Echtzeit-Updates via SSE/WebSocket
- **NEU:** Drill-Down von KPIs zu Detail-Ansichten
- **NEU:** Datumsbereich-Selektor für alle Widgets
- **NEU:** Dashboard-Sharing und PDF-Export
- **FIX:** Widget-Einstellungen persistent speichern
- **FIX:** Error-States und Loading-Skeleton

---

## 2. Termine (Appointments)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| Kalender (Tag/Woche/Monat) | Voll implementiert | Vue-Kalender-Komponente |
| Listen-Ansicht | Datums-gruppiert | Mit Pagination, Virtualisierung |
| Terminbuchung (Modal) | Service/Kunde/Zeit | Multi-Step Wizard |
| Dynamic Pricing | Early Bird, Last Minute, Seasonal | Foundation PricingPort |
| Filterung | Text, Mitarbeiter, Kunde, Kategorie, Status | + Standort, Raum, Zeitraum |
| Status-Management | 5 Status | + Workflow-Engine |
| Mitarbeiter-Zuweisung | Nur Dropdown | 5 Strategien (Round Robin, etc.) |

### Verbesserungen
- **NEU:** Drag & Drop im Kalender (Verschieben, Resize)
- **NEU:** Verfügbarkeits-Prüfung in Echtzeit
- **NEU:** Konflikt-Erkennung (Doppelbuchung)
- **NEU:** Wiederkehrende Termine (Serie)
- **NEU:** Warteliste bei ausgebuchten Slots
- **NEU:** Automatische Erinnerungen (Foundation NotificationPort)
- **NEU:** Online-Buchungslink (öffentlich)
- **NEU:** Kalender-Sync (Google/Microsoft via Foundation CalendarPort)
- **FIX:** Mobile Kalender-Ansicht (aktuell horizontal scrollbar)
- **FIX:** Server-seitige Preisvalidierung

---

## 3. Kunden (Customers)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| Kundenliste (Tabelle) | Voll implementiert | + Virtualisierung |
| Suche & Filter | Name, E-Mail, Telefon, Status, Geschlecht, Geburtstag, Land, Stadt | + Tags, Segmente |
| Pagination | Konfigurierbar (10/25/50/100) | Cursor-basiert (skalierbar) |
| CRUD | Create, Edit, Soft-Delete | + Hard-Delete mit DSGVO-Prüfung |
| Buchungs-Historie | Modal mit Status | Vollständige Timeline |
| Custom Fields | Gruppen, 4 Typen | + Validation Rules, Bedingte Felder |
| CSV-Export | Alle Kunden | + Filter-respektierend, PDF |
| Länder-Auswahl | SearchableSelect | Standardisiert (ISO 3166) |

### Verbesserungen
- **NEU:** CSV/Excel-Import mit Validierung und Duplikat-Erkennung
- **NEU:** Kunden-Segmentierung (Tags, Gruppen, Smart-Listen)
- **NEU:** Kunden-Timeline (alle Interaktionen chronologisch)
- **NEU:** Duplikat-Erkennung und Zusammenführung
- **NEU:** DSGVO-Auskunftsrecht (Art. 15) — Daten-Export pro Kunde
- **NEU:** DSGVO-Löschrecht (Art. 17) — Foundation CryptoShreddingPort
- **NEU:** Consent-Management (Foundation ConsentPort)
- **NEU:** Kunden-Portal (Self-Service)
- **FIX:** Formular-Validierung (E-Mail, Telefon, PLZ)
- **FIX:** Soft-Delete rückgängig machen

---

## 4. Mitarbeiter (Employees)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| Mitarbeiter-Grid (Karten) | Voll implementiert | + Listen-/Tabellen-Ansicht |
| Profil (Name, Bio, Avatar) | Modal Tab 1 | Dedizierte Profilseite |
| Adresse | Modal Tab 2 | + Geo-Validierung |
| HR-Daten | Modal Tab 3 | Foundation DossierPort (Personaldossier) |
| Service-Zuordnung | Modal Tab 4 (Checkbox-Liste) | + Qualifikations-Management |
| Status | 5 Status | + Workflow mit Datum |
| Rollen | Dropdown | Foundation RBAC |
| Avatar-Upload | Data-URL (kein Persist) | File-Upload (Foundation FilePort) |
| Suche | Name, Position | + Abteilung, Status, Skills |

### Verbesserungen
- **NEU:** Personaldossier (Foundation DossierPort) — Revisionssicher, 10J Aufbewahrung
- **NEU:** Qualifikations- & Zertifikats-Management
- **NEU:** Arbeitszeugnis-Generierung
- **NEU:** On-/Offboarding-Checklisten
- **NEU:** Gehalts-Historie (verknüpft mit Lohnbuchhaltung)
- **NEU:** Abwesenheits-Kalender (verknüpft mit Workday)
- **NEU:** Mitarbeiter-Self-Service-Portal
- **FIX:** Avatar persistent speichern
- **FIX:** Formular-Validierung
- **FIX:** Sortierung und erweiterte Filter

---

## 5. Arbeitstag (Workday)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Zeiterfassung** | Clock In/Out (simuliert) | Foundation TimeTrackingPort (ArG-konform) |
| Wöchentliche Übersicht | Manuell | Automatisch aus Stempelzeiten |
| Team-Übersicht | Grid mit Suche | + Abteilungsfilter, Genehmigungen |
| **Schichtplan** | 7-Tage-Grid (Read-Only) | Drag & Drop, Vorlagen, Auto-Zuweisung |
| Schichttypen | Früh/Spät/Nacht/Frei | + Benutzerdefiniert, Pausen |
| **Abwesenheiten** | Request-Formular (nicht persistiert) | Genehmigungsworkflow, Saldo-Berechnung |
| Abwesenheitstypen | 6 Typen | + ArG/OR-konforme Kategorien |
| **Kursplaner** | Grid (nicht funktional) | Voll funktional mit Drag & Drop |
| Termine-Tab | Delegiert an Appointments | Inline-Integration |

### Verbesserungen
- **NEU:** ArG Art. 46 konforme Arbeitszeiterfassung (Foundation)
- **NEU:** Automatische Pausen-Validierung (>5.5h → 15min, >7h → 30min, >9h → 60min)
- **NEU:** Überstunden-Berechnung und -Kompensation
- **NEU:** Nacht-/Sonntagszuschläge
- **NEU:** Schichtplan-Vorlagen und Auto-Rotation
- **NEU:** Konflikterkennung bei Schichten
- **NEU:** Feriensaldo-Berechnung (gesetzlich + vertraglich)
- **NEU:** Arztzeugnis-Upload bei Krankheit (Foundation DossierPort)
- **NEU:** Export für Lohnbuchhaltung (Foundation → Swissdec)
- **FIX:** Echte Clock-In/Out-Funktionalität
- **FIX:** Abwesenheits-Genehmigungsworkflow

---

## 6. Finanzen (Finance)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Übersicht** | 4 KPI-Karten (Mock) | Live-Daten aus FIBU |
| **Rechnungen** | Liste, Vorschau, Swiss QR-Bill | Foundation InvoicePort |
| Rechnungs-Templates | Editor mit Live-Vorschau | Template-Engine |
| Swiss QR-Bill | A6-Preview | Swissdec-konform, ISO 20022 |
| Rechnungs-Status | 5 Status | + Mahnwesen-Workflow |
| **Buchhaltung** | Kontenplan (Read-Only) | GeBüV/GoBD-konform (Foundation) |
| Journal-Buchungen | Platzhalter | Doppelte Buchhaltung (Foundation) |
| MwSt-Sätze | CRUD | + ESTV-konforme Abrechnung |
| Bilanz/Erfolgsrechnung | Statische Anzeige | Live-Berechnung |
| Geschäftsjahre | Read-Only | Voll-Management mit Abschluss |
| **Lohnbuchhaltung** | Read-Only Anzeige | Swissdec ELM 5.0/5.5 (Foundation) |
| **Gutschein-Ledger** | Such- und Anzeige | Voll-Management |

### Verbesserungen
- **NEU:** Swissdec ELM 5.0/5.5 Lohnmeldung (Foundation SwissdecTransmitterPort)
- **NEU:** GeBüV-konforme Buchhaltung (Hashchain, lückenlose Sequenzen)
- **NEU:** MWST-Abrechnung für ESTV (effektiv + Saldosteuersatz)
- **NEU:** Mahnwesen mit automatischem Stufenversand
- **NEU:** ISO 20022 Zahlungsverkehr (pain.001, camt.053/054)
- **NEU:** Kreditoren-Buchhaltung
- **NEU:** Kostenstellenrechnung
- **NEU:** Bank-Import (MT940, camt.053)
- **NEU:** Bexio/Abacus-Schnittstelle
- **NEU:** Revisionssicheres Archiv (Foundation AuditPort)
- **FIX:** Journal-Buchungen implementieren
- **FIX:** Bilanz/ER live berechnen
- **FIX:** Lohnabrechnungen generieren

---

## 7. Angebote (Offers)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Katalog** | Karten-Grid mit Status-Toggle | + Listen-Ansicht, Sortierung |
| Service-Typen | Service, Event, Online-Kurs | Gleich |
| Service-Editor | 5-Tab-Modal (umfangreich) | Dedizierte Seite |
| **Bundles** | Karten mit Ersparnis-Badge | + Verfügbarkeits-Kaskade |
| **Gutscheine** | Tabelle mit Verbrauchsbalken | + Auto-Expiry, Stacking-Rules |
| **Dynamic Pricing** | 5 Strategien (inkl. AI) | Foundation PricingPort |
| **Tags** | Erstellen/Löschen (6 Farben) | + Bearbeiten, Hierarchie, Custom-Farben |
| **Extras** | Erstellen/Löschen (Upsell) | + Bearbeiten, Verfügbarkeitsregeln |
| **Kategorien** | CRUD mit Bild | + Hierarchie (Eltern/Kind) |
| **Buchungsformulare** | Split-Panel-Editor | + Drag & Drop, Bedingte Logik |

### Verbesserungen
- **NEU:** Service-Duplikation
- **NEU:** Bulk-Operationen (Mehrfach-Bearbeitung)
- **NEU:** Verfügbarkeits-Kalender pro Service
- **NEU:** Buchungsstatistiken pro Service
- **NEU:** A/B-Testing für Preise
- **NEU:** Automatische Upsell-Vorschläge (AI)
- **NEU:** Service-Versioning (Änderungshistorie)
- **FIX:** Formular-Validierung in allen Editoren
- **FIX:** Pagination für grosse Kataloge
- **FIX:** Tag-Bearbeitung ermöglichen

---

## 8. Akademie (Academy)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Kurse** | CRUD, 3 Typen, Curriculum | + Fortschritts-Tracking |
| Kurs-Editor | 3 Tabs (Definition, Planung, Zertifikat) | + Vorschau-Modus |
| **Lektionen** | CRUD, Gruppen, Rich-Text | + Video-Embedding, SCORM |
| Lektion-Editor | Modal mit Medien | Dedizierte Seite |
| **Quizze** | Modal-Editor (unvollständig) | Voll funktional |
| **Bildungskarten** | Kapitel, Bewertung, Automation | + Fortschrittsbalken |
| **Badges** | Erstellen/Löschen | + Vergabe-Regeln, Statistiken |
| Gruppen-Manager | Modal für Lektion-Gruppen | Inline-Management |

### Verbesserungen
- **NEU:** Lernfortschritts-Tracking pro Teilnehmer
- **NEU:** Zertifikats-Generierung (PDF)
- **NEU:** Kurs-Katalog (öffentlich, Buchung)
- **NEU:** Video-Streaming-Integration
- **NEU:** SCORM/xAPI-Kompatibilität
- **NEU:** Kurs-Bewertungen und Feedback
- **NEU:** Kurs-Duplikation
- **NEU:** Automatische Kurs-Empfehlungen
- **FIX:** Quiz-Editor vervollständigen
- **FIX:** Drag & Drop für Curriculum-Reihenfolge
- **FIX:** Kurs-Löschfunktion

---

## 9. Ressourcen (Resources)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Standorte** | Karten (Read-Only) | Volle CRUD |
| **Räume** | Karten mit Features (Read-Only) | CRUD + Kapazität + Belegungsplan |
| **Equipment** | Tabelle mit Verfügbarkeit (Read-Only) | CRUD + Check-In/Out + Wartung |

### Verbesserungen
- **NEU:** Raum-Buchungssystem mit Kalender
- **NEU:** Equipment Check-In/Out-Tracking
- **NEU:** Wartungsplanung und -historie
- **NEU:** Kapazitätswarnungen
- **NEU:** Standort-Karte (Geo-Integration)
- **NEU:** QR-Codes für Equipment-Tracking
- **NEU:** Automatische Raum-Zuordnung bei Terminbuchung
- **FIX:** Komplette CRUD-Operationen

---

## 10. Einstellungen (Settings)

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Allgemein** | Sprache, Zeitzone, Datum, Währung | Gleich |
| **Firma** | Name, Adresse, IBAN (Swiss) | + Logo-Upload, Mehrere Standorte |
| **Integrationen** | 4 Services (Stripe, Google, Zoom, SendGrid) | Foundation IntegrationPort |
| **Lizenz** | Status-Anzeige | SaaS Subscription Management |
| **Module** | Toggle-Switches | + Modul-Konfiguration |
| **Rollen & Berechtigungen** | Matrix-Editor | Foundation RBAC |

### Verbesserungen
- **NEU:** Audit-Log für Einstellungsänderungen
- **NEU:** Backup & Export aller Einstellungen
- **NEU:** Webhook-Testing
- **NEU:** Multi-Standort-Konfiguration
- **NEU:** White-Label-Einstellungen
- **NEU:** E-Mail-Template-Konfiguration
- **FIX:** Validierung aller Formularfelder
- **FIX:** Bestätigungsdialoge bei Änderungen

---

## 11. Tools

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Reports** | 5 vordefinierte (Mock) | Echtzeit-Reports + Custom Builder |
| **Import** | CSV-Templates (3 Typen) | + Validierung, Preview, Rollback |
| **API & Webhooks** | Key-Management, Doku-Links | Vollständiges API-Portal |
| **Design-Widget** | Theme-Editor (unvollständig) | Voller Widget-Builder |
| **Notifications** | Stub | Template-basiertes System |
| **System** | Stub | Logs, Health, Diagnostics |

### Verbesserungen
- **NEU:** Custom Report Builder (Drag & Drop)
- **NEU:** Geplante Reports (E-Mail-Versand)
- **NEU:** Import-Validierung mit Vorschau
- **NEU:** Webhook-Log-Viewer
- **NEU:** API-Sandbox zum Testen
- **NEU:** System-Health-Dashboard
- **FIX:** Report-Generierung mit echten Daten

---

## 12. Partner Hub

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Netzwerk** | Partner-Tabelle (Mock) | CRUD + Einladungs-System |
| **DSGVO** | AVV-Template, Audit-Log (Mock) | Foundation ConsentPort |
| **API** | Key-Management | Pro-Partner API-Scoping |
| **Einstellungen** | Stub | Partner-spezifische Konfiguration |

### Verbesserungen
- **NEU:** Partner-Einladung und Onboarding
- **NEU:** Revenue-Share-Abrechnung (automatisch)
- **NEU:** Daten-Synchronisation (Foundation WebhookPort)
- **NEU:** Partner-Portal (Self-Service)
- **NEU:** Vertragsmanagement (Foundation DossierPort)

---

## 13-14. Design Templates & Design Frontend

### Features
| Feature | Referenz | Neu (Foundation) |
|---------|----------|-----------------|
| **Katalog-Darstellung** | Service-Grid mit Pagination | + Vorlagen-Auswahl |
| **Widget-Studio** | Theme-Editor (abgeschnitten) | Voller WYSIWYG-Editor |
| **Portal-Konfiguration** | Kunden/Mitarbeiter-Portal | + Konfigurierbare Dashboards |
| **Mehrsprachigkeit** | 5 Sprachen (Text-Overrides) | Foundation i18n |
| **Presets** | Save/Load (nicht implementiert) | Voll-Management mit Import/Export |

### Verbesserungen
- **NEU:** Live-Preview im Editor
- **NEU:** CSS-Export
- **NEU:** Template-Marketplace
- **NEU:** A/B-Testing für Designs
- **NEU:** Geräte-spezifische Overrides

---

## Cross-Module Verknüpfungen (Intelligent Linking)

### Bestehend (Referenz)
| Von | Nach | Art |
|-----|------|-----|
| Dashboard | Termine | KPI-Anzeige |
| Termine | Kunden | Kunden-Auswahl |
| Termine | Angebote | Service-Auswahl |
| Termine | Finanzen | Auto-Rechnungserstellung |
| Arbeitstag | Termine | Eingebettetes Modul |
| Finanzen | Kunden | Rechnungsempfänger |
| Angebote | Akademie | Lektion-Verknüpfung in Events |
| Angebote | Dynamic Pricing | Preis-Regeln |

### Neu (Verbesserungen)
| Von | Nach | Art | Beschreibung |
|-----|------|-----|--------------|
| Kunden | Finanzen | Bidirektional | Kundenkonto, offene Posten |
| Kunden | Akademie | Verknüpfung | Kurs-Fortschritt im Kundenprofil |
| Kunden | Arbeitstag | Verknüpfung | Termin-Historie im Profil |
| Mitarbeiter | Arbeitstag | Bidirektional | Zeitkonto, Feriensaldo |
| Mitarbeiter | Finanzen | Verknüpfung | Lohnabrechnung |
| Mitarbeiter | Akademie | Verknüpfung | Qualifikationen, Zertifikate |
| Termine | Ressourcen | Bidirektional | Raum-Buchung bei Termin |
| Termine | Arbeitstag | Bidirektional | Arbeitszeit aus Terminen |
| Finanzen | Arbeitstag | Verknüpfung | Zeiterfassung → Lohn |
| Finanzen | Angebote | Verknüpfung | Umsatz pro Service |
| Akademie | Angebote | Bidirektional | Kurs = buchbares Angebot |
| Ressourcen | Termine | Bidirektional | Verfügbarkeit bei Buchung |
| Dashboard | Alle Module | Aggregation | Echtzeit-KPIs aus allen Modulen |
| Partner Hub | Angebote | Verknüpfung | Geteilte Services |
| Einstellungen | Alle Module | Konfiguration | Rollen, Module, Integrationen |

---

## Technische Architektur

### Foundation-Anbindung (Kernel Ports)

| Port | Module die ihn nutzen |
|------|----------------------|
| `TenantPort` | Alle (Multi-Tenancy) |
| `AuditPort` | Alle (GeBüV-Compliance) |
| `AuthPort` | Alle (Authentifizierung) |
| `PermissionPort` | Alle (Autorisierung/RBAC) |
| `NotificationPort` | Termine, Finanzen, Arbeitstag |
| `InvoicePort` | Finanzen, Termine |
| `PaymentPort` | Finanzen, Angebote |
| `BookingPort` | Termine, Angebote |
| `PricingPort` | Angebote, Termine |
| `FilePort` | Mitarbeiter, Akademie, Ressourcen |
| `DossierPort` | Mitarbeiter, Kunden, Finanzen |
| `TimeTrackingPort` | Arbeitstag |
| `ConsentPort` | Kunden, Mitarbeiter, Partner Hub |
| `SwissdecTransmitterPort` | Finanzen (Lohn) |
| `CalendarPort` | Termine, Arbeitstag |
| `WebhookPort` | Einstellungen, Partner Hub, Tools |
| `SearchPort` | Kunden, Termine, Angebote |
| `ReportPort` | Dashboard, Tools, Finanzen |
| `CryptoShreddingPort` | Kunden (DSGVO Art. 17) |

---

## Umsetzungsreihenfolge

### Phase 1 — Kern (Wochen 1-4)
1. Design-System & Scaffolding
2. Auth & Multi-Tenancy
3. Einstellungen (Basis)
4. Kunden
5. Mitarbeiter

### Phase 2 — Buchung (Wochen 5-8)
6. Angebote (Katalog, Kategorien, Tags)
7. Ressourcen
8. Termine (Kalender, Buchung)
9. Dashboard (Basis-KPIs)

### Phase 3 — Betrieb (Wochen 9-12)
10. Arbeitstag (Zeiterfassung, Schichtplan)
11. Finanzen (Rechnungen, FIBU)
12. Angebote (Dynamic Pricing, Bundles, Formulare)

### Phase 4 — Erweiterung (Wochen 13-16)
13. Akademie
14. Finanzen (Lohn/Swissdec, MwSt)
15. Tools (Reports, Import)
16. Partner Hub

### Phase 5 — Verfeinerung (Wochen 17-20)
17. Design Templates & Frontend
18. Cross-Module Optimierungen
19. WordPress Plugin
20. Performance & Testing
