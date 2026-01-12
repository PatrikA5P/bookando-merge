# REST-API Routing

Die Bookando-Module registrieren ihre REST-Endpunkte zentral im jeweiligen
`Api`-Namespace. Alle Endpunkte folgen der Namenskonvention

```
/wp-json/bookando/v1/{modul-slug}/...
```

Dabei entspricht `{modul-slug}` dem Slug aus dem Modulverzeichnis (z. B.
`appointments`, `customers`). Jeder Eintrag in `Module::registerRoutes()` muss
ein `register_rest_route()` für seine Endpunkte setzen und die Zugriffsprüfung
über `RestModuleGuard::for('{slug}')` oder ein eigenes Callback absichern.

> **Verbindliche Leitplanken**
> - Antworten erfolgen konsequent über `Bookando\Core\Api\Response` (kein
>   direkter `WP_REST_Response`-Return in neuen Endpunkten).
> - Zusätzliche Modul-Guards implementieren `guardPermissions()` oder
>   `guardCapabilities()` und nutzen `Bookando\Core\Auth\Gate` anstelle
>   direkter `current_user_can()`-Aufrufe.
> - Status-Endpunkte (`/state`, `/sync_state`) registrieren einen eigenen Guard,
>   häufig via `RestModuleGuard::for('{slug}', [RestHandler::class, 'guardState'])`,
>   damit Modulaktivierungen respektiert werden (`tests/Integration/Rest/ResourcesPermissionsTest.php`).
> - Der frühere Catch-all-Dispatcher (`/{module}/{type}/{subkey?}`) bleibt nur
>   für Bestandsclients aktiv und wird perspektivisch entfernt. Neue Routen
>   **müssen** explizit im Modul registriert werden.
> - Schreibende Endpunkte validieren Request-Daten über modulare Validatoren
>   (z. B. `Bookando\Modules\customers\CustomerValidator`) und liefern
>   lokalisierte Fehlermeldungen gemäß
>   [`docs/api-response-conventions.md`](./api-response-conventions.md).

## Standard-CRUD-Konventionen

Neue Module orientieren sich an einem wiederverwendbaren CRUD-Schema:

- `GET /{module}/{resource}` → Liste (Pagination & Filter) via `RestHandler::list()`.
- `POST /{module}/{resource}` → Erstellung via `RestHandler::create()`.
- `GET /{module}/{resource}/{id}` → Detailansicht via `RestHandler::read()`.
- `PUT|PATCH /{module}/{resource}/{id}` → Aktualisierung via `RestHandler::update()`.
- `DELETE /{module}/{resource}/{id}` → Löschung via `RestHandler::delete()`.

Die Methoden spiegeln sich 1:1 im `RestHandler` wider. Tests wie
`tests/Integration/Rest/RouteSnapshotTest.php` und `tests/Integration/Rest/CustomersRoutesTest.php`
überprüfen, dass Endpunkte und Validierungsfehler stabil bleiben. Neue Fehlermeldungen müssen über
`__('...', 'bookando')` oder `_x()` übersetzt und – falls sie im UI erscheinen – zusätzlich in die
Modul-`i18n.local.ts` aufgenommen werden (siehe `docs/i18n.md`).

## Modulspezifische Endpunkte

Die folgende Übersicht listet alle aktuell registrierten Routen pro Modul.

### Academy

| Methoden            | Pfad                                            | Beschreibung |
| ------------------- | ----------------------------------------------- | ------------ |
| GET                 | `/bookando/v1/academy/state`                    | Aggregierter Modulstatus |
| POST                | `/bookando/v1/academy/courses`                  | Kurs anlegen/aktualisieren |
| DELETE              | `/bookando/v1/academy/courses/{id}`             | Kurs löschen |
| POST                | `/bookando/v1/academy/training_cards`           | Trainingskarte speichern |
| DELETE              | `/bookando/v1/academy/training_cards/{id}`      | Trainingskarte löschen |
| POST                | `/bookando/v1/academy/training_cards_progress`  | Fortschritt aktualisieren |

### Appointments

| Methoden                  | Pfad                                                       | Beschreibung |
| ------------------------- | ---------------------------------------------------------- | ------------ |
| GET                       | `/bookando/v1/appointments/timeline`                       | Zeitachsenübersicht |
| GET, POST, PUT, PATCH, DELETE | `/bookando/v1/appointments/appointments`              | Terminliste / Erstellung |
| GET, POST, PUT, PATCH, DELETE | `/bookando/v1/appointments/appointments/{subkey}`     | Terminoperationen auf Einzeltermin |
| POST                      | `/bookando/v1/appointments/assign`                         | Kunde einem Event/Termin zuweisen |
| GET                       | `/bookando/v1/appointments/lookups`                        | Lookup-Daten für Kunden/Services |

### Customers

| Methoden            | Pfad                                        | Beschreibung |
| ------------------- | ------------------------------------------- | ------------ |
| GET, POST           | `/bookando/v1/customers`                    | Kundenliste & Erstellung |
| GET, PUT, PATCH, DELETE | `/bookando/v1/customers/{subkey}`       | Einzelkunde lesen/aktualisieren/löschen |
| POST                | `/bookando/v1/customers/bulk`               | Bulk-Aktionen |
| GET                 | `/bookando/v1/customers/export`             | Export starten |

### Employees

| Methoden            | Pfad                                                    | Beschreibung |
| ------------------- | ------------------------------------------------------- | ------------ |
| GET, POST           | `/bookando/v1/employees`                                | Mitarbeitende (Collection) |
| GET, POST           | `/bookando/v1/employees/employees`                      | Legacy-Collection |
| GET, PUT, PATCH, DELETE | `/bookando/v1/employees/{id}`                       | Einzelner Mitarbeitender |
| GET, PUT, PATCH, DELETE | `/bookando/v1/employees/employees/{subkey}`         | Legacy-Einzelroute |
| POST                | `/bookando/v1/employees/bulk`                           | Bulk-Import |
| GET, POST           | `/bookando/v1/employees/{id}/workday-sets`              | Arbeitstage verwalten |
| GET, POST           | `/bookando/v1/employees/{id}/special-day-sets`          | Sondertage verwalten |
| GET                 | `/bookando/v1/employees/{id}/special-days`              | Legacy-Sondertage |
| GET, PUT            | `/bookando/v1/employees/{id}/calendars`                 | Kalenderzugänge |
| PATCH, DELETE       | `/bookando/v1/employees/{id}/calendars/{calId}`         | Kalender aktualisieren/löschen |
| POST                | `/bookando/v1/employees/{id}/calendar/invite`           | Kalender-Einladungen |
| POST, DELETE        | `/bookando/v1/employees/{id}/calendar/connections/ics`  | ICS-Verknüpfung verwalten |
| GET, POST, PUT      | `/bookando/v1/employees/{id}/days-off`                  | Abwesenheiten |

### Finance

| Methoden            | Pfad                                           | Beschreibung |
| ------------------- | ---------------------------------------------- | ------------ |
| GET                 | `/bookando/v1/finance/state`                   | Modulstatus |
| POST                | `/bookando/v1/finance/invoices`                | Rechnung speichern |
| DELETE              | `/bookando/v1/finance/invoices/{id}`           | Rechnung löschen |
| POST                | `/bookando/v1/finance/credit_notes`            | Gutschrift speichern |
| DELETE              | `/bookando/v1/finance/credit_notes/{id}`       | Gutschrift löschen |
| POST                | `/bookando/v1/finance/discount_codes`          | Rabattcode speichern |
| DELETE              | `/bookando/v1/finance/discount_codes/{id}`     | Rabattcode löschen |
| POST                | `/bookando/v1/finance/settings`                | Einstellungen speichern |
| POST                | `/bookando/v1/finance/export`                  | Export auslösen |

### Offers

| Methoden            | Pfad                                        | Beschreibung |
| ------------------- | ------------------------------------------- | ------------ |
| GET, POST           | `/bookando/v1/offers/offers`                | Angebote listen/anlegen |
| GET, PUT, PATCH, DELETE | `/bookando/v1/offers/offers/{id}`       | Angebot lesen/ändern/löschen |
| POST                | `/bookando/v1/offers/bulk`                  | Bulk-Aktionen |

### Resources

| Methoden            | Pfad                                         | Beschreibung |
| ------------------- | -------------------------------------------- | ------------ |
| GET                 | `/bookando/v1/resources/state`               | Ressourcenstatus |
| GET                 | `/bookando/v1/resources/locations`           | Standorte auflisten |
| GET                 | `/bookando/v1/resources/locations/{id}`      | Standortdetails abrufen |
| POST                | `/bookando/v1/resources/locations`           | Standort speichern |
| DELETE              | `/bookando/v1/resources/locations/{id}`      | Standort löschen |
| GET                 | `/bookando/v1/resources/rooms`               | Räume auflisten |
| GET                 | `/bookando/v1/resources/rooms/{id}`          | Raumdetails abrufen |
| POST                | `/bookando/v1/resources/rooms`               | Raum speichern |
| DELETE              | `/bookando/v1/resources/rooms/{id}`          | Raum löschen |
| GET                 | `/bookando/v1/resources/materials`           | Materialien auflisten |
| GET                 | `/bookando/v1/resources/materials/{id}`      | Materialdetails abrufen |
| POST                | `/bookando/v1/resources/materials`           | Material speichern |
| DELETE              | `/bookando/v1/resources/materials/{id}`      | Material löschen |

### Settings

| Methoden            | Pfad                                               | Beschreibung |
| ------------------- | -------------------------------------------------- | ------------ |
| GET                 | `/bookando/v1/settings`                            | Überblick |
| GET, POST           | `/bookando/v1/settings/company`                    | Firmendaten |
| GET, POST           | `/bookando/v1/settings/general`                    | Allgemeine Einstellungen |
| GET, POST           | `/bookando/v1/settings/roles/{role_slug}`          | Rollenverwaltung |
| GET, POST           | `/bookando/v1/settings/feature/{feature_key}`      | Feature-Flags |

Diese Tabelle dient sowohl als Referenz für Backend-Implementierungen als
auch für API-Konsumenten. Neue Module orientieren sich an derselben
Konvention, indem sie ihr eigenes `Api`-Register pflegen und jeden Endpoint
explizit anmelden.
