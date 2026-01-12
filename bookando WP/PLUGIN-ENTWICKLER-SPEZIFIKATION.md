# BOOKANDO - ENTWICKLER-SPEZIFIKATION
## WordPress Plugin / SaaS / Mobile App Plattform

**Version:** 1.0.0 | **Zielgruppe:** WordPress-Plugin & SaaS/Cloud Webentwickler

---

## 1. PROJEKT-ÜBERSICHT

**Produktname:** Bookando
**Typ:** Multi-Tenant WordPress Plugin mit SaaS/Cloud und Mobile App Support
**Zielgruppe:** Fahrschulen, Trainingsanbieter, Wellness-Center, Dienstleister
**Kernfunktion:** Umfassendes Buchungs-, Termin-, Kunden-, Mitarbeiter- und Partner-Management

**Technologie-Stack:**
- **Backend:** PHP 8.1+, WordPress 6.0+, MySQL 5.7+, Composer, PSR-4
- **Frontend:** Vue 3.5 (Composition API), TypeScript 5.9 (strict), Vite 7.1, Pinia 3.0
- **Build:** Vite HMR, ESLint, Vitest, Playwright
- **UI:** Headless UI, TipTap, SCSS Design System (65+ Komponenten)
- **Zahlungen:** Stripe, PayPal, Klarna, Mollie
- **Kalender:** Google, Outlook, Apple, CalDAV, iCal

---

## 2. ARCHITEKTUR-ANFORDERUNGEN

### 2.1 Multi-Tenant Architektur
- **Shared Database, Isolated Data:** Alle Tabellen mit `tenant_id` Spalte
- **Tenant-Auflösung (Priorität):**
  1. HTTP Header `X-BOOKANDO-TENANT` (Admin/Dev)
  2. Request Parameter `tenant_id`
  3. User Meta `user_tenant_id`
  4. Subdomain Mapping (konfigurierbar)
  5. Fallback: Tenant ID 1
- **Security:** Capability-Check `bookando_switch_tenant`, Audit-Logging, Daten-Isolation
- **Tabellen:** `wp_bookando_tenants` (id, name, status, time_zone, created_at, updated_at)

### 2.2 Modulare Struktur
- **11 unabhängige Module** mit eigenem `module.json` Manifest
- **Modul-Gruppen:** Core, Operations, CRM, Offers
- **Lizenz-Stufen:** Starter, Professional, Enterprise
- **Abhängigkeiten:** Über `dependencies` Array im Manifest
- **Lifecycle:** BaseModule Pattern (init, register_hooks, activate, deactivate)

### 2.3 REST API
- **Base URL:** `/wp-json/bookando/v1/`
- **Pattern:** `/{module}/{type}/{id?}`
- **Auth:** WordPress Session, JWT Bearer, API Keys, REST Nonce
- **Security:** Rate Limiting, CSRF, XSS-Protection, Input Sanitization
- **Response Format:**
  ```json
  {
    "data": {...},
    "meta": {"success": true, "status": 200, "pagination": {...}},
    "error": {"code": "...", "message": "...", "details": {...}}
  }
  ```

### 2.4 WordPress Integration
- **Rollen:** `bookando_manager`, `bookando_employee` + WordPress native
- **Capabilities:** `manage_bookando_{module}`, `view_bookando_{module}`, etc.
- **Hooks:** `plugins_loaded`, `init`, `admin_menu`, `rest_api_init`
- **Cron Jobs:** License-Verify (daily), Log-Cleanup (daily), Queue-Process (1min)
- **Shortcodes:** `[bookando_booking_form]`, `[bookando_customer_portal]`, `[bookando_employee_portal]`

### 2.5 Datenbankstruktur
- **Präfix:** `wp_bookando_`
- **44+ Tabellen** mit Tenant-Isolation
- **Kernfelder:** `id`, `tenant_id`, `created_at`, `updated_at`, `deleted_at` (soft delete)
- **Indizes:** `idx_{table}_tenant` auf allen Tabellen
- **Foreign Keys:** ON DELETE CASCADE/SET NULL je nach Relation
- **Charset:** utf8mb4_unicode_ci

---

## 3. MODULE - DETAILLIERTE SPEZIFIKATION

### 3.1 CUSTOMERS (Kunden-CRM)
**Pfad:** `/src/modules/customers/`
**Lizenz:** Starter
**Gruppe:** CRM

**Funktionen:**
- Kundenverwaltung (CRUD) mit Soft-Delete
- Felder: Vorname, Nachname, Email, Telefon, Adresse, Geburtstag, Geschlecht, Notizen
- Status: Aktiv, Blockiert, Gelöscht
- Wartelisten-Management
- Feedback-System
- CSV-Export
- Custom Fields Support
- Mobile App Integration
- Aktivitäts-Tracking

**Datenbank:**
- `wp_bookando_users` (id, tenant_id, type='customer', first_name, last_name, email, phone, address, city, zip, country, birthdate, gender, notes, status, created_at, updated_at, deleted_at)

**API Endpoints:**
```
GET    /bookando/v1/customers/customers          # Liste
POST   /bookando/v1/customers/customers          # Erstellen
GET    /bookando/v1/customers/customers/{id}     # Details
PUT    /bookando/v1/customers/customers/{id}     # Aktualisieren
DELETE /bookando/v1/customers/customers/{id}     # Soft Delete
DELETE /bookando/v1/customers/customers/{id}?hard=1  # Hard Delete
POST   /bookando/v1/customers/customers/{id}/block      # Blockieren
POST   /bookando/v1/customers/customers/{id}/activate   # Aktivieren
GET    /bookando/v1/customers/export?format=csv  # CSV Export
```

**UI Komponenten:**
- CustomerList (Tabelle mit Filterbar, Suche, Pagination)
- CustomerForm (Modal: Erstellen/Bearbeiten)
- CustomerDetail (Sidebar mit Tabs: Info, Termine, Zahlungen, Notizen)

---

### 3.2 EMPLOYEES (Mitarbeiter-HRM)
**Pfad:** `/src/modules/employees/`
**Lizenz:** Starter
**Gruppe:** Core

**Funktionen:**
- Mitarbeiterverwaltung (CRUD)
- Felder: Name, Email, Telefon, Badge-ID, Externe-ID, Position, Abteilung, Einstellungsdatum
- Workday Sets (wöchentliche Verfügbarkeit)
  - Mehrere Sets pro Mitarbeiter
  - Zeitslots pro Wochentag (z.B. Mo 9:00-17:00)
- Spezielle Tage (Ausnahmen vom regulären Plan)
- Abwesenheitsverwaltung
  - Anfrage-/Genehmigungsworkflow
  - Status: Pending, Approved, Rejected
  - Typen: Urlaub, Krankheit, Fortbildung, Sonstiges
- Kalender-Integration (Google, Outlook, Apple, CalDAV)
  - OAuth2 Flow
  - Bidirektionale Sync
  - Free/Busy Abfrage
  - ICS Export
- Multi-Kalender Support
- Zeiterfassung (siehe Workday Modul)

**Datenbank:**
- `wp_bookando_users` (type='employee', badge_id, external_id, position, department, hire_date)
- `wp_bookando_workday_sets` (id, tenant_id, employee_id, name, is_default, created_at, updated_at)
- `wp_bookando_workday_slots` (id, set_id, weekday, start_time, end_time)
- `wp_bookando_special_days` (id, tenant_id, employee_id, date, start_time, end_time, type)
- `wp_bookando_days_off` (id, tenant_id, employee_id, start_date, end_date, type, status, reason, approved_by, approved_at)
- `wp_bookando_calendars` (id, tenant_id, employee_id, provider, calendar_id, access_token, refresh_token, expires_at, is_active)

**API Endpoints:**
```
GET    /bookando/v1/employees/employees
POST   /bookando/v1/employees/employees
GET    /bookando/v1/employees/employees/{id}
PUT    /bookando/v1/employees/employees/{id}
DELETE /bookando/v1/employees/employees/{id}
GET    /bookando/v1/employees/employees/{id}/workday-sets
POST   /bookando/v1/employees/employees/{id}/workday-sets
PUT    /bookando/v1/employees/workday-sets/{id}
DELETE /bookando/v1/employees/workday-sets/{id}
GET    /bookando/v1/employees/employees/{id}/days-off
POST   /bookando/v1/employees/employees/{id}/days-off
PUT    /bookando/v1/employees/days-off/{id}
POST   /bookando/v1/employees/days-off/{id}/approve
POST   /bookando/v1/employees/days-off/{id}/reject
GET    /bookando/v1/employees/employees/{id}/calendars
POST   /bookando/v1/employees/employees/{id}/calendars/connect
DELETE /bookando/v1/employees/calendars/{id}
GET    /bookando/v1/employees/employees/{id}/free-busy?start=...&end=...
```

---

### 3.3 APPOINTMENTS (Termine & Events)
**Pfad:** `/src/modules/appointments/`
**Lizenz:** Starter
**Gruppe:** Operations

**Funktionen:**
- Terminverwaltung (Einzeltermine)
- Event-/Kursverwaltung (Mehrere Perioden)
- Event-Perioden (Mehrere Termine pro Kurs)
- Ressourcen-Zuweisung pro Periode:
  - Mitarbeiter (mehrere möglich)
  - Dienstleistungen
  - Locations
  - Ressourcen (Räume, Equipment)
- Status-Workflow:
  - Pending (wartend auf Bestätigung)
  - Approved (vom Admin freigegeben)
  - Confirmed (vom Kunden bestätigt)
  - Cancelled (storniert)
  - No-Show (nicht erschienen)
- Buchungszeitfenster (Anmeldefrist)
- Kapazitätsverwaltung (Min/Max Teilnehmer)
- Preisgestaltung
- Multi-Person Buchungen
- Referral-Tracking (Empfehlungen)
- Provisionsberechnung
- Kalender-Sync (bidirektional)
- iCal/ICS Export
- Online-Buchung (Frontend Shortcode)
- Verfügbarkeitsprüfung

**Datenbank:**
- `wp_bookando_appointments` (id, tenant_id, customer_id, type='single', title, description, start_datetime, end_datetime, status, price, currency, capacity_min, capacity_max, location_id, created_by, notes, referral_source, created_at, updated_at, deleted_at)
- `wp_bookando_events` (id, tenant_id, title, description, type, category_id, image_url, price, currency, capacity_min, capacity_max, booking_deadline_days, status, created_at, updated_at)
- `wp_bookando_event_periods` (id, tenant_id, event_id, period_number, start_datetime, end_datetime, location_id, status)
- `wp_bookando_event_period_resources` (period_id, resource_type='employee|service|location|resource', resource_id)
- `wp_bookando_appointment_attendees` (id, appointment_id, customer_id, status, attended, created_at)

**API Endpoints:**
```
GET    /bookando/v1/appointments/appointments
POST   /bookando/v1/appointments/appointments
GET    /bookando/v1/appointments/appointments/{id}
PUT    /bookando/v1/appointments/appointments/{id}
DELETE /bookando/v1/appointments/appointments/{id}
GET    /bookando/v1/appointments/events
POST   /bookando/v1/appointments/events
GET    /bookando/v1/appointments/events/{id}
PUT    /bookando/v1/appointments/events/{id}
GET    /bookando/v1/appointments/events/{id}/periods
POST   /bookando/v1/appointments/events/{id}/periods
PUT    /bookando/v1/appointments/periods/{id}
POST   /bookando/v1/appointments/periods/{id}/assign-resources
GET    /bookando/v1/appointments/timeline?start=...&end=...&employee_id=...
POST   /bookando/v1/appointments/check-availability
GET    /bookando/v1/appointments/{id}/export.ics
```

---

### 3.4 FINANCE (Finanzen)
**Pfad:** `/src/modules/finance/`
**Lizenz:** Starter
**Gruppe:** Operations

**Funktionen:**
- Rechnungserstellung (automatisch & manuell)
- Zahlungsabwicklung (Stripe, PayPal, Klarna, Mollie)
- Zahlungsverfolgung
- Transaktionshistorie
- Gutschriften (Credit Notes)
- FIBU-Export (CSV, DATEV)
- Provisionsberechnung (für Partner)
- Mehrwährungsunterstützung
- Steuerberechnung (konfigurierbar)
- Payment Gateway Webhooks
- Zahlungserinnerungen
- Rechnungsvorlagen

**Datenbank:**
- `wp_bookando_payments` (id, tenant_id, invoice_number, customer_id, appointment_id, amount, currency, payment_method, gateway_transaction_id, status='pending|completed|failed|refunded', paid_at, created_at, updated_at)
- `wp_bookando_invoices` (id, tenant_id, invoice_number, customer_id, items_json, subtotal, tax_rate, tax_amount, total, currency, status, issued_at, due_at, paid_at)
- `wp_bookando_invoice_items` (id, invoice_id, description, quantity, unit_price, total)

**API Endpoints:**
```
GET    /bookando/v1/finance/payments
POST   /bookando/v1/finance/payments
GET    /bookando/v1/finance/payments/{id}
GET    /bookando/v1/finance/invoices
POST   /bookando/v1/finance/invoices
GET    /bookando/v1/finance/invoices/{id}
POST   /bookando/v1/finance/invoices/{id}/send
POST   /bookando/v1/finance/payments/{id}/refund
GET    /bookando/v1/finance/export?format=csv&from=...&to=...
POST   /bookando/v1/finance/webhook/stripe
POST   /bookando/v1/finance/webhook/paypal
```

---

### 3.5 OFFERS (Angebote)
**Pfad:** `/src/modules/offers/`
**Lizenz:** Starter
**Gruppe:** Offers

**Funktionen:**
- Service-Katalog
  - Dienstleistungen definieren
  - Preise, Dauer, Beschreibung
  - Kategorien
  - Verfügbarkeit
- Kurse
  - Kursdefinitionen
  - Lehrplan
  - Preise & Pakete
- Pakete/Bundles
  - Mehrere Services kombinieren
  - Paketpreise
  - Gültigkeit
- Gutscheine
  - Gutscheincodes
  - Wertgutscheine
  - Rabattgutscheine
  - Gültigkeitsdauer
  - Einlöseverfolgung

**Datenbank:**
- `wp_bookando_services` (id, tenant_id, name, description, category_id, duration_minutes, price, currency, is_active)
- `wp_bookando_courses` (id, tenant_id, name, description, curriculum_json, price, currency, is_active)
- `wp_bookando_packages` (id, tenant_id, name, description, service_ids_json, price, currency, validity_days, is_active)
- `wp_bookando_vouchers` (id, tenant_id, code, type='value|discount', value, currency, expiry_date, max_uses, times_used, is_active)

**API Endpoints:**
```
GET    /bookando/v1/offers/services
POST   /bookando/v1/offers/services
GET    /bookando/v1/offers/courses
POST   /bookando/v1/offers/courses
GET    /bookando/v1/offers/packages
POST   /bookando/v1/offers/packages
GET    /bookando/v1/offers/vouchers
POST   /bookando/v1/offers/vouchers
POST   /bookando/v1/offers/vouchers/validate
```

---

### 3.6 ACADEMY (Akademie)
**Pfad:** `/src/modules/academy/`
**Lizenz:** Starter
**Gruppe:** Operations

**Funktionen:**
- Kursverwaltung (Lehrveranstaltungen)
- Lernplan-Erstellung
- Lerner-Tracking (Fortschritt)
- Quiz & Prüfungssystem
- Zertifikatsverwaltung
- Kursmaterialien (Dokumente, Videos)
- Lernpfade
- Leistungsbewertung
- Kursabschluss-Tracking

**Datenbank:**
- `wp_bookando_academy_courses` (id, tenant_id, title, description, duration_hours, level, is_active)
- `wp_bookando_academy_lessons` (id, course_id, title, content, order_number, duration_minutes)
- `wp_bookando_academy_enrollments` (id, tenant_id, course_id, student_id, status, progress_percentage, started_at, completed_at)
- `wp_bookando_academy_quizzes` (id, lesson_id, title, questions_json, passing_score)
- `wp_bookando_academy_certificates` (id, tenant_id, enrollment_id, certificate_number, issued_at, expires_at)

**API Endpoints:**
```
GET    /bookando/v1/academy/courses
GET    /bookando/v1/academy/courses/{id}/lessons
POST   /bookando/v1/academy/enrollments
GET    /bookando/v1/academy/enrollments/{id}/progress
POST   /bookando/v1/academy/quizzes/{id}/submit
GET    /bookando/v1/academy/certificates/{id}
```

---

### 3.7 RESOURCES (Ressourcen)
**Pfad:** `/src/modules/resources/`
**Lizenz:** Starter
**Gruppe:** Operations

**Funktionen:**
- Location-Management
  - Standorte mit Adresse
  - GPS-Koordinaten
  - Öffnungszeiten
  - Kontaktinformationen
- Raum-/Facility-Management
  - Räume, Studios, Säle
  - Kapazität
  - Ausstattung
- Equipment-Tracking
  - Fahrzeuge, Geräte, Materialien
  - Seriennummern
  - Wartungshistorie
- Verfügbarkeitsfenster
- Mengenverwaltung (Quantity)
- Ressourcenzuweisung pro Event-Periode
- Konfliktserkennung

**Datenbank:**
- `wp_bookando_locations` (id, tenant_id, name, address, city, zip, country, latitude, longitude, phone, email, is_active)
- `wp_bookando_resources` (id, tenant_id, type='room|equipment|vehicle', name, description, location_id, quantity, is_active)
- `wp_bookando_resource_availability` (id, resource_id, weekday, start_time, end_time)

**API Endpoints:**
```
GET    /bookando/v1/resources/locations
POST   /bookando/v1/resources/locations
GET    /bookando/v1/resources/resources
POST   /bookando/v1/resources/resources
GET    /bookando/v1/resources/resources/{id}/availability?date=...
```

---

### 3.8 WORKDAY (Arbeitstag)
**Pfad:** `/src/modules/workday/`
**Lizenz:** Starter
**Gruppe:** Operations

**Funktionen:**
- **Zeiterfassung:**
  - Clock In/Out (Stempeln)
  - Aktive Timer-Verfolgung
  - Pausenzeit-Erfassung
  - Manuelle Zeiteinträge
  - Zeiteintrags-Notizen
  - Überstunden-Berechnung
- **Schicht-Planung:**
  - Schichtvorlagen erstellen
  - Schicht-Zuweisung
  - Wiederkehrende Schichtmuster
  - Schicht-Tausch
  - Schicht-Typen (Früh, Spät, Nacht)
- **Anwesenheitsverfolgung:**
  - Check-in/Check-out
  - Anwesenheitsnotizen
  - Status-Tracking (Anwesend, Abwesend, Verspätet)
- **Arbeitsplan-Management:**
  - Tagesplan
  - Wochenmuster
  - Mitarbeiter-Zuweisung
- **Kalender-Integration:**
  - Sync mit Employee-Kalendern
- **Reporting:**
  - Arbeitsstunden-Reports
  - Anwesenheits-Reports

**Datenbank:**
- `wp_bookando_time_entries` (id, tenant_id, employee_id, start_time, end_time, break_minutes, total_hours, notes, created_at, updated_at)
- `wp_bookando_active_timers` (id, tenant_id, employee_id, start_time, break_start_time, created_at)
- `wp_bookando_shifts` (id, tenant_id, template_id, employee_id, date, start_time, end_time, type, status, created_at)
- `wp_bookando_shift_templates` (id, tenant_id, name, start_time, end_time, type, days_of_week_json, is_active)
- `wp_bookando_workday_schedule` (id, tenant_id, name, description, is_active, created_at)
- `wp_bookando_workday_assignments` (id, schedule_id, employee_id, weekday, start_time, end_time)
- `wp_bookando_shift_attendance` (id, shift_id, employee_id, check_in_time, check_out_time, status, notes)

**API Endpoints:**
```
GET    /bookando/v1/workday/time-entries?employee_id=...&from=...&to=...
POST   /bookando/v1/workday/time-entries/start
POST   /bookando/v1/workday/time-entries/stop
POST   /bookando/v1/workday/time-entries/break-start
POST   /bookando/v1/workday/time-entries/break-end
GET    /bookando/v1/workday/shifts?date=...&employee_id=...
POST   /bookando/v1/workday/shifts
PUT    /bookando/v1/workday/shifts/{id}
DELETE /bookando/v1/workday/shifts/{id}
GET    /bookando/v1/workday/shift-templates
POST   /bookando/v1/workday/shift-templates
GET    /bookando/v1/workday/calendar?employee_id=...&month=...
POST   /bookando/v1/workday/attendance/check-in
POST   /bookando/v1/workday/attendance/check-out
GET    /bookando/v1/workday/reports/hours?from=...&to=...
```

---

### 3.9 PARTNER HUB (Partner-Netzwerk)
**Pfad:** `/src/modules/partnerhub/`
**Lizenz:** Professional
**Gruppe:** Operations

**Funktionen:**
- **Partner-Verwaltung:**
  - Firmeninformationen
  - Vertragsmanagement (AVV - Auftragsverarbeitungsverträge)
  - API-Authentifizierung (API-Keys)
  - Provisions-Konfiguration
  - Status-Verwaltung (Aktiv, Inaktiv, Gesperrt)
- **Service-/Event-Sharing:**
  - Listing-Mapping (Angebote zwischen Partnern teilen)
  - Bidirektionale Synchronisation
  - Override-Optionen (Titel, Beschreibung, Preis)
  - Sync-Status-Tracking
- **Preis-Regeln:**
  - Festpreis
  - Prozentuale Aufschläge/Rabatte
  - Min/Max Preise
  - Gültigkeit (Zeitraum)
- **Verfügbarkeits-Regeln:**
  - Pufferzeit vor/nach Buchungen
  - Max. Buchungen pro Tag/Woche
  - Genehmigungsworkflow
  - Wochentag-/Zeitbeschränkungen
  - Blackout-Perioden
- **Feed-Export:**
  - Formate: JSON, XML, iCal, CSV
  - Filterung nach Typ, Kategorien, Standorten
  - Access Tokens
  - IP-Whitelist
  - Rate Limiting
  - Zugriffsstatistiken
- **Provisions-Tracking:**
  - Automatische Berechnung
  - Transaktionshistorie
  - Umsatz-Reports
  - Offene Zahlungen
- **DSGVO-konforme Datenfreigabe:**
  - Explizite Einwilligungsverwaltung (Art. 6 DSGVO)
  - Zweckbindung (Art. 28 DSGVO)
  - Datenkategorien-Auswahl
  - Zeitlich begrenzte Einwilligungen
  - Jederzeit widerrufbar
  - Audit-Trail (Art. 30 DSGVO)
  - Automatische Datenlöschung
  - Löschbestätigung

**Datenbank:**
- `wp_bookando_partners` (id, tenant_id, company_name, contact_person, email, phone, api_key, api_secret, commission_percentage, avv_contract_url, status, ip_whitelist_json, created_at, updated_at)
- `wp_bookando_partner_mappings` (id, tenant_id, partner_id, local_type, local_id, partner_type, partner_id, sync_direction, override_json, last_synced_at, sync_status)
- `wp_bookando_partner_rules` (id, tenant_id, partner_id, rule_type='price|availability', applies_to_type, applies_to_id, config_json, is_active)
- `wp_bookando_partner_feeds` (id, tenant_id, partner_id, slug, format, access_token, filters_json, ip_whitelist_json, rate_limit, access_count, last_accessed_at, is_active)
- `wp_bookando_partner_transactions` (id, tenant_id, partner_id, appointment_id, amount, commission_amount, status, settled_at, created_at)
- `wp_bookando_partner_consents` (id, tenant_id, partner_id, customer_id, purpose, data_categories_json, legal_basis, granted_at, expires_at, revoked_at, status)
- `wp_bookando_partner_audit_logs` (id, tenant_id, partner_id, action, resource_type, resource_id, user_id, ip_address, user_agent, created_at)
- `wp_bookando_partner_data_shares` (id, consent_id, shared_at, data_snapshot_json, access_log_json)

**API Endpoints:**
```
GET    /bookando/v1/partnerhub/partners
POST   /bookando/v1/partnerhub/partners
GET    /bookando/v1/partnerhub/partners/{id}
PUT    /bookando/v1/partnerhub/partners/{id}
DELETE /bookando/v1/partnerhub/partners/{id}
POST   /bookando/v1/partnerhub/partners/{id}/regenerate-api-key
GET    /bookando/v1/partnerhub/mappings?partner_id=...
POST   /bookando/v1/partnerhub/mappings
POST   /bookando/v1/partnerhub/mappings/{id}/sync
GET    /bookando/v1/partnerhub/rules?partner_id=...
POST   /bookando/v1/partnerhub/rules
PUT    /bookando/v1/partnerhub/rules/{id}
GET    /bookando/v1/partnerhub/feeds?partner_id=...
POST   /bookando/v1/partnerhub/feeds
POST   /bookando/v1/partnerhub/feeds/{id}/regenerate-token
GET    /bookando/v1/partnerhub/feed/{slug}?token={access_token}
GET    /bookando/v1/partnerhub/transactions?partner_id=...&status=...
POST   /bookando/v1/partnerhub/transactions/{id}/settle
GET    /bookando/v1/partnerhub/partners/{id}/revenue?from=...&to=...
GET    /bookando/v1/partnerhub/consents?customer_id=...&partner_id=...
POST   /bookando/v1/partnerhub/consents
POST   /bookando/v1/partnerhub/consents/{id}/grant
POST   /bookando/v1/partnerhub/consents/{id}/revoke
POST   /bookando/v1/partnerhub/data-share
GET    /bookando/v1/partnerhub/audit-logs?partner_id=...&from=...&to=...
GET    /bookando/v1/partnerhub/dashboard
```

**DSGVO-Anforderungen:**
- Art. 6 DSGVO: Rechtsgrundlage für Datenverarbeitung
- Art. 28 DSGVO: Auftragsverarbeitung (AVV)
- Art. 30 DSGVO: Verzeichnis der Verarbeitungstätigkeiten
- Zweckbindung bei allen Data-Shares
- Audit-Trail für alle Partner-Aktivitäten
- Automatische Löschung nach Widerruf

---

### 3.10 TOOLS (Werkzeuge)
**Pfad:** `/src/modules/tools/`
**Lizenz:** Starter (keine Lizenz erforderlich)
**Gruppe:** Core

**Funktionen:**
- **Reports (Berichte):**
  - Kunden-Reports
  - Mitarbeiter-Reports
  - Finanz-Reports
  - Termin-Statistiken
  - Partner-Umsatz
  - Custom Reports
- **Kursplaner:**
  - Visueller Kursplan-Designer
  - Drag & Drop Planung
  - Ressourcenzuweisung
- **Zeiterfassung:**
  - Admin-Interface für Time Tracking
  - Zeiteinträge bearbeiten
  - Reports generieren
- **Dienstplanung:**
  - Schicht-Planer Interface
  - Mitarbeiter-Zuweisung
  - Konflikt-Erkennung
- **Buchungsformulare:**
  - Formular-Builder (Drag & Drop)
  - Custom Fields definieren
  - Design anpassen
  - Einbettungscode generieren
- **Benachrichtigungen:**
  - E-Mail-Templates verwalten
  - SMS-Templates (geplant)
  - Template-Variablen
  - Trigger-Bedingungen
  - Multi-Sprache
- **Design:**
  - CSS-Variablen-Editor (1296 Zeilen)
  - Branding (Logo, Farben)
  - Custom Styling
  - RTL-Support
  - Responsive-Vorschau
- **System-Tools:**
  - Activity-Log-Viewer
  - Custom Fields Management
  - Systemdiagnose
  - Cache-Verwaltung

**Datenbank:**
- `wp_bookando_custom_fields` (id, tenant_id, entity_type, field_name, field_type, is_required, options_json, order_number)
- `wp_bookando_form_templates` (id, tenant_id, name, fields_json, design_json, is_active)
- `wp_bookando_notification_templates` (id, tenant_id, trigger_event, subject, body, variables_json, is_active)

**API Endpoints:**
```
GET    /bookando/v1/tools/reports/{type}?from=...&to=...
GET    /bookando/v1/tools/activity-logs?from=...&to=...&user_id=...
GET    /bookando/v1/tools/custom-fields?entity_type=...
POST   /bookando/v1/tools/custom-fields
GET    /bookando/v1/tools/form-templates
POST   /bookando/v1/tools/form-templates
GET    /bookando/v1/tools/notification-templates
POST   /bookando/v1/tools/notification-templates
POST   /bookando/v1/tools/test-notification/{id}
```

**UI Tabs:**
1. Reports - Interaktive Berichte
2. Kursplaner - Visueller Planer
3. Zeiterfassung - Time Tracking Admin
4. Dienstplanung - Shift Planner
5. Buchungsformulare - Form Builder
6. Benachrichtigungen - Notification Manager
7. Design - CSS/Branding Editor

---

### 3.11 SETTINGS (Einstellungen)
**Pfad:** `/src/modules/settings/`
**Lizenz:** Starter (keine Lizenz erforderlich)
**Gruppe:** Core

**Funktionen:**
- **Allgemeine Einstellungen:**
  - Sprachauswahl (de, en, fr, it)
  - Zeitzone
  - Datums-/Zeitformate
  - Währung
  - Ländereinstellungen
- **Firmendaten:**
  - Firmenname, Adresse, Kontakt
  - Rechtliche Informationen
  - Steuer-ID
  - Logo-Upload
- **Integrations-Einstellungen:**
  - Kalender (Google, Outlook, Apple, CalDAV)
  - Zahlungs-Gateways (Stripe, PayPal, Klarna, Mollie)
  - Videokonferenz (Zoom, Teams, Jitsi)
  - E-Mail-Service (SMTP, SendGrid, Mailgun)
- **Lizenz-Management:**
  - Lizenzschlüssel-Eingabe
  - Plan-Informationen (Starter, Professional, Enterprise)
  - Feature-Verfügbarkeit
  - Automatische Verifizierung (täglich)
  - Lizenz-Status
- **Modul-Konfiguration:**
  - Module aktivieren/deaktivieren
  - Feature-Toggles
  - Abhängigkeits-Prüfung
  - Pro-Modul Einstellungen
- **Berechtigungen:**
  - Rollen-Management
  - Capability-Zuweisung
  - Pro-Modul Zugriffsrechte

**Datenbank:**
- `wp_bookando_settings` (id, tenant_id, category, key, value, type, is_encrypted, updated_at)
- `wp_bookando_licenses` (id, tenant_id, license_key, plan, expires_at, status, verified_at, features_json)

**API Endpoints:**
```
GET    /bookando/v1/settings/general
PUT    /bookando/v1/settings/general
GET    /bookando/v1/settings/company
PUT    /bookando/v1/settings/company
GET    /bookando/v1/settings/integrations
PUT    /bookando/v1/settings/integrations
POST   /bookando/v1/settings/integrations/{provider}/connect
DELETE /bookando/v1/settings/integrations/{provider}/disconnect
GET    /bookando/v1/settings/license
POST   /bookando/v1/settings/license/activate
POST   /bookando/v1/settings/license/verify
GET    /bookando/v1/settings/modules
PUT    /bookando/v1/settings/modules/{module}/activate
PUT    /bookando/v1/settings/modules/{module}/deactivate
```

---

## 4. PLATTFORM-ÜBERGREIFENDE ANFORDERUNGEN

### 4.1 WordPress Plugin
**Installation:**
- Standalone WordPress Plugin
- Über WordPress.org oder manueller Upload
- Aktivierung über WordPress Admin
- Automatische Datenbank-Installation

**Anforderungen:**
- WordPress 6.0+
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.2+
- Composer für Dependencies
- Node.js 18+ für Frontend-Build (Entwicklung)

**Integration:**
- WordPress User-System (WP_User)
- WordPress Roles & Capabilities
- WordPress Cron
- WordPress REST API
- WordPress Admin Menu
- WordPress Shortcodes
- WordPress Widgets (optional)

**Deployment:**
- Build: `npm run build` (produziert minified JS/CSS)
- Enqueue Assets über `wp_enqueue_script/style`
- Textdomain: `bookando`
- Translation-ready (.po/.mo Files)

### 4.2 SaaS/Cloud Plattform
**Architektur:**
- Multi-Tenant von Grund auf
- Subdomain-basierte Tenant-Auflösung (z.B. `client1.bookando.app`)
- Zentrale WordPress-Installation
- Shared Database mit Tenant-Isolation
- API-First Approach

**Infrastruktur:**
- Webserver: Apache 2.4+ / Nginx 1.18+
- PHP-FPM für Performance
- MySQL Master-Slave Replikation (optional)
- Redis für Caching (optional)
- CDN für Assets (Cloudflare, AWS CloudFront)
- SSL/TLS (Let's Encrypt)

**Skalierung:**
- Horizontal Skalierung (Load Balancer)
- Database Sharding (für sehr große Deployments)
- Queue-System (wp_bookando_queue)
- Cron-Job Management
- Background Processing

**Sicherheit:**
- Rate Limiting pro Tenant
- DDoS Protection
- Firewall (WAF)
- Backup-Strategie (täglich)
- SSL-Erzwingung
- 2FA für Admin-Logins (geplant)

**Monitoring:**
- Error Logging (wp_bookando_activity_log)
- Performance Monitoring
- Uptime Monitoring
- Alert-System

### 4.3 Mobile App (iOS/Android)
**Technologie-Stack (Empfehlung):**
- React Native / Flutter
- TypeScript
- REST API Kommunikation
- JWT Authentication
- Offline-Modus (Local Storage)
- Push Notifications

**Features:**
- **Kunden-App:**
  - Buchungen erstellen
  - Termine ansehen/verwalten
  - Zahlungen durchführen
  - Benachrichtigungen erhalten
  - Profil verwalten
- **Mitarbeiter-App:**
  - Zeiterfassung (Clock In/Out)
  - Termine ansehen
  - Schichtplan ansehen
  - Abwesenheiten beantragen
  - Push-Benachrichtigungen
- **Admin-App:**
  - Dashboard
  - Schnellaktionen (Termine erstellen, Kunden hinzufügen)
  - Benachrichtigungen

**API Integration:**
- Alle Endpoints über REST API
- Token-basierte Auth (JWT)
- Offline Queue (Sync bei Verbindung)
- Real-time Updates (WebSocket optional)

**App Stores:**
- Apple App Store (iOS)
- Google Play Store (Android)
- Enterprise Distribution (optional)

---

## 5. DESIGN SYSTEM & UI/UX

### 5.1 Design Tokens
**Farben:**
- Primary: `--color-primary` (blau)
- Secondary: `--color-secondary`
- Success: `--color-success` (grün)
- Warning: `--color-warning` (gelb)
- Error: `--color-error` (rot)
- Neutral: `--color-gray-50` bis `--color-gray-900`

**Typography:**
- Font Family: System Font Stack
- Größen: `--font-size-xs` bis `--font-size-5xl`
- Gewichte: `--font-weight-light` bis `--font-weight-black`

**Spacing:**
- `--spacing-0` bis `--spacing-96` (0px bis 384px)
- Basiert auf 4px Grid

**Border Radius:**
- `--radius-sm`, `--radius-md`, `--radius-lg`, `--radius-full`

**Shadows:**
- `--shadow-sm`, `--shadow-md`, `--shadow-lg`, `--shadow-xl`

### 5.2 Komponenten-Bibliothek (65+ Komponenten)
**Layout:**
- AppContainer, AppGrid, AppStack, AppSidebar, AppHeader

**Navigation:**
- AppTabs, AppBreadcrumb, AppPagination

**Forms:**
- AppInput, AppTextarea, AppSelect, AppCheckbox, AppRadio, AppDatePicker, AppTimePicker, AppFileUpload

**Data Display:**
- AppTable, AppCard, AppBadge, AppAvatar, AppTooltip, AppPopover

**Feedback:**
- AppAlert, AppModal, AppToast, AppSpinner, AppProgressBar

**Actions:**
- AppButton, AppIconButton, AppDropdown, AppMenu

**Specialized:**
- AppCalendar, AppTimeline, AppFilterBar, AppChart

### 5.3 Responsive Design
- Mobile-First Approach
- Breakpoints: 640px, 768px, 1024px, 1280px, 1536px
- Touch-optimiert (44px Mindestgröße für Buttons)
- Hamburger-Menü auf Mobile
- Responsive Tables (horizontal scroll)

### 5.4 Accessibility (WCAG 2.1 Level AA)
- Keyboard-Navigation
- Screen Reader Support
- ARIA-Labels
- Fokus-Indikatoren
- Kontrast-Verhältnis 4.5:1
- Skip-Links

### 5.5 RTL Support
- Automatische Richtungsumkehr für RTL-Sprachen (Arabisch, Hebräisch)
- CSS Logical Properties
- Mirrored Icons

---

## 6. TECHNISCHE SPEZIFIKATIONEN

### 6.1 Performance-Anforderungen
- Seitenladezeit: < 2 Sekunden (Desktop), < 3 Sekunden (Mobile)
- Time to Interactive: < 3.5 Sekunden
- API Response Time: < 200ms (95. Perzentil)
- Database Queries: < 10 pro Request (optimiert)
- Asset Sizes: JS < 300KB (gzipped), CSS < 50KB (gzipped)

### 6.2 Browser-Support
- Chrome/Edge: Letzte 2 Versionen
- Firefox: Letzte 2 Versionen
- Safari: Letzte 2 Versionen
- Mobile Safari: iOS 13+
- Chrome Android: Letzte 2 Versionen

### 6.3 Datenschutz & Compliance
**DSGVO (GDPR):**
- Einwilligungsverwaltung
- Recht auf Auskunft (Export)
- Recht auf Löschung (Soft + Hard Delete)
- Recht auf Datenportabilität (CSV Export)
- Verarbeitungsverzeichnis (Art. 30)
- AVV für Partner (Art. 28)
- Datenschutz durch Technikgestaltung (Privacy by Design)

**Weitere Standards:**
- PCI DSS (für Zahlungen über Gateways)
- ISO 27001 (empfohlen für SaaS)
- SOC 2 Type II (empfohlen für SaaS)

### 6.4 Testing-Anforderungen
**Backend:**
- PHPUnit Tests (Ziel: 80% Code Coverage)
- Integration Tests für API Endpoints
- Database Constraint Tests

**Frontend:**
- Vitest Unit Tests (Ziel: 70% Coverage)
- Playwright E2E Tests für kritische User Flows
- Component Tests (Storybook)

**Manual Testing:**
- Cross-Browser Testing
- Mobile Device Testing
- Accessibility Testing (WAVE, axe)

---

## 7. DEPLOYMENT & DEVOPS

### 7.1 Entwicklungsumgebung
**Lokale Entwicklung:**
```bash
# Backend
composer install
wp bookando install  # Datenbank-Installation

# Frontend
npm install
npm run dev  # Vite Dev Server mit HMR
```

**Git Workflow:**
- Main Branch: `main` (produktionsbereit)
- Development Branch: `develop`
- Feature Branches: `feature/module-name`
- Hotfix Branches: `hotfix/issue-description`

### 7.2 Build-Prozess
```bash
# Production Build
npm run build  # Kompiliert Vue/TS zu minified JS/CSS

# Output:
# src/Core/Design/assets/css/admin-ui.css
# src/modules/{module}/assets/dist/
```

### 7.3 CI/CD (Empfehlung)
**GitHub Actions / GitLab CI:**
1. Linting (ESLint, PHPStan)
2. Unit Tests (Vitest, PHPUnit)
3. Build Assets
4. E2E Tests (Playwright)
5. Security Scan (Snyk, OWASP)
6. Deploy zu Staging
7. Manual Approval
8. Deploy zu Production

### 7.4 Versionierung
- Semantic Versioning (MAJOR.MINOR.PATCH)
- Changelog generieren (conventional commits)
- Git Tags für Releases

---

## 8. DOKUMENTATION FÜR ENTWICKLER

### 8.1 Code-Struktur Konventionen
**PHP:**
- PSR-4 Autoloading
- PSR-12 Coding Style
- Strict Types: `declare(strict_types=1);`
- Type Hints für alle Parameter & Return Values
- PHPDoc für alle Public Methods

**TypeScript/Vue:**
- Composition API (kein Options API)
- TypeScript Strict Mode
- ESLint Airbnb Style Guide
- Single File Components (.vue)
- Prop Types validieren

**Naming Conventions:**
- Classes: PascalCase
- Functions/Methods: camelCase
- Constants: UPPER_SNAKE_CASE
- Files: kebab-case.php / PascalCase.vue
- Database Tables: wp_bookando_{table_name}
- Database Columns: snake_case

### 8.2 Module Manifest (module.json)
```json
{
  "slug": "module-name",
  "plan": "starter|professional|enterprise",
  "version": "1.0.0",
  "tenant_required": true,
  "license_required": true,
  "features_required": ["feature1"],
  "group": "core|operations|crm|offers",
  "is_saas": true,
  "has_admin": true,
  "supports_webhook": true,
  "supports_offline": false,
  "supports_calendar": false,
  "name": {
    "en": "Module Name",
    "de": "Modulname",
    "fr": "Nom du module",
    "it": "Nome del modulo"
  },
  "description": {
    "en": "Description",
    "de": "Beschreibung"
  },
  "visible": true,
  "menu_icon": "dashicons-admin-generic",
  "menu_position": 30,
  "dependencies": ["employees", "customers"],
  "tabs": [
    {"id": "overview", "label": {"de": "Übersicht"}},
    {"id": "settings", "label": {"de": "Einstellungen"}}
  ]
}
```

### 8.3 REST API Handler Pattern
```php
namespace Bookando\Modules\{Module}\Api;

class {Type}Handler {
    public function get_items(WP_REST_Request $request): WP_REST_Response {
        // Tenant-ID aus Request
        $tenant_id = TenantManager::currentTenantId();

        // Pagination
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;

        // Query
        $results = $this->service->get_all($tenant_id, $page, $per_page);

        // Response
        return new WP_REST_Response([
            'data' => $results['items'],
            'meta' => [
                'success' => true,
                'pagination' => $results['pagination']
            ]
        ], 200);
    }
}
```

### 8.4 Vue Component Pattern
```vue
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Customer } from '../types'

const props = defineProps<{
  customer: Customer
}>()

const emit = defineEmits<{
  update: [customer: Customer]
  delete: [id: number]
}>()

const { t } = useI18n()
const isEditing = ref(false)
</script>

<template>
  <AppCard>
    <!-- Template hier -->
  </AppCard>
</template>
```

---

## 9. PRIORITÄTEN & ROADMAP (EMPFEHLUNG)

### Phase 1: MVP (3-4 Monate)
- Core Module: Settings, Tools
- CRM: Customers, Employees
- Operations: Appointments, Resources, Offers
- Basic Frontend (Booking Form)
- WordPress Plugin Paket

### Phase 2: Extended Features (2-3 Monate)
- Finance Modul (Zahlungen, Rechnungen)
- Workday Modul (Zeiterfassung, Schichten)
- Academy Modul (Kurse, Zertifikate)
- Mobile App (Beta)

### Phase 3: Enterprise (2-3 Monate)
- Partner Hub (komplett)
- SaaS Platform Deployment
- Multi-Tenant Subdomain Routing
- Advanced Reporting

### Phase 4: Optimization (laufend)
- Performance Tuning
- Security Audits
- Testing Coverage auf 80%+
- Dokumentation vervollständigen

---

## 10. KRITISCHE SUCCESS FAKTOREN

### 10.1 Must-Have Features
✅ Multi-Tenant mit strikter Datenisolation
✅ DSGVO-Compliance (Einwilligung, Löschung, Export)
✅ REST API für alle Operationen
✅ WordPress Integration (Roles, Capabilities, Cron)
✅ Responsive Design (Mobile-First)
✅ Modulare Architektur (Plugins für Module)
✅ Kalender-Integration (Google, Outlook, etc.)
✅ Zahlungs-Integration (Stripe, PayPal, etc.)
✅ Internationalisierung (de, en, fr, it)

### 10.2 Security Checklist
✅ SQL Injection Prevention (`$wpdb->prepare()`)
✅ XSS Prevention (`sanitize_text_field()`, `esc_html()`)
✅ CSRF Protection (WordPress Nonces, REST Nonces)
✅ Input Validation auf allen Endpoints
✅ Rate Limiting (API & Forms)
✅ Audit Logging (alle kritischen Aktionen)
✅ Encrypted Sensitive Data (API Keys, Tokens)
✅ Role-Based Access Control (RBAC)
✅ IP Whitelisting (für Partner Feeds)

### 10.3 Performance Checklist
✅ Database Indizes auf allen Foreign Keys & Tenant IDs
✅ Query-Optimierung (< 10 Queries pro Request)
✅ Asset Minification & Compression (Gzip/Brotli)
✅ Lazy Loading für Bilder & Components
✅ Caching (Object Cache, Transients, CDN)
✅ Background Processing für lange Tasks (Queue)
✅ Pagination für alle Listen (Standard: 20 Items)

---

## 11. GLOSSAR

**AVV:** Auftragsverarbeitungsvertrag (Art. 28 DSGVO)
**Tenant:** Mandant/Client in Multi-Tenant System
**Workday Set:** Wöchentlicher Verfügbarkeitsplan eines Mitarbeiters
**Event Period:** Einzelner Termin innerhalb eines Kurses/Events
**Soft Delete:** Logisches Löschen (deleted_at gesetzt, aber Daten bleiben)
**Hard Delete:** Physisches Löschen aus Datenbank
**Feed:** Exportierter Daten-Stream für Partner (JSON/XML/iCal/CSV)
**Mapping:** Zuordnung eines lokalen Angebots zu Partner-Angebot
**Consent:** DSGVO-Einwilligung zur Datenverarbeitung
**Audit Log:** Protokoll aller sicherheitsrelevanten Aktionen

---

## ZUSAMMENFASSUNG FÜR ENTWICKLER

Dies ist ein **hochkomplexes, enterprise-grade WordPress Plugin** mit folgenden Kernmerkmalen:

1. **11 Module** (Customers, Employees, Appointments, Finance, Offers, Academy, Resources, Workday, Partner Hub, Tools, Settings)
2. **Multi-Tenant Architektur** mit Subdomain-Support
3. **44+ Datenbanktabellen** mit strikter Tenant-Isolation
4. **REST API** mit 100+ Endpoints
5. **Vue 3 + TypeScript Frontend** mit 65+ Komponenten
6. **DSGVO-Compliance** by Design
7. **WordPress/SaaS/Mobile** Plattform-Support
8. **Umfangreiche Integrationen** (Kalender, Zahlungen, Partner-Netzwerk)

**Technologie-Stack:** PHP 8.1, WordPress 6.0+, Vue 3, TypeScript, MySQL, Vite, Pinia, Composer

**Ziel:** All-in-One Lösung für Buchungs-, Kunden-, Mitarbeiter- und Partner-Management mit höchsten Anforderungen an Sicherheit, Datenschutz und Skalierbarkeit.

---

**Dokumentversion:** 1.0
**Stand:** 2025-11-20
**Erstellt für:** WordPress-Plugin & SaaS/Cloud Webentwickler
**Kontakt:** Patrik Augello
