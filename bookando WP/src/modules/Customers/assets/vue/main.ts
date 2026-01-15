// src/modules/customers/assets/vue/main.ts
import { devErrorDisplay } from '@core/Design/assets/ts/dev-error-display'
import { createVueErrorHandler } from '@core/Design/assets/ts/vue-error-handler'

const isDev = import.meta.env.DEV || (window as any).BOOKANDO_VARS?.debug
if (isDev) console.log('[Bookando Customers] 🚀 Script loading...')

devErrorDisplay.checkpoint('Script loaded', {
  isDev,
  timestamp: new Date().toISOString()
})

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import CustomersView from './views/CustomersView.vue'
import { messages as coreMessages } from '@core/Design/i18n'
import { bootLocaleFromBridge, initLocaleBridge } from '@core/Locale/bridge'
import { mergeI18nMessages } from '@core/Locale/messages'

import BookandoUI from '@core/Design/assets/vue/ui.js'

devErrorDisplay.checkpoint('Imports loaded')

try {
  const localModules = import.meta.glob('./i18n.local.{ts,js}', { eager: true })
  devErrorDisplay.checkpoint('Local i18n modules loaded', {
    moduleCount: Object.keys(localModules).length
  })

  const messages = mergeI18nMessages(coreMessages, localModules)
  devErrorDisplay.checkpoint('i18n messages merged', {
    languages: Object.keys(messages),
    totalKeys: Object.keys(messages.de || {}).length
  })

  // 1) Locale booten (setzt <html lang> & dayjs)
  const { i18nLocale } = bootLocaleFromBridge({ available: Object.keys(messages), fallback: 'de' })
  devErrorDisplay.checkpoint('Locale booted from bridge', {
    locale: i18nLocale,
    available: Object.keys(messages)
  })

  // 2) vue-i18n
  const i18n = createI18n({ legacy: false, locale: i18nLocale, fallbackLocale: 'de', messages })
  devErrorDisplay.checkpoint('Vue i18n instance created')

  // 3) mounten
  const slug = (window as any).BOOKANDO_VARS?.slug || 'customers'
  const root = document.querySelector(`#bookando-${slug}-root`) as HTMLElement | null

  // Initialize dev error display with root element
  devErrorDisplay.init(slug, root)

  devErrorDisplay.checkpoint('DOM mount point check', {
    slug,
    rootFound: !!root,
    hasDataVApp: root?.hasAttribute('data-v-app'),
    bookandoVars: (window as any).BOOKANDO_VARS
  })

  if (!root) {
    throw new Error(`Root element #bookando-${slug}-root not found in DOM`)
  }

  if (root.hasAttribute('data-v-app')) {
    devErrorDisplay.error(
      'Mount skipped',
      new Error('Element already has data-v-app attribute'),
      { element: root.outerHTML.substring(0, 200) }
    )
  } else {
    devErrorDisplay.checkpoint('Creating Vue app instance')
    const app = createApp(CustomersView)

    // Install error handler first
    devErrorDisplay.checkpoint('Installing error handler plugin')
    app.use(createVueErrorHandler({ moduleSlug: slug }))

    devErrorDisplay.checkpoint('Installing i18n plugin')
    app.use(i18n)

    devErrorDisplay.checkpoint('Installing Pinia plugin')
    app.use(createPinia())

    devErrorDisplay.checkpoint('Installing BookandoUI plugin')
    app.use(BookandoUI)

    devErrorDisplay.checkpoint('Initializing locale bridge')
    initLocaleBridge(i18n)

    devErrorDisplay.checkpoint('Mounting Vue app to DOM')
    app.mount(root)

    devErrorDisplay.checkpoint('✅ APP MOUNTED SUCCESSFULLY', {
      mountedAt: new Date().toISOString(),
      totalCheckpoints: devErrorDisplay.getCheckpoints().length
    })

    // Make debug info available in console
    if (isDev) {
      console.log('[Bookando Customers] ✅ Module loaded successfully!')
      console.log('[Bookando Customers] Debug info:', {
        checkpoints: devErrorDisplay.getCheckpoints(),
        exportDebugInfo: () => console.log(devErrorDisplay.exportCheckpoints())
      })
    }
  }
} catch (error) {
  const err = error instanceof Error ? error : new Error(String(error))

  devErrorDisplay.error('Fatal initialization error', err, {
    bookandoVars: (window as any).BOOKANDO_VARS,
    documentReadyState: document.readyState,
    location: window.location.href
  })

  // Also log to console
  console.error('[Bookando Customers] ❌ FATAL ERROR during initialization:', err)
  console.error('[Bookando Customers] Stack:', err.stack)
  console.error('[Bookando Customers] Debug info:', (window as any).BOOKANDO_VARS)
}
