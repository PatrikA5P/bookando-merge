<!-- AppBadge.vue -->
<template>
  <span
    :class="badgeClasses"
    :role="interactive ? 'button' : undefined"
    :tabindex="interactive ? 0 : undefined"
    :aria-label="ariaLabel"
    @click="onClick"
    @keydown.enter.space="onKeydown"
  >
    <AppIcon
      v-if="icon"
      :name="icon"
      :size="iconSize"
      class="bookando-badge__icon"
    />
    <span class="bookando-badge__content">
      <slot>{{ label }}</slot>
    </span>
    <button
      v-if="removable"
      type="button"
      class="bookando-badge__remove"
      :aria-label="removeLabel || t('ui.badge.remove')"
      @click.stop="onRemove"
    >
      <AppIcon
        name="x"
        size="xs"
      />
    </button>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from './AppIcon.vue'

export interface AppBadgeProps {
  label?: string
  variant?: 'default' | 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'info' |
            'success-light' | 'warning-light' | 'danger-light' | 'info-light' |
            'success-dark' | 'warning-dark' | 'danger-dark' | 'info-dark'
  size?: 'sm' | 'md' | 'lg'
  outlined?: boolean
  pill?: boolean
  removable?: boolean
  icon?: string
  interactive?: boolean
  ariaLabel?: string
  removeLabel?: string
}

const props = withDefaults(defineProps<AppBadgeProps>(), {
  variant: 'default',
  size: 'md',
  outlined: false,
  pill: false,
  removable: false,
  interactive: false
})

const emit = defineEmits<{
  (event: 'click', e: Event): void
  (event: 'remove'): void
}>()

const { t } = useI18n()

const iconSize = computed(() => {
  switch (props.size) {
    case 'sm': return 'xs'
    case 'lg': return 'sm'
    default: return 'xs'
  }
})

const badgeClasses = computed(() => [
  'bookando-badge',
  `bookando-badge--${props.variant}`,
  `bookando-badge--${props.size}`,
  {
    'bookando-badge--outlined': props.outlined,
    'bookando-badge--pill': props.pill,
    'bookando-badge--removable': props.removable,
    'bookando-badge--interactive': props.interactive
  }
])

function onClick(e: Event) {
  if (props.interactive) {
    emit('click', e)
  }
}

function onKeydown(e: KeyboardEvent) {
  if (props.interactive && (e.key === 'Enter' || e.key === ' ')) {
    e.preventDefault()
    emit('click', e)
  }
}

function onRemove() {
  emit('remove')
}
</script>

<style lang="scss" scoped>
@use '../assets/scss/variables' as *;

.bookando-badge {
  display: inline-flex;
  align-items: center;
  gap: $bookando-spacing-xs;
  padding: 2px $bookando-spacing-xs;
  font-size: $bookando-font-size-xs;
  font-weight: $bookando-font-weight-medium;
  line-height: 1.25;
  border-radius: $bookando-radius-sm;
  white-space: nowrap;
  transition: all var(--bookando-transition-base, 200ms) ease-in-out;

  &__content {
    display: inline-flex;
    align-items: center;
  }

  &__icon {
    flex-shrink: 0;
  }

  &__remove {
    background: none;
    border: none;
    padding: 0;
    margin: 0 0 0 $bookando-spacing-xxxs;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: opacity var(--bookando-transition-fast, 150ms) ease;

    &:hover {
      opacity: 1;
    }

    &:focus-visible {
      outline: 2px solid currentColor;
      outline-offset: 1px;
      border-radius: 2px;
    }
  }

  // Sizes
  &--sm {
    font-size: 0.625rem;
    padding: 1px $bookando-spacing-xxs;
  }

  &--md {
    font-size: $bookando-font-size-xs;
    padding: 2px $bookando-spacing-xs;
  }

  &--lg {
    font-size: $bookando-font-size-sm;
    padding: 4px $bookando-spacing-sm;
  }

  // Variants
  &--default {
    background: var(--bookando-bg-muted, $bookando-bg-muted);
    color: var(--bookando-text, $bookando-text);
  }

  &--primary {
    background: rgba($bookando-primary, 0.1);
    color: $bookando-primary;
  }

  &--secondary {
    background: rgba($bookando-secondary, 0.1);
    color: $bookando-secondary;
  }

  &--success {
    background: rgba($bookando-success, 0.1);
    color: $bookando-success;
  }

  &--warning {
    background: rgba($bookando-warning, 0.1);
    color: $bookando-warning;
  }

  &--danger {
    background: rgba($bookando-danger, 0.1);
    color: $bookando-danger;
  }

  &--info {
    background: rgba($bookando-info, 0.1);
    color: $bookando-info;
  }

  // Status Variants - Light
  &--success-light {
    background: var(--bookando-success-light, $bookando-success-light);
    color: var(--bookando-success-dark, $bookando-success-dark);
  }

  &--warning-light {
    background: var(--bookando-warning-light, $bookando-warning-light);
    color: var(--bookando-warning-dark, $bookando-warning-dark);
  }

  &--danger-light {
    background: var(--bookando-danger-light, $bookando-danger-light);
    color: var(--bookando-danger-dark, $bookando-danger-dark);
  }

  &--info-light {
    background: var(--bookando-info-light, $bookando-info-light);
    color: var(--bookando-info-dark, $bookando-info-dark);
  }

  // Status Variants - Dark
  &--success-dark {
    background: var(--bookando-success-dark, $bookando-success-dark);
    color: var(--bookando-white, $bookando-white);
  }

  &--warning-dark {
    background: var(--bookando-warning-dark, $bookando-warning-dark);
    color: var(--bookando-white, $bookando-white);
  }

  &--danger-dark {
    background: var(--bookando-danger-dark, $bookando-danger-dark);
    color: var(--bookando-white, $bookando-white);
  }

  &--info-dark {
    background: var(--bookando-info-dark, $bookando-info-dark);
    color: var(--bookando-white, $bookando-white);
  }

  // Outlined Variant
  &--outlined {
    background: transparent;
    border: 1px solid currentColor;

    &.bookando-badge--default {
      border-color: var(--bookando-border, $bookando-border);
    }
  }

  // Pill Shape
  &--pill {
    border-radius: $bookando-radius-pill;
  }

  // Interactive
  &--interactive {
    cursor: pointer;

    &:hover {
      filter: brightness(0.95);
    }

    &:focus-visible {
      outline: 2px solid var(--bookando-primary, $bookando-primary);
      outline-offset: 2px;
    }
  }
}
</style>
