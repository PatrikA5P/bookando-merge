// src/core/composables/useBreakpoint.ts
import { ref, onMounted, onUnmounted } from 'vue'

function getBreakpointVar(bp: string) {
  const val = getComputedStyle(document.documentElement).getPropertyValue(bp)
  return parseInt(val) || 0
}

export function useBreakpoint(breakpoint: 'sm' | 'md' | 'lg' | 'xl' = 'md') {
  const isBelow = ref(false)
  let mq: MediaQueryList
  let update: () => void

  onMounted(() => {
    const px = getBreakpointVar(`--bookando-breakpoint-${breakpoint}`)
    mq = window.matchMedia(`(max-width: ${px}px)`)
    update = () => { isBelow.value = mq.matches }
    mq.addEventListener('change', update)
    update()
  })

  onUnmounted(() => {
    mq?.removeEventListener('change', update)
  })

  return { isMobile: isBelow }
}
