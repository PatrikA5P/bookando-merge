<!-- AppInfoModal.vue -->
<template>
  <transition name="bookando-fade">
    <div
      v-if="open"
      class="bookando-notice"
      @mouseenter="pause"
      @mouseleave="resume"
    >
      <div
        class="bookando-notice__content"
        :class="[levelClass, { 'is-paused': isPaused }]"
        :style="{ '--bookando-notice-timeout': timeout + 'ms' }"
        :role="level === 'danger' ? 'alert' : 'status'"
        :aria-live="ariaLive"
        aria-atomic="true"
      >
        <!-- Close oben rechts -->
        <button
          type="button"
          class="bookando-notice__close"
          :aria-label="t?.('core.common.close') ?? 'Schliessen'"
          @click="close"
        >
          <AppIcon
            name="x"
            size="sm"
          />
        </button>

        <!-- Runder Status-Ring + Icon -->
        <span
          class="bookando-notice__icon"
          aria-hidden="true"
        >
          <AppIcon
            :name="levelIcon"
            size="sm"
          />
        </span>

        <!-- Textblock -->
        <div class="bookando-notice__body">
          <header class="bookando-notice__header">
            <strong>{{ title }}</strong>
          </header>
          <p class="bookando-notice__text">
            {{ displayMessage }}
          </p>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@core/Design/components/AppIcon.vue'

type Level = 'success' | 'info' | 'warning' | 'danger'
const { t, te } = useI18n()

const DEFAULT_TIMEOUT = 20000
const props = defineProps<{ defaultTimeoutMs?: number }>()
const defaultTimeout = computed(() => props.defaultTimeoutMs ?? DEFAULT_TIMEOUT)

const open = ref(false)
const message = ref<string>('')
const level = ref<Level>('info')
const timeout = ref<number>(defaultTimeout.value)

let tHandle: number | null = null
let remainingMs = defaultTimeout.value
let startedAt = 0
const isPaused = ref(false)

const title = computed(() => {
  switch (level.value) {
    case 'success': return t?.('core.common.success') ?? 'Erfolg'
    case 'warning': return t?.('core.common.hint')    ?? 'Hinweis'
    case 'danger':  return t?.('core.common.error')   ?? 'Fehler'
    default:        return t?.('core.common.info')    ?? 'Info'
  }
})
const levelClass = computed(() => ({
  'bookando-notice--success': level.value === 'success',
  'bookando-notice--warning': level.value === 'warning',
  'bookando-notice--danger' : level.value === 'danger',
  'bookando-notice--info'   : level.value === 'info',
}))
const levelIcon = computed(() => {
  switch (level.value) {
    case 'success': return 'check'
    case 'warning': return 'alert-triangle'
    case 'danger':  return 'x'
    default:        return 'info'
  }
})
const ariaLive = computed<'assertive'|'polite'>(() => level.value === 'danger' ? 'assertive' : 'polite')
const displayMessage = computed(() => {
  const msg = message.value?.toString() ?? ''
  return te && typeof te === 'function' && te(msg) ? (t(msg) as string) : msg
})

function startTimer(ms: number) {
  clearTimer()
  remainingMs = ms
  startedAt = Date.now()
  tHandle = window.setTimeout(close, remainingMs) as unknown as number
}
function clearTimer() {
  if (tHandle) { window.clearTimeout(tHandle); tHandle = null }
}
function show(l: Level, msg: string, ms?: number) {
  level.value = l
  message.value = msg
  timeout.value = typeof ms === 'number' ? ms : defaultTimeout.value
  open.value = true
  isPaused.value = false
  startTimer(timeout.value)
}
function pause() {
  if (!open.value || isPaused.value) return
  isPaused.value = true
  const elapsed = Date.now() - startedAt
  remainingMs = Math.max(0, remainingMs - elapsed)
  clearTimer()
}
function resume() {
  if (!open.value || !isPaused.value) return
  isPaused.value = false
  startTimer(remainingMs || 1)
}
function close() {
  open.value = false
  isPaused.value = false
  clearTimer()
}
function onEvt(event: Event) {
  const { level: l, message: m, timeoutMs } = (event as CustomEvent).detail || {}
  if (!m) return
  show((l || 'info') as Level, m, timeoutMs)
}
function onKey(event: KeyboardEvent) { if (event.key === 'Escape' && open.value) close() }

onMounted(() => {
  window.addEventListener('bookando:notify', onEvt as any)
  window.addEventListener('keydown', onKey)
})
onUnmounted(() => {
  window.removeEventListener('bookando:notify', onEvt as any)
  window.removeEventListener('keydown', onKey)
})
</script>
