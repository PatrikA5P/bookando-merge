<!-- AppEmptyState.vue -->
<template>
  <div :class="emptyStateClasses">
    <div v-if="icon || $slots.icon" class="bookando-empty-state__icon">
      <slot name="icon">
        <AppIcon
          v-if="icon"
          :name="icon"
          :size="iconSize"
        />
      </slot>
    </div>

    <div class="bookando-empty-state__content">
      <h3 v-if="title || $slots.title" class="bookando-empty-state__title">
        <slot name="title">{{ title }}</slot>
      </h3>

      <p v-if="description || $slots.description" class="bookando-empty-state__description">
        <slot name="description">{{ description }}</slot>
      </p>

      <div v-if="$slots.actions || actionLabel" class="bookando-empty-state__actions">
        <slot name="actions">
          <AppButton
            v-if="actionLabel"
            :variant="actionVariant"
            :icon="actionIcon"
            @click="onActionClick"
          >
            {{ actionLabel }}
          </AppButton>
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'
import AppButton from './AppButton.vue'

export interface AppEmptyStateProps {
  title?: string
  description?: string
  icon?: string
  iconSize?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl'
  actionLabel?: string
  actionIcon?: string
  actionVariant?: 'primary' | 'secondary' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  centered?: boolean
}

const props = withDefaults(defineProps<AppEmptyStateProps>(), {
  iconSize: '2xl',
  actionVariant: 'primary',
  size: 'md',
  centered: true
})

const emit = defineEmits<{
  (event: 'action'): void
}>()

const emptyStateClasses = computed(() => [
  'bookando-empty-state',
  `bookando-empty-state--${props.size}`,
  {
    'bookando-empty-state--centered': props.centered
  }
])

function onActionClick() {
  emit('action')
}
</script>

<style lang="scss" scoped>
@use '../assets/scss/variables' as *;
@use '../assets/scss/mixins' as *;

.bookando-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--bookando-spacing-xl, $bookando-spacing-xl);
  text-align: center;

  &__icon {
    margin-bottom: var(--bookando-spacing-md, $bookando-spacing-md);
    color: var(--bookando-text-muted, $bookando-text-muted);
    opacity: 0.5;
  }

  &__content {
    max-width: 480px;
  }

  &__title {
    font-size: var(--bookando-font-size-lg, $bookando-font-size-lg);
    font-weight: $bookando-font-weight-semi-bold;
    color: var(--bookando-text-dark, $bookando-text-dark);
    margin: 0 0 var(--bookando-spacing-xs, $bookando-spacing-xs);
  }

  &__description {
    font-size: var(--bookando-font-size-base, $bookando-font-size-base);
    color: var(--bookando-text-muted, $bookando-text-muted);
    line-height: 1.6;
    margin: 0 0 var(--bookando-spacing-md, $bookando-spacing-md);
  }

  &__actions {
    margin-top: var(--bookando-spacing-md, $bookando-spacing-md);
    display: flex;
    gap: var(--bookando-spacing-sm, $bookando-spacing-sm);
    justify-content: center;
    flex-wrap: wrap;
  }

  // Size Variants
  &--sm {
    padding: var(--bookando-spacing-md, $bookando-spacing-md);

    .bookando-empty-state__icon {
      margin-bottom: var(--bookando-spacing-sm, $bookando-spacing-sm);
    }

    .bookando-empty-state__title {
      font-size: var(--bookando-font-size-base, $bookando-font-size-base);
    }

    .bookando-empty-state__description {
      font-size: var(--bookando-font-size-sm, $bookando-font-size-sm);
    }
  }

  &--lg {
    padding: var(--bookando-spacing-xxl, $bookando-spacing-xxl);

    .bookando-empty-state__icon {
      margin-bottom: var(--bookando-spacing-lg, $bookando-spacing-lg);
    }

    .bookando-empty-state__title {
      font-size: var(--bookando-font-size-xl, $bookando-font-size-xl);
    }

    .bookando-empty-state__description {
      font-size: var(--bookando-font-size-lg, $bookando-font-size-lg);
    }
  }

  // Centered Variant
  &--centered {
    min-height: 300px;
  }

  // Responsive Design
  @include bp-down(md) {
    padding: var(--bookando-spacing-lg, $bookando-spacing-lg);

    &__content {
      max-width: 100%;
    }

    &__title {
      font-size: var(--bookando-font-size-base, $bookando-font-size-base);
    }

    &__description {
      font-size: var(--bookando-font-size-sm, $bookando-font-size-sm);
    }
  }

  @include bp-down(sm) {
    padding: var(--bookando-spacing-md, $bookando-spacing-md);
    min-height: 200px;

    &__icon {
      margin-bottom: var(--bookando-spacing-sm, $bookando-spacing-sm);
    }

    &__actions {
      flex-direction: column;
      width: 100%;

      :deep(button) {
        width: 100%;
      }
    }
  }
}
</style>
