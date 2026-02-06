# 📋 Module Structure Standard - Tab-per-File Pattern

> **WICHTIG FÜR ALLE KIs (AI Studio, ChatGPT, Claude, etc.):**
> Dieser Standard ist **VERBINDLICH** für alle Arbeiten an diesem Projekt.
> Jede Änderung an Modulen MUSS diesem Standard folgen.

---

## 🎯 Kern-Prinzip: Ein Tab = Eine Datei

**Grundregel:** Jeder Tab innerhalb eines Moduls erhält seine **eigene separate Datei**.

### ❌ NICHT SO (Monolithisch):
```typescript
// modules/MyModule.tsx - 3000+ Zeilen
const MyModule = () => {
  // Tab 1 inline (300 Zeilen)
  const Tab1 = () => { /* ... */ }

  // Tab 2 inline (400 Zeilen)
  const Tab2 = () => { /* ... */ }

  // Tab 3 inline (500 Zeilen)
  const Tab3 = () => { /* ... */ }

  return (
    <ModuleLayout>
      {activeTab === 'tab1' && <Tab1 />}
      {activeTab === 'tab2' && <Tab2 />}
      {activeTab === 'tab3' && <Tab3 />}
    </ModuleLayout>
  );
};
```

### ✅ SO IST ES RICHTIG (Modular):
```typescript
// modules/MyModule.tsx - ~200 Zeilen
import Tab1 from './MyModule/tabs/Tab1';
import Tab2 from './MyModule/tabs/Tab2';
import Tab3 from './MyModule/tabs/Tab3';

const MyModule = () => {
  return (
    <ModuleLayout>
      {activeTab === 'tab1' && <Tab1 />}
      {activeTab === 'tab2' && <Tab2 />}
      {activeTab === 'tab3' && <Tab3 />}
    </ModuleLayout>
  );
};
```

---

## 📁 Standard Verzeichnisstruktur

Jedes Modul MUSS diese Struktur verwenden:

```
modules/
└── ModuleName/
    ├── tabs/               # Alle Tab-Komponenten
    │   ├── Tab1.tsx
    │   ├── Tab2.tsx
    │   └── Tab3.tsx
    │
    ├── components/         # Wiederverwendbare Komponenten
    │   ├── Modal.tsx
    │   └── SubComponent.tsx
    │
    ├── editors/           # Editor-Komponenten (falls benötigt)
    │   ├── ItemEditor.tsx
    │   └── FormEditor.tsx
    │
    └── types.ts           # Shared TypeScript Types
```

---

## 📚 Beispiele aus dem Projekt

### Beispiel 1: Offers-Modul

**Vorher:** 3,089 Zeilen in einer Datei
**Nachher:** 418 Zeilen + 9 separate Komponenten

```
modules/Offers/
├── tabs/
│   ├── CatalogTab.tsx          # Services-Katalog
│   ├── CategoriesTab.tsx       # Kategorien-Verwaltung
│   ├── TagsTab.tsx             # Tags
│   ├── ExtrasTab.tsx           # Upsells
│   ├── DynamicPricingTab.tsx   # Preisstrategien
│   ├── BookingFormsTab.tsx     # Formulare
│   ├── BundlesTab.tsx          # Pakete
│   └── VouchersTab.tsx         # Gutscheine
│
├── components/
│   └── OfferModal.tsx          # Modal für Offer-Bearbeitung
│
└── types.ts                    # ModalTab, OfferModalProps
```

### Beispiel 2: Academy-Modul

**Vorher:** 2,305 Zeilen in einer Datei
**Nachher:** 272 Zeilen + 9 separate Komponenten

```
modules/Academy/
├── tabs/
│   ├── CoursesTab.tsx          # Kurs-Katalog
│   ├── LessonsTab.tsx          # Lektionen-Verwaltung
│   ├── BadgesTab.tsx           # Badge-System
│   └── CardsTab.tsx            # Education Cards
│
├── editors/
│   ├── CourseEditor.tsx        # Kurs-Editor
│   ├── LessonEditor.tsx        # Lektionen-Editor
│   ├── EducationCardEditor.tsx # Card-Editor
│   └── QuizEditor.tsx          # Quiz-Editor
│
└── components/
    └── GroupManagerModal.tsx   # Gruppen-Modal
```

---

## 🔧 Implementierungs-Leitfaden für KIs

### Schritt 1: Analyse
```
1. Modul-Datei öffnen (z.B. modules/NewModule.tsx)
2. Alle inline Tab-Komponenten identifizieren
3. Alle inline Modals/Editoren identifizieren
4. Shared Types identifizieren
5. Zeilenzahl notieren (Baseline)
```

### Schritt 2: Verzeichnisse erstellen
```bash
mkdir -p modules/ModuleName/{tabs,components,editors}
```

### Schritt 3: Komponenten extrahieren

Für jeden Tab:
```typescript
// modules/ModuleName/tabs/TabName.tsx

import React from 'react';
import { Icon1, Icon2 } from 'lucide-react';
import { useApp } from '../../../context/AppContext';
import { RequiredType } from '../../../types';

interface TabNameProps {
  // Props die vom Hauptmodul übergeben werden
  data: any[];
  onAction: (id: string) => void;
}

const TabName: React.FC<TabNameProps> = ({ data, onAction }) => {
  // Exakt die gleiche Logik wie vorher
  // KEINE Änderungen am Design oder Funktionalität!

  return (
    <div>
      {/* Original JSX */}
    </div>
  );
};

export default TabName;
```

### Schritt 4: Haupt-Modul anpassen
```typescript
// modules/ModuleName.tsx

// Imports hinzufügen
import Tab1 from './ModuleName/tabs/Tab1';
import Tab2 from './ModuleName/tabs/Tab2';

const ModuleName: React.FC = () => {
  // State und Logik bleiben gleich

  return (
    <ModuleLayout>
      {/* Tab JSX durch Komponente ersetzen */}
      {activeTab === 'tab1' && (
        <Tab1
          data={filteredData}
          onAction={handleAction}
        />
      )}
    </ModuleLayout>
  );
};
```

### Schritt 5: Types extrahieren (falls vorhanden)
```typescript
// modules/ModuleName/types.ts

/**
 * Shared Types for ModuleName Module
 */

export type TabType = 'tab1' | 'tab2' | 'tab3';

export interface ModalProps {
  mode: 'create' | 'edit';
  onClose: () => void;
  onSave: (data: any) => void;
}
```

---

## ⚠️ KRITISCHE REGELN (NIEMALS BRECHEN!)

### 1. 🔒 100% Funktionalität erhalten
```
❌ NICHT: "Ich vereinfache die Logik"
✅ RICHTIG: Exakt gleiche Logik kopieren
```

### 2. 🎨 Kein Design ändern
```
❌ NICHT: className="p-4" → className="p-6"
✅ RICHTIG: Exakt gleiche Klassen verwenden
```

### 3. 📦 Kein Inhalt verlieren
```
❌ NICHT: "Diese Funktion sieht ungenutzt aus"
✅ RICHTIG: Alles übernehmen, auch wenn es ungenutzt erscheint
```

### 4. 🏗️ Struktur einhalten
```
❌ NICHT: modules/ModuleName/Tab1.tsx
✅ RICHTIG: modules/ModuleName/tabs/Tab1.tsx
```

### 5. 📝 Immer Commits dokumentieren
```
git commit -m "refactor(module): Extrahiere TabName in separate Datei

- Erstelle modules/ModuleName/tabs/TabName.tsx
- Funktionalität 100% erhalten
- Design unverändert
- ModuleName.tsx reduziert von X auf Y Zeilen"
```

---

## 🎯 Wann diesen Standard anwenden?

### ✅ IMMER bei diesen Situationen:

1. **Neue Module erstellen**
   - Von Anfang an Tab-per-File verwenden

2. **Bestehende Module erweitern**
   - Neue Tabs als separate Dateien
   - Wenn Datei > 1,500 Zeilen: Refactoring durchführen

3. **Module überarbeiten**
   - Monolithische Struktur → Modulare Struktur

4. **Bugs in großen Dateien fixen**
   - Erst refactoren, dann Bug fixen

### ❌ AUSNAHMEN (sehr selten):

1. Sehr kleine Module (< 300 Zeilen total)
2. Single-Tab Module
3. Explizite Anweisung vom Entwickler

---

## 📊 Qualitätskriterien

Eine erfolgreiche Refaktorierung erfüllt:

- ✅ Datei-Reduktion: Hauptmodul -70% bis -90%
- ✅ Keine gebrochenen Funktionen
- ✅ Alle Tests bestehen (falls vorhanden)
- ✅ Kein TypeScript-Fehler
- ✅ Gleiche visuelle Darstellung
- ✅ Strukturierte Commits
- ✅ Dokumentation aktualisiert

---

## 🤖 Spezielle Hinweise für KI-Assistenten

### Wenn du gebeten wirst, an einem Modul zu arbeiten:

1. **Prüfe ZUERST die Zeilenzahl:**
   ```bash
   wc -l modules/ModuleName.tsx
   ```

2. **Wenn > 1,500 Zeilen:**
   - Schlage Refactoring VOR der eigentlichen Arbeit vor
   - "Ich sehe, dass ModuleName.tsx X Zeilen hat. Soll ich es zuerst nach dem Tab-per-File Standard refactoren?"

3. **Während der Extraktion:**
   - Nutze `Read` Tool für jeden Tab
   - Kopiere EXAKT - keine "Verbesserungen"
   - Prüfe nach jedem Tab: Funktioniert es noch?

4. **Nach dem Refactoring:**
   - Zeige Vorher/Nachher Statistik
   - Bestätige: "Alle Funktionen getestet - funktioniert ✓"

---

## 📖 Weiterführende Ressourcen

- **Beispiel-PR:** Siehe Commits in `claude/apply-design-system-template-lbq1V`
- **Offers-Refactoring:** modules/Offers/ (3089 → 418 Zeilen)
- **Academy-Refactoring:** modules/Academy/ (2305 → 272 Zeilen)

---

## ✍️ Template für neue Module

```typescript
// modules/NewModule.tsx (Haupt-Datei, ~200-300 Zeilen)

import React, { useState } from 'react';
import ModuleLayout from '../components/ModuleLayout';
import { getModuleDesign } from '../utils/designTokens';

// Tab-Imports
import Tab1 from './NewModule/tabs/Tab1';
import Tab2 from './NewModule/tabs/Tab2';

// Component-Imports
import ItemModal from './NewModule/components/ItemModal';

const NewModule: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'tab1' | 'tab2'>('tab1');
  const [data, setData] = useState([]);

  const moduleDesign = getModuleDesign('newmodule');

  const tabs = [
    { id: 'tab1', icon: Icon1, label: 'Tab 1' },
    { id: 'tab2', icon: Icon2, label: 'Tab 2' }
  ];

  return (
    <div className="flex flex-col min-h-full">
      <ModuleLayout
        moduleName="New Module"
        hero={{
          icon: Icon1,
          title: 'Module Title',
          description: 'Module description',
          gradient: moduleDesign.gradient
        }}
        tabs={tabs}
        activeTab={activeTab}
        onTabChange={setActiveTab}
        primaryAction={{
          label: 'Create',
          icon: Plus,
          onClick: handleCreate
        }}
      >
        {activeTab === 'tab1' && <Tab1 data={data} onAction={handleAction} />}
        {activeTab === 'tab2' && <Tab2 data={data} onAction={handleAction} />}
      </ModuleLayout>

      {/* Modals hier */}
    </div>
  );
};

export default NewModule;
```

---

## 🏁 Zusammenfassung

**Kernprinzip:** Ein Tab = Eine Datei
**Ziel:** Wartbarkeit, Übersichtlichkeit, Skalierbarkeit
**Regel:** NIEMALS Funktionalität oder Design ändern beim Refactoring

**Bei Unsicherheit:** Frage den Entwickler!

---

**Dokumentation erstellt:** 2026-01-12
**Version:** 1.0
**Status:** Verbindlich für alle KI-Assistenten
