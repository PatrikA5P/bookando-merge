// src/modules/settings/assets/vue/main.ts
import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import SettingsView from './views/SettingsView.vue'
import { messages as coreMessages } from '@core/Design/i18n'

import BookandoUI from '@core/Design/assets/vue/ui.js'
import { bootLocaleFromBridge, initLocaleBridge } from '@core/Locale/bridge'
import { mergeI18nMessages } from '@core/Locale/messages'

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

const slug = (window as any).BOOKANDO_VARS?.slug || 'settings'

const mountSelector = `#bookando-${slug}-root`
const root = document.querySelector(mountSelector) as HTMLElement | null

if (root && !root.hasAttribute('data-v-app')) {
  const app = createApp(SettingsView)
  app.use(i18n)
  app.use(BookandoUI)

  const off = initLocaleBridge(i18n)
  // optional: onUnmounted(() => off())

  app.mount(root)
} else {
  console.warn(`[Bookando] Mountpoint ${mountSelector} nicht gefunden oder bereits gemountet.`)
}
