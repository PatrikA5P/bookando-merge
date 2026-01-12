import { test, expect } from '@playwright/test';

/**
 * E2E-Test: Modul-Navigation ohne Redirect-Loop
 *
 * Testet, dass die Navigation zu Modulen ohne Endlosschleifen funktioniert.
 * Dieser Test hätte den Redirect-Loop-Bug (Commit 56b300c) erkannt.
 */

test.describe('Module Navigation', () => {
  // WordPress-Login vor jedem Test
  test.beforeEach(async ({ page }) => {
    // Umgebungsvariablen für Login
    const username = process.env.WP_ADMIN_USER || 'admin';
    const password = process.env.WP_ADMIN_PASSWORD || 'password';

    // Login zu WordPress Admin
    await page.goto('/wp-login.php');
    await page.fill('#user_login', username);
    await page.fill('#user_pass', password);
    await page.click('#wp-submit');

    // Warte auf Dashboard
    await page.waitForURL('**/wp-admin/**');
  });

  test('Navigate to Appointments module without redirect loop', async ({ page }) => {
    let redirectCount = 0;

    // Zähle Redirects
    page.on('response', (response) => {
      if (response.status() >= 300 && response.status() < 400) {
        redirectCount++;
      }
    });

    // Navigiere zum Appointments-Modul
    await page.goto('/wp-admin/admin.php?page=bookando_appointments');

    // Warte auf Netzwerk-Idle (keine weiteren Requests)
    await page.waitForLoadState('networkidle', { timeout: 10000 });

    // Assertions
    expect(redirectCount, 'Es sollten maximal 2 Redirects sein (Nonce-Refresh)').toBeLessThanOrEqual(2);
    expect(page.url()).toContain('bookando_appointments');
    expect(page.url()).toContain('_wpnonce');

    // Prüfe, dass die Seite vollständig geladen wurde
    const pageTitle = await page.title();
    expect(pageTitle).toBeTruthy();
  });

  test('Navigate to Customers module without redirect loop', async ({ page }) => {
    let redirectCount = 0;

    page.on('response', (response) => {
      if (response.status() >= 300 && response.status() < 400) {
        redirectCount++;
      }
    });

    await page.goto('/wp-admin/admin.php?page=bookando_customers');
    await page.waitForLoadState('networkidle', { timeout: 10000 });

    expect(redirectCount).toBeLessThanOrEqual(2);
    expect(page.url()).toContain('bookando_customers');
    expect(page.url()).toContain('_wpnonce');
  });

  test('Navigate to Employees module without redirect loop', async ({ page }) => {
    let redirectCount = 0;

    page.on('response', (response) => {
      if (response.status() >= 300 && response.status() < 400) {
        redirectCount++;
      }
    });

    await page.goto('/wp-admin/admin.php?page=bookando_employees');
    await page.waitForLoadState('networkidle', { timeout: 10000 });

    expect(redirectCount).toBeLessThanOrEqual(2);
    expect(page.url()).toContain('bookando_employees');
    expect(page.url()).toContain('_wpnonce');
  });

  test('Bookando main menu redirects to first submenu', async ({ page }) => {
    // Navigiere zum Hauptmenü
    await page.goto('/wp-admin/admin.php?page=bookando');

    // Sollte automatisch zum ersten Modul weiterleiten
    await page.waitForURL('**/admin.php?page=bookando_*', { timeout: 5000 });

    // Prüfe, dass wir zu einem Modul weitergeleitet wurden
    const url = page.url();
    expect(url).toMatch(/bookando_\w+/);
  });

  test('Nonce remains valid after page reload', async ({ page }) => {
    // Erste Navigation
    await page.goto('/wp-admin/admin.php?page=bookando_appointments');
    await page.waitForLoadState('networkidle');

    const firstUrl = page.url();
    const firstNonce = new URL(firstUrl).searchParams.get('_wpnonce');

    expect(firstNonce).toBeTruthy();

    // Reload
    await page.reload();
    await page.waitForLoadState('networkidle');

    const secondUrl = page.url();
    const secondNonce = new URL(secondUrl).searchParams.get('_wpnonce');

    // Nonce sollte nach Reload noch gültig sein (oder neu generiert)
    expect(secondNonce).toBeTruthy();
  });
});
