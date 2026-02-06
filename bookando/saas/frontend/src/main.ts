/**
 * Bookando SaaS Frontend — Entry Point
 */
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { VueQueryPlugin } from '@tanstack/vue-query';
import App from './App.vue';
import router from './router';

const app = createApp(App);

// Pinia (State Management)
const pinia = createPinia();
app.use(pinia);

// Vue Router
app.use(router);

// Vue Query (Server State Management)
app.use(VueQueryPlugin, {
  queryClientConfig: {
    defaultOptions: {
      queries: {
        staleTime: 30_000,       // 30s — Daten gelten als "frisch"
        gcTime: 5 * 60_000,      // 5min — Garbage Collection
        retry: 1,                 // 1 Retry bei Fehler
        refetchOnWindowFocus: true,
      },
    },
  },
});

// Global Error Handler
app.config.errorHandler = (err, instance, info) => {
  console.error('[Bookando Error]', err, info);
  // TODO: Sentry/Error-Tracking
};

app.mount('#app');
