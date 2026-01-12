# Bookando Design System - Style Guide

**Version:** 2.0
**Last Updated:** 2025-11-12
**Status:** ✅ Active

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Core Components](#core-components)
4. [Styling Approach](#styling-approach)
5. [Module Structure](#module-structure)
6. [Best Practices](#best-practices)
7. [Migration Guide](#migration-guide)
8. [Common Patterns](#common-patterns)

---

## Overview

The Bookando Design System follows a **hybrid utility-first approach** with semantic component composition. After modernization (Nov 2025), all modules use a consistent, DRY-compliant structure.

### Key Principles

1. **Composition over Repetition** - Use slot-based components instead of repeating HTML structure
2. **SCSS Variables over CSS Custom Properties** - Use design tokens from `_variables.scss`
3. **Utility Classes for Spacing** - Use `bookando-p-*`, `bookando-m-*`, `bookando-gap-*` for all spacing
4. **Semantic HTML** - Use proper `<header>`, `<nav>`, `<main>`, `<footer>` tags
5. **Single Source of Truth** - Core components define layout, not module files

---

## Architecture

### File Structure

```
src/
├── Core/
│   └── Design/
│       ├── components/
│       │   ├── AppPageLayout.vue      ✨ NEW: Page-level layout
│       │   ├── AppDataCard.vue        ✨ NEW: Generic card component
│       │   ├── AppPageHeader.vue
│       │   ├── AppTabs.vue
│       │   ├── AppFilterBar.vue
│       │   └── ...
│       └── assets/scss/
│           ├── _tokens.scss           → Raw design tokens
│           ├── _variables.scss        → Semantic aliases
│           ├── _container.scss        → Container + utilities
│           ├── _layout.scss           → Grid + FilterBar
│           ├── _table-cards.scss      → bookando-row-* classes
│           └── admin-ui.scss          → Main entry point
│
└── modules/
    └── <module>/
        ├── assets/
        │   ├── vue/views/
        │   │   └── <Module>View.vue  → Uses AppPageLayout
        │   └── css/
        │       └── admin.scss         → Module-specific styles ONLY
        └── components/
```

---

## Core Components

### 1. AppPageLayout

**Purpose:** Standard page layout structure for all module views.

**Location:** `/src/Core/Design/components/AppPageLayout.vue`

**Slots:**
- `#header` - Page header (AppPageHeader)
- `#nav` - Navigation tabs (AppTabs with `nav-only`)
- `#toolbar` - Filter bar or actions (AppFilterBar)
- Default slot - Main content

**Props:**
- `maxWidth?: string` - Custom max-width (default: from tokens)
- `compact?: boolean` - Reduced padding variant
- `fluid?: boolean` - Full-width without constraints

**Usage:**

```vue
<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay v-if="!moduleAllowed" :plan="requiredPlan" />

      <AppPageLayout v-else>
        <!-- Header Slot -->
        <template #header>
          <AppPageHeader :title="t('mod.customers.title')">
            <template #actions>
              <AppButton icon="plus">Add Customer</AppButton>
            </template>
          </AppPageHeader>
        </template>

        <!-- Nav Slot (for tabs) -->
        <template #nav>
          <AppTabs v-model="activeTab" :tabs="tabs" nav-only />
        </template>

        <!-- Toolbar Slot (for filterbar) -->
        <template #toolbar>
          <AppFilterBar>...</AppFilterBar>
        </template>

        <!-- Main Content (default slot) -->
        <CustomerTable :data="customers" />
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
// ... other imports
</script>
```

**Benefits:**
- ✅ Automatic container constraints and centering
- ✅ Consistent spacing and borders
- ✅ Semantic HTML structure
- ✅ No repetitive wrapper divs
- ✅ Single place to modify layout globally

**Spacing Behavior:**
- All sections get horizontal padding: `padding-inline: $bookando-spacing-md`
- Header, Nav, Toolbar get vertical padding: `padding-block: $bookando-spacing-md`
- Content area gets vertical padding: `padding-block: $bookando-spacing-md`
- This matches the old `bookando-p-md` pattern exactly
- On mobile (`@respond-to('md')`), spacing scales down appropriately

---

### 2. AppDataCard

**Purpose:** Generic card container for data display (replaces module-specific cards).

**Location:** `/src/Core/Design/components/AppDataCard.vue`

**Slots:**
- `#header` - Card header actions
- Default slot - Card body content
- `#footer` - Card footer

**Props:**
- `title?: string` - Card title
- `compact?: boolean` - Reduced padding
- `borderless?: boolean` - Remove border and shadow

**Usage:**

```vue
<AppDataCard title="Invoice List">
  <template #header>
    <AppButton icon="plus" @click="addInvoice">
      Add Invoice
    </AppButton>
  </template>

  <AppTable :data="invoices" :columns="columns" />

  <template #footer>
    <AppPagination />
  </template>
</AppDataCard>
```

**Replaces:**
- `bookando-finance-card` → Use `AppDataCard`
- `bookando-[module]-card` → Use `AppDataCard`
- Custom card divs → Use `AppDataCard`

---

### 3. AppTabs

**IMPORTANT:** Always use `nav-only` prop when tabs are in AppPageLayout!

**Usage:**

```vue
<!-- ✅ CORRECT: Use nav-only in AppPageLayout -->
<template #nav>
  <AppTabs v-model="activeTab" :tabs="tabs" nav-only />
</template>

<!-- ❌ WRONG: Missing nav-only causes extra height -->
<template #nav>
  <AppTabs v-model="activeTab" :tabs="tabs" />
</template>
```

**Why?** Without `nav-only`, AppTabs renders a `<div class="bookando-tab-content">` wrapper that adds unwanted padding/height. The `nav-only` prop renders ONLY the tab navigation.

---

## Styling Approach

### Hybrid Utility-First

**Use utility classes for:**
- ✅ Padding: `bookando-p-md`, `bookando-px-lg`, `bookando-py-sm`
- ✅ Margin: `bookando-m-md`, `bookando-mb-lg`, `bookando-mt-sm`
- ✅ Gap: `bookando-gap-sm`, `bookando-gap-md`
- ✅ Borders: `bookando-border-b`, `bookando-border-t`
- ✅ Typography: `bookando-h5`, `bookando-text-muted`

**Use scoped SCSS for:**
- ✅ Component-specific layouts (e.g., CSS Grid with `3fr 7fr`)
- ✅ Colors (component-specific backgrounds, hover states)
- ✅ Transitions and animations
- ✅ Complex selectors

**DON'T use:**
- ❌ CSS custom properties (`var(--bookando-space-*)`) - Use SCSS variables
- ❌ Hardcoded pixel values - Use design tokens
- ❌ Inline styles - Use utility classes or scoped SCSS
- ❌ Repetitive wrapper divs - Use AppPageLayout

### SCSS Variables

**Always import and use SCSS variables:**

```scss
<style scoped lang="scss">
@use '@scss/variables' as *;
@use '@scss/mixins' as *;

.design-grid {
  display: grid;
  grid-template-columns: 3fr 7fr;
  gap: $bookando-spacing-lg;           // ✅ CORRECT
  padding: $bookando-spacing-md;       // ✅ CORRECT
  border-radius: $bookando-radius;     // ✅ CORRECT
  background: $bookando-bg-light;      // ✅ CORRECT

  /* ❌ WRONG - Don't use CSS vars with fallbacks */
  gap: var(--bookando-space-lg, 1.5rem);

  /* ❌ WRONG - Don't hardcode values */
  padding: 1rem;
}

/* Responsive */
@include respond-to('lg') {             // ✅ CORRECT - Use mixin
  .design-grid {
    grid-template-columns: 1fr;
  }
}
</style>
```

### Available Design Tokens

**Spacing:**
```scss
$bookando-spacing-xxxs  // 0.125rem (2px)
$bookando-spacing-xxs   // 0.25rem (4px)
$bookando-spacing-xs    // 0.5rem (8px)
$bookando-spacing-sm    // 0.75rem (12px)
$bookando-spacing-md    // 1rem (16px)
$bookando-spacing-lg    // 1.5rem (24px)
$bookando-spacing-xl    // 2rem (32px)
$bookando-spacing-xxl   // 3rem (48px)
```

**Radius:**
```scss
$bookando-radius-xxs    // 2px
$bookando-radius-xs     // 4px
$bookando-radius-sm     // 6px
$bookando-radius        // 8px (default)
$bookando-radius-md     // 8px
$bookando-radius-lg     // 12px
$bookando-radius-xl     // 16px
$bookando-radius-xxl    // 24px
```

**Colors:**
```scss
$bookando-primary
$bookando-secondary
$bookando-success
$bookando-warning
$bookando-danger
$bookando-info

$bookando-white
$bookando-black
$bookando-gray-100 through $bookando-gray-600

$bookando-text
$bookando-text-dark
$bookando-text-muted

$bookando-border
$bookando-border-light
$bookando-border-dark

$bookando-bg-light
$bookando-bg-soft
$bookando-bg-contrast
```

---

## Module Structure

### Standard Module View

All module views should follow this structure:

```vue
<!-- src/modules/[module]/assets/vue/views/[Module]View.vue -->
<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <!-- Always: Header -->
        <template #header>
          <AppPageHeader :title="t('mod.[module].title')">
            <template #actions>
              <!-- Action buttons -->
            </template>
          </AppPageHeader>
        </template>

        <!-- Optional: Tabs -->
        <template #nav v-if="hasTabs">
          <AppTabs
            v-model="activeTab"
            :tabs="tabs"
            nav-only
          />
        </template>

        <!-- Optional: FilterBar -->
        <template #toolbar v-if="hasFilters">
          <AppFilterBar>
            <!-- Filter inputs -->
          </AppFilterBar>
        </template>

        <!-- Main Content -->
        <!-- Content goes here -->
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppTabs from '@core/Design/components/AppTabs.vue' // if needed
import AppFilterBar from '@core/Design/components/AppFilterBar.vue' // if needed
// ... module-specific imports

const { t } = useI18n()
// ... module logic
</script>

<!-- NO layout-related scoped styles needed! -->
```

### Module SCSS Files

**Module SCSS should ONLY contain module-specific styles:**

```scss
// src/modules/[module]/assets/css/admin.scss
@use 'sass:map';
@use '@scss/variables' as *;
@use '@scss/mixins' as *;

// =========================================
// [Module] Styles
// =========================================
// NOTE: Layout structure uses AppPageLayout and Core components
// This file only contains [module]-specific components

// Module-specific component styles
.bookando-[module]-table {
  // ... module-specific table styling
}

.bookando-[module]-custom-widget {
  // ... unique to this module
}

// Responsive overrides (if needed)
@include bp-down(md) {
  // ...
}
```

**DO NOT include:**
- ❌ `.bookando-[module]-page` - Use AppPageLayout
- ❌ `.bookando-[module]-card` - Use AppDataCard
- ❌ Generic layout wrappers - Use Core components

---

## Best Practices

### ✅ DO

1. **Use AppPageLayout for all module views**
   ```vue
   <AppPageLayout>
     <template #header>...</template>
     <template #nav>...</template>
     <Content />
   </AppPageLayout>
   ```

2. **Use AppDataCard for data containers**
   ```vue
   <AppDataCard title="Data List">
     <AppTable :data="items" />
   </AppDataCard>
   ```

3. **Use utility classes for spacing**
   ```vue
   <div class="bookando-p-md bookando-mb-lg bookando-gap-sm">
   ```

4. **Import SCSS variables in scoped styles**
   ```scss
   @use '@scss/variables' as *;
   gap: $bookando-spacing-lg;
   ```

5. **Use respond-to mixin for responsive**
   ```scss
   @include respond-to('lg') { ... }
   ```

6. **Use nav-only prop on AppTabs**
   ```vue
   <AppTabs nav-only />
   ```

### ❌ DON'T

1. **Don't repeat container wrappers**
   ```vue
   <!-- ❌ WRONG -->
   <div class="bookando-page">
     <div class="bookando-container bookando-p-md">
       <AppPageHeader />
     </div>
   </div>

   <!-- ✅ CORRECT -->
   <AppPageLayout>
     <template #header>
       <AppPageHeader />
     </template>
   </AppPageLayout>
   ```

2. **Don't use CSS custom properties for spacing**
   ```scss
   /* ❌ WRONG */
   gap: var(--bookando-space-lg, 1.5rem);

   /* ✅ CORRECT */
   gap: $bookando-spacing-lg;
   ```

3. **Don't hardcode pixel values**
   ```scss
   /* ❌ WRONG */
   padding: 16px;
   gap: 24px;

   /* ✅ CORRECT */
   padding: $bookando-spacing-md;
   gap: $bookando-spacing-lg;
   ```

4. **Don't create module-specific layout classes**
   ```scss
   /* ❌ WRONG - Don't create these */
   .bookando-[module]-page { ... }
   .bookando-[module]-header { ... }
   .bookando-[module]-card { ... }

   /* ✅ CORRECT - Use Core components */
   // AppPageLayout, AppPageHeader, AppDataCard
   ```

5. **Don't use old @media queries**
   ```scss
   /* ❌ WRONG */
   @media (max-width: 1024px) { ... }

   /* ✅ CORRECT */
   @include respond-to('lg') { ... }
   ```

---

## Migration Guide

### From Old Pattern to New Pattern

**Before (Old Pattern):**
```vue
<template>
  <AppShell>
    <div class="bookando-admin-page">
      <template v-else>
        <div class="bookando-page">
          <div class="bookando-container bookando-p-md bookando-border-b">
            <AppPageHeader :title="..." />
          </div>
          <div class="bookando-container bookando-p-md bookando-border-b">
            <AppTabs v-model="tab" :tabs="tabs" />
          </div>
          <div class="bookando-container bookando-p-md">
            <Content />
          </div>
        </div>
      </template>
    </div>
  </AppShell>
</template>
```

**After (New Pattern):**
```vue
<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppPageLayout v-else>
        <template #header>
          <AppPageHeader :title="..." />
        </template>
        <template #nav>
          <AppTabs v-model="tab" :tabs="tabs" nav-only />
        </template>
        <Content />
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
// ... existing imports
</script>
```

### Migration Steps

1. **Add import:**
   ```ts
   import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
   ```

2. **Replace wrapper:**
   - Remove `<template v-else>` + `<div class="bookando-page">`
   - Add `<AppPageLayout v-else>`

3. **Move header:**
   - Remove `<div class="bookando-container bookando-p-md bookando-border-b">`
   - Wrap `<AppPageHeader>` in `<template #header>`

4. **Move tabs (if exists):**
   - Remove container wrapper
   - Wrap `<AppTabs>` in `<template #nav>`
   - Add `nav-only` prop

5. **Move content:**
   - Remove container wrapper
   - Content goes in default slot

6. **Close properly:**
   - Close with `</AppPageLayout>` instead of `</div></template>`

---

## Common Patterns

### Pattern 1: Module with Tabs

```vue
<AppPageLayout>
  <template #header>
    <AppPageHeader :title="t('mod.finance.title')" />
  </template>

  <template #nav>
    <AppTabs v-model="currentTab" :tabs="tabItems" nav-only />
  </template>

  <!-- Tab content -->
  <InvoicesTab v-if="currentTab === 'invoices'" />
  <CreditTab v-else-if="currentTab === 'credit'" />
</AppPageLayout>
```

### Pattern 2: Module with FilterBar

```vue
<AppPageLayout>
  <template #header>
    <AppPageHeader :title="t('mod.customers.title')">
      <template #actions>
        <AppButton icon="plus">Add Customer</AppButton>
      </template>
    </AppPageHeader>
  </template>

  <template #toolbar>
    <AppFilterBar :ratio="[6,3,2]" stack-below="md">
      <template #left>
        <AppSearch v-model="search" />
      </template>
      <template #center>
        <AppSort v-model="sort" />
      </template>
      <template #right>
        <AppButton icon="download">Export</AppButton>
      </template>
    </AppFilterBar>
  </template>

  <CustomersTable :data="customers" />
</AppPageLayout>
```

### Pattern 3: Module with Cards

```vue
<AppPageLayout>
  <template #header>
    <AppPageHeader :title="t('mod.finance.title')" />
  </template>

  <template #nav>
    <AppTabs v-model="tab" :tabs="tabs" nav-only />
  </template>

  <AppDataCard title="Invoice List">
    <template #header>
      <AppButton icon="plus">Add Invoice</AppButton>
    </template>
    <AppTable :data="invoices" />
  </AppDataCard>
</AppPageLayout>
```

### Pattern 4: Custom Grid Layout (e.g., Design Tab)

```vue
<AppPageLayout>
  <template #header>
    <AppPageHeader :title="t('mod.tools.title')" />
  </template>

  <template #nav>
    <AppTabs v-model="tab" :tabs="tabs" nav-only />
  </template>

  <template #toolbar v-if="!selectedTemplate">
    <AppFilterBar>...</AppFilterBar>
  </template>

  <!-- Custom grid in main content -->
  <div class="design-grid">
    <div class="design-sidebar">...</div>
    <div class="design-preview">...</div>
  </div>
</AppPageLayout>

<style scoped lang="scss">
@use '@scss/variables' as *;

.design-grid {
  display: grid;
  grid-template-columns: 3fr 7fr;
  gap: $bookando-spacing-lg;
  align-items: start;
}

@include respond-to('lg') {
  .design-grid {
    grid-template-columns: 1fr;
  }
}
</style>
```

---

## Summary

### Key Takeaways

1. **AppPageLayout is mandatory** for all module views
2. **Use utility classes** for all spacing (padding, margin, gap)
3. **Use SCSS variables** (not CSS custom properties)
4. **Always use `nav-only`** on AppTabs in AppPageLayout
5. **Module SCSS files** should only contain module-specific styles
6. **AppDataCard replaces** all module-specific card components

### Benefits of New System

- **70% less code** in module views
- **Single source of truth** for layout structure
- **Consistent design** across all modules
- **Easy to maintain** - change once, applies everywhere
- **Better semantic HTML** structure
- **Optimal performance** - less duplicate CSS

---

## Support

**Documentation:** This guide
**Components:** `/src/Core/Design/components/`
**Styles:** `/src/Core/Design/assets/scss/`
**Examples:** All 8 modules have been migrated as reference implementations

---

**Last Migration:** November 2025
**Modules Migrated:** Tools, Customers, Finance, Employees, Offers, Settings, Resources, Appointments, Academy
**Status:** ✅ Production Ready
