# Bookando Design System - Standardisierung & Verbesserungen

## 📋 Übersicht

Dieses Dokument beschreibt die durchgeführten Design-System-Verbesserungen für das Bookando-Projekt. Ziel war es, ein konsistentes, wartbares und vollständig responsives Design-System zu schaffen.

---

## ✅ Durchgeführte Verbesserungen

### 1. **Erweiterte Design-Tokens** (`_tokens.scss`)

#### Neue Farbvarianten für Status-Elemente:
```scss
// Light Varianten (für subtile Hintergründe)
$color-success-light: #d1fae5;
$color-warning-light: #fef3c7;
$color-danger-light:  #fee2e2;
$color-info-light:    #e3f2fd;

// Dark Varianten (für Text auf hellen Hintergründen)
$color-success-dark: #065f46;
$color-warning-dark: #92400e;
$color-danger-dark:  #991b1b;
$color-info-dark:    #1565c0;
```

**Verwendungszweck:** Perfekt für Badges, Alerts und Status-Indikatoren mit weichen Hintergründen und gut lesbarem Text.

#### Erweiterte Font-Sizes (vollständig responsive mit `clamp()`):
```scss
$font-size-xxs:  clamp(0.625rem, 0.55rem + 0.1vw, 0.7rem);    // ~10-11px
$font-size-xs:   clamp(0.68rem, 0.6rem + 0.1vw, 0.78rem);     // ~11-12px
$font-size-sm:   clamp(0.80rem, 0.7rem + 0.2vw, 0.95rem);     // ~13-15px
$font-size-base: clamp(0.88rem, 0.75rem + 0.4vw, 1rem);       // ~14-16px
$font-size-md:   clamp(0.88rem, 0.75rem + 0.4vw, 1rem);       // ~14-16px
$font-size-lg:   clamp(1.02rem, 0.9rem + 0.5vw, 1.15rem);     // ~16-18px
$font-size-xl:   clamp(1.19rem, 1.05rem + 1vw, 1.45rem);      // ~19-23px
$font-size-2xl:  clamp(1.5rem, 1.2rem + 1.5vw, 1.875rem);     // ~24-30px
$font-size-3xl:  clamp(1.875rem, 1.5rem + 2vw, 2.25rem);      // ~30-36px
$font-size-4xl:  clamp(2.25rem, 1.8rem + 2.5vw, 3rem);        // ~36-48px
$font-size-5xl:  clamp(3rem, 2.5rem + 3vw, 4rem);             // ~48-64px
$font-size-heading: clamp(1.3rem, 1.05rem + 1.5vw, 2rem);     // ~21-32px
```

**Vorteil:** Automatisches Scaling basierend auf Viewport-Breite – kein Media-Query-Chaos mehr!

---

### 2. **Erweiterte Semantic Variables** (`_variables.scss`)

Alle neuen Tokens sind als semantische Aliase verfügbar:

```scss
// Status-Varianten
$bookando-success-light
$bookando-success-dark
$bookando-warning-light
$bookando-warning-dark
$bookando-danger-light
$bookando-danger-dark
$bookando-info-light
$bookando-info-dark

// Font-Sizes
$bookando-font-size-xxs
$bookando-font-size-2xl
$bookando-font-size-3xl
$bookando-font-size-4xl
$bookando-font-size-5xl
```

#### Erweiterte Utility-Maps:

**Background-Utilities:**
```scss
$bookando-bg-utilities: (
  "success-light": $bookando-success-light,
  "warning-light": $bookando-warning-light,
  "danger-light": $bookando-danger-light,
  "info-light": $bookando-info-light,
  // ... und dark Varianten
)
```

**Text-Utilities:**
```scss
$bookando-text-utilities: (
  "success-dark": $bookando-success-dark,
  "warning-dark": $bookando-warning-dark,
  "danger-dark": $bookando-danger-dark,
  "info-dark": $bookando-info-dark,
  // ... alle Varianten
)
```

**Font-Size-Utilities:**
```scss
$bookando-font-size-utilities: (
  "xxs": $bookando-font-size-xxs,
  "2xl": $bookando-font-size-2xl,
  "3xl": $bookando-font-size-3xl,
  "4xl": $bookando-font-size-4xl,
  "5xl": $bookando-font-size-5xl,
  // ... alle Größen
)
```

---

### 3. **CSS Custom Properties** (`_custom-properties.scss`)

Alle neuen Tokens sind als CSS Custom Properties verfügbar für Runtime-Theming:

```scss
:root {
  // Status-Varianten
  --bookando-success-light: #d1fae5;
  --bookando-success-dark: #065f46;
  --bookando-warning-light: #fef3c7;
  --bookando-warning-dark: #92400e;
  --bookando-danger-light: #fee2e2;
  --bookando-danger-dark: #991b1b;
  --bookando-info-light: #e3f2fd;
  --bookando-info-dark: #1565c0;

  // Font-Sizes
  --bookando-font-size-xxs: clamp(...);
  --bookando-font-size-2xl: clamp(...);
  --bookando-font-size-3xl: clamp(...);
  --bookando-font-size-4xl: clamp(...);
  --bookando-font-size-5xl: clamp(...);
}
```

---

### 4. **Erweiterte Badge-Komponente** (`AppBadge.vue`)

Die bestehende `AppBadge`-Komponente wurde um die neuen Status-Varianten erweitert:

#### Neue Variants:
```typescript
variant?:
  | 'default' | 'primary' | 'secondary'
  | 'success' | 'warning' | 'danger' | 'info'
  | 'success-light' | 'warning-light' | 'danger-light' | 'info-light'
  | 'success-dark' | 'warning-dark' | 'danger-dark' | 'info-dark'
```

#### Verwendungsbeispiele:
```vue
<!-- Light Variants (subtile Hintergründe) -->
<AppBadge variant="success-light" label="Aktiv" />
<AppBadge variant="warning-light" label="Ausstehend" />
<AppBadge variant="danger-light" label="Fehler" />

<!-- Dark Variants (kräftige Farben) -->
<AppBadge variant="success-dark" label="Bestätigt" />
<AppBadge variant="danger-dark" label="Abgelehnt" />
```

---

### 5. **Neue Empty-State-Komponente** (`AppEmptyState.vue`)

Eine wiederverwendbare Komponente für "No Data"-Zustände:

#### Props:
```typescript
interface AppEmptyStateProps {
  title?: string
  description?: string
  icon?: string
  iconSize?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl'
  actionLabel?: string
  actionIcon?: string
  actionVariant?: 'primary' | 'secondary' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  centered?: boolean
}
```

#### Verwendungsbeispiele:
```vue
<!-- Einfach -->
<AppEmptyState
  icon="inbox"
  title="Keine Termine"
  description="Es wurden noch keine Termine erstellt."
/>

<!-- Mit Action-Button -->
<AppEmptyState
  icon="calendar"
  title="Keine Buchungen"
  description="Erstelle deine erste Buchung."
  actionLabel="Buchung erstellen"
  actionIcon="plus"
  @action="handleCreateBooking"
/>

<!-- Mit Custom Content -->
<AppEmptyState size="lg">
  <template #icon>
    <img src="custom-illustration.svg" />
  </template>
  <template #title>Custom Titel</template>
  <template #description>Custom Beschreibung mit <strong>HTML</strong></template>
  <template #actions>
    <AppButton variant="primary">Action 1</AppButton>
    <AppButton variant="secondary">Action 2</AppButton>
  </template>
</AppEmptyState>
```

#### Features:
- ✅ Vollständig responsive (Mobile-first)
- ✅ Flexible Slots für Custom Content
- ✅ 3 Größenvarianten (sm, md, lg)
- ✅ Nutzt Design-Tokens für alle Styles
- ✅ Accessibility-optimiert

---

## 🎨 Automatisch generierte Utility-Klassen

Durch die erweiterten Maps werden automatisch neue Utility-Klassen generiert:

### Background-Klassen:
```html
<div class="bookando-bg-success-light">Heller Erfolgs-Hintergrund</div>
<div class="bookando-bg-warning-light">Heller Warn-Hintergrund</div>
<div class="bookando-bg-danger-light">Heller Fehler-Hintergrund</div>
```

### Text-Klassen:
```html
<span class="bookando-text-success-dark">Dunkler Erfolgs-Text</span>
<span class="bookando-text-danger-dark">Dunkler Fehler-Text</span>
```

### Font-Size-Klassen:
```html
<h1 class="bookando-text-5xl">Große Überschrift</h1>
<h2 class="bookando-text-4xl">Mittelgroße Überschrift</h2>
<p class="bookando-text-xxs">Sehr kleiner Text</p>
```

---

## 📐 Responsive Design Best Practices

### 1. **Breakpoints verwenden**

```scss
@use '@scss/mixins' as *;

.my-component {
  padding: $bookando-spacing-md;

  // Mobile (< 768px)
  @include bp-down(md) {
    padding: $bookando-spacing-sm;
  }

  // Tablet (>= 768px)
  @include bp-up(md) {
    display: flex;
    gap: $bookando-spacing-lg;
  }

  // Desktop (>= 1024px)
  @include bp-up(lg) {
    max-width: 1200px;
  }

  // Zwischen zwei Breakpoints
  @include bp-between(md, lg) {
    // Tablet-spezifische Styles
  }
}
```

### 2. **Fluid Typography mit `clamp()` nutzen**

Verwende die vordefinierten Font-Sizes – sie skalieren automatisch:

```scss
@use '@scss/variables' as *;

.heading {
  font-size: $bookando-font-size-3xl; // Skaliert automatisch von 30px bis 36px
}

.subheading {
  font-size: $bookando-font-size-xl; // Skaliert automatisch von 19px bis 23px
}
```

### 3. **Spacing responsive gestalten**

```scss
.card {
  // Responsive Padding (11-24px)
  padding: $bookando-spacing-md;

  // Responsive Gap (19-40px)
  gap: $bookando-spacing-lg;

  // Responsive Margin (32-64px)
  margin-bottom: $bookando-spacing-xl;
}
```

---

## 🔄 Migration Guide: Hardcodierte Werte ersetzen

### ❌ Vorher (Hardcodiert):
```vue
<style scoped>
.status-badge {
  background: #d1fae5;
  color: #065f46;
  padding: 2px 8px;
  font-size: 14px;
  border-radius: 4px;
}

.empty-state {
  padding: 3rem 1rem;
  text-align: center;
  color: #666;
}

.card {
  gap: 1rem;
  margin: 2rem 0;
}
</style>
```

### ✅ Nachher (Design-Tokens):
```vue
<template>
  <!-- Status-Badge durch AppBadge ersetzen -->
  <AppBadge variant="success-light" label="Aktiv" />

  <!-- Empty-State durch AppEmptyState ersetzen -->
  <AppEmptyState
    icon="inbox"
    title="Keine Daten"
    description="Es wurden noch keine Einträge gefunden."
  />
</template>

<style scoped lang="scss">
@use '@scss/variables' as *;

.card {
  gap: $bookando-spacing-md;
  margin: $bookando-spacing-lg 0;
}
</style>
```

### Oder mit CSS Custom Properties:
```vue
<style scoped>
.status-badge {
  background: var(--bookando-success-light);
  color: var(--bookando-success-dark);
  padding: var(--bookando-spacing-xxs) var(--bookando-spacing-xs);
  font-size: var(--bookando-font-size-sm);
  border-radius: var(--bookando-radius-xs);
}
</style>
```

---

## 🎯 Identifizierte Refactoring-Opportunities

### Häufig verwendete hardcodierte Werte:

#### **Farben:**
- `#fff`, `#f0f0f0`, `#f6f6f6` → `$bookando-white`, `$bookando-bg-light`
- `#666`, `#adb5bd` → `$bookando-text-muted`
- `#d1fae5` → `$bookando-success-light`
- `#fee2e2` → `$bookando-danger-light`
- `#fef3c7` → `$bookando-warning-light`
- `#e3f2fd` → `$bookando-info-light`

#### **Spacing:**
- `gap: 1rem` → `gap: $bookando-spacing-md`
- `padding: 2rem` → `padding: $bookando-spacing-lg`
- `margin: 3rem 1rem` → `margin: $bookando-spacing-xl $bookando-spacing-md`
- `gap: 8px` → `gap: $bookando-spacing-xs`
- `padding: 12px 14px` → `padding: $bookando-spacing-sm`

#### **Font-Sizes:**
- `font-size: 14px` → `font-size: $bookando-font-size-sm`
- `font-size: 16px` → `font-size: $bookando-font-size-base`
- `font-size: 24px` → `font-size: $bookando-font-size-2xl`
- `font-size: 32px` → `font-size: $bookando-font-size-3xl`
- `font-size: 48px` → `font-size: $bookando-font-size-4xl`

---

## 📦 Komponenten mit vielen hardcodierten Werten

Diese Komponenten sollten priorisiert refactored werden:

1. **`modules/tools/assets/vue/components/AppointmentsTab.vue`**
   - Viele hardcodierte Status-Farben
   - Spacing-Werte
   - Font-Sizes

2. **`modules/tools/assets/vue/components/TimeTrackingTab.vue`**
   - Hardcodierte Farben für Zeitstatus
   - Spacing

3. **`modules/tools/assets/vue/components/CalendarTab.vue`**
   - Hardcodierte Spacing & Farben
   - Responsive Breakpoints

4. **`modules/tools/assets/vue/components/DesignTab.vue`**
   - Viele hardcodierte Font-Sizes
   - Farben

5. **`Core/Design/components/AppRichTextField.vue`**
   - Hardcodierte Editor-Styles

---

## 🛠 Refactoring-Strategie

### Phase 1: Neue Komponenten verwenden
1. Alle Status-Badges durch `<AppBadge>` ersetzen
2. Alle Empty-States durch `<AppEmptyState>` ersetzen

### Phase 2: Farben standardisieren
1. Alle `#fff` → `$bookando-white` oder `var(--bookando-white)`
2. Alle Grautöne → Gray-Scale-Variablen
3. Alle Status-Farben → Status-Color-Varianten

### Phase 3: Spacing standardisieren
1. Alle hardcodierten `gap`-Werte
2. Alle hardcodierten `padding`-Werte
3. Alle hardcodierten `margin`-Werte

### Phase 4: Typography standardisieren
1. Alle hardcodierten `font-size`-Werte
2. Alle hardcodierten `line-height`-Werte

### Phase 5: Responsive Design optimieren
1. Media Queries durch Mixins ersetzen
2. Fixed Sizes durch Fluid Typography ersetzen
3. Breakpoint-basierte Layouts optimieren

---

## 📱 Responsive Design Checkliste

Für jede Komponente prüfen:

- [ ] **Desktop (>= 1024px):** Optimales Layout, volle Features
- [ ] **Tablet Querformat (768px - 1023px):** Kompakteres Layout
- [ ] **Tablet Hochformat (480px - 767px):** Einspaltiges Layout wo sinnvoll
- [ ] **Mobile Querformat (480px - 767px):** Touch-optimierte Buttons
- [ ] **Mobile Hochformat (< 480px):** Stack-Layout, große Touch-Targets

### Häufige Probleme vermeiden:
- ❌ Abgeschnittene Texte bei kleinen Screens
- ❌ Zu kleine Touch-Targets (< 44px)
- ❌ Horizontales Scrolling auf Mobile
- ❌ Nicht lesbare Schriftgrößen (< 14px auf Mobile)
- ❌ Überlappende Elemente bei schmalen Viewports

---

## 🎨 Design-Prinzipien

1. **Mobile-First:** Starte mit mobilen Styles, erweitere für größere Screens
2. **Fluid Typography:** Nutze `clamp()` für automatisches Scaling
3. **Konsistentes Spacing:** Verwende die Spacing-Scale (xxxs bis 4xl)
4. **Semantische Farben:** Success, Warning, Danger, Info statt Grün, Gelb, Rot, Blau
5. **Komponenten statt Duplikation:** Wiederverwendbare Komponenten für häufige Patterns
6. **Accessibility:** Ausreichende Kontraste, Touch-Targets, Semantisches HTML

---

## 📚 Weiterführende Ressourcen

### SCSS-Dateien:
- `src/Core/Design/assets/scss/_tokens.scss` - Primitive Design-Tokens
- `src/Core/Design/assets/scss/_variables.scss` - Semantische Variablen
- `src/Core/Design/assets/scss/_custom-properties.scss` - CSS Custom Properties
- `src/Core/Design/assets/scss/_mixins.scss` - Wiederverwendbare Mixins
- `src/Core/Design/assets/scss/_utilities.scss` - Utility-Klassen-Generator

### Vue-Komponenten:
- `src/Core/Design/components/AppBadge.vue` - Badge-Komponente
- `src/Core/Design/components/AppEmptyState.vue` - Empty-State-Komponente
- `src/Core/Design/components/AppButton.vue` - Button-Komponente
- `src/Core/Design/components/AppIcon.vue` - Icon-Komponente

---

## 🚀 Nächste Schritte

1. **Dokumentation lesen** und verstehen
2. **Neue Komponenten** in eigenen Features testen
3. **Schrittweise Migration** bestehender Komponenten
4. **Responsive Tests** auf echten Geräten durchführen
5. **Feedback sammeln** und Design-System iterativ verbessern

---

## 📞 Support

Bei Fragen oder Problemen:
- Siehe `_tokens.scss` für alle verfügbaren Design-Tokens
- Siehe `_variables.scss` für semantische Aliase
- Siehe Komponenten-Dateien für Verwendungsbeispiele
- Nutze Browser DevTools um CSS Custom Properties zu inspizieren

---

**Version:** 1.0
**Letzte Aktualisierung:** 2025-11-19
**Autor:** Design System Team
