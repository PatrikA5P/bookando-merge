# 🔍 Vollständige Diagnose-Anleitung

Sie haben bereits alles Richtige getan:
- ✅ Git Pull durchgeführt
- ✅ Module sind in DB aktiv
- ✅ Hard Reload gemacht
- ✅ Keine JavaScript-Fehler
- ✅ Plugin deaktiviert/reaktiviert

Aber die Module funktionieren immer noch nicht. Jetzt machen wir eine **tiefgehende System-Analyse**.

## 🚀 Schritt-für-Schritt Diagnose

### Schritt 1: Diagnose-Script installieren

```powershell
# 1. Kopieren Sie das Diagnose-Script
Copy-Item "bookando-full-diagnosis.php" "C:\Users\User\Local Sites\bookando-site\app\public\wp-content\mu-plugins\bookando-full-diagnosis.php"

# 2. Falls mu-plugins Ordner nicht existiert:
New-Item -ItemType Directory -Path "C:\Users\User\Local Sites\bookando-site\app\public\wp-content\mu-plugins" -Force
Copy-Item "bookando-full-diagnosis.php" "C:\Users\User\Local Sites\bookando-site\app\public\wp-content\mu-plugins\"
```

### Schritt 2: Debug-Logging aktivieren

In `wp-config.php` (falls noch nicht aktiviert):

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('BOOKANDO_DEBUG', true);
```

### Schritt 3: Debug-Log leeren

```powershell
# Altes Log löschen für saubere Analyse
Remove-Item "C:\Users\User\Local Sites\bookando-site\app\public\wp-content\debug.log" -ErrorAction SilentlyContinue
```

### Schritt 4: WordPress Admin öffnen

1. Öffnen Sie http://bookando-site.local/wp-admin
2. Navigieren Sie zu einer Bookando-Seite (z.B. Dashboard)
3. Versuchen Sie beide Module zu öffnen:
   - Workday (falls sichtbar)
   - Resources

### Schritt 5: Debug-Log analysieren

```powershell
# Log-Datei öffnen
notepad "C:\Users\User\Local Sites\bookando-site\app\public\wp-content\debug.log"

# Oder im Terminal anzeigen:
Get-Content "C:\Users\User\Local Sites\bookando-site\app\public\wp-content\debug.log" -Tail 500
```

### Schritt 6: Diagnose-Ergebnisse senden

**Kopieren Sie mir den GESAMTEN Diagnose-Block aus dem Log:**

```
═══════════════════════════════════════════════════════════
🔍 BOOKANDO MODUL-DIAGNOSE START: ...
═══════════════════════════════════════════════════════════
...
═══════════════════════════════════════════════════════════
🏁 DIAGNOSE ENDE
═══════════════════════════════════════════════════════════
```

## 📊 Was das Diagnose-Script prüft

### 1. Datenbank-Status
- ✓ Welche Module sind in `wp_bookando_module_states`?
- ✓ Welcher Status (active/inactive)?
- ✓ Wann wurden sie aktiviert?

### 2. WordPress-Option
- ✓ Ist `bookando_active_modules` korrekt gesetzt?
- ✓ Sind workday und resources enthalten?

### 3. Modul-Dateien
- ✓ Existiert `module.json`?
- ✓ Ist es gültiges JSON?
- ✓ Sind die wichtigen Felder gesetzt?
- ✓ Existiert `Module.php`?
- ✓ Existiert `Admin/Admin.php`?
- ✓ Existieren die gebauten Assets (`dist/*/main.js`)?

### 4. ModuleManager
- ✓ Werden Module gescannt?
- ✓ Werden Module geladen?
- ✓ Sind Module als "sichtbar" markiert?

### 5. Lizenz-Manager
- ✓ Ist das Modul erlaubt?
- ✓ Sind alle Features aktiviert?

### 6. Admin-Menü
- ✓ Ist das Bookando-Hauptmenü registriert?
- ✓ Sind die Modul-Submenüs registriert?

### 7. WordPress-Hooks
- ✓ Sind die Hooks korrekt registriert?
- ✓ Werden Assets enqueued?

## 🔎 Häufige Probleme & Lösungen

### Problem 1: Module werden nicht geladen

**Diagnose-Ausgabe zeigt:**
```
Geladene Module (0):
   ❌ KEINE Module geladen!
```

**Mögliche Ursachen:**
1. **Lizenz blockiert Module**
   - Lösung: Lizenz-Check deaktivieren (temporär für Debug)

2. **Feature-Flags fehlen**
   - Lösung: Feature-Flags in Lizenz aktivieren

3. **Dependencies fehlen**
   - Lösung: Prüfen welche Dependencies ein Modul braucht

**Fix:**
```php
// Temporär in wp-config.php:
define('BOOKANDO_BYPASS_LICENSE', true);
```

### Problem 2: Module geladen, aber Menü fehlt

**Diagnose-Ausgabe zeigt:**
```
Geladene Module (11):
   ✓ workday (Bookando\Modules\workday\Module)
...
Menü-Status:
   Workday Submenü: ✗ FEHLT
```

**Mögliche Ursachen:**
1. **Hook `bookando_register_module_menus` wird nicht gefeuert**
2. **Admin::register_menu() wird nicht aufgerufen**
3. **Capabilities fehlen**

**Fix:**
```php
// In src/modules/workday/Module.php prüfen:
$this->registerAdminHooks(function (): void {
    add_action('bookando_register_module_menus', [Admin::class, 'register_menu']);
});
```

### Problem 3: Menü da, Assets laden nicht

**Diagnose-Ausgabe zeigt:**
```
Workday Submenü: ✓ GEFUNDEN
...
Enqueued Bookando Scripts:
   (keine bookando-workday Scripts)
```

**Mögliche Ursachen:**
1. **enqueue_admin_assets() wird nicht aufgerufen**
2. **Assets sind am falschen Ort**
3. **Nonce-Problem verhindert Asset-Loading**

**Fix:**
```php
// In src/modules/workday/Module.php prüfen:
public function enqueue_admin_assets(): void
{
    $this->enqueue_module_assets();
}
```

### Problem 4: Assets werden geladen, Vue mountet nicht

**Browser-Konsole zeigt:**
```
[Bookando] Mountpoint #bookando-workday-root nicht gefunden
```

**Lösung:** Template-Datei prüfen:
```php
// src/modules/workday/Templates/admin-vue-container.php
$moduleSlug = 'workday';
$moduleData = [
    'rest_base' => 'workday',
];
require BOOKANDO_PLUGIN_DIR . 'src/Core/Admin/vue-container.php';
```

## 🛠️ Weitere Debugging-Tools

### Tool 1: SQL-Abfragen

```sql
-- Alle Module mit Details
SELECT slug, status, installed_at, activated_at, deactivated_at, last_error
FROM wp_bookando_module_states
ORDER BY slug;

-- Nur Problem-Module
SELECT slug, status, last_error
FROM wp_bookando_module_states
WHERE slug IN ('workday', 'resources');

-- Activity Log prüfen
SELECT logged_at, severity, context, message, module_slug
FROM wp_bookando_activity_log
WHERE module_slug IN ('workday', 'resources')
ORDER BY logged_at DESC
LIMIT 50;
```

### Tool 2: PHP-Schnelltest

```php
// In wp-content/mu-plugins/test-workday.php
<?php
add_action('init', function() {
    if (!class_exists('Bookando\Modules\workday\Module')) {
        error_log('❌ Workday Module Klasse existiert nicht!');
        return;
    }

    $reflection = new ReflectionClass('Bookando\Modules\workday\Module');
    error_log('✅ Workday Module Klasse: ' . $reflection->getFileName());

    if (!class_exists('Bookando\Modules\workday\Admin\Admin')) {
        error_log('❌ Workday Admin Klasse existiert nicht!');
        return;
    }

    $reflection = new ReflectionClass('Bookando\Modules\workday\Admin\Admin');
    error_log('✅ Workday Admin Klasse: ' . $reflection->getFileName());

    // Prüfe ob register_menu Methode existiert
    if (method_exists('Bookando\Modules\workday\Admin\Admin', 'register_menu')) {
        error_log('✅ register_menu Methode existiert');
    } else {
        error_log('❌ register_menu Methode fehlt!');
    }
}, 1);
```

### Tool 3: Browser-Konsole Checks

In der Browser-Konsole (F12), führen Sie aus:

```javascript
// 1. Prüfe ob BOOKANDO_VARS existiert
console.log('BOOKANDO_VARS:', window.BOOKANDO_VARS);

// 2. Prüfe ob Mount-Point existiert
console.log('Workday Mount:', document.querySelector('#bookando-workday-root'));
console.log('Resources Mount:', document.querySelector('#bookando-resources-root'));

// 3. Prüfe ob Vue geladen wurde
console.log('Vue:', typeof window.Vue !== 'undefined' ? 'geladen' : 'nicht geladen');

// 4. Prüfe ob Module-Scripts geladen wurden
const scripts = Array.from(document.querySelectorAll('script[src]'));
const bookandoScripts = scripts.filter(s => s.src.includes('bookando'));
console.log('Bookando Scripts:', bookandoScripts.map(s => s.src));
```

## 🎯 Nächste Schritte

**Basierend auf Ihren Diagnose-Ergebnissen:**

1. **Führen Sie das Diagnose-Script aus** (Schritt 1-5)
2. **Kopieren Sie mir den vollständigen Output**
3. Ich werde dann **genau sehen** wo das Problem liegt
4. **Zielgerichtete Lösung** erstellen

## 📋 Checkliste für die Diagnose

Bitte sammeln Sie folgende Informationen:

- [ ] Diagnose-Script Output aus debug.log
- [ ] Browser-Konsole Screenshot (F12)
- [ ] Network Tab Screenshot (F12 → Network)
- [ ] SQL-Abfrage Ergebnisse (Module States)
- [ ] `npm run validate:modules` Output (haben Sie schon ✓)

## 💡 Wichtige Erkenntnisse bisher

Aus Ihrem npm-validate Output:
- ✅ **workday hat keine Schema-Fehler mehr** (nur TODO-Warnungen)
- ✅ **resources hat keine Schema-Fehler**
- ❌ **partnerhub hat viele Schema-Fehler** (aber das ist nicht Ihr aktuelles Problem)

Aus Ihrem Debug-Log:
- ✅ **Nonce funktioniert** (VALID, age: 1)
- ✅ **Keine kritischen PHP-Fehler**
- ✅ **Settings werden geladen** (general.lang = 'de')
- ⚠️ **wp_queue_jobs Tabelle fehlt** (nicht kritisch, aber sollte behoben werden)

Das Problem liegt also höchstwahrscheinlich bei:
1. **Modul-Loading** (ModuleManager lädt die Module nicht)
2. **Menü-Registrierung** (Hooks werden nicht gefeuert)
3. **Lizenz/Features** (Module werden blockiert)

Die Diagnose wird uns genau zeigen, welches!
