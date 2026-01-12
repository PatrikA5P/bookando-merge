// vitest.ts

import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'jsdom',
    coverage: {
      reporter: ['text', 'html'],
    },
  },
  resolve: {
    alias: {
      '@core': path.resolve(__dirname, '../src/Core'),
      '@assets': path.resolve(__dirname, '../src/assets'),
      '@assets/http': path.resolve(__dirname, '../src/assets/http/index.ts'),
      '@http': path.resolve(__dirname, '../src/assets/http/index.ts'),
    },
  },
})
