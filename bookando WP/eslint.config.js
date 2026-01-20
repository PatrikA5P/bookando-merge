// eslint.config.js
import js from '@eslint/js'
import vue from 'eslint-plugin-vue'
import vueParser from 'vue-eslint-parser'
import tsParser from '@typescript-eslint/parser'
import tsPlugin from '@typescript-eslint/eslint-plugin'
import globals from 'globals'

export default [
  // Globale Ignore-Liste
  {
    ignores: [
      '**/node_modules/**',
      '**/dist/**',
      '**/build/**',
      'var/**',
      'coverage/**',
      'bookandoGoogleAI/**',
      // Vendor/3rd-party Assets
      'src/**/assets/vendor/**',
      'src/Core/Design/assets/vendor/**',
      // Minifizierte Dateien
      '**/*.min.js',
      'scripts/old/**',
    ],
  },

  // Basis JS (Flat)
  js.configs.recommended,

  // Vue 3 (Flat)
  ...vue.configs['flat/recommended'],

  // -----------------------
  // Vue SFC + TypeScript
  // -----------------------
  {
    files: ['**/*.vue', '**/*.{ts,tsx}'],
    linterOptions: {
      reportUnusedDisableDirectives: 'off',
    },
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tsParser,
        ecmaVersion: 'latest',
        sourceType: 'module',
        extraFileExtensions: ['.vue'],
      },
      globals: {
        ...globals.browser,
        ...globals.node,
        // Vue Macros
        defineProps: 'readonly',
        defineEmits: 'readonly',
        defineExpose: 'readonly',
        withDefaults: 'readonly',
        defineModel: 'readonly',
        // Projektweite Globals
        BOOKANDO_VARS: 'readonly',
        // Optional: jQuery
        jQuery: 'readonly',
        $: 'readonly',
      },
    },
    plugins: { vue, '@typescript-eslint': tsPlugin },
    rules: {
      // Vue-Feintuning
      'vue/multi-word-component-names': 'off',
      'vue/no-v-html': 'off',
      'vue/require-default-prop': 'off',
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-self-closing': 'off',
      'vue/attributes-order': 'off',
      'vue/first-attribute-linebreak': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/multiline-html-element-content-newline': 'off',
      'vue/one-component-per-file': 'off',
      'vue/require-prop-types': 'off',
      'vue/html-closing-bracket-spacing': 'off',
      'vue/require-toggle-inside-transition': 'off',
      'vue/no-unused-vars': 'off',

      // Leere Blöcke: Catch ok, sonst Fehler
      'no-empty': ['error', { allowEmptyCatch: true }],

      // In TS/inside .vue bitte nur die TS-Var-Rule verwenden
      'no-unused-vars': 'off',
      '@typescript-eslint/no-unused-vars': 'off',

      // Typnamen wie DOM/TS-Typen sollen kein 'no-undef' auslösen
      'no-undef': 'off',

      // Entschärft – später schärfen möglich
      '@typescript-eslint/no-explicit-any': 'off',

      // NBSP & Co. strikt verhindern
      'no-irregular-whitespace': 'error',
    },
  },

  // -----------------------
  // Legacy/Prototype UI (bookandoGoogleAI) – ignore unused vars noise
  // -----------------------
  {
    files: ['bookandoGoogleAI/**/*.{ts,tsx,vue}'],
    rules: {
      '@typescript-eslint/no-unused-vars': 'off',
    },
  },

  // -----------------------
  // Node-Skripte: ESM (.js, .mjs)
  // (Dein package.json hat "type": "module" – daher .js hier Modul!)
  // -----------------------
  {
    files: ['scripts/**/*.{js,mjs}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: { ...globals.node },
    },
    rules: {
      'no-console': 'off',
      'no-unused-vars': 'off',
    },
  },

  // -----------------------
  // Node-Skripte: CJS (.cjs)
  // -----------------------
  {
    files: ['scripts/**/*.cjs'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'script',
      globals: { ...globals.node },
    },
    rules: {
      'no-console': 'off',
      'no-unused-vars': 'off',
    },
  },

  // (Optional) Tests/Vitest – falls du später willst:
  // {
  //   files: ['**/*.spec.{js,ts,vue}'],
  //   languageOptions: { globals: { ...globals.jest } },
  // },
]
