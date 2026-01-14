// scripts/vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import fs from 'fs'
import purgecss from 'vite-plugin-purgecss'
import svgLoader from 'vite-svg-loader'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// -----------------------------------------------------------------------------
// Flags / CDN mapping
// -----------------------------------------------------------------------------
const USE_CDN = process.env.VITE_USE_CDN === 'true'
const PUBLIC_BASE = process.env.VITE_PUBLIC_BASE || '/wp-content/plugins/bookando/dist/'
const CDN_VUE_ESM   = 'https://cdn.jsdelivr.net/npm/vue@3.5.21/dist/vue.esm-browser.prod.js'
const CDN_PINIA_ESM = 'https://cdn.jsdelivr.net/npm/pinia@2.2.6/dist/pinia.esm-browser.prod.js'
const CDN_I18N_ESM  = 'https://cdn.jsdelivr.net/npm/vue-i18n@9.14.0/dist/vue-i18n.esm-browser.prod.js'

// -----------------------------------------------------------------------------
// Multi-Entry (alle Modul-SPAs + zentrales Admin-CSS)
// -----------------------------------------------------------------------------
const modulesPath = path.resolve(__dirname, '../src/modules')

function getModuleEntries() {
  const entries: Record<string, string> = {}

  if (fs.existsSync(modulesPath)) {
    fs.readdirSync(modulesPath, { withFileTypes: true })
      .filter((d) => d.isDirectory())
      .forEach((d) => {
        const slug = d.name.toLowerCase()
        const entry = path.join(modulesPath, d.name, 'assets/vue/main.ts')
        if (fs.existsSync(entry)) entries[slug] = entry
      })
  }

  // zentrales Admin-Stylesheet (als eigenes Entry)
  entries.bookando = path.resolve(__dirname, '../src/Core/Design/assets/scss/admin-ui.scss')

  console.log('📦 Generierte Vite-Einträge:', entries)
  return entries
}

// -----------------------------------------------------------------------------
// Aliases (Core + dynamische Modul-Aliase)
// -----------------------------------------------------------------------------
const alias: Record<string, string> = {
  '@core': path.resolve(__dirname, '../src/Core'),
  '@core/Locale': path.resolve(__dirname, '../src/Core/Locale'),
  '@scss': path.resolve(__dirname, '../src/Core/Design/assets/scss'),
  '@assets': path.resolve(__dirname, '../src/assets'),
  '@assets/http': path.resolve(__dirname, '../src/assets/http/index.ts'),
  '@http': path.resolve(__dirname, '../src/assets/http/index.ts'),
  '@icons': path.resolve(__dirname, '../src/Core/Design/assets/icons'),
}

if (fs.existsSync(modulesPath)) {
  fs.readdirSync(modulesPath, { withFileTypes: true })
    .filter((d) => d.isDirectory())
    .forEach((d) => {
      const slug = d.name.toLowerCase()
      alias[`/${slug}/main.ts`] = path.resolve(modulesPath, d.name, 'assets/vue/main.ts')
      alias[`/${slug}/main.css`] = path.resolve(modulesPath, d.name, 'assets/css/admin.scss')
    })
}

// -----------------------------------------------------------------------------
// Export
// -----------------------------------------------------------------------------
export default defineConfig({
  base: PUBLIC_BASE,

  server: {
    port: 5173,
    strictPort: true,
    fs: { strict: false },
  },

  plugins: [
    vue(),
    svgLoader({
      defaultImport: 'component',
      svgoConfig: {
        plugins: [
          { name: 'removeDimensions', active: true },
          { name: 'addAttributesToSVGElement', params: { attributes: [{ width: '1em' }, { height: '1em' }] } },
        ],
      },
    }),
    // PurgeCSS nur im Build anwenden
    purgecss({
      apply: 'build',
      content: [
        './src/**/*.vue',
        './src/**/*.ts',
        './src/**/*.js',
        './**/*.php',
        './src/Core/Design/assets/scss/**/*.scss',
      ],
      safelist: [
        /^bookando-/,
        /^dp__/,
        /^dp--/,
        /^dp__theme_/,
        /^calendar-/,
        /^v-popper/,
        /^teleport$/,
        /^is-/,
        /-enter-active$/,
        /-leave-active$/,
        /-enter-from$/,
        /-leave-to$/,
        /^liquid-toggle$/,
        /^indicator$/,
        /^indicator--masked$/,
        /^indicator__liquid$/,
        /^knockout$/,
        /^mask$/,
        /^wrapper$/,
        /^liquids$/,
        /^liquid__shadow$/,
        /^liquid__track$/,
        /^sr-only$/,
      ],
      defaultExtractor: (content: string) => content.match(/[\w-/:]+(?<!:)/g) || [],
    }),
  ],

  // Dev: Vue nicht vorbundlen (stabiles HMR); GSAP & focus-trap vorbundlen
  optimizeDeps: {
    exclude: ['vue'],
    include: ['gsap', 'gsap/Draggable', 'focus-trap', 'focus-trap-vue'],
  },

  resolve: {
    alias,
    // Sicherheit: garantiert eine einzige Vue-Instanz, falls Pakete lokal verlinkt sind
    dedupe: ['vue'],
    // Erweiterte Modul-Auflösung für bessere Kompatibilität
    extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue'],
    conditions: ['import', 'module', 'browser', 'default']
  },

  esbuild: {
    // Entferne console.log/debugger im Production-Build
    drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
  },

  build: {
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: process.env.NODE_ENV !== 'production',
    cssCodeSplit: true,
    minify: 'esbuild',
    cssMinify: true,
    target: 'es2020',
    manifest: true,
    rollupOptions: {
      input: getModuleEntries(),
      // Optionales CDN (nur wenn explizit aktiviert)
      external: USE_CDN ? ['vue', 'pinia', 'vue-i18n'] : [],
      output: {
        format: 'es',
        inlineDynamicImports: false,
        paths: USE_CDN
          ? { vue: CDN_VUE_ESM, pinia: CDN_PINIA_ESM, 'vue-i18n': CDN_I18N_ESM }
          : {},
        // Dateinamen
        chunkFileNames: 'chunks/[name]-[hash].js',
        entryFileNames: (chunkInfo) =>
          chunkInfo.name === 'bookando'
            ? 'core/bookando-style.js'
            : `${chunkInfo.name}/main.js`,
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name ?? ''
          if (/\.(woff2?|ttf|eot|otf)$/i.test(name)) return 'assets/fonts/[name]-[hash][extname]'
          if (/\.(png|jpe?g|gif|svg|webp|avif)$/i.test(name)) return 'assets/images/[name]-[hash][extname]'
          return 'assets/[name]-[hash][extname]'
        },
        // ✅ manualChunks für optimale Code-Splitting
        manualChunks: (id) => {
          // Vue vendor chunk (core framework)
          if (id.includes('node_modules/vue') ||
              id.includes('node_modules/@vue') ||
              id.includes('node_modules/pinia') ||
              id.includes('node_modules/vue-i18n')) {
            return 'vue-vendor'
          }

          // UI heavy dependencies
          if (id.includes('node_modules/@floating-ui') ||
              id.includes('node_modules/@headlessui')) {
            return 'ui-core'
          }

          // Heavy third-party libraries
          if (id.includes('node_modules/@tiptap')) {
            return 'tiptap'
          }

          if (id.includes('node_modules/gsap')) {
            return 'gsap'
          }

          // Shared utilities
          if (id.includes('node_modules/lodash') ||
              id.includes('node_modules/dayjs')) {
            return 'utils'
          }

          // Core Design System components (shared across modules)
          if (id.includes('/src/Core/Design/components/')) {
            return 'design-system'
          }

          // Core composables and utilities
          if (id.includes('/src/Core/Composables/') ||
              id.includes('/src/Core/Util/')) {
            return 'core-shared'
          }

          // Alle anderen node_modules in vendor
          if (id.includes('node_modules')) {
            return 'vendor'
          }
        },
      },
    },
  },
})
