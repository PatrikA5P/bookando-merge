import '../css/admin.scss'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'

import BookandoUI from '@core/Design/assets/vue/ui.js'
import { messages as coreMessages } from '@core/Design/i18n'
import { bootLocaleFromBridge, initLocaleBridge } from '@core/Locale/bridge'
import { mergeI18nMessages } from '@core/Locale/messages'

import ToolsView from './views/ToolsView.vue'

const localModules = import.meta.glob('./i18n.local.{ts,js}', { eager: true })
const messages = mergeI18nMessages(coreMessages, localModules)

const { i18nLocale } = bootLocaleFromBridge({
  available: Object.keys(messages),
  fallback: 'de',
})

const i18n = createI18n({
  legacy: false,
  locale: i18nLocale,
  fallbackLocale: 'de',
  messages,
})

const slug = (window as any).BOOKANDO_VARS?.slug || 'tools'
const mountSelector = `#bookando-${slug}-root`
const root = document.querySelector(mountSelector) as HTMLElement | null

if (root && !root.hasAttribute('data-v-app')) {
  const app = createApp(ToolsView)
  app.use(i18n).use(createPinia()).use(BookandoUI)
  initLocaleBridge(i18n)
  app.mount(root)
} else {
  console.warn(`[Bookando] Mountpoint ${mountSelector} nicht gefunden oder bereits gemountet.`)
}
