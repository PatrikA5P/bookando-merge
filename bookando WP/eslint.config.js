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

      // Leere Blöcke: Catch ok, sonst Fehler
      'no-empty': ['error', { allowEmptyCatch: true }],

      // In TS/inside .vue bitte nur die TS-Var-Rule verwenden
      'no-unused-vars': 'off',
      '@typescript-eslint/no-unused-vars': [
        'warn',
        { argsIgnorePattern: '^_', varsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' },
      ],

      // Typnamen wie DOM/TS-Typen sollen kein 'no-undef' auslösen
      'no-undef': 'off',

      // Entschärft – später schärfen möglich
      '@typescript-eslint/no-explicit-any': 'off',

      // NBSP & Co. strikt verhindern
      'no-irregular-whitespace': 'error',
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
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
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
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    },
  },

  // (Optional) Tests/Vitest – falls du später willst:
  // {
  //   files: ['**/*.spec.{js,ts,vue}'],
  //   languageOptions: { globals: { ...globals.jest } },
  // },
]
