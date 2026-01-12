# 📐 Bookando Plugin Governance & Modulrichtlinien

> **Version:** 1.0 · Stand 2025-11-08  \
> **Geltungsbereich:** Gesamtes Bookando Ökosystem (WordPress Plugin, SaaS/Cloud, iOS & Android Apps)

Diese Richtlinie definiert **verbindliche Regeln** für Struktur, API, Layout, Mehrsprachigkeit
und Funktionalität aller Bookando Module. Sie ergänzt die technische Architektur
([`Bookando-Plugin-Struktur.md`](./Bookando-Plugin-Struktur.md)) und ist bei jeder
Neuentwicklung, Migration oder Prüfung zwingend zu berücksichtigen.

---

## 1. Grundprinzipien

1. **Single Source of Truth** – Fachlogik, Datenhaltung und APIs sitzen zentral im Plugin.
   SaaS- und App-Clients konsumieren ausschließlich dokumentierte REST-Endpunkte.
2. **Mandantenfähigkeit & Sicherheit** – Jeder Request ist tenant-gebunden, Lizenz- und
   Feature-Flags sind strikt einzuhalten. Kein direkter Zugriff auf fremde Daten.
3. **Modularität & Wiederverwendung** – Jedes Modul ist autonom, nutzt aber zentrale
   Core-Komponenten (Designsystem, Dispatcher, Services) und vermeidet Duplikate.
4. **API-First & Offline-Ready** – Alle Funktionen sind über REST ansprechbar, unterstützen
   Delta-Sync (`updated_after`) und strukturierte Fehlermeldungen für Mobile-Clients.
5. **Internationalisierung** – Deutsch, Englisch und weitere Zielsprachen werden vollständig
   abgedeckt (UI, API Labels, Texte, Validierung). Keine hardcodierten Strings.
6. **Testbarkeit & Observability** – Module liefern automatisierte Tests (PHPUnit/Vitest)
   und nutzen den zentralen ActivityLogger für Audits.

---

## 2. Verzeichnis- & Naming-Konventionen

| Element | Vorgabe |
|---------|---------|
| Modul-Root | `src/modules/<slug>/` · Slug in `kebab_case`, englisch, plural (`customers`). |
| Namespaces | `Bookando\Modules\<slug>\...` für PHP, `@bookando/modules/<slug>` für TS. |
| Manifest | `module.json` mit `slug`, `name`, `description`, `group`, `version`, `features`, `dependencies`, `doc`. |
| PHP-Klassen | PascalCase, Datei = Klasse (PSR-4). |
| Vue-Komponenten | PascalCase (`CustomersView.vue`). |
| Stores/Composables | `use<PascalName>.ts` / `<slug>Store.ts`. |
| Tests | `Tests/` mit `Unit`, `Feature`, `Browser` (optional). |

**Pflichtdateien pro Modul:**

- `Module.php` – Registrierung (Capabilities, Assets, Installer Hook).
- `Api/Api.php` – REST-Routen via `register_rest_route`.
- `RestHandler.php` – Implementierung der Endpunkte.
- `Admin/Admin.php` + `Templates/admin-vue-container.php` – Mount für SPA.
- `assets/vue/main.ts` – Bootstrapping inkl. i18n/Store.
- `assets/css/admin.scss` – modulare Styles (nutzt SCSS Token via Import).
- `module.json` – Manifest.

**Optionale Dateien** sind nur erlaubt, wenn dokumentiert (`docs/<slug>.md`) und in `module.json.doc` verlinkt.

---

## 3. API-Design & RestHandler-Regeln

1. **Namensschema**: `/wp-json/bookando/v{version}/{module}/{resource}`. Versionen werden
   nur bei Breaking Changes erhöht (`v1` → `v2`).
2. **Standard-CRUD** muss `list`, `read`, `create`, `update`, `delete` Methoden bereitstellen.
   Diese Methoden sind als **statische** Funktionen in `RestHandler` abzulegen (`list()`, `get()`, `create()` ...).
3. **Permission Callback**: Jeder Route ist mit `RestModuleGuard::for('<slug>')` oder einem
   äquivalenten Guard zu schützen. Zusätzliche Feature-Prüfungen erfolgen über
   `LicenseManager::ensureFeature('<slug>', '<feature>')` im Handler.
4. **State-Guard**: Jeder `/state`-Endpoint (oder vergleichbare Aggregationen) nutzt einen
   separaten Guard wie `RestModuleGuard::for('<slug>', [RestHandler::class, 'guardState'])`,
   um den Modulstatus nur für berechtigte Rollen freizugeben. Das Ressourcen-Modul dient als
   Referenz (`tests/Integration/Rest/ResourcesPermissionsTest.php`).
   Auomatische Prüfung: `scripts/validate-modules.mjs` schlägt fehl, wenn `RestHandler.php`
   `RestModuleGuard::` oder `WP_REST_Server::` ohne entsprechendes `use`-Statement verwendet.
5. **Request-Validierung**: Eingaben über modulare Validatoren wie
   `Bookando\Modules\customers\CustomerValidator` oder `zod`-Schemata (TS) absichern.
   Fehlermeldungen folgen dem Response-Schema in
   [`docs/api-response-conventions.md`](./api-response-conventions.md) und werden über
   `__()`/`_x()` lokalisiert. Die PHPUnit-Suite dokumentiert die Erwartungshaltung, z. B.
   `tests/Integration/Rest/CustomersRoutesTest.php`.
6. **Tenant-Scope**: Alle Queries nutzen `BaseModel`/`BaseRepository` mit implizitem `tenant_id` Filter.
   Mobile OAuth/JWT Tokens liefern Tenant-Claim (`tenant_id`). Kein Vertrauen in Request-Parameter.
7. **CRUD-Kontrakt**: Collections stellen mindestens `list`, `read`, `create`, `update`, `delete`
   bereit. READ/WRITE-Methoden bedienen dasselbe Schema (`/resource` + `/resource/{id}`) und
   spiegeln sich im `RestHandler` als dedizierte Methoden wider (`create()`, `update()` usw.).
   State-abhängige Mutationen dokumentieren ihre Seiteneffekte in `meta.sync` und lösen Tests in
   `tests/Integration/Rest/RouteSnapshotTest.php` aus.
8. **Pagination & Filter**: Standard-Response `{ data: [], meta: { pagination, filters, sort } }`.
   Query-Parameter: `page`, `per_page`, `sort`, `direction`, `filters[...]`.
9. **Delta Sync**: Jedes Modul stellt `updated_after` Filter sowie `meta.sync.checksum`
   bereit. Für Mobile Clients muss ein `sync_state` Endpoint existieren (z. B. `/state`).
10. **Webhook-Kompatibilität**: Änderungen, die externe Systeme betreffen, triggern Events
   über den `WebhookDispatcher` (`bookando_{module}_{action}` Topic).
11. **Error Logging**: Kritische Fehler werden mit `ActivityLogger::log()` erfasst (Severity `error`).
12. **API-Dokumentation**: Neue Endpunkte werden in `docs/api-routing.md` ergänzt.

---

## 4. SaaS-, iOS- & Android-Integration

1. **Auth-Flow**: SaaS-Backend und Mobile Apps nutzen OAuth2/OIDC. REST-Handler akzeptieren
   `Authorization: Bearer` Tokens und binden Mandant + User-Rollen ein.
2. **Plan/Lizenz**: Funktionen, die nur in bestimmten SaaS-Plänen verfügbar sind, prüfen
   `LicenseManager::currentPlan()` und liefern bei Verstoß HTTP 402 (Payment Required) mit
   eindeutiger `code` (`plan_upgrade_required`).
3. **Offline Support**: Responses enthalten konsistente `updated_at` Felder. Bulk-Endpoints
   dürfen max. 500 Einträge liefern. Mutationen sind idempotent und geben `etag` zurück.
4. **Notification Hooks**: Aktionen, die Mobile-Push benötigen, dispatchen Events via
   `NotificationService::queuePush($tenantId, $payload)` (Core-Service).
5. **Data Sharing**: Module, die Daten zwischen Mandanten teilen (z. B. Franchise), müssen
   `TenantManager::authorizeShare($from, $to, $scope)` verwenden und Audit-Log schreiben.
6. **Feature Parity**: Jede Funktion im Admin hat ein API-Äquivalent, sodass Mobile Clients
   ohne direkten WP-Backend-Zugriff arbeiten können.

---

## 5. Internationalisierung & Sprachsensitivität

1. **PHP**: Übersetzbare Strings ausschließlich über `__('string', 'bookando')` oder
   `_x()` mit Kontext. Keine concatenated Strings; Platzhalter via `sprintf`.
2. **Vue/TS**: Texte über `useI18n()` aus `src/Core/Design/i18n/`. Modul-spezifische
   Übersetzungen liegen unter `assets/vue/i18n/<locale>.json` und werden in `main.ts`
   registriert (`registerModuleMessages('<slug>', messages)`).
3. **Module Labels**: `module.json.name` und `description` enthalten `default` (Deutsch)
   sowie `en`, `fr`, `it` Felder. Fehlende Übersetzungen blockieren den Merge.
4. **Validatoren & Guards**: Neue Fehlermeldungen aus Request-Validierung oder
   Guard-Callbacks (z. B. `RestModuleGuard`) nutzen die WordPress-Lokalisierung und
   werden zusätzlich in den Modul-i18n-Dateien gespiegelt, falls sie in der UI
   auftauchen. Prüfe `scripts/check-rest-i18n.mjs` und `npm run i18n:locale-audit`.
5. **Formate & Lokalisierung**: Datum/Uhrzeit über `LocaleBridge` (`formatDate`, `formatTime`).
   Keine manuellen Formatierungen.
6. **Content Sensitivity**: Strings berücksichtigen gendersensible Sprache (`Nutzer:innen`).
   Sprachspezifische Anpassungen erfolgen in den jeweiligen JSON-Dateien.

---

## 6. Layout & UX-Richtlinien

1. **Designsystem**: Alle Komponenten nutzen `src/Core/Design/components`.
   Eigene UI-Elemente sind nur erlaubt, wenn sie generisch in das Designsystem
   überführt werden können.
2. **Spacing & Tokens**: Styles importieren `_tokens.scss` und `_utilities.scss`.
   Fixe Pixelwerte sind verboten; stattdessen Maps (`map-get($spacing, 'md')`).
3. **Responsiveness**: Vue-Views verwenden `useResponsive()` und unterstützen Breakpoints
   `sm`, `md`, `lg`. Admin-Listen bieten eine mobile Tab-Darstellung.
4. **Accessibility**: Pflichtattribute (`aria-*`, `role`) setzen, Fokusreihenfolge testen,
   Tastaturbedienung sicherstellen. Kontrast ≥ 4.5:1.
5. **State Management**: Stores kapseln API-Zugriffe, Komponenten bleiben präsentationslastig.
6. **Error & Empty States**: Jede Liste/Form implementiert Loading-, Error- und Empty-State.
7. **Design Reviews**: Änderungen werden gegen das zentrale Figma-File geprüft (Verweis in
   Modul-Dokumentation).

---

## 7. Funktionale Moduleigenschaften

1. **Installer**: Optionaler `Installer.php` führt Schema-Migrationen aus (`SchemaManager`).
   Migrationen sind idempotent und versioniert (`SchemaVersion` Tabelle).
2. **Capabilities**: `Capabilities.php` registriert alle `manage_bookando_<slug>_*` Caps.
   Feinere Scopes (lesen/schreiben/export) definieren boolesche Flags im Modul.
3. **Settings & Feature Flags**: Globale Moduleinstellungen laufen über `Core\Admin\Settings`.
   Modulinterne Optionen nutzen `OptionRepository` (`bookando_<slug>_*`).
4. **Background Jobs**: Langläufer über WP-Cron oder SaaS-Worker; Status im Modul
   (`/state` Endpoint) bereitstellen.
5. **Intermodule Dependencies**: `module.json.dependencies` pflegen. Aktivierung schlägt
   fehl, wenn Abhängigkeit nicht verfügbar (`ActivationException`).
6. **Auditing**: Kritische Aktionen loggen `ActivityLogger::log('<slug>.<action>', ...)`
   inkl. `tenant_id` und `actor_id`.

---

## 8. Entwicklungs- & Review-Prozess

1. **Scaffolding**: Neue Module ausschließlich über `node scripts/generate-module.js` erzeugen.
2. **CI Checks**: `npm run lint`, `npm run test`, `composer test` müssen grün sein.
3. **Code Review**: Pull Requests referenzieren diese Richtlinie und dokumentieren Abweichungen.
4. **Docs & Changelog**: Jede Änderung aktualisiert `docs/api-routing.md`, modulare Docs,
   sowie `CHANGELOG.md` (falls vorhanden). README erhält Link auf dieses Dokument.
5. **Versioning**: Module erhöhen `module.json.version` semantisch (`MAJOR.MINOR.PATCH`).
6. **Testsuite**: Für das Customers-Modul existieren dedizierte PHPUnit-Unit-Tests (`tests/Unit/Modules/Customers/CustomerServiceTest.php`),
   WordPress-Integrationstests (`tests/Integration/Rest/CustomersRoutesWpTest.php`) sowie Vitest-Spezifikationen
   für Store- und Composable-Logik (`src/modules/customers/assets/vue/store/store.test.ts`,
   `src/modules/customers/composables/useCustomerData.test.ts`). Diese Suiten sind Teil der CI-Checks.
7. **Review-Checkliste**: Verifiziere, dass jedes neue oder geänderte Modul `module.json`
   mit `default`, `de`, `en`, `fr`, `it` für `name`, `alias`, `description` pflegt und die
   zugehörigen Frontend-i18n-Dateien dieselben Sprachen enthalten.
8. **REST-Handler-Imports**: Der Governance-Test `tests/phpunit/RestHandlerImportsTest.php` (ausgeführt über
   `composer test`) lädt alle `RestHandler.php` Dateien und stellt sicher, dass `RestModuleGuard` sowie
   `WP_REST_Server` importiert sind. Neue oder angepasste Handler müssen diese Vorgabe erfüllen.

---

## 9. Durchsetzung & künftige Prüfungen

- **Automatisierte Checks**: Linting/Tests prüfen Naming, i18n-Abdeckung und REST-Schemata.
- **Review-Guideline**: Prüfer:innen verweisen auf dieses Dokument. Abweichungen erfordern
  explizite Ausnahmegenehmigung (Dokumentation im PR).
- **Self-Audit**: Vor jedem Release `scripts/doctor.php` und `scripts/check-license.js`
  ausführen. Zusätzlich `docs/plugin-governance-checklist.md` (Folgedokument) pflegen.

## 10. Automatisierte Governance-Validierung

- **Pflichtdateien**: Jedes Modul in `src/modules/<slug>/` muss `Module.php`, `Api/Api.php`,
  `RestHandler.php`, `Admin/Admin.php`, `Templates/admin-vue-container.php`,
  `assets/vue/main.ts`, `assets/css/admin.scss` sowie `module.json` bereitstellen.
- **Keine TODO-Platzhalter**: Module enthalten keine offenen `TODO`-Hinweise in PHP-, TS-,
  Vue-, CSS- oder SCSS-Dateien. Unfertige Arbeiten werden stattdessen über Tickets
  dokumentiert.
- **Standard-Hooks**: `Module.php` registriert Admin-Menüs (`bookando_register_module_menus`),
  bindet Admin-Assets über `admin_enqueue_scripts` ein und meldet REST-Routen über
  `registerRestRoutes()` an.
- **Script**: `npm run validate:modules` prüft Schema, Pflichtdateien, TODO-Freiheit und
  Hook-Konventionen. Der CI-Workflow ruft den Befehl separat auf und bricht bei Verstößen ab.

Die Einhaltung dieser Governance garantiert ein einheitliches, wartbares und
zukunftsfähiges Plugin über alle Plattformen hinweg.

## 11. Logging & Observability

- **Info-Level gezielt nutzen**: `ActivityLogger::LEVEL_INFO` ist echten Ereignissen
  vorbehalten, die für Betreiber:innen oder Auditor:innen nachvollziehbar sein müssen
  (z. B. erfolgreiche Lizenzvalidierung, Modulaktivierung, abgeschlossene Migrationen).
- **Diagnose-Logs kapseln**: Reine Diagnose- oder Trace-Ausgaben (z. B. Feature-Checks,
  Polling, Heartbeats) dürfen nur geschrieben werden, wenn `BOOKANDO_DEV` aktiv ist
  oder `WP_DEBUG` auf `true` steht. In Produktionsumgebungen bleiben diese Einträge
  unterdrückt, um die Audit-Trails nicht zu verwässern.
- **Fehler & Warnungen priorisieren**: Für unerwartete Zustände (`warning`) und
  Ausnahmefälle (`error`) gilt weiterhin die Pflicht zur Dokumentation inklusive
  Kontext- und Payload-Daten.
