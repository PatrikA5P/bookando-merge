<!-- AppDataCard.vue -->
<template>
  <article
    class="bookando-data-card"
    :class="{
      'bookando-data-card--compact': compact,
      'bookando-data-card--borderless': borderless
    }"
  >
    <header
      v-if="$slots.header || title"
      class="bookando-data-card__header"
    >
      <h2
        v-if="title"
        class="bookando-h5 bookando-m-0"
      >
        {{ title }}
      </h2>
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
/**
 * AppDataCard - Generic card container for data display
 *
 * Replaces module-specific card components (e.g., bookando-finance-card)
 * with a reusable, consistent card layout.
 *
 * @example
 * <AppDataCard title="Invoice List">
 *   <template #header>
 *     <AppButton icon="plus">Add Invoice</AppButton>
 *   </template>
 *   <AppTable :data="invoices" />
 * </AppDataCard>
 */

defineProps<{
  /** Card title (displayed in header) */
  title?: string
  /** Compact variant with reduced padding */
  compact?: boolean
  /** Remove border and shadow */
  borderless?: boolean
}>()
</script>

<style scoped lang="scss">
@use '@scss/variables' as *;
@use '@scss/mixins' as *;

.bookando-data-card {
  background: $bookando-white;
  border: 1px solid $bookando-border-light;
  border-radius: $bookando-radius-md;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba($bookando-bg-dark, 0.04);
}

.bookando-data-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $bookando-spacing-sm;
  padding: $bookando-spacing-md;
  border-bottom: 1px solid $bookando-border-light;

  @include respond-to('md') {
    flex-wrap: wrap;
    padding: $bookando-spacing-sm;
  }
}

.bookando-data-card__body {
  padding: $bookando-spacing-md;

  @include respond-to('md') {
    padding: $bookando-spacing-sm;
  }
}

.bookando-data-card__footer {
  padding: $bookando-spacing-md;
  border-top: 1px solid $bookando-border-light;
  background: $bookando-bg-soft;

  @include respond-to('md') {
    padding: $bookando-spacing-sm;
  }
}

// Modifiers
.bookando-data-card--compact {
  .bookando-data-card__header,
  .bookando-data-card__body,
  .bookando-data-card__footer {
    padding: $bookando-spacing-sm;
  }

  @include respond-to('md') {
    .bookando-data-card__header,
    .bookando-data-card__body,
    .bookando-data-card__footer {
      padding: $bookando-spacing-xs;
    }
  }
}

.bookando-data-card--borderless {
  border: none;
  box-shadow: none;
}
</style>
