# Modul: Academy

## REST-Routen

| Route | Methoden | Beschreibung | Permission |
| --- | --- | --- | --- |
| `/bookando/v1/academy/state` | `GET` | Aktuellen Academy-State abrufen (Kurse + Trainingskarten). | `manage_bookando_academy` |
| `/bookando/v1/academy/courses` | `POST` | Kursdaten speichern bzw. upserten. | `manage_bookando_academy` |
| `/bookando/v1/academy/courses/{id}` | `DELETE` | Bestehenden Kurs entfernen. | `manage_bookando_academy` |
| `/bookando/v1/academy/training_cards` | `POST` | Trainingskarte speichern bzw. upserten. | `manage_bookando_academy` |
| `/bookando/v1/academy/training_cards/{id}` | `DELETE` | Trainingskarte löschen. | `manage_bookando_academy` |
| `/bookando/v1/academy/training_cards_progress` | `POST` | Fortschritt einer Trainingskarte aktualisieren. | `manage_bookando_academy` |

## Parameter

| Parameter | Typ | Beschreibung |
| --- | --- | --- |
| `id` | `string` | Slug/UUID der Kurs- oder Trainingskartenressource (Pfadparameter). |
| `progress` | `float` | Fortschrittswert (0-100) für `training_cards_progress`. |
| `milestones` | `array|null` | Optionales Milestone-Array für `training_cards_progress`. |

## Permissions

Alle Endpunkte sind über den `RestModuleGuard` geschützt und verlangen die WordPress-Fähigkeit `manage_bookando_academy`. Bei schreibenden Zugriffe greift zusätzlich der Guard `RestHandler::guardPermissions`.
