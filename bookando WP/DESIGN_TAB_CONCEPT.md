# Design Tab - Neues Konzept (Amelia-inspiriert)

## Problem-Analyse

Die aktuelle DesignTab.vue hat mehrere Probleme:

1. **Zu komplex**: Zwei separate Layoutstrukturen (`design-layout` und `booking-design-customizer`) sind verschachtelt
2. **Kein klares 3/7 Grid**: Layout folgt nicht dem gewünschten Muster
3. **Keine Template-Verwaltung**: Fehlt die Liste/Übersicht aller Vorlagen
4. **Verwirrende Struktur**: Template-Selector oben, dann Settings links und Preview rechts

## Neue Struktur (Amelia-Muster)

### Layout: 30% / 70% Grid

```
┌─────────────────────────────────────────────────────┐
│                 Header / Actions                     │
├──────────────┬──────────────────────────────────────┤
│              │                                       │
│   Template   │        Template Editor /              │
│   Liste      │        Preview                        │
│   (30%)      │        (70%)                          │
│              │                                       │
│  ┌────────┐  │  Bei Auswahl: Editor                  │
│  │Vorlage1│  │  Bei Hover: Preview                   │
│  └────────┘  │                                       │
│  ┌────────┐  │  Bei "Neu": Formular                  │
│  │Vorlage2│  │  - Name eingeben                      │
│  └────────┘  │  - Bereich wählen                     │
│              │    (Services/Courses/Events/etc)      │
│              │                                       │
└──────────────┴──────────────────────────────────────┘
```

### Komponenten-Struktur

```vue
<template>
  <div class="design-customizer">
    <!-- Header -->
    <div class="design-customizer-header">
      <h2>Design Vorlagen</h2>
      <button @click="createNew">+ Neue Vorlage</button>
    </div>

    <!-- Main Grid 3/7 -->
    <div class="design-customizer-body">

      <!-- Left: Template List (30%) -->
      <div class="template-list">
        <div
          v-for="template in templates"
          :key="template.id"
          class="template-card"
          :class="{ active: selectedTemplate?.id === template.id }"
          @click="selectTemplate(template)"
          @mouseenter="previewTemplate(template)"
        >
          <div class="template-card-header">
            <h4>{{ template.name }}</h4>
            <span class="template-badge">{{ getAreaLabel(template.area) }}</span>
          </div>
          <div class="template-card-actions">
            <button @click.stop="editTemplate(template)">Bearbeiten</button>
            <button @click.stop="deleteTemplate(template)">Löschen</button>
          </div>
        </div>
      </div>

      <!-- Right: Editor/Preview (70%) -->
      <div class="template-editor">
        <!-- Wenn neu/bearbeiten -->
        <div v-if="editorMode === 'edit'" class="template-form">
          <div class="form-row">
            <AppInputText
              v-model="currentTemplate.name"
              label="Vorlagenname"
            />
            <AppSelect
              v-model="currentTemplate.area"
              label="Bereich"
              :options="areaOptions"
            />
          </div>
          <!-- Weitere Design-Einstellungen -->
        </div>

        <!-- Wenn preview -->
        <div v-else-if="editorMode === 'preview'" class="template-preview">
          <!-- Live-Preview der Vorlage -->
        </div>

        <!-- Default -->
        <div v-else class="template-empty">
          <p>Wähle eine Vorlage aus oder erstelle eine neue</p>
        </div>
      </div>

    </div>
  </div>
</template>
```

### CSS-Struktur

```scss
.design-customizer {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 200px);
  background: #f9fafb;
}

.design-customizer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: white;
  border-bottom: 1px solid #e5e7eb;
}

.design-customizer-body {
  display: grid;
  grid-template-columns: 3fr 7fr; /* 30% / 70% */
  gap: 1.5rem;
  flex: 1;
  overflow: hidden;
  padding: 1.5rem;
}

.template-list {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.template-card {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    border-color: #4F46E5;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  &.active {
    background: #EEF2FF;
    border-color: #4F46E5;
  }
}

.template-editor {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  overflow-y: auto;
}
```

### Datenstruktur

```typescript
interface Template {
  id: string
  name: string
  area: 'services' | 'courses' | 'events' | 'employee_panel' | 'customer_panel'
  settings: {
    colors: {}
    fonts: {}
    layout: {}
    // etc.
  }
  createdAt: string
  updatedAt: string
}

const areaOptions = [
  { value: 'services', label: 'Dienstleistungen' },
  { value: 'courses', label: 'Kurse' },
  { value: 'events', label: 'Events' },
  { value: 'employee_panel', label: 'Mitarbeiter Panel' },
  { value: 'customer_panel', label: 'Kunden Panel' }
]
```

## Vorteile der neuen Struktur

1. ✅ **Übersichtlich**: Klare Trennung Liste / Editor
2. ✅ **Amelia-Pattern**: Folgt bewährtem UX-Muster
3. ✅ **3/7 Grid**: Nutzt `grid-template-columns: 3fr 7fr`
4. ✅ **Template-Verwaltung**: Alle Vorlagen auf einen Blick
5. ✅ **Einfacher Workflow**: Name + Bereich → dann Design anpassen
6. ✅ **Responsive**: Grid kann auf mobile stack werden

## Implementierungs-Schritte

1. Alte komplexe Struktur entfernen
2. Neue 3/7 Grid-Struktur aufbauen
3. Template-Liste Component erstellen
4. Template-Editor Component erstellen
5. Template-Verwaltung (CRUD) implementieren
6. Preview-Funktionalität hinzufügen
7. CSS optimieren
