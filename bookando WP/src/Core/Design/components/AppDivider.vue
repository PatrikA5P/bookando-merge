<!-- AppDivider.vue -->
<template>
  <div
    :class="dividerClasses"
    :role="role"
    :aria-orientation="orientation"
  >
    <span
      v-if="label || $slots.default"
      class="bookando-divider__label"
    >
      <slot>{{ label }}</slot>
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

export interface AppDividerProps {
  orientation?: 'horizontal' | 'vertical'
  align?: 'start' | 'center' | 'end'
  variant?: 'solid' | 'dashed' | 'dotted'
  spacing?: 'none' | 'sm' | 'md' | 'lg'
  label?: string
  role?: string
}

const props = withDefaults(defineProps<AppDividerProps>(), {
  orientation: 'horizontal',
  align: 'center',
  variant: 'solid',
  spacing: 'md',
  role: 'separator'
})

const dividerClasses = computed(() => [
  'bookando-divider',
  `bookando-divider--${props.orientation}`,
  `bookando-divider--align-${props.align}`,
  `bookando-divider--${props.variant}`,
  `bookando-divider--spacing-${props.spacing}`,
  {
    'bookando-divider--with-label': props.label || '$slots.default'
  }
])
</script>

<style lang="scss" scoped>
@use 'sass:color';
@use '../assets/scss/variables' as *;

.bookando-divider {
  position: relative;
  display: flex;
  align-items: center;
  border: 0;
  color: var(--bookando-text-muted, $bookando-text-muted);

  // Orientation
  &--horizontal {
    width: 100%;
    flex-direction: row;

    &::before,
    &::after {
      content: '';
      flex: 1;
      border-top-width: 1px;
      border-top-style: inherit;
      border-color: var(--bookando-border, $bookando-border);
    }
  }

  &--vertical {
    height: 100%;
    flex-direction: column;
    align-self: stretch;
    width: auto;

    &::before,
    &::after {
      content: '';
      flex: 1;
      border-left-width: 1px;
      border-left-style: inherit;
      border-color: var(--bookando-border, $bookando-border);
    }
  }

  // Variants
  &--solid {
    border-style: solid;
  }

  &--dashed {
    border-style: dashed;
  }

  &--dotted {
    border-style: dotted;
  }

  // Spacing
  &--spacing-none {
    margin: 0;
  }

  &--spacing-sm {
    &.bookando-divider--horizontal {
      margin: $bookando-spacing-sm 0;
    }

    &.bookando-divider--vertical {
      margin: 0 $bookando-spacing-sm;
    }
  }

  &--spacing-md {
    &.bookando-divider--horizontal {
      margin: $bookando-spacing-md 0;
    }

    &.bookando-divider--vertical {
      margin: 0 $bookando-spacing-md;
    }
  }

  &--spacing-lg {
    &.bookando-divider--horizontal {
      margin: $bookando-spacing-lg 0;
    }

    &.bookando-divider--vertical {
      margin: 0 $bookando-spacing-lg;
    }
  }

  // Label
  &__label {
    padding: 0 $bookando-spacing-sm;
    font-size: $bookando-font-size-sm;
    font-weight: $bookando-font-weight-medium;
    white-space: nowrap;
    background: var(--bookando-bg, $bookando-bg);
  }

  // Alignment
  &--align-start {
    &.bookando-divider--horizontal {
      justify-content: flex-start;

      &::before {
        flex: 0 0 $bookando-spacing-md;
      }
    }

    &.bookando-divider--vertical {
      justify-content: flex-start;

      &::before {
        flex: 0 0 $bookando-spacing-md;
      }
    }
  }

  &--align-center {
    justify-content: center;
  }

  &--align-end {
    &.bookando-divider--horizontal {
      justify-content: flex-end;

      &::after {
        flex: 0 0 $bookando-spacing-md;
      }
    }

    &.bookando-divider--vertical {
      justify-content: flex-end;

      &::after {
        flex: 0 0 $bookando-spacing-md;
      }
    }
  }

  // Without Label - einfache Linie
  &:not(.bookando-divider--with-label) {
    &.bookando-divider--horizontal {
      border-top-width: 1px;
      border-top-style: inherit;
      border-color: var(--bookando-border, $bookando-border);

      &::before,
      &::after {
        display: none;
      }
    }

    &.bookando-divider--vertical {
      border-left-width: 1px;
      border-left-style: inherit;
      border-color: var(--bookando-border, $bookando-border);

      &::before,
      &::after {
        display: none;
      }
    }
  }
}

// Dark Mode
[data-theme="dark"],
.theme-dark {
  .bookando-divider {
    &__label {
      background: var(--bookando-bg, #{color.adjust($bookando-bg-dark, $lightness: -5%)});
    }
  }
}
</style>
