<?php

declare(strict_types=1);

namespace Bookando\Modules\DesignFrontend\Admin;

/**
 * Admin Interface for DesignFrontend Module
 */
class Admin
{
    public static function init(): void
    {
        // Direct registration on admin_menu hook (no waiting for bookando_register_module_menus)
        add_action('admin_menu', [self::class, 'registerMenus'], 100);
    }

    public static function registerMenus(): void
    {
        // Direct submenu registration in Bookando menu
        \Bookando\Core\Admin\Menu::addModuleSubmenu([
            'page_title'  => 'Design Frontend',
            'menu_title'  => 'Design Frontend',
            'capability'  => 'manage_bookando_designfrontend',
            'menu_slug'   => 'bookando_designfrontend',
            'module_slug' => 'design-frontend',
            'callback'    => [self::class, 'renderPage'],
            'icon_url'    => 'dashicons-admin-customizer',
            'position'    => 90
        ]);
    }

    public static function renderPage(): void
    {
        ?>
        <div class="wrap">
            <h1>Design Frontend</h1>
            <p>Shortcode Generator und Frontend-Portal-Konfiguration.</p>

            <h2>Coming Soon</h2>
            <p>Die vollständige Design Frontend Konfiguration wird in Kürze verfügbar sein.</p>
        </div>
        <?php
    }

    /**
     * OAuth Settings Page (simplified)
     */
    public static function renderOAuthSettings(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_frontend_auth_providers';

        // Handle form submission
        if ($_POST && check_admin_referer('bookando_oauth_settings')) {
            // Google
            if (isset($_POST['google_enabled'])) {
                $wpdb->update($table, [
                    'enabled' => !empty($_POST['google_enabled']) ? 1 : 0,
                    'client_id' => sanitize_text_field($_POST['google_client_id'] ?? ''),
                    'client_secret' => sanitize_text_field($_POST['google_client_secret'] ?? ''),
                    'redirect_uri' => sanitize_text_field($_POST['google_redirect_uri'] ?? ''),
                ], ['provider' => 'google']);
            }

            // Apple
            if (isset($_POST['apple_enabled'])) {
                $wpdb->update($table, [
                    'enabled' => !empty($_POST['apple_enabled']) ? 1 : 0,
                    'client_id' => sanitize_text_field($_POST['apple_client_id'] ?? ''),
                    'client_secret' => sanitize_text_field($_POST['apple_client_secret'] ?? ''),
                    'redirect_uri' => sanitize_text_field($_POST['apple_redirect_uri'] ?? ''),
                ], ['provider' => 'apple']);
            }

            echo '<div class="notice notice-success"><p>✅ Einstellungen gespeichert!</p></div>';
        }

        // Load current settings
        $google = $wpdb->get_row("SELECT * FROM {$table} WHERE provider = 'google'", ARRAY_A);
        $apple = $wpdb->get_row("SELECT * FROM {$table} WHERE provider = 'apple'", ARRAY_A);

        ?>
        <div class="wrap">
            <h1>🔐 OAuth Einstellungen</h1>
            <p class="description">Konfigurieren Sie Google und Apple Sign In für Ihre Frontend-Portale.</p>

            <form method="post" action="">
                <?php wp_nonce_field('bookando_oauth_settings'); ?>

                <h2>Google OAuth 2.0</h2>
                <table class="form-table">
                    <tr>
                        <th><label>Aktiviert</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="google_enabled" value="1" <?php checked($google['enabled'] ?? 0, 1); ?>>
                                Google Sign In aktivieren
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Client ID</label></th>
                        <td>
                            <input type="text" name="google_client_id" value="<?php echo esc_attr($google['client_id'] ?? ''); ?>" class="large-text">
                            <p class="description">Von Google Cloud Console</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Client Secret</label></th>
                        <td>
                            <input type="password" name="google_client_secret" value="<?php echo esc_attr($google['client_secret'] ?? ''); ?>" class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Redirect URI</label></th>
                        <td>
                            <input type="text" name="google_redirect_uri" value="<?php echo esc_attr($google['redirect_uri'] ?? home_url('/bookando/auth/google/callback')); ?>" class="large-text">
                            <p class="description">Standard: <?php echo home_url('/bookando/auth/google/callback'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 style="margin-top: 40px;">Apple Sign In</h2>
                <table class="form-table">
                    <tr>
                        <th><label>Aktiviert</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="apple_enabled" value="1" <?php checked($apple['enabled'] ?? 0, 1); ?>>
                                Apple Sign In aktivieren
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Service ID</label></th>
                        <td>
                            <input type="text" name="apple_client_id" value="<?php echo esc_attr($apple['client_id'] ?? ''); ?>" class="large-text">
                            <p class="description">Von Apple Developer Account</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Team ID / Key ID</label></th>
                        <td>
                            <input type="password" name="apple_client_secret" value="<?php echo esc_attr($apple['client_secret'] ?? ''); ?>" class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Redirect URI</label></th>
                        <td>
                            <input type="text" name="apple_redirect_uri" value="<?php echo esc_attr($apple['redirect_uri'] ?? home_url('/bookando/auth/apple/callback')); ?>" class="large-text">
                            <p class="description">Standard: <?php echo home_url('/bookando/auth/apple/callback'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Einstellungen speichern'); ?>
            </form>

            <hr style="margin: 40px 0;">

            <h2>📖 Setup-Anleitung</h2>

            <h3>Google OAuth einrichten:</h3>
            <ol>
                <li>Gehen Sie zu <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                <li>Erstellen Sie ein neues Projekt oder wählen Sie ein bestehendes aus</li>
                <li>Aktivieren Sie die "Google+ API"</li>
                <li>Gehen Sie zu "APIs & Services" → "Credentials"</li>
                <li>Erstellen Sie "OAuth 2.0 Client ID"</li>
                <li>Fügen Sie Ihre Redirect URI hinzu: <code><?php echo home_url('/bookando/auth/google/callback'); ?></code></li>
                <li>Kopieren Sie Client ID und Secret hierher</li>
            </ol>

            <h3>Apple Sign In einrichten:</h3>
            <ol>
                <li>Gehen Sie zu <a href="https://developer.apple.com/account" target="_blank">Apple Developer Account</a></li>
                <li>Erstellen Sie eine Service ID unter "Certificates, Identifiers & Profiles"</li>
                <li>Konfigurieren Sie "Sign in with Apple"</li>
                <li>Fügen Sie Ihre Redirect URI hinzu: <code><?php echo home_url('/bookando/auth/apple/callback'); ?></code></li>
                <li>Erstellen Sie einen Private Key</li>
                <li>Kopieren Sie Service ID und Key Details hierher</li>
            </ol>
        </div>
        <?php
    }
}
