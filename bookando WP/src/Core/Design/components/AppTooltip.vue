<!-- AppTooltip.vue -->
<template>
  <span
    class="bookando-tooltip-wrapper"
    @mouseenter="onShow"
    @mouseleave="onHide"
    @focusin="onShow"
    @focusout="onHide"
    @mousedown="onHide"
    @keydown="onKeyDown"
  >
    <!-- Trigger -->
    <slot />

    <!-- Body -->
    <transition name="bookando-tooltip-fade">
      <div
        v-if="show"
        class="bookando-tooltip"
        :class="['pos-' + position, panelClass]"
        role="tooltip"
        aria-live="polite"
        :style="{ maxWidth }"
      >
        <!-- Falls text gesetzt: einfacher Modus -->
        <template v-if="text">
          {{ text }}
        </template>

        <!-- Rich Content via Slot -->
        <slot
          v-else
          name="content"
        />
      </div>
    </transition>
  </span>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue'

const props = defineProps({
  /** Entweder einfacher Text... */
  text: { type: String, default: '' },
  /** ...oder Rich-Content via <template #content>. */
  position: { type: String, default: 'top' }, // 'top' | 'bottom' | 'left' | 'right'
  delay: { type: Number, default: 400 },
  maxWidth: { type: String, default: '420px' },
  panelClass: { type: String, default: '' } // z.B. 'bookando-p-sm bookando-grid-2'
})

const show = ref(false)
let timer: ReturnType<typeof setTimeout> | null = null

function scheduleShow() {
  clearTimer()
  timer = setTimeout(() => { show.value = true }, Math.max(0, props.delay))
}
function onShow() { if (!show.value) scheduleShow() }
function onHide() { clearTimer(); show.value = false }
function clearTimer() { if (timer) { clearTimeout(timer); timer = null } }

function onKeyDown(event: KeyboardEvent) {
  if (event.key === 'Enter' || event.key === ' ' || event.key === 'Escape') onHide()
}

onBeforeUnmount(() => clearTimer())
</script>
