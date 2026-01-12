/**
 * Test Utilities für Bookando Design System
 *
 * Wiederverwendbare Helper für Component-Tests
 */

import { render, type RenderOptions } from '@testing-library/vue'
import { createI18n } from 'vue-i18n'
import type { Component, App } from 'vue'

// Mock I18n für Tests
const i18n = createI18n({
  legacy: false,
  locale: 'de',
  messages: {
    de: {
      'ui.badge.remove': 'Entfernen',
      'ui.alert.close': 'Schließen',
      'ui.skeleton.loading': 'Lädt...',
      'ui.dialog.confirm_title': 'Bestätigen',
      'ui.tabs.scroll_left': 'Nach links scrollen',
      'ui.tabs.scroll_right': 'Nach rechts scrollen',
      'core.bulk.confirmMessage': 'Möchten Sie fortfahren?',
      'core.common.cancel': 'Abbrechen',
      'core.bulk.confirm': 'Bestätigen'
    }
  }
})

/**
 * Custom Render mit automatischem I18n-Plugin
 */
export function renderWithI18n(
  component: Component,
  options: RenderOptions = {}
) {
  return render(component, {
    global: {
      plugins: [i18n],
      ...options.global
    },
    ...options
  })
}

/**
 * Warte auf asynchrone DOM-Updates
 */
export const waitFor = (ms: number = 0) =>
  new Promise(resolve => setTimeout(resolve, ms))

/**
 * Simuliere User-Event mit Verzögerung
 */
export async function userEvent(callback: () => void, delay: number = 0) {
  await callback()
  await waitFor(delay)
}

/**
 * Finde Element by Test-ID
 */
export function getByTestId(container: HTMLElement, testId: string): HTMLElement {
  const element = container.querySelector(`[data-testid="${testId}"]`)
  if (!element) {
    throw new Error(`Element with test-id "${testId}" not found`)
  }
  return element as HTMLElement
}

/**
 * Prüfe ob Element sichtbar ist
 */
export function isVisible(element: HTMLElement): boolean {
  return (
    element.offsetWidth > 0 &&
    element.offsetHeight > 0 &&
    window.getComputedStyle(element).visibility !== 'hidden' &&
    window.getComputedStyle(element).display !== 'none'
  )
}

/**
 * Simuliere Tastatur-Event
 */
export function fireKeyboardEvent(
  element: HTMLElement,
  key: string,
  type: 'keydown' | 'keyup' | 'keypress' = 'keydown'
) {
  const event = new KeyboardEvent(type, {
    key,
    bubbles: true,
    cancelable: true
  })
  element.dispatchEvent(event)
}

/**
 * Simuliere Click-Event
 */
export function fireClickEvent(element: HTMLElement) {
  const event = new MouseEvent('click', {
    bubbles: true,
    cancelable: true
  })
  element.dispatchEvent(event)
}

/**
 * Warte auf Element
 */
export async function waitForElement(
  container: HTMLElement,
  selector: string,
  timeout: number = 3000
): Promise<HTMLElement> {
  const startTime = Date.now()

  while (Date.now() - startTime < timeout) {
    const element = container.querySelector(selector)
    if (element) {
      return element as HTMLElement
    }
    await waitFor(50)
  }

  throw new Error(`Element "${selector}" not found within ${timeout}ms`)
}

/**
 * Mock console.error/warn
 */
export function mockConsole() {
  const originalError = console.error
  const originalWarn = console.warn

  console.error = vi.fn()
  console.warn = vi.fn()

  return {
    restore: () => {
      console.error = originalError
      console.warn = originalWarn
    }
  }
}

/**
 * Erstelle Mock-Funktion mit Spy
 */
export function createSpy() {
  return vi.fn()
}
