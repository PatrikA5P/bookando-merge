
# 📦 Plugin-Struktur für Bookando (Technische Dokumentation)

## 🔧 Zielsetzung

Bookando ist ein zukunftssicheres, modular aufgebautes WordPress-Plugin zur Verwaltung von Kursen, Buchungen und Events – insbesondere für Fahrschulen und ähnliche Anbieter. Es ersetzt externe Tools wie Amelia und integriert sich mit Bexio, Google Drive, Zahlungsanbietern und Kalenderdiensten.

## 🧱 Architekturübersicht

```
bookando/
├── bookando.php                   # Haupt-Plugin-Datei, lädt Core\Plugin
├── composer.json                 # Autoloading + PHP-Dependencies (PSR-4)
├── package.json                  # JS-Abhängigkeiten für Vite, Vue etc.
├── vite.config.js                # Root-Config für Core + Module
├── vite.config.core.js           # Nur für globale SCSS/Design-Assets
├── vite.config.module.js         # Build einzelner Module via env.MODULE
├── .gitignore                    # Standard
├── readme.txt                    # WP-Repository-kompatible Pluginbeschreibung
├── docs/                         # Technische Doku
│   ├── Bookando-Plugin-Struktur.md   # Architektur- und Strukturvorgabe (verbindlich)
│   └── Guidelines.md                 # Vue-, CSS-, Naming-Guides (optional)
├── scripts/                      # Dev-Tools
│   ├── generate-module.js       # 🛠 Erstellt vollständige Modulstruktur
│   ├── cleanup.js               # Entfernt alte dist-Dateien
│   ├── check-license.js        # Lizenzprüfung
│   ├── doctor.php              # Moduldiagnose
│   └── ...
├── languages/
│   └── bookando.pot             # POT-Datei für Übersetzungen (Textdomain: 'bookando')
├── vendor/                      # Composer-Autoload
│   └── ...
├── dist/                        # Vite-Build-Ausgabe (nicht versionieren!)
│   ├── core/                    # Admin-Styling
│   └── modules/
│       └── <modul>/            # main.js + style.css pro Modul
├── src/
│   ├── Core/
│   │   ├── Plugin.php          # Hauptklasse, initialisiert alles
│   │   ├── Loader.php          # Dispatcher, Module, Hooks
│   │   ├── Dispatcher/
│   │   │   ├── AjaxDispatcher.php
│   │   │   ├── RestDispatcher.php
│   │   │   └── WebhookDispatcher.php
│   │   ├── Licensing/
│   │   │   └── LicenseManager.php
│   │   ├── Manager/
│   │   │   ├── ModuleManager.php
│   │   │   └── ModuleManifest.php
│   │   ├── Admin/
│   │   │   ├── Menu.php
│   │   │   └── Settings.php
│   │   ├── Design/
│   │   │   ├── Templates/      # PHP-Templates für Formulare, Listen
│   │   │   └── assets/
│   │   │       ├── scss/
│   │   │       ├── icons/
│   │   │       ├── js/interop.js
│   │   │       └── vendor/
│   │   ├── Helpers.php         # zentrales Helper-Entrypoint
│   │   ├── Helper/             # z. B. Icons.php, Locales.php etc.
│   │   ├── Roles/              # CapabilityService.php (shared)
│   │   ├── Base/               # Abstrakte Basisklassen
│   │   └── Installer.php       # Optionaler Setup-Runner für Kerninstallationen
│   └── Modules/
│       ├── <modul>/
│       │   ├── Admin/              # Admin.php – Menü, Template, Slug
│       │   ├── Api/                # REST-Controller (CRUD)
│       │   ├── Capabilities.php    # Modul-spezifische Rechte (optional)
│       │   ├── Installer.php       # Setup-Logik (z. B. DB-Tabellen)
│       │   ├── Model.php           # Datenmodell + Logik
│       │   ├── Module.php          # Einstiegspunkt (extends BaseModule)
│       │   ├── Views/
│       │   │   └── AdminView.php   # PHP-View als Fallback (optional)
│       │   ├── Templates/
│       │   │   └── admin-table.php # Template für WP-Ansichten oder Overrides
│       │   ├── Tests/
│       │   │   └── ModuleTest.php  # Einstiegspunkt für Unit/Integration-Tests
│       │   ├── README.md           # Dev-Doku zum Modul (optional)
│       │   ├── module.json         # Manifest (slug, version, dependencies ...)
│       │   └── assets/
│       │       └── vue/
│       │           ├── components/
│       │           │   ├── Table.vue
│       │           │   ├── Filters.vue
│       │           │   ├── Actions.vue
│       │           │   ├── Pagination.vue
│       │           │   ├── Tabs.vue
│       │           ├── views/
│       │           │   └── Admin.vue
│       │           │   ├── TabAllgemein.vue (optinal)
│       │           │   ├── TabDetails.vue (optinal)
│       │           │   ├── TabNotizen.vue (optinal)
│       │           ├── utils/
│       │           │   ├── api.js
│       │           │   └── csv.js
│       │           └── main.js
│       └── ...

```

## ⚙️ Modulstruktur

Jedes Modul liegt in `src/Modules/<Modulname>/` mit PSR-4 Namespace `Bookando\Modules\<Modulname>`. Pflichtbestandteile:

- `Module.php` – Einstiegspunkt (muss `BaseModule` erweitern)
- `module.json` – Metadaten (Name, Version, Abhängigkeiten)
- Optional: `Admin.php`, `Api.php`, `Model.php`, `Templates/`, `Assets/`

Datei	Zweck
- `Module.php` - Einstiegspunkt – erweitert BaseModule, registriert Menü, REST etc.
- `Admin/Admin.php` - Stellt Admin-Oberflächen via register_menu() bereit
- `Api/Api.php` - Definiert REST-Routen dieses Moduls über register_routes()
- `Model.php` -	Enthält Datenmodell, z. B. DB-Tabelle via wpdb, Abfragen etc.
- `Views/	` -PHP-Dateien zur Darstellung (z. B. AdminView.php)
- `Templates/	` -Übersteuerbare Templates für Tabellen/Formulare (Admin + Frontend)
- `assets/vue/	` -Vue-Single-Page-App, optional mit Vite-Build im dist/
- `module.json	` -Definiert Metadaten des Moduls inkl. Lizenz, Sichtbarkeit, Abhängigkeiten

Beispiel `module.json`:

```json
{
  "slug": "events",                   // eindeutiger Modul-Slug (Pflicht)
  "name": {
    "default": "Veranstaltungen",
    "de": "Veranstaltungen",
    "en": "Events"
  },
  "description": {
    "default": "Verwalten Sie Ihre Termine & Veranstaltungen.",
    "en": "Manage your appointments and events."
  },
  "version": "1.0.0",
  "dependencies": ["customers", "employee"],
  "license_required": "pro",         // false, true oder z. B. "pro", "agency"
  "visible": true                    // wird in Modulübersicht angezeigt
}

```

## 🌐 Dispatcher-Konzept

### AjaxDispatcher

- Ein einziger Hook: `wp_ajax_bookando`
- Erwartet: `$_POST['module']`, `$_POST['action']`
- Sicherheitsprüfung via Nonce + `current_user_can()`

### RestDispatcher

- Globaler Namespace: `/wp-json/bookando/v1/`
- Module registrieren sich über zentrale Routen-Definition (z. B. `Events\Api::routes()`)

### WebhookDispatcher

- Route: `/wp-json/bookando/v1/webhook/<typ>` oder `admin-post.php?action=bookando_webhook`
- Sicherheit: Tokenprüfung + Log

## 🛡 Sicherheitsstrategie

- Alle Formulare, Links mit **Nonces** (`wp_nonce_field`, `check_admin_referer`)
- **Berechtigungen** prüfen mit `current_user_can('manage_bookando_<modul>')`
- Alle **Ausgaben escapen** (`esc_html()`, `esc_attr()`, etc.)
- REST/POST Eingaben validieren & sanitisieren (`sanitize_text_field`, `absint`, `wp_kses_post`)
- Webhook-Sicherheit: Token-basierte Verifikation + Response Logging

## 🌍 Internationalisierung

- Textdomain: `bookando`
- Alle Strings mit `__()`, `_e()`, `esc_html__()` etc.
- Zentrale `bookando.pot` in `/languages/`
- Vue-Formulare erhalten sprachspezifische Labels via `wp_localize_script()`

## 🖌 Design & Assets

### Zentrale Styles

- `admin-ui.css` = visuelles Design Adminbereich
- `layout.css` = strukturierende Layout-Klassen (z. B. Grid, Tabs)
- Werden **nur geladen**, wenn Modul/Seite aktiv ist

### Vue-Integration

Verzeichnisstruktur eines Vue-Moduls (z. B. im Design-Modul):

```
Modules/Design/
├── assets/
│   ├── js/
│   │   └── app.js (kompiliert)
│   └── src/ (Entwicklung)
│       ├── components/
│       ├── views/
│       └── main.js
├── Templates/
│   └── admin-vue-container.php (enthält <div id="bookando-vue-root">)
```

Vue wird pro Modul unter assets/vue/main.js mit #bookando-vue-root als Mountpoint geladen. Komponenten sind als *.vue in components/, Views in views/ organisiert. Der Modul-spezifische JS-Build landet in dist/main.js.

## 📦 White-Labeling

- Einstellungen zentral gespeichert (`white_label_name`, `white_label_logo`, etc.)
- Betreffen Menü, Logo, Farben, Texte
- Theme-Overrides für Logos, CSS möglich

## 📁 Templates & Theme Overrides

- Templates liegen in `src/Modules/<Modul>/Templates/`
- Werden über `bookando_get_template( $module, $template )` geladen
- Theme-Override möglich unter:
  `/wp-content/themes/<theme>/bookando/<modul>/<template>.php`

## 🔒 Rollen & Rechte

- Eigene Capabilities je Modul: `manage_bookando_<slug>`
- Beispiel: `manage_bookando_events`, `manage_bookando_bookings`
- Eigene Rolle `bookando_manager` denkbar mit angepasstem Rechteprofil

## 🔄 Modulverwaltung

Module müssen nicht manuell registriert werden. Der `ModuleManager` scannt alle vorhandenen `src/Modules/*/module.json` und lädt automatisch jene Module, die im Aktivierungsstatus (`bookando_active_modules`) eingetragen sind.

- Jede Modul-Konfiguration erfolgt über `module.json`
- Aktivierungsstatus wird in der WP-Option `bookando_active_modules` gespeichert
- Metadaten & Lizenzanforderungen werden über `ModuleManifest::fromSlug($slug)` ausgelesen
- Module können optional `install()` und `uninstall()` implementieren
- Eine UI zur Modulaktivierung kann unter `src/Core/Admin/Modules.php` (z. B. mit Vue) bereitgestellt werden

## 🔑 Lizenzverwaltung

Bookando verwendet ein modulares Lizenzsystem. Jedes Modul kann über einen Lizenzschlüssel aktiviert werden. Ein Schlüssel kann folgende Eigenschaften enthalten:

- aktivierte **Module** (z. B. `"customers"`, `"events"`)
- aktivierte **Features** (z. B. `"package_support"`, `"export_csv"`)
- zugehöriger **Tarif/Plan** (optional, z. B. `"starter"`, `"pro"`)

Die Lizenz wird im Backend unter `Einstellungen → Aktivierung` verwaltet und lokal gespeichert in `bookando_license_data`.

### Modulfreischaltung

- Modulaktivierung erfolgt über: `LicenseManager::isModuleAllowed($slug)`
- Gültige Lizenzen erlauben sofortige Aktivierung
- Optional kann eine **Gnadenfrist von 30 Tagen** nach Erstaktivierung gewährt werden

### Feature-Freischaltung

Einzelne Funktionen innerhalb eines Moduls können zusätzlich durch `"features_required"` gesperrt sein. Beispiel:

```json
{
  "slug": "services",
  "license_required": true,
  "features_required": ["package_support"]
}
```

Prüfung erfolgt via:

```php
LicenseManager::isFeatureEnabled('package_support')
```

## 📦 Lizenzfelder in module.json

Folgende zusätzliche Felder sind erlaubt:

```json
{
  "slug": "services",
  "license_required": true,
  "features_required": ["package_support", "export_csv"],
  "plan": "pro"
}
```

### Bedeutung:

| Feld               | Beschreibung                              |
|--------------------|-------------------------------------------|
| license_required   | ob Modul nur mit Lizenz nutzbar ist        |
| features_required  | Features, die zusätzlich lizenziert sein müssen |
| plan               | optionaler Hinweis für UI, z. B. "starter" |

Diese Daten werden vom `LicenseManager` automatisch ausgewertet.

## ⚙️ Lizenz-REST-API

Das Modul `settings` enthält einen REST-Endpunkt zur Lizenzprüfung:

| Route                          | Methode | Zweck                       |
|--------------------------------|---------|-----------------------------|
| `/bookando/v1/license`         | `GET`   | Lizenzstatus abrufen        |
| `/bookando/v1/license`         | `POST`  | Lizenzschlüssel speichern   |
| `/bookando/v1/license/deactivate` | `POST` | Lizenz löschen              |

Diese API wird automatisch erstellt, wenn das Modul `settings` über `generate-module.js` generiert wird.

## 🧠 Lizenz-Logik

Wird zentral über `Bookando\Core\Licensing\LicenseManager` gesteuert:

| Methode                              | Zweck                              |
|--------------------------------------|------------------------------------|
| `isModuleAllowed($slug)`             | Modul aktiv/erlaubt? (inkl. Gnadenzeit) |
| `hasValidLicenseFor($slug)`          | Modul in Lizenz enthalten?         |
| `isFeatureEnabled($feature)`         | Feature lizenziert?                |
| `getLicenseKey()`                    | Aktiver Lizenzschlüssel            |
| `verifyRemote($key)`                 | Remote-Validierung via API         |
| `setLicenseData($data)`              | Lizenzdaten speichern              |

## ⚙️ Modul-Generator: Lizenz-Felder

Die `generate-module.js` fragt beim Erstellen interaktiv ab:

- Lizenzpflicht: ja/nein
- erforderliche Features
- Tabs (z. B. für Einstellungen)
- Optionale REST-API für `settings`

Die daraus generierte `module.json` enthält automatisch:

- `license_required`
- `features_required`
- `dependencies`
- `visible`, `always_active`

## 📦 Beispielausgabe mit Lizenz:

```bash
❓ Modul-Slug: services
❓ Lizenzpflichtig: Ja
❓ Erforderliche Features: package_support, export_csv

✅ Modul "services" erfolgreich erstellt unter src/Modules/services
```

## 🔐 Lizenzserver (optional)

Für automatische Schlüsselprüfung wird empfohlen:

- eigener Endpoint z. B. `https://lizenz.bookando.ch/api/check`
- erwartet `license_key` + `site_url`
- gibt JSON mit `modules`, `features`, `plan` zurück

Diese Struktur erlaubt vollständige Automatisierung ohne manuellen Aufwand bei neuen Kunden.

## 🛠 Modul-Generator (generate-module.js)

Zur schnellen Erstellung neuer Module enthält das Plugin ein CLI-Skript:

```bash
node scripts/generate-module.js
```

Du wirst interaktiv nach dem Modulnamen, Anzeigenamen, Lizenzstatus und Sichtbarkeit gefragt. Das Skript erzeugt automatisch:

- Verzeichnisstruktur in `src/Modules/<slug>/`
- Starter-Dateien: `Module.php`, `Model.php`, `Admin.php`, `Api.php`, `assets/vue/`, etc.
- Eine vollständige `module.json` auf Basis deiner Eingaben

Beispielausgabe:

```bash
❓ Modul-Slug: customers
❓ Anzeigename: Kundenverwaltung
❓ Lizenzpflichtig: Nein
❓ Sichtbar im Admin: Ja

✅ Modul "customers" erfolgreich erstellt unter src/Modules/customers
```

👉 Für automatisierte Builds wird das Modul durch den `ModuleManager` automatisch erkannt, sobald `module.json` vorliegt.

## 🧪 Testing & Veröffentlichung

- Unit Tests mit WP_Mock (später optional)
- Sicherheitsüberprüfung: Code linting, nonce/cap check
- Deployment via GitHub + optional ZIP-Build (WP Directory konform)

## 📘 Für GPT & Entwickler

Alle GPT-Antworten müssen sich an diese Datei halten. Erweiterungsvorschläge sind **explizit erlaubt**, sofern sie den bestehenden Aufbau respektieren. Falls ein Modul, eine Methode oder ein Pfad nicht dokumentiert ist: Nachfrage stellen.

---

© Bookando Plugin Architektur · Version 1.0
