# Dev Mode Error Display System

## Übersicht

Das Dev Mode Error Display System zeigt **visuelle Fehlermeldungen direkt im Browser** anstatt nur "Lade Modul..." anzuzeigen, wenn ein Modul nicht korrekt lädt.

## Features

✅ **Visuelle Fehleranzeige** - Ersetzt "Lade Modul..." durch detaillierte Fehlermeldungen
✅ **Loading Timeline** - Zeigt genau, an welchem Checkpoint das Loading fehlgeschlagen ist
✅ **Stack Traces** - Vollständige JavaScript-Stack-Traces inline angezeigt
✅ **Debug Context** - Zeigt BOOKANDO_VARS, DOM-Status und weitere Kontextinfos
✅ **Production Safe** - Nur aktiv wenn `WP_DEBUG` in wp-config.php aktiviert ist
✅ **Export-Funktion** - Debug-Informationen können als JSON exportiert werden

## Aktivierung

### 1. WP_DEBUG aktivieren

In `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Optional: verhindert PHP-Fehler im HTML
```

### 2. Browser öffnen

- Navigiere zu einem Bookando-Modul (z.B. Customers, Offers)
- Bei Fehlern siehst du jetzt eine detaillierte Fehleranzeige statt "Lade Modul..."

### 3. Browser Console öffnen

Zusätzliche Debug-Informationen werden in der Browser-Console geloggt:

```javascript
[Bookando Customers] ✅ Script loaded
[Bookando Customers] ✅ Imports loaded
[Bookando Customers] ✅ Local i18n modules loaded
[Bookando Customers] ✅ DOM mount point check
[Bookando Customers] ✅ Creating Vue app instance
...
```

## Visuelle Fehleranzeige

Wenn ein Fehler auftritt, zeigt das System:

### 📦 Module Information
- Welches Modul betroffen ist (z.B. "customers")

### ❌ Fehler-Details
- Checkpoint, an dem der Fehler aufgetreten ist
- Fehlermeldung
- Stack Trace (klickbar, expandierbar)
- Zusätzliche Kontextinformationen

### 📊 Loading Timeline
- Liste aller erfolgreichen Checkpoints
- Zeitdifferenz zwischen Checkpoints
- Zeigt genau, welcher Schritt fehlgeschlagen ist

### 💡 Debugging-Tipps
- Hinweise zur Fehlerdiagnose
- Empfohlene nächste Schritte

## Verwendung in Modulen

### Beispiel: Customers Module (bereits integriert)

```typescript
import { devErrorDisplay } from '@core/Design/assets/ts/dev-error-display'
import { createVueErrorHandler } from '@core/Design/assets/ts/vue-error-handler'

// Initialize early
devErrorDisplay.checkpoint('Script loaded', {
  isDev: true,
  timestamp: new Date().toISOString()
})

try {
  // Import modules
  import { createApp } from 'vue'
  devErrorDisplay.checkpoint('Imports loaded')

  // Load i18n
  const messages = mergeI18nMessages(coreMessages, localModules)
  devErrorDisplay.checkpoint('i18n messages merged', {
    languages: Object.keys(messages)
  })

  // Get root element
  const root = document.querySelector('#bookando-customers-root')
  devErrorDisplay.init('customers', root)

  devErrorDisplay.checkpoint('DOM mount point check', {
    rootFound: !!root
  })

  if (!root) {
    throw new Error('Root element not found')
  }

  // Create app
  devErrorDisplay.checkpoint('Creating Vue app instance')
  const app = createApp(CustomersView)

  // Install error handler FIRST
  devErrorDisplay.checkpoint('Installing error handler plugin')
  app.use(createVueErrorHandler({ moduleSlug: 'customers' }))

  // Install other plugins
  app.use(i18n)
  app.use(createPinia())

  // Mount
  devErrorDisplay.checkpoint('Mounting Vue app to DOM')
  app.mount(root)

  devErrorDisplay.checkpoint('✅ APP MOUNTED SUCCESSFULLY')

} catch (error) {
  devErrorDisplay.error('Fatal initialization error', error, {
    bookandoVars: window.BOOKANDO_VARS,
    location: window.location.href
  })
}
```

## API Reference

### `devErrorDisplay`

#### `checkpoint(name: string, details?: object)`
Loggt einen erfolgreichen Checkpoint während des Loadings.

```typescript
devErrorDisplay.checkpoint('Imports loaded')
devErrorDisplay.checkpoint('Vue app created', { version: '3.4.0' })
```

#### `error(name: string, error: Error, details?: object)`
Loggt einen Fehler und zeigt visuelle Fehleranzeige.

```typescript
devErrorDisplay.error('Mount failed', new Error('Root element not found'), {
  selector: '#bookando-customers-root',
  available: document.querySelectorAll('[id^="bookando-"]')
})
```

#### `init(moduleSlug: string, rootElement: HTMLElement | null)`
Initialisiert das Error Display für ein bestimmtes Modul.

```typescript
const root = document.querySelector('#bookando-customers-root')
devErrorDisplay.init('customers', root)
```

#### `getCheckpoints(): LoadingCheckpoint[]`
Gibt alle bisher geloggten Checkpoints zurück.

```typescript
const checkpoints = devErrorDisplay.getCheckpoints()
console.log(checkpoints)
```

#### `exportCheckpoints(): string`
Exportiert alle Checkpoints als JSON-String (nützlich für Bug Reports).

```typescript
const debugInfo = devErrorDisplay.exportCheckpoints()
console.log(debugInfo)
// oder in die Zwischenablage kopieren:
navigator.clipboard.writeText(debugInfo)
```

### `createVueErrorHandler(options)`

#### Options
```typescript
interface VueErrorHandlerOptions {
  moduleSlug: string          // z.B. 'customers'
  onError?: (error: Error, info: string) => void  // Optional custom handler
}
```

#### Verwendung
```typescript
import { createVueErrorHandler } from '@core/Design/assets/ts/vue-error-handler'

const app = createApp(MyComponent)

// WICHTIG: Als ERSTES Plugin installieren
app.use(createVueErrorHandler({ moduleSlug: 'customers' }))

// Dann andere Plugins
app.use(i18n)
app.use(createPinia())
```

## Debug-Informationen exportieren

### Via Browser Console

```javascript
// Alle Checkpoints anzeigen
window.__BOOKANDO_DEV__.errorDisplay.getCheckpoints()

// Debug-Info als JSON exportieren
console.log(window.__BOOKANDO_DEV__.exportDebugInfo())

// In Zwischenablage kopieren
navigator.clipboard.writeText(window.__BOOKANDO_DEV__.exportDebugInfo())
```

### Export enthält:
- Modul-Slug
- Alle Checkpoints mit Timestamps
- User Agent
- BOOKANDO_VARS Inhalt
- ISO-Timestamp des Exports

## Andere Module aktualisieren

Um das Error Display System in anderen Modulen zu aktivieren, folge dem Customers-Beispiel:

1. **Imports hinzufügen** (ganz oben in main.ts):
   ```typescript
   import { devErrorDisplay } from '@core/Design/assets/ts/dev-error-display'
   import { createVueErrorHandler } from '@core/Design/assets/ts/vue-error-handler'
   ```

2. **Checkpoints hinzufügen** bei wichtigen Schritten:
   ```typescript
   devErrorDisplay.checkpoint('Schritt-Name', { optional: 'details' })
   ```

3. **Init aufrufen** vor dem Mount:
   ```typescript
   devErrorDisplay.init(moduleSlug, rootElement)
   ```

4. **Error Handler installieren** als erstes Plugin:
   ```typescript
   app.use(createVueErrorHandler({ moduleSlug }))
   ```

5. **Try-Catch erweitern**:
   ```typescript
   catch (error) {
     devErrorDisplay.error('Fatal error', error, { context: 'info' })
   }
   ```

## Beispiel-Output

Bei einem Fehler wird folgendes angezeigt:

```
⚠️ Module Loading Error [DEV MODE]

Module: customers

Failed at: DOM mount point check

Error:
Root element #bookando-customers-root not found in DOM

📋 Loading Timeline (5 checkpoints)
1. ✅ Script loaded                        +0ms
2. ✅ Imports loaded                       +234ms
3. ✅ i18n messages merged                 +45ms
4. ✅ Locale booted from bridge            +12ms
5. ❌ DOM mount point check                +8ms

💡 Debugging Tips:
• Check the browser console for detailed logs
• Verify BOOKANDO_VARS is properly initialized
• Check network tab for failed asset loading
• Ensure root element exists in DOM
• Check for JavaScript syntax errors in Vue components
```

## Troubleshooting

### "Lade Modul..." bleibt stehen, aber keine Fehleranzeige

**Mögliche Ursachen:**
1. WP_DEBUG ist nicht aktiviert → In wp-config.php prüfen
2. JavaScript läuft gar nicht erst → Network-Tab prüfen, ob main.js geladen wird
3. Fehler tritt vor Error-Display-Import auf → Browser-Console prüfen

**Lösung:**
- Browser-Console öffnen (F12)
- Auf JavaScript-Fehler prüfen
- Network-Tab prüfen, ob Assets geladen werden

### Error Display erscheint nicht im DOM

**Mögliche Ursachen:**
1. `devErrorDisplay.init()` wurde nicht aufgerufen
2. Root-Element ist null
3. Fehler tritt nach dem Mount auf (Vue Error Handler übernimmt)

**Lösung:**
- Console-Logs prüfen
- Sicherstellen, dass `devErrorDisplay.init(slug, root)` vor dem Mount aufgerufen wird

### Export-Funktion nicht verfügbar

**Mögliche Ursache:**
- Module noch nicht geladen

**Lösung:**
```javascript
// Warten bis Module geladen ist
setTimeout(() => {
  console.log(window.__BOOKANDO_DEV__.exportDebugInfo())
}, 1000)
```

## Best Practices

### ✅ DO

- Checkpoints bei wichtigen Schritten setzen
- Error Handler als ERSTES Plugin installieren
- Aussagekräftige Namen für Checkpoints verwenden
- Details-Objekte mit relevanten Infos hinzufügen
- Try-Catch um den gesamten Init-Code

### ❌ DON'T

- Checkpoints in Loops setzen (Performance)
- Sensible Daten in Details-Objekte packen (Passwörter, API-Keys)
- Error Display in Production Code lassen (automatisch deaktiviert wenn WP_DEBUG = false)
- devErrorDisplay vor Import verwenden

## Performance

Das Error Display System hat **minimalen Performance-Impact**:

- In Production (WP_DEBUG = false): **Nur 2 Zeilen Code ausgeführt**
- In Development: **< 1ms pro Checkpoint**
- Visual Rendering: **Nur bei Fehlern**

## Support

Bei Fragen oder Problemen:

1. Browser Console öffnen und nach Fehlern suchen
2. `window.__BOOKANDO_DEV__.exportDebugInfo()` ausführen
3. Output kopieren und an Support senden
4. WordPress Debug-Log prüfen (`/wp-content/debug.log`)
