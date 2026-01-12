<!-- AppPageLayout.vue -->
<template>
  <div
    class="bookando-page-layout"
    :class="{
      'bookando-page-layout--compact': compact,
      'bookando-page-layout--fluid': fluid
    }"
    :style="maxWidth ? `--page-max-width: ${maxWidth}` : undefined"
  >
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
/**
 * AppPageLayout - Standard page layout component
 *
 * Provides consistent structure for all module views with semantic HTML
 * and automatic spacing/container constraints.
 *
 * @example
 * <AppPageLayout>
 *   <template #header>
 *     <AppPageHeader :title="..." />
 *   </template>
 *   <template #nav>
 *     <AppTabs v-model="tab" :tabs="tabs" nav-only />
 *   </template>
 *   <template #toolbar>
 *     <AppFilterBar>...</AppFilterBar>
 *   </template>
 *   <!-- Main content -->
 *   <YourContent />
 * </AppPageLayout>
 */

defineProps<{
  /** Max width for content (default: uses $bookando-container-max from tokens) */
  maxWidth?: string
  /** Compact layout with reduced padding */
  compact?: boolean
  /** Full-width layout without container constraint */
  fluid?: boolean
}>()
</script>

