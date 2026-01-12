
# 🔑 Bookando – Licensing & SaaS Guide (v 2025-05-21, "Academy"-Edition)

Dieses Dokument ergänzt **Bookando‑Plugin‑Struktur v 2.4** um alle Details zur Lizenz‑ und SaaS‑Abwicklung.  
*(File‑Reference in Hauptdoku: `docs/Licensing.md`)*

## 1. Überblick & Key Facts

- **Lizenzmodell:** Granular, API-basiert, cloud-fähig (SaaS + klassisch)
- **Lizenzschlüssel:** Universell als **UUID** oder random Token (Copy/Paste)
- **Pläne:** starter / pro / academy / enterprise  
- **Module & Features:** Strikte Trennung, alle zentral in `license-features.php`
- **Mandantenfähigkeit:** Tenant-ID, SaaS und Multi-Account-Ready
- **Verifizierung:** Lokal & Remote, mit Fallback, Gnadenfrist


Bookando verwendet ein **modulares Lizenzmodell** – jede Installation (WordPress oder SaaS‑Mandant) wird mit einem Lizenzschlüssel aktiviert.  
Der Schlüssel definiert:
- **Plan** (Starter, Pro, Academy, Enterprise)
- **freigeschaltete Module**
- **freigeschaltete Features**
- Gültigkeit & Remote‑Verifizierung

Die Lizenzprüfung läuft wahlweise **lokal** (Offline‑Key) oder **remote** (Lizenzserver API).

## 2. Lizenzschlüssel‑Payload

```json
{
  "license_key": "550e8400-e29b-41d4-a716-446655440000",
  "site_url": "https://kunde.example",
  "tenant_id": "fahrschule_mustermann",
  "plan": "pro",
  "modules": ["customers", "bookings", "events"],
  "features": ["export_csv", "package_support"],
  "issued_at": "2025-01-10T12:00:00Z",
  "expires_at": "2026-01-10T12:00:00Z"
}
```

**Hinweis 1:** Der Lizenzschlüssel wird entweder als **UUID** oder als **Base58-String** ausgegeben, um Sicherheit und Eindeutigkeit zu gewährleisten.
**Hinweis 2:** Lizenzschlüssel-Datenstruktur optional erweiterbar um zusätzliche Metadaten (z.B. seats, limits, revoked).


## 3. Plan‑Staffelung (aktuell)

| Plan        | Enthaltene Module (Slugs)                                    | Features                                 | Zielgruppe           |
|-------------|--------------------------------------------------------------|------------------------------------------|----------------------|
| **starter** | Siehe license-features.php                                   | Siehe license-features.php               | Einzelanbieter, KMU  |
| **pro**     | Alle Starter-Module + Module wie "analytics", "refunds"      | Pro-Features (z.B. PDF-Export, API-Write)| Studios, Ketten      |
| **academy** | Pro + Bildung/Coaching (education_cards, learning_progress…) | Academy-Features (z.B. Q&A, Lernpläne)   | Fahrschule, Academy  |
| **enterprise** | Alle Module + "enterprise-only" Features                  | White-Label, Multi-Tenant etc.           | Franchise, SaaS      |

→ Alle Module/Features sind granular in **license-features.php** dokumentiert.

## 4. Speicherung in WordPress

```php
update_option( 'bookando_license_data', [
  'key'         => $key,
  'plan'        => 'pro',
  'modules'     => [...],
  'features'    => [...],
  'verified_at' => current_time('mysql')
] );
```

## 5. LicenseManager – wichtigste APIs

| Methode                     | Zweck                                  |
|-----------------------------|----------------------------------------|
| `getLicenseData()`          | Rohdaten‑Array                         |
| `isModuleAllowed($slug)`    | true/false (inkl. Gnadenfrist)         |
| `isFeatureEnabled($feature)`| true/false                             |
| `getPlan()`                 | `starter / pro / academy / enterprise` |
| `verifyRemote($key)`        | Remote-Lizenzprüfung                   |
| `setLicenseData($data)`     | Update Lizenzdaten                     |

Gnadenfrist: 30 Tage nach Ablauf `expires_at`.

## 6. Remote‑Verifizierung

### POST-Endpoint

```
POST https://license.bookando.ch/api/check
```

**Request‑Body**

```json
{ "license_key": "<key>", "site_url": "https://example.com", "tenant_id": "abc" }
```

**Response**

```json
{
  "valid": true,
  "plan": "pro",
  "modules": ["customers","events"],
  "features": ["export_csv"],
  "expires_at": "2026-01-10T12:00:00Z"
}
```

### Cron‑Job

Die automatische Lizenzprüfung erfolgt täglich. Bei Nichterreichbarkeit des Lizenzservers wird die zuletzt gültige Lizenz verwendet (Grace-Period von 30 Tagen). Danach erfolgt die automatische Sperrung.

```php
if ( wp_next_scheduled('bookando_license_verify') === false ) {
    wp_schedule_event( time(), 'daily', 'bookando_license_verify' );
}
```

**Hook:**

```php
add_action( 'bookando_license_verify', function () {
    $key = LicenseManager::getLicenseKey();
    if ( $key ) {
        LicenseManager::verifyRemote( $key );
    }
});
```

## 7. SaaS‑Betrieb & Multi‑Tenant

| Element           | Beschreibung                             |
|-------------------|------------------------------------------|
| Mandant           | Sub‑Site oder Tenant‑ID                  |
| Tenant‑Bindung    | Lizenz kann auf tenant_id restricted sein |
| API-Routen        | `/wp-json/bookando/v1/... ?tenant_id=xyz`|
| Tenant-Switch     | `LicenseManager::forTenant($tenantId)`   |

## 8. Modul‑ & Feature‑Abhängigkeiten

- Jede `module.json` enthält:
  - `"license_required": true`
  - `"plan": "pro"`
  - `"features_required": ["export_csv"]`

## 9. Lizenzprüfung in REST & UI

REST 403 mit `feature_unavailable`. Lizenzprüfung Backend-seitig immer durchführen!

**REST-API Beispiel:**

```php
register_rest_route('/bookando/v1/customers', '/list', [
  'methods' => 'GET',
  'permission_callback' => function() {
    return current_user_can('manage_bookando_customers') && LicenseManager::isModuleAllowed('customers');
  },
  'callback' => function() {
    if (!LicenseManager::isModuleAllowed('customers')) {
      return new WP_REST_Response(['error' => 'feature_unavailable'], 403);
    }
    // Normale Antwort hier generieren...
  }
]);
```

## 10. UI‑Indikatoren

| Status       | Icon | Farbe    |
|--------------|------|----------|
| aktiv        | 🔓   | #16a34a  |
| Gnadenfrist  | ⏳   | #d97706  |
| gesperrt     | 🔐   | #dc2626  |
| abgelaufen   | ❌   | #dc2626  |

## 11. Logging, Fehler & Testing

- **Logging:** Tabelle `wp_bookando_license_log` (siehe SQL-Schema-Ergänzung) loggt Ergebnis, Zeit, Fehlermeldung.
- **Empfohlen:** Nightly-Report der letzten 24h; 403-Statistiken zur Früherkennung.
- **Fehlerhandling:** REST/AJAX bei Lizenzfehler immer 403 `feature_unavailable` ausgeben, Admin-Notice bei Remote-Error
- **Testing:** Alle Szenarien abdecken (Ablauf, Block, Tenant, Downgrade, Gnadenfrist)
- **Fallback:** Lizenzdaten lokal cachen, Grace-Period von 30 Tagen bei Remote-API-Ausfall

## 12. Test‑Szenarien

| Szenario                | Erwartung     |
|-------------------------|--------------|
| Key gültig              | Zugriff OK   |
| Modul fehlt             | REST 403     |
| Key abgelaufen (<30 T)  | Gnadenfrist  |
| Key abgelaufen (>30 T)  | gesperrt     |
| Lizenzserver down       | Fallback (Grace-Period) |

## 13. Modul/Feature-Übersicht

→ **Siehe aktuelle Datei `license-features.php` für Mapping-Details!**

## 14. Best Practice: Was jeder Entwickler beachten MUSS

- REST/AJAX/Backend: Lizenz immer checken (Modul + Feature)
- Frontend: UX-Feedback ja, Sicherheit nein (nie nur JS prüfen!)
- Automatische Lizenzprüfung: Cron immer aktivieren!
- Mandant/Kunde: Tenant immer mitchecken in SaaS-Umgebung!
- Export/Import: Module/Features nie "hardcoden", immer Mapping nutzen!

## 15. Weiterführende Dokumente

- Bookando-Plugin-Struktur.md (Technik & Architektur)
- license-features.php (Plan-Feature-Mapping, zentrale Freischaltung)
- generate-module.js (Scaffolding & Konsistenz)
- REST API Reference (auto-generiert)