<!-- AppCard.vue -->
<template>
  <article
    class="bookando-card"
    :class="[
      hoverable ? 'is-hoverable' : '',
      disabled ? 'bookando-card--disabled' : '',
      expandable ? 'bookando-card--accordion' : '',
      (expandable && isOpen) ? 'bookando-card--open' : '',
      heightClass,
      roundedClass,
      shadowClass,
      padClass,
      marginClass,
      extraCardClasses
    ]"
    :style="cardStyle"
    :tabindex="clickable && !disabled ? 0 : undefined"
    :role="clickable ? 'button' : undefined"
    :aria-disabled="disabled || undefined"
    @click="onMaybeClick"
    @keydown.enter.prevent="onMaybeClick"
  >
    <!-- Optionaler Standard-Header -->
    <header
      v-if="!hideHeader && (hasHeader || title)"
      class="bookando-card__header"
      :class="[ headerBgClass, headerPadClass, 'bookando-flex', 'bookando-justify-between', 'bookando-items-center', 'bookando-gap-sm' ]"
    >
      <div
        class="bookando-flex bookando-items-center bookando-gap-sm"
        style="min-width:0;"
      >
        <slot name="icon">
          <AppIcon
            v-if="icon"
            :name="icon"
            class="bookando-icon--lg"
          />
        </slot>

        <div
          class="bookando-flex bookando-flex-col bookando-gap-xxs"
          style="min-width:0;"
        >
          <slot name="title">
            <h3
              v-if="title"
              class="bookando-ellipsis"
              style="margin:0;"
            >
              {{ title }}
            </h3>
          </slot>
          <slot name="subtitle" />
        </div>
      </div>

      <nav class="bookando-flex bookando-items-center bookando-gap-sm">
        <slot name="actions" />
        <button
          v-if="expandable && showToggle"
          class="bookando-card__toggle"
          type="button"
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-controls="panelId"
          @click="toggle"
        >
          <span class="bookando-sr-only">{{ isOpen ? toggleLabels.less : toggleLabels.more }}</span>
          <svg
            class="chevron"
            viewBox="0 0 24 24"
            aria-hidden="true"
          ><path d="M7 10l5 5 5-5" /></svg>
        </button>
      </nav>
    </header>

    <!-- CONTENT (accordion) -->
    <section
      v-if="expandable"
      :id="panelId"
      class="bookando-card__panel"
      :class="{ 'is-open': isOpen }"
      role="region"
      :aria-label="title ? title + ' Inhalt' : undefined"
    >
      <div class="bookando-card__panel-inner">
        <div
          class="bookando-card__content"
          :class="[ bodyBgClass, bodyPadClass ]"
          :style="contentStyle"
        >
          <slot />
        </div>

        <footer
          v-if="$slots.footer"
          class="bookando-card__footer"
        >
          <slot name="footer" />
        </footer>
      </div>
    </section>

    <!-- CONTENT (ohne accordion) -->
    <template v-else>
      <div
        class="bookando-card__content"
        :class="[ bodyBgClass, bodyPadClass ]"
        :style="contentStyle"
      >
        <slot />
      </div>

      <footer
        v-if="$slots.footer"
        class="bookando-card__footer"
      >
        <slot name="footer" />
      </footer>
    </template>
  </article>
</template>

<script setup lang="ts">
import { computed, ref, useSlots } from 'vue'
import AppIcon from '@core/Design/components/AppIcon.vue'

type Spacing = '0'|'xxxs'|'xxs'|'xs'|'sm'|'md'|'lg'|'xl'|'xxl'
type HeightKey = 'xxs'|'xs'|'sm'|'md'|'lg'|'xl'|'xxl'
type RadiusKey = 'none'|'xxs'|'xs'|'sm'|'md'|'lg'|'xl'|'xxl'|'pill'|'full'
type ShadowKey = '0'|'1'|'2'|'3'
type BgKey =
  | 'primary'|'secondary'|'accent'|'danger'|'warning'|'success'|'info'
  | 'muted'|'soft'|'light'|'dark'|'white'|'black'
type Place = 'start'|'center'|'end'|'stretch'

const props = withDefaults(defineProps<{
  hideHeader?: boolean

  title?: string
  icon?: string

  disabled?: boolean
  hoverable?: boolean
  clickable?: boolean

  height?: HeightKey
  heightPx?: number

  expandable?: boolean
  defaultOpen?: boolean
  showToggle?: boolean
  toggleLabels?: { more: string; less: string }

  rounded?: RadiusKey
  shadow?: ShadowKey
  padding?: Spacing
  margin?: Spacing

  headerPadding?: Spacing
  bodyPadding?: Spacing
  headerBg?: BgKey
  bodyBg?: BgKey

  cols?: string
  rows?: string
  gap?: Spacing

  /** NEU: Grid-Ausrichtung im Body */
  placeItems?: Place           // setzt align & justify gleichzeitig
  alignItems?: Place           // ueberschreibt placeItems vertikal
  justifyItems?: Place         // ueberschreibt placeItems horizontal

  extraCardClasses?: string | string[]
}>(), {
  hideHeader: false,
  hoverable: true,
  clickable: false,
  showToggle: true,
  defaultOpen: false,
  rounded: 'sm',
  shadow: '1',
  padding: 'md',
  margin: '0',
  headerPadding: 'sm',
  bodyPadding: '0',
  gap: 'md',
  placeItems: 'start'
})

const emit = defineEmits<{ (event:'click'): void; (event:'toggle', open:boolean): void }>()
const slots = useSlots()

const isOpen = ref(!!props.defaultOpen)
const panelId = `card-panel-${Math.random().toString(36).slice(2,8)}`
const hasHeader = computed(() => !!(props.icon || slots.title || slots.subtitle || props.title || slots.actions))

const heightClass = computed(() => props.height ? `bookando-card--h-${props.height}` : '')
const roundedClass = computed(() => `bookando-rounded-${props.rounded}`)
const shadowClass  = computed(() => props.shadow === '0' ? '' : `bookando-shadow-${props.shadow}`)
const padClass     = computed(() => `bookando-p-${props.padding}`)
const marginClass  = computed(() => `bookando-m-${props.margin}`)

const headerBgClass = computed(() => props.headerBg ? `bookando-bg-${props.headerBg}` : '')
const bodyBgClass   = computed(() => props.bodyBg   ? `bookando-bg-${props.bodyBg}` : '')
const headerPadClass= computed(() => `bookando-p-${props.headerPadding}`)
const bodyPadClass  = computed(() => `bookando-p-${props.bodyPadding}`)

const isGrid = computed(() => !!(props.cols || props.rows))
const resolvedAlign = computed(() => props.alignItems ?? props.placeItems!)
const resolvedJustify = computed(() => props.justifyItems ?? props.placeItems!)

const contentStyle = computed(() => {
  if (!isGrid.value) return {}
  return {
    display: 'grid',
    gridTemplateColumns: props.cols || '1fr',
    gridTemplateRows: props.rows || 'auto',
    gap: `var(--gap, 1rem)`,
    alignItems: resolvedAlign.value,     // top alignment
    justifyItems: resolvedJustify.value, // left alignment
    minHeight: '0'                       // fuer sicheres Strecken im Flex-Parent
  } as Record<string,string>
})

const cardStyle = computed(() => ({
  '--bookando-card-min-h': props.heightPx ? `${props.heightPx}px` : undefined,
  '--gap': gapToCssVar(props.gap)
}) as Record<string, string | undefined>)

function gapToCssVar(g: Spacing) {
  const map: Record<Spacing,string> = {
    '0':'0','xxxs':'0.125rem','xxs':'0.25rem','xs':'0.375rem','sm':'0.5rem',
    'md':'1rem','lg':'1.5rem','xl':'2rem','xxl':'3rem'
  }
  return map[g] || '1rem'
}

function toggle(){ isOpen.value = !isOpen.value; emit('toggle', isOpen.value) }
function onMaybeClick(){ if (props.clickable && !props.disabled) emit('click') }
</script>
