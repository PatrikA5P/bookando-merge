# Bookando CRM/HRM Module Redesign - Konzept

## 🎯 Vision

Transformation der Customer- und Employee-Module in ein modernes, intuitives CRM/HRM-System mit Fokus auf **schnellen Zugriff auf alle wichtigen Informationen** und **nahtlosen Workflows**.

---

## 📊 Analyse: Moderne CRM/HRM Best Practices

### Führende Systeme & Ihre Patterns

#### **Salesforce / HubSpot (CRM)**
- ✅ **Split-View:** Liste (30-40%) + Details (60-70%)
- ✅ **Inline Quick Actions:** Direkt in der Liste
- ✅ **Rich Activity Timeline:** Alle Interaktionen chronologisch
- ✅ **Tab-basierte Details:** Übersicht, Kontakte, Deals, Aufgaben
- ✅ **Quick Edit:** Inline-Editing in Details
- ✅ **Smart Filters:** Gespeicherte Views, Segmente

#### **BambooHR / Personio (HRM)**
- ✅ **Employee Card:** Foto + Key Info prominent
- ✅ **Status Indicators:** Verfügbar, Im Urlaub, Krank, etc.
- ✅ **Document Hub:** Zentrale Ablage für Dokumente
- ✅ **Time Off Overview:** Kalenderintegration
- ✅ **Org Chart Integration:** Hierarchie-Ansicht
- ✅ **Performance Tracking:** Timeline mit Meilensteinen

#### **Monday.com / Notion (Hybrid)**
- ✅ **Flexible Views:** Tabelle, Kanban, Kalender, Timeline
- ✅ **Keyboard Shortcuts:** Power-User-Features
- ✅ **Drag & Drop:** Intuitive Bedienung
- ✅ **Hover Actions:** Zusätzliche Informationen on Demand

---

## 🎨 Neues Design-Konzept

### Desktop-Version (≥1024px)

#### **Split-View Layout**

```
┌─────────────────────────────────────────────────────────────┐
│ Header: Customers / Add New / Filters / Search / Export    │
├───────────────────┬─────────────────────────────────────────┤
│                   │                                         │
│  LISTE (35%)      │  DETAIL-PANEL (65%)                    │
│                   │                                         │
│  ┌─────────────┐  │  ┌─────────────────────────────────┐  │
│  │ [✓] Avatar  │  │  │ Header: Avatar + Name + Actions │  │
│  │ John Doe    │◄─┼──┤ ─────────────────────────────── │  │
│  │ ✉ ☎ 📍      │  │  │ Tabs: Overview | Details | ... │  │
│  │ •••         │  │  │                                 │  │
│  └─────────────┘  │  │ Tab Content:                    │  │
│  ┌─────────────┐  │  │  ┌─────────────────────────┐   │  │
│  │ [ ] Avatar  │  │  │  │ Quick Stats Grid        │   │  │
│  │ Jane Smith  │  │  │  ├─────────────────────────┤   │  │
│  │ ✉ ☎ 📍      │  │  │  │ Contact Info            │   │  │
│  │ •••         │  │  │  ├─────────────────────────┤   │  │
│  └─────────────┘  │  │  │ Activity Timeline       │   │  │
│  ...              │  │  │  • Created customer     │   │  │
│                   │  │  │  • Booked appointment   │   │  │
│  [Showing 1-20]   │  │  │  • Updated profile      │   │  │
│  [Pagination]     │  │  └─────────────────────────┘   │  │
│                   │  └─────────────────────────────────┘  │
│                   │                                         │
└───────────────────┴─────────────────────────────────────────┘
│ Bulk Actions (slides up when items selected)               │
└─────────────────────────────────────────────────────────────┘
```

**Key Features:**
- **Resizable Split:** Nutzer kann Verhältnis anpassen (Min 25%, Max 50% für Liste)
- **Persistent Selection:** Ausgewählter Eintrag bleibt highlighted
- **Auto-Select First:** Beim Laden wird automatisch erster Eintrag ausgewählt
- **Keyboard Navigation:** Pfeiltasten zum Navigieren, Enter zum Bearbeiten
- **Quick Actions in Liste:** Hover zeigt Email, Call, Edit, Delete Icons
- **Live Updates:** Detail-Panel aktualisiert sich bei Änderungen

#### **Liste - Kompakt-Modus**

Jeder Listen-Eintrag zeigt:
```
┌──────────────────────────────────────┐
│ [✓] [Avatar]  John Doe          [•••] │
│              john@example.com         │
│              +49 123 456789           │
│              🇩🇪 Berlin • Active      │
└──────────────────────────────────────┘
```

**Hover-State:**
```
┌──────────────────────────────────────┐
│ [✓] [Avatar]  John Doe     [✉][☎][✎][🗑] │
│              john@example.com         │
│              +49 123 456789           │
│              🇩🇪 Berlin • Active      │
└──────────────────────────────────────┘
```

#### **Detail-Panel - Tab-Struktur**

**Tabs für Customers:**
1. **Overview** - Dashboard mit Key Metrics
2. **Contact & Personal** - Alle Kontaktdaten
3. **Appointments** - Historie & Upcoming
4. **Courses** - Aktive Kurse & Fortschritt
5. **Documents** - Verträge, Rechnungen
6. **Activity** - Vollständige Timeline

**Tabs für Employees:**
1. **Overview** - Dashboard mit Key Metrics
2. **Personal Info** - Kontakt, Adresse, etc.
3. **Schedule** - Arbeitszeiten, Verfügbarkeit
4. **Services** - Zugewiesene Dienstleistungen
5. **Time Off** - Urlaub, Krankheit
6. **Performance** - KPIs, Bewertungen
7. **Documents** - Verträge, Zertifikate

**Overview Tab - Layout:**
```
┌─────────────────────────────────────────────┐
│ Header: [Avatar] John Doe (#12345)          │
│         john@example.com • +49 123 456789   │
│         [Edit] [Delete] [More ▼]            │
├─────────────────────────────────────────────┤
│ Quick Stats (2x2 Grid)                      │
│  ┌─────────────┐  ┌─────────────┐          │
│  │ 24 Appts    │  │ 3 Courses   │          │
│  │ This Month  │  │ Active      │          │
│  └─────────────┘  └─────────────┘          │
│  ┌─────────────┐  ┌─────────────┐          │
│  │ €2,400      │  │ 98% Attend. │          │
│  │ Revenue     │  │ Rate        │          │
│  └─────────────┘  └─────────────┘          │
├─────────────────────────────────────────────┤
│ Recent Activity (Timeline)                  │
│  • 2h ago: Booked appointment with Sarah   │
│  • 1d ago: Completed "Yoga Basics"         │
│  • 3d ago: Updated phone number            │
│  [View All Activity →]                      │
├─────────────────────────────────────────────┤
│ Upcoming Appointments                       │
│  📅 Today, 14:00 - Massage (Sarah)         │
│  📅 Tomorrow, 10:00 - Yoga (Lisa)          │
│  [View All Appointments →]                  │
└─────────────────────────────────────────────┘
```

---

### Tablet-Version (768px - 1023px)

**Zwei Modi:**

#### **Modus 1: Split-View (Landscape)**
- Liste: 40% (links)
- Details: 60% (rechts)
- Slide-out Drawer für Filter
- Touch-optimierte Buttons (min 44x44px)

#### **Modus 2: Full-Screen Toggle (Portrait)**
- Liste nimmt vollen Bildschirm ein
- Tap auf Eintrag → Slide-in Detail-Panel von rechts
- Swipe nach links schließt Details
- Floating Action Button für "Add New"

```
Landscape:
┌──────────┬──────────────────┐
│ Liste    │ Detail-Panel     │
│ (40%)    │ (60%)            │
│          │                  │
└──────────┴──────────────────┘

Portrait:
┌───────────────┐    ┌───────────────┐
│ Liste         │ →  │ Detail-Panel  │
│ (Fullscreen)  │    │ (Slide-in)    │
│               │ ←  │               │
│        [+]    │    │ [✕]           │
└───────────────┘    └───────────────┘
```

---

### Mobile-Version (<768px)

**Card-basierte Liste mit Swipe-Actions**

```
┌─────────────────────────────────────┐
│ Search: [🔍 Search customers...]    │
│ Filters: [All ▼] [Active ▼] [🔄]   │
├─────────────────────────────────────┤
│ ╔═══════════════════════════════╗   │
│ ║ [Avatar] John Doe        [>] ║   │ ← Swipe
│ ║ john@example.com             ║   │
│ ║ +49 123 456789               ║   │
│ ║ Active • Last visit: 2d ago  ║   │
│ ╚═══════════════════════════════╝   │
│                                     │
│ ╔═══════════════════════════════╗   │
│ ║ [Avatar] Jane Smith      [>] ║   │
│ ║ jane@example.com             ║   │
│ ║ +49 987 654321               ║   │
│ ║ Active • Last visit: 1w ago  ║   │
│ ╚═══════════════════════════════╝   │
│                                     │
│ [Load More...]                      │
└─────────────────────────────────────┘

Swipe Left:
╔════════════════════════╦═══╦═══╦═══╗
║ [Avatar] John Doe      ║ ✉ ║ ☎ ║ 🗑 ║
║ john@example.com       ║   ║   ║   ║
╚════════════════════════╩═══╩═══╩═══╝
```

**Tap auf Card → Fullscreen Detail-View:**
```
┌─────────────────────────────────────┐
│ [<] John Doe              [Edit] [•]│
├─────────────────────────────────────┤
│ [Avatar - Groß]                     │
│ john@example.com                    │
│ +49 123 456789                      │
│ 🇩🇪 Berlin                          │
├─────────────────────────────────────┤
│ Tabs: [Overview][Appointments]...   │
├─────────────────────────────────────┤
│ Tab Content (Scrollable)            │
│                                     │
│ [Quick Stats]                       │
│ [Recent Activity]                   │
│ [Upcoming Appointments]             │
│                                     │
└─────────────────────────────────────┘
```

**Swipe-Actions:**
- **Swipe Left:** Zeigt Quick Actions (Email, Call, Delete)
- **Swipe Right:** Quick-Edit (z.B. Status ändern)
- **Long Press:** Multi-Select Modus aktivieren

---

## 🎨 Design-Spezifikationen

### Farb-System

**Liste:**
- **Selected Item:** Primary-Light Background (#E3FBF3)
- **Hover:** Gray-100 Background (#f3f4f6)
- **Active Border:** 3px Primary Left Border
- **Checkbox:** Primary when selected

**Detail-Panel:**
- **Header Background:** White mit Bottom Border
- **Tab Active:** Primary Underline (3px)
- **Tab Inactive:** Transparent
- **Section Separators:** Gray-200 Border

**Status-Badges:**
- **Active:** Success-Light (#d1fae5) + Success-Dark Text (#065f46)
- **Inactive:** Gray-200 (#e5e7eb) + Gray-600 Text (#4b5563)
- **Blocked:** Danger-Light (#fee2e2) + Danger-Dark Text (#991b1b)
- **Pending:** Warning-Light (#fef3c7) + Warning-Dark Text (#92400e)

### Typography

**Liste:**
- **Name:** Font-Size-Base (16px), Font-Weight-Semi-Bold
- **Email/Phone:** Font-Size-SM (14px), Font-Weight-Normal, Text-Muted
- **Meta Info:** Font-Size-XS (12px), Text-Muted

**Detail-Panel:**
- **Header Name:** Font-Size-2XL (24-30px), Font-Weight-Bold
- **Tab Labels:** Font-Size-Base (16px), Font-Weight-Medium
- **Section Titles:** Font-Size-SM (14px), Font-Weight-Semi-Bold, Uppercase
- **Content:** Font-Size-Base (16px)

### Spacing

**Liste:**
- **Item Padding:** Spacing-MD (16-24px)
- **Item Gap:** Spacing-SM (8-13px)
- **Avatar-to-Text:** Spacing-MD

**Detail-Panel:**
- **Header Padding:** Spacing-LG (24-40px)
- **Tab Padding:** Spacing-MD horizontal, Spacing-SM vertical
- **Section Spacing:** Spacing-XL (32-64px) between sections
- **Card Padding:** Spacing-LG

### Animations

**Transitions:**
- **Item Selection:** 200ms ease background color
- **Tab Switch:** 300ms ease slide
- **Detail-Panel Slide-in (Tablet):** 400ms cubic-bezier(0.4, 0, 0.2, 1)
- **Swipe Actions:** 250ms ease transform
- **Hover States:** 150ms ease

**Micro-Interactions:**
- **Checkbox:** Scale animation on toggle
- **Buttons:** Subtle lift on hover (translateY -2px + shadow)
- **Cards:** Gentle bounce on tap (Mobile)

---

## 🎯 Key Features

### 1. **Smart Auto-Select**
- Beim Laden der View wird automatisch der erste Eintrag ausgewählt
- Details werden sofort angezeigt
- Bei Klick auf anderen Eintrag: Smooth transition

### 2. **Keyboard Shortcuts**
- **↑/↓:** Navigieren in Liste
- **Enter:** Detail-Panel öffnen / Bearbeiten starten
- **Esc:** Bearbeiten abbrechen / Zurück zur Liste
- **Cmd/Ctrl + K:** Quick Search
- **Cmd/Ctrl + N:** Neuer Eintrag
- **Cmd/Ctrl + E:** Bearbeiten
- **Cmd/Ctrl + D:** Löschen (mit Confirm)

### 3. **Quick Actions in Liste**
- **Hover:** Zeigt Action-Icons (Email, Call, Edit, Delete)
- **Icons sind immer an gleicher Position** für muscle memory
- **Click:** Öffnet entsprechende Aktion (Mail-Client, Tel-Dialog, Edit-Modal)

### 4. **Rich Activity Timeline**
- **Chronologisch:** Neueste zuerst
- **Gruppiert:** Nach Tag/Woche
- **Icons:** Unterschiedliche Icons für Event-Typen
- **Interaktiv:** Klick auf Event zeigt Details
- **Filter:** "Alle", "Buchungen", "Änderungen", "Kommunikation"

### 5. **Inline Quick Edit**
- **Click auf Feld im Detail-Panel:** Wird zu Input
- **Auto-Save:** Nach 1 Sekunde Inaktivität
- **Undo-Option:** Toast mit "Rückgängig"-Button
- **Validation:** Inline-Fehler direkt am Feld

### 6. **Smart Filters & Saved Views**
- **Preset Filters:** "Alle", "Aktiv", "Inaktiv", "Neu (7 Tage)", "VIP"
- **Custom Filters:** Nutzer kann eigene Filter speichern
- **Filter Badges:** Zeigen aktive Filter
- **Quick Clear:** Ein Klick zum Zurücksetzen

### 7. **Bulk Operations**
- **Select All:** Checkbox in Header
- **Partial Selection:** Zeigt "15 von 245 ausgewählt"
- **Bulk Actions:** Export, Delete, Status ändern, Email senden
- **Progress Indicator:** Bei langen Operations
- **Undo-Option:** Nach Bulk-Delete

### 8. **Search & Filter**
- **Instant Search:** Live-Filtering während Tippen
- **Fuzzy Search:** Fehlertolerante Suche
- **Multi-Field:** Sucht in Name, Email, Phone, Adresse
- **Search Chips:** Zeigt Suchbegriffe als entfernbare Chips
- **Recent Searches:** Dropdown mit letzten 5 Suchen

---

## 📱 Responsive Behavior

### Breakpoint-Strategie

```scss
// Mobile First
.crm-view {
  // Mobile: Card Stack
  display: flex;
  flex-direction: column;

  @media (min-width: 768px) {
    // Tablet Portrait: Slide-in Details
    &.portrait { ... }

    // Tablet Landscape: Split-View
    &.landscape {
      flex-direction: row;
      .list { width: 40%; }
      .details { width: 60%; }
    }
  }

  @media (min-width: 1024px) {
    // Desktop: Always Split-View
    flex-direction: row;
    .list { width: 35%; }
    .details { width: 65%; }
  }

  @media (min-width: 1440px) {
    // Large Desktop: More breathing room
    .list { width: 30%; }
    .details { width: 70%; }
  }
}
```

### Touch Optimization

**Mobile & Tablet:**
- **Touch Targets:** Min 44x44px (Apple HIG)
- **Swipe Gestures:** Left/Right für Quick Actions
- **Pull to Refresh:** Am Anfang der Liste
- **Infinite Scroll:** Automatisches Nachladen
- **Bottom Navigation:** Quick Actions als FAB (Floating Action Button)

---

## 🔄 State Management

### Selection State
```typescript
interface SelectionState {
  selectedId: string | null          // Aktuell ausgewählter Eintrag
  selectedIds: Set<string>            // Multi-Select (Bulk)
  isDetailOpen: boolean               // Detail-Panel sichtbar (Mobile/Tablet)
  lastSelectedId: string | null       // Für Keyboard-Navigation
}
```

### View State
```typescript
interface ViewState {
  viewMode: 'list' | 'split' | 'detail'  // Aktueller Modus
  listWidth: number                       // Prozent (25-50%)
  activeTab: string                       // Aktiver Tab im Detail-Panel
  expandedSections: Set<string>           // Aufgeklappte Sections
}
```

### Filter State
```typescript
interface FilterState {
  searchQuery: string
  activeFilters: Map<string, any>
  savedViews: SavedView[]
  sortBy: string
  sortOrder: 'asc' | 'desc'
}
```

---

## 🎨 Component Architecture

### Neue Komponenten

1. **`CRMSplitView.vue`** - Master-Layout
2. **`CRMListPanel.vue`** - Liste mit Items
3. **`CRMListItem.vue`** - Einzelner Listen-Eintrag
4. **`CRMDetailPanel.vue`** - Detail-Ansicht mit Tabs
5. **`CRMDetailHeader.vue`** - Header mit Avatar + Actions
6. **`CRMActivityTimeline.vue`** - Activity Feed
7. **`CRMQuickStats.vue`** - Stats Grid
8. **`CRMTabContent.vue`** - Tab-basierter Content
9. **`CRMQuickActions.vue`** - Hover/Swipe Actions
10. **`CRMBulkActions.vue`** - Bulk-Action-Toolbar

### Wiederverwendung bestehender Komponenten

- ✅ `AppAvatar` - für Profilbilder
- ✅ `AppBadge` - für Status-Badges (mit neuen Varianten)
- ✅ `AppButton` - für alle Actions
- ✅ `AppIcon` - für Icons
- ✅ `AppTabs` - für Tab-Navigation
- ✅ `AppEmptyState` - für leere Zustände
- ✅ Neue Module-Layout-Klassen - für Grid-Systeme

---

## ✅ Implementation Roadmap

### Phase 1: Foundation (High Priority)
- [ ] Erstelle CRM Split-View Layout-System (SCSS)
- [ ] Erstelle CRMSplitView Master-Komponente
- [ ] Erstelle CRMListPanel + CRMListItem
- [ ] Erstelle CRMDetailPanel mit Tab-System

### Phase 2: Detail-Features
- [ ] Implementiere CRMDetailHeader
- [ ] Implementiere CRMActivityTimeline
- [ ] Implementiere CRMQuickStats
- [ ] Implementiere Quick Actions (Hover)

### Phase 3: Interactions
- [ ] Auto-Select first item
- [ ] Keyboard Navigation
- [ ] Inline Quick Edit
- [ ] Resizable Split

### Phase 4: Mobile Optimization
- [ ] Swipe Actions
- [ ] Slide-in Detail-Panel
- [ ] Pull to Refresh
- [ ] Touch-optimierte Buttons

### Phase 5: Advanced Features
- [ ] Smart Filters
- [ ] Saved Views
- [ ] Bulk Operations
- [ ] Undo/Redo

### Phase 6: Polish
- [ ] Animations & Transitions
- [ ] Loading States
- [ ] Error States
- [ ] Empty States

---

## 🎯 Success Metrics

**User Experience:**
- ⏱ **Time to Info:** < 1 Sekunde von Klick bis Details sichtbar
- 🖱 **Clicks to Action:** Max 2 Klicks für häufige Actions
- ⌨️ **Keyboard Efficiency:** Alle Actions per Shortcut erreichbar

**Performance:**
- ⚡ **Initial Load:** < 2 Sekunden
- 🔄 **Filter/Search:** < 500ms Response-Zeit
- 📱 **Mobile Score:** > 95 (Lighthouse)

**Accessibility:**
- ♿ **WCAG 2.1 AA:** Vollständig compliant
- ⌨️ **Keyboard Navigation:** 100% ohne Maus bedienbar
- 🔊 **Screen Reader:** Alle Inhalte zugänglich

---

**Version:** 1.0
**Status:** Ready for Implementation
**Author:** Design System Team
