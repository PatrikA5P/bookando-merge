<script setup lang="ts">
/**
 * BBadge — Zentrale Badge/Status-Komponente
 *
 * Unterstützt vordefinierte Varianten und automatische
 * Status-Erkennung via getStatusColors().
 */
import { computed } from 'vue';
import { BADGE_STYLES, getStatusColors } from '@/design';

const props = withDefaults(defineProps<{
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info' | 'brand' | 'purple' | 'outline';
  /** Alternativ: automatische Farbe basierend auf Status-String */
  status?: string;
  /** Punkt-Indikator anzeigen */
  dot?: boolean;
}>(), {
  variant: 'default',
  dot: false,
});

const badgeClass = computed(() => {
  if (props.status) {
    const colors = getStatusColors(props.status);
    return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors.bg} ${colors.text}`;
  }
  return BADGE_STYLES[props.variant];
});

const dotClass = computed(() => {
  if (props.status) {
    return getStatusColors(props.status).dot;
  }
  return '';
});
</script>

<template>
  <span :class="badgeClass">
    <span
      v-if="dot"
      :class="['w-1.5 h-1.5 rounded-full mr-1.5', dotClass]"
    />
    <slot />
  </span>
</template>
