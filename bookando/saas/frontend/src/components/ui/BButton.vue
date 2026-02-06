<script setup lang="ts">
/**
 * BButton — Zentrale Button-Komponente
 *
 * Verwendet die Design-Token-basierten Stile.
 * Unterstützt alle Varianten, Grössen und States.
 */
import { computed } from 'vue';
import { BUTTON_STYLES, BUTTON_SIZES } from '@/design';

const props = withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'icon' | 'iconRound';
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
  disabled?: boolean;
  loading?: boolean;
  type?: 'button' | 'submit' | 'reset';
}>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
});

const buttonClass = computed(() => {
  const base = BUTTON_STYLES[props.variant];
  // Für icon-Varianten keine Grössen-Klassen anwenden
  if (props.variant === 'icon' || props.variant === 'iconRound') {
    return base;
  }
  return base;
});
</script>

<template>
  <button
    :type="type"
    :class="[buttonClass, { 'opacity-50 cursor-not-allowed': disabled || loading }]"
    :disabled="disabled || loading"
    :aria-busy="loading"
  >
    <!-- Loading Spinner -->
    <svg
      v-if="loading"
      class="animate-spin -ml-1 mr-2 h-4 w-4"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
    </svg>

    <slot />
  </button>
</template>
