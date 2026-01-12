<!-- AppIcon.vue -->
<template>
  <component
    :is="iconComponent"
    v-if="iconComponent && !isLoading"
    :aria-label="alt || name"
    role="img"
    :class="['bookando-icon', sizeClass]"
    :style="sizeStyle"
    v-bind="$attrs"
  />
  <span
    v-else-if="isLoading"
    :class="['bookando-icon', 'bookando-icon--loading', sizeClass]"
    :style="sizeStyle"
    aria-hidden="true"
  >
    <span class="bookando-icon__spinner" />
  </span>
  <span
    v-else
    :class="['bookando-icon', 'bookando-icon--missing', sizeClass]"
    :style="sizeStyle"
    :aria-label="`Icon ${name} not found`"
  >?</span>
</template>

<script setup lang="ts">
/**
 * @component AppIcon
 * @description
 * Dynamic SVG icon component with lazy loading and caching.
 *
 * Features:
 * - Lazy loads SVG icons on demand using Vite's import.meta.glob
 * - Caches loaded icons to prevent redundant loading
 * - Supports predefined sizes (xs, sm, md, lg, xl, 2xl) or custom CSS values
 * - Displays loading state while icon loads
 * - Shows fallback UI if icon is not found
 * - Accessible with ARIA labels
 *
 * Icon files must be placed in `../assets/icons/` directory.
 *
 * @example
 * <AppIcon name="check" size="lg" alt="Success checkmark" />
 * <AppIcon name="user" size="1.5rem" />
 */
import { ref, computed, watch, onMounted, shallowRef } from 'vue'
import type { Component } from 'vue'

const props = defineProps<{
  name: string
  alt?: string
  /** 'xs'|'sm'|'md'|'lg'|'xl'|'2xl' | number | CSS-Länge ('1.25rem','22px',…) */
  size?: string | number
}>()

// Lazy Loading: Icons werden nur bei Bedarf geladen
const iconMap = import.meta.glob('../assets/icons/*.svg')

const iconComponent = shallowRef<Component | null>(null)
const isLoading = ref(false)

// Icon-Cache um bereits geladene Icons nicht erneut zu laden
const iconCache = new Map<string, Component>()

async function loadIcon(iconName: string) {
  // Check Cache
  if (iconCache.has(iconName)) {
    iconComponent.value = iconCache.get(iconName)!
    return
  }

  const iconPath = `../assets/icons/${iconName}.svg`
  const loader = iconMap[iconPath]

  if (!loader) {
    iconComponent.value = null
    return
  }

  isLoading.value = true

  try {
    const module = await loader() as any
    const component = module.default || module

    // Cache speichern
    iconCache.set(iconName, component)
    iconComponent.value = component
  } catch (error) {
    console.error(`Failed to load icon: ${iconName}`, error)
    iconComponent.value = null
  } finally {
    isLoading.value = false
  }
}

// Initial load
onMounted(() => {
  loadIcon(props.name)
})

// Reagiere auf Name-Änderungen
watch(() => props.name, (newName) => {
  loadIcon(newName)
})

// Klassenversion für Token-Größen
const sizeClass = computed(() => {
  if (typeof props.size === 'string') {
    const tokens = new Set(['xs','sm','md','lg','xl','2xl'])
    return tokens.has(props.size) ? `bookando-icon--${props.size}` : null
  }
  return null
})

// Inline-Style, falls Zahl oder CSS-Länge übergeben wird
const sizeStyle = computed(() => {
  if (typeof props.size === 'number') {
    return { width: `${props.size}px`, height: `${props.size}px` }
  }
  if (typeof props.size === 'string' && /[\d.](px|rem|em|%)$/.test(props.size)) {
    return { width: props.size, height: props.size }
  }
  return {}
})
</script>

<style lang="scss" scoped>
.bookando-icon {
  &--loading {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  &__spinner {
    display: inline-block;
    width: 1em;
    height: 1em;
    border: 2px solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

<!--
Hinweise:
- Icons werden jetzt lazy geladen (Performance-Verbesserung)
- Icon-Cache verhindert doppeltes Laden
- Loading-State mit Spinner
- Icon-Klasse und Utility-Klassen kommen aus $attrs (von Button oder direkt).
- Style (z.B. Farbe) kann per :style direkt gesetzt werden.
-->
