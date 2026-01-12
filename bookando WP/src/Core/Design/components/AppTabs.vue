<!-- AppTabs.vue -->
<template>
  <div
    ref="rootRef"
    class="bookando-tabs"
    :class="{ 'bookando-tabs--nav-only': navOnly }"
    role="tablist"
    aria-orientation="horizontal"
  >
    <div
      class="bookando-tab-nav-wrap"
      :class="{
        'is-left-visible': showLeft,
        'is-right-visible': showRight
      }"
    >
      <!-- Left Chevron -->
      <button
        v-if="showLeft"
        class="bookando-tab-caret bookando-tab-caret--left"
        type="button"
        :aria-label="t('ui.tabs.scroll_left')"
        @click="onChevron('left')"
      >
        <AppIcon
          name="chevron-left"
          class="bookando-icon"
          aria-hidden="true"
        />
      </button>

      <!-- Scrollable Nav -->
      <nav
        ref="navRef"
        class="bookando-tab-nav"
        :class="{
          'is-indicator-hovering': indicatorHovering,
          'is-centered': centered
        }"
        @scroll="onScroll"
        @wheel="onWheel"
        @pointerdown="onPointerDown"
        @keydown="onKeydown"
      >
        <!-- Active indicator -->
        <div
          ref="indicatorRef"
          class="bookando-tab-indicator"
          aria-hidden="true"
        />

        <!-- Tabs -->
        <button
          v-for="(tab, idx) in normalizedTabs"
          :id="tabId(tab)"
          :key="tab.value"
          :ref="setTabBtnRef"
          class="bookando-tab"
          :class="{ active: isActive(t), 'bookando-tab--disabled': !!t.disabled }"
          type="button"
          role="tab"
          :aria-disabled="!!t.disabled || undefined"
          :disabled="!!t.disabled"
          :tabindex="t.disabled ? -1 : (isActive(t) ? 0 : -1)"
          :aria-selected="isActive(t)"
          :aria-controls="panelId(t)"
          :data-index="idx"
          @click="onTabClick(idx, tab)"
          @mouseenter="onHoverEnter(idx)"
          @mouseleave="onHoverLeave"
        >
          <span
            v-if="t.icon"
            :class="['bookando-icon', t.icon]"
            aria-hidden="true"
          />
          {{ tab.label }}
        </button>
      </nav>

      <!-- Right Chevron -->
      <button
        v-if="showRight"
        class="bookando-tab-caret bookando-tab-caret--right"
        type="button"
        :aria-label="t('ui.tabs.scroll_right')"
        @click="onChevron('right')"
      >
        <AppIcon
          name="chevron-right"
          class="bookando-icon"
          aria-hidden="true"
        />
      </button>

      <!-- Optional Progress -->
      <div
        v-if="progressEnabled && hasOverflow"
        class="bookando-tab-progress"
        :style="{ '--bookando-tab-progress': progressPercent }"
        aria-hidden="true"
      />
    </div>

    <!-- Panels -->
    <template v-if="!navOnly">
      <div
        v-for="tab in normalizedTabs"
        v-show="isActive(tab)"
        :id="panelId(tab)"
        :key="panelId(tab)"
        class="bookando-tab-content"
        role="tabpanel"
        :aria-labelledby="tabId(tab)"
      >
        <slot
          :active="tab.value"
          :tab="tab"
        />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, onBeforeUpdate, nextTick, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@core/Design/components/AppIcon.vue'

type TabItem = { label: string; value: string | number; icon?: string; disabled?: boolean }

const props = defineProps<{
  modelValue: string | number
  tabs: Array<string | number | Partial<TabItem>>
  navOnly?: boolean
  showProgress?: boolean

  // Optional-BigPlayer Goodies:
  recenterOnSelect?: boolean // wie Ant/Element
  indicatorThickness?: number // wie Material (px)
  variant?: 'default' | 'centered' // centered = zentriert, solange kein Overflow
  swipeable?: boolean
  swipeThreshold?: number
}>()

const emit = defineEmits<{ (event:'update:modelValue', value:string|number): void }>()
const { t } = useI18n()

const navOnly = computed(() => !!props.navOnly)
const progressEnabled = computed(() => !!props.showProgress)

const navRef = ref<HTMLElement | null>(null)
const indicatorRef = ref<HTMLDivElement | null>(null)

const swipeable = computed(() => props.swipeable !== false)
const threshold = computed(() => Math.max(24, props.swipeThreshold ?? 42))
const rootRef = ref<HTMLElement|null>(null)

// Unterstreichung schmaler – via CSS-Var überschreibbar
const indicatorInset = ref(12)
function readCssInset(){
  const el = navRef.value
  if (!el) return
  const v = parseFloat(getComputedStyle(el).getPropertyValue('--bookando-tab-inset') || '0')
  if (!Number.isNaN(v)) indicatorInset.value = v
}

// geordnete Button-Refs
const tabBtnRefs = ref<HTMLButtonElement[]>([])
function setTabBtnRef(el: HTMLButtonElement | null) { if (el) tabBtnRefs.value.push(el) }
onBeforeUpdate(() => { tabBtnRefs.value = [] })

const normalizedTabs = computed<TabItem[]>(() =>
  (props.tabs || []).map((x:any, i:number) =>
    (typeof x === 'string' || typeof x === 'number')
      ? ({ label: String(x), value: x })
      : ({ label: String(x.label ?? x.value ?? `Tab ${i+1}`), value: x.value ?? i, icon: x.icon, disabled: !!x.disabled })
  )
)

function isActive(t:TabItem){ return t.value === props.modelValue }
function tabId(t:TabItem){ return `bookando-tab-${String(t.value).replace(/\s+/g, '-')}` }
function panelId(t:TabItem){ return `bookando-tabpanel-${String(t.value).replace(/\s+/g, '-')}` }

// ==== layout / overflow
type Rect = { left:number; right:number; width:number; index:number }
let ro:ResizeObserver | null = null
let recalcRaf = 0
const tabRects = ref<Rect[]>([])
const showLeft = ref(false), showRight = ref(false), hasOverflow = ref(false)

const centered = computed(() => props.variant === 'centered' && !hasOverflow.value)

function recalcLayout(){
  cancelAnimationFrame(recalcRaf)
  recalcRaf = requestAnimationFrame(() => {
    const el = navRef.value; if (!el) return
    const list: Rect[] = []
    tabBtnRefs.value.forEach((btn, index) => {
      const left = btn.offsetLeft
      const width = btn.offsetWidth
      list.push({ left, width, right: left + width, index })
    })
    tabRects.value = list
    updateCarets()
    updateIndicator(true) // first update after layout: ohne Animation
    updateProgress()
  })
}

function updateCarets(){
  const el = navRef.value; if (!el) return
  const eps = 1
  const scrollW = Math.round(el.scrollWidth)
  const clientW = Math.round(el.clientWidth)
  showLeft.value  = el.scrollLeft > eps
  showRight.value = el.scrollLeft + clientW < scrollW - eps
  hasOverflow.value = scrollW > clientW + eps
}

// Utility: Indicator setzen (+ optional „instant“ ohne Transition)
function setIndicator(x:number, w:number, instant = false){
  const ind = indicatorRef.value; if (!ind) return
  if (props.indicatorThickness) {
    ind.style.setProperty('--bookando-tab-indicator-h', `${props.indicatorThickness}px`)
  }
  if (instant) ind.classList.add('is-no-animate')
  // neue Variablen: translateX + scaleX
  ind.style.setProperty('--bookando-tab-x', `${x}px`)
  ind.style.setProperty('--bookando-tab-scale', String(Math.max(1, w))) // unitless
  if (instant) requestAnimationFrame(() => ind.classList.remove('is-no-animate'))
}

// ==== indicator (width + translateX)
function updateIndicator(instant = false){
  const el = navRef.value
  const ind = indicatorRef.value
  if (!el || !ind) return

  const idx = normalizedTabs.value.findIndex(t => t.value === props.modelValue)
  if (idx < 0) return
  const r = tabRects.value[idx]; if (!r) return

  const inset = indicatorInset.value
  const x = r.left + inset
  const w = Math.max(1, r.width - inset * 2)
  setIndicator(x, w, instant)
}

// ==== hover magnetism
const indicatorHovering = ref(false)
let hoverTimer = 0
function cancelHover(){ clearTimeout(hoverTimer); indicatorHovering.value = false }
function onHoverEnter(idx:number){
  clearTimeout(hoverTimer)
  const el = navRef.value, ind = indicatorRef.value
  const r = tabRects.value[idx]
  if (!el || !ind || !r) return
  const inset = indicatorInset.value
  const hx = r.left + inset
  ind.style.setProperty('--bookando-tab-hover-x', `${hx}px`)
  ind.style.setProperty('--bookando-tab-hover-scale', String(Math.max(1, r.width - inset * 2)))
  indicatorHovering.value = true
}
function onHoverLeave(){
  clearTimeout(hoverTimer)
  hoverTimer = window.setTimeout(() => { indicatorHovering.value = false }, 60)
}

// ==== progress
const progress = ref(0)
const progressPercent = computed(() => `${Math.round(progress.value * 100)}%`)
function updateProgress(){
  const el = navRef.value; if (!el) { progress.value = 0; return }
  const max = Math.max(1, el.scrollWidth - el.clientWidth)
  progress.value = Math.min(1, Math.max(0, el.scrollLeft / max))
}

// ==== scroll / wheel (rAF throttled)
let rafScroll = 0
function onScroll(){
  cancelHover()
  if (rafScroll) return
  rafScroll = requestAnimationFrame(() => {
    rafScroll = 0
    updateCarets()
    updateIndicator() // falls sich Breiten live ändern
    updateProgress()
  })
}
function onWheel(e: WheelEvent){
  cancelHover()
  const el = navRef.value; if (!el) return
  const delta = e.shiftKey
    ? (Math.abs(e.deltaY) > Math.abs(e.deltaX) ? e.deltaY : e.deltaX)
    : (Math.abs(e.deltaY) > Math.abs(e.deltaX) ? e.deltaY : 0)
  if (delta !== 0){
    e.preventDefault()
    el.scrollLeft += delta
  }
}

// ==== chevrons → next voll sichtbarer Tab
function onChevron(dir:'left'|'right'){
  cancelHover()
  const el = navRef.value; if (!el) return
  const rects = tabRects.value
  if (!rects.length) return
  const viewL = el.scrollLeft
  const viewR = viewL + el.clientWidth

  if (dir === 'right'){
    const cand = rects.find(r => r.right > viewR + 1)
    if (cand) el.scrollTo({ left: cand.left, behavior: 'smooth' })
    else el.scrollTo({ left: el.scrollWidth - el.clientWidth, behavior: 'smooth' })
  } else {
    const cand = [...rects].reverse().find(r => r.left < viewL - 1)
    if (cand) el.scrollTo({ left: cand.left, behavior: 'smooth' })
    else el.scrollTo({ left: 0, behavior: 'smooth' })
  }
}

// ==== Animation der Scrollbar
function animateToIndex(idx:number){
  const r = tabRects.value[idx]; if (!r) return
  const inset = indicatorInset.value
  setIndicator(r.left + inset, Math.max(1, r.width - inset * 2), false)
}

// ==== click/select – Hover hart beenden → kein „zu schmal dann größer“
function onTabClick(idx:number, t:TabItem){
  if (t.disabled) return
  cancelHover()
  animateToIndex(idx)                // ← sofort animieren
  emit('update:modelValue', t.value) // v-model nachziehen
  ensureFullyVisible(idx)
  if (props.recenterOnSelect) {
    tabBtnRefs.value[idx]?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' })
  }
}

// ==== selection helper
function ensureFullyVisible(idx:number){
  const el = navRef.value; if (!el) return
  const r = tabRects.value[idx]; if (!r) return
  const viewL = el.scrollLeft
  const viewR = viewL + el.clientWidth
  if (r.left < viewL || r.right > viewR) el.scrollTo({ left: r.left, behavior:'smooth' })
}

// ==== drag-to-scroll mit Momentum + Snap
let dragging = false, lastX = 0, vx = 0, lastT = 0, rafMomentum = 0, startX = 0
const DRAG_THRESHOLD = 6

function onPointerDown(e: PointerEvent){
  cancelHover()
  const el = navRef.value; if (!el) return
  if (e.button !== 0) return
  if ((e.target as HTMLElement)?.closest('.bookando-tab')) return

  startX = e.clientX
  lastX = e.clientX
  vx = 0
  lastT = performance.now()
  dragging = false

  const onMove = (ev: PointerEvent) => {
    const now = performance.now()
    const dx = ev.clientX - lastX
    const totalDx = Math.abs(ev.clientX - startX)

    if (!dragging && totalDx > DRAG_THRESHOLD) {
      el.setPointerCapture(ev.pointerId)
      dragging = true
    }
    if (!dragging) { lastX = ev.clientX; lastT = now; return }

    const dt = Math.max(1, now - lastT)
    el.scrollLeft -= dx
    vx = (dx) / dt
    lastX = ev.clientX
    lastT = now
    updateCarets()
    updateIndicator()
    updateProgress()
  }

  const onUp = () => {
    el.removeEventListener('pointermove', onMove as any)
    el.removeEventListener('pointerup', onUp as any)
    el.removeEventListener('pointercancel', onUp as any)

    if (!dragging) return
    const friction = 0.0022
    cancelAnimationFrame(rafMomentum)
    const step = (x:number) => {
      const dt = 16
      const decay = Math.exp(-friction * dt)
      x *= decay
      el.scrollLeft -= x * dt
      updateCarets(); updateIndicator(); updateProgress()
      if (Math.abs(x) > 0.02) {
        rafMomentum = requestAnimationFrame(() => step(x))
      } else {
        snapToNearest()
      }
    }
    rafMomentum = requestAnimationFrame(() => step(vx))
  }

  el.addEventListener('pointermove', onMove, { passive: true })
  el.addEventListener('pointerup', onUp, { once: true })
  el.addEventListener('pointercancel', onUp, { once: true })
}

function snapToNearest(){
  const el = navRef.value; if (!el) return
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

  const viewL = el.scrollLeft
  const endL  = el.scrollWidth - el.clientWidth
  if (endL - viewL < 24) return

  let best: Rect | undefined
  let bestDist = Infinity
  for (const r of tabRects.value){
    const d = Math.abs(r.left - viewL)
    if (d < bestDist){ best = r; bestDist = d }
  }
  if (best && bestDist < 24) el.scrollTo({ left: best.left, behavior:'smooth' })
}

// ==== Swipe nav: next/prev aktiver Tab (disabled überspringen)
function nextTab() {
  const list = normalizedTabs.value
  const i = list.findIndex(t => t.value === props.modelValue)
  for (let k = i+1; k < list.length; k++) if (!list[k].disabled) { emit('update:modelValue', list[k].value); return }
}
function prevTab() {
  const list = normalizedTabs.value
  const i = list.findIndex(t => t.value === props.modelValue)
  for (let k = i-1; k >= 0; k--) if (!list[k].disabled) { emit('update:modelValue', list[k].value); return }
}

// Swipe-Gesten (horizontal), Inputs ausnehmen
let swipeStartX = 0, swipeStartY = 0, swiping = false, moved = false
function isTextInput(el: EventTarget | null) {
  const n = el as HTMLElement | null
  return !!n?.closest('input, textarea, select, [contenteditable="true"]')
}
function onTouchStart(e: TouchEvent) {
  if (!swipeable.value) return
  if (isTextInput(e.target)) return
  const t = e.touches[0]
  swipeStartX = t.clientX
  swipeStartY = t.clientY
  swiping = true
  moved = false
}
function onTouchMove(e: TouchEvent) {
  if (!swiping) return
  const t = e.touches[0]
  const dx = t.clientX - swipeStartX
  const dy = t.clientY - swipeStartY
  if (Math.abs(dx) > Math.abs(dy) * 1.2) {
    e.preventDefault()
    moved = true
  }
}
// Pointer-agnostisch: dx beim Move merken (TouchEvent liefert kein dx auf End)
let lastDX = 0
function onTouchMoveSave(e: TouchEvent) {
  if (!swiping) return
  const t = e.touches[0]
  lastDX = t.clientX - swipeStartX
  ;(window as any)._bookando_last_dx = lastDX // falls du das anderswo nutzt
}
function onTouchEndApply() {
  if (!swiping) return
  swiping = false
  if (!moved) { lastDX = 0; return }
  const dx = lastDX
  if (Math.abs(dx) >= threshold.value) {
    if (dx < 0) nextTab()
    else prevTab()
  }
  lastDX = 0
  moved = false
}

// ==== keyboard nav
function onKeydown(e:KeyboardEvent){
  cancelHover()
  const btns = tabBtnRefs.value
  if (!btns.length) return
  const current = btns.findIndex(b => b === document.activeElement)
  const max = btns.length - 1
  const focus = (i:number)=>{
    const t = Math.max(0, Math.min(i, max))
    btns[t]?.focus()
    ensureFullyVisible(t)
  }
  switch (e.key){
    case 'ArrowRight': e.preventDefault(); focus((current<0?0:current+1)); break
    case 'ArrowLeft':  e.preventDefault(); focus((current<0?0:current-1)); break
    case 'Home':       e.preventDefault(); focus(0); break
    case 'End':        e.preventDefault(); focus(max); break
    case 'Enter':
    case ' ':          e.preventDefault(); if (current>=0){ const t = normalizedTabs.value[current]; if (!t.disabled) emit('update:modelValue', t.value) } break
  }
}

// ==== lifecycle
onMounted(async ()=>{
  await nextTick()
  readCssInset()
  recalcLayout()
  // Fonts können nachladen → Breiten ändern
  // @ts-ignore
  if (document.fonts?.ready) document.fonts.ready.then(() => recalcLayout())
  ro = new ResizeObserver(() => recalcLayout())
  if (navRef.value) ro.observe(navRef.value)
  window.addEventListener('resize', recalcLayout, { passive:true })

  // Swipe nur wenn Panels existieren
  if (!props.navOnly && rootRef.value) {
    const root = rootRef.value
    root.addEventListener('touchstart', onTouchStart, { passive: true })
    root.addEventListener('touchmove',  onTouchMove,  { passive: false })
    root.addEventListener('touchmove',  onTouchMoveSave, { passive: true })
    root.addEventListener('touchend',   onTouchEndApply, { passive: true })
  }
})

onBeforeUnmount(()=>{
  window.removeEventListener('resize', recalcLayout)
  if (ro && navRef.value) ro.unobserve(navRef.value)
  ro = null
  cancelAnimationFrame(recalcRaf)
  cancelAnimationFrame(rafMomentum)

  if (rootRef.value) {
    rootRef.value.removeEventListener('touchstart', onTouchStart as any)
    rootRef.value.removeEventListener('touchmove', onTouchMove as any)
    rootRef.value.removeEventListener('touchmove', onTouchMoveSave as any)
    rootRef.value.removeEventListener('touchend', onTouchEndApply as any)
  }
})

watch([normalizedTabs], async () => {
  await nextTick()
  recalcLayout()
})

// Wenn modelValue extern geändert wird → sofort korrekte Breite setzen (ohne kurzes „Zwischenmaß“)
watch(() => props.modelValue, async () => {
  await nextTick()
  const idx = normalizedTabs.value.findIndex(t => t.value === props.modelValue)
  if (idx >= 0) ensureFullyVisible(idx)
  cancelHover()
  updateIndicator()
})
</script>
