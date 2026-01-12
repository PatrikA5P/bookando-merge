/**
 * useBreakpoints
 *
 * Reactive Breakpoint-Detection für Responsive Design
 *
 * @example
 * ```vue
 * <script setup>
 * import { useBreakpoints } from '@core/Design/composables/useBreakpoints'
 *
 * const { isMobile, isTablet, isDesktop, current, greaterOrEqual } = useBreakpoints()
 *
 * // Reactive usage
 * watch(isMobile, (mobile) => {
 *   console.log('Is mobile:', mobile)
 * })
 *
 * // In template
 * if (greaterOrEqual('md')) {
 *   // Show desktop navigation
 * }
 * </script>
 * ```
 */

import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

/**
 * Bookando Breakpoints (aus _mixins.scss)
 *
 * xs: 0px
 * sm: 576px
 * md: 768px
 * lg: 992px
 * xl: 1200px
 * xxl: 1400px
 */
export const breakpoints = {
  xs: 0,
  sm: 576,
  md: 768,
  lg: 992,
  xl: 1200,
  xxl: 1400
} as const

export type Breakpoint = keyof typeof breakpoints

export interface UseBreakpointsReturn {
  /** Aktueller Viewport-Breite in px */
  width: Readonly<Ref<number>>

  /** Aktueller Breakpoint */
  current: Readonly<Ref<Breakpoint>>

  /** Ist Mobile (< md) */
  isMobile: Readonly<Ref<boolean>>

  /** Ist Tablet (md bis lg) */
  isTablet: Readonly<Ref<boolean>>

  /** Ist Desktop (>= lg) */
  isDesktop: Readonly<Ref<boolean>>

  /** Ist kleiner als Breakpoint */
  smaller: (breakpoint: Breakpoint) => boolean

  /** Ist größer als Breakpoint */
  greater: (breakpoint: Breakpoint) => boolean

  /** Ist kleiner oder gleich Breakpoint */
  smallerOrEqual: (breakpoint: Breakpoint) => boolean

  /** Ist größer oder gleich Breakpoint */
  greaterOrEqual: (breakpoint: Breakpoint) => boolean

  /** Ist zwischen zwei Breakpoints */
  between: (min: Breakpoint, max: Breakpoint) => boolean
}

export function useBreakpoints(): UseBreakpointsReturn {
  const width = ref(typeof window !== 'undefined' ? window.innerWidth : 0)

  // Aktueller Breakpoint
  const current = computed<Breakpoint>(() => {
    const w = width.value
    if (w >= breakpoints.xxl) return 'xxl'
    if (w >= breakpoints.xl) return 'xl'
    if (w >= breakpoints.lg) return 'lg'
    if (w >= breakpoints.md) return 'md'
    if (w >= breakpoints.sm) return 'sm'
    return 'xs'
  })

  // Convenience Flags
  const isMobile = computed(() => width.value < breakpoints.md)
  const isTablet = computed(() => width.value >= breakpoints.md && width.value < breakpoints.lg)
  const isDesktop = computed(() => width.value >= breakpoints.lg)

  // Comparison Functions
  const smaller = (breakpoint: Breakpoint): boolean => {
    return width.value < breakpoints[breakpoint]
  }

  const greater = (breakpoint: Breakpoint): boolean => {
    return width.value > breakpoints[breakpoint]
  }

  const smallerOrEqual = (breakpoint: Breakpoint): boolean => {
    return width.value <= breakpoints[breakpoint]
  }

  const greaterOrEqual = (breakpoint: Breakpoint): boolean => {
    return width.value >= breakpoints[breakpoint]
  }

  const between = (min: Breakpoint, max: Breakpoint): boolean => {
    return width.value >= breakpoints[min] && width.value < breakpoints[max]
  }

  // Resize Handler mit Debouncing
  let resizeTimer: number | undefined

  const updateWidth = () => {
    clearTimeout(resizeTimer)
    resizeTimer = window.setTimeout(() => {
      width.value = window.innerWidth
    }, 100) // 100ms Debounce
  }

  onMounted(() => {
    if (typeof window !== 'undefined') {
      width.value = window.innerWidth
      window.addEventListener('resize', updateWidth, { passive: true })
    }
  })

  onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('resize', updateWidth)
      clearTimeout(resizeTimer)
    }
  })

  return {
    width: computed(() => width.value),
    current: computed(() => current.value),
    isMobile: computed(() => isMobile.value),
    isTablet: computed(() => isTablet.value),
    isDesktop: computed(() => isDesktop.value),
    smaller,
    greater,
    smallerOrEqual,
    greaterOrEqual,
    between
  }
}
