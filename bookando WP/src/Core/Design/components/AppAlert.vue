<!-- AppAlert.vue -->
<template>
  <transition
    :name="transition"
    @after-leave="onAfterLeave"
  >
    <div
      v-if="visible"
      :class="alertClasses"
      :role="role"
      :aria-live="ariaLive"
    >
      <div
        v-if="showIcon"
        class="bookando-alert__icon"
      >
        <AppIcon
          :name="iconName"
          :size="iconSize"
        />
      </div>

      <div class="bookando-alert__content">
        <h5
          v-if="title"
          class="bookando-alert__title"
        >
          {{ title }}
        </h5>
        <div class="bookando-alert__message">
          <slot>{{ message }}</slot>
        </div>
        <div
          v-if="$slots.actions"
          class="bookando-alert__actions"
        >
          <slot name="actions" />
        </div>
      </div>

      <button
        v-if="closable"
        type="button"
        class="bookando-alert__close"
        :aria-label="closeLabel || t('ui.alert.close')"
        @click="onClose"
      >
        <AppIcon
          name="x"
          size="md"
        />
      </button>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from './AppIcon.vue'

export interface AppAlertProps {
  title?: string
  message?: string
  variant?: 'info' | 'success' | 'warning' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  showIcon?: boolean
  icon?: string
  closable?: boolean
  closeLabel?: string
  transition?: string
  role?: string
  ariaLive?: 'polite' | 'assertive' | 'off'
  modelValue?: boolean
}

const props = withDefaults(defineProps<AppAlertProps>(), {
  variant: 'info',
  size: 'md',
  showIcon: true,
  closable: false,
  transition: 'slide-down',
  role: 'alert',
  ariaLive: 'polite',
  modelValue: true
})

const emit = defineEmits<{
  (event: 'close'): void
  (event: 'update:modelValue', value: boolean): void
  (event: 'after-leave'): void
}>()

const { t } = useI18n()
const visible = ref(props.modelValue)

const alertClasses = computed(() => [
  'bookando-alert',
  `bookando-alert--${props.variant}`,
  `bookando-alert--${props.size}`,
  {
    'bookando-alert--with-icon': props.showIcon,
    'bookando-alert--closable': props.closable
  }
])

const iconName = computed(() => {
  if (props.icon) return props.icon

  switch (props.variant) {
    case 'success': return 'check-circle'
    case 'warning': return 'alert-triangle'
    case 'danger': return 'alert-circle'
    case 'info':
    default: return 'info'
  }
})

const iconSize = computed(() => {
  switch (props.size) {
    case 'sm': return 'sm'
    case 'lg': return 'lg'
    default: return 'md'
  }
})

function onClose() {
  visible.value = false
  emit('close')
  emit('update:modelValue', false)
}

function onAfterLeave() {
  emit('after-leave')
}
</script>

<style lang="scss" scoped>
@use 'sass:color';
@use '../assets/scss/variables' as *;

.bookando-alert {
  display: flex;
  align-items: flex-start;
  gap: $bookando-spacing-sm;
  padding: $bookando-spacing-md;
  border-radius: $bookando-radius-md;
  border: 1px solid transparent;
  position: relative;
  transition: all var(--bookando-transition-base, 200ms) ease-in-out;

  &__icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__content {
    flex: 1;
    min-width: 0;
  }

  &__title {
    margin: 0 0 $bookando-spacing-xs 0;
    font-size: $bookando-font-size-md;
    font-weight: $bookando-font-weight-semi-bold;
    line-height: 1.4;
  }

  &__message {
    font-size: $bookando-font-size-sm;
    line-height: 1.5;
  }

  &__actions {
    margin-top: $bookando-spacing-sm;
    display: flex;
    gap: $bookando-spacing-xs;
    flex-wrap: wrap;
  }

  &__close {
    flex-shrink: 0;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity var(--bookando-transition-fast, 150ms) ease;
    margin-top: 2px;

    &:hover {
      opacity: 1;
    }

    &:focus-visible {
      outline: 2px solid currentColor;
      outline-offset: 2px;
      border-radius: $bookando-radius-sm;
    }
  }

  // Sizes
  &--sm {
    padding: $bookando-spacing-sm;
    gap: $bookando-spacing-xs;

    .bookando-alert__title {
      font-size: $bookando-font-size-sm;
    }

    .bookando-alert__message {
      font-size: $bookando-font-size-xs;
    }
  }

  &--md {
    padding: $bookando-spacing-md;
    gap: $bookando-spacing-sm;
  }

  &--lg {
    padding: $bookando-spacing-lg;
    gap: $bookando-spacing-md;

    .bookando-alert__title {
      font-size: $bookando-font-size-lg;
    }

    .bookando-alert__message {
      font-size: $bookando-font-size-base;
    }
  }

  // Variants
  &--info {
    background: rgba($bookando-info, 0.1);
    border-color: rgba($bookando-info, 0.3);
    color: color.adjust($bookando-info, $lightness: -10%);

    [data-theme="dark"] &,
    .theme-dark & {
      background: rgba($bookando-info, 0.15);
      color: color.adjust($bookando-info, $lightness: 15%);
    }
  }

  &--success {
    background: rgba($bookando-success, 0.1);
    border-color: rgba($bookando-success, 0.3);
    color: color.adjust($bookando-success, $lightness: -10%);

    [data-theme="dark"] &,
    .theme-dark & {
      background: rgba($bookando-success, 0.15);
      color: color.adjust($bookando-success, $lightness: 15%);
    }
  }

  &--warning {
    background: rgba($bookando-warning, 0.1);
    border-color: rgba($bookando-warning, 0.3);
    color: color.adjust($bookando-warning, $lightness: -15%);

    [data-theme="dark"] &,
    .theme-dark & {
      background: rgba($bookando-warning, 0.15);
      color: color.adjust($bookando-warning, $lightness: 10%);
    }
  }

  &--danger {
    background: rgba($bookando-danger, 0.1);
    border-color: rgba($bookando-danger, 0.3);
    color: color.adjust($bookando-danger, $lightness: -10%);

    [data-theme="dark"] &,
    .theme-dark & {
      background: rgba($bookando-danger, 0.15);
      color: color.adjust($bookando-danger, $lightness: 15%);
    }
  }
}

// Transitions
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-out;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
