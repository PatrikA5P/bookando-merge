<script setup lang="ts">
/**
 * BSkeleton — Lade-Platzhalter
 *
 * Aus IMPROVEMENTS.md: Skeleton-Loader für jede Ansicht.
 */
import { SKELETON_STYLES } from '@/design';

withDefaults(defineProps<{
  variant?: 'text' | 'heading' | 'avatar' | 'card' | 'row';
  width?: string;
  height?: string;
  count?: number;
}>(), {
  variant: 'text',
  count: 1,
});

const variantClass: Record<string, string> = {
  text: SKELETON_STYLES.text,
  heading: SKELETON_STYLES.heading,
  avatar: `${SKELETON_STYLES.avatar} w-10 h-10`,
  card: SKELETON_STYLES.card,
  row: SKELETON_STYLES.row,
};
</script>

<template>
  <div class="space-y-3" :aria-busy="true" role="status">
    <div
      v-for="i in count"
      :key="i"
      :class="variantClass[variant]"
      :style="{
        width: width || (variant === 'text' ? `${60 + Math.random() * 40}%` : undefined),
        height: height || undefined,
      }"
    />
    <span class="sr-only">Laden...</span>
  </div>
</template>
