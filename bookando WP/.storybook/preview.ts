import type { Preview } from '@storybook/vue3'
import { setup } from '@storybook/vue3'
import { createI18n } from 'vue-i18n'

// Import global styles
import '../src/Core/Design/assets/scss/admin-ui.scss'

// Setup I18n
const i18n = createI18n({
  legacy: false,
  locale: 'de',
  messages: {
    de: {
      'ui.badge.remove': 'Entfernen',
      'ui.alert.close': 'Schließen',
      'ui.skeleton.loading': 'Lädt...',
      'ui.dialog.confirm_title': 'Bestätigen',
      'core.bulk.confirmMessage': 'Möchten Sie fortfahren?',
      'core.common.cancel': 'Abbrechen',
      'core.bulk.confirm': 'Bestätigen'
    }
  }
})

setup((app) => {
  app.use(i18n)
})

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i
      }
    },
    backgrounds: {
      default: 'light',
      values: [
        { name: 'light', value: '#ffffff' },
        { name: 'dark', value: '#1a1a1a' },
        { name: 'gray', value: '#f3f4f6' }
      ]
    }
  }
}

export default preview
