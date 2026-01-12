# AppPageLayout Component Proposal

## Konzept: Slot-basiertes Layout mit CSS Grid

### Vorteile
- ✅ Single Source of Truth für Page-Layout
- ✅ Konsistentes Spacing automatisch
- ✅ Weniger DOM-Verschachtelung
- ✅ Flexibel durch Slots
- ✅ Einfach zu ändern (eine Component statt N Module)

### Komponente

```vue
<!-- src/Core/Design/components/AppPageLayout.vue -->
<template>
  <div class="bookando-page-layout">
    <!-- Header Section (optional) -->
    <header
      v-if="$slots.header"
      class="bookando-page-layout__header"
    >
      <slot name="header" />
    </header>

    <!-- Navigation (Tabs/Breadcrumbs) (optional) -->
    <nav
      v-if="$slots.nav"
      class="bookando-page-layout__nav"
    >
      <slot name="nav" />
    </nav>

    <!-- Toolbar (FilterBar, Actions) (optional) -->
    <div
      v-if="$slots.toolbar"
      class="bookando-page-layout__toolbar"
    >
      <slot name="toolbar" />
    </div>

    <!-- Main Content (required) -->
    <main class="bookando-page-layout__content">
      <slot />
    </main>

    <!-- Footer (optional) -->
    <footer
      v-if="$slots.footer"
      class="bookando-page-layout__footer"
    >
      <slot name="footer" />
    </footer>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  /** Max width für den Content (default: 1400px) */
  maxWidth?: string
  /** Kompaktes Layout ohne zusätzliches Padding */
  compact?: boolean
  /** Full-width Layout (kein Container-Constraint) */
  fluid?: boolean
}>()
</script>

<style scoped lang="scss">
@use '@scss/variables' as *;
@use '@scss/mixins' as *;

.bookando-page-layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto auto 1fr auto;
  grid-template-areas:
    "header"
    "nav"
    "toolbar"
    "content"
    "footer";
  min-height: 100%;
  background: $bookando-bg-light;

  // Alle Sections bekommen automatisch Container-Constraint
  > * {
    width: 100%;
    max-width: var(--page-max-width, $bookando-container-max);
    margin-inline: auto;
    padding-inline: $bookando-spacing-md;
    box-sizing: border-box;

    @include respond-to('md') {
      padding-inline: $bookando-spacing-sm;
    }
  }
}

// Header Section
.bookando-page-layout__header {
  grid-area: header;
  padding-block: $bookando-spacing-md;
  border-bottom: 1px solid $bookando-border-light;
  background: $bookando-white;
}

// Navigation Section (Tabs)
.bookando-page-layout__nav {
  grid-area: nav;
  padding-block: $bookando-spacing-md;
  border-bottom: 1px solid $bookando-border-light;
  background: $bookando-white;
}

// Toolbar Section (FilterBar)
.bookando-page-layout__toolbar {
  grid-area: toolbar;
  padding-block: $bookando-spacing-md;
  background: $bookando-white;
}

// Main Content
.bookando-page-layout__content {
  grid-area: content;
  padding-block: $bookando-spacing-lg;
}

// Footer Section
.bookando-page-layout__footer {
  grid-area: footer;
  padding-block: $bookando-spacing-md;
  border-top: 1px solid $bookando-border-light;
  background: $bookando-white;
}

// Modifiers
.bookando-page-layout--compact {
  .bookando-page-layout__header,
  .bookando-page-layout__nav,
  .bookando-page-layout__toolbar {
    padding-block: $bookando-spacing-sm;
  }
  .bookando-page-layout__content {
    padding-block: $bookando-spacing-md;
  }
}

.bookando-page-layout--fluid > * {
  max-width: none;
}
</style>
```

---

## Verwendung in Modulen

### Option 1: Standard Layout (Customers, Finance, Tools)

```vue
<!-- src/modules/customers/assets/vue/views/CustomersView.vue -->
<template>
  <AppShell>
    <AppPageLayout>
      <!-- Header Slot -->
      <template #header>
        <AppPageHeader :title="t('mod.customers.title')">
          <template #actions>
            <AppButton icon="user-plus" variant="primary">
              {{ t('mod.customers.actions.add') }}
            </AppButton>
          </template>
        </AppPageHeader>
      </template>

      <!-- Toolbar Slot (FilterBar) -->
      <template #toolbar>
        <AppFilterBar
          :ratio="[6,3,2]"
          :ratio-mobile="[2,1]"
          stack-below="md"
        >
          <template #left>
            <AppSearch v-model="search" />
          </template>
          <template #center>
            <AppSort v-model="sort" :options="sortOptions" />
          </template>
          <template #right>
            <AppButton icon="download">Export</AppButton>
            <AppButton icon="upload">Import</AppButton>
          </template>
        </AppFilterBar>
      </template>

      <!-- Main Content (default slot) -->
      <CustomerTable :data="customers" />
    </AppPageLayout>
  </AppShell>
</template>

<script setup lang="ts">
// ... logic
</script>

<!-- NO SCOPED STYLES for layout - all in AppPageLayout! -->
```

### Option 2: Mit Tabs (Finance, Tools)

```vue
<!-- src/modules/finance/assets/vue/views/FinanceView.vue -->
<template>
  <AppShell>
    <AppPageLayout>
      <!-- Header -->
      <template #header>
        <AppPageHeader :title="t('mod.finance.title')">
          <template #actions>
            <AppButton icon="file-text">New Invoice</AppButton>
            <AppButton icon="file-minus" variant="primary">New Credit</AppButton>
          </template>
        </AppPageHeader>
      </template>

      <!-- Tabs Navigation -->
      <template #nav>
        <AppTabs v-model="currentTab" :tabs="tabItems" nav-only />
      </template>

      <!-- Tab Content -->
      <InvoicesTab v-if="currentTab === 'invoices'" />
      <CreditNotesTab v-else-if="currentTab === 'credit'" />
      <DiscountsTab v-else-if="currentTab === 'discounts'" />
      <!-- ... -->
    </AppPageLayout>
  </AppShell>
</template>
```

### Option 3: Custom Layout (Tools/Design mit Sidebar)

```vue
<!-- src/modules/tools/assets/vue/views/ToolsView.vue -->
<template>
  <AppShell>
    <AppPageLayout>
      <template #header>
        <AppPageHeader :title="t('mod.tools.title')" />
      </template>

      <template #nav>
        <AppTabs v-model="activeTab" :tabs="tabs" nav-only />
      </template>

      <!-- Main Content mit Custom Layout -->
      <DesignTab v-if="activeTab === 'design'" />
      <!-- DesignTab hat eigenes 30/70 Grid, kein Problem -->
    </AppPageLayout>
  </AppShell>
</template>
```

### Option 4: Ohne Header (minimales Layout)

```vue
<!-- Irgendein einfaches Modul -->
<template>
  <AppShell>
    <AppPageLayout compact>
      <!-- Nur Content, keine Header/Toolbar -->
      <SimpleContent />
    </AppPageLayout>
  </AppShell>
</template>
```

---

## Vergleich: Vorher vs. Nachher

### ❌ VORHER (Repetitiv)

```vue
<div class="bookando-page">
  <div class="bookando-container bookando-p-md bookando-border-b">
    <AppPageHeader :title="..." />
  </div>
  <div class="bookando-container bookando-p-md bookando-border-b">
    <AppTabs nav-only />
  </div>
  <div class="bookando-container bookando-p-md">
    <Content />
  </div>
</div>

<style scoped>
.bookando-page { ... }
</style>
```

**Nachteile:**
- 12+ Zeilen HTML pro Modul
- 3x `bookando-container` wiederholt
- Scoped styles nötig
- Schwer zu ändern

### ✅ NACHHER (DRY)

```vue
<AppPageLayout>
  <template #header>
    <AppPageHeader :title="..." />
  </template>
  <template #nav>
    <AppTabs nav-only />
  </template>
  <Content />
</AppPageLayout>
```

**Vorteile:**
- 6 Zeilen HTML
- Keine Container-Repetition
- Keine scoped styles
- Eine Stelle zum Ändern

---

## Zusatz: AppDataCard für Finance-Pattern

```vue
<!-- src/Core/Design/components/AppDataCard.vue -->
<template>
  <article class="bookando-data-card">
    <header
      v-if="$slots.header || title"
      class="bookando-data-card__header"
    >
      <h2 v-if="title" class="bookando-h5 bookando-m-0">{{ title }}</h2>
      <slot name="header" />
    </header>

    <div class="bookando-data-card__body">
      <slot />
    </div>

    <footer
      v-if="$slots.footer"
      class="bookando-data-card__footer"
    >
      <slot name="footer" />
    </footer>
  </article>
</template>

<script setup lang="ts">
defineProps<{
  title?: string
}>()
</script>

<style scoped lang="scss">
@use '@scss/variables' as *;

.bookando-data-card {
  background: $bookando-white;
  border: 1px solid $bookando-border-light;
  border-radius: $bookando-radius-md;
  overflow: hidden;
}

.bookando-data-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $bookando-spacing-sm;
  padding: $bookando-spacing-md;
  border-bottom: 1px solid $bookando-border-light;
}

.bookando-data-card__body {
  padding: $bookando-spacing-md;
}

.bookando-data-card__footer {
  padding: $bookando-spacing-md;
  border-top: 1px solid $bookando-border-light;
  background: $bookando-bg-soft;
}
</style>
```

### Verwendung

```vue
<!-- Finance kann jetzt so aussehen -->
<AppPageLayout>
  <template #header>
    <AppPageHeader :title="t('mod.finance.title')" />
  </template>

  <template #nav>
    <AppTabs v-model="currentTab" :tabs="tabItems" nav-only />
  </template>

  <!-- Clean! -->
  <AppDataCard :title="t('mod.finance.tabs.invoices')">
    <template #header>
      <AppButton icon="plus" @click="openForm">
        {{ t('mod.finance.actions.add_invoice') }}
      </AppButton>
    </template>

    <AppTable :data="invoices" :columns="columns" />
  </AppDataCard>
</AppPageLayout>
```

---

## Migration Strategy

### Phase 1: Core Components erstellen
1. ✅ `AppPageLayout.vue` - Page-Level Layout
2. ✅ `AppDataCard.vue` - Card-Container (ersetzt finance-card)
3. ✅ `AppSplitLayout.vue` (optional) - Sidebar + Content für Design Tab

### Phase 2: Module migrieren (eines nach dem anderen)
1. **Finance** (einfach, schon gute Struktur)
2. **Customers** (Reference-Implementation)
3. **Tools** (komplex wegen DesignTab)
4. **Employees, Offers, Settings**

### Phase 3: Cleanup
1. ❌ Entfernen: `bookando-finance-card` aus finance/admin.scss
2. ❌ Entfernen: Repetitive Container-Wrapper
3. ❌ Entfernen: Layout-bezogene scoped styles

---

## Entscheidungen für dich

**Frage 1: AppPageLayout Component**
- Soll ich das so implementieren?
- Oder bevorzugst du eine andere Slot-Struktur?

**Frage 2: AppDataCard Component**
- Direkt umsetzen und Finance migrieren?
- Oder lieber später?

**Frage 3: Migration Reihenfolge**
- Alle Module auf einmal?
- Oder eins nach dem anderen mit deiner Bestätigung?

**Frage 4: DesignTab Special Case**
- Behält eigenes 30/70 Grid (in scoped styles)
- Nutzt aber AppPageLayout drumrum
- OK für dich?

---

## Mein Vorschlag für Today

1. ✅ **Schnellfix:** Tab height in Tools (nav-only prop)
2. ✅ **AppPageLayout erstellen** - Core Component
3. ✅ **AppDataCard erstellen** - Core Component
4. ✅ **Finance migrieren** - Als Proof of Concept
5. 📋 **Customers migrieren** - Reference Implementation
6. 📋 **Tools migrieren** - Mit Sonderfall DesignTab
7. 📋 **Restliche Module** nach Feedback

Was sagst du? Soll ich mit der Implementierung starten?
