<!-- AppPopover.vue -->
<template>
  <!-- Trigger -->
  <div
    :id="triggerId"
    ref="triggerEl"
    class="bookando-popover-trigger"
  >
    <!-- Eigener Trigger via Slot oder Auto-Button -->
    <slot
      name="trigger"
      v-bind="{ open, close, toggle, isOpen }"
    >
      <AppButton
        :variant="triggerVariant"
        :size="triggerSizeComputed"
        :btn-type="triggerBtnTypeComputed"
        :icon="triggerIcon"
        :icon-size="triggerIconSize"
        :icon-color="triggerIconColor"
        :custom-class="triggerClass"
        :tooltip="tooltip"
        :is-mobile-view="isMobileView"
        :icon-only-on-mobile="iconOnlyOnMobile"
        aria-haspopup="menu"
        :aria-expanded="String(isOpen)"
        :aria-controls="panelId"
        @click.stop="toggle"
        @keydown.enter.prevent.stop="toggle"
        @keydown.space.prevent.stop="toggle"
      >
        <template v-if="showTriggerText">
          {{ triggerLabel }}
        </template>
        <template v-if="showChevron">
          <AppIcon
            name="chevron-down"
            class="bookando-icon bookando-ml-xxs"
          />
        </template>
      </AppButton>
    </slot>
  </div>

  <!-- Panel (teleported) -->
  <Teleport
    v-if="isOpen && teleport !== false"
    to="body"
  >
    <div
      :id="panelId"
      ref="panelEl"
      data-teleported="true"
      :class="['bookando-combobox-list', panelClass, { dropup: isDropup }]"
      :style="panelStyle"
      role="menu"
      :aria-labelledby="triggerId"
      @keydown.esc.prevent.stop="close"
      @click="closeOnItemClick ? close() : null"
    >
      <slot
        name="content"
        v-bind="{ close }"
      />
    </div>
  </Teleport>

  <!-- Fallback ohne Teleport -->
  <div
    v-else-if="isOpen"
    :id="panelId"
    ref="panelEl"
    :class="['bookando-combobox-list', panelClass, { dropup: isDropup }]"
    :style="{ zIndex: String(zIndex) }"
    role="menu"
    :aria-labelledby="triggerId"
    @keydown.esc.prevent.stop="close"
    @click="closeOnItemClick ? close() : null"
  >
    <slot
      name="content"
      v-bind="{ close }"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { computePosition, autoUpdate, flip, shift, offset as flOffset } from '@floating-ui/dom'

import { safeStartsWith } from '@core/Util/sanitize'
import AppButton from './AppButton.vue'
import AppIcon from './AppIcon.vue'

/** Props */
const props = withDefaults(defineProps<{
  modelValue?: boolean

  /** Trigger-Darstellung */
  triggerMode?: 'icon' | 'text' | 'both'
  triggerLabel?: string
  triggerIcon?: string
  triggerIconSize?: string
  triggerIconColor?: string
  triggerVariant?: string
  triggerSize?: string
  triggerBtnType?: string
  triggerClass?: string | string[] | Record<string, boolean>
  chevron?: 'auto' | boolean

  /* Button-Extras */
  tooltip?: string
  isMobileView?: boolean
  iconOnlyOnMobile?: boolean

  /** Popover-Optionen */
  id?: string
  teleport?: boolean
  placement?: 'bottom-start' | 'bottom-end' | 'top-start' | 'top-end'
  offset?: number
  zIndex?: number
  panelClass?: string | string[] | Record<string, boolean>
  closeOnItemClick?: boolean

  /** Breite des Panels:
   *  'content' | 'trigger' | 'full' | number | CSS-Länge ('320px','28rem',…)
   */
  width?: 'content' | 'trigger' | 'full' | number | string
  panelMinWidth?: number
  panelMaxWidth?: string
  /** Scrollverhalten im Panel: 'none' (Default) zeigt alles ohne Scrollbar, 'auto' erlaubt Scroll */
  scroll?: 'none' | 'auto'
  /** Optional: max. Höhe wenn scroll='auto' */
  panelMaxHeight?: number | string
}>(), {
  triggerMode: 'icon',
  triggerLabel: '',
  triggerIcon: 'more-horizontal',
  triggerIconSize: 'md',
  triggerVariant: 'standard',
  triggerSize: 'square',
  triggerBtnType: 'icononly',
  chevron: 'auto',

  teleport: true,
  placement: 'bottom-end',
  offset: 6,
  zIndex: 10030,
  closeOnItemClick: false,

  width: 'content',
  panelMinWidth: 220,
  panelMaxWidth: 'calc(100vw - 24px)',
  /** Scrollverhalten im Panel: 'none' (Default) zeigt alles ohne Scrollbar, 'auto' erlaubt Scroll */
  scroll: 'none',
  panelMaxHeight: '70vh'
})

const emit = defineEmits(['update:modelValue', 'open', 'close'])

/* ---------- State ---------- */
const isOpen = ref<boolean>(!!props.modelValue)
watch(() => props.modelValue, v => { if (typeof v === 'boolean') isOpen.value = v })
watch(isOpen, v => emit('update:modelValue', v))

/* ---------- Trigger / Chevron ---------- */
const showTriggerText = computed(() => props.triggerMode !== 'icon')
const showChevron = computed(() => {
  if (props.chevron === true) return true
  if (props.chevron === false) return false
  return props.triggerMode !== 'icon'
})
const triggerSizeComputed = computed(() =>
  props.triggerSize || (props.triggerMode === 'icon' ? 'square' : 'dynamic')
)
const triggerBtnTypeComputed = computed(() =>
  props.triggerBtnType || (props.triggerMode === 'icon' ? 'icononly' : 'full')
)

/* ---------- Positionierung ---------- */
const triggerEl = ref<HTMLElement|null>(null)
const panelEl   = ref<HTMLElement|null>(null)
const panelStyle = ref<Record<string, string>>({ zIndex: String(props.zIndex) })
let cleanup: (() => void) | null = null

const panelId = computed(() =>
  props.id ? `${props.id}-panel` : `popover-${Math.random().toString(36).slice(2, 8)}`
)
const triggerId = computed(() =>
  props.id ? `${props.id}-trigger` : `popover-trigger-${Math.random().toString(36).slice(2, 8)}`
)

const isDropup = computed(() => safeStartsWith(props.placement, 'top'))

function resolveWidthStyle(rect: DOMRect): Record<string, string> {
  const w = props.width
  const style: Record<string, string> = { maxWidth: props.panelMaxWidth || '' }

  if (typeof w === 'number') {
    style.width = `${Math.max(0, Math.round(w))}px`
    return style
  }
  if (typeof w === 'string') {
    if (w === 'trigger') {
      style.width = `${Math.round(rect.width)}px`
      return style
    }
    if (w === 'content') {
      style.width = 'auto'
      if (props.panelMinWidth) style.minWidth = `${Math.round(props.panelMinWidth)}px`
      return style
    }
    if (w === 'full') {
      style.width = props.panelMaxWidth || '100vw'
      return style
    }
    // beliebiger CSS-Wert (z.B. "28rem", "320px")
    style.width = w

    // Scroll-Styles:
    if (props.scroll === 'auto') {
      style.maxHeight = typeof props.panelMaxHeight === 'number'
        ? `${props.panelMaxHeight}px` : String(props.panelMaxHeight || '70vh')
      style.overflowY = 'auto'
    } else {
      style.maxHeight = 'none'
      style.overflowY = 'visible'
    }
    return style
  }
  // Fallback: Content
  style.width = 'auto'
  if (props.panelMinWidth) style.minWidth = `${Math.round(props.panelMinWidth)}px`
  return style
}

async function updatePos() {
  if (!triggerEl.value || !panelEl.value) return
  const { x, y } = await computePosition(triggerEl.value, panelEl.value, {
    strategy: 'fixed',
    placement: props.placement,
    middleware: [ flOffset(props.offset), flip(), shift({ padding: 8 }) ]
  })
  const rect = triggerEl.value.getBoundingClientRect()
  panelStyle.value = {
    position: 'fixed',
    left: `${Math.round(x)}px`,
    top: `${Math.round(y)}px`,
    zIndex: String(props.zIndex),
    ...resolveWidthStyle(rect)
  }
}

watch(isOpen, async v => {
  if (v) {
    await nextTick()
    cleanup?.(); cleanup = null
    if (triggerEl.value && panelEl.value) {
      cleanup = autoUpdate(triggerEl.value, panelEl.value, updatePos)
      updatePos()
      emit('open')
    }
  } else {
    cleanup?.(); cleanup = null
    emit('close')
  }
})

/* ---------- Public API ---------- */
function open()  { isOpen.value = true }
function close() { isOpen.value = false }
function toggle(){ isOpen.value = !isOpen.value }

/* ---------- Outside-Click / Keyboard ---------- */
function onPointerDown(e: PointerEvent) {
  if (!isOpen.value) return
  const t = e.target as Node
  if (!triggerEl.value?.contains(t) && !panelEl.value?.contains(t)) close()
}
function onGlobalKeydown(e: KeyboardEvent) {
  if (e.key === 'Tab') close()
}

onMounted(() => {
  document.addEventListener('pointerdown', onPointerDown, { capture: true })
  document.addEventListener('keydown', onGlobalKeydown, { capture: true })
})
onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onPointerDown, { capture: true } as any)
  document.removeEventListener('keydown', onGlobalKeydown, { capture: true } as any)
  cleanup?.(); cleanup = null
})
</script>

<style scoped>
.bookando-popover-trigger { display: inline-block; }
/* Panel nutzt Styles von .bookando-combobox-list aus deinem Design-System */
</style>
