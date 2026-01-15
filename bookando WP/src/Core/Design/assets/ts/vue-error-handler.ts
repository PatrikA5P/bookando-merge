/**
 * Vue 3 Error Handler Plugin
 *
 * Catches and displays Vue errors in development mode
 */

import type { App, ComponentPublicInstance } from 'vue'
import { devErrorDisplay } from './dev-error-display'

export interface VueErrorHandlerOptions {
  moduleSlug: string
  onError?: (error: Error, info: string) => void
}

export function createVueErrorHandler(options: VueErrorHandlerOptions) {
  return {
    install(app: App) {
      // Vue error handler
      app.config.errorHandler = (
        err: unknown,
        instance: ComponentPublicInstance | null,
        info: string
      ) => {
        const error = err instanceof Error ? err : new Error(String(err))

        // Log to dev error display
        devErrorDisplay.error('Vue Runtime Error', error, {
          component: instance?.$options?.name || instance?.$options?.__name || 'Unknown',
          lifecycle: info,
          props: instance?.$props,
          route: (window as any).location?.pathname
        })

        // Call custom error handler if provided
        if (options.onError) {
          options.onError(error, info)
        }
      }

      // Vue warning handler (dev only)
      if (import.meta?.env?.DEV) {
        app.config.warnHandler = (msg: string, instance: ComponentPublicInstance | null, trace: string) => {
          console.warn(
            `[Vue Warning - ${options.moduleSlug}]\n` +
            `Message: ${msg}\n` +
            `Component: ${instance?.$options?.name || 'Unknown'}\n` +
            `Trace: ${trace}`
          )
        }
      }

      // Global error handler for uncaught errors
      window.addEventListener('error', (event: ErrorEvent) => {
        // Check if error is from our module's script
        const isModuleError = event.filename?.includes(options.moduleSlug) ||
                              event.error?.stack?.includes(options.moduleSlug)

        if (isModuleError) {
          devErrorDisplay.error('Uncaught JavaScript Error', event.error || new Error(event.message), {
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno
          })
        }
      })

      // Unhandled promise rejection handler
      window.addEventListener('unhandledrejection', (event: PromiseRejectionEvent) => {
        const error = event.reason instanceof Error
          ? event.reason
          : new Error(String(event.reason))

        devErrorDisplay.error('Unhandled Promise Rejection', error, {
          promise: String(event.promise)
        })
      })
    }
  }
}
