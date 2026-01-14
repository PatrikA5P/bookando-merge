# Modul: Resources

## REST-Routen

| Route | Methoden | Beschreibung | Permission |
| --- | --- | --- | --- |
| `/bookando/v1/resources/state` | `GET` | Liefert Locations, Räume und Materialien als gemeinsamen Modul-Status. | `manage_bookando_resources` (via Guard) |
| `/bookando/v1/resources/locations` | `POST` | Legt eine neue Location an oder aktualisiert sie. | `manage_bookando_resources` |
| `/bookando/v1/resources/locations/{id}` | `DELETE` | Entfernt eine Location dauerhaft. | `manage_bookando_resources` |
| `/bookando/v1/resources/rooms` | `POST` | Legt einen Raum an oder aktualisiert ihn. | `manage_bookando_resources` |
| `/bookando/v1/resources/rooms/{id}` | `DELETE` | Entfernt einen Raum dauerhaft. | `manage_bookando_resources` |
| `/bookando/v1/resources/materials` | `POST` | Legt Materialressourcen an oder aktualisiert sie. | `manage_bookando_resources` |
| `/bookando/v1/resources/materials/{id}` | `DELETE` | Entfernt eine Materialressource. | `manage_bookando_resources` |

## Parameter

| Parameter | Typ | Beschreibung |
| --- | --- | --- |
| `id` | `string` | Pfadparameter für DELETE-Endpunkte (Slug/UUID der Ressource). |
| `payload` | `object` | JSON-Body mit Ressourcendaten (`name`, `color`, `capacity`, …). |

## Permissions

Standardmäßig verlangt jeder Schreibzugriff die Fähigkeit `Capabilities::CAPABILITY_MANAGE` (Filter `bookando_resources_capability_map`). Der Guard blockiert außerdem DELETE/POST, wenn es sich nicht um einen Schreibzugriff handelt (`Gate::isWrite`).
