# 📦 Bookando – Plugin- & SaaS-Struktur  (Technische Dokumentation v 2.4 · **Extended**)

> **Änderungsstand:** 2025‑11‑04  
> **Status:** Vollständige Langfassung (auf Basis v 2.2 + v 2.4) mit konsolidierter Architektur, Guards, Multi‑Tenant‑Pflichten, Design‑Leitplanken, Build‑/CLI‑Beispielen, Test‑Snippets und Checklisten.  
> **Hinweis:** Diese Fassung ist ein *Superset* der bisherigen Dokumente und kann 1:1 die alte Datei ersetzen.

---

## 🔧 Zielsetzung

Bookando ist ein **zukunftssicheres, modular erweiterbares** WordPress‑ & SaaS‑Framework für **Events, Kurse, Buchungen, Ressourcen‑ & Kundenverwaltung, Zahlungen, Lerninhalte und Kommunikation**.  
Einsatzgebiete: Salons, Studios, Agenturen, Coaches – **sowie Fahrschulen** dank des *Education‑Packs* (OrphyDrive‑Features).

**Kernprinzipien**

- **Modularität & Lizenz‑Flagging** – Funktionen werden per Modul aktiviert, SaaS‑Plan bestimmt Zugriffsrechte.  
- **Vue 3 + Vite + TypeScript SPA** im Admin und in zentralen Frontend‑Portalen (nicht pro Modul).  
- **REST / GraphQL API** + Webhook‑Dispatcher.  
- **Offline‑fähig** (PWA, IndexedDB‑Sync) für Fahrlehrer‑ & Schüler‑Apps.  
- **Mandantenfähig** – Betrieb als klassisches Plugin *oder* Multi‑Tenant‑SaaS.  
- **DSGVO‑konform**, Mehrsprachigkeit (DE/EN/FR/IT), White‑Label‑Option.

---

## 🧱 Architekturübersicht

```text
bookando/
├── bookando.php                # Haupt-Plugin-Datei, Entry-Point für WP (lädt Core\Plugin)
├── composer.json               # Composer-Konfig für PHP-Abhängigkeiten & Autoloading (PSR-4)
├── package.json                # JS/TS-Abhängigkeiten (Vite, Vue, etc.)
├── package-lock.json           # Genaue Lock-Datei für JS-Dependencies
├── .gitignore                  # Git-Ausnahmen (node_modules, dist, etc.)
├── readme.txt                  # WordPress-kompatible Pluginbeschreibung (für WP-Repo)
├── docs/
│   ├── Bookando-Plugin-Struktur.md # Technische Architektur, Doku, verbindlich!
│   ├── Licensing.md                # Lizenzmodell, API, SaaS-Handling
│   └── Guidelines.md               # Coding-Standards für Vue, CSS, Naming
├── languages/
│   └── bookando.pot                # Übersetzungs-Template (i18n, gettext)
├── scripts/
│   ├── generate-module.js          # CLI: Erstellt neue Modulstruktur (Scaffolding)
│   ├── cleanup.js                  # Build-Utility, räumt veraltete Artefakte auf
│   ├── check-license.js            # CLI/Build-Check für Lizenzstatus
│   ├── doctor.php                  # CLI: Diagnose-/Systemcheck
│   ├── export-license-map.php      # Exportiert Lizenz-/Featuremapping (z. B. für SaaS)
│   ├── query-license.php           # CLI: Fragt Lizenz-Status ab
│   ├── vite.config.ts              # Standard-Build-Konfiguration (Core & alle Module, empfohlen)
│   ├── vite.config.core.ts         # (optional) Nur für getrennte Core-Builds (z. B. White-Label)
│   ├── vite.config.module.ts       # (optional) Nur für getrennte Modul-Builds (Spezialfälle)
│   ├── vitest.config.ts            # Testing-Konfiguration für JS/TS    
│   ├── .eslintrc.json              # Linter-Konfiguration für JS/TS-Codequalität
│   └── … (weitere Build/Dev-Utilities nach Bedarf)
├── vendor/                        # Composer-Autoload, PHP-Abhängigkeiten
├── dist/                          # Build-Output von Vite (immer git-ignored)
│   ├── core/                      # Core-Assets (CSS/JS)
│   ├── frontend-booking/          # SPA-Bundle Buchungsformular-Portal (Shortcode)
│   ├── customer-portal/           # SPA-Bundle Kundenportal (Shortcode)
│   ├── employee-portal/           # SPA-Bundle Mitarbeiterportal (Shortcode)
│   └── modules/<slug>/            # Build-Output pro Backend-Modul (JS/CSS Bundles für Admin)
└── src/
    ├── assets/
    │   └── http/
    │       ├── client.ts          # Axios-Instanz + Interceptors
    │       └── index.ts           # kleine Wrapper (get/post/…)
    ├── Core/                      # Zentrale Plugin-Logik, immer groß geschrieben
    │   ├── Plugin.php             # Einstiegspunkt des Plugins (init, Hooks, Loader; lädt Core/Helpers.php case-sensitiv)
    │   ├── Loader.php             # Lädt Module, Dispatcher, Rollen
    │   ├── Installer.php          # Setup/Upgrade-Logik für das Plugin (DB, Defaults)
    │   ├── Dispatcher/            # Zentrale Request-Handler (REST, AJAX, Webhook)
    │   │   ├── AjaxDispatcher.php     # AJAX-Handler (immer mit Nonce + Capabilities)
    │   │   ├── RestDispatcher.php     # REST-API-Handler (Permission Callback!)
    │   │   └── WebhookDispatcher.php  # Webhook-Endpoint, Token+Signatur geprüft
    │   ├── Services/
    │   │   └── UserSyncService.php    # Zentrale Service/Bootstrap-Klasse
    │   ├── Licensing/
    │   │   ├── LicenseManager.php     # Prüft Lizenz, Module/Feature-Flags
    │   │   └── license-features.php   # Zentrales Mapping Plan <-> Module/Features
    │   ├── Manager/
    │   │   ├── ModuleManager.php      # Lädt/aktiviert Module nach Lizenz & Slug
    │   │   └── ModuleManifest.php     # Kapselt/parst module.json (Meta, Flags)
    │   ├── Admin/
    │   │   ├── Menu.php               # Registriert/steuert Admin-Menüs (WP-Backend)
    │   │   └── Settings.php           # Zentrale Settings (Optionen, global)
    │   ├── Composables/
    │   │   ├── useModuleActions.ts    # CRUD/Bulk/Quick + API/License-Integration
    │   │   ├── useResponsive.ts       # Breakpoint-Erkennung (isMobile/isTablet)
    │   │   └── useTable.ts            # Tabellen-Logik (Spalten, Sortierung, Filter, Pagination)  
    │   ├── Design/
    │   │   ├── Templates/             # PHP-Templates für UI, Fallback/Server-Rendering
    │   │   ├── i18n/                  # Zentrale i18n-Konfiguration  
    │   │   │   ├── index.ts           # Indexierung der Sprachdateien 
    │   │   │   ├── de.json            # Sprachdatei Deutsch
    │   │   │   └── en.json            # Sprachdatei Englisch  
    │   │   ├── Locale/
    │   │   │   ├── index.ts           # dayjs-Locale & Formats (setLocale/getLang/…)
    │   │   │   └── bridge.ts          # applyGlobalLocale/initLocaleBridge (Event-Bridge)         
    │   │   ├── helpers/
    │   │   │   └── resolveIcon.ts     # Liefert Pfad zum Icon innerhalb des Plugins
    │   │   ├── components/            # Zentrale Design-Vues
    │   │   │   ├── AppAccordion.vue, AppButton.vue, AppCheckbox.vue, AppColorInput.vue, AppDateInput.vue, AppDateRangeInput.vue, AppDropdown.vue, AppFileInput.vue, AppForm(.vue/.Group), AppLicenseOverlay.vue, AppText.vue, AppModal.vue, AppMultiselect.vue, AppPhoneInput.vue, AppRadioGroup.vue, AppRangeInput.vue, AppSelect.vue, AppTabs.vue, AppTextarea.vue, AppTimeInput.vue, BookandoField.vue
    │   │   └── assets/                # SCSS, Icons, Images, JS
    │   │       ├── index.ts           # Zentraler Asset-Export   
    │   │       ├── scss/              # Zentrales SCSS-Designsystem (alle UI-Komponenten)
    │   │       ├── css/admin-ui.css   # Zentrales CSS-Designsystem
    │   │       ├── icons/             # SVG/Font-Icons
    │   │       ├── images/            # Backend-Grafiken
    │   │       ├── js/                # (optional) Bridge jQuery/3rd-Party (interop/api/http.js)
    │   │       └── vendor/            # Vendor-Libs (z. B. bootstrap, flags, intlTel)
    │   ├── Tenant/
    │   │   └── TenantManager.php     
    │   ├── Api/
    │   │   ├── apiClient.ts          # fetchJson/apiGet/apiPost/Errorhandling
    │   │   ├── Response.php          # Helper für WP_REST_Response                              
    │   │   └── RolesApi.php
    │   ├── Helper/
    │   │   ├── Icons.php             # Icons-Logik/Mapping
    │   │   └── Locales.php           # Sprach- & Länder-Utilities
    │   ├── Roles/
    │   │   └── CapabilityService.php # Zentrale Rollen-/Rechtestruktur
    │   ├── Base/
    │   │   ├── BaseModel.php         # Abstrakte Basisklassen für DB-Modelle (mit Tenant-Guard)
    │   │   ├── BaseAdmin.php         # Abstrakte Basisklassen für Admin-Panels
    │   │   └── … weitere abstrakte Klassen/Traits
    │   └── Holes/                    # (optional, Erweiterungspunkte/"Plug-in Points")
    └── modules/                      # Alle Module, klein geschrieben!
        └── <slug>/                   # z. B. "customers"
            ├── Module.php                  # bindet Admin, Api, Capabilities; enqueued Module-Assets
            ├── module.json                 # Manifest (Plan, Features, Flags)
            ├── Capabilities.php            # registriert manage_bookando_<slug> u. ä.
            ├── RestHandler.php             # statische Methoden für RestDispatcher ({type})
            ├── Admin/
            │   └── Admin.php               # Menü + Render (lädt Template)
            ├── Api/
            │   └── Api.php                 # register(): RestDispatcher::registerModule('<slug>', RestHandler::class); registerRoutes(): modul-spezifische REST-Routen/Guards
            ├── Templates/
            │   └── admin-vue-container.php # EIN Mountpoint für die SPA
            ├── assets/
            │   ├── vue/
            │   │   ├── main.ts
            │   │   ├── api/api.ts
            │   │   ├── models/<PascalSlug>Model.ts
            │   │   ├── store/store.ts      # Pinia (optional)
            │   │   └── views/<PascalSlug>View.vue
            │   └── css/
            │       └── admin.css                        
            ├── Installer.php               # optional: DB-Migrationen (Core ruft auf, wenn vorhanden)
            ├── Model.php                   # empfohlen: Domain-/DB-Logik
            └── Tests/                      # optional                                
```

**Ergänzungen (neu, verbindlich):**

- **Designsystem:** SCSS‑Tokens in `_tokens.scss`, abgeleitete Maps in `_variables.scss`, Utilities in `_utilities.scss`. **Neue Varianten** stets über Maps pflegen (keine Hardcodes in Komponenten).
- **Permission‑Helper (DRY):** zentral nutzbar, siehe Dispatcher‑Kapitel: `bookando_allow($slug, $feature = null)`.
- **Multi‑Tenant Fail‑Safe:** `BaseModel` injiziert *immer* `WHERE tenant_id = ?`; Mandant wird **nie** aus Query‑Parametern abgeleitet, sondern ausschließlich aus Gate/JWT/Session.

---

## 🏗️ Frontend‑Architektur & Portale

- **Frontend‑Portale (z. B. Buchungsformular, Kundenportal, Mitarbeiterportal) sind zentrale, eigenständige SPAs.**
- Einbindung ins Frontend **ausschließlich über Shortcodes**:
  - `[bookando_booking_form]`
  - `[bookando_customer_portal]`
  - `[bookando_employee_portal]`
- Jedes Portal lädt gezielt sein eigenes SPA‑Bundle (`dist/frontend-booking/`, `dist/customer-portal/`, `dist/employee-portal/`).  
- **Design zentral anpassbar** (Farben, Abstände, Branding) via Design‑Modul; Variablen als CSS und JS an den SPA‑Mount übergeben.  
- Kommunikation nur über REST‑API; **keine Modul‑Frontend‑Assets** laden (keine Redundanz).  
- White‑Label‑ und mandantenfähig (Branding, Sprache, etc.).

---

## 🌍 Globale Helper‑Funktionen

- Globale Utilitys in `/src/Core/Helpers.php` als `bookando_*`‑Funktionen (ohne Namespace).
- Wrappen Services wie `Bookando\Core\Helper\Icon`, `Languages`, `Locales`.
- **Ziel:** maximale WP‑Kompatibilität (Themes, Child‑Themes, Multisite).

**Beispiel:**

```php
bookando_get_template('module', 'template');
echo bookando_icon('user', 'icon-large');
```

---

## 📊 Aktivitäts-Logging & Auditing

- **Service:** `Bookando\Core\Service\ActivityLogger` schreibt Ereignisse in die Tabelle `wp_bookando_activity_log` (Fallback `error_log`).
  - Neue Methode `ActivityLogger::recent(int $limit = 50, array $filters = [])` liefert mandantenbewusst die letzten Einträge.
  - Unterstützte Filterkeys: `tenant_id`, `include_global` (bool), `severity` (`info|warning|error`, auch Array), `context`, `module_slug`, `message`, `since`, `until`.
  - Mandanten werden standardmäßig über `TenantManager::currentTenantId()` eingegrenzt; globale Einträge (`tenant_id = NULL`) werden automatisch mitgeladen.
- **Admin-UI:** `Bookando\Core\Admin\LogsPage` registriert sich über `Menu::addModuleSubmenu()` an `bookando_register_module_menus` und stellt im Backend (nur `manage_options`) die Seite **Bookando → Aktivitätslog** bereit.
  - Filterleiste: Kontext, Modul-Slug, Message-Search, Severity-Checkboxen, Datumsbereich (`date_from`/`date_to`), Limit (25–500) sowie Mandanten-Scope (aktueller Mandant, alle, spezifische Tenant-ID).
  - Tabellenansicht mit Zeitstempel, Severity, Mandant, Kontext, Modul, Message und formatiertem Payload (JSON Pretty Print).
  - CSV-Export (`Als CSV exportieren`) liefert die aktuell gefilterte Sicht; Headers sind `id, logged_at, severity, tenant_id, context, module_slug, message, payload`.
- **Integrationen:**
  - WP-CLI: eigene Commands können `ActivityLogger::log('cli.sync', 'Aktion', [...], ActivityLogger::LEVEL_INFO, $tenantId)` nutzen; `TenantManager::setCurrentTenantId()` erlaubt Pre-Scoped-Logging.
  - Webhooks / externe Worker: via Header `X-BOOKANDO-TENANT` und Filter `bookando_tenant_allow_header_switch` integrierbar; Payloads sollten JSON-serialisierbar sein (max. 65kB je Eintrag).
  - Cronjobs, REST-/Webhook-Dispatcher und Module nutzen denselben Service, wodurch Audits zentral nach Mandant gefiltert werden können.

---

## 🔀 Modulgruppen

| Gruppe      | Zweck                                                   |
|-------------|---------------------------------------------------------|
| `core`      | Dashboard, Kalender, Buchungen                          |
| `offers`    | Dienstleistungen, Kurse, Events, Onlinekurse            |
| `crm`       | Kunden, Kommunikation, Benachrichtigungen, Custom‑Fields|
| `resources` | Mitarbeitende, Orte, Fahrzeuge, Räume                   |
| `finance`   | Rechnungen, Zahlungen, Gutscheine, Abos                 |
| `education` | Ausbildungskarte, Fortschritt, Lernmaterial, Tests      |
| `integration` | API‑Keys, Kalender‑Sync, Tracking, Social‑Login      |
| `system`    | Einstellungen, Design, Exporte, Zeit‑Tracking           |
| `ux`        | Frontend‑Widgets, Formular‑UX                           |

> Lizenzpläne steuern den Zugriff (z. B. Plan *education* schaltet alle `education`‑Module frei).

---

## 🗂️ Modul‑Feature‑Mapping & Lizenzierung

Bookando trennt **strikt** zwischen:

- **Feature‑Definition**: pro Modul in `module.json` (`features`, `features_required`, `dependencies`).  
- **Lizenz‑Zuordnung**: zentral in `src/Core/Licensing/license-features.php` (Pläne → Module/Features).

**Beispiel `module.json`:**

```json
{
  "slug": "gutscheine",
  "name": { "default": "Gutscheine", "en": "Vouchers" },
  "description": { "default": "Verwaltung von Gutscheinen" },
  "features": ["import_export", "batch_create"],
  "dependencies": [],
  "features_required": [],
  "tabs": ["Allgemein", "Import/Export"],
  "version": "1.0.0",
  "doc": "Siehe docs/gutscheine.md"
}
```

**Actions-Registry (optional):**

- `actions.allowed` – welche Bulk-/Quick-Aktionen der Admin zur Auswahl bekommt.
- `actions.endpoint` – überschreibt den Standard-Endpunkt (`/wp-json/bookando/v1/<slug>/bulk`). Platzhalter `{slug}` oder `:slug` werden ersetzt.
- `actions.features` – Mapping `action -> Feature(s)`. Ohne aktiviertes Feature blockt `useModuleActions` den Request (Frontend-Gate, Meldung „Funktion nicht lizenziert“).
- PHP injiziert die Daten als `BOOKANDO_VARS.module_actions`; gleichzeitig landet die aktuelle Lizenzliste (`BOOKANDO_VARS.license_features`) in der Bridge.
- Tests sollten sowohl erlaubte Aktionen als auch Gates pro Modul abdecken (`src/Core/Composables/__tests__/useModuleActions.spec.ts`).

### 📌 Modulversionierung & Dokumentation

Module enthalten `version` und `doc` für Upgrades und Wartbarkeit.

---

## 🧩 Mandantenfähigkeit, Usermodell & Duplikat‑Handling (ab 2025)

### Mandantenfähigkeit & SaaS

- **Jeder Eintrag** ist einer **Mandant:in (`tenant_id`/`company_id`)** zugeordnet.  
- **Datenisolation:** Nur eigene Daten sichtbar.  
- **Suborganisationen:** optional via `parent_tenant_id` für Franchise/Verbund.  
- **REST & Backend‑Views** filtern strikt nach `tenant_id`.

### Usermodell

**Eigene User‑Tabelle (`{prefix}bookando_users`)** für Kunden/Mitarbeitende/Admins.

- Wichtige Felder: `tenant_id`, `email` (unique pro Tenant), `roles` (JSON, z. B. `["employee","customer"]`).  
- Rollen serverseitig vergeben (Registrierung, Import, Buchung, Anlage).  
- `(tenant_id, email)` ist unique; keine redundanten Typfelder.  
- Optionaler Link zu WP‑User via `wp_user_id` möglich.

### Passwort‑Strategie

- Login/Reset/Berechtigungen in Bookando verwaltet; **keine** automatische WP‑User‑Anlage (opt. Verknüpfung möglich).  
- Passwörter mit `password_hash` gespeichert.  
- SaaS/Cloud‑Portale: Steuerung via `tenant_id` + Rollen.

### Import / Duplikat‑Handling

- Merge‑Logik bei vorhandenem `(tenant_id, email)` (Feld‑Vergleich, wählbare Übernahme).

### Security, Rechte & Zugriff

- **Jede** REST‑API prüft `tenant_id` & Capabilities; Daten nur für aktive Organisation.  
- Multi‑Tenant‑Sicherheit hat oberste Priorität (keine Leaks).

### 🌐 SaaS‑Ready: Multi‑Tenant

- Ab Plan **Academy** (`multi_tenant`‑Feature).  
- Module unterstützen `tenant_id`; UI filtert entsprechend.

### 📖 Naming Guidelines

- Englisch, klein, Unterstriche (`calendar_sync`).  
- Module: **Plural** (`customers`), Features: **Singular** (`feedback`).

---

## 🛠 `generate-module.js`

```bash
node scripts/generate-module.js
```

Generiert ein voll­ständiges, **SPA‑fähiges** Modulgerüst (Vue 3, Vite, REST), inkl.:

- `module.json`  
- Vue‑Komponenten (Table, Form, Tabs, Lizenz‑Overlay)  
- Demo‑REST‑API (CRUD, Dummy‑Daten)  
- Store (Pinia, optional)  
- Admin‑Menü inkl. Icon/Position  
- Zentrale Styles, Tests (Vitest), Playwright‑Test, Docs, Changelog  
- **Fallback‑Templates** via `generate-fallbacks.js`  
- **Keine** Modul‑Frontend‑Assets; Frontend nur über zentrale Portale.

### Prompts (Ausschnitt)

| Prompt | Beispiel |
|---|---|
| `slug` | `events` |
| `group` | `offers` |
| `plan` | `starter` / `pro` / `academy` |
| `tenant_required` | `true` / `false` |
| `name_default` | `Events` |
| `license_required` | `true` |
| `features_required` | `[calendar_sync, feedback, pdf_export]` |
| `menu_icon` | `dashicons-calendar-alt` |
| `menu_position` | `30` |

> ℹ️  Alle Manifestfelder werden gegen [`docs/module-schema.json`](./module-schema.json) geprüft. `npm run validate:modules`
>  validiert sämtliche `module.json` Dateien und läuft automatisch im `npm test`-Workflow.

### Generiert werden

- Admin‑Mount (`Templates/admin-vue-container.php`, `Admin/Admin.php`)  
- REST (`Api/Api.php`, `RestHandler.php`) mit Guard‑Beispiel  
- State (`assets/vue/store/store.ts`)  
- API‑Konstanten (`assets/vue/api/api.ts`)  
- Model‑Interface (`assets/vue/models/<PascalSlug>Model.ts`)  
- Styles (`assets/css/admin.scss`)
- Docs/Tests (optional)  
- i18n (optional)  

---

## 🔌 REST‑Basis: `/wp-json/bookando/v1/<slug>/`

**Standard‑CRUD** (RestDispatcher → RestHandler):

```
GET    /<slug>/<slug>           → Liste   (mit { data, meta })
GET    /<slug>/<slug>/{id}      → Detail
POST   /<slug>/<slug>           → Create
PUT    /<slug>/<slug>/{id}      → Update
PATCH  /<slug>/<slug>/{id}      → Update (teilweise)
DELETE /<slug>/<slug>/{id}      → Delete (?hard=1 für Hard-Delete)
```

**Bulk:** `POST /<slug>/bulk` → `{ action, ids[] }` (z. B. `delete_soft` / `delete_hard`)

> ✅ **Antworten ausschließlich über `Bookando\\Core\\Api\\Response`.** Einheitliche Payloads (`data`, `meta`, optionale `error`)
>  sind verpflichtend; direkte `WP_REST_Response`-Instanzen bleiben Altlasten und sollen nicht
>  mehr in neuen Handlern auftauchen.

**Versionierung & Mobile**

- `/wp-json/bookando/v1/...` produktiv; `/v2` für Breaking Changes.  
- Portale via **JWT (HttpOnly‑Cookie)**; Apps via **OAuth2/OIDC** (Header).  
- `tenant_id` **immer** serverseitig ableiten (kein Vertrauen in Query).  
- **Delta‑Sync:** `updated_at` + `?updated_after=ISO‑8601`.  
- **Sharing:** `wp_bookando_shares` (Opt‑in, Ablauf, Scopes).

---

## 🌐 Dispatcher‑Konzept & Permission‑Helper

| Dispatcher | Route / Hook | Security |
|---|---|---|
| **AjaxDispatcher** | `wp_ajax_bookando` | Nonce + `current_user_can()` |
| **RestDispatcher** | `/wp-json/bookando/v1/<slug>/<type>[/{subkey}]` (Legacy-Catch-all) | Modul wird über `Api/Api.php` registriert; Permission via `RestModuleGuard` |
| **WebhookDispatcher** | `/wp-json/bookando/v1/webhook/<type>` | Token + Signatur (+ Replay‑Schutz) |

**Permission‑Callback (neu, zentral nutzbar via `RestModuleGuard`):**

```php
use Bookando\Core\Dispatcher\RestModuleGuard;

register_rest_route(
    'bookando/v1',
    '/customers/list',
    [
        'methods'             => 'GET',
        'callback'            => [\Bookando\Modules\customers\RestHandler::class, 'list'],
        'permission_callback' => RestModuleGuard::for('customers'),
    ]
);
```

- `RestDispatcher::registerModule('<slug>', RestHandler::class)` wird in `Api/Api.php::register()`
  ausgeführt. Das Permission-Callback basiert auf `RestModuleGuard` und darf wahlweise eine
  modulinterne `guardPermissions()`/`guardCapabilities()`-Methode aufrufen.
- Zusätzliche Guards liefern `bool|WP_Error` und nutzen den zentralen `Bookando\Core\Auth\Gate`
  (anstatt direkt `current_user_can()` aufzurufen).
- Die Catch-all-Route `/bookando/v1/{module}/{type}/{subkey?}` bleibt ausschließlich für bestehende
  Clients aktiviert und wird mittelfristig entfernt. Neue Routen **müssen** explizit über das Modul
  registriert werden (`Api::registerRoutes()`).

---

## 🛡 Sicherheit & Datenschutz

- **Nonces** für alle Forms/Links → `wp_nonce_field`, `check_admin_referer`.  
- **Capabilities**: `manage_bookando_<slug>`.  
- **Sanitizing/Escaping** nach WP‑Standards.  
- **JWT** (HTTP‑only Cookie) für Portale; **OAuth2/OIDC** via IdentityServer/Keycloak.  
- **reCAPTCHA (v3)** für Buchungen/Login/Kontakt (Settings).  
- **DSGVO**: Consent, Timestamp‑Log, Datenexport.  
- **Guards (Server‑seitig, Pflicht):** Gate + Capability + Lizenz in **jedem** Endpoint.  
- **Multi‑Tenant Fail‑Safe**: BaseModel injiziert Tenant‑Filter.

---

## 🎓 Education‑Module (OrphyDrive‑Features)

| Sub‑Modul         | Kernfunktionen                                                    |
|-------------------|-------------------------------------------------------------------|
| `training_plan`   | Übungs‑ & Kompetenz‑Matrix (variabel)                             |
| `progress`        | Emoji‑/Prozent‑Bewertung, Historie, Offline‑Sync, PDF‑Export      |
| `student_notes`   | Freitext, Datei‑Uploads, Canvas‑Skizzen                           |
| `learning_materials` | Video/PDF‑Bibliothek, Versionierung                           |
| `tests`           | MC/Freitext‑Quiz, Auto‑Scoring                                    |

Alle Sub‑Module teilen sich REST‑API & PWA‑Frontends.

---

## 🧭 UX‑Navigation (Admin)

```
Bookando 
├─ 📊 Dashboard (BI: Termine, Auslastung, Umsatz, …)
├─ Buchungen
├─ Kunden
├─ 📦 Angebote
│   ├── Dienstleistungen
│   ├── Kurse & Events
│   ├── Gutscheine
│   ├── Abos
│   └── Rabattcodes
├─ Ressourcen
│   ├── Mitarbeitende
│   ├── Orte
│   ├── Räume
│   └── Fahrzeuge
├─ 🔗 Finanzen
│   ├── Rechnungen
│   ├── Zahlungen
│   └── Mahnlauf
├─ ⚙️ Einstellungen
│   ├── Allgemein (White‑Label, Security, Analytics)
│   ├── Design
│   ├── Benutzerdefinierte Felder
│   └── Benachrichtigungen
├─ 🎓 Ausbildung
│   ├── Onlinekurse
│   ├── Ausbildungskarte/Trainingsplan
│   ├── Lernmaterialien
│   └── Tests
......
```

Menü wird dynamisch aus `module.json["group"]` + Lizenzstatus generiert.

---

## 📶 Portale & Offline‑Support

| Portal                  | Technik         | Offline | Rollen             |
|-------------------------|-----------------|---------|--------------------|
| Admin (`/wp-admin`)     | Vue SPA‑Embed   | –       | Admin              |
| Mitarbeitende (`/employee`) | Stand‑alone SPA | ✅     | Lehrperson         |
| Kunde / Schüler (`/portal`) | PWA             | ✅     | Kunde/Schüler      |

*SW‑Strategie:* *network‑first* kritisch, *cache‑first* statisch, **IndexedDB** für Ausbildungskarte.

---

## 🎨 White‑Label & Analytics

- **White‑Label:** Logo, Name, Farben (Optionen `white_label_*`).  
- **Analytics/Tag Manager:** Events `booking_started`, `booking_submitted`, `course_purchased`.  
- **Social‑Login:** Google/Facebook/Apple via OAuth2.

**White‑Label‑Optionen (Auszug):**

| Option Key             | Beschreibung                              |
|------------------------|--------------------------------------------|
| `white_label_name`     | Überschreibt „Bookando“ im Menü/Logos      |
| `white_label_logo`     | URL oder Attachment‑ID für Logo            |
| `primary_color`        | HEX‑Wert für Akzentfarbe                   |
| `secondary_color`      | Zweitfarbe (optional)                      |
| `support_url`          | Link in „Hilfe/Support“                    |
| `emails_from_address`  | Absender‑E‑Mail                            |

---

## 🗝 Lizenzmodell, Pläne & Feature‑Mapping (zentral)

Zentrale Pflege in **`src/Core/Licensing/license-features.php`**.

**Pläne:** `starter`, `pro`, `academy`, `enterprise`

**Beispiel‑Definition (Ausschnitt):**

```php
return [
  'starter' => [
    'modules' => ['customers','employees','locations','services','resources','events',
                  'appointments','packages','payments','invoices','discounts',
                  'notifications','custom_fields'],
    'features' => ['export_csv','analytics_basic','multi_location','group_appointments',
                   'basic_payments','basic_notifications','basic_calendar_sync',
                   'invoices','taxes','waiting_list','event_tickets','webhooks','rest_api_read'],
  ],
  'pro' => [
    'modules' => ['@starter','refunds','analytics','reports','online_meeting','app_mobile'],
    'features' => ['@starter','pdf_export','employee_scheduler','multi_calendar','analytics_advanced',
                   'user_roles','multiple_payments','refunds','calendar_sync','online_meeting',
                   'rest_api_write','custom_reports','integration_zoom','integration_teams','integration_meet','mobile_app'],
  ],
  'academy' => [
    'modules' => ['@pro','education_cards','learning_materials','tests','training_plans','document_upload'],
    'features' => ['@pro','student_offline','progress_tracking','multi_tenant','feedback','qanda',
                   'learning_progress','digital_report','school_custom_features','competence_matrix','grade_export'],
  ],
  'enterprise' => [
    'modules' => ['@academy'],
    'features' => ['@academy','priority_support','white_label','advanced_security','sso','unlimited_domains'],
  ]
];
```

> Mapping ändert **ohne Codeänderung** die Freischaltung (Server‑seitig).

---

## 🔑 Lizenzmodell & SaaS‑Betrieb (kurz)

- Lizenz‑Key Felder: `plan`, `modules`, `features`.  
- Pläne: `starter`, `pro`, `academy`, `enterprise`.  
- **LicenseManager** prüft zyklisch via Remote‑API.  
- **Gnadenfrist:** 30 Tage.  
- **SaaS‑Multi‑Tenant:** WP‑Multisite oder `tenant_id`.  
- Status‑Icons: 🔓 aktiv, 🔐 gesperrt, ⏳ Gnadenfrist, ❌ abgelaufen.

---

## 🧩 Module & Features (Master‑Architektur, SaaS‑ready)

### Module

- **customers** – CRM, Profile, Felder  
- **employees** – Mitarbeitende/Trainer, Rollen, Abwesenheiten, Kalender‑Sync  
- **locations** – Standorte, Räume, Adressen, Zuordnung zu Services/Events  
- **services** – Dienstleistungen, Typen, Preise, Dauer, Extras  
- **resources** – Räume, Geräte, Fahrzeuge, Inventar, Reservierung  
- **events** – Kurse, Seminare, Events, Buchungen, Warteliste, Tickets  
- **appointments** – Einzelbuchungen, Terminverwaltung, CustomFields  
- **packages** – Bundles/Abos, Zuordnung zu Kunden/Services  
- **payments** – Zahlungen, Transaktionen, Refunds, Zahlungsarten/Gateways  
- **invoices** – Rechnungen, Gutschriften, Steuern, PDF‑Export  
- **discounts** – Gutscheine, Rabattcodes, Limits  
- **notifications** – E‑Mail, SMS, WhatsApp, Vorlagen/Trigger/Logs  
- **custom_fields** – Benutzerdefinierte Felder (Modul‑Mapping)  
- **analytics** – Statistiken/Berichte (DataView/BI)  
- **reports** – CSV, PDF, Exporte, Custom‑Reports  
- **education_cards**, **learning_materials**, **tests**, **training_plans**, **document_upload** (Academy+)

### Feature‑Flags (Beispiele)

`waitlist`, `calendar_sync`, `feedback`, `mobile_app`, `webhooks`, `multi_tenant`, `white_label`,  
`integration_zoom|meet|teams`, `rest_api_read|write`, `export_csv|pdf`, `refunds`, `online_payment`,  
`user_roles`, `custom_reports`, `priority_support`, `analytics_advanced`, `notifications_whatsapp`,  
`student_offline`, `progress_tracking` …

---

## 🧩 Frontend‑Portale, Shortcodes & Design (Praxis)

- Shortcodes: `[bookando_booking_form]`, `[bookando_customer_portal]`, `[bookando_employee_portal]`  
- SPA‑Mount inkl. Designvariablen; Logik via Vue/JS, **kein** PHP‑Render.  
- **Kein** automatisches Laden von Modul‑Frontend‑Assets.

---

## 🧱 Komponentenrichtlinien – Formulare

- Basierend auf `admin-ui.css` (Utilities).  
- Struktur: `.bookando-form`, Layout `.bookando-grid two-columns/three-columns`, `.form-group`, `.form-actions`.  
- Buttons: `.bookando-btn --primary/--secondary/--danger`.  
- Fehler: `.error-message`, `.is-error`.  

### **Fallback‑Templates (optional)**

Klassische PHP‑Templates via:

```bash
node scripts/generate-fallbacks.js
```

---

## ⚙️ Modulstruktur – Pflicht & Best‑Practice

### Pflicht‑Verzeichnisse (Minimal‑Setup)

| Ordner / Datei | Zweck |
|---|---|
| `Module.php` | Einstiegspunkt (`BaseModule`), bindet Admin/Api/Capabilities |
| `module.json` | Manifest (Meta, Lizenz, Abhängigkeiten) |
| `Admin/Admin.php` + `Templates/admin-vue-container.php` | Admin‑SPA‑Wrapper + Menü |
| `Api/Api.php` | registriert Routen am RestDispatcher |
| `Installer.php` | Migrationen |
| `RestHandler.php` | statische Handler‑Methoden (CRUD, Bulk, Spezial) |
| `Model.php` | empfohlen (Domain-/DB‑Logik, BaseModel) |
| `assets/vue/` | SPA (Vite, **nur Admin**) |

### Empfohlene Best‑Practices (größere Teams)

| Ordner | Nutzen |
|---|---|
| `views/` | PHP‑Views als Fallback / SSR |
| `tests/` | PHPUnit / Integration‑Tests |
| `base/` (Core) | Abstrakte Klassen & Traits |
| `assets/vue/components/` | geteilte Komponenten |
| `assets/vue/utils/` | API‑Wrapper, Helpers |

#### Lizenzstatus im Admin‑Menü & UI

- Alle Module werden angezeigt; UI prüft Lizenzstatus und zeigt Overlay/Sperre (Upgrade‑Hinweis).

**Vue‑Beispiel Overlay:**

```vue
<template>
  <div>
    <div v-if="!isAllowed" class="license-overlay">
      <strong>Nur mit {{ planName }}-Lizenz verfügbar.</strong>
      <button @click="gotoUpgrade">Upgrade jetzt</button>
    </div>
    <div :class="{ 'blur-sm pointer-events-none': !isAllowed }">
      <!-- Modul-Inhalte -->
    </div>
  </div>
</template>
<script setup>
const isAllowed = window.BOOKANDO_VARS?.module_allowed ?? true
const planName = window.BOOKANDO_VARS?.required_plan ?? 'PRO'
function gotoUpgrade() { window.open('https://bookando.ch/upgrade', '_blank') }
</script>
```

---

## 🧪 Testing & Deployment

- **PHPUnit** + **Vitest**.
- **WP_Mock** für Unit‑Tests.
- **GitHub Actions**: lint → build → zip‑release.
- Optionaler ZIP‑Deploy für WP‑Repo.
- `VITE_USE_CDN=true` nur setzen, wenn Vue/Pinia/vue-i18n im Build externalisiert werden (CDN-Auslieferung; benötigt WordPress ≥ 6.5 wegen `wp_enqueue_script_module`).

---

## ✅ Automatisierte Review‑ & Test‑Checkliste (CLI)

- `npm run bookando:review --module=MODULNAME` → führt A11y, Security, UX, Tests durch; Report‑File.

**Tipp:** Die README‑Checkliste lässt sich für PR‑Reviews verwenden.

---

## 🧰 CLI‑Beispiele & ENV‑Flags

| Ziel | Befehl | Ergebnis |
|------|--------|----------|
| **Core & alle Module** | `vite build` | erzeugt `/dist/core` und `/dist/modules/*` |
| **Nur Core** | `VITE_TARGET=core vite build` | schnelles Rebuild bei Designänderungen |
| **Ein bestimmtes Modul** | `VITE_MODULE=events vite build` | nur `/dist/modules/events/` |
| **Watch-Modus Modul** | `VITE_MODULE=progress vite dev` | HMR für Education‑SPA |

<details>
<summary>Beispiel <code>vite.config.ts</code> (Ausschnitt)</summary>

```ts
export default defineConfig({
  base: '/',
  server: {
    origin: 'http://bookando-site.local:5173',
    port: 5173,
    strictPort: true,
    fs: { strict: false },
    hmr: { protocol: 'ws', host: 'localhost', port: 5173 }
  },
  plugins: [
    vue(),
    cdnImport({ modules: [{ name: 'vue', var: 'Vue', path: 'https://cdn.jsdelivr.net/npm/vue@3.4.27/dist/vue.global.prod.js' }] }),
    purgecss({
      content: ['./src/**/*.vue','./src/**/*.ts','./src/**/*.js','./**/*.php','./src/Core/Design/assets/scss/**/*.scss'],
      safelist: [/^bookando-/],
      defaultExtractor: c => c.match(/[\w-/:]+(?<!:)/g) || []
    })
  ],
  resolve: { alias },
  build: {
    rollupOptions: {
      input: getModuleEntries(),
      external: ['vue'],
      output: {
        globals: { vue: 'Vue' },
        entryFileNames: i => i.name === 'bookando' ? 'core/bookando-style.js' : `${i.name}/main.js`,
        assetFileNames: a => {
          if (a.name && a.name.endsWith('.css')) {
            const m = a.name.match(/modules[\\/](\w+)[\\/]/i)
            if (m && m[1]) return `${m[1].toLowerCase()}/main.css`
            if (a.name.includes('admin-ui')) return 'core/bookando-style.css'
            return '[name]/main.css'
          }
          return '[name][extname]'
        }
      }
    },
    outDir: 'dist',
    emptyOutDir: false,
  }
})
```
</details>

---

## 🧪 Beispiel‑Tests

**PHP (WP_Mock):**

```php
use WP_Mock\Tools\TestCase;

class EventsTest extends TestCase {
  public function test_slug_sanitized() {
    $this->assertEquals('contact', \Bookando\sanitize_slug('Contact '));
  }
}
```

**TypeScript (Vue Test Utils):**

```ts
import { mount } from '@vue/test-utils'
import Table from '@/components/Table.vue'

test('renders rows', () => {
  const wrapper = mount(Table, { props: { rows: [1,2,3] } })
  expect(wrapper.findAll('tr')).toHaveLength(3)
})
```

---

## 🗺 Docs‑Map

| Dokument | Zweck |
|----------|-------|
| `Bookando-Plugin-Struktur.md` | Hauptarchitektur (dieses Dokument) |
| `docs/Licensing.md` | Lizenz‑ & SaaS‑Details |
| `docs/Guidelines.md` | Vue‑, CSS‑, Naming‑Konventionen |
| **Bookando SQL** | Datenbankschema (separat) |
| **REST API Reference** | auto‑generiert per `doctor.php` |

---

## 📑 Changelog 2.3 (2025‑05‑21)

- Zusammenführung v 2.0 & v 2.1.  
- Build‑Setup: Standard‑Config + optionale Multi‑Config.  
- Modul‑Ordner: Pflicht vs. Best‑Practice.  
- `generate-module.js` dokumentiert.  
- Lizenz & SaaS in *docs/Licensing.md*.  
- Recaptcha, Analytics, White‑Label in „System & Sicherheit“.  
- Education‑Module: Offline‑Sync, Emoji‑Rating, PDF‑Export.  
- Lizenz‑Feature‑Mapping konsolidiert.  
- Autom. Feature‑Scaffold.  
- Modul-/Feature‑Liste angepasst (SaaS‑ready).  
- Versionierung & Docstrings.  
- Naming Guidelines.  
- SaaS/Multi‑Tenant‑Infos.

**Breaking Changes**

- `vite.config.core.ts`/`vite.config.module.ts` optional – Standard‑CI nutzt `vite.config.ts`.  
- `Views/` & `Tests/` empfohlen, nicht Pflicht.  
- Text‑Domain bleibt `bookando`.  
- PHP‑Fallback‑Views nicht mehr Standard (stattdessen Admin‑SPA‑Mount).  
- RestDispatcher + RestHandler (statisch) empfohlen.  
- `BOOKANDO_VARS` via `wp_add_inline_script` gemerged.

---

## ✨ Changelog 2.4 (2025‑11‑04)

- **Permission‑Helper `bookando_allow()`** dokumentiert und empfohlen.  
- **Multi‑Tenant Fail‑Safe** im BaseModel als Pflicht.  
- **Design‑Leitplanken** (Tokens→Maps→Utilities) verbindlich.  
- Review‑Checkliste um Security/Tenant/Design/DB/CI geschärft.  
- **Extended‑Fassung:** enthält zusätzlich die in v 2.2 gelisteten CLI‑Beispiele, ENV‑Flags, White‑Label‑Optionen, Beispiel‑Tests & Docs‑Map.

---

© 2025 Bookando GmbH — Text‑Domain: `bookando`
