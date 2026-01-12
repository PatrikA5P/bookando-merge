<!-- AppSort.vue -->
<template>
  <div
    ref="root"
    class="bookando-sort-inline"
  >
    <!-- Normal: Dropdown mit intrinsischer Breite -->
    <AppDropdown
      v-if="!usePopover"
      class="bookando-sort-inline"
      :options="options"
      :model-value="value"
      option-key="value"
      option-label="label"
      :icon-left="'sort'"
      icon-size="lg"
      icon-color="var(--bookando-text)"
      :placeholder="t('Sortieren nach')"
      width="content"
      @update:model-value="emitValue"
    />

    <!-- Fallback: zu wenig Platz -> Popover mit Icon-Trigger -->
    <AppPopover
      v-else
      trigger-mode="icon"
      trigger-icon="sort"
      trigger-variant="standard"
      :offset="2"
      width="content"
      :panel-min-width="180"
      :panel-max-width="'min(92vw, 420px)'"
      :close-on-item-click="true"
    >
      <template #content>
        <AppDropdown
          class="bookando-sort-inline"
          :options="options"
          :model-value="value"
          option-key="value"
          option-label="label"
          width="full"
          @update:model-value="emitValue"
        />
      </template>
    </AppPopover>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import AppDropdown from '@core/Design/components/AppDropdown.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'

const { t } = useI18n()

const props = withDefaults(defineProps<{
  options: { value: string; label: string; icon?: string }[]
  value: string
  /** Schwelle in px, unter der Popover genutzt wird */
  threshold?: number
}>(), {
})

const emit = defineEmits<{
  (event: 'update:value', value: string): void
}>()

function emitValue(val: string) {
  emit('update:value', val)
}

const root = ref<HTMLElement | null>(null)
const usePopover = ref(false)
let ro: ResizeObserver | null = null

function getAvailableWidth(): number {
  const el = root.value
  if (!el) return 0

  const container = el.closest('.bookando-filter-bar-center') as HTMLElement | null
  const rRoot = el.getBoundingClientRect()
  const rCont = container?.getBoundingClientRect()

  // rechter Rand = Sichtbereich des Viewports ODER rechter Rand der Center-Spalte – je nachdem, was kleiner ist
  const viewportRight = document.documentElement.clientWidth
  const rightEdge = Math.min(rCont ? rCont.right : viewportRight, viewportRight)

  // sichtbarer Platz vom linken Rand des Sort-Roots bis zum rechten Rand
  return Math.max(0, Math.floor(rightEdge - rRoot.left))
}

function updateWidthBasedMode() {
  const el = root.value
  if (!el) return

  const trigger = el.querySelector('.bookando-combobox-btn') as HTMLElement | null

  // tatsächlich verfügbarer Platz (sichtbar)
  const available = getAvailableWidth()

  // benötigte Breite für den aktuellen Trigger (Icon + Text + Chevron)
  const needed = trigger ? Math.ceil(trigger.getBoundingClientRect().width) : 140

  // Overflow-Erkennung (falls der Button bereits beschnitten würde)
  const isOverflowing = !!trigger && trigger.scrollWidth > trigger.clientWidth

  // explizite Schwelle erlaubt Override; sonst die gemessene "needed"-Breite benutzen
  const threshold = typeof props.threshold === 'number' ? props.threshold : needed

  usePopover.value = available < threshold || isOverflowing
}

function attachObserver() {
  ro?.disconnect()
  ro = new ResizeObserver(() => updateWidthBasedMode())
  // beobachte sowohl den eigenen Root als auch die Center-Spalte (Platz ändert sich oft dort)
  const el = root.value
  const center = el?.closest('.bookando-filter-bar-center') as HTMLElement | null
  if (el) ro.observe(el)
  if (center) ro.observe(center)
}

onMounted(async () => {
  await nextTick()
  updateWidthBasedMode()
  attachObserver()
  window.addEventListener('resize', updateWidthBasedMode)
})

onBeforeUnmount(() => {
  ro?.disconnect()
  window.removeEventListener('resize', updateWidthBasedMode)
})
</script>

<style scoped>
.bookando-sort-inline {
  display: inline-flex;
  max-width: 100%;
  min-width: 0;      /* ⬅️ erlaubt Schrumpfen unter min-content */
}
</style>
