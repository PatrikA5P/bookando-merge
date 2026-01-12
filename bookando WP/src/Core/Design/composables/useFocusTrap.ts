/**
 * useFocusTrap
 *
 * Wrapper um focus-trap-vue mit verbesserter DX
 * Hält den Fokus innerhalb eines Elements (z.B. Modals, Dropdowns)
 *
 * @example
 * ```vue
 * <script setup>
 * import { ref, watch } from 'vue'
 * import { useFocusTrap } from '@core/Design/composables/useFocusTrap'
 *
 * const modalRef = ref<HTMLElement>()
 * const isOpen = ref(false)
 *
 * const { activate, deactivate } = useFocusTrap(modalRef, {
 *   immediate: false,
 *   returnFocusOnDeactivate: true
 * })
 *
 * watch(isOpen, (open) => {
 *   if (open) activate()
 *   else deactivate()
 * })
 * </script>
 * ```
 */

import { onBeforeUnmount, unref, type Ref, ref } from 'vue'
import { createFocusTrap, type Options as FocusTrapOptions, type FocusTrap } from 'focus-trap'

export interface UseFocusTrapOptions extends Partial<FocusTrapOptions> {
  /**
   * Ob der Focus-Trap sofort aktiviert werden soll
   * @default false
   */
  immediate?: boolean

  /**
   * Ob der Fokus beim Deaktivieren zurückgegeben werden soll
   * @default true
   */
  returnFocusOnDeactivate?: boolean

  /**
   * Ob Klicks außerhalb erlaubt sein sollen
   * @default true
   */
  allowOutsideClick?: boolean | ((e: MouseEvent | TouchEvent) => boolean)

  /**
   * Ob ESC die Trap deaktivieren soll
   * @default false (wir handhaben ESC meist selbst)
   */
  escapeDeactivates?: boolean

  /**
   * Initial zu fokussierendes Element
   * @default false (auto-fokussiert erstes fokussierbares Element)
   */
  initialFocus?: false | string | (() => HTMLElement)
}

export function useFocusTrap(
  target: Ref<HTMLElement | null | undefined> | HTMLElement | null | undefined,
  options: UseFocusTrapOptions = {}
) {
  const {
    immediate = false,
    returnFocusOnDeactivate = true,
    allowOutsideClick = true,
    escapeDeactivates = false,
    initialFocus = false,
    ...focusTrapOptions
  } = options

  let trap: FocusTrap | null = null
  let isActive = false
  const hasFocus = ref(false)

  const activate = () => {
    if (isActive) return

    const el = unref(target)
    if (!el) {
      console.warn('useFocusTrap: Cannot activate - target element is not available')
      return
    }

    try {
      // Erstelle Focus-Trap falls noch nicht vorhanden
      if (!trap) {
        trap = createFocusTrap(el, {
          allowOutsideClick,
          escapeDeactivates,
          returnFocusOnDeactivate,
          initialFocus: initialFocus === false ? false : (initialFocus || undefined),
          ...focusTrapOptions,
          onActivate: () => {
            hasFocus.value = true
            focusTrapOptions.onActivate?.()
          },
          onDeactivate: () => {
            hasFocus.value = false
            focusTrapOptions.onDeactivate?.()
          }
        })
      }

      trap.activate()
      isActive = true
    } catch (error) {
      console.error('useFocusTrap: Failed to activate', error)
    }
  }

  const deactivate = () => {
    if (!isActive || !trap) return

    try {
      trap.deactivate()
      isActive = false
    } catch (error) {
      console.error('useFocusTrap: Failed to deactivate', error)
    }
  }

  // Sofortige Aktivierung wenn gewünscht
  if (immediate) {
    activate()
  }

  // Cleanup beim Unmount
  onBeforeUnmount(() => {
    if (isActive) {
      deactivate()
    }
  })

  return {
    activate,
    deactivate,
    hasFocus,
    isActive: () => isActive
  }
}
