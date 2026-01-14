// src/modules/partnerhub/assets/vue/main.ts
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import PartnerhubView from './views/PartnerhubView.vue'
import { messages as coreMessages } from '@core/Design/i18n'
import { bootLocaleFromBridge, initLocaleBridge } from '@core/Locale/bridge'
import { mergeI18nMessages } from '@core/Locale/messages'

import BookandoUI from '@core/Design/assets/vue/ui.js'

const localModules = import.meta.glob('./i18n.local.{ts,js}', { eager: true })
const messages = mergeI18nMessages(coreMessages, localModules)

// 1) Locale booten (setzt <html lang> & dayjs)
const { i18nLocale } = bootLocaleFromBridge({ available: Object.keys(messages), fallback: 'de' })

// 2) vue-i18n
const i18n = createI18n({ legacy: false, locale: i18nLocale, fallbackLocale: 'de', messages })

// 3) mounten
const slug = (window as any).BOOKANDO_VARS?.slug || 'partnerhub'
const root = document.querySelector(`#bookando-${slug}-root`) as HTMLElement | null
if (root && !root.hasAttribute('data-v-app')) {
  const app = createApp(PartnerhubView)
  app.use(i18n).use(createPinia()).use(BookandoUI)

  // hält vue-i18n & dayjs synchron, wenn Settings die Sprache ändern
  initLocaleBridge(i18n)

  app.mount(root)
} else {
  console.warn('[Bookando Partnerhub] Mountpoint nicht gefunden oder bereits gemountet.')
}
