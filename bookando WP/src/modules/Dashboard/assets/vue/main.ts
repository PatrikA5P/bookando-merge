import { createApp } from 'vue'
import { createPinia } from 'pinia'
import DashboardView from './views/DashboardView.vue'

const app = createApp(DashboardView)
app.use(createPinia())

const rootElement = document.getElementById('bookando-dashboard-root')
if (rootElement) {
  app.mount(rootElement)
}
