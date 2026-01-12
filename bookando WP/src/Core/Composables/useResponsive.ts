// src/Core/Composables/useResponsive.ts
import { readonly, ref, onMounted, onUnmounted } from 'vue'

type BP = 'sm'|'md'|'lg'|'xl'
const _state = {
  sm: ref(false),
  md: ref(false),
  lg: ref(false),
  xl: ref(false),
}
let _inited = false
let _mqs: Partial<Record<BP, MediaQueryList>> = {}
let _onChange: ((_e: MediaQueryListEvent) => void) | null = null

function getVar(bp: BP) {
  const raw = getComputedStyle(document.documentElement).getPropertyValue(`--bookando-breakpoint-${bp}`)
  const px = parseInt(raw, 10)
  return Number.isFinite(px) ? px : 0
}

function init() {
  if (_inited) return
  (['sm','md','lg','xl'] as BP[]).forEach(bp => {
    const px = getVar(bp)
    const mq = window.matchMedia(`(max-width: ${px}px)`)
    _mqs[bp] = mq
    _state[bp].value = mq.matches
  })
  _onChange = () => {
    (['sm','md','lg','xl'] as BP[]).forEach(bp => { _state[bp].value = !!_mqs[bp]?.matches })
  }
  Object.values(_mqs).forEach(mq => mq?.addEventListener('change', _onChange!))
  _inited = true
}

export function useResponsive() {
  onMounted(() => init())
  onUnmounted(() => {})
  const isBelow = (bp: BP) => readonly(_state[bp])
  // Komfort-Shortcuts
  const isBelowSm = readonly(_state.sm)
  const isBelowMd = readonly(_state.md)
  const isBelowLg = readonly(_state.lg)
  const isBelowXl = readonly(_state.xl)
  // isMobile (md) nur als Legacy:
  const isMobile = isBelowMd
  return { isBelow, isBelowSm, isBelowMd, isBelowLg, isBelowXl, isMobile }
}

