# Phase 0 — Fast Inventory

> Automatisch generiert aus Codeanalyse des Bookando-Repositories.
> Referenz: `bookando WP/` als Root-Pfad des Plugins.

---

## 1. Verzeichnisbaum (fokussiert)

```
bookando WP/
├── bookando.php                          # Plugin-Einstiegspunkt (352 Zeilen)
├── composer.json                         # PHP ≥8.1, PSR-4, Stripe/PayPal/Mollie/Klarna SDKs
├── package.json                          # Vue 3, Vite 7, Pinia, Tailwind, Playwright
├── phpunit.xml.dist                      # 6 Testsuiten (Tenant, License, Module, Unit, Integration, Misc)
├── vitest.config.ts                      # Frontend-Tests (JSDOM)
├── playwright.config.ts                  # E2E-Tests
├── .env.example                          # Umgebungsvariablen
│
├── src/
│   ├── Core/                             # ~19.600 LOC PHP — Framework-Kern
│   │   ├── Plugin.php                    # (895 Z.) Assets, Cron, Uninstall
│   │   ├── Loader.php                    # (226 Z.) Boot: Auth → Dispatcher → Helpers → Module → API
│   │   ├── Installer.php                 # (1.224 Z.) Alle Core-Tabellen + Migrations
│   │   ├── Helpers.php                   # (190 Z.) Globale Hilfsfunktionen
│   │   ├── Assets.php                    # (82 Z.) Asset-Enqueueing
│   │   │
│   │   ├── Adapter/                      # DB-Abstraktion
│   │   │   ├── DatabaseAdapter.php       # Interface (query, insert, transaction)
│   │   │   ├── DatabaseAdapterFactory.php
│   │   │   └── WordPressDatabaseAdapter.php  # $wpdb-Implementierung
│   │   │
│   │   ├── Admin/                        # WP-Admin-Seiten
│   │   │   ├── Menu.php
│   │   │   ├── LogsPage.php
│   │   │   ├── ModuleDiagnostics.php
│   │   │   └── AdminUtils.php
│   │   │
│   │   ├── Api/                          # Core REST-Endpoints
│   │   │   ├── AuthApi.php               # Login, Refresh, Logout, Me, Register
│   │   │   ├── RolesApi.php              # Rollen-CRUD
│   │   │   ├── PartnershipApi.php        # Partnerschaften
│   │   │   ├── HealthApi.php             # Health-Check
│   │   │   └── Response.php              # Standardisierte API-Antworten
│   │   │
│   │   ├── Auth/                         # Authentifizierung & Autorisierung
│   │   │   ├── AuthMiddleware.php        # Multi-Layer: JWT → API-Key → WP-Session
│   │   │   ├── Gate.php                  # Zentrale Berechtigungsprüfung
│   │   │   └── JWTService.php            # JWT Erzeugung/Validierung/Revocation
│   │   │
│   │   ├── Base/                         # Abstrakte Basisklassen
│   │   │   ├── BaseModule.php            # boot(), register(), Asset-Loading
│   │   │   ├── BaseApi.php               # REST-Namespace, Route-Registration
│   │   │   └── BaseAdmin.php             # Admin-Menü-Integration
│   │   │
│   │   ├── Config/
│   │   │   └── EnvLoader.php             # .env-Datei laden
│   │   │
│   │   ├── Container/                    # PSR-11 DI-Container
│   │   │   ├── Container.php             # Singleton/Bind/Instance, Circular-Dep-Detection
│   │   │   └── helpers.php               # resolve()-Helferfunktion
│   │   │
│   │   ├── Contracts/                    # Interfaces
│   │   │   ├── TenantManagerInterface.php
│   │   │   └── CustomerRepositoryInterface.php
│   │   │
│   │   ├── Database/                     # Migrationen
│   │   │   ├── Migrator.php              # Migrations-Runner
│   │   │   ├── Migration002_CreateQueueTable.php
│   │   │   ├── Migration003_TimeTrackingAndShiftManagement.php
│   │   │   └── Migration004_ModuleSlugStudlyCase.php
│   │   │
│   │   ├── Design/                       # Vue 3 Design-System
│   │   │   ├── components/               # ModuleLayout.vue, ModuleStub.vue
│   │   │   ├── composables/              # 7 Vue-Composables (Breakpoints, Sort, Selection…)
│   │   │   ├── designTokens.ts           # ~20K Zeilen Design-Konfiguration
│   │   │   ├── i18n/                     # 7 Sprachen (de, en, es, fr, it + 2)
│   │   │   ├── assets/icons/             # 600+ SVG-Icons
│   │   │   └── assets/scss/              # SCSS → CSS Pipeline
│   │   │
│   │   ├── Dispatcher/                   # Request-Routing (9 Dateien, 1.661 Z.)
│   │   │   ├── RestDispatcher.php        # (1.173 Z.) Zentrale REST-Route-Registration
│   │   │   ├── AjaxDispatcher.php        # AJAX-Endpoints
│   │   │   ├── WebhookDispatcher.php     # Eingehende Webhooks
│   │   │   ├── PublicDispatcher.php      # Frontend-Endpoints
│   │   │   ├── CronDispatcher.php        # Geplante Tasks
│   │   │   ├── AdminDispatcher.php       # Admin-Hooks
│   │   │   ├── RestGuard.php             # (deprecated) Legacy-Berechtigungsprüfung
│   │   │   ├── RestModuleGuard.php       # Modul-spezifische Berechtigungsprüfung
│   │   │   └── RestPermissions.php       # Fine-grained Permission-Callbacks
│   │   │
│   │   ├── Helper/
│   │   │   ├── Manifest.php, Languages.php, Locales.php, Icon.php
│   │   │   └── HelperPathResolver.php
│   │   │
│   │   ├── Integrations/                 # Drittanbieter-Integrationen
│   │   │   ├── Calendar/
│   │   │   │   ├── GoogleCalendarSync.php    # OAuth2, CRUD, FreeBusy
│   │   │   │   ├── AppleCalendarSync.php     # ICS-Feed (read-only)
│   │   │   │   └── MicrosoftCalendarSync.php # Graph API, OAuth2
│   │   │   └── VideoConference/
│   │   │       ├── ZoomIntegration.php
│   │   │       └── GoogleMeetIntegration.php
│   │   │
│   │   ├── Licensing/                    # Lizenzierung & Feature-Flags
│   │   │   ├── LicenseManager.php        # Feature/Modul-Gating
│   │   │   ├── LicenseGuard.php          # Tenant-Lizenz-Validierung + Grace-Period
│   │   │   ├── LicenseIntegration.php
│   │   │   ├── LicenseMiddleware.php     # REST pre-dispatch Lizenz-Check
│   │   │   └── license-features.php      # Feature-Map
│   │   │
│   │   ├── Manager/                      # Modul-Lifecycle
│   │   │   ├── ModuleManager.php         # Laden, Aktivieren, Legacy-Handling
│   │   │   ├── ModuleManifest.php        # manifest.json Parsing
│   │   │   └── ModuleStateRepository.php # Modul-Zustandsspeicher
│   │   │
│   │   ├── Middleware/                    # HTTP-Middleware
│   │   │   ├── SecurityHeadersMiddleware.php  # CSP, HSTS, X-Frame, etc.
│   │   │   └── RateLimitMiddleware.php        # User/IP-basiert, Transient-Cache
│   │   │
│   │   ├── Model/                        # Datenzugriffsschicht
│   │   │   ├── BaseModel.php             # CRUD mit erzwungener Tenant-Isolation
│   │   │   └── Traits/MultiTenantTrait.php  # SQL-Wrapping: WHERE tenant_id = %d
│   │   │
│   │   ├── Partnership/
│   │   │   ├── PartnershipService.php
│   │   │   └── PartnershipRepository.php
│   │   │
│   │   ├── Providers/
│   │   │   └── ServiceProvider.php       # Zentrale DI-Registrierung
│   │   │
│   │   ├── Queue/                        # Async-Job-System
│   │   │   ├── QueueManager.php          # Enqueue, Process, Retry, Dead-Letter
│   │   │   └── Jobs/ExampleJob.php
│   │   │
│   │   ├── Role/
│   │   │   └── CapabilityService.php     # WP-Capabilities + Bookando-Rollen
│   │   │
│   │   ├── Security/
│   │   │   └── BaseCapabilities.php      # Abstrakte Capability-Registrierung
│   │   │
│   │   ├── Service/                      # Core-Services
│   │   │   ├── ActivityLogger.php        # Persistenter Audit-Log
│   │   │   ├── DebugLogger.php           # Debug-Log
│   │   │   ├── UserSyncService.php       # WP↔Bookando User-Sync
│   │   │   └── OAuthTokenStorage.php     # AES-256-GCM verschlüsselte Token-Speicherung
│   │   │
│   │   ├── Settings/
│   │   │   └── FormRules.php             # Validierungsregeln
│   │   │
│   │   ├── Sharing/
│   │   │   └── ShareService.php          # Cross-Tenant ACL + HMAC-signierte Token
│   │   │
│   │   ├── Tenant/                       # Multi-Tenancy
│   │   │   ├── TenantManager.php         # Resolution (Header/Param/Meta/Subdomain/Fallback)
│   │   │   ├── TenantProvisioner.php     # Tenant-Erstellung
│   │   │   ├── TenantInstaller.php       # Per-Tenant DB-Setup
│   │   │   └── ProvisioningApi.php       # Provisioning REST-API
│   │   │
│   │   └── Util/
│   │       ├── Ics.php                   # iCalendar-Format-Generator
│   │       └── Sanitizer.php             # Input-Sanitierung
│   │
│   ├── modules/                          # ~37.000 LOC PHP — 14 Module
│   │   ├── Academy/                      # Kurse, Training, Zertifizierungen
│   │   ├── Appointments/                 # Buchungen & Termine
│   │   ├── Customers/                    # Kundenverwaltung
│   │   ├── Dashboard/                    # Analytics & Übersicht
│   │   ├── DesignFrontend/               # Frontend-Widgets, A/B-Tests, Shortcodes
│   │   ├── DesignSystem/                 # Storybook-Modul
│   │   ├── Employees/                    # Mitarbeiterverwaltung (16 Handler-Dateien)
│   │   ├── Finance/                      # Zahlungen (5 Gateways), Rechnungen
│   │   ├── Offers/                       # Dienstleistungen & Angebote
│   │   ├── Partnerhub/                   # Partner-Management (8 Models, Audit)
│   │   ├── Resources/                    # Ressourcen-Management
│   │   ├── Settings/                     # Plugin-Einstellungen
│   │   ├── Tools/                        # Werkzeuge (Scheduler, Vacation, Notifications)
│   │   └── Workday/                      # Arbeitszeit, Schichten, Dienstplanung
│   │
│   ├── CLI/
│   │   └── SeedDevLicenseCommand.php     # WP-CLI für Dev-Lizenzen
│   │
│   ├── Helper/
│   │   └── Template.php                  # Template-Rendering
│   │
│   └── frontend/
│       ├── apiClient.ts                  # Zentraler API-Client
│       └── types/global.d.ts             # TypeScript-Deklarationen
│
├── packages/                             # Monorepo-Pakete
│   ├── api-client/                       # Shared API-Client (npm-Paket)
│   │   └── src/endpoints/               # appointments, customers, employees
│   ├── design-system/                    # UI-Komponentenbibliothek
│   └── types/                            # Shared TypeScript-Types
│       └── src/                          # models.ts, enums.ts, offers.ts, academy.ts
│
├── database/
│   ├── schemas/wp_bookando_users.sql     # Unified-Users-Schema + Views
│   ├── seeds/test_users.sql              # Test-Daten
│   └── migrations/migrate_to_unified_users.php
│
├── config/
│   ├── modules.php                       # 14 Modul-Registrierungen
│   └── tenants.php                       # Tenant-Konfiguration
│
├── scripts/                              # Build- & Wartungsskripte
│   ├── generate-module.js                # Modul-Scaffolding
│   ├── validate-modules.mjs             # Modul-Validierung
│   ├── doctor.php                        # System-Health-Check
│   ├── migrate-null-tenant-ids.php       # Tenant-Migration
│   └── [12 weitere Skripte]
│
├── tests/                                # 49 Testdateien
│   ├── Tenant/                           # TenantManager-Tests
│   ├── License/                          # Lizenz-Tests
│   ├── Module/                           # ModuleManager-Tests
│   ├── Unit/                             # Unit-Tests
│   └── Integration/                      # REST-Integrationstests
│
└── .storybook/                           # Storybook 8.6 Konfiguration
```

---

## 2. Modul-Inventar

| # | Modul-Slug | Verantwortung | PHP-Einstiegspunkte | DB-Tabellen / Migrationen | REST-Routen | Frontend-Entry | Externe Integrationen |
|---|-----------|---------------|---------------------|--------------------------|-------------|----------------|----------------------|
| 1 | **academy** | Kurse, Trainingskarten, Pakete, Lektionen, Quizze, Meilensteine | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, `CourseModel.php`, `TrainingCardModel.php`, `PackageModel.php`, `StateRepository.php`, `FinanceIntegration.php` | `academy_courses`, `academy_packages`, `academy_topics`, `academy_lessons`, `academy_quizzes`, `academy_training_cards`, `academy_training_milestones`, `academy_training_topics`, `academy_training_lessons` | `/bookando/v1/academy/*` | `assets/vue/main.ts` | Finance-Modul (Rechnungsstellung) |
| 2 | **appointments** | Terminbuchung, Events, Zeitachse, Zuweisungen | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, `Model.php`, `EventModel.php` | `appointments`, `events`, `event_periods`, `event_period_employees`, `event_period_services`, `event_period_locations`, `event_period_resources` | `/bookando/v1/appointments/*` (timeline, CRUD, assign, lookups) | `assets/vue/main.ts` | Calendar-Sync (Google, MS, Apple) |
| 3 | **customers** | Kundenverwaltung, Validierung, CRUD | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, `CustomerRepository.php`, `CustomerValidator.php`, `CustomerService.php` | `users` (via unified table, role=customer) | `/bookando/v1/customers/*` | `assets/vue/main.ts` | Cloud-Webhooks |
| 4 | **dashboard** | Übersicht, Analytics, KPIs | `Module.php`, `Api.php`, `Admin.php` | — (liest aus allen Tabellen) | `/bookando/v1/dashboard/*` | `assets/vue/main.ts` | — |
| 5 | **designfrontend** | Shortcodes, A/B-Tests, OAuth-Links, Landing Pages, Frontend-Auth | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, `AuthHandler.php`, `ShortcodeManager.php` | `frontend_oauth_links`, `frontend_pages`, `frontend_shortcode_templates`, `frontend_auth_sessions`, `frontend_auth_providers`, `frontend_offer_displays`, `frontend_ab_tests`, `frontend_shortcode_analytics`, `frontend_generated_links` | `/bookando/v1/designfrontend/*` | `assets/vue/main.ts` | Google/Apple OAuth |
| 6 | **designsystem** | Storybook, Komponentenbibliothek | `Module.php`, `Api.php`, `Admin.php` | — | `/bookando/v1/designsystem/*` | Storybook | — |
| 7 | **employees** | Mitarbeiterverwaltung, Arbeitstage, Kalender, Abwesenheiten, Bulk-Ops | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, `Model.php`, `Capabilities.php`, 16 Handler (Repository, Validator, CommandHandler, QueryHandler, DaysOffManager, WorkdaySetManager, SpecialDaySetManager, CalendarManager, BulkEmployeeHandler, EmployeeAuthorizationGuard, EmployeeInputValidator…) | `users` (role=employee), `employees_days_off` (erweitert) | `/bookando/v1/employees/*` (inkl. workday-sets, calendars, days-off, special-days) | `assets/vue/main.ts` | Calendar-Sync |
| 8 | **finance** | Zahlungsabwicklung (5 Gateways), Rechnungen, Gutschriften, Rabattcodes, Ledger-Export | `Module.php`, `Api.php`, `RestHandler.php`, `PaymentRestHandler.php`, `PaymentWebhookHandler.php`, `StateRepository.php`, `Installer.php` + 6 Gateway-Klassen | `payments`, `invoices` (via StateRepository) | `/bookando/v1/finance/*` (gateways, payment CRUD, invoices, credit notes, discount codes, export) + Webhook-Endpoints | `assets/vue/main.ts` | **Stripe**, **PayPal**, **Mollie**, **Klarna**, **TWINT** |
| 9 | **offers** | Dienstleistungen, Pakete, Kalenderansichten, Preisgestaltung | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, `Model.php`, `CalendarViewController.php`, `AcademyEnrollmentHandler.php` | `offers` | `/bookando/v1/offers/*` | `assets/vue/main.ts` | Cloud-Webhooks, Academy-Bridge |
| 10 | **partnerhub** | Partner-Management, Consent, Feeds, Audit-Logs, Transaktionen | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, 8 Models, `ConsentService.php`, `FeedService.php` | `partners`, `partner_mappings`, `partner_rules`, `partner_consents`, `partner_data_shares`, `partner_audit_logs`, `partner_feeds`, `partner_transactions` | `/bookando/v1/partnerhub/*` | `assets/vue/main.ts` | — |
| 11 | **resources** | Raum-/Equipment-Verwaltung | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, `ResourcesRepository.php`, `ResourcesService.php`, `StateRepository.php` | `resources` | `/bookando/v1/resources/*` | `assets/vue/main.ts` | — |
| 12 | **settings** | Plugin-Einstellungen (Booking, Company, Working Hours, Notifications, Payments, Integrations, Events) | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php` | `settings`, `booking_settings`, `working_hours_settings`, `company_settings`, `notifications_settings`, `payments_settings`, `integrations_settings`, `event_settings` | `/bookando/v1/settings/*` | `assets/vue/main.ts` | Cloud-Webhooks, OAuth |
| 13 | **tools** | Dienstplannung, Urlaubsverwaltung, Zeiterfassung, Kursplanung, Benachrichtigungen, Barrierefreiheit | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, 9 Services (SchedulerService, VacationService, TimeTrackingService, CoursePlanningService, NotificationService, AccessibilityService…) | Nutzt Workday-Tabellen | `/bookando/v1/tools/*` | `assets/vue/main.ts` | — |
| 14 | **workday** | Dienstpläne, Schichten, Zeiterfassung, Pausen, Überstunden, Urlaub | `Module.php`, `Api.php`, `RestHandler.php`, `Installer.php`, `Capabilities.php`, 6 Services (DutySchedulerService, VacationRequestService, ShiftService, TimeTrackingService, BreakService, ShiftTemplateService) | `shifts`, `shift_templates`, `shift_requirements`, `employee_shift_preferences`, `time_entry_breaks`, `employee_vacation_balances`, `overtime_balances`, `active_timers` | `/bookando/v1/workday/*` | `assets/vue/main.ts` | — |

---

## 3. Platform-Kernel-Kandidaten in `src/Core/`

| Bereich | Dateien / Klassen | Kernel-Tauglichkeit | WP-Abhängigkeiten | Anmerkungen |
|---------|-------------------|--------------------|--------------------|-------------|
| **Tenant-Management** | `Tenant/TenantManager.php`, `TenantProvisioner.php`, `TenantInstaller.php`, `ProvisioningApi.php`, `Model/Traits/MultiTenantTrait.php` | **HOCH** — Zentral für Isolation | `wp_get_current_user()`, `get_user_meta()`, `get_option()`, `apply_filters()`, `$_SERVER` | Muss in Adapter extrahiert werden; Kern-Logik (Resolution-Kette, ACL) ist WP-agnostisch machbar |
| **Auth (Authn)** | `Auth/AuthMiddleware.php`, `Auth/JWTService.php` | **HOCH** — JWT-Kern ist host-agnostisch | JWT: nutzt `AUTH_KEY`/`SECURE_AUTH_KEY` als Secret. API-Key: `$wpdb` direkt. Session: `wp_get_current_user()` | JWT-Service fast rein; AuthMiddleware braucht Adapter für Request/User-Resolution |
| **Auth (Authz)** | `Auth/Gate.php`, `Dispatcher/RestModuleGuard.php`, `Dispatcher/RestPermissions.php`, `Security/BaseCapabilities.php`, `Role/CapabilityService.php` | **HOCH** — RBAC-Logik extrahierbar | `current_user_can()`, `wp_verify_nonce()`, WP-Capabilities | Nonce ist WP-spezifisch (→ CSRF-Adapter). RBAC-Kern (Rollen, Capabilities, Evaluate) kann WP-frei sein |
| **Lizenzierung / Feature-Flags** | `Licensing/LicenseManager.php`, `LicenseGuard.php`, `LicenseMiddleware.php`, `license-features.php` | **HOCH** — Bereits gut abstrahiert | `get_transient()` für Cache, `get_option()` | Leichte Adapter-Arbeit (Cache/Settings → Port) |
| **Settings / Config** | `Config/EnvLoader.php`, `Settings/FormRules.php`, diverse Settings-Tabellen | **MITTEL** — Validierung ist rein; Speicherung WP-gebunden | `get_option()`, `update_option()` | EnvLoader ist bereits WP-frei. Settings brauchen einen KeyValue-Port |
| **Queue / Jobs** | `Queue/QueueManager.php`, `Jobs/ExampleJob.php` | **HOCH** — Eigenständige Queue-Engine | `$wpdb` für Job-Tabelle, WP-Cron für Trigger | Queue-Kern (Enqueue, Process, Retry, Dead-Letter) ist DB-agnostisch machbar; Trigger = Adapter |
| **Integrations (Calendar)** | `Integrations/Calendar/GoogleCalendarSync.php`, `AppleCalendarSync.php`, `MicrosoftCalendarSync.php` | **HOCH** — HTTP-basiert, wenig WP | `wp_remote_get/post()` für HTTP, `get_transient()`/`set_transient()` für Token-Cache | HTTP-Client und Cache → Ports |
| **Integrations (Video)** | `Integrations/VideoConference/ZoomIntegration.php`, `GoogleMeetIntegration.php` | **HOCH** | Gleiche HTTP/Cache-Pattern | — |
| **DB / Model Layer** | `Model/BaseModel.php`, `Traits/MultiTenantTrait.php`, `Adapter/DatabaseAdapter.php`, `WordPressDatabaseAdapter.php`, `DatabaseAdapterFactory.php` | **HOCH** — Adapter-Pattern bereits vorhanden | `$wpdb` global, `wpdb->prepare/insert/update/delete` | DatabaseAdapter-Interface existiert bereits! WordPressDatabaseAdapter ist der einzige Implementierer |
| **Scheduling / Time** | Appointments/EventModel, Workday/ShiftService, DutySchedulerService | **MITTEL** — Logik in Modulen, nicht in Core | `DateTimeImmutable` (rein), `wp_timezone()` | Zeitlogik ist PHP-nativ. Nur `wp_timezone()` und `current_time()` sind WP-spezifisch |
| **Payments / Accounting** | Finance/Gateways/*, PaymentWebhookHandler, StateRepository | **HOCH** — Gateway-Interface existiert | `$wpdb`, `wp_remote_post()` | GatewayInterface ist bereits abstrakt. HTTP + DB → Ports |
| **Logging / Observability** | `Service/ActivityLogger.php`, `Service/DebugLogger.php` | **HOCH** — Logger-Schnittstelle einfach | `$wpdb` für persistenten Log, `error_log()` als Fallback | Logger → Port; ActivityLogger-Kern ist tabellenbasiert |
| **DI Container** | `Container/Container.php` | **SEHR HOCH** — PSR-11, kein WP | Keine | Bereits vollständig host-agnostisch |
| **Sharing / Cross-Tenant** | `Sharing/ShareService.php` | **HOCH** — HMAC + ACL | `$wpdb`, WP-Salt-Konstanten für HMAC | Crypto-Secret → Port; ACL-Tabelle → DB-Port |
| **Encryption** | `Service/OAuthTokenStorage.php` | **HOCH** — AES-256-GCM | `openssl_encrypt()` (PHP-nativ), Schlüssel aus WP-Salt | Key-Derivation → Port |

---

## 4. Datenbank-Tabellen (Gesamtübersicht)

### Core-Tabellen (aus `Installer.php`)

| Tabelle | Zweck | Tenant-isoliert |
|---------|-------|----------------|
| `wp_bookando_tenants` | Mandantenstammdaten, Lizenz, Status | Nein (Meta-Tabelle) |
| `wp_bookando_roles` | Rollendefinitionen | Ja |
| `wp_bookando_users` | Unified Users (Kunden, Mitarbeiter, Admins) | Ja |
| `wp_bookando_user_roles` | User-Rollen-Zuordnung | Ja |
| `wp_bookando_events` | Events (Kurse, Lektionen, Meetings) | Ja |
| `wp_bookando_event_periods` | Zeiträume/Perioden pro Event (UTC) | Ja |
| `wp_bookando_event_period_employees` | Event↔Mitarbeiter-Zuordnung | Ja |
| `wp_bookando_event_period_services` | Event↔Service-Zuordnung | Ja |
| `wp_bookando_event_period_locations` | Event↔Standort-Zuordnung | Ja |
| `wp_bookando_event_period_resources` | Event↔Ressourcen-Zuordnung | Ja |
| `wp_bookando_appointments` | Buchungen/Termine (UTC) | Ja |
| `wp_bookando_locations` | Standorte | Ja |
| `wp_bookando_resources` | Räume/Equipment | Ja |
| `wp_bookando_payments` | Zahlungsdatensätze | Ja |
| `wp_bookando_notifications` | Benachrichtigungsvorlagen | Ja |
| `wp_bookando_notification_log` | Benachrichtigungshistorie | Ja |
| `wp_bookando_custom_fields` | Custom-Field-Definitionen | Ja |
| `wp_bookando_custom_field_options` | Field-Optionen | Ja |
| `wp_bookando_custom_field_map` | Field↔Entity-Mappings | Ja |
| `wp_bookando_settings` | Allgemeine Einstellungen | Ja |
| `wp_bookando_booking_settings` | Buchungskonfiguration | Ja |
| `wp_bookando_working_hours_settings` | Arbeitszeitvorlagen | Ja |
| `wp_bookando_company_settings` | Firmendaten/Branding | Ja |
| `wp_bookando_notifications_settings` | E-Mail/SMS-Konfiguration | Ja |
| `wp_bookando_payments_settings` | Zahlungskonfiguration | Ja |
| `wp_bookando_integrations_settings` | Integrations-Konfiguration | Ja |
| `wp_bookando_event_settings` | Event-Buchungsregeln | Ja |
| `wp_bookando_queue_jobs` | Async-Job-Queue | Ja |
| `wp_bookando_activity_log` | Audit-Trail | Ja |
| `wp_bookando_share_acl` | Cross-Tenant-Zugriffssteuerung | — (Tenant-übergreifend) |
| `wp_bookando_api_keys` | API-Schlüssel | Ja |
| `wp_bookando_calendar_connections` | Verschlüsselte OAuth-Tokens | Ja |
| `wp_bookando_notification_matrices` | Benachrichtigungs-Routing | Ja |
| `wp_bookando_active_timers` | Aktive Zeiterfassungssitzungen | Ja |

### Modul-spezifische Tabellen

| Modul | Tabellen |
|-------|---------|
| Academy | `academy_courses`, `academy_packages`, `academy_topics`, `academy_lessons`, `academy_quizzes`, `academy_training_cards`, `academy_training_milestones`, `academy_training_topics`, `academy_training_lessons` |
| Offers | `offers` |
| DesignFrontend | `frontend_oauth_links`, `frontend_pages`, `frontend_shortcode_templates`, `frontend_auth_sessions`, `frontend_auth_providers`, `frontend_offer_displays`, `frontend_ab_tests`, `frontend_shortcode_analytics`, `frontend_generated_links` |
| Partnerhub | `partners`, `partner_mappings`, `partner_rules`, `partner_consents`, `partner_data_shares`, `partner_audit_logs`, `partner_feeds`, `partner_transactions` |
| Workday (Migration003) | `time_entry_breaks`, `employee_vacation_balances`, `shifts`, `shift_templates`, `employee_shift_preferences`, `shift_requirements`, `overtime_balances` |

---

## 5. Metriken

| Metrik | Wert |
|--------|------|
| PHP-LOC (Core) | ~19.600 |
| PHP-LOC (Module) | ~37.000 |
| PHP-LOC (Gesamt) | ~57.000 |
| TypeScript-Dateien | 153 |
| Vue-Komponenten | 149 |
| Aktive Module | 14 |
| DB-Tabellen | ~55 |
| REST-API-Routen | ~100+ |
| Payment-Gateways | 5 (Stripe, PayPal, Mollie, Klarna, TWINT) |
| Kalender-Integrationen | 3 (Google, Apple/iCal, Microsoft) |
| Sprachen (i18n) | 7 |
| Test-Dateien | 49 |
| Design-System-Icons | 600+ |
