// DEBUG VERSION OF main.ts to identify mount failure
console.log('[DEBUG] 1. Script loaded')

try {
  console.log('[DEBUG] 2. Importing Vue...')
  const { createApp } = await import('vue')
  console.log('[DEBUG] 3. Vue imported successfully')

  const { createPinia } = await import('pinia')
  console.log('[DEBUG] 4. Pinia imported successfully')

  const { createI18n } = await import('vue-i18n')
  console.log('[DEBUG] 5. Vue-i18n imported successfully')

  console.log('[DEBUG] 6. Importing CustomersView...')
  const CustomersView = await import('./views/CustomersView.vue')
  console.log('[DEBUG] 7. CustomersView imported successfully:', CustomersView)

  console.log('[DEBUG] 8. Importing i18n messages...')
  const { messages as coreMessages } = await import('@core/Design/i18n')
  console.log('[DEBUG] 9. Core messages imported')

  const { bootLocaleFromBridge, initLocaleBridge } = await import('@core/Locale/bridge')
  console.log('[DEBUG] 10. Locale bridge imported')

  const { mergeI18nMessages } = await import('@core/Locale/messages')
  console.log('[DEBUG] 11. Message merger imported')

  console.log('[DEBUG] 12. Loading SCSS...')
  await import('../css/admin.scss')
  console.log('[DEBUG] 13. SCSS loaded')

  const BookandoUI = await import('@core/Design/assets/vue/ui.js')
  console.log('[DEBUG] 14. BookandoUI imported')

  const localModules = import.meta.glob('./i18n.local.{ts,js}', { eager: true })
  console.log('[DEBUG] 15. Local modules loaded:', Object.keys(localModules))

  const messages = mergeI18nMessages(coreMessages, localModules)
  console.log('[DEBUG] 16. Messages merged')

  const { i18nLocale } = bootLocaleFromBridge({ available: Object.keys(messages), fallback: 'de' })
  console.log('[DEBUG] 17. Locale booted:', i18nLocale)

  const i18n = createI18n({ legacy: false, locale: i18nLocale, fallbackLocale: 'de', messages })
  console.log('[DEBUG] 18. i18n created')

  const slug = (window as any).BOOKANDO_VARS?.slug || 'customers'
  console.log('[DEBUG] 19. Slug:', slug)

  const root = document.querySelector(`#bookando-${slug}-root`) as HTMLElement | null
  console.log('[DEBUG] 20. Root element:', root)

  if (root && !root.hasAttribute('data-v-app')) {
    console.log('[DEBUG] 21. Creating Vue app...')
    const app = createApp(CustomersView.default || CustomersView)
    console.log('[DEBUG] 22. Vue app created')

    app.use(i18n)
    console.log('[DEBUG] 23. i18n registered')

    app.use(createPinia())
    console.log('[DEBUG] 24. Pinia registered')

    app.use(BookandoUI.default || BookandoUI)
    console.log('[DEBUG] 25. BookandoUI registered')

    initLocaleBridge(i18n)
    console.log('[DEBUG] 26. Locale bridge initialized')

    app.mount(root)
    console.log('[DEBUG] 27. ✅ APP MOUNTED SUCCESSFULLY!')
  } else {
    console.warn('[DEBUG] 28. ❌ Mountpoint not found or already mounted', { root, hasAttr: root?.hasAttribute('data-v-app') })
  }
} catch (error) {
  console.error('[DEBUG] ❌ FATAL ERROR:', error)
  console.error('[DEBUG] Error stack:', error.stack)
}
