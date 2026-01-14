# Modul: Finance

## REST-Routen

| Route | Methoden | Beschreibung | Permission |
| --- | --- | --- | --- |
| `/bookando/v1/finance/state` | `GET` | Aggregierter Finanzstatus (Rechnungen, Gutschriften, Settings). | `manage_bookando_finance` |
| `/bookando/v1/finance/invoices` | `POST` | Neue Rechnung erstellen oder aktualisieren. | `manage_bookando_finance` |
| `/bookando/v1/finance/invoices/{id}` | `DELETE` | Rechnung löschen. | `manage_bookando_finance` |
| `/bookando/v1/finance/credit_notes` | `POST` | Gutschrift speichern. | `manage_bookando_finance` |
| `/bookando/v1/finance/credit_notes/{id}` | `DELETE` | Gutschrift löschen. | `manage_bookando_finance` |
| `/bookando/v1/finance/discount_codes` | `POST` | Rabattcode speichern. | `manage_bookando_finance` |
| `/bookando/v1/finance/discount_codes/{id}` | `DELETE` | Rabattcode löschen. | `manage_bookando_finance` |
| `/bookando/v1/finance/settings` | `POST` | Finanz-Einstellungen sichern (IBAN, Rechnungspräfix, …). | `manage_bookando_finance` |
| `/bookando/v1/finance/export` | `POST` | Journaldaten exportieren. | `manage_bookando_finance` |

## Parameter

| Parameter | Typ | Beschreibung |
| --- | --- | --- |
| `id` | `string` | Pfadparameter für DELETE-Routen (ID/Slug der Entität). |
| `payload` | `object` | JSON-Body mit Finanzdaten (`amount`, `currency`, `customer_id`, …). |
| `filter` | `array|null` | Optionale Filter (Zeiträume etc.) für Export-Aufrufe. |

## Permissions

Der REST-Guard erwartet die Fähigkeit `manage_bookando_finance`. Nicht berechtigte Aufrufe liefern einen `rest_forbidden` Fehler. Schreibende Aufrufe validieren zusätzlich das übermittelte JSON (siehe `RestHandler::decodeBody`).
