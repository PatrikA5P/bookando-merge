# Bookando - Customer & Employee Module Modernisierung

## 📋 Übersicht

Dieser Guide beschreibt die Modernisierung der Customer- und Employee-Module mit Fokus auf:
- ✅ Moderne, ansprechende Tabellen und Cards
- ✅ Verbesserte Checkboxen und Bulk-Actions
- ✅ Optimierte Bearbeiten-Buttons und Actions
- ✅ Stammdaten-Übersicht als Sidebar mit verbessertem Design
- ✅ Vollständig responsives Design (Desktop, Tablet, Mobile)

---

## 🎨 Neue Design-Tokens

### Module-spezifische Tokens (_tokens.scss)

```scss
// Sidebar / Preview Panel
$sidebar-resize-handle-width:     0.25rem;        // 4px
$sidebar-min-width:                17.5rem;       // 280px
$sidebar-max-width:                37.5rem;       // 600px
$sidebar-default-width:            20rem;         // 320px

// Page Layout
$page-header-height:               4rem;          // 64px
$page-toolbar-height:              3.5rem;        // 56px
$page-footer-spacer:               5.625rem;      // 90px (für Bulk Actions)

// Icons & Visual Elements
$icon-stat:                        2.5rem;        // 40px
$icon-activity:                    2rem;          // 32px
$icon-list:                        1.5rem;        // 24px
$icon-placeholder:                 4rem;          // 64px

// Grid Templates
$grid-stats-2col:                  repeat(2, 1fr);
$grid-stats-4col:                  repeat(4, 1fr);
$grid-content-2col:                2fr 1fr;
$grid-detail-row:                  7.5rem 1fr;   // 120px 1fr

// Overlays & Backdrops
$overlay-backdrop-opacity:         0.5;
$loader-backdrop-opacity:          0.6;

// Transitions
$transition-fast:                  0.15s;
$transition-normal:                0.2s;
$transition-medium:                0.3s;
$transition-slow:                  0.4s;

// Z-Index Layers
$z-index-sidebar:                  100;
$z-index-overlay:                  1000;
$z-index-modal:                    1050;
$z-index-bulk-action:              900;
$z-index-loader:                   9999;
```

---

## 🏗️ Neues Module-Layout-System

### Verfügbare Komponenten-Klassen

#### 1. **Grid-Systeme**

```scss
// Stats Grid (2 Spalten)
.bookando-grid--stats-2 {
  // Desktop: 2 Spalten
  // Mobile: 1 Spalte
}

// Stats Grid (4 Spalten)
.bookando-grid--stats-4 {
  // Desktop: 4 Spalten
  // Tablet: 2 Spalten
  // Mobile: 1 Spalte
}

// Content Grid (2 Spalten, asymmetrisch 2:1)
.bookando-grid--content {
  // Desktop: 2fr 1fr
  // Mobile: 1 Spalte
}

// Detail Row (Label + Value)
.bookando-grid--detail-row {
  // Desktop: 120px 1fr
  // Mobile: 1 Spalte
}
```

#### 2. **Stat Cards**

```vue
<div class="bookando-stat-card">
  <div class="bookando-stat-card__icon">
    <AppIcon name="users" />
  </div>
  <div class="bookando-stat-card__value">1,234</div>
  <div class="bookando-stat-card__label">Total Customers</div>
  <div class="bookando-stat-card__trend bookando-stat-card__trend--up">
    <AppIcon name="trending-up" size="xs" />
    <span>+12% this month</span>
  </div>
</div>
```

**Features:**
- Hover-Animation (translateY + shadow)
- Responsive Icon-Sizes
- Trend-Indikatoren (up, down, neutral)
- Automatisches Spacing

#### 3. **Activity Timeline**

```vue
<div class="bookando-activity-timeline">
  <div class="bookando-activity-item">
    <div class="bookando-activity-item__icon">
      <AppIcon name="user-plus" />
    </div>
    <div class="bookando-activity-item__content">
      <div class="bookando-activity-item__title">Customer Created</div>
      <div class="bookando-activity-item__description">
        John Doe was added to the system
      </div>
      <div class="bookando-activity-item__timestamp">2 hours ago</div>
    </div>
  </div>
  <!-- Weitere Items -->
</div>
```

**Features:**
- Verbindungslinien zwischen Items
- Responsive Icon-Größen
- Automatische Zeitstempel-Formatierung

#### 4. **Preview Sidebar (Resizable)**

```vue
<div class="bookando-preview-sidebar" :style="{ width: `${sidebarWidth}px` }">
  <!-- Resize Handle -->
  <div
    class="bookando-preview-sidebar__resize-handle"
    :class="{ 'is-resizing': isResizing }"
    @mousedown="startResize"
  />

  <!-- Header (Sticky) -->
  <div class="bookando-preview-sidebar__header">
    <AppAvatar :src="item.avatar" size="md" />
    <div class="bookando-preview-sidebar__title">
      {{ item.name }}
      <div class="bookando-preview-sidebar__subtitle">#{{ item.id }}</div>
    </div>
    <AppButton icon="x" variant="ghost" @click="close" />
  </div>

  <!-- Content (Scrollable) -->
  <div class="bookando-preview-sidebar__content">
    <div class="bookando-preview-sidebar__section">
      <div class="bookando-preview-sidebar__section-title">Contact Info</div>
      <div class="bookando-detail-list">
        <!-- Detail Items -->
      </div>
    </div>
    <!-- Weitere Sections -->
  </div>

  <!-- Footer (Sticky) -->
  <div class="bookando-preview-sidebar__footer">
    <AppButton variant="primary" block @click="edit">Edit</AppButton>
  </div>
</div>
```

**Features:**
- Resizable mit visueller Handle-Animation
- Sticky Header & Footer
- Scrollbarer Content-Bereich
- Min/Max-Width-Constraints

#### 5. **Detail List (Label + Value)**

```vue
<div class="bookando-detail-list">
  <div class="bookando-detail-item">
    <div class="bookando-detail-item__label">Email</div>
    <div class="bookando-detail-item__value">john@example.com</div>
  </div>
  <div class="bookando-detail-item">
    <div class="bookando-detail-item__label">Phone</div>
    <div class="bookando-detail-item__value">+49 123 456789</div>
  </div>
</div>
```

#### 6. **Placeholder / Empty State**

```vue
<div class="bookando-placeholder">
  <div class="bookando-placeholder__icon">
    <AppIcon name="inbox" />
  </div>
  <div class="bookando-placeholder__title">No Appointments</div>
  <div class="bookando-placeholder__description">
    There are no upcoming appointments for this customer.
  </div>
  <AppButton variant="primary">Schedule Appointment</AppButton>
</div>

<!-- Compact Variant -->
<div class="bookando-placeholder bookando-placeholder--compact">
  <!-- Kleinere Sizes -->
</div>
```

---

## 📝 Modernisierungs-Beispiele

### Vorher vs. Nachher

#### **CustomerQuickPreview.vue**

##### ❌ Vorher (Hardcodiert):

```vue
<style scoped>
.customer-quick-preview {
  min-width: 280px;
  max-width: 600px;
  background: #fff;
  border-left: 1px solid #e0e4e8;
}

.resize-handle {
  width: 4px;
  background: transparent;
  transition: background-color 0.2s;
}

.resize-handle:hover {
  background: #4F46E5;  /* ← Hardcoded Primary */
}

.preview-header {
  padding: 1.5rem;  /* ← Hardcoded 24px */
  border-bottom: 1px solid #f3f3f3;
}

.preview-title {
  font-size: 1.25rem;  /* ← Hardcoded 20px */
  font-weight: 600;
  color: #23272f;
}

.preview-section {
  padding: 1.5rem;  /* ← Hardcoded */
  margin-bottom: 1.5rem;
}

.preview-section-title {
  font-size: 0.75rem;  /* ← Hardcoded 12px */
  text-transform: uppercase;
  color: #7b8794;
  margin-bottom: 1rem;
}

.detail-row {
  display: grid;
  grid-template-columns: 120px 1fr;  /* ← Hardcoded */
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.detail-label {
  font-size: 0.875rem;  /* ← Hardcoded 14px */
  color: #7b8794;
}

.detail-value {
  font-size: 1rem;  /* ← Hardcoded 16px */
  color: #23272f;
}
</style>
```

##### ✅ Nachher (Design-Tokens):

```vue
<template>
  <div
    class="bookando-preview-sidebar"
    :style="{ width: `${width}px` }"
  >
    <!-- Resize Handle -->
    <div
      class="bookando-preview-sidebar__resize-handle"
      :class="{ 'is-resizing': isResizing }"
      @mousedown="startResize"
    />

    <!-- Header -->
    <div class="bookando-preview-sidebar__header">
      <AppAvatar :src="customer.avatar" size="md" />
      <div>
        <div class="bookando-preview-sidebar__title">
          {{ customer.full_name }}
        </div>
        <div class="bookando-preview-sidebar__subtitle">
          #{{ customer.id }}
        </div>
      </div>
      <AppButton icon="x" variant="ghost" size="sm" @click="close" />
    </div>

    <!-- Content -->
    <div class="bookando-preview-sidebar__content">
      <!-- Contact Info Section -->
      <div class="bookando-preview-sidebar__section">
        <div class="bookando-preview-sidebar__section-title">
          Contact Info
        </div>
        <div class="bookando-detail-list">
          <div class="bookando-detail-item">
            <div class="bookando-detail-item__label">Email</div>
            <div class="bookando-detail-item__value">{{ customer.email }}</div>
          </div>
          <div class="bookando-detail-item">
            <div class="bookando-detail-item__label">Phone</div>
            <div class="bookando-detail-item__value">{{ customer.phone }}</div>
          </div>
        </div>
      </div>

      <!-- Next Appointments Section -->
      <div class="bookando-preview-sidebar__section">
        <div class="bookando-preview-sidebar__section-title">
          Next Appointments
        </div>
        <div v-if="upcomingAppointments.length" class="bookando-activity-timeline">
          <div
            v-for="appointment in upcomingAppointments"
            :key="appointment.id"
            class="bookando-activity-item"
          >
            <div class="bookando-activity-item__icon">
              <AppIcon name="calendar" />
            </div>
            <div class="bookando-activity-item__content">
              <div class="bookando-activity-item__title">
                {{ appointment.service_name }}
              </div>
              <div class="bookando-activity-item__description">
                {{ appointment.employee_name }}
              </div>
              <div class="bookando-activity-item__timestamp">
                {{ formatDate(appointment.start_time) }}
              </div>
            </div>
          </div>
        </div>
        <div v-else class="bookando-placeholder bookando-placeholder--compact">
          <div class="bookando-placeholder__icon">
            <AppIcon name="calendar" />
          </div>
          <div class="bookando-placeholder__title">No Appointments</div>
          <div class="bookando-placeholder__description">
            No upcoming appointments scheduled
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="bookando-preview-sidebar__footer">
      <AppButton variant="primary" block @click="edit">
        <AppIcon name="edit" />
        Edit Customer
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
// Keine hardcodierten Styles mehr nötig!
// Alle Styles kommen aus .bookando-preview-sidebar und .bookando-detail-* Klassen
</script>
```

**Verbesserungen:**
- ✅ Keine hardcodierten Pixel-Werte
- ✅ Verwendung von Design-Tokens via CSS-Klassen
- ✅ Konsistentes Spacing
- ✅ Wiederverwendbare Komponenten-Struktur
- ✅ Automatisch responsive

---

### **Stats Cards Modernisierung**

##### ❌ Vorher:

```vue
<div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
  <div class="stat-card" style="padding: 1.5rem; background: #fff; border-radius: 12px;">
    <div style="font-size: 2.5rem; color: #12DE9D;">👤</div>
    <div style="font-size: 2rem; font-weight: 700; color: #23272f;">1,234</div>
    <div style="font-size: 0.875rem; color: #7b8794;">Total Customers</div>
  </div>
</div>
```

##### ✅ Nachher:

```vue
<div class="bookando-grid--stats-2">
  <div class="bookando-stat-card">
    <div class="bookando-stat-card__icon">
      <AppIcon name="users" />
    </div>
    <div class="bookando-stat-card__value">1,234</div>
    <div class="bookando-stat-card__label">Total Customers</div>
    <div class="bookando-stat-card__trend bookando-stat-card__trend--up">
      <AppIcon name="trending-up" size="xs" />
      <span>+12%</span>
    </div>
  </div>

  <div class="bookando-stat-card">
    <div class="bookando-stat-card__icon">
      <AppIcon name="calendar" />
    </div>
    <div class="bookando-stat-card__value">456</div>
    <div class="bookando-stat-card__label">This Month</div>
    <div class="bookando-stat-card__trend bookando-stat-card__trend--down">
      <AppIcon name="trending-down" size="xs" />
      <span>-3%</span>
    </div>
  </div>
</div>
```

---

## 🎯 Implementierungs-Checkliste

### Phase 1: Sidebar-Komponenten ✅

- [x] Design-Tokens für Sidebars erweitert
- [x] `.bookando-preview-sidebar` System erstellt
- [x] `.bookando-detail-list` System erstellt
- [ ] `CustomerQuickPreview.vue` refactoren
- [ ] `EmployeeQuickPreview.vue` refactoren

### Phase 2: Stats & Cards

- [x] `.bookando-stat-card` System erstellt
- [x] `.bookando-grid--stats-*` Systeme erstellt
- [ ] Stats in `CustomerCard.vue` modernisieren
- [ ] Stats in `EmployeeCard.vue` modernisieren
- [ ] Stats in Mobile-Tables integrieren

### Phase 3: Activity Timeline

- [x] `.bookando-activity-timeline` System erstellt
- [ ] Activity in `CustomerQuickPreview` modernisieren
- [ ] Activity in `EmployeeQuickPreview` modernisieren

### Phase 4: Placeholder / Empty States

- [x] `.bookando-placeholder` System erstellt
- [ ] Empty States in Sidebars ersetzen
- [ ] Empty States in Mobile-Tables ersetzen

### Phase 5: Responsive Design

- [x] Mobile-first Breakpoints in allen Grid-Systemen
- [x] Responsive Typography via clamp()
- [ ] Touch-optimierte Buttons (min 44px)
- [ ] Mobile Sidebar-Verhalten testen

---

## 📱 Responsive Design Guidelines

### Breakpoints

```scss
$breakpoint-sm:  480px;   // Mobile Querformat
$breakpoint-md:  768px;   // Tablet
$breakpoint-lg:  1024px;  // Desktop
$breakpoint-xl:  1440px;  // Large Desktop
```

### Mobile-First Approach

```scss
// Standard (Mobile)
.bookando-grid--stats-4 {
  grid-template-columns: 1fr;
}

// Tablet
@include bp-up(md) {
  .bookando-grid--stats-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

// Desktop
@include bp-up(lg) {
  .bookando-grid--stats-4 {
    grid-template-columns: repeat(4, 1fr);
  }
}
```

### Touch-Targets

**Mindestgröße: 44x44px**

```scss
.touch-target {
  min-width: 2.75rem;   // 44px
  min-height: 2.75rem;  // 44px
}
```

### Responsive Spacing

Nutze fluid spacing mit clamp():

```scss
padding: $bookando-spacing-md;     // clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem)
gap: $bookando-spacing-lg;         // clamp(1.1875rem, 0.5rem + 2vw, 2.5rem)
```

---

## 🎨 Design-Patterns

### 1. **Hover-States für Interactive Elements**

```scss
.interactive-element {
  transition: all $transition-normal ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba($bookando-black, 0.08);
  }
}
```

### 2. **Focus-States für Accessibility**

```scss
.focusable {
  &:focus-visible {
    outline: 2px solid $bookando-primary;
    outline-offset: 2px;
  }
}
```

### 3. **Loading States**

```vue
<div v-if="loading" class="bookando-loader-backdrop">
  <AppLoader />
</div>
```

### 4. **Error States**

```vue
<div v-if="error" class="bookando-alert bookando-alert--danger">
  {{ error.message }}
</div>
```

---

## 🔄 Migration Guide

### Schritt-für-Schritt Refactoring

#### 1. **Import Design-Tokens**

```vue
<style lang="scss" scoped>
@use '@scss/variables' as *;
@use '@scss/mixins' as *;

// Jetzt kannst du Design-Tokens verwenden
</style>
```

#### 2. **Ersetze hardcodierte Werte**

```scss
// ❌ Vorher
.element {
  padding: 24px;
  font-size: 20px;
  color: #23272f;
  gap: 16px;
}

// ✅ Nachher
.element {
  padding: $bookando-spacing-lg;
  font-size: $bookando-font-size-xl;
  color: $bookando-text-dark;
  gap: $bookando-spacing-md;
}
```

#### 3. **Verwende BEM-Klassen**

```scss
// ❌ Vorher
<div class="preview-header">
  <div class="title">...</div>
  <div class="subtitle">...</div>
</div>

// ✅ Nachher
<div class="bookando-preview-sidebar__header">
  <div class="bookando-preview-sidebar__title">...</div>
  <div class="bookando-preview-sidebar__subtitle">...</div>
</div>
```

#### 4. **Nutze Utility-Klassen**

```vue
<!-- ❌ Vorher -->
<div style="display: flex; gap: 1rem; align-items: center;">

<!-- ✅ Nachher -->
<div class="bookando-flex-start bookando-gap-md">
```

---

## 🛠️ Utilities & Helper

### Flex-Utilities

```html
<div class="bookando-flex-center">Zentriert</div>
<div class="bookando-flex-between">Space Between</div>
<div class="bookando-flex-start">Start Aligned</div>
<div class="bookando-flex-column">Column</div>
```

### Gap-Utilities

```html
<div class="bookando-gap-xs">Extra Small Gap</div>
<div class="bookando-gap-sm">Small Gap</div>
<div class="bookando-gap-md">Medium Gap</div>
<div class="bookando-gap-lg">Large Gap</div>
<div class="bookando-gap-xl">Extra Large Gap</div>
```

### Text-Utilities

```html
<div class="bookando-truncate">Text wird abgeschnitten...</div>
```

---

## 📊 Vorteile der Modernisierung

### ✅ Konsistenz
- Einheitliches Design über alle Module hinweg
- Zentrale Design-Tokens erleichtern Updates
- BEM-Naming für klare Struktur

### ✅ Wartbarkeit
- Keine hardcodierten Werte mehr
- Wiederverwendbare Komponenten-Klassen
- Einfachere Anpassungen im Design-System

### ✅ Performance
- Weniger CSS-Code durch Wiederverwendung
- Optimierte Transitions und Animations
- Lazy Loading von Sections möglich

### ✅ Accessibility
- Focus-States für Keyboard-Navigation
- ARIA-Labels wo nötig
- Touch-Targets mindestens 44x44px

### ✅ Responsive Design
- Mobile-First Approach
- Fluid Typography
- Touch-optimierte Interaktionen

---

## 🚀 Nächste Schritte

1. **Sidebar-Komponenten refactoren**
   - CustomerQuickPreview.vue
   - EmployeeQuickPreview.vue

2. **Stats-Cards modernisieren**
   - CustomerCard.vue
   - EmployeeCard.vue

3. **Activity-Timeline integrieren**
   - In Sidebars
   - In Mobile-Tables

4. **Empty-States standardisieren**
   - Placeholder-Komponenten verwenden
   - Konsistente Icons und Texte

5. **Responsive Tests durchführen**
   - Desktop (1920x1080, 1440x900)
   - Tablet (iPad, Surface)
   - Mobile (iPhone, Android)

6. **Accessibility-Audit**
   - Keyboard-Navigation testen
   - Screen-Reader Kompatibilität
   - Kontrast-Verhältnisse prüfen

---

## 📞 Support & Fragen

Bei Fragen zur Verwendung des neuen Design-Systems:

1. Siehe `_module-layout.scss` für alle verfügbaren Klassen
2. Siehe `_tokens.scss` für alle Design-Tokens
3. Siehe dieses Dokument für Verwendungsbeispiele

---

**Version:** 1.0
**Letzte Aktualisierung:** 2025-11-19
**Autor:** Design System Team
