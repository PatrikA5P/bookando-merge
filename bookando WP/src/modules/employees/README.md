# Modul: Employees

> Scaffold erstellt mit Bookando CLI – 2025-09-08

## Beschreibung
Manage employees, schedules, days off and permissions.

## REST-Endpunkte
- GET /employees

## Vue-Komponenten
- SPA: EmployeesView.vue
- State: Pinia
- Liste: ✅
- Formular: ✅
- Upload: ✅
- Datepicker: ✅
- Editor: ❌

## Lokalisierung
- Core-Strings via `@core/Design/i18n`
- Lokale Erweiterungen mit `mergeI18nMessages` aus `@core/Locale/messages`

```ts
import { mergeI18nMessages } from '@core/Locale/messages'
import { messages as coreMessages } from '@core/Design/i18n'

const localModules = import.meta.glob('./i18n.local.{ts,js}', { eager: true })
const messages = mergeI18nMessages(coreMessages, localModules)
```

## Lizenzpflicht: ✅ Ja
