import { defineConfig, devices } from '@playwright/test'

/**
 * Playwright E2E Test Configuration für Bookando WordPress Plugin
 *
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests/e2e',

  /* Maximum Zeit für einen Test */
  timeout: 30 * 1000,

  /* Test-Parallelisierung */
  fullyParallel: false,

  /* Bei CI-Fehler sofort abbrechen */
  forbidOnly: !!process.env.CI,

  /* Retries bei Fehlschlag */
  retries: process.env.CI ? 2 : 0,

  /* Anzahl paralleler Worker */
  workers: process.env.CI ? 1 : 1,

  /* Reporter */
  reporter: [
    ['html'],
    ['list'],
  ],

  /* Gemeinsame Einstellungen für alle Tests */
  use: {
    /* Base URL für WordPress */
    baseURL: process.env.WP_BASE_URL || 'http://bookando-site.local',

    /* Screenshot bei Fehler */
    screenshot: 'only-on-failure',

    /* Trace bei Fehler */
    trace: 'retain-on-failure',

    /* Video bei Fehler */
    video: 'retain-on-failure',
  },

  /* Test-Projekte für verschiedene Browser */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
})
