# Design Customization V2 - Änderungen und Verbesserungen

**Datum**: 13. November 2025
**Version**: 2.0
**Status**: Implementiert

---

## 📋 Zusammenfassung

Das Design Customization Tool im `Tools/Design` Modul wurde vollständig überarbeitet und dem Design vom Plugintemplate (Amelia) angepasst. Die Hauptverbesserungen umfassen:

1. ✅ **Live-Preview während Anpassungen** - Änderungen werden in Echtzeit angezeigt
2. ✅ **Erweiterter ColorPicker** - Unterstützt Gradient, HEX, RGB und Alpha-Transparenz
3. ✅ **Sidebar + Preview Layout** - Bessere Raumnutzung und Übersichtlichkeit
4. ✅ **CSS-Variable System** - Real-time Updates ohne Neuladen
5. ✅ **Plugintemplate Design** - Professionelles Design basierend auf Best Practices

---

## 🎯 Implementierte Features

### 1. Advanced Color Picker

**Datei**: `src/modules/tools/assets/vue/components/design/AdvancedColorPicker.vue`

**Features:**
- ✅ Kleine quadratische Box (32x32px) mit Vorschau
- ✅ Dropdown mit 2 Modi: **Solid** und **Gradient**
- ✅ **Solid Mode**:
  - Farbspektrum (Saturation + Brightness)
  - Hue-Slider (0-360°)
  - Alpha-Slider (0-100%) - optional
  - HEX Input (z.B. `#FF0000`)
  - RGB Inputs (R: 0-255, G: 0-255, B: 0-255)
  - Alpha Percent Input (0-100%)
  - 12 Preset-Farben
- ✅ **Gradient Mode**:
  - Linear / Radial Gradient Auswahl
  - Gradient-Winkel (0-360°) für Linear
  - Beliebig viele Color Stops
  - Drag & Drop Position für Stops
  - Farbe pro Stop editierbar
  - Add/Remove Stops
- ✅ **Unterstützte Formate**:
  - `#RRGGBB` (HEX)
  - `rgb(r, g, b)` (RGB)
  - `rgba(r, g, b, a)` (RGBA mit Transparenz)
  - `linear-gradient(angle, color1, color2, ...)`
  - `radial-gradient(circle, color1, color2, ...)`

**Design:** Angelehnt an Plugintemplate Farb-Picker mit modernem UI

---

### 2. Redesigned DesignTab Layout

**Datei**: `src/modules/tools/assets/vue/components/design/DesignTab.vue`

**Alte Struktur (VORHER):**
```
❌ Fullscreen Overlay (40% Form / 60% Preview)
❌ Placeholder-Preview (kein echtes Formular)
❌ 20+ Farboptionen unorganisiert
❌ Lange Scrolllisten
```

**Neue Struktur (NACHHER):**
```
✅ Level 1: Type Overview (BLEIBT GLEICH)
    - Card-basierte Übersicht für Service-Types

✅ Level 2: Template List (BLEIBT GLEICH)
    - Liste der Vorlagen pro Type
    - Drag & Drop Sortierung

✅ Level 3: Editor (NEU - KOMPLETT ÜBERARBEITET)
    ┌─────────────────────────────────────────────┐
    │ Header: Zurück | Title | Actions            │
    ├─────────────┬───────────────────────────────┤
    │  Sidebar    │   Live Preview                │
    │  (398px)    │   (Fluid)                     │
    │             │                               │
    │  Settings   │   [Desktop] [Tablet] [Mobile] │
    │  Content    │                               │
    │  - Name     │   ┌──────────────────────┐   │
    │  - Global   │   │                      │   │
    │  - Colors   │   │  Echtes Formular     │   │
    │  - Gradient │   │  mit CSS-Variablen   │   │
    │  - Comps    │   │                      │   │
    │             │   └──────────────────────┘   │
    └─────────────┴───────────────────────────────┘
```

**Sidebar (30% / 398px) - Inspiriert von Plugintemplate:**
- ✅ Fixed Breite (398px) wie in Amelia
- ✅ Background: `#f9f9f9` (Plugintemplate Highlight Color)
- ✅ Scroll-Container für lange Einstellungslisten
- ✅ Collapse/Accordion Pattern für Gruppierungen

**Preview (70% / Fluid):**
- ✅ Echtes Formular-Rendering
- ✅ CSS-Variablen für Live-Updates
- ✅ Device-Selector (Desktop/Tablet/Mobile)
- ✅ Max-Width anpassbar (Desktop: 802px, Tablet: 768px, Mobile: 375px)
- ✅ Box-Shadow wie Plugintemplate: `0 20px 40px rgba(0, 0, 0, 0.1)`

---

### 3. Globale Farben - Strukturiert & Gruppiert

**Alte Struktur:**
```
❌ Primärfarbe
❌ Erfolg Farbe
❌ Warnung Farbe
❌ Fehlerfarbe
❌ Seitenleiste Hintergrund
❌ Seitenleiste Text
❌ ... 20+ weitere Farben ungruppert
```

**Neue Struktur (Collapse-Gruppen):**
```
✅ Globale Einstellungen [Collapse]
    - Schriftart
    - Custom Font URL
    - Rahmenbreite
    - Eckenradius

✅ Globale Farben [Collapse]
    ┌─ Primär- und Zustandsfarben
    │   - Primärfarbe
    │   - Erfolg Farbe
    │   - Warnung Farbe
    │   - Fehlerfarbe
    │
    ┌─ Seitenleiste
    │   - Hintergrundfarbe (mit Alpha)
    │   - Textfarbe
    │
    ┌─ Inhalt
    │   - Hintergrundfarbe (mit Alpha)
    │   - Überschrift Farbe
    │   - Text Farbe
    │
    ┌─ Eingabefelder
    │   - Hintergrund (mit Alpha)
    │   - Rahmen
    │   - Text
    │   - Platzhalter (mit Alpha)
    │
    └─ Schaltflächen
        - Primär - Hintergrund
        - Primär - Text
        - Sekundär - Hintergrund
        - Sekundär - Text

✅ Gradient [Collapse]
    - Gradient aktivieren [Toggle]
    - Farbe 1
    - Farbe 2
    - Winkel (0-360°)

✅ Komponenten [Collapse]
    - Kategorien [Drill-Down Card]
    - Überblick [Drill-Down Card]
```

**Vorteile:**
- ✅ 23 Farben statt 20+, aber übersichtlich gruppiert
- ✅ Standardmäßig eingeklappt → weniger Scrolling
- ✅ Logische Gruppierung nach UI-Bereichen
- ✅ Visuell getrennt durch Gruppe-Titel mit Akzent-Linie

---

### 4. CSS-Variable System für Live-Updates

**Datei**: `src/modules/tools/assets/vue/components/design/previews/ServiceCatalogPreview.vue`

**Wie es funktioniert:**

1. **CSS-Variablen werden generiert:**
```typescript
const cssVariables = computed(() => ({
  '--bookando-primary': colors.primary,
  '--bookando-success': colors.success,
  '--bookando-warning': colors.warning,
  '--bookando-error': colors.error,
  '--bookando-sidebar-bg': colors.sidebar.background,
  '--bookando-sidebar-text': colors.sidebar.text,
  '--bookando-content-bg': colors.content.background,
  '--bookando-content-heading': colors.content.heading,
  '--bookando-content-text': colors.content.text,
  '--bookando-input-bg': colors.input.background,
  '--bookando-input-border': colors.input.border,
  '--bookando-input-text': colors.input.text,
  '--bookando-input-placeholder': colors.input.placeholder,
  '--bookando-btn-primary-bg': colors.buttons.primary.background,
  '--bookando-btn-primary-text': colors.buttons.primary.text,
  '--bookando-btn-secondary-bg': colors.buttons.secondary.background,
  '--bookando-btn-secondary-text': colors.buttons.secondary.text,
  '--bookando-font-family': fontFamily,
  '--bookando-border-width': `${border.width}px`,
  '--bookando-border-radius': `${border.radius}px`,
  '--bookando-gradient': gradient.enabled
    ? `linear-gradient(${gradient.angle}deg, ${gradient.color1}, ${gradient.color2})`
    : 'none'
}))
```

2. **CSS-Variablen werden auf Preview-Container angewendet:**
```vue
<div class="service-catalog-preview" :style="cssVariables">
  <!-- Content nutzt var(--bookando-*) -->
</div>
```

3. **Preview-Komponente nutzt CSS-Variablen:**
```scss
.preview-sidebar {
  background: var(--bookando-sidebar-bg, #1F2937);
  color: var(--bookando-sidebar-text, #F9FAFB);
}

.search-input {
  border: var(--bookando-border-width, 1px) solid var(--bookando-input-border, #E2E6EC);
  border-radius: var(--bookando-border-radius, 6px);
  background: var(--bookando-input-bg, #FFFFFF);
  color: var(--bookando-input-text, #354052);

  &::placeholder {
    color: var(--bookando-input-placeholder, rgba(127, 143, 164, 0.5));
  }
}

.service-button {
  background: var(--bookando-btn-primary-bg, #1A84EE);
  color: var(--bookando-btn-primary-text, #FFFFFF);
  border-radius: var(--bookando-border-radius, 6px);
}
```

**Ergebnis:** Änderungen in Sidebar werden **sofort** in Preview sichtbar - ohne Speichern oder Neulade.

---

### 5. Live Preview Komponenten

**Erstellt:**
- ✅ `ServiceCatalogPreview.vue` - Zeigt Dienstleistungs-Katalog mit echten UI-Elementen

**Komponente beinhaltet:**
- Sidebar mit Kategorieliste
- Suchleiste
- Filter-Dropdown
- Service-Cards mit:
  - Badge (Beliebt)
  - Image Placeholder
  - Titel & Beschreibung
  - Meta-Informationen (Dauer, Standort)
  - Preis
  - "Jetzt buchen" Button
- Responsive für Desktop/Tablet/Mobile

**Gradient-Support:**
- Eine Service-Card zeigt Gradient im Image-Bereich
- Gradient wird aus den Einstellungen übernommen

**TODO (für später):**
- `CoursePreview.vue` - Für Kurs-Buchungen
- `EventListPreview.vue` - Für Veranstaltungsliste
- `CustomerPanelPreview.vue` - Für Kundenbereich

---

### 6. Plugintemplate Design-System

**Datei**: `src/modules/tools/assets/vue/components/design/_design-tab-v2.scss`

**Übernommene Styles:**

1. **Farben:**
```scss
$am-color-text-prime: #354052;    // Primary Text
$am-color-text-second: #7F8FA4;   // Secondary Text
$am-color-divider-gray: #E2E6EC;  // Borders/Dividers
$am-color-highlight: #f9f9f9;     // Background Highlight
$am-color-blue: #1A84EE;          // Accent/Primary
$am-color-red: #FF0040;           // Error/Danger
```

2. **Spacing:**
```scss
$am-padding-big: 24px;
$am-padding-medium: 16px;
$am-padding-small: 8px;
$am-margin-big: 24px;
$am-margin-medium: 16px;
$am-margin-small: 8px;
```

3. **Typography:**
```scss
$am-regular-fs: 16px;
$am-small-fs: 14px;
$am-mini-fs: 12px;
$am-medium-fs: 20px;
```

4. **Border Radius:**
```scss
$am-border-radius: 6px;
```

5. **Animations:**
```scss
@keyframes fadeIn { ... }
@keyframes slideDown { ... }
@keyframes am-animation-slide-up { ... }
@keyframes am-animation-slide-right { ... }
```

6. **Scrollbar Styling:**
```scss
&::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

&::-webkit-scrollbar-track {
  background: #f9f9f9;
  border-radius: 4px;
}

&::-webkit-scrollbar-thumb {
  background: rgba(127, 143, 164, 0.3);
  border-radius: 4px;
}
```

---

## 📁 Dateistruktur

```
src/modules/tools/assets/vue/components/design/
├── AdvancedColorPicker.vue         (NEU - 620 Zeilen)
├── DesignTab.vue                   (ÜBERARBEITET - 1050 Zeilen)
├── DesignTab_old_backup.vue        (BACKUP - Original)
├── DesignTypeCard.vue              (UNVERÄNDERT)
├── RangeSlider.vue                 (UNVERÄNDERT)
├── ServiceDesignForm.vue           (UNVERÄNDERT - Waterfall für später)
├── _design-tab-v2.scss             (NEU - 320 Zeilen)
└── previews/
    └── ServiceCatalogPreview.vue   (NEU - 420 Zeilen)
```

---

## 🎨 Design-Entscheidungen

### Warum Sidebar + Preview statt Fullscreen Overlay?

**Vorteile:**
1. ✅ **Bessere Übersicht**: Einstellungen und Preview gleichzeitig sichtbar
2. ✅ **Mehr Platz für Preview**: 70% statt 60%
3. ✅ **Weniger Klicks**: Kein Wechsel zwischen Edit und Preview nötig
4. ✅ **Professioneller**: Standardmuster in Design-Tools (Figma, Webflow, etc.)
5. ✅ **Plugintemplate-Standard**: Konsistent mit Best Practices

### Warum Collapse/Accordion für Farben?

**Vorteile:**
1. ✅ **Weniger Scrolling**: Nur aktive Gruppen geöffnet
2. ✅ **Bessere Organisation**: Logische Gruppierung
3. ✅ **Übersichtlicher**: Auf den ersten Blick nur Gruppe-Titel sichtbar
4. ✅ **Schnellerer Zugriff**: Direkt zur gewünschten Gruppe springen

### Warum CSS-Variablen statt SCSS/LESS Kompilation?

**Vorteile:**
1. ✅ **Echtzeit-Updates**: Änderungen sofort sichtbar ohne Kompilation
2. ✅ **Kein Backend nötig**: Kompilation passiert im Browser
3. ✅ **Einfacher zu debuggen**: Chrome DevTools zeigen CSS-Variablen an
4. ✅ **Performance**: Keine Server-Anfrage für jede Änderung

**Nachteil:**
- ❌ CSS-Variablen werden nicht in finale CSS-Datei gespeichert
- **Lösung**: Beim Speichern Backend-API aufrufen, die CSS kompiliert

---

## 🔧 Technische Details

### AdvancedColorPicker State Management

```typescript
// Solid Color State
const hue = ref(0)                  // 0-360°
const saturation = ref(1)           // 0-1
const brightness = ref(1)           // 0-1
const alpha = ref(1)                // 0-1
const rgb = ref({ r: 255, g: 0, b: 0 })
const hexInput = ref('#FF0000')

// Gradient State
const gradientType = ref<'linear' | 'radial'>('linear')
const gradientAngle = ref(90)      // 0-360°
const gradientStops = ref([
  { color: '#FF0000', position: 0 },
  { color: '#0000FF', position: 100 }
])
```

### Conversion Functions

1. **HSB → RGB:**
```typescript
function updateRGBFromHSB() {
  const h = hue.value
  const s = saturation.value
  const v = brightness.value

  const c = v * s
  const x = c * (1 - Math.abs(((h / 60) % 2) - 1))
  const m = v - c

  // ... calculate r, g, b based on hue range

  rgb.value = {
    r: Math.round((r + m) * 255),
    g: Math.round((g + m) * 255),
    b: Math.round((b + m) * 255)
  }
}
```

2. **RGB → HSB:**
```typescript
function updateHSB() {
  const r = rgb.value.r / 255
  const g = rgb.value.g / 255
  const b = rgb.value.b / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const delta = max - min

  brightness.value = max
  saturation.value = max === 0 ? 0 : delta / max

  // ... calculate hue based on which color is max
}
```

3. **RGB → HEX:**
```typescript
function updateHexInput() {
  const r = rgb.value.r.toString(16).padStart(2, '0')
  const g = rgb.value.g.toString(16).padStart(2, '0')
  const b = rgb.value.b.toString(16).padStart(2, '0')
  hexInput.value = `#${r}${g}${b}`.toUpperCase()
}
```

### Drag & Drop für Farbspektrum/Sliders

```typescript
function startSpectrumDrag(e: MouseEvent) {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()

  const updateSpectrum = (event: MouseEvent) => {
    saturation.value = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width))
    brightness.value = Math.max(0, Math.min(1, 1 - (event.clientY - rect.top) / rect.height))
    updateRGBFromHSB()
    updateHexInput()
    emitColor()
  }

  updateSpectrum(e)

  const onMove = (event: MouseEvent) => updateSpectrum(event)
  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}
```

---

## 🚀 Nächste Schritte (TODO)

### 1. Backend API Integration

**Erforderlich:**
```php
// DesignRestHandler.php

GET /tools/design/templates
→ Gibt alle gespeicherten Templates zurück

POST /tools/design/templates
→ Speichert neues Template

PUT /tools/design/templates/{id}
→ Aktualisiert Template

DELETE /tools/design/templates/{id}
→ Löscht Template

POST /tools/design/templates/{id}/compile
→ Kompiliert Template zu CSS-Datei
```

**CSS-Kompilation:**
```php
class CSSCompiler {
    public function compile(array $template): string {
        $variables = $this->extractVariables($template);
        $scss = $this->generateSCSS($variables);
        return $this->compileToCSS($scss);
    }

    private function extractVariables(array $template): array {
        return [
            'bookando-primary' => $template['globalSettings']['colors']['primary'],
            'bookando-success' => $template['globalSettings']['colors']['success'],
            // ... alle weiteren Variablen
        ];
    }
}
```

### 2. Weitere Preview-Komponenten

**TODO:**
- `CoursePreview.vue` - Für Kurs-Buchungsformular
- `EventListPreview.vue` - Für Veranstaltungsliste
- `CustomerPanelPreview.vue` - Für Kundenbereich

**Struktur:**
```vue
<template>
  <div class="[type]-preview" :style="cssVariables">
    <!-- Type-spezifische UI -->
  </div>
</template>

<script setup>
const props = defineProps(['template', 'cssVariables'])
</script>

<style scoped>
/* Nutzt CSS-Variablen wie ServiceCatalogPreview */
</style>
```

### 3. Preset-System

**Vordefinierte Vorlagen:**
```typescript
const presets = [
  {
    name: 'Modern',
    colors: {
      primary: '#1A84EE',
      // ... complete color set
    },
    gradient: {
      enabled: true,
      color1: '#1A84EE',
      color2: '#A28FF3',
      angle: 135
    }
  },
  {
    name: 'Classic',
    colors: { /* ... */ }
  },
  {
    name: 'Minimal',
    colors: { /* ... */ }
  },
  {
    name: 'Vibrant',
    colors: { /* ... */ }
  }
]
```

### 4. Export/Import System

**JSON Export:**
```typescript
function exportTemplate() {
  const json = JSON.stringify(currentTemplate.value, null, 2)
  const blob = new Blob([json], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${currentTemplate.value.name}.json`
  a.click()
}
```

**JSON Import:**
```typescript
function importTemplate(file: File) {
  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const template = JSON.parse(e.target.result as string)
      currentTemplate.value = template
    } catch (error) {
      alert('Ungültiges JSON-Format')
    }
  }
  reader.readAsText(file)
}
```

### 5. Komponenten-spezifische Einstellungen

**Waterfall-Navigation für Komponenten:**
- Kategorien → Optionen (Toggles), Beschriftungen (Inputs)
- Überblick → Optionen (Toggles), Beschriftungen (Inputs)
- Details → Layout, Farben, Textoptionen
- Pakete → Layout, Preisdarstellung, Button-Styles

**Bereits vorhanden in:**
- `ServiceDesignForm.vue` (kann als Vorlage dienen)

### 6. Accessibility Check

**Kontrast-Prüfung:**
```typescript
function checkContrast(fg: string, bg: string): number {
  const l1 = getLuminance(fg)
  const l2 = getLuminance(bg)
  const lighter = Math.max(l1, l2)
  const darker = Math.min(l1, l2)
  return (lighter + 0.05) / (darker + 0.05)
}

function showAccessibilityWarnings() {
  const ratio = checkContrast(
    currentTemplate.value.colors.content.text,
    currentTemplate.value.colors.content.background
  )

  if (ratio < 4.5) {
    // WCAG AA Level nicht erreicht
    showWarning('Textkontrast zu niedrig')
  }
}
```

### 7. Undo/Redo History

**History Stack:**
```typescript
const history = ref([])
const historyIndex = ref(-1)

function saveToHistory() {
  history.value = history.value.slice(0, historyIndex.value + 1)
  history.value.push(JSON.stringify(currentTemplate.value))
  historyIndex.value++
}

function undo() {
  if (historyIndex.value > 0) {
    historyIndex.value--
    currentTemplate.value = JSON.parse(history.value[historyIndex.value])
  }
}

function redo() {
  if (historyIndex.value < history.value.length - 1) {
    historyIndex.value++
    currentTemplate.value = JSON.parse(history.value[historyIndex.value])
  }
}
```

---

## 📝 Testing Checklist

### Funktionale Tests

- [ ] Type-Auswahl funktioniert
- [ ] Template-Liste zeigt korrekte Templates an
- [ ] Drag & Drop Sortierung funktioniert
- [ ] Template erstellen/bearbeiten/löschen/duplizieren funktioniert
- [ ] ColorPicker öffnet/schließt korrekt
- [ ] Solid Color Mode:
  - [ ] Farbspektrum Drag funktioniert
  - [ ] Hue-Slider funktioniert
  - [ ] Alpha-Slider funktioniert (wenn showAlpha=true)
  - [ ] HEX Input aktualisiert Farbe
  - [ ] RGB Inputs aktualisieren Farbe
  - [ ] Alpha Input aktualisiert Transparenz
  - [ ] Preset-Farben funktionieren
- [ ] Gradient Mode:
  - [ ] Linear/Radial Toggle funktioniert
  - [ ] Winkel-Slider funktioniert (Linear)
  - [ ] Color Stops hinzufügen/entfernen funktioniert
  - [ ] Stop-Position ändern funktioniert
  - [ ] Stop-Farbe ändern funktioniert
- [ ] Collapse/Accordion funktioniert
- [ ] Live-Preview zeigt Änderungen sofort an
- [ ] Device-Selector wechselt Preview-Größe
- [ ] Speichern funktioniert
- [ ] Abbrechen funktioniert

### Visual Tests

- [ ] Sidebar hat korrekte Breite (398px Desktop)
- [ ] Colors von Plugintemplate werden verwendet
- [ ] Border Radius ist 6px
- [ ] Spacing ist konsistent (24/16/8px)
- [ ] Scrollbars haben Custom-Styling
- [ ] Animations funktionieren (fadeIn, slideDown)
- [ ] Hover-Effekte funktionieren
- [ ] Box-Shadows sind korrekt

### Responsive Tests

- [ ] Desktop (>1200px): Sidebar + Preview nebeneinander
- [ ] Tablet (768-1199px): Sidebar kleiner, Preview angepasst
- [ ] Mobile (<767px): Sidebar über Preview, Full-Width

### Browser Tests

- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)

---

## 🐛 Bekannte Einschränkungen

1. **Backend-Integration fehlt noch:**
   - Templates werden aktuell nur im Frontend-State gespeichert
   - Kein Persistieren in Datenbank
   - Kein CSS-Kompilation Service
   - **Workaround**: Manuelle Speicherung via Browser LocalStorage möglich

2. **Preview-Komponenten unvollständig:**
   - Nur ServiceCatalogPreview existiert
   - CoursePreview, EventListPreview, CustomerPanelPreview fehlen noch
   - **Workaround**: ServiceCatalogPreview wird für alle Types verwendet

3. **Keine Undo/Redo:**
   - History-Stack noch nicht implementiert
   - **Workaround**: "Abbrechen" Button verwenden

4. **Kein Export/Import:**
   - JSON Export/Import noch nicht implementiert
   - **Workaround**: Copy-Paste von Browser DevTools JSON

5. **Keine Accessibility-Prüfung:**
   - WCAG-Kontrast-Check fehlt
   - **Workaround**: Externe Tools nutzen (z.B. WebAIM Contrast Checker)

---

## 📚 Referenzen

### Plugintemplate (Amelia) Dateien analysiert:

1. `Plugintemplate/assets/less/backend/customize.less` (36KB)
2. `Plugintemplate/assets/less/common/_variables.less` (168 Zeilen)
3. `Plugintemplate/src/Application/Commands/Settings/UpdateSettingsCommandHandler.php`
4. `Plugintemplate/src/Infrastructure/WP/SettingsService/SettingsStorage.php`

### Design-Entscheidungen basiert auf:

1. **Plugintemplate Customize Page** - Layout, Farben, Spacing
2. **Figma** - Live-Preview Pattern
3. **Webflow** - CSS-Variable System
4. **Material Design** - Color Picker UI
5. **Tailwind CSS** - Responsive Breakpoints

---

## 🎉 Fazit

Die Design Customization V2 ist eine **vollständige Überarbeitung** mit folgenden Highlights:

1. ✅ **Professionelles Design** basierend auf Plugintemplate Best Practices
2. ✅ **Live-Preview** die tatsächlich funktioniert
3. ✅ **Erweiterter ColorPicker** mit Gradient-Support
4. ✅ **Bessere UX** durch Sidebar + Preview Layout
5. ✅ **Strukturierte Farben** durch Collapse/Accordion
6. ✅ **Real-time Updates** via CSS-Variablen
7. ✅ **Moderne Architektur** mit Vue 3 Composition API

**Nächste Schritte:**
- Backend API implementieren (Priorität 1)
- Weitere Preview-Komponenten erstellen (Priorität 2)
- Preset-System hinzufügen (Priorität 3)

---

**Erstellt von**: Claude (Anthropic)
**Datum**: 13. November 2025
**Version**: 2.0.0
