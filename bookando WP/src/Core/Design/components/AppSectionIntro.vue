<!-- AppSectionIntro.vue -->
<template>
  <component
    :is="tag"
    :class="wrapperClasses"
    :aria-labelledby="headingId"
    :aria-describedby="hint ? hintId : undefined"
  >
    <!-- Kopfzeile mit optionalem Icon und Actions-Slot -->
    <div :class="headerRowClasses">
      <div class="bookando-inline-flex bookando-items-center bookando-gap-xs bookando-min-w-0">
        <AppIcon
          v-if="icon"
          :name="icon"
          class="bookando-icon"
          aria-hidden="true"
        />
        <component
          :is="as"
          :id="headingId"
          :class="titleClasses"
        >
          <slot name="title-prefix" />
          {{ title }}
          <slot name="title-suffix" />
        </component>
      </div>

      <!-- Actions rechts (Buttons, Links, Badges, o.ä.) -->
      <div
        v-if="$slots.actions"
        class="bookando-inline-flex bookando-items-center bookando-gap-xs"
      >
        <slot name="actions" />
      </div>
    </div>

    <!-- Hinweis / Untertitel -->
    <p
      v-if="hint"
      :id="hintId"
      class="bookando-text-sm bookando-text-muted bookando-m-0"
    >
      <slot name="hint-prefix" />
      {{ hint }}
      <slot name="hint-suffix" />
    </p>

    <!-- Optionaler Zusatzinhalt (z.B. Filter, Tabs-Untertitel, Chips) -->
    <div
      v-if="$slots.default"
      :class="contentSpacingClass"
    >
      <slot />
    </div>
  </component>
</template>

<script setup lang="ts">
/**
 * AppSectionIntro – schlanker, universeller Abschnitts-Header für Tabs/Abschnitte.
 * - Heading-Level via `as` (default h3)
 * - Ausrichtung, Dichte, Abstände, Borders via Props → nur Utilities
 * - Slots: actions (rechts), default (unterhalb), title-/hint- prefix/suffix
 *
 * Siehe Plugin-Struktur: Komponenten-Verantwortung / Design-Layer.
 */
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'

const props = withDefaults(defineProps<{
  title: string
  hint?: string

  /** Heading-Level für den Titel */
  as?: 'h2' | 'h3' | 'h4'

  /** Äusseres Wrapper-Tag */
  tag?: 'div' | 'section' | 'header'

  /** Optionales Icon (Name für AppIcon) */
  icon?: string

  /** Text- und Layout-Ausrichtung */
  align?: 'start' | 'center' | 'end'

  /** Vertikale Dichte (reduziert Abstände) */
  dense?: boolean

  /** Zusätzlicher Abstand unterhalb des Intros */
  spacing?: 'none' | 'sm' | 'md' | 'lg'

  /** Rahmenlinien (Top/Bottom/Both) */
  border?: 'none' | 'top' | 'bottom' | 'both'

  /** Optional sticky Header (innerhalb scollbarer Pane) */
  sticky?: boolean

  /** Zusätzliche Klassen vom Parent */
  class?: string
}>(), {
  as: 'h3',
  tag: 'div',
  align: 'start',
  dense: false,
  spacing: 'sm',
  border: 'none',
  sticky: false,
  class: ''
})

/* A11y-IDs */
const headingId = `intro-h-${Math.random().toString(36).slice(2, 8)}`
const hintId    = `intro-p-${Math.random().toString(36).slice(2, 8)}`

/* Klassen-Building via Utilities */
const alignClass = computed(() =>
  props.align === 'center' ? 'bookando-text-center'
  : props.align === 'end'  ? 'bookando-text-right'
  : 'bookando-text-left'
)

const outerSpacingClass = computed(() => {
  if (props.spacing === 'none') return ''
  if (props.spacing === 'lg')   return 'bookando-mb-lg'
  if (props.spacing === 'md')   return 'bookando-mb-md'
  return 'bookando-mb-sm'
})

const titleClasses = computed(() => [
  'bookando-mt-0',
  props.dense ? 'bookando-mb-xxs' : 'bookando-mb-xxs',
  'bookando-ellipsis',
])

const contentSpacingClass = computed(() =>
  props.dense ? 'bookando-mt-xs' : 'bookando-mt-sm'
)

const borderClasses = computed(() => {
  if (props.border === 'top')    return 'bookando-border-t-sm bookando-border-t-solid bookando-border-t-light bookando-pt-sm'
  if (props.border === 'bottom') return 'bookando-border-b-sm bookando-border-b-solid bookando-border-b-light bookando-pb-sm'
  if (props.border === 'both')   return 'bookando-border-t-sm bookando-border-t-solid bookando-border-t-light bookando-pt-sm bookando-border-b-sm bookando-border-b-solid bookando-border-b-light bookando-pb-sm'
  return ''
})

const stickyClasses = computed(() =>
  props.sticky
    ? 'bookando-sticky bookando-top-0 bookando-bg-body bookando-z-sticky' // nutzt zentrale Utilities
    : ''
)

const headerRowClasses = computed(() => [
  'bookando-flex',
  'bookando-items-start',
  'bookando-justify-between',
  props.dense ? 'bookando-mb-xxs' : 'bookando-mb-xs'
].join(' '))

const wrapperClasses = computed(() => [
  alignClass.value,
  outerSpacingClass.value,
  borderClasses.value,
  stickyClasses.value,
  props.class || ''
].filter(Boolean).join(' '))
</script>
