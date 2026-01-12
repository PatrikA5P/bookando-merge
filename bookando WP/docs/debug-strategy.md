# 🔍 Bookando Debug-Strategie: Fehler finden und beheben

**Version:** 1.0.0
**Datum:** 2025-11-10
**Autor:** Bookando Development Team

---

## 📋 Inhaltsverzeichnis

1. [Einführung](#einführung)
2. [Debug-System aktivieren](#debug-system-aktivieren)
3. [Nonce-Probleme debuggen](#nonce-probleme-debuggen)
4. [Asset-Loading-Probleme debuggen](#asset-loading-probleme-debuggen)
5. [Allgemeine Debug-Strategie](#allgemeine-debug-strategie)
6. [Debug-Log interpretieren](#debug-log-interpretieren)
7. [Häufige Probleme & Lösungen](#häufige-probleme--lösungen)

---

## 1. EINFÜHRUNG

Das Bookando-Plugin verfügt über ein **umfassendes Debug-Logging-System**, das Ihnen hilft:
- ✅ Nonce-Redirect-Loops zu identifizieren
- ✅ Asset-Loading-Probleme zu finden
- ✅ Performance-Bottlenecks zu erkennen
- ✅ Request-Flows zu verfolgen

**Wichtig:** Debug-Logging sollte **NUR in Development-Umgebungen** aktiviert werden!

---

## 2. DEBUG-SYSTEM AKTIVIEREN

### Schritt 1: WordPress Debug aktivieren

In `wp-config.php`:

```php
// WordPress Debug aktivieren
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Bookando Debug aktivieren
define('BOOKANDO_DEBUG', true);
```

### Schritt 2: Log-Datei finden

Die Logs werden geschrieben nach:
```
wp-content/debug.log
```

### Schritt 3: Live-Monitoring (optional)

```bash
# Terminal 1: Logs live verfolgen
tail -f wp-content/debug.log | grep "BOOKANDO"

# Terminal 2: Nur Nonce-Logs
tail -f wp-content/debug.log | grep "🔐 NONCE"

# Terminal 3: Nur Asset-Logs
tail -f wp-content/debug.log | grep "📦 ASSET"
```

---

## 3. NONCE-PROBLEME DEBUGGEN

### Problem: "Diese Seite funktioniert im Moment nicht. Sie zu oft umgeleitet."

#### Schritt 1: Debug aktivieren

```php
// In wp-config.php
define('BOOKANDO_DEBUG', true);
```

#### Schritt 2: Modul öffnen

Navigieren Sie zu einem betroffenen Modul (z.B. Einstellungen).

#### Schritt 3: Logs analysieren

Öffnen Sie `wp-content/debug.log` und suchen Sie nach:

```
[BOOKANDO-abc12345] 🔐 NONCE: ensureModuleNonce_start
[BOOKANDO-abc12345] 🔐 NONCE: nonce_read
[BOOKANDO-abc12345] 🔐 NONCE: nonce_verify
[BOOKANDO-abc12345] 🔐 NONCE: nonce_invalid_redirect_needed
```

#### Schritt 4: Nonce-Details prüfen

**Beispiel Log-Eintrag:**
```
[BOOKANDO-abc12345] 🔐 14:23:45.123 | Menu.php:125 | NONCE: nonce_read | {
  "action": "bookando_module_assets_settings",
  "nonce_empty": false,
  "nonce_length": 10,
  "nonce_preview": "abc1234567..."
}
```

**Prüfen Sie:**
- ✅ `nonce_empty`: Sollte `false` sein
- ✅ `nonce_length`: Sollte ~10 Zeichen sein
- ✅ `action`: Sollte `bookando_module_assets_{module_slug}` sein

#### Schritt 5: Verifikations-Ergebnis prüfen

**Beispiel Log-Eintrag:**
```
[BOOKANDO-abc12345] 🔐 14:23:45.124 | Menu.php:139 | NONCE: nonce_verify | {
  "action": "bookando_module_assets_settings",
  "result": "INVALID",
  "nonce_age": false
}
```

**Wenn `result: "INVALID"`:**
- ❌ Der Nonce ist beschädigt oder abgelaufen
- ❌ Der Action-String stimmt nicht überein
- ❌ Der Nonce wurde nicht korrekt generiert

#### Schritt 6: Redirect-URL prüfen

**Beispiel Log-Eintrag:**
```
[BOOKANDO-abc12345] 🔐 14:23:45.125 | Menu.php:202 | NONCE: redirect_executing | {
  "redirect_url": "/wp-admin/admin.php?page=bookando_settings&_wpnonce=NEW_NONCE",
  "action": "bookando_module_assets_settings"
}
```

**Prüfen Sie:**
- ✅ Die URL enthält `_wpnonce` Parameter
- ✅ Der Action-String ist korrekt

### Nonce temporär deaktivieren (Debugging)

**NUR FÜR DEBUGGING:**

```php
// In wp-config.php
define('BOOKANDO_DISABLE_MODULE_NONCE', true);
```

**Warnung:** Dies deaktiviert die Nonce-Validierung vollständig! Nur in Development-Umgebungen verwenden!

---

## 4. ASSET-LOADING-PROBLEME DEBUGGEN

### Problem: "Lade Modul..." bleibt stehen

#### Schritt 1: Debug aktivieren

```php
// In wp-config.php
define('BOOKANDO_DEBUG', true);
```

#### Schritt 2: Logs analysieren

Suchen Sie nach Asset-Logs:

```
[BOOKANDO-abc12345] 📦 ASSET: nonce_read_for_validation
[BOOKANDO-abc12345] 📦 ASSET: nonce_verification_result
```

#### Schritt 3: Verifikations-Ergebnis prüfen

**Beispiel Log-Eintrag:**
```
[BOOKANDO-abc12345] 📦 14:23:46.200 | BaseModule.php:560 | ASSET: nonce_verification_result | {
  "slug": "settings",
  "action": "bookando_module_assets_settings",
  "nonce_preview": "abc1234567...",
  "verify_result": "INVALID",
  "status": "BLOCKED - Assets will not load"
}
```

**Wenn `verify_result: "INVALID"` und `status: "BLOCKED"`:**
- ❌ Assets werden NICHT geladen
- ❌ Vue-App kann nicht starten
- ❌ "Lade Modul..." bleibt sichtbar

#### Schritt 4: Nonce-Mismatch identifizieren

Vergleichen Sie die Action-Strings zwischen:

1. **ensureModuleNonce() (Redirect-Nonce):**
   ```
   "action": "bookando_module_assets_settings"
   ```

2. **hasValidModuleNonce() (Asset-Nonce):**
   ```
   "action": "bookando_module_assets_settings"
   ```

**Sie sollten IDENTISCH sein!**

Wenn nicht:
- ❌ Bug in `getSlug()` Methode
- ❌ Bug in `sanitize_key()` Anwendung
- ❌ Unterschiedliche Module-Slug-Bestimmung

---

## 5. ALLGEMEINE DEBUG-STRATEGIE

### 5.1 Systematisches Debugging

```
1. PROBLEM IDENTIFIZIEREN
   └─ Was funktioniert nicht?
   └─ Welche Fehlermeldung erscheint?
   └─ Welche Module sind betroffen?

2. DEBUG AKTIVIEREN
   └─ define('BOOKANDO_DEBUG', true);
   └─ define('WP_DEBUG_LOG', true);

3. PROBLEM REPRODUZIEREN
   └─ Betroffene Seite aufrufen
   └─ Aktion ausführen (z.B. Modul öffnen)

4. LOGS ANALYSIEREN
   └─ wp-content/debug.log öffnen
   └─ Nach BOOKANDO-Session-ID filtern
   └─ Relevante Log-Einträge finden

5. ROOT CAUSE IDENTIFIZIEREN
   └─ Welcher Schritt schlägt fehl?
   └─ Welche Werte sind unerwartet?
   └─ Gibt es Timing-Probleme?

6. FIX IMPLEMENTIEREN
   └─ Code-Änderung vornehmen
   └─ Fix testen
   └─ Debug-Logs prüfen

7. DEBUG DEAKTIVIEREN
   └─ define('BOOKANDO_DEBUG', false);
   └─ Logs löschen (optional)
```

### 5.2 Debug-Checkliste

Bevor Sie Hilfe suchen, prüfen Sie:

- [ ] `WP_DEBUG` und `BOOKANDO_DEBUG` aktiviert?
- [ ] `wp-content/debug.log` existiert und ist beschreibbar?
- [ ] Problem reproduzierbar?
- [ ] Vollständige Session-Logs gesammelt?
- [ ] Action-Strings verglichen?
- [ ] Nonce-Werte überprüft?
- [ ] Browser-Console geprüft? (F12 → Console)
- [ ] Network-Tab geprüft? (F12 → Network)

---

## 6. DEBUG-LOG INTERPRETIEREN

### Log-Format

```
[BOOKANDO-{session_id}] {emoji} {time}.{ms} | {file}:{line} | {message} | {context_json}
```

**Beispiel:**
```
[BOOKANDO-abc12345] 🔐 14:23:45.123 | Menu.php:125 | NONCE: nonce_read | {"action":"bookando_module_assets_settings"}
```

**Komponenten:**
- `BOOKANDO-abc12345`: Session-ID (eindeutig pro Request)
- `🔐`: Emoji-Kategorie (🔐 Nonce, 📦 Asset, ⏱️ Timer, etc.)
- `14:23:45.123`: Timestamp mit Millisekunden
- `Menu.php:125`: Datei und Zeilennummer
- `NONCE: nonce_read`: Log-Nachricht
- `{...}`: Kontext-Daten als JSON

### Emoji-Kategorien

| Emoji | Kategorie | Bedeutung |
|-------|-----------|-----------|
| 🚀 | SESSION START | Start einer Debug-Session |
| 🏁 | SESSION END | Ende einer Debug-Session |
| 🔐 | NONCE | Nonce-bezogene Logs |
| 📦 | ASSET | Asset-Loading-Logs |
| ⏱️ | TIMER | Performance-Timer |
| 🔍 | TEST | Test-Verifikationen |
| ℹ️ | INFO | Informationen |
| ⚠️ | WARNING | Warnungen |
| ❌ | ERROR | Fehler |

### Session-Logs filtern

```bash
# Alle Logs einer Session anzeigen
grep "BOOKANDO-abc12345" wp-content/debug.log

# Nur Nonce-Logs einer Session
grep "BOOKANDO-abc12345" wp-content/debug.log | grep "🔐"

# Nur Fehler einer Session
grep "BOOKANDO-abc12345" wp-content/debug.log | grep "❌"
```

---

## 7. HÄUFIGE PROBLEME & LÖSUNGEN

### Problem 1: Redirect-Loop

**Symptome:**
```
Browser-Fehler: "Diese Seite funktioniert im Moment nicht. Sie zu oft umgeleitet."
```

**Ursachen:**
1. ❌ Nonce wird beschädigt durch `sanitize_text_field()`
2. ❌ Action-String stimmt nicht überein
3. ❌ Nonce abgelaufen (nach 12/24 Stunden)

**Lösung:**

```php
// In src/Core/Helpers.php prüfen:
function bookando_read_sanitized_request(string $key, bool $isNonce = false): string {
    // ...
    if ($isNonce) {
        return $value; // ✅ KEIN sanitize_text_field()!
    }
    // ...
}

// In Menu.php und BaseModule.php prüfen:
$nonce = bookando_read_sanitized_request('_wpnonce', true); // ✅ true!
```

**Debug-Logs:**
```
❌ Wenn result: "INVALID" bei JEDEM Request → Nonce beschädigt
✅ Wenn result: "VALID" beim 2. Request → Fix funktioniert
```

---

### Problem 2: "Lade Modul..." bleibt stehen

**Symptome:**
```
Vue-App lädt nicht, "Lade Modul..." bleibt sichtbar
```

**Ursachen:**
1. ❌ `hasValidModuleNonce()` gibt `false` zurück
2. ❌ Assets werden nicht geladen
3. ❌ Vue-App startet nicht

**Lösung:**

```php
// Option 1: Nonce temporär deaktivieren (Debugging)
// In wp-config.php:
define('BOOKANDO_DISABLE_MODULE_NONCE', true);

// Option 2: Nonce-Fix verifizieren
// In BaseModule.php prüfen:
$nonce = $this->readRequestString('_wpnonce', true); // ✅ true!
```

**Debug-Logs:**
```
📦 ASSET: nonce_verification_result | {
  "verify_result": "INVALID",
  "status": "BLOCKED - Assets will not load"
}
```

**Wenn BLOCKED:**
- ❌ Assets werden nicht geladen
- ✅ Nonce-Fix anwenden oder temporär deaktivieren

---

### Problem 3: Action-String Mismatch

**Symptome:**
```
Redirect funktioniert, aber Assets laden nicht
```

**Ursachen:**
1. ❌ Unterschiedliche Slug-Berechnung in `ensureModuleNonce()` und `hasValidModuleNonce()`
2. ❌ `sanitize_key()` wird inkonsistent angewendet

**Lösung:**

```php
// In Menu.php Zeile 95:
$moduleSlug = sanitize_key($moduleSlug); // z.B. "settings"

// In BaseModule.php muss getSlug() denselben Wert liefern!
protected function getSlug(): string {
    $parts = explode('\\', static::class);
    return isset($parts[2]) ? strtolower($parts[2]) : 'unknown';
}
```

**Debug-Logs vergleichen:**
```
🔐 Menu.php | "action": "bookando_module_assets_settings"
📦 BaseModule.php | "action": "bookando_module_assets_settings"
                              ↑
                              Muss IDENTISCH sein!
```

---

### Problem 4: Nonce abgelaufen

**Symptome:**
```
Nach 12-24 Stunden funktionieren Module nicht mehr
```

**Ursachen:**
1. ❌ WordPress Nonces sind auf 12/24 Stunden begrenzt
2. ❌ Nonce wird nicht erneuert bei Page-Refresh

**Lösung:**

WordPress Nonces haben standardmäßig eine Lebensdauer von:
- **12 Stunden** (1. Tick)
- **24 Stunden** (2. Tick)

**Workaround:**
```php
// Option 1: Nonce-Lifetime erhöhen (nicht empfohlen)
add_filter('nonce_life', function() {
    return 48 * HOUR_IN_SECONDS; // 48 Stunden
});

// Option 2: Nonce bei jedem Admin-Request erneuern (empfohlen)
// Dies passiert automatisch durch ensureModuleNonce()
```

**Debug-Logs:**
```
🔐 NONCE: nonce_verify | {
  "result": "INVALID",
  "nonce_age": false  // ← 0 = expired, 1 = valid, 2 = old but valid
}
```

---

### Problem 5: Browser-Cache-Problem

**Symptome:**
```
Nach Code-Änderungen funktioniert Nonce nicht
```

**Ursachen:**
1. ❌ Browser cached alte JavaScript-Dateien
2. ❌ Browser cached alte Admin-Seiten

**Lösung:**

```bash
# Option 1: Hard-Refresh im Browser
# Chrome/Firefox: Ctrl + Shift + R (Windows) oder Cmd + Shift + R (Mac)

# Option 2: Browser-Cache komplett leeren
# Chrome: F12 → Network → Disable cache (bei geöffneten DevTools)

# Option 3: Private/Incognito-Modus testen
```

---

## 8. ADVANCED DEBUG-TECHNIKEN

### 8.1 Custom Debug-Points

Sie können eigene Debug-Logs hinzufügen:

```php
use Bookando\Core\Service\DebugLogger;

// In Ihrer Funktion:
DebugLogger::init();

// Einfaches Logging
DebugLogger::log('Meine Debug-Nachricht', [
    'variable' => $myVar,
    'status' => 'testing',
]);

// Nonce-spezifisches Logging
DebugLogger::logNonce('custom_check', [
    'my_nonce' => $nonce,
    'my_action' => $action,
]);

// Asset-spezifisches Logging
DebugLogger::logAsset('custom_load', 'my-handle', [
    'src' => $src,
    'deps' => $deps,
]);

// Performance-Timer
DebugLogger::startTimer('my_operation');
// ... Code ausführen
$duration = DebugLogger::stopTimer('my_operation');
```

### 8.2 Nonce-Verifikations-Test

```php
use Bookando\Core\Service\DebugLogger;

DebugLogger::init();

$nonce = $_REQUEST['_wpnonce'] ?? '';

// Teste mehrere Actions
DebugLogger::testNonceVerification($nonce, [
    'bookando_module_assets_settings',
    'bookando_module_assets_customers',
    'bookando_module_assets_employees',
]);

// Log-Output:
// 🔍 NONCE VERIFICATION TEST | {
//   "nonce": "abc123...",
//   "results": {
//     "bookando_module_assets_settings": "✅ VALID",
//     "bookando_module_assets_customers": "❌ INVALID",
//     "bookando_module_assets_employees": "❌ INVALID"
//   }
// }
```

### 8.3 Request-Details loggen

```php
use Bookando\Core\Service\DebugLogger;

DebugLogger::init();
DebugLogger::logRequest();
DebugLogger::logScreen();

// Log-Output:
// 📥 REQUEST DETAILS | {
//   "url": "/wp-admin/admin.php?page=bookando_settings",
//   "method": "GET",
//   "get_params": ["page", "_wpnonce"],
//   ...
// }
//
// 🖥️ SCREEN INFO | {
//   "id": "bookando_page_bookando_settings",
//   "base": "bookando_page_bookando_settings",
//   ...
// }
```

---

## 9. DEBUG-LOGS AUSWERTEN

### Session-Zusammenfassung

Am Ende jeder Debug-Session wird automatisch eine Zusammenfassung geloggt:

```
[BOOKANDO-abc12345] 🏁 14:23:47.890 | DebugLogger.php:280 | DEBUG SESSION END | {
  "total_logs": 42,
  "duration_ms": 234.56,
  "by_level": {
    "NONCE": 12,
    "ASSET": 8,
    "INFO": 18,
    "WARNING": 3,
    "ERROR": 1
  }
}
```

**Interpretation:**
- `total_logs`: Anzahl aller Log-Einträge
- `duration_ms`: Gesamtdauer der Session
- `by_level`: Verteilung nach Log-Level

---

## 10. PRODUCTION-SICHERHEIT

### ⚠️ WICHTIG: Debug NIEMALS in Production aktivieren!

**Risiken:**
- ❌ Sensible Daten in Logs (Nonces, User-IDs, etc.)
- ❌ Performance-Einbußen (Logging verlangsamt Requests)
- ❌ Große Log-Dateien (können Server füllen)
- ❌ Sicherheitsrisiko (Debug-Logs enthalten Systeminformationen)

**Best Practices:**
```php
// In wp-config.php (Production):
define('BOOKANDO_DEBUG', false); // ✅ IMMER false in Production!
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

// In wp-config.php (Development):
define('BOOKANDO_DEBUG', true);  // ✅ Nur in Dev-Umgebungen
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Debug-Logs löschen

```bash
# Logs löschen
rm wp-content/debug.log

# Oder in WordPress:
# Tools → Site Health → Logs löschen
```

---

## 11. SUPPORT & HILFE

Wenn Sie trotz Debug-Logs das Problem nicht lösen können:

### Vorbereitung für Support-Anfrage

1. **Debug aktivieren** und Problem reproduzieren
2. **Vollständige Session-Logs** kopieren (alle Logs mit derselben Session-ID)
3. **Screenshots** der Fehlermeldung
4. **Browser-Console-Logs** (F12 → Console)
5. **Network-Logs** (F12 → Network → Failed Requests)

### Support-Ticket erstellen

```markdown
**Problem:**
Beschreibung des Problems

**Debug-Logs:**
[BOOKANDO-abc12345] ... (komplette Session)

**Environment:**
- WordPress: 6.5.2
- PHP: 8.2
- Bookando: 1.0.0
- Browser: Chrome 120

**Steps to Reproduce:**
1. ...
2. ...
3. ...
```

---

## 12. ZUSAMMENFASSUNG

### Quick-Start-Guide

```php
// 1. In wp-config.php:
define('BOOKANDO_DEBUG', true);
define('WP_DEBUG_LOG', true);

// 2. Problem reproduzieren

// 3. Logs prüfen:
tail -f wp-content/debug.log | grep "BOOKANDO"

// 4. Session-ID finden und filtern:
grep "BOOKANDO-abc12345" wp-content/debug.log

// 5. Problem analysieren und beheben

// 6. Debug deaktivieren:
define('BOOKANDO_DEBUG', false);
```

### Debug-Levels

| Level | Verwendung | Performance-Impact |
|-------|------------|-------------------|
| **BOOKANDO_DEBUG** | Vollständiges Debug-Logging | Hoch |
| **WP_DEBUG_LOG** | WordPress Core-Logs | Mittel |
| **BOOKANDO_DISABLE_MODULE_NONCE** | Nonce-Bypass (Testing only!) | Niedrig |

---

**Happy Debugging!** 🚀

Bei Fragen oder Problemen kontaktieren Sie bitte das Bookando Development Team.
