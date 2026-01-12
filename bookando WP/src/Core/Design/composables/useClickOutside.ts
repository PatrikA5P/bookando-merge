/**
 * useClickOutside
 *
 * Composable zum Erkennen von Klicks außerhalb eines Elements
 *
 * @example
 * ```vue
 * <script setup>
 * import { ref } from 'vue'
 * import { useClickOutside } from '@core/Design/composables/useClickOutside'
 *
 * const dropdownRef = ref<HTMLElement>()
 * const isOpen = ref(false)
 *
 * useClickOutside(dropdownRef, () => {
 *   isOpen.value = false
 * })
 * </script>
 *
 * <template>
 *   <div ref="dropdownRef">
 *     <!-- Dropdown content -->
 *   </div>
 * </template>
 * ```
 */

import { onMounted, onBeforeUnmount, unref, type Ref } from 'vue'

export interface UseClickOutsideOptions {
  /**
   * Event-Typ zum Lauschen
   * @default 'click'
   */
  event?: 'click' | 'mousedown' | 'mouseup' | 'pointerdown' | 'pointerup'

  /**
   * Ob das Event in der Capture-Phase behandelt werden soll
   * @default true
   */
  capture?: boolean

  /**
   * Ob das Composable sofort aktiv sein soll
   * @default true
   */
  immediate?: boolean

  /**
   * Ignore-Selektoren: Klicks auf diese Elemente werden ignoriert
   */
  ignore?: string[]
}

export function useClickOutside(
  target: Ref<HTMLElement | null | undefined> | HTMLElement | null | undefined,
  handler: (event: Event) => void,
  options: UseClickOutsideOptions = {}
) {
  const {
    event = 'click',
    capture = true,
    immediate = true,
    ignore = []
  } = options

  if (!immediate) {
    return {
      start: () => {},
      stop: () => {}
    }
  }

  let isActive = false

  const listener = (e: Event) => {
    const el = unref(target)

    if (!el || !isActive) return

    // Check ob Klick innerhalb des Elements war
    const isClickInside = el === e.target || el.contains(e.target as Node)

    if (isClickInside) return

    // Check ob Klick auf ignoriertes Element war
    if (ignore.length > 0) {
      const isIgnored = ignore.some(selector => {
        const ignoredElements = document.querySelectorAll(selector)
        return Array.from(ignoredElements).some(ignoredEl =>
          ignoredEl === e.target || ignoredEl.contains(e.target as Node)
        )
      })

      if (isIgnored) return
    }

    // Trigger Handler
    handler(e)
  }

  const start = () => {
    if (isActive) return

    // Verzögere die Aktivierung, damit der initiale Klick nicht gezählt wird
    setTimeout(() => {
      isActive = true
      document.addEventListener(event, listener, capture)
    }, 0)
  }

  const stop = () => {
    isActive = false
    document.removeEventListener(event, listener, capture)
  }

  onMounted(start)
  onBeforeUnmount(stop)

  return {
    start,
    stop
  }
}
