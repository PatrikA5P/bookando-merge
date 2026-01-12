# Workforce Management Implementation

## Übersicht

Diese Implementierung erweitert das Bookando-System um ein vollständiges Workforce Management mit Zeiterfassung und Urlaubsverwaltung.

## ✅ Implementierte Features

### 1. Datenbank-Schema

#### Neue Tabellen

**`wp_bookando_time_entries`**
- Zeiterfassung für Mitarbeiter
- Clock-in/Clock-out Tracking
- Automatische Pausenberechnung
- Support für Timer, manuelle Einträge und Importe
- Tenant-Isolation

**`wp_bookando_active_timers`**
- Aktive Timer-Tracking
- Ein Timer pro Mitarbeiter
- Automatische Bereinigung bei Clock-out

#### Erweiterte Tabellen

**`wp_bookando_employees_days_off`** (erweitert)
- Neue Felder für Urlaubsantrags-Workflow:
  - `request_status`: ENUM('approved','pending','rejected','cancelled')
  - `requested_by`: Wer den Antrag gestellt hat
  - `requested_at`: Zeitstempel des Antrags
  - `reviewed_by`: Genehmiger/Ablehner
  - `reviewed_at`: Zeitstempel der Entscheidung
  - `rejection_reason`: Grund bei Ablehnung

### 2. Backend-Services

#### WorkforceTimeTrackingService.php
- ✅ Integration mit Employees-Modul
- ✅ Automatische Employee-Liste (mit Status-Filter)
- ✅ Clock-In/Out mit aktiven Timern
- ✅ Automatische Pausenberechnung (DE Arbeitsrecht)
- ✅ Manuelle Zeiteinträge
- ✅ Wochen-/Monatsstatistiken
- ✅ Multi-Tenant Support

**Kern-Methoden:**
```php
getActiveEmployees($status)     // Mitarbeiter-Liste laden
getState($userId, $limit)       // Aktueller Status mit Timern & Einträgen
clockIn($userId, $data)         // Timer starten
clockOut($userId, $data)        // Timer stoppen & Eintrag erstellen
createManualEntry($userId, $data) // Manuelle Erfassung
```

**Automatische Pausen (DE Arbeitsrecht):**
- \> 6 Stunden: 30 Minuten Pause
- \> 9 Stunden: 45 Minuten Pause

#### VacationRequestService.php
- ✅ Urlaubsantrags-Erstellung
- ✅ Genehmigungs-Workflow (pending → approved/rejected)
- ✅ Stornierung durch Mitarbeiter
- ✅ Überschneidungs-Prüfung
- ✅ Resturlaubs-Berechnung
- ✅ Jahres-Übersicht

**Kern-Methoden:**
```php
getRequests($filters)                    // Alle Anträge (gefiltert)
createRequest($userId, $data)            // Neuen Antrag erstellen
approveRequest($requestId, $reviewerId)  // Antrag genehmigen
rejectRequest($requestId, $reviewerId, $reason) // Antrag ablehnen
cancelRequest($requestId, $userId)       // Antrag stornieren
calculateRemainingDays($userId, $year, $entitlement) // Resturlaub
getEmployeeOverview($userId, $year)      // Mitarbeiter-Übersicht
```

### 3. REST API Endpoints

**Basis:** `/wp-json/bookando/v1/tools/workforce/`

#### Zeiterfassung
```
GET  /time-tracking              // Status, Employees, Timer, Einträge
GET  /time-tracking/employees    // Mitarbeiter-Liste (filter: status)
POST /time-tracking/clock-in     // Timer starten
POST /time-tracking/clock-out    // Timer stoppen
POST /time-tracking/manual       // Manuelle Erfassung
```

#### Urlaubsanträge
```
GET  /vacation-requests                      // Alle Anträge
POST /vacation-requests                      // Neuer Antrag
POST /vacation-requests/{id}/approve         // Genehmigen
POST /vacation-requests/{id}/reject          // Ablehnen
POST /vacation-requests/{id}/cancel          // Stornieren
GET  /vacation-requests/overview/{user_id}   // Mitarbeiter-Übersicht
```

#### Kalender (Vorbereitet)
```
GET  /calendar  // Kombinierte Ansicht (Arbeitstage, Urlaub, Buchungen, etc.)
```

### 4. Frontend-Komponenten

#### WorkforceTab.vue
- ✅ Mitarbeiter-Auswahl mit Status-Filter
- ✅ Großer Clock-In/Out Button (mobile-optimiert)
- ✅ Aktive Timer-Anzeige mit Live-Dauer
- ✅ Wochen-/Monatsstatistiken
- ✅ Letzte Zeiteinträge (Tabelle)
- ✅ Urlaubsantrags-Formular
- ✅ Offene Anträge mit Genehmigen/Ablehnen

**Integration in ToolsView.vue:**
- Workforce als erster Tab hinzugefügt
- Icon: 'briefcase'

### 5. Übersetzungen (DE/EN)

Vollständige i18n-Unterstützung:
- `mod.tools.tabs.workforce`
- `mod.tools.workforce.*` (40+ Übersetzungsschlüssel)

## 📊 Datenfluss

```
┌─────────────┐
│ WorkforceTab│
│   (Vue)     │
└──────┬──────┘
       │
       ↓ REST API
┌──────────────────┐
│  RestHandler     │
│  (API Router)    │
└──────┬───────────┘
       │
       ↓
┌─────────────────────────────┐
│  WorkforceTimeTrackingService │
│  VacationRequestService     │
└──────┬──────────────────────┘
       │
       ↓ SQL
┌─────────────────┐
│  wp_bookando_*  │
│  (Database)     │
└─────────────────┘
```

## 🔒 Sicherheit & Best Practices

### Implementiert:
- ✅ Nonce-Validierung (WP REST API)
- ✅ Input-Sanitierung (sanitize_text_field, sanitize_key)
- ✅ SQL Prepared Statements ($wpdb->prepare)
- ✅ Tenant-Isolation (tenant_id in allen Tabellen)
- ✅ Status-Validierung (nur pending → approved/rejected)
- ✅ Ownership-Checks (nur Requester kann stornieren)
- ✅ Überschneidungs-Prüfung (verhindert doppelte Urlaube)

### Fehlerbehandlung:
- Try-Catch in allen API-Endpoints
- Validierte Fehler-Responses (400, 404, 500)
- Detaillierte Fehlermeldungen im Frontend

## 🧪 Testing-Checkliste

### Backend
- [ ] Datenbank-Tabellen erstellt (wp-admin → Plugins → Deaktivieren → Aktivieren)
- [ ] Clock-In API: `/wp-json/bookando/v1/tools/workforce/time-tracking/clock-in`
- [ ] Clock-Out API mit Pausenberechnung
- [ ] Urlaubsantrag erstellen & genehmigen
- [ ] Überschneidungs-Prüfung testen

### Frontend
- [ ] Mitarbeiter-Auswahl funktioniert
- [ ] Clock-In Button disabled wenn Timer läuft
- [ ] Timer-Anzeige aktualisiert sich
- [ ] Urlaubsformular validiert Datumsbereich
- [ ] Genehmigungs-Buttons funktionieren
- [ ] Statistiken werden korrekt berechnet

### Integration
- [ ] Employee-Daten werden korrekt geladen
- [ ] Multi-Tenant Isolation (Tenant A sieht nicht Tenant B)
- [ ] Berechtigungen (nur Manager können genehmigen)

## 🚀 Migration & Deployment

### Datenbank-Migration
```php
// Automatisch bei Plugin-Aktivierung via Installer.php
// Manuelle Trigger-Möglichkeit:
do_action('bookando_install_core_tables');
```

### Alte Daten migrieren (optional)
Falls alte WP-Options-basierte Zeiterfassung existiert:
```php
// Alte Daten aus wp_options holen
$old_entries = get_option('bookando_time_tracking_entries', []);

// In neue Tabelle migrieren
foreach ($old_entries as $entry) {
    WorkforceTimeTrackingService::createManualEntry(
        $entry['employee_id'],
        [
            'clock_in_at' => $entry['clock_in'],
            'clock_out_at' => $entry['clock_out'],
            'notes' => $entry['notes'],
        ]
    );
}
```

## 📈 Erweiterungsmöglichkeiten

### Nächste Schritte:
1. **Grafischer Kalender**
   - FullCalendar.js Integration
   - Farbcodierung (Arbeitstage, Urlaub, Buchungen, Blockierte Zeiten)
   - Drag & Drop für Schicht-Planung

2. **Export-Funktionen**
   - PDF-Export für Lohnabrechnungen
   - CSV für Excel/DATEV
   - XLSX mit Formeln

3. **Mobile App API**
   - JWT-Authentifizierung
   - Push-Benachrichtigungen
   - Offline-Support

4. **Erweiterte Analytics**
   - Überstunden-Tracking
   - Kostenstellen-Zuordnung
   - Projekt-Zeiterfassung

5. **Self-Service Portal**
   - Separate Route für Mitarbeiter
   - Eigene Zeiten einsehen
   - Urlaubsanträge stellen
   - Dienstplan ansehen

## 📝 Anmerkungen

### Architektur-Entscheidungen:
1. **Warum keine separate Tabelle für vacation_requests?**
   - `employees_days_off` wurde erweitert um bestehende Funktionalität zu bewahren
   - Rückwärtskompatibel (default: request_status='approved')
   - Einfachere Queries (ein JOIN statt zwei)

2. **Warum WorkforceTimeTrackingService statt bestehenden TimeTrackingService erweitern?**
   - Saubere Trennung (alter Service nutzt WP Options)
   - Neue Implementierung nutzt echte Datenbank-Tabellen
   - Bessere Performance & Skalierbarkeit
   - Migration kann schrittweise erfolgen

3. **Warum Workforce-Tab statt eigenes Modul?**
   - Schnellere Entwicklung
   - Bessere UX (alles an einem Ort)
   - Einfachere Wartung
   - Kann später noch ausgelagert werden

## 🔗 Abhängigkeiten

### Backend:
- WordPress 5.0+
- PHP 8.0+
- Bookando Core
- Bookando Employees Modul

### Frontend:
- Vue 3
- vue-i18n
- Bookando UI Components

## 📞 Support

Bei Fragen oder Problemen:
1. Prüfe Fehler-Logs: `wp-content/debug.log`
2. Browser-Konsole für Frontend-Fehler
3. REST API direkt testen mit Postman/cURL
4. Datenbank-Schema überprüfen mit phpMyAdmin

---

**Version:** 1.0.0
**Datum:** 2025-01-17
**Autor:** Claude (Anthropic)
