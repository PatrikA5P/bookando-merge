# Schritt-für-Schritt Anleitung: Workday & Resources Module aktivieren

## Problem-Zusammenfassung

**Workday:** Modul wird gar nicht im Admin-Menü angezeigt
**Resources:** Modul wird angezeigt, bleibt aber bei "Lade Modul..." hängen

## Ursachen-Analyse

### 1. Workday module.json hatte Validierungsfehler
❌ Das `capabilities` Feld ist nicht erlaubt in module.json
✅ **BEHOBEN** - capabilities Feld wurde entfernt

### 2. Module sind nicht in der Datenbank aktiviert
Die Module müssen in `wp_bookando_module_states` aktiviert werden

### 3. Assets sind korrekt gebaut
✅ `dist/workday/main.js` (28.74 kB)
✅ `dist/resources/main.js` (14.23 kB)

## Lösung: Schritt-für-Schritt

### Schritt 1: Repository aktualisieren

```powershell
cd "C:\Users\User\Local Sites\bookando-site\app\public\wp-content\plugins\bookando"
git pull origin claude/fix-module-loading-01BJx2KQiYb71JfEkbhoBZkE
```

### Schritt 2: Module in der Datenbank aktivieren

**Option A: Via PHP-Script (Empfohlen)**

```powershell
php activate-workday-resources.php
```

**Option B: Direkt in der Datenbank (phpMyAdmin oder Adminer)**

```sql
-- 1. Workday aktivieren
INSERT INTO wp_bookando_module_states (slug, status, installed_at, activated_at, updated_at)
VALUES ('workday', 'active', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
    status = 'active',
    activated_at = NOW(),
    updated_at = NOW();

-- 2. Resources aktivieren
INSERT INTO wp_bookando_module_states (slug, status, installed_at, activated_at, updated_at)
VALUES ('resources', 'active', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
    status = 'active',
    activated_at = NOW(),
    updated_at = NOW();

-- 3. Prüfen ob Module aktiv sind
SELECT slug, status, activated_at
FROM wp_bookando_module_states
WHERE slug IN ('workday', 'resources');
```

**Option C: Via WordPress Admin**

Falls Bookando eine Modul-Verwaltungsseite hat:
1. Gehen Sie zu **Bookando > Einstellungen > Module**
2. Aktivieren Sie "Workday" und "Resources"
3. Speichern

### Schritt 3: WordPress-Option aktualisieren

Die `bookando_active_modules` Option muss ebenfalls aktualisiert werden.

**Via phpMyAdmin:**

```sql
-- Aktuelle Module auslesen
SELECT option_value FROM wp_options WHERE option_name = 'bookando_active_modules';

-- Manuell aktualisieren (passen Sie die Liste an, falls schon Module aktiv sind)
UPDATE wp_options
SET option_value = 'a:11:{i:0;s:7:"academy";i:1;s:12:"appointments";i:2;s:9:"customers";i:3;s:9:"employees";i:4;s:7:"finance";i:5;s:6:"offers";i:6;s:10:"partnerhub";i:7;s:9:"resources";i:8;s:8:"settings";i:9;s:5:"tools";i:10;s:7:"workday";}'
WHERE option_name = 'bookando_active_modules';
```

**Via WP-CLI (falls verfügbar):**

```powershell
wp option patch update bookando_active_modules workday --format=json
wp option patch update bookando_active_modules resources --format=json
```

### Schritt 4: Cache leeren

```powershell
# WordPress Object Cache (falls Redis/Memcached aktiv)
wp cache flush

# Oder im WordPress Admin:
# - Alle Caching-Plugins deaktivieren/Cache leeren
# - Browser-Cache leeren (Strg+Shift+R oder Strg+F5)
```

### Schritt 5: Seite neu laden

1. Öffnen Sie den WordPress Admin neu
2. Drücken Sie **Strg+Shift+R** (Hard Reload)
3. Prüfen Sie das Menü

## Erwartetes Ergebnis

### Workday-Modul
✅ Erscheint im Admin-Menü als **"Arbeitstag"**
✅ Icon: Kalender (dashicons-calendar-alt)
✅ Menüposition: 50
✅ Vue-App lädt korrekt

### Resources-Modul
✅ Erscheint im Admin-Menü als **"Ressourcen"**
✅ Kein "Lade Modul..." mehr
✅ Vue-App lädt und ist interaktiv

## Debugging: Falls es immer noch nicht funktioniert

### 1. Browser-Konsole prüfen (F12)

Öffnen Sie die Browser-Entwicklertools und prüfen Sie auf JavaScript-Fehler:

```
Console -> Nach Fehlern suchen (rot markiert)
```

Häufige Fehler:
- `BOOKANDO_VARS is not defined` → Assets nicht geladen
- `Cannot mount on selector...` → Mount-Point fehlt
- Nonce-Fehler → Cache-Problem

### 2. WordPress Debug-Log aktivieren

In `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('BOOKANDO_DEBUG', true);
```

Log-Datei prüfen:
```
wp-content/debug.log
```

### 3. Modul-Status in Datenbank prüfen

```sql
-- Alle Module mit Status anzeigen
SELECT slug, status, activated_at, installed_at
FROM wp_bookando_module_states
ORDER BY slug;

-- Nur workday und resources
SELECT slug, status, activated_at
FROM wp_bookando_module_states
WHERE slug IN ('workday', 'resources');
```

### 4. Assets prüfen

```powershell
# Prüfen ob Assets existieren
Test-Path "dist\workday\main.js"
Test-Path "dist\resources\main.js"

# Asset-Größen prüfen
Get-ChildItem dist\workday\main.js | Select-Object Name, Length
Get-ChildItem dist\resources\main.js | Select-Object Name, Length
```

### 5. Modul-Validierung

```powershell
npm run validate:modules
```

Sollte keine Fehler mehr für workday zeigen!

### 6. Module manuell neu laden

Falls die Aktivierung nicht funktioniert, können Sie die Module neu installieren:

```sql
-- Module deaktivieren
UPDATE wp_bookando_module_states SET status = 'inactive' WHERE slug IN ('workday', 'resources');

-- Dann über PHP-Script oder SQL wieder aktivieren (siehe Schritt 2)
```

## Technische Details

### Wie Module geladen werden

1. **Scan:** `ModuleManager::scanModules()` findet alle `*/module.json` Dateien
2. **Filter:** Nur Module mit `status = 'active'` in `wp_bookando_module_states`
3. **Lizenz:** `LicenseManager::isModuleAllowed()` prüft Lizenz
4. **Features:** Prüfung ob alle `features_required` erfüllt sind
5. **Dependencies:** Prüfung ob alle `dependencies` aktiv sind
6. **Init:** `Module::boot()` wird aufgerufen
7. **Assets:** `enqueue_admin_assets()` lädt JavaScript/CSS

### Warum "Lade Modul..." erscheint

Der Text "Lade Modul..." ist der **Placeholder** aus `vue-container.php` (Zeile 149):

```php
$placeholder = isset($moduleData['placeholder']) && is_string($moduleData['placeholder'])
    ? $moduleData['placeholder']
    : __('Lade Modul...', 'bookando');
```

Dieser wird ersetzt, sobald die Vue-App erfolgreich mounted. Falls er stehen bleibt:
- **JavaScript lädt nicht** → Assets fehlen oder 404
- **Vue-App mountet nicht** → JavaScript-Fehler
- **Mount-Punkt nicht gefunden** → HTML-Element fehlt

### Module-JSON Schema

Die erlaubten Felder in `module.json`:

```json
{
  "slug": "string (required)",
  "plan": "starter|professional|enterprise|custom",
  "version": "string",
  "tenant_required": "boolean",
  "license_required": "boolean",
  "features_required": ["array of strings"],
  "group": "string",
  "is_saas": "boolean",
  "has_admin": "boolean",
  "supports_webhook": "boolean",
  "supports_offline": "boolean",
  "supports_calendar": "boolean",
  "name": { "default": "...", "de": "...", "en": "...", "fr": "...", "it": "..." },
  "alias": { "de": "...", "en": "...", "fr": "...", "it": "..." },
  "description": { "default": "...", "de": "...", "en": "...", "fr": "...", "it": "..." },
  "visible": "boolean",
  "menu_icon": "string (dashicons-*)",
  "menu_position": "number",
  "dependencies": ["array of module slugs"],
  "tabs": [],
  "is_submodule": "boolean",
  "parent_module": "string|null"
}
```

**NICHT erlaubt:**
- ❌ `capabilities` → gehört in `Capabilities.php`
- ❌ `namespace` → wird automatisch generiert
- ❌ `requires` → use `dependencies` stattdessen

## Support

Bei weiteren Problemen:

1. **Debug-Log prüfen:** `wp-content/debug.log`
2. **Browser-Konsole:** JavaScript-Fehler suchen
3. **Datenbank prüfen:** Module-States Tabelle
4. **Nonce-Debug:** Wurde im Log ausgegeben

### Nützliche SQL-Queries

```sql
-- Alle aktiven Module
SELECT slug FROM wp_bookando_module_states WHERE status = 'active';

-- Modul-Metadaten (aus JSON)
SELECT slug,
       JSON_EXTRACT(meta, '$.visible') as visible,
       JSON_EXTRACT(meta, '$.menu_position') as position
FROM (
    SELECT 'workday' as slug,
           LOAD_FILE('C:/Users/User/Local Sites/bookando-site/app/public/wp-content/plugins/bookando/src/modules/workday/module.json') as meta
) tmp;
```

## Checkliste

Nach allen Schritten sollten Sie folgendes haben:

- [ ] Git Pull durchgeführt (aktualisierte module.json)
- [ ] Module in Datenbank aktiviert (status = 'active')
- [ ] WordPress-Option aktualisiert (bookando_active_modules)
- [ ] Cache geleert (WordPress + Browser)
- [ ] Seite neu geladen (Strg+Shift+R)
- [ ] Workday erscheint im Menü
- [ ] Resources lädt korrekt (kein "Lade Modul...")
- [ ] Keine JavaScript-Fehler in der Konsole
- [ ] `npm run validate:modules` zeigt keine Fehler für workday
