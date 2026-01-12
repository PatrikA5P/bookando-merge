# Modul-Aktivierungsanleitung

## Problem
Die Module "Workday" und "Resources" sind nicht sichtbar oder zeigen nur "Lade Modul..." an.

## Ursache
Die Module sind vermutlich nicht in der Datenbank als aktiv markiert, obwohl:
- Die `module.json` Dateien korrekt sind
- Die Assets (JavaScript-Dateien) gebaut wurden
- Der Code funktionsfähig ist

## Lösung 1: PHP-Script ausführen (Empfohlen)

Führen Sie das Aktivierungsscript aus:

```bash
cd /path/to/wordpress/wp-content/plugins/bookando
php activate-workday-resources.php
```

Das Script wird:
1. Prüfen ob die Module bereits aktiv sind
2. Die Module in der Datenbank aktivieren
3. Die `bookando_active_modules` Option aktualisieren
4. Die Installation verifizieren

## Lösung 2: Über WordPress Admin

1. Gehen Sie zu **Bookando > Einstellungen > Module**
2. Aktivieren Sie die Module "Workday" und "Resources"
3. Klicken Sie auf "Speichern"
4. Laden Sie die Seite neu

## Lösung 3: Über SQL (Fortgeschritten)

Falls die obigen Methoden nicht funktionieren, können Sie die SQL-Befehle direkt in phpMyAdmin oder der MySQL-Konsole ausführen:

```sql
-- Workday aktivieren
INSERT INTO wp_bookando_module_states (slug, status, installed_at, activated_at, updated_at)
VALUES ('workday', 'active', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE status = 'active', activated_at = NOW(), updated_at = NOW();

-- Resources aktivieren
INSERT INTO wp_bookando_module_states (slug, status, installed_at, activated_at, updated_at)
VALUES ('resources', 'active', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE status = 'active', activated_at = NOW(), updated_at = NOW();
```

Danach WordPress-Option aktualisieren (PHP):

```php
$active = get_option('bookando_active_modules', []);
if (!in_array('workday', $active)) $active[] = 'workday';
if (!in_array('resources', $active)) $active[] = 'resources';
update_option('bookando_active_modules', $active);
```

## Verifikation

Nach der Aktivierung sollten Sie folgendes sehen:

### Workday-Modul
- **Sichtbar im Admin-Menü**: Ja, als "Arbeitstag" unter dem Hauptmenü
- **Icon**: Kalender-Symbol (dashicons-calendar-alt)
- **Position**: Menüposition 50

### Resources-Modul
- **Sichtbar im Admin-Menü**: Ja, als "Ressourcen"
- **Lädt korrekt**: Keine "Lade Modul..."-Meldung mehr
- **Funktionsfähig**: Vue-App wird geladen und ist interaktiv

## Technische Details

### Geänderte Dateien (Commit d26148b)

1. **src/modules/workday/module.json**
   - Vollständige Modul-Konfiguration hinzugefügt
   - `visible: true` gesetzt
   - Mehrsprachige Namen und Beschreibungen
   - Menu-Icon und Position definiert

2. **dist/workday/main.js** (28.7 KB)
   - Vue-App Assets gebaut

3. **dist/resources/main.js** (14.2 KB)
   - Vue-App Assets gebaut

### Wie Module geladen werden

1. `ModuleManager::loadModules()` lädt alle aktiven Module
2. Module werden aus `wp_bookando_module_states` gelesen (Status = 'active')
3. Fallback auf `bookando_active_modules` WordPress-Option
4. Module werden nur geladen wenn:
   - Lizenz erlaubt (`LicenseManager::isModuleAllowed()`)
   - Alle Feature-Flags erfüllt sind
   - Alle Dependencies vorhanden sind

## Häufige Probleme

### Modul ist aktiviert, aber nicht sichtbar
- Prüfen Sie `module.json`: `"visible": true` muss gesetzt sein
- Prüfen Sie Lizenz: Modul muss im aktuellen Plan enthalten sein
- WordPress-Cache leeren

### "Lade Modul..." bleibt stehen
- Assets fehlen: `npm run build` ausführen
- Nonce-Probleme: Debug-Log prüfen
- JavaScript-Fehler: Browser-Konsole öffnen

### Module werden nach Plugin-Update deaktiviert
- Dies ist normal wenn keine legacy active modules existieren
- Einfach re-aktivieren über das Aktivierungsscript

## Support

Bei weiteren Problemen:
1. Browser-Konsole auf Fehler prüfen (F12)
2. WordPress Debug-Log aktivieren: `define('WP_DEBUG_LOG', true);`
3. Bookando Activity-Log prüfen: `wp_bookando_activity_log` Tabelle
