<!-- AppSkeleton.vue -->
<template>
  <div
    :class="skeletonClasses"
    :style="skeletonStyles"
    :aria-label="ariaLabel || t('ui.skeleton.loading')"
    aria-busy="true"
    role="status"
  >
    <span
      v-if="!hideAccessibilityText"
      class="bookando-sr-only"
    >
      {{ ariaLabel || t('ui.skeleton.loading') }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

export interface AppSkeletonProps {
  variant?: 'text' | 'rect' | 'circle' | 'avatar' | 'button' | 'card'
  width?: string | number
  height?: string | number
  animated?: boolean
  count?: number
  ariaLabel?: string
  hideAccessibilityText?: boolean
}

const props = withDefaults(defineProps<AppSkeletonProps>(), {
  variant: 'text',
  animated: true,
  count: 1,
  hideAccessibilityText: false
})

const { t } = useI18n()

const skeletonClasses = computed(() => [
  'bookando-skeleton',
  `bookando-skeleton--${props.variant}`,
  {
    'bookando-skeleton--animated': props.animated
  }
])

const skeletonStyles = computed(() => {
  const styles: Record<string, string> = {}

  if (props.width) {
    styles.width = typeof props.width === 'number' ? `${props.width}px` : props.width
  }

  if (props.height) {
    styles.height = typeof props.height === 'number' ? `${props.height}px` : props.height
  }

  // Preset sizes für verschiedene Varianten
  if (props.variant === 'avatar' && !props.width && !props.height) {
    styles.width = '48px'
    styles.height = '48px'
  }

  if (props.variant === 'button' && !props.width && !props.height) {
    styles.width = '120px'
    styles.height = '40px'
  }

  if (props.variant === 'card' && !props.height) {
    styles.height = '200px'
  }

  return styles
})
</script>

<style lang="scss" scoped>
@use '../assets/scss/variables' as *;

.bookando-skeleton {
  display: block;
  background: linear-gradient(
    90deg,
    var(--bookando-bg-muted, $bookando-bg-muted) 0%,
    var(--bookando-bg-light, $bookando-bg-light) 50%,
    var(--bookando-bg-muted, $bookando-bg-muted) 100%
  );
  background-size: 200% 100%;
  border-radius: var(--bookando-radius-sm, $bookando-radius-sm);
  position: relative;
  overflow: hidden;

  &--animated {
    animation: shimmer 1.5s ease-in-out infinite;
  }

  // Variants
  &--text {
    height: 1em;
    border-radius: var(--bookando-radius-sm, $bookando-radius-sm);
    margin-bottom: 0.5em;

    &:last-child {
      margin-bottom: 0;
      width: 80%; // Letzte Zeile kürzer für realistischeren Look
    }
  }

  &--circle {
    border-radius: 50%;
    aspect-ratio: 1;
  }

  &--avatar {
    border-radius: 50%;
    width: 48px;
    height: 48px;
  }

  &--rect {
    border-radius: var(--bookando-radius-md, $bookando-radius-md);
  }

  &--button {
    border-radius: var(--bookando-radius-sm, $bookando-radius-sm);
    width: 120px;
    height: 40px;
  }

  &--card {
    border-radius: var(--bookando-radius-lg, $bookando-radius-lg);
    width: 100%;
    min-height: 200px;
  }
}

@keyframes shimmer {
  0% {
    background-position: -1000px 0;
  }
  100% {
    background-position: 1000px 0;
  }
}

// Dark Mode Adjustments
[data-theme="dark"],
.theme-dark {
  .bookando-skeleton {
    background: linear-gradient(
      90deg,
      rgba(255, 255, 255, 0.05) 0%,
      rgba(255, 255, 255, 0.1) 50%,
      rgba(255, 255, 255, 0.05) 100%
    );
    background-size: 200% 100%;
  }
}

// Reduced Motion
@media (prefers-reduced-motion: reduce) {
  .bookando-skeleton--animated {
    animation: none;
    background: var(--bookando-bg-muted, $bookando-bg-muted);
  }
}
</style>
