# Partnerhub Modul

## Übersicht

Das Partnerhub-Modul ermöglicht es, Dienstleistungen und Kurse mit anderen Berufskollegen zu teilen und DSGVO-konform Kundendaten auszutauschen.

## Hauptfunktionen

### 1. Partnerverwaltung
- Zentrale Verwaltung aller Partner
- Vertragsdaten (AVV - Auftragsverarbeitungsvertrag)
- API-Authentifizierung (API-Keys, Webhooks)
- Provisions-Konfiguration
- Datenspeicherungs-Einstellungen

### 2. Listings-Mapping
- Verknüpfung lokaler Services/Events mit Remote-Listings
- Bidirektionale Synchronisation
- Override-Optionen (Titel, Beschreibung, Preis)
- Sync-Status-Tracking
- Fehlerbehandlung

### 3. Preis- & Verfügbarkeitsregeln
- **Preisregeln**:
  - Fester Preis
  - Prozentuale Aufschläge/Rabatte
  - Min/Max-Preise
- **Verfügbarkeitsregeln**:
  - Zeitpuffer vor/nach Buchungen
  - Maximale Buchungen pro Tag/Woche
  - Genehmigungs-Workflow
  - Wochentags- und Zeitbeschränkungen
- **Blackout-Regeln**: Komplette Sperrung bestimmter Zeiträume

### 4. Feed-Export
- **Formate**: JSON, XML, iCal, CSV
- **Filterung**: Nach Typ, Kategorien, Standorten
- **Sicherheit**:
  - Access-Tokens
  - IP-Whitelist
  - Rate-Limiting
- **Statistiken**: Zugriffs-Tracking

### 5. Provisions-Tracking
- Automatische Provisions-Berechnung
- Transaktions-Verlauf
- Provisions-Reports nach Zeitraum
- Offene Zahlungen
- Währungs-Unterstützung

### 6. DSGVO-konformer Datenaustausch

#### Rechtsgrundlagen
- **Art. 6 Abs. 1 lit. a DSGVO**: Einwilligung
- **Art. 28 DSGVO**: Auftragsverarbeitungsvertrag (AVV)
- **Schweizer DSG**: Vollständige Kompatibilität

#### Einwilligungs-System
1. **Explizite Einwilligung**: Kunde muss aktiv zustimmen
2. **Zweckbindung**: Jede Einwilligung hat einen spezifischen Zweck
3. **Datenkategorien**: Genau definiert welche Daten geteilt werden
4. **Zeitliche Begrenzung**: Automatische Ablaufdaten
5. **Widerrufbarkeit**: Jederzeit widerrufbar mit Löschfolgen
6. **Nachweisbarkeit**: IP, User-Agent, Zeitstempel

#### Daten-Kategorien
- `name`: Name des Kunden
- `email`: E-Mail-Adresse
- `phone`: Telefonnummer
- `address`: Postanschrift
- `birth_date`: Geburtsdatum
- `student_id`: Schüler-/Kundennummer
- Weitere nach Bedarf erweiterbar

#### Zwecke (Purpose)
- `booking`: Buchung von Dienstleistungen
- `student_card`: Nutzung einer Schülerkarte
- `course_enrollment`: Kursanmeldung
- `event_participation`: Event-Teilnahme
- `other`: Sonstige (mit Beschreibung)

#### Datenspeicherung & Löschung
- Konfigurierbare Aufbewahrungsfrist (Standard: 365 Tage)
- Automatische Löschung nach Zweckerfüllung
- Lösch-Cron-Job läuft täglich
- Bestätigung der Löschung beim Partner

### 7. Vollständiger Audit-Trail
Alle Aktionen werden protokolliert:
- Partner-Erstellung/Änderung/Löschung
- Datenweitergabe an Partner
- Einwilligungen erteilt/widerrufen
- Mapping-Änderungen
- Feed-Zugriffe

**DSGVO Art. 30 Konformität**: Verarbeitungsverzeichnis

## Datenbankschema

### Tabellen
1. `wp_bookando_partners` - Partner-Stammdaten
2. `wp_bookando_partner_mappings` - Listings-Verknüpfungen
3. `wp_bookando_partner_rules` - Preis- & Verfügbarkeitsregeln
4. `wp_bookando_partner_feeds` - Feed-Konfigurationen
5. `wp_bookando_partner_transactions` - Provisions-Tracking
6. `wp_bookando_partner_consents` - Einwilligungen (DSGVO)
7. `wp_bookando_partner_audit_logs` - Audit-Trail (immutable)
8. `wp_bookando_partner_data_shares` - Tracking geteilter Daten

Alle Tabellen unterstützen Multi-Tenancy via `tenant_id`.

## REST API

### Endpoints

#### Partner
- `GET /wp-json/bookando/v1/partnerhub/partners` - Liste aller Partner
- `POST /wp-json/bookando/v1/partnerhub/partners` - Partner erstellen
- `GET /wp-json/bookando/v1/partnerhub/partners/{id}` - Partner abrufen
- `PUT /wp-json/bookando/v1/partnerhub/partners/{id}` - Partner aktualisieren
- `DELETE /wp-json/bookando/v1/partnerhub/partners/{id}` - Partner löschen

#### Mappings
- `GET /wp-json/bookando/v1/partnerhub/mappings` - Liste aller Mappings
- `POST /wp-json/bookando/v1/partnerhub/mappings` - Mapping erstellen
- `POST /wp-json/bookando/v1/partnerhub/mappings/{id}/sync` - Synchronisation starten

#### Regeln
- `GET /wp-json/bookando/v1/partnerhub/rules` - Liste aller Regeln
- `POST /wp-json/bookando/v1/partnerhub/rules` - Regel erstellen
- `PUT /wp-json/bookando/v1/partnerhub/rules/{id}` - Regel aktualisieren
- `DELETE /wp-json/bookando/v1/partnerhub/rules/{id}` - Regel löschen

#### Feeds
- `GET /wp-json/bookando/v1/partnerhub/feeds` - Liste aller Feeds
- `POST /wp-json/bookando/v1/partnerhub/feeds` - Feed erstellen
- `POST /wp-json/bookando/v1/partnerhub/feeds/{id}/regenerate-token` - Token erneuern

**Öffentlicher Feed-Zugriff** (mit Token):
```
GET /wp-json/bookando/v1/partnerhub/feed/{slug}?token={access_token}
```
Oder mit Header:
```
GET /wp-json/bookando/v1/partnerhub/feed/{slug}
X-API-Key: {access_token}
```

#### Transaktionen
- `GET /wp-json/bookando/v1/partnerhub/transactions` - Liste aller Transaktionen
- `GET /wp-json/bookando/v1/partnerhub/partners/{id}/revenue` - Provisions-Report

#### Einwilligungen
- `GET /wp-json/bookando/v1/partnerhub/consents` - Liste aller Einwilligungen
- `POST /wp-json/bookando/v1/partnerhub/consents` - Einwilligung anfordern
- `POST /wp-json/bookando/v1/partnerhub/consents/{id}/grant` - Einwilligung erteilen
- `POST /wp-json/bookando/v1/partnerhub/consents/{id}/revoke` - Einwilligung widerrufen
- `POST /wp-json/bookando/v1/partnerhub/data-share` - Kundendaten teilen

#### Audit-Logs
- `GET /wp-json/bookando/v1/partnerhub/audit-logs` - Audit-Logs abrufen

#### Dashboard
- `GET /wp-json/bookando/v1/partnerhub/dashboard` - Dashboard-Statistiken

## Berechtigungen

- `view_bookando_partnerhub` - Partner Hub im Backend öffnen
- `view_bookando_partners` - Partner anzeigen (Legacy-Alias für Abwärtskompatibilität)
- `manage_bookando_partnerhub` - Partner Hub verwalten (erforderlich für SPA-Assets)
- `manage_bookando_partners` - Partner verwalten (Legacy-Alias für Abwärtskompatibilität)
- `manage_bookando_partner_mappings` - Mappings verwalten
- `manage_bookando_partner_rules` - Regeln verwalten
- `manage_bookando_partner_feeds` - Feeds verwalten
- `view_bookando_partner_transactions` - Transaktionen anzeigen
- `manage_bookando_partner_transactions` - Transaktionen verwalten
- `view_bookando_partner_consents` - Einwilligungen anzeigen
- `manage_bookando_partner_consents` - Einwilligungen verwalten (DSGVO-sensitiv)
- `view_bookando_partner_audit_logs` - Audit-Logs anzeigen (nur Admins)

## Verwendung

### Partner erstellen

```php
$partner_model = new \Bookando\Modules\Partnerhub\Models\PartnerModel();

$partner_id = $partner_model->insert([
    'name' => 'Yoga Studio Zürich',
    'company_name' => 'Yoga Studio Zürich GmbH',
    'website_url' => 'https://yogastudio-zh.ch',
    'contact_email' => 'info@yogastudio-zh.ch',
    'contract_type' => 'avv',
    'contract_signed_at' => '2025-01-01 00:00:00',
    'data_processing_agreement_accepted' => true,
    'commission_type' => 'percentage',
    'commission_value' => 10.00,
    'status' => 'active',
]);
```

### Einwilligung einholen

```php
$consent_service = new \Bookando\Modules\Partnerhub\Services\ConsentService();

$consent_id = $consent_service->request_consent(
    customer_id: 123,
    partner_id: $partner_id,
    purpose: 'student_card',
    data_categories: ['name', 'email', 'birth_date', 'student_id'],
    options: [
        'valid_until' => '2025-12-31 23:59:59',
        'purpose_description' => 'Nutzung der Schülerkarte bei Partnerstandorten',
    ]
);

// Kunde erteilt Einwilligung
$consent_service->grant_consent($consent_id);
```

### Kundendaten teilen

```php
// Nur nach Einwilligung möglich!
$consent_service->share_customer_data(
    customer_id: 123,
    partner_id: $partner_id,
    purpose: 'student_card',
    data: [
        'name' => 'Max Mustermann',
        'email' => 'max@example.com',
        'birth_date' => '1990-01-01',
        'student_id' => 'ST-123456',
    ],
    booking_id: 456
);
```

### Feed erstellen

```php
$feed_model = new \Bookando\Modules\Partnerhub\Models\PartnerFeedModel();

$feed_id = $feed_model->insert([
    'partner_id' => $partner_id,
    'feed_type' => 'json',
    'feed_name' => 'Yoga Kurse für Partner',
    'feed_slug' => 'yoga-kurse',
    'include_types' => json_encode(['event', 'service']),
    'status' => 'active',
]);

$feed = $feed_model->get_by_id($feed_id);
echo "Feed-URL: " . rest_url("bookando/v1/partnerhub/feed/{$feed->feed_slug}");
echo "Token: {$feed->access_token}";
```

## Shortcodes

### Einwilligungs-Formular

```
[bookando_partner_consent partner_id="1" purpose="student_card"]
```

Zeigt ein Formular zur Einwilligung für Kunden an.

## Cron-Jobs

### Täglich ausgeführt

1. **Einwilligungen ablaufen lassen**: `bookando_partnerhub_expire_consents`
   - Markiert abgelaufene Einwilligungen als `expired`

2. **Datenlöschungen verarbeiten**: `bookando_partnerhub_process_deletions`
   - Löscht Daten bei Partnern nach Widerruf oder Zweckerfüllung

## DSGVO-Checkliste

✅ **Einwilligung**: Explizite, dokumentierte Einwilligung erforderlich
✅ **Zweckbindung**: Jede Einwilligung hat einen spezifischen Zweck
✅ **Datenminimierung**: Nur notwendige Kategorien werden geteilt
✅ **Transparenz**: Kunde sieht genau, welche Daten wohin gehen
✅ **Widerrufbarkeit**: Jederzeit widerrufbar
✅ **Speicherbegrenzung**: Automatische Löschung nach Aufbewahrungsfrist
✅ **Auftragsverarbeitung**: AVV-Pflicht für alle Partner
✅ **Nachweisbarkeit**: Vollständiger Audit-Trail (Art. 30 DSGVO)
✅ **Auskunftsrecht**: Kunden können ihre Logs exportieren (Art. 15 DSGVO)
✅ **Löschrecht**: Automatische Löschung nach Widerruf (Art. 17 DSGVO)

## Schweizer Datenschutzgesetz

Das Modul ist vollständig kompatibel mit dem neuen Schweizer Datenschutzgesetz (revDSG):

- ✅ Einwilligung dokumentiert
- ✅ Informationspflicht erfüllt (Consent-Text)
- ✅ Bearbeitungsverzeichnis (Audit-Logs)
- ✅ Datensicherheit (API-Keys, IP-Whitelist)
- ✅ Auftragsverarbeiter-Regelung (AVV-Pflicht)

## Sicherheit

- API-Key-basierte Authentifizierung
- IP-Whitelist für Feeds
- Rate-Limiting
- Verschlüsselung von API-Secrets (bcrypt)
- Immutable Audit-Logs
- SHA256-Hashing geteilter Daten

## Support & Erweiterung

Das Modul ist vollständig erweiterbar:

- Filter-Hooks für Custom-Validierung
- Action-Hooks für Custom-Sync-Logik
- Event-System für Notifications
- Custom-Feeds über `FeedService::format_feed()`

## Lizenz

Professional Plan erforderlich
Features: `partnerhub`, `partner_network`, `data_sharing`
