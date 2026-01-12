// src/modules/customers/assets/vue/main.ts
const isDev = import.meta.env.DEV
if (isDev) console.log('[Bookando Customers] 🚀 Script loading...')

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import CustomersView from './views/CustomersView.vue'
import { messages as coreMessages } from '@core/Design/i18n'
import { bootLocaleFromBridge, initLocaleBridge } from '@core/Locale/bridge'
import { mergeI18nMessages } from '@core/Locale/messages'

import '../css/admin.scss'
import BookandoUI from '@core/Design/assets/vue/ui.js'

if (isDev) console.log('[Bookando Customers] ✅ Imports loaded successfully')

try {
  const localModules = import.meta.glob('./i18n.local.{ts,js}', { eager: true })
  if (isDev) console.log('[Bookando Customers] Local modules loaded')

  const messages = mergeI18nMessages(coreMessages, localModules)
  if (isDev) console.log('[Bookando Customers] Messages merged')

  // 1) Locale booten (setzt <html lang> & dayjs)
  const { i18nLocale } = bootLocaleFromBridge({ available: Object.keys(messages), fallback: 'de' })
  if (isDev) console.log('[Bookando Customers] Locale booted:', i18nLocale)

  // 2) vue-i18n
  const i18n = createI18n({ legacy: false, locale: i18nLocale, fallbackLocale: 'de', messages })
  if (isDev) console.log('[Bookando Customers] i18n instance created')

  // 3) mounten
  const slug = (window as any).BOOKANDO_VARS?.slug || 'customers'
  const root = document.querySelector(`#bookando-${slug}-root`) as HTMLElement | null

  if (isDev) console.log('[Bookando Customers] Mount check:', {
    slug,
    rootFound: !!root,
    rootElement: root,
    hasDataVApp: root?.hasAttribute('data-v-app')
  })

  if (root && !root.hasAttribute('data-v-app')) {
    if (isDev) console.log('[Bookando Customers] Creating Vue app...')
    const app = createApp(CustomersView)
    if (isDev) console.log('[Bookando Customers] Vue app created')

    if (isDev) console.log('[Bookando Customers] Installing i18n plugin...')
    app.use(i18n)

    if (isDev) console.log('[Bookando Customers] Installing Pinia plugin...')
    app.use(createPinia())

    if (isDev) console.log('[Bookando Customers] Installing BookandoUI plugin...')
    app.use(BookandoUI)

    if (isDev) console.log('[Bookando Customers] Initializing locale bridge...')
    initLocaleBridge(i18n)

    if (isDev) console.log('[Bookando Customers] Mounting app to DOM...')
    app.mount(root)

    if (isDev) console.log('[Bookando Customers] ✅ APP MOUNTED SUCCESSFULLY!')
  } else {
    if (!root) {
      console.error('[Bookando Customers] ❌ Mount failed: Root element not found!')
    } else if (root.hasAttribute('data-v-app')) {
      console.warn('[Bookando Customers] ⚠️ Mount skipped: Element already has data-v-app attribute')
    }
  }
} catch (error) {
  console.error('[Bookando Customers] ❌ FATAL ERROR during initialization:', error)
  if (error instanceof Error) {
    console.error('[Bookando Customers] Error name:', error.name)
    console.error('[Bookando Customers] Error message:', error.message)
    console.error('[Bookando Customers] Error stack:', error.stack)
    if ('cause' in error) {
      console.error('[Bookando Customers] Error cause:', error.cause)
    }
  }
  // Log BOOKANDO_VARS for debugging
  console.error('[Bookando Customers] Debug - BOOKANDO_VARS:', (window as any).BOOKANDO_VARS)
  console.error('[Bookando Customers] Debug - Document ready state:', document.readyState)
}
