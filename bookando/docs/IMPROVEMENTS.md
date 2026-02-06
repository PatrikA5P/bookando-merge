# Bookando — Verbesserungs- & Ausbauvorschläge

> Systematische Analyse der Schwächen der Referenz-Applikation und
> konkrete Vorschläge für die Neuimplementierung auf der Software Foundation.

---

## 1. Architektur-Verbesserungen

### 1.1 State Management
**Problem (Referenz):** Monolithischer AppContext (~1112 Zeilen) mit 24+ State-Slices.
Alle Module teilen sich einen einzigen Context — jede Änderung triggert Re-Renders in allen Modulen.

**Lösung:**
- Pinia Stores pro Modul (z.B. `useCustomerStore`, `useAppointmentStore`)
- Composables für shared Logic (`useAuth`, `useTenancy`, `usePermissions`)
- Lazy-Loading der Stores (nur laden wenn Modul aktiv)

### 1.2 Routing
**Problem (Referenz):** Kein URL-Routing. Navigation via State-Switch (`activeModule`).
Kein Deep-Linking, kein Browser-Back, kein Bookmarking möglich.

**Lösung:**
- Vue Router mit verschachtelten Routen
- `/dashboard`, `/appointments/calendar`, `/customers/:id`, etc.
- Route Guards für Auth und Permissions
- Breadcrumb-Navigation

### 1.3 API-Integration
**Problem (Referenz):** Keine echte Backend-Anbindung. Alle Daten im Frontend-Context.
Kein Error-Handling, keine Loading-States, keine Optimistic Updates.

**Lösung:**
- API-Client mit Interceptors (Auth, Tenancy, Error-Handling)
- TanStack Query (Vue Query) für Server-State-Management
- Automatisches Caching, Refetching, Stale-While-Revalidate
- Optimistic Updates für schnelle UX
- Standardisierte Error-Boundaries

### 1.4 Formular-Validierung
**Problem (Referenz):** Keine Formular-Validierung. Felder akzeptieren beliebige Eingaben.
Keine Inline-Fehlermeldungen, keine Server-seitige Validierung.

**Lösung:**
- Zod-Schemas für alle Entitäten (Client + Server)
- VeeValidate für Vue-Formular-Handling
- Inline-Fehlermeldungen mit i18n
- Server-seitige Re-Validierung (Foundation Domain-Objekte)

### 1.5 Fehlerbehandlung
**Problem (Referenz):** Keine Error-States, keine Error-Boundaries.
Silent Failures bei API-Aufrufen.

**Lösung:**
- Globale Error-Boundary-Komponente
- Toast/Snackbar-System für Benutzer-Feedback
- Retry-Logic für fehlgeschlagene Requests
- Sentry/Logging-Integration

---

## 2. UX/UI-Verbesserungen

### 2.1 Responsive Design
**Problem (Referenz):** Kalender-Wochenansicht auf Mobile horizontal scrollbar.
Modals nicht für Touch optimiert. Keine Touch-Gesten.

**Lösung:**
- Mobile: Tagesansicht als Default, Woche via Swipe
- Bottom-Sheets statt Modals auf Mobile
- Touch-Gesten (Swipe für Navigation, Pull-to-Refresh)
- Floating Action Button für Hauptaktionen
- Responsive Tabellen → Karten auf Mobile

### 2.2 Loading & Empty States
**Problem (Referenz):** Keine Loading-Indikatoren. Leere Listen ohne Erklärung.

**Lösung:**
- Skeleton-Loader für jede Ansicht
- Empty-State-Illustrationen mit Handlungsaufforderung
- Progressive Loading (Inhalte erscheinen stückweise)
- Offline-Modus-Indikator

### 2.3 Accessibility (a11y)
**Problem (Referenz):** Minimale ARIA-Labels. Keine Keyboard-Navigation.
Fokus-Management in Modals unvollständig.

**Lösung:**
- WCAG 2.1 AA Konformität
- Aria-Labels für alle interaktiven Elemente
- Fokus-Trap in Modals/Dialogen
- Skip-Navigation-Links
- Farbkontrast-Prüfung (min. 4.5:1)
- Screen-Reader-Unterstützung

### 2.4 Undo/Redo
**Problem (Referenz):** Keine Möglichkeit, Aktionen rückgängig zu machen.
Löschaktionen teilweise ohne Bestätigung.

**Lösung:**
- Undo-Toast nach destruktiven Aktionen (5s Timeout)
- Bestätigungsdialoge für irreversible Aktionen
- Papierkorb-Konzept für gelöschte Elemente (30-Tage Aufbewahrung)

---

## 3. Modul-spezifische Verbesserungen

### 3.1 Dashboard
| Verbesserung | Priorität | Aufwand | Beschreibung |
|-------------|-----------|---------|--------------|
| Echtzeit-KPIs | Hoch | Mittel | SSE/WebSocket statt Polling |
| Rollen-Dashboards | Hoch | Mittel | Admin sieht andere Widgets als Mitarbeiter |
| Drill-Down | Mittel | Mittel | Klick auf KPI → Detail-Ansicht |
| Widget-Persistenz | Hoch | Niedrig | User-Preferences in DB speichern |
| Datumsbereich | Hoch | Niedrig | Globaler Datums-Selektor für alle Widgets |
| PDF-Export | Niedrig | Mittel | Dashboard als PDF exportieren |

### 3.2 Termine
| Verbesserung | Priorität | Aufwand | Beschreibung |
|-------------|-----------|---------|--------------|
| Drag & Drop | Hoch | Hoch | Termine im Kalender verschieben/resizen |
| Verfügbarkeits-Check | Hoch | Mittel | Echtzeit-Prüfung bei Buchung |
| Konflikterkennung | Hoch | Mittel | Doppelbuchung verhindern |
| Wiederkehrende Termine | Mittel | Hoch | Serien-Termine (täglich/wöchentlich/monatlich) |
| Warteliste | Mittel | Mittel | Benachrichtigung wenn Slot frei wird |
| Online-Buchung | Hoch | Hoch | Öffentlicher Buchungslink |
| Erinnerungen | Hoch | Mittel | SMS/E-Mail/Push vor Termin |
| Kalender-Sync | Mittel | Hoch | Google/Outlook bidirektional |
| Multi-Ressource | Mittel | Mittel | Raum + Mitarbeiter + Equipment pro Termin |

### 3.3 Kunden
| Verbesserung | Priorität | Aufwand | Beschreibung |
|-------------|-----------|---------|--------------|
| Import-Funktion | Hoch | Mittel | CSV/Excel mit Validierung + Preview |
| Duplikat-Erkennung | Hoch | Mittel | Fuzzy-Matching auf Name + E-Mail |
| Kunden-Timeline | Mittel | Mittel | Alle Interaktionen chronologisch |
| Segmentierung | Mittel | Mittel | Tags, Smart-Listen, Scoring |
| DSGVO-Export | Hoch | Niedrig | Art. 15 Auskunftsrecht (JSON/PDF) |
| DSGVO-Löschung | Hoch | Mittel | Art. 17 Recht auf Vergessenwerden |
| Kunden-Portal | Niedrig | Hoch | Self-Service (Termine, Rechnungen, Profil) |
| Merge-Funktion | Mittel | Mittel | Duplikate zusammenführen |
| Smart-Filter | Niedrig | Mittel | Gespeicherte Filter, komplexe Abfragen |

### 3.4 Mitarbeiter
| Verbesserung | Priorität | Aufwand | Beschreibung |
|-------------|-----------|---------|--------------|
| Personaldossier | Hoch | Mittel | Foundation DossierPort, 10J Aufbewahrung |
| Qualifikationen | Mittel | Mittel | Zertifikate, Ablaufdaten, Erinnerungen |
| On-/Offboarding | Mittel | Mittel | Checklisten mit Fortschritt |
| Gehalts-Historie | Mittel | Niedrig | Verknüpfung mit Lohnbuchhaltung |
| Self-Service | Niedrig | Hoch | Mitarbeiter-Portal |
| Organigramm | Niedrig | Mittel | Hierarchie-Visualisierung |

### 3.5 Arbeitstag
| Verbesserung | Priorität | Aufwand | Beschreibung |
|-------------|-----------|---------|--------------|
| ArG-Konformität | Hoch | Hoch | Foundation TimeTrackingPort, Pausen-Validierung |
| Überstunden | Hoch | Mittel | Berechnung, Kompensation, Auszahlung |
| Schichtplan Drag&Drop | Mittel | Hoch | Visuelles Planen mit Vorlagen |
| Abwesenheits-Workflow | Hoch | Mittel | Antrag → Genehmigung → Saldo-Update |
| Feriensaldo | Hoch | Mittel | Gesetzlich + vertraglich, Übertrag |
| Lohn-Export | Hoch | Mittel | Arbeitszeit → Swissdec (Foundation) |
| GPS-Stempeln | Niedrig | Mittel | Mobile Zeiterfassung mit Standort |

### 3.6 Finanzen
| Verbesserung | Priorität | Aufwand | Beschreibung |
|-------------|-----------|---------|--------------|
| Swissdec ELM | Hoch | Hoch | Lohnmeldung an AHV/BVG/UVG/Steuer |
| GeBüV-Buchhaltung | Hoch | Hoch | Hashchain, Sequenzen, Archiv |
| Mahnwesen | Hoch | Mittel | 3-Stufen-Mahnlauf automatisiert |
| ISO 20022 | Mittel | Hoch | Zahlungsverkehr (pain.001, camt.053) |
| MWST-Abrechnung | Hoch | Mittel | ESTV-konforme Abrechnung |
| Bank-Import | Mittel | Mittel | camt.053/MT940 |
| Kreditoren | Mittel | Mittel | Eingangsrechnungen, Zahlungen |
| Bexio-Schnittstelle | Niedrig | Hoch | Export/Import zu Bexio |
| Rechnungs-Versand | Hoch | Mittel | E-Mail mit PDF-Anhang |
| QR-Bill korrekt | Hoch | Niedrig | SIX-konform mit allen Referenztypen |

### 3.7 Angebote
| Verbesserung | Priorität | Aufwand | Beschreibung |
|-------------|-----------|---------|--------------|
| Pagination | Hoch | Niedrig | Für grosse Kataloge |
| Service-Duplikation | Mittel | Niedrig | Kopieren + Anpassen |
| Bulk-Operationen | Mittel | Mittel | Mehrere Services gleichzeitig bearbeiten |
| Verfügbarkeitskalender | Mittel | Mittel | Wann ist Service buchbar |
| Buchungsstatistiken | Niedrig | Mittel | Umsatz/Buchungen pro Service |
| Formular Drag&Drop | Mittel | Mittel | Felder per Drag&Drop anordnen |
| Bedingte Felder | Mittel | Mittel | Felder basierend auf Auswahl ein-/ausblenden |

### 3.8 Akademie
| Verbesserung | Priorität | Aufwand | Beschreibung |
|-------------|-----------|---------|--------------|
| Fortschritts-Tracking | Hoch | Mittel | Pro Teilnehmer, pro Kurs |
| Zertifikats-PDF | Mittel | Mittel | Automatische Generierung |
| Video-Streaming | Mittel | Hoch | Integration (Vimeo/YouTube/eigene) |
| Quiz vollständig | Hoch | Mittel | Alle Fragetypen implementieren |
| Kurs-Katalog | Mittel | Mittel | Öffentlich buchbar |
| Bewertungssystem | Niedrig | Niedrig | Sterne + Text-Feedback |

---

## 4. Compliance & Sicherheit

### 4.1 DSGVO/DSG (bereits in Foundation)
| Anforderung | Foundation Port | Status |
|------------|----------------|--------|
| Einwilligung (Art. 6/7) | ConsentPort | Bereit |
| Auskunftsrecht (Art. 15) | — (neuer Port) | Zu implementieren |
| Löschrecht (Art. 17) | CryptoShreddingPort | Bereit |
| Datenportabilität (Art. 20) | — (Export-Service) | Zu implementieren |
| Datenschutz by Design | Alle Ports | Architektonisch |
| Aufbewahrungsfristen | RetentionCategory | Bereit |
| Audit-Trail | AuditPort | Bereit |

### 4.2 Schweizer Arbeitsrecht
| Anforderung | Foundation Port | Status |
|------------|----------------|--------|
| ArG Art. 46 Zeiterfassung | TimeTrackingPort | Bereit |
| ArGV 1 Art. 73 Aufbewahrung | RetentionCategory (5Y) | Bereit |
| Pausen-Regelung | WorkTimeValidation | Bereit |
| Überstunden-Grenzen | WorkTimeValidation | Bereit |

### 4.3 Finanzcompliance
| Anforderung | Foundation Port | Status |
|------------|----------------|--------|
| GeBüV Revisionssicherheit | AuditPort + HashChain | Bereit |
| GoBD Unveränderbarkeit | SequencePort + AuditPort | Bereit |
| Swissdec ELM 5.0/5.5 | SwissdecTransmitterPort | Bereit |
| OR Art. 958f Aufbewahrung | RetentionCategory (10Y) | Bereit |
| MWSTG | — (neuer Service) | Zu implementieren |

---

## 5. Performance-Verbesserungen

| Bereich | Problem | Lösung |
|---------|---------|--------|
| Listen-Rendering | Alle Daten auf einmal geladen | Virtual Scrolling (vue-virtual-scroller) |
| Bundle-Grösse | Alle Module im Initial-Bundle | Code-Splitting pro Modul (Lazy Routes) |
| Bilder | Keine Optimierung | Responsive Images, WebP, Lazy Loading |
| API-Calls | Keine Deduplizierung | TanStack Query mit Caching |
| Re-Renders | Monolithischer Context | Pinia Stores, computed Properties |
| Suche | Client-Side-Filter | Debounced Server-Search + Client-Cache |
| Kalender | Alle Termine geladen | Virtualisierung + Pagination pro Zeitfenster |

---

## 6. Zusammenfassung der Prioritäten

### Muss (MVP)
1. Formular-Validierung in allen Modulen
2. Error-Handling und Loading-States
3. URL-Routing mit Deep-Linking
4. Responsive Design (besonders Kalender)
5. ArG-konforme Zeiterfassung
6. GeBüV-konforme Buchhaltung
7. DSGVO-konforme Datenhaltung
8. Swiss QR-Bill (korrekt nach SIX-Standard)

### Sollte
1. Drag & Drop (Kalender, Schichtplan, Formulare)
2. Online-Buchung (öffentlicher Link)
3. Mahnwesen
4. Import-Funktionen
5. Echtzeit-Dashboard
6. Kalender-Sync
7. Swissdec ELM

### Kann
1. AI-basierte Preisoptimierung
2. Kunden-Portal
3. Mitarbeiter-Portal
4. Partner Hub
5. Bexio-Schnittstelle
6. Video-Streaming in Akademie
